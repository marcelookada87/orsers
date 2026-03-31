<?php
class OrdemHistorico extends Model
{
    protected string $table = 'ordens_historico';

    public function registrar(int $ordemId, int $usuarioId, string $acao, string $descricao, array $dados = []): void
    {
        $this->create([
            'ordem_id'   => $ordemId,
            'usuario_id' => $usuarioId,
            'acao'       => $acao,
            'descricao'  => $descricao,
            'dados_json' => empty($dados) ? null : json_encode($dados, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function porOrdem(int $ordemId): array
    {
        return $this->db->fetchAll(
            "SELECT h.*, u.nome AS usuario_nome
             FROM `ordens_historico` h
             LEFT JOIN `usuarios` u ON u.id = h.usuario_id
             WHERE h.ordem_id = ?
             ORDER BY h.created_at DESC",
            [$ordemId]
        );
    }

    public static function iconeAcao(string $acao): string
    {
        return match ($acao) {
            'criacao'      => '🆕',
            'alteracao'    => '✏️',
            'status'       => '🔄',
            'comentario'   => '💬',
            'imagem'       => '🖼️',
            'finalizacao'  => '✅',
            'reabertura'   => '🔓',
            'cancelamento' => '❌',
            'sla'          => '⏱️',
            default        => '📋',
        };
    }
}
