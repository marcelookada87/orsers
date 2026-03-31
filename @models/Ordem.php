<?php
class Ordem extends Model
{
    protected string $table = 'ordens_servico';

    public function findComDetalhes(int $id): array|false
    {
        return $this->db->fetch(
            "SELECT os.*,
                    p.nome AS prioridade_nome, p.cor AS prioridade_cor, p.nivel AS prioridade_nivel, p.sla_multiplicador,
                    c.nome AS categoria_nome, c.cor AS categoria_cor, c.sla_horas,
                    u.nome AS criador_nome,
                    r.nome AS responsavel_nome,
                    cl.nome_razao_social AS cliente_nome, cl.nome_fantasia AS cliente_fantasia,
                    cl.documento AS cliente_documento, cl.email AS cliente_email,
                    cl.telefone AS cliente_telefone, cl.celular AS cliente_celular,
                    cl.cidade AS cliente_cidade, cl.uf AS cliente_uf
             FROM `ordens_servico` os
             LEFT JOIN `prioridades` p     ON p.id = os.prioridade_id
             LEFT JOIN `categorias_os` c   ON c.id = os.categoria_id
             LEFT JOIN `usuarios` u        ON u.id = os.usuario_criador_id
             LEFT JOIN `usuarios` r        ON r.id = os.usuario_responsavel_id
             LEFT JOIN `clientes` cl       ON cl.id = os.cliente_id
             WHERE os.id = ?",
            [$id]
        );
    }

    public function findByNumero(string $numero): array|false
    {
        return $this->db->fetch(
            "SELECT os.*, p.nome AS prioridade_nome, p.cor AS prioridade_cor, p.sla_multiplicador,
                    c.nome AS categoria_nome, c.sla_horas,
                    cl.nome_razao_social AS cliente_nome, cl.nome_fantasia AS cliente_fantasia
             FROM `ordens_servico` os
             LEFT JOIN `prioridades` p   ON p.id = os.prioridade_id
             LEFT JOIN `categorias_os` c ON c.id = os.categoria_id
             LEFT JOIN `clientes` cl     ON cl.id = os.cliente_id
             WHERE os.numero = ?",
            [$numero]
        );
    }

    public function listarComFiltros(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['status'])) {
            $where[]  = 'os.status = ?';
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['prioridade_id'])) {
            $where[]  = 'os.prioridade_id = ?';
            $params[] = $filtros['prioridade_id'];
        }
        if (!empty($filtros['categoria_id'])) {
            $where[]  = 'os.categoria_id = ?';
            $params[] = $filtros['categoria_id'];
        }
        if (!empty($filtros['usuario_criador_id'])) {
            $where[]  = 'os.usuario_criador_id = ?';
            $params[] = $filtros['usuario_criador_id'];
        }
        if (!empty($filtros['usuario_responsavel_id'])) {
            $where[]  = 'os.usuario_responsavel_id = ?';
            $params[] = $filtros['usuario_responsavel_id'];
        }
        if (!empty($filtros['cliente_id'])) {
            $where[]  = 'os.cliente_id = ?';
            $params[] = (int)$filtros['cliente_id'];
        }
        if (!empty($filtros['busca'])) {
            $where[]  = '(os.titulo LIKE ? OR os.numero LIKE ? OR cl.nome_razao_social LIKE ? OR cl.nome_fantasia LIKE ? OR cl.documento LIKE ?)';
            $like     = '%' . $filtros['busca'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT os.*, p.nome AS prioridade_nome, p.cor AS prioridade_cor, p.nivel AS prioridade_nivel,
                    c.nome AS categoria_nome, c.cor AS categoria_cor, c.sla_horas, p.sla_multiplicador,
                    u.nome AS criador_nome, r.nome AS responsavel_nome,
                    cl.nome_razao_social AS cliente_nome, cl.nome_fantasia AS cliente_fantasia
             FROM `ordens_servico` os
             LEFT JOIN `prioridades` p   ON p.id = os.prioridade_id
             LEFT JOIN `categorias_os` c ON c.id = os.categoria_id
             LEFT JOIN `usuarios` u      ON u.id = os.usuario_criador_id
             LEFT JOIN `usuarios` r      ON r.id = os.usuario_responsavel_id
             LEFT JOIN `clientes` cl     ON cl.id = os.cliente_id
             WHERE {$whereStr}
             ORDER BY
                FIELD(os.status,'aberta','em_andamento','aguardando','finalizada','cancelada'),
                p.nivel ASC,
                os.sla_prazo ASC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function contarCriadasNoMesPorUsuario(int $userId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS t FROM `ordens_servico`
             WHERE `usuario_criador_id` = ?
             AND YEAR(`data_abertura`) = YEAR(CURDATE())
             AND MONTH(`data_abertura`) = MONTH(CURDATE())",
            [$userId]
        );
        return (int)($row['t'] ?? 0);
    }

    /** @return array{valor_servico: float, valor_pago: float} */
    public function somaFinanceiroPeriodo(string $dataIni, string $dataFim): array
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(`valor_servico`), 0) AS vs, COALESCE(SUM(`valor_pago`), 0) AS vp
             FROM `ordens_servico`
             WHERE DATE(`data_abertura`) BETWEEN ? AND ?",
            [$dataIni, $dataFim]
        );
        return [
            'valor_servico' => (float)($row['vs'] ?? 0),
            'valor_pago'    => (float)($row['vp'] ?? 0),
        ];
    }

    public function contarPorStatus(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS total FROM `ordens_servico` GROUP BY status"
        );
        $result = [
            'aberta' => 0, 'em_andamento' => 0,
            'aguardando' => 0, 'finalizada' => 0, 'cancelada' => 0
        ];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['total'];
        }
        return $result;
    }

    public function finalizadasHoje(): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM `ordens_servico`
             WHERE status='finalizada' AND DATE(data_finalizacao) = CURDATE()"
        );
        return (int)($row['total'] ?? 0);
    }

    public function slaVencidas(): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM `ordens_servico`
             WHERE sla_prazo < NOW() AND status NOT IN ('finalizada','cancelada')"
        );
        return (int)($row['total'] ?? 0);
    }

    public function abertas(int $usuarioId = 0): array
    {
        $where  = "os.status NOT IN ('finalizada','cancelada')";
        $params = [];
        if ($usuarioId) {
            $where  .= " AND (os.usuario_criador_id = ? OR os.usuario_responsavel_id = ?)";
            $params  = [$usuarioId, $usuarioId];
        }
        return $this->db->fetchAll(
            "SELECT os.*, p.nome AS prioridade_nome, p.cor AS prioridade_cor, p.sla_multiplicador,
                    c.nome AS categoria_nome, c.sla_horas
             FROM `ordens_servico` os
             LEFT JOIN `prioridades` p   ON p.id = os.prioridade_id
             LEFT JOIN `categorias_os` c ON c.id = os.categoria_id
             WHERE {$where}
             ORDER BY p.nivel ASC, os.sla_prazo ASC LIMIT 5",
            $params
        );
    }

    public function gerarNumero(): string
    {
        $ano = date('Y');
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM `ordens_servico` WHERE YEAR(data_abertura) = ?",
            [$ano]
        );
        $seq = (int)($row['total'] ?? 0) + 1;
        return 'OS-' . $ano . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function recentesParaDashboard(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT os.id, os.numero, os.titulo, os.status, os.sla_prazo, os.data_abertura,
                    p.nome AS prioridade_nome, p.cor AS prioridade_cor,
                    c.nome AS categoria_nome,
                    u.nome AS criador_nome,
                    cl.nome_razao_social AS cliente_nome
             FROM `ordens_servico` os
             LEFT JOIN `prioridades` p   ON p.id = os.prioridade_id
             LEFT JOIN `categorias_os` c ON c.id = os.categoria_id
             LEFT JOIN `usuarios` u      ON u.id = os.usuario_criador_id
             LEFT JOIN `clientes` cl     ON cl.id = os.cliente_id
             ORDER BY os.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Recalcula o prazo de SLA a partir da data/hora atual (mantém categoria e prioridade).
     */
    public function recalcularSlaApartirDeAgora(int $id): bool
    {
        $ordem = $this->findComDetalhes($id);
        if (!$ordem || in_array($ordem['status'], ['finalizada', 'cancelada'], true)) {
            return false;
        }
        $slaHoras = (float)($ordem['sla_horas'] ?? 24);
        $mult     = (float)($ordem['sla_multiplicador'] ?? 1.0);
        $novoPrazo = SLAHelper::calcPrazo(date('Y-m-d H:i:s'), $slaHoras, $mult);
        $prev      = $slaHoras * $mult;
        $n         = $this->update($id, [
            'sla_prazo'           => $novoPrazo,
            'sla_horas_previstas' => $prev,
        ]);
        return $n > 0;
    }

    public function paineISla(): array
    {
        return $this->db->fetchAll(
            "SELECT os.id, os.numero, os.titulo, os.status, os.sla_prazo, os.data_abertura,
                    os.usuario_criador_id, os.usuario_responsavel_id,
                    p.nome AS prioridade_nome, p.cor AS prioridade_cor, p.nivel AS prioridade_nivel,
                    c.nome AS categoria_nome, c.sla_horas, p.sla_multiplicador,
                    r.nome AS responsavel_nome,
                    cl.nome_razao_social AS cliente_nome, cl.nome_fantasia AS cliente_fantasia
             FROM `ordens_servico` os
             LEFT JOIN `prioridades` p   ON p.id = os.prioridade_id
             LEFT JOIN `categorias_os` c ON c.id = os.categoria_id
             LEFT JOIN `usuarios` r      ON r.id = os.usuario_responsavel_id
             LEFT JOIN `clientes` cl     ON cl.id = os.cliente_id
             WHERE os.status NOT IN ('finalizada','cancelada')
             ORDER BY
                CASE WHEN os.sla_prazo < NOW() THEN 0 ELSE 1 END ASC,
                os.sla_prazo ASC,
                p.nivel ASC"
        );
    }

    /** Títulos mais repetidos no sistema (sugestões no formulário). */
    public function titulosMaisUsados(int $limit = 12): array
    {
        $limit = max(1, min(30, $limit));
        $rows  = $this->db->fetchAll(
            "SELECT titulo FROM `ordens_servico`
             WHERE TRIM(titulo) <> ''
             GROUP BY titulo
             ORDER BY COUNT(*) DESC, titulo ASC
             LIMIT " . $limit
        );
        return array_column($rows, 'titulo');
    }

    /** Últimos títulos distintos já usados pelo usuário (mais recentes primeiro). */
    public function titulosRecentesDoUsuario(int $usuarioId, int $limit = 10): array
    {
        $limit = max(1, min(30, $limit));
        $rows  = $this->db->fetchAll(
            "SELECT titulo FROM `ordens_servico`
             WHERE usuario_criador_id = ? AND TRIM(titulo) <> ''
             GROUP BY titulo
             ORDER BY MAX(created_at) DESC
             LIMIT " . $limit,
            [$usuarioId]
        );
        return array_column($rows, 'titulo');
    }

    /** Frases iniciais quando ainda não há histórico suficiente no banco. */
    public static function titulosSugestaoPadrao(): array
    {
        return [
            'Conserto de tela',
            'Troca de tela',
            'Formatação',
            'Instalação de software',
            'Manutenção preventiva',
            'Visita técnica',
            'Configuração de rede',
            'Backup e recuperação',
            'Suporte remoto',
        ];
    }

    /** Formas de pagamento (valor interno => rótulo). */
    public static function formasPagamentoOpcoes(): array
    {
        return [
            ''              => '— Selecione —',
            'pix'           => 'PIX',
            'dinheiro'      => 'Dinheiro',
            'cartao_credito' => 'Cartão de crédito',
            'cartao_debito' => 'Cartão de débito',
            'boleto'        => 'Boleto',
            'transferencia' => 'Transferência bancária',
            'pendente'      => 'Pagamento pendente',
            'isento'        => 'Isento',
            'outros'        => 'Outros',
        ];
    }

    public static function labelFormaPagamento(?string $chave): string
    {
        if ($chave === null || $chave === '') {
            return '—';
        }
        $op = self::formasPagamentoOpcoes();
        return $op[$chave] ?? $chave;
    }

    public static function formatarMoeda(?float $valor): string
    {
        if ($valor === null) {
            return '—';
        }
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}
