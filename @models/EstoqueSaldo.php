<?php

/**
 * Saldos por usuário/item — operações sensíveis usam transação e SELECT … FOR UPDATE.
 */
class EstoqueSaldo extends Model
{
    protected string $table = 'estoque_saldo';

    public static function parseQuantidade(mixed $raw): float
    {
        if (is_numeric($raw)) {
            $f = (float)$raw;
        } else {
            $s = str_replace(',', '.', trim((string)$raw));
            $f = (float)$s;
        }
        if (!is_finite($f) || $f <= 0) {
            throw new InvalidArgumentException('Quantidade deve ser um número positivo.');
        }

        return round($f, 3);
    }

    /** Saldo atual (sem lock). */
    public function getQuantidade(int $usuarioId, int $itemId): float
    {
        $row = $this->db->fetch(
            'SELECT `quantidade` FROM `estoque_saldo` WHERE `usuario_id` = ? AND `item_id` = ? LIMIT 1',
            [$usuarioId, $itemId]
        );
        if (!$row) {
            return 0.0;
        }

        return round((float)$row['quantidade'], 3);
    }

    /** @return list<array<string,mixed>> */
    public function getSaldoUsuario(int $usuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT s.*, i.`nome` AS item_nome, i.`codigo` AS item_codigo, i.`unidade` AS item_unidade,
                    i.`ativo` AS item_ativo, c.`nome` AS categoria_nome
             FROM `estoque_saldo` s
             INNER JOIN `estoque_itens` i ON i.`id` = s.`item_id`
             LEFT JOIN `estoque_categorias` c ON c.`id` = i.`categoria_id`
             WHERE s.`usuario_id` = ?
             ORDER BY i.`nome` ASC",
            [$usuarioId]
        );
    }

    /** Quantidade de tipos de item com estoque > 0. */
    public function contarTiposComEstoquePositivo(int $usuarioId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS t FROM `estoque_saldo` WHERE `usuario_id` = ? AND `quantidade` > 0",
            [$usuarioId]
        );

        return (int)($row['t'] ?? 0);
    }

    /**
     * Linhas com mínimo configurado e saldo no ou abaixo do mínimo (alerta no topo).
     */
    public function contarAlertasEstoqueMinimo(int $usuarioId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS t FROM `estoque_saldo` s
             INNER JOIN `estoque_itens` i ON i.`id` = s.`item_id`
             WHERE s.`usuario_id` = ?
               AND s.`quantidade_minima` > 0
               AND s.`quantidade` <= s.`quantidade_minima`',
            [$usuarioId]
        );

        return (int)($row['t'] ?? 0);
    }

    /**
     * Itens com mínimo configurado e saldo no ou abaixo do mínimo (texto no topo).
     *
     * @return list<array{item_codigo:string,item_nome:string}>
     */
    public function listarAlertasEstoqueMinimo(int $usuarioId): array
    {
        return $this->db->fetchAll(
            'SELECT i.`codigo` AS `item_codigo`, i.`nome` AS `item_nome`
             FROM `estoque_saldo` s
             INNER JOIN `estoque_itens` i ON i.`id` = s.`item_id`
             WHERE s.`usuario_id` = ?
               AND s.`quantidade_minima` > 0
               AND s.`quantidade` <= s.`quantidade_minima`
             ORDER BY i.`nome` ASC',
            [$usuarioId]
        );
    }

    /**
     * Itens com saldo > 0 para lançamento na OS (JSON / select).
     *
     * @return list<array<string,mixed>>
     */
    public function listarComSaldoParaUsuario(int $usuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT s.`item_id`, s.`quantidade`, s.`quantidade_minima`, i.`nome`, i.`codigo`, i.`unidade`
             FROM `estoque_saldo` s
             INNER JOIN `estoque_itens` i ON i.`id` = s.`item_id` AND i.`ativo` = 1
             WHERE s.`usuario_id` = ? AND s.`quantidade` > 0
             ORDER BY i.`nome` ASC",
            [$usuarioId]
        );
    }

    private function saldoRowForUpdate(int $usuarioId, int $itemId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM `estoque_saldo` WHERE `usuario_id` = ? AND `item_id` = ? FOR UPDATE',
            [$usuarioId, $itemId]
        );
    }

    /**
     * Entrada manual de estoque (ou ajuste positivo).
     *
     * @return int ID da movimentação criada
     */
    public function executarEntrada(
        int $usuarioId,
        int $itemId,
        float $quantidade,
        string $referenciaTipo,
        ?int $referenciaId,
        ?string $observacao
    ): int {
        $q = round($quantidade, 3);
        if ($q <= 0) {
            throw new InvalidArgumentException('Quantidade inválida.');
        }

        $movModel = new EstoqueMovimentacao();
        $this->db->beginTransaction();
        try {
            $row = $this->saldoRowForUpdate($usuarioId, $itemId);
            $anterior = $row ? round((float)$row['quantidade'], 3) : 0.0;

            if ($anterior <= 0 && !LimiteConta::estoquePodeAdicionarNovoTipo($usuarioId, $itemId)) {
                throw new RuntimeException('Limite de tipos de itens no estoque atingido para este usuário.');
            }

            $posterior = round($anterior + $q, 3);

            if ($row) {
                $this->db->execute(
                    'UPDATE `estoque_saldo` SET `quantidade` = ? WHERE `id` = ?',
                    [$posterior, $row['id']]
                );
            } else {
                $this->db->execute(
                    'INSERT INTO `estoque_saldo` (`usuario_id`, `item_id`, `quantidade`, `quantidade_minima`)
                     VALUES (?, ?, ?, 0)',
                    [$usuarioId, $itemId, $posterior]
                );
            }

            $movModel->registrar([
                'usuario_id'      => $usuarioId,
                'item_id'         => $itemId,
                'tipo'            => 'entrada',
                'quantidade'      => $q,
                'saldo_anterior'  => $anterior,
                'saldo_posterior' => $posterior,
                'referencia_tipo' => $referenciaTipo,
                'referencia_id'   => $referenciaId,
                'observacao'      => $observacao ? mb_substr($observacao, 0, 500) : null,
            ]);
            $mid = (int)$this->db->lastInsertId();
            $this->db->commit();

            return $mid;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Débito para OS: atualiza saldo, movimentação saída, linha em estoque_os_itens.
     *
     * @return array{os_item_id:int,movimentacao_id:int}
     */
    public function executarSaidaParaOs(
        int $usuarioId,
        int $ordemId,
        int $itemId,
        float $quantidade,
        ?string $observacao,
        bool $permitirSaldoNegativo = false
    ): array {
        $q = round($quantidade, 3);
        if ($q <= 0) {
            throw new InvalidArgumentException('Quantidade inválida.');
        }

        $movModel = new EstoqueMovimentacao();
        $osItemModel = new EstoqueOsItem();
        $this->db->beginTransaction();
        try {
            $row = $this->saldoRowForUpdate($usuarioId, $itemId);
            if (!$row) {
                throw new RuntimeException('Sem saldo deste item.');
            }
            $anterior = round((float)$row['quantidade'], 3);
            if ($anterior + 1e-9 < $q) {
                if (!$permitirSaldoNegativo) {
                    throw new EstoqueSaldoInsuficienteException($anterior, $q);
                }
            }
            $posterior = round($anterior - $q, 3);

            $obsFinal = null;
            if ($observacao !== null && trim($observacao) !== '') {
                $obsFinal = mb_substr(trim($observacao), 0, 500);
            }
            if ($permitirSaldoNegativo && $posterior < -1e-9) {
                $tag = '[Saldo negativo autorizado] ';
                $obsFinal = $obsFinal !== null && $obsFinal !== ''
                    ? $tag . $obsFinal
                    : rtrim($tag);
                if (mb_strlen($obsFinal) > 500) {
                    $obsFinal = mb_substr($obsFinal, 0, 500);
                }
            }

            $this->db->execute(
                'UPDATE `estoque_saldo` SET `quantidade` = ? WHERE `id` = ?',
                [$posterior, $row['id']]
            );

            $movModel->registrar([
                'usuario_id'      => $usuarioId,
                'item_id'         => $itemId,
                'tipo'            => 'saida',
                'quantidade'      => $q,
                'saldo_anterior'  => $anterior,
                'saldo_posterior' => $posterior,
                'referencia_tipo' => 'os',
                'referencia_id'   => $ordemId,
                'observacao'      => $obsFinal,
            ]);
            $movId = (int)$this->db->lastInsertId();

            $osItemModel->create([
                'ordem_id'        => $ordemId,
                'item_id'         => $itemId,
                'usuario_id'      => $usuarioId,
                'quantidade'      => $q,
                'movimentacao_id' => $movId,
                'observacao'      => $obsFinal,
            ]);
            $osItemId = (int)$this->db->lastInsertId();

            $this->db->commit();

            return ['os_item_id' => $osItemId, 'movimentacao_id' => $movId];
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Remove lançamento na OS e devolve quantidade ao saldo.
     */
    public function executarDevolucaoOs(int $osItemId, int $ordemId, int $operadorUserId, bool $isAdmin): void
    {
        $movModel = new EstoqueMovimentacao();

        $this->db->beginTransaction();
        try {
            $oi = $this->db->fetch(
                'SELECT * FROM `estoque_os_itens` WHERE `id` = ? AND `ordem_id` = ? FOR UPDATE',
                [$osItemId, $ordemId]
            );
            if (!$oi) {
                throw new RuntimeException('Item não encontrado nesta OS.');
            }

            $usuarioId = (int)$oi['usuario_id'];
            $itemId    = (int)$oi['item_id'];
            $q         = round((float)$oi['quantidade'], 3);

            if (!$isAdmin && (int)$oi['usuario_id'] !== $operadorUserId) {
                throw new RuntimeException('Sem permissão para remover este lançamento.');
            }

            $row = $this->saldoRowForUpdate($usuarioId, $itemId);
            $anterior = $row ? round((float)$row['quantidade'], 3) : 0.0;
            $posterior = round($anterior + $q, 3);

            if ($row) {
                $this->db->execute(
                    'UPDATE `estoque_saldo` SET `quantidade` = ? WHERE `id` = ?',
                    [$posterior, $row['id']]
                );
            } else {
                $this->db->execute(
                    'INSERT INTO `estoque_saldo` (`usuario_id`, `item_id`, `quantidade`, `quantidade_minima`)
                     VALUES (?, ?, ?, 0)',
                    [$usuarioId, $itemId, $posterior]
                );
            }

            $movModel->registrar([
                'usuario_id'      => $usuarioId,
                'item_id'         => $itemId,
                'tipo'            => 'entrada',
                'quantidade'      => $q,
                'saldo_anterior'  => $anterior,
                'saldo_posterior' => $posterior,
                'referencia_tipo' => 'os',
                'referencia_id'   => $ordemId,
                'observacao'      => 'Devolução — remoção do material na OS',
            ]);

            $this->db->execute('DELETE FROM `estoque_os_itens` WHERE `id` = ?', [$osItemId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function atualizarMinimo(int $usuarioId, int $itemId, float $minimo): void
    {
        $minimo = max(0, round($minimo, 3));
        $this->db->execute(
            'UPDATE `estoque_saldo` SET `quantidade_minima` = ? WHERE `usuario_id` = ? AND `item_id` = ?',
            [$minimo, $usuarioId, $itemId]
        );
    }
}

class EstoqueSaldoInsuficienteException extends RuntimeException
{
    public function __construct(
        public readonly float $disponivel,
        public readonly float $solicitado,
    ) {
        parent::__construct('Saldo insuficiente.');
    }
}
