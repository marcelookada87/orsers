<?php

class EstoqueMovimentacao extends Model
{
    protected string $table = 'estoque_movimentacoes';

    public function registrar(array $data): string
    {
        return $this->create($data);
    }

    /**
     * @param array{type?:string,item_id?:int,data_ini?:string,data_fim?:string} $filtros
     * @return list<array<string,mixed>>
     */
    public function listarPorUsuario(int $usuarioId, array $filtros = []): array
    {
        $sql = "SELECT m.*, i.`nome` AS item_nome, i.`codigo` AS item_codigo, i.`unidade` AS item_unidade
                FROM `estoque_movimentacoes` m
                INNER JOIN `estoque_itens` i ON i.`id` = m.`item_id`
                WHERE m.`usuario_id` = ?";
        $params = [$usuarioId];
        if (!empty($filtros['tipo'])) {
            $sql .= ' AND m.`tipo` = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['item_id'])) {
            $sql .= ' AND m.`item_id` = ?';
            $params[] = (int)$filtros['item_id'];
        }
        if (!empty($filtros['data_ini'])) {
            $sql .= ' AND m.`created_at` >= ?';
            $params[] = $filtros['data_ini'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $sql .= ' AND m.`created_at` <= ?';
            $params[] = $filtros['data_fim'] . ' 23:59:59';
        }
        $sql .= ' ORDER BY m.`created_at` DESC, m.`id` DESC';

        return $this->db->fetchAll($sql, $params);
    }

    /** @return list<array<string,mixed>> */
    public function listarPorOrdem(int $ordemId): array
    {
        return $this->db->fetchAll(
            "SELECT m.*, i.`nome` AS item_nome, i.`codigo` AS item_codigo
             FROM `estoque_movimentacoes` m
             INNER JOIN `estoque_itens` i ON i.`id` = m.`item_id`
             WHERE m.`referencia_tipo` = 'os' AND m.`referencia_id` = ?
             ORDER BY m.`created_at` ASC",
            [$ordemId]
        );
    }
}
