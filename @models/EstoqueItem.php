<?php

class EstoqueItem extends Model
{
    protected string $table = 'estoque_itens';

    public function findByCodigo(string $codigo): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM `estoque_itens` WHERE `codigo` = ? LIMIT 1',
            [$codigo]
        );
    }

    /** @return list<array<string,mixed>> */
    public function listarAtivos(): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, c.`nome` AS categoria_nome
             FROM `estoque_itens` i
             LEFT JOIN `estoque_categorias` c ON c.`id` = i.`categoria_id`
             WHERE i.`ativo` = 1
             ORDER BY i.`nome` ASC"
        );
    }

    /** @return list<array<string,mixed>> */
    public function listarTodosComCategoria(): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, c.`nome` AS categoria_nome
             FROM `estoque_itens` i
             LEFT JOIN `estoque_categorias` c ON c.`id` = i.`categoria_id`
             ORDER BY i.`nome` ASC"
        );
    }

    /** @return list<array<string,mixed>> */
    public function buscarAtivosParaAutocomplete(string $q, int $limite = 40): array
    {
        $q = trim($q);
        if ($q === '') {
            return $this->db->fetchAll(
                "SELECT `id`, `codigo`, `nome`, `unidade` FROM `estoque_itens`
                 WHERE `ativo` = 1 ORDER BY `nome` ASC LIMIT " . (int)$limite
            );
        }
        $like = '%' . $q . '%';

        return $this->db->fetchAll(
            "SELECT `id`, `codigo`, `nome`, `unidade` FROM `estoque_itens`
             WHERE `ativo` = 1 AND (`nome` LIKE ? OR `codigo` LIKE ?)
             ORDER BY `nome` ASC LIMIT " . (int)$limite,
            [$like, $like]
        );
    }

    /**
     * Prefixo para sugestão de código: A–Z, 0–9 e . _ -
     * Remove o que for inválido; vazio ou só separadores vira ITEM.
     */
    public static function normalizarTagCodigoItem(string $raw): string
    {
        $s = strtoupper(preg_replace('/[^A-Za-z0-9._-]/', '', $raw) ?? '');
        if ($s === '') {
            return 'ITEM';
        }
        if (strlen($s) > 16) {
            $s = substr($s, 0, 16);
        }

        return $s;
    }

    /**
     * Próximo código sugerido: {tag do usuário ou ITEM}{sequência 4 dígitos com base no maior id do catálogo}.
     */
    public function proximoCodigoSugerido(?int $usuarioId = null): string
    {
        $prefix = 'ITEM';
        if ($usuarioId !== null && $usuarioId > 0) {
            $cfgModel = new UsuarioConfiguracao();
            $salva    = $cfgModel->getValorCodigoItemTagOuLegado($usuarioId);
            $raw      = $salva !== null && $salva !== '' ? $salva : 'ITEM';
            $prefix   = self::normalizarTagCodigoItem($raw);
        }

        $row = $this->db->fetch('SELECT COALESCE(MAX(`id`), 0) AS m FROM `estoque_itens`');
        $n   = (int)($row['m'] ?? 0) + 1;

        return $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
    }
}
