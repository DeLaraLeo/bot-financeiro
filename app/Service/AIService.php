<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Guzzle\ClientFactory;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Google\Cloud\AIPlatform\V1\PredictionServiceClient;
use Google\Cloud\AIPlatform\V1\PredictRequest;
use Google\Cloud\AIPlatform\V1\Value;
use Google\Cloud\AIPlatform\V1\Instance;
use Hyperf\Config\Config;

class AIService
{
    public function __construct(
        private ClientFactory $clientFactory,
        private LoggerFactory $loggerFactory,
        private Config $config
    ) {
        $this->logger = $loggerFactory->get('ai-service');
    }

    private LoggerInterface $logger;

    public function classifyMessage(string $messageBody): array
    {
        try {
            return $this->classifyWithVertexAI($messageBody);
        } catch (\Exception $e) {
            $this->logger->error('Vertex AI classification failed, using fallback', [
                'error' => $e->getMessage(),
                'message' => $messageBody
            ]);
            return $this->classifyWithHeuristics($messageBody);
        }
    }

    private function classifyWithVertexAI(string $messageBody): array
    {
        $client = new PredictionServiceClient();
        
        $projectId = $this->config->get('vertex_ai.project_id', 'bot-financeiro-475714');
        $location = $this->config->get('vertex_ai.location', 'us-central1');
        $modelId = $this->config->get('vertex_ai.model_id', 'gemini-1.5-flash');
        
        $endpoint = $client->endpointName($projectId, $location, $modelId);
        
        $instance = new Instance();
        $instance->setContent($this->buildPrompt($messageBody));
        
        $request = new PredictRequest();
        $request->setEndpoint($endpoint);
        $request->setInstances([$instance]);
        
        $response = $client->predict($request);
        $predictions = $response->getPredictions();
        
        if (empty($predictions)) {
            throw new \Exception('No predictions returned from Vertex AI');
        }
        
        $prediction = $predictions[0];
        $content = $prediction->getContent();
        
        return $this->parseAIResponse($content);
    }

    private function buildPrompt(string $messageBody): string
    {
        return "Analise a seguinte mensagem de WhatsApp e classifique a intenção do usuário:

Mensagem: \"{$messageBody}\"

Categorias possíveis:
- expense_registration: usuário está registrando um gasto
- query_expenses: usuário quer consultar seus gastos
- list_categories: usuário quer listar categorias
- greeting: saudação ou pedido de ajuda

Responda APENAS em JSON no formato:
{
  \"intent\": \"categoria_detectada\",
  \"confidence\": 0.95,
  \"data\": {
    \"amount_cents\": 5000,
    \"description\": \"farmacia\",
    \"category_hint\": \"farmacia\"
  }
}

Se for expense_registration, extraia:
- amount_cents: valor em centavos (ex: 50.50 = 5050)
- description: descrição do gasto
- category_hint: categoria sugerida (mercado, farmacia, combustivel, etc)

Se não for expense_registration, data pode ser null.";
    }

    private function parseAIResponse(string $content): array
    {
        // Extract JSON from response
        preg_match('/\{.*\}/s', $content, $matches);
        if (empty($matches)) {
            throw new \Exception('No JSON found in AI response');
        }
        
        $json = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in AI response: ' . json_last_error_msg());
        }
        
        return $json;
    }

    private function classifyWithHeuristics(string $messageBody): array
    {
        $messageBody = strtolower(trim($messageBody));
        
        // Check if it's an expense registration
        if (preg_match('/\d+[,.]?\d*/', $messageBody) && (
            strpos($messageBody, 'gastei') !== false ||
            strpos($messageBody, 'gasto') !== false ||
            strpos($messageBody, 'paguei') !== false ||
            strpos($messageBody, 'comprei') !== false ||
            strpos($messageBody, 'reais') !== false
        )) {
            return $this->extractExpenseData($messageBody);
        }

        // Check if it's a query
        if (strpos($messageBody, 'resumo') !== false || 
            strpos($messageBody, 'gastos') !== false ||
            strpos($messageBody, 'quanto') !== false) {
            return [
                'intent' => 'query_expenses',
                'confidence' => 0.9
            ];
        }

        // Check if it's category request
        if (strpos($messageBody, 'categorias') !== false) {
            return [
                'intent' => 'list_categories',
                'confidence' => 0.9
            ];
        }

        // Default to greeting/help
        return [
            'intent' => 'greeting',
            'confidence' => 0.7
        ];
    }

    private function extractExpenseData(string $messageBody): array
    {
        // Extract amount
        preg_match('/(\d+[,.]?\d*)/', $messageBody, $matches);
        $amount = $matches[1] ?? null;
        
        if (!$amount) {
            return [
                'intent' => 'expense_registration',
                'confidence' => 0.3,
                'error' => 'Valor não encontrado'
            ];
        }

        // Convert to cents
        $amountCents = (int) (str_replace(',', '.', $amount) * 100);

        // Extract description/merchant
        $description = $this->extractDescription($messageBody);
        
        // Try to classify category
        $category = $this->classifyCategory($messageBody);

        return [
            'intent' => 'expense_registration',
            'confidence' => 0.9,
            'data' => [
                'amount_cents' => $amountCents,
                'description' => $description,
                'category_hint' => $category,
                'raw_message' => $messageBody
            ]
        ];
    }

    private function extractDescription(string $messageBody): ?string
    {
        // Remove amount and common words
        $cleaned = preg_replace('/\d+[,.]?\d*/', '', $messageBody);
        $cleaned = preg_replace('/\b(gastei|gasto|paguei|comprei|reais|com|na|no|em)\b/', '', $cleaned);
        $cleaned = trim($cleaned);
        
        return !empty($cleaned) ? $cleaned : null;
    }

    private function classifyCategory(string $messageBody): ?string
    {
        $categoryKeywords = [
            'farmacia' => ['farmacia', 'farmacia', 'remedio', 'medicamento'],
            'mercado' => ['mercado', 'supermercado', 'comida', 'alimento'],
            'combustivel' => ['combustivel', 'gasolina', 'posto', 'abastecer'],
            'restaurante' => ['restaurante', 'lanchonete', 'comer', 'almoço', 'jantar'],
            'transporte' => ['transporte', 'uber', 'taxi', 'onibus', 'metro'],
            'saude' => ['saude', 'medico', 'hospital', 'clinica'],
            'lazer' => ['lazer', 'cinema', 'show', 'festa', 'diversao'],
            'moradia' => ['moradia', 'aluguel', 'condominio', 'casa'],
            'educacao' => ['educacao', 'curso', 'escola', 'faculdade'],
            'assinaturas' => ['assinatura', 'netflix', 'spotify', 'plano']
        ];

        foreach ($categoryKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($messageBody, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return null;
    }

    public function generateResponse(array $aiResult, array $context = []): string
    {
        switch ($aiResult['intent']) {
            case 'expense_registration':
                if (isset($aiResult['error'])) {
                    return "❌ " . $aiResult['error'] . "\n\nPor favor, informe o valor do gasto. Exemplo: 'Gastei 25,50 no mercado'";
                }
                
                $data = $aiResult['data'];
                $amount = number_format($data['amount_cents'] / 100, 2, ',', '.');
                $description = $data['description'] ? " - {$data['description']}" : "";
                $category = $data['category_hint'] ? " ({$data['category_hint']})" : "";
                
                return "✅ Gasto registrado: R$ {$amount}{$description}{$category}";
                
            case 'query_expenses':
                return "📊 Buscando seus gastos...";
                
            case 'list_categories':
                return "📋 Listando categorias disponíveis...";
                
            case 'greeting':
            default:
                return "🤖 Olá! Sou seu assistente financeiro.\n\n" .
                       "Comandos disponíveis:\n" .
                       "• Registre gastos: 'Gastei 25,50 no mercado'\n" .
                       "• Veja resumo: 'resumo dos gastos'\n" .
                       "• Listar categorias: 'categorias'";
        }
    }
}
