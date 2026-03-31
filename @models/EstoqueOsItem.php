<?php

class EstoqueOsItem extends Model
{
    protected string $table = 'estoque_os_itens';

    /** @return list<array<string,mixed>> */
    public function listarPorOrdem(int $ordemId): array
    {
        return $this->db->fetchAll(
            "SELECT oi.*, i.`nome` AS item_nome, i.`codigo` AS item_codigo, i.`unidade` AS item_unidade,
                    u.`nome` AS usuario_nome
             FROM `estoque_os_itens` oi
             INNER JOIN `estoque_itens` i ON i.`id` = oi.`item_id`
             INNER JOIN `usuarios` u ON u.`id` = oi.`usuario_id`
             WHERE oi.`ordem_id` = ?
             ORDER BY oi.`created_at` ASC",
            [$ordemId]
        );
    }

    public function findDaOrdem(int $osItemId, int $ordemId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM `estoque_os_itens` WHERE `id` = ? AND `ordem_id` = ? LIMIT 1',
            [$osItemId, $ordemId]
        );
    }
}
