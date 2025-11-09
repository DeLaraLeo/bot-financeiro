<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CategoryRepository;
use function Hyperf\Support\env;

class AIService
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function classifyMessage(string $messageBody): array
    {
        return $this->classifyWithOpenAI($messageBody);
    }

    private function classifyWithOpenAI(string $messageBody): array
    {
        $today = date('Y-m-d');
        $categories = $this->getCategoriesForPrompt();
        
        $promptTemplate = \Hyperf\Config\config('ai_prompts.classification_prompt');
        $prompt = $promptTemplate($messageBody, $today, $categories);
        
        try {
            $response = $this->callOpenAI($prompt);
            return $this->parseAnyResponse($response);
        } catch (\Exception $e) {
            return [
                'intent' => 'greeting',
                'confidence' => 0.5,
                'data' => []
            ];
        }
    }
    
    private function parseAnyResponse(string $content): array
    {
        $json = $this->extractJsonFromResponse($content);
        
        if ($json && isset($json['intent'])) {
            return $json;
        }
        
        return [
            'intent' => 'greeting',
            'confidence' => 0.5,
            'data' => []
        ];
    }
    
    public function processNameMessage(string $messageBody): array
    {
        $promptTemplate = \Hyperf\Config\config('ai_prompts.name_extraction_prompt');
        $prompt = $promptTemplate($messageBody);
        
        try {
            $response = $this->callOpenAI($prompt);
            $decoded = $this->extractJsonFromResponse($response);
            
            if ($decoded && isset($decoded['isName']) && isset($decoded['name'])) {
                return [
                    'isName' => (bool)$decoded['isName'],
                    'name' => mb_convert_case(trim($decoded['name']), MB_CASE_TITLE, 'UTF-8')
                ];
            }
            
            return ['isName' => false, 'name' => trim($messageBody)];
            
        } catch (\Exception $e) {
            return ['isName' => false, 'name' => trim($messageBody)];
        }
    }

    private function callOpenAI(string $prompt): string
    {
        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured');
        }
        
        $client = \OpenAI::client($apiKey);
        
        $result = $client->completions()->create([
            'model' => 'gpt-4o-mini',
            'prompt' => $prompt,
            'max_tokens' => 150,
            'temperature' => 0.0,
        ]);
        
        return trim($result->choices[0]->text);
    }

    private function extractJsonFromResponse(string $response): ?array
    {
        $cleanResponse = trim($response);
        
        $json = json_decode($cleanResponse, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        
        if (preg_match('/\{.*\}/s', $cleanResponse, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        
        return null;
    }

    private function getCategoriesForPrompt(): string
    {
        try {
            $categories = $this->categoryRepository->findAll();
            
            $categoryList = [];
            foreach ($categories as $category) {
                $categoryList[] = "- ID {$category['id']}: {$category['name']} ({$category['code']})";
            }
            
            return implode("\n", $categoryList);
        } catch (\Exception $e) {
            return "- ID 1: Mercado (mercado)\n- ID 2: Farmácia (farmacia)\n- ID 3: Transporte (transporte)\n- ID 4: Restaurante (restaurante)\n- ID 5: Outros (outros)";
        }
    }

}