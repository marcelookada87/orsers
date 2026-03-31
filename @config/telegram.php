<?php
/**
 * Configuração do Telegram Bot
 * Defina o token obtido no @BotFather
 * NUNCA versionar segredos reais em repositório público
 */

define('TELEGRAM_BOT_TOKEN',       'SEU_TOKEN_AQUI');
define('TELEGRAM_API_URL',         'https://api.telegram.org/bot');
define('TELEGRAM_DEFAULT_CHAT_ID', '');
define('TELEGRAM_PARSE_MODE',      'HTML');

// URL do webhook (ajuste para seu domínio com HTTPS)
define('TELEGRAM_WEBHOOK_URL', BASE_URL . '/telegram/webhook');
