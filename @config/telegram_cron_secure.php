<?php
/**
 * Configuração isolada do endpoint de cron do Telegram
 * Não depende do config.php completo — seguro para carregamento isolado
 */

define('TELEGRAM_CRON_API_ENABLED',           true);
define('TELEGRAM_CRON_API_TOKEN',             'GERE_UM_TOKEN_LONGO_COM_PELO_MENOS_64_CARACTERES_AQUI_0000000000000');
define('TELEGRAM_CRON_API_REQUIRE_HTTPS',     false); // true em produção
define('TELEGRAM_CRON_API_URL',               'http://localhost/orsers/api/cron/telegram');
define('TELEGRAM_CRON_API_MIN_INTERVAL_SECONDS', 30);
define('TELEGRAM_CRON_API_MAX_BODY_BYTES',    4096);
define('NOTIFICACAO_FILA_BATCH_LIMIT',        20);
define('NOTIFICACAO_FILA_RETENCAO_HORAS',     72);
define('TELEGRAM_CRON_API_ALLOWED_IPS',       ''); // IPs separados por vírgula, vazio = sem restrição

function telegramCronApiAllowedIps(): array
{
    $raw = defined('TELEGRAM_CRON_API_ALLOWED_IPS') ? TELEGRAM_CRON_API_ALLOWED_IPS : '';
    if (empty(trim($raw))) {
        return [];
    }
    return array_filter(array_map('trim', explode(',', $raw)));
}
