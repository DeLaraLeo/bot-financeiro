<?php

require_once 'vendor/autoload.php';

use App\Service\AIService;
use Hyperf\Logger\LoggerFactory;

// Testar o AIService
$loggerFactory = new LoggerFactory();
$aiService = new AIService($loggerFactory);

echo "🧪 Testando AIService...\n";

try {
    $result = $aiService->classifyMessage('Gastei 25,50 no mercado');
    echo "✅ Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
