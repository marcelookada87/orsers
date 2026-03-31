<?php
class TelegramCronController extends Controller
{
    private TelegramSender $telegram;
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->telegram  = new TelegramSender();
        $this->userModel = new User();
    }

    public function processar(): void
    {
        if (!defined('TELEGRAM_CRON_API_ENABLED') || !TELEGRAM_CRON_API_ENABLED) {
            $this->json(['error' => 'Desabilitado'], 503);
        }

        if (TELEGRAM_CRON_API_REQUIRE_HTTPS && empty($_SERVER['HTTPS'])) {
            $this->json(['error' => 'HTTPS obrigatório'], 403);
        }

        $ips = telegramCronApiAllowedIps();
        if (!empty($ips) && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $ips, true)) {
            $this->json(['error' => 'IP não autorizado'], 403);
        }

        $token  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token2 = $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
        $bearer = ltrim(str_replace('Bearer', '', $token));
        if (trim($bearer) !== TELEGRAM_CRON_API_TOKEN && $token2 !== TELEGRAM_CRON_API_TOKEN) {
            $this->json(['error' => 'Token inválido'], 401);
        }

        $enviados = 0;
        $erros    = 0;
        $limit    = defined('NOTIFICACAO_FILA_BATCH_LIMIT') ? NOTIFICACAO_FILA_BATCH_LIMIT : 20;

        $fila = $this->db->fetchAll(
            "SELECT f.*, u.telegram_chat_id FROM notificacoes_fila f
             LEFT JOIN usuarios u ON u.id = f.destinatario_id
             WHERE f.status='pendente' AND f.tentativas < 3 AND f.canal='telegram'
             ORDER BY f.id ASC LIMIT ?",
            [$limit]
        );

        foreach ($fila as $item) {
            if (empty($item['telegram_chat_id'])) {
                $this->db->execute(
                    "UPDATE notificacoes_fila SET status='erro' WHERE id=?",
                    [$item['id']]
                );
                $erros++;
                continue;
            }

            $result = $this->telegram->enviar($item['telegram_chat_id'], $item['mensagem']);

            if ($result['ok'] ?? false) {
                $this->db->execute(
                    "UPDATE notificacoes_fila SET status='enviado', enviado_at=NOW() WHERE id=?",
                    [$item['id']]
                );
                $enviados++;
            } else {
                $this->db->execute(
                    "UPDATE notificacoes_fila SET tentativas=tentativas+1 WHERE id=?",
                    [$item['id']]
                );
                $erros++;
            }
        }

        // Limpeza de registros antigos
        if (defined('NOTIFICACAO_FILA_RETENCAO_HORAS')) {
            $this->db->execute(
                "DELETE FROM notificacoes_fila WHERE status='enviado' AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)",
                [NOTIFICACAO_FILA_RETENCAO_HORAS]
            );
        }

        $this->json(['ok' => true, 'enviados' => $enviados, 'erros' => $erros]);
    }

    public function ping(): void
    {
        $this->json(['ok' => true, 'timestamp' => date('c')]);
    }
}
