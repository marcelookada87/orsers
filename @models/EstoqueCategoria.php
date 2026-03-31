<?php

class EstoqueCategoria extends Model
{
    protected string $table = 'estoque_categorias';

    /** @return list<array<string,mixed>> */
    public function listarAtivas(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM `estoque_categorias` WHERE `ativo` = 1 ORDER BY `nome` ASC'
        );
    }

    /** @return list<array<string,mixed>> */
    public function listarTodas(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM `estoque_categorias` ORDER BY `nome` ASC'
        );
    }

    /**
     * Lista com contagem de itens do catálogo por categoria (para tela de gestão).
     *
     * @return list<array<string,mixed>>
     */
    public function listarTodasComContagem(): array
    {
        $col = $this->db->fetch(
            "SELECT COUNT(*) AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_categorias' AND COLUMN_NAME = 'updated_at'"
        );
        if ((int)($col['t'] ?? 0) === 0) {
            $rows = $this->listarTodas();
            $out  = [];
            foreach ($rows as $r) {
                $r['itens_qtd']   = $this->contarItensVinculados((int)$r['id']);
                $r['updated_at'] = $r['created_at'] ?? null;
                $out[]           = $r;
            }

            return $out;
        }

        return $this->db->fetchAll(
            'SELECT c.`id`, c.`nome`, c.`descricao`, c.`ativo`, c.`created_at`, c.`updated_at`,
                    (SELECT COUNT(*) FROM `estoque_itens` i WHERE i.`categoria_id` = c.`id`) AS `itens_qtd`
             FROM `estoque_categorias` c
             ORDER BY c.`nome` ASC'
        );
    }

    public function contarItensVinculados(int $categoriaId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS t FROM `estoque_itens` WHERE `categoria_id` = ?',
            [$categoriaId]
        );

        return (int)($row['t'] ?? 0);
    }
}
