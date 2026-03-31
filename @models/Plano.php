<?php

class Plano extends Model
{
    protected string $table = 'planos';

    public function findByCodigo(string $codigo): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM `planos` WHERE `codigo` = ? LIMIT 1',
            [$codigo]
        );
    }

    public function allAtivosOrdenados(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM `planos` WHERE `ativo` = 1 ORDER BY `ordem` ASC, `nome` ASC'
        );
    }

    /**
     * Planos que podem ser atribuídos no cadastro de usuário (exclui ilimitado, reservado a admin).
     */
    public function listarParaSelecaoUsuario(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `planos` WHERE `ativo` = 1 AND `codigo` <> 'ilimitado'
             ORDER BY `ordem` ASC, `nome` ASC"
        );
    }

    public function listarTodos(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM `planos` ORDER BY `ordem` ASC, `nome` ASC'
        );
    }

    public function countUsuariosPorPlano(int $planoId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS t FROM `usuarios` WHERE `plano_id` = ?',
            [$planoId]
        );
        return (int)($row['t'] ?? 0);
    }

    public function idPadraoFree(): int
    {
        $p = $this->findByCodigo('free');
        return $p ? (int)$p['id'] : 1;
    }
}
