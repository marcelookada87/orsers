<?php
/**
 * TelegramSender — envio de mensagens via API do Telegram
 */
class TelegramSender
{
    private string $token;
    private string $apiUrl;
    private string $parseMode;

    public function __construct()
    {
        $this->token     = defined('TELEGRAM_BOT_TOKEN')  ? TELEGRAM_BOT_TOKEN  : '';
        $this->apiUrl    = defined('TELEGRAM_API_URL')    ? TELEGRAM_API_URL    : 'https://api.telegram.org/bot';
        $this->parseMode = defined('TELEGRAM_PARSE_MODE') ? TELEGRAM_PARSE_MODE : 'HTML';
    }

    public function enviar(string $chatId, string $mensagem, array $extra = []): array
    {
        $payload = array_merge([
            'chat_id'    => $chatId,
            'text'       => $mensagem,
            'parse_mode' => $this->parseMode,
        ], $extra);
        return $this->call('sendMessage', $payload);
    }

    public function enviarComTeclado(string $chatId, string $mensagem, array $keyboard): array
    {
        return $this->enviar($chatId, $mensagem, [
            'reply_markup' => json_encode([
                'keyboard'        => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard'=> false,
            ]),
        ]);
    }

    public function getBotInfo(): array
    {
        return $this->call('getMe', []);
    }

    public function configurarWebhook(string $url): array
    {
        return $this->call('setWebhook', ['url' => $url]);
    }

    public function getWebhookInfo(): array
    {
        return $this->call('getWebhookInfo', []);
    }

    public function removerWebhook(): array
    {
        return $this->call('deleteWebhook', []);
    }

    public function getUpdates(int $offset = 0, int $limit = 100): array
    {
        return $this->call('getUpdates', ['offset' => $offset, 'limit' => $limit]);
    }

    public function substituirVariaveis(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string)$value, $template);
        }
        return $template;
    }

    private function call(string $method, array $payload): array
    {
        if (empty($this->token) || $this->token === 'SEU_TOKEN_AQUI') {
            return ['ok' => false, 'description' => 'Token do Telegram não configurado.'];
        }

        $url = $this->apiUrl . $this->token . '/' . $method;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['ok' => false, 'description' => "cURL error: {$err}"];
        }
        return json_decode($response, true) ?? ['ok' => false, 'description' => 'Resposta inválida'];
    }
}
