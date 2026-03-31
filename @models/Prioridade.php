<?php
class Prioridade extends Model
{
    protected string $table = 'prioridades';

    public function listarAtivas(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `prioridades` WHERE ativo = 1 ORDER BY nivel ASC, id ASC"
        );
    }

    public function listarTodas(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `prioridades` ORDER BY nivel ASC, id ASC"
        );
    }

    public function listarParaFiltroOrdens(): array
    {
        return $this->listarTodas();
    }

    /** Select de nova OS: só ativas; se não houver, todas (base legada sem coluna ativo preenchida). */
    public function listarParaCriarOrdem(): array
    {
        $ativas = $this->listarAtivas();
        return count($ativas) > 0 ? $ativas : $this->listarTodas();
    }

    /** Primeiro id válido para criar OS (dropdown / default POST). */
    public function idPadraoCriacao(): ?int
    {
        $lista = $this->listarParaCriarOrdem();
        return isset($lista[0]['id']) ? (int)$lista[0]['id'] : null;
    }

    /** Edição: ativas + prioridade atual se estiver inativa. */
    public function listarParaEdicaoOrdem(?int $prioridadeIdAtual): array
    {
        $ativas = $this->listarAtivas();
        if (count($ativas) === 0) {
            return $this->listarTodas();
        }
        $ids = array_column($ativas, 'id');
        if ($prioridadeIdAtual && !in_array($prioridadeIdAtual, $ids, true)) {
            $atual = $this->find($prioridadeIdAtual);
            if ($atual) {
                $ativas[] = $atual;
                usort($ativas, static function (array $a, array $b): int {
                    return ($a['nivel'] <=> $b['nivel']) ?: ($a['id'] <=> $b['id']);
                });
            }
        }
        return $ativas;
    }

    /** Telegram /nova: prefere nível 3 entre ativas; senão primeira ativa; senão primeira linha. */
    public function idPadraoTelegram(): int
    {
        $ativas = $this->listarAtivas();
        if (count($ativas) === 0) {
            $todas = $this->listarTodas();
            return (int)($todas[0]['id'] ?? 0);
        }
        foreach ($ativas as $p) {
            if ((int)($p['nivel'] ?? 0) === 3) {
                return (int)$p['id'];
            }
        }
        return (int)$ativas[0]['id'];
    }
}
