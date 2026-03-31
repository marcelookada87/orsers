<?php
class User extends Model
{
    protected string $table = 'usuarios';

    public function findByEmail(string $email): array|false
    {
        return $this->db->fetch(
            "SELECT * FROM `usuarios` WHERE email = ? AND ativo = 1 LIMIT 1",
            [$email]
        );
    }

    /**
     * @return array|false Inclui plano_codigo, plano_nome e limites do plano (prefixo plano_)
     */
    public function findWithPlano(int $id): array|false
    {
        return $this->db->fetch(
            "SELECT u.*,
                    p.`codigo` AS `plano_codigo`,
                    p.`nome`   AS `plano_nome`,
                    p.`max_imagens_por_os` AS `plano_max_imagens_por_os`,
                    p.`max_clientes`       AS `plano_max_clientes`,
                    p.`max_os_mes`         AS `plano_max_os_mes`
             FROM `usuarios` u
             LEFT JOIN `planos` p ON p.`id` = u.`plano_id`
             WHERE u.`id` = ? LIMIT 1",
            [$id]
        );
    }

    public function findByTelegramChatId(string $chatId): array|false
    {
        return $this->db->fetch(
            "SELECT * FROM `usuarios` WHERE telegram_chat_id = ? AND ativo = 1 LIMIT 1",
            [$chatId]
        );
    }

    public function findByTelegramToken(string $token): array|false
    {
        return $this->db->fetch(
            "SELECT * FROM `usuarios` WHERE telegram_token = ? AND ativo = 1 LIMIT 1",
            [$token]
        );
    }

    public function allAtivos(): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.nome, u.email, u.perfil, u.admin, u.plano_id,
                    u.max_imagens_por_os_override, u.max_clientes_override, u.max_os_mes_override,
                    u.telegram_ativo, u.ultimo_acesso,
                    u.estoque_ativo, u.estoque_limite_itens,
                    p.nome AS plano_nome, p.codigo AS plano_codigo
             FROM `usuarios` u
             LEFT JOIN `planos` p ON p.id = u.plano_id
             WHERE u.ativo = 1 ORDER BY u.nome"
        );
    }

    public function allTecnicos(): array
    {
        return $this->db->fetchAll(
            "SELECT id, nome FROM `usuarios` WHERE perfil IN ('admin','tecnico') AND ativo = 1 ORDER BY nome"
        );
    }

    public function updateUltimoAcesso(int $id): void
    {
        $this->db->execute(
            "UPDATE `usuarios` SET ultimo_acesso = NOW() WHERE id = ?",
            [$id]
        );
    }

    public function vincularTelegram(int $userId, string $chatId): void
    {
        $this->db->execute(
            "UPDATE `usuarios` SET telegram_chat_id = ?, telegram_ativo = 1, telegram_token = NULL WHERE id = ?",
            [$chatId, $userId]
        );
    }

    public function gerarTelegramToken(int $userId): string
    {
        $token = bin2hex(random_bytes(16));
        $this->db->execute(
            "UPDATE `usuarios` SET telegram_token = ? WHERE id = ?",
            [$token, $userId]
        );
        return $token;
    }

    public function hashSenha(string $senha): string
    {
        return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function verificarSenha(string $senha, string $hash): bool
    {
        return password_verify($senha, $hash);
    }

    /** @return list<array<string,mixed>> */
    public function relatorioUsoPlanos(): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.nome, u.email, u.admin, u.perfil, u.plano_id,
                    u.max_imagens_por_os_override, u.max_clientes_override, u.max_os_mes_override,
                    p.nome AS plano_nome, p.codigo AS plano_codigo,
                    p.max_imagens_por_os AS plano_max_imagens_por_os,
                    p.max_clientes AS plano_max_clientes,
                    p.max_os_mes AS plano_max_os_mes,
                    (SELECT COUNT(*) FROM `clientes` c
                     WHERE c.`ativo` = 1 AND c.`usuario_cadastro_id` = u.`id`) AS qtd_clientes,
                    (SELECT COUNT(*) FROM `ordens_servico` os
                     WHERE os.`usuario_criador_id` = u.`id`
                     AND YEAR(os.`data_abertura`) = YEAR(CURDATE())
                     AND MONTH(os.`data_abertura`) = MONTH(CURDATE())) AS os_mes_criadas
             FROM `usuarios` u
             LEFT JOIN `planos` p ON p.`id` = u.`plano_id`
             WHERE u.`ativo` = 1
             ORDER BY u.`nome` ASC"
        );
    }
}
