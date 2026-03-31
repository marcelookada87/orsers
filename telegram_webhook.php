<?php
/**
 * Telegram Webhook entry point
 * Alternativa ao routing via index.php (útil se rewrite não estiver ativo)
 */

if (isset($_GET['info'])) {
    echo 'Telegram Webhook — OS Manager<br>';
    echo 'Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . '<br>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

require_once __DIR__ . '/@config/config.php';

$controller = new TelegramWebhookController();
$controller->webhook();
