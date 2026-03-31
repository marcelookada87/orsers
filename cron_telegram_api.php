<?php
/**
 * cron_telegram_api.php — CLI script para disparo da fila de notificações Telegram
 *
 * Uso:
 *   php C:\xampp\htdocs\orsers\cron_telegram_api.php
 *
 * Agendar no cron (Linux):
 *   * * * * * /usr/bin/php /var/www/orsers/cron_telegram_api.php >> /tmp/orsers_cron.log 2>&1
 */

require_once __DIR__ . '/@config/telegram_cron_secure.php';

$token = defined('TELEGRAM_CRON_API_TOKEN') ? TELEGRAM_CRON_API_TOKEN : '';

if (strlen($token) < 64) {
    echo "[ERRO] TELEGRAM_CRON_API_TOKEN deve ter no mínimo 64 caracteres.\n";
    exit(1);
}

$url = defined('TELEGRAM_CRON_API_URL') ? TELEGRAM_CRON_API_URL : '';
if (empty($url)) {
    echo "[ERRO] TELEGRAM_CRON_API_URL não configurada.\n";
    exit(1);
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => '{}',
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'X-CRON-TOKEN: ' . $token,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => defined('TELEGRAM_CRON_API_REQUIRE_HTTPS') && TELEGRAM_CRON_API_REQUIRE_HTTPS,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "[ERRO] cURL: {$err}\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] HTTP {$httpCode} — {$response}\n";
exit(0);
