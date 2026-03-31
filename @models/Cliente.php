<?php
class Cliente extends Model
{
    protected string $table = 'clientes';

    /**
     * @param int|null $usuarioCadastroId Se informado, só clientes cadastrados por esse usuário.
     */
    public function listarParaSelect(?int $usuarioCadastroId = null): array
    {
        if ($usuarioCadastroId !== null) {
            return $this->db->fetchAll(
                "SELECT id, nome_razao_social, nome_fantasia, documento
                 FROM `clientes` WHERE ativo = 1 AND `usuario_cadastro_id` = ?
                 ORDER BY nome_razao_social ASC",
                [$usuarioCadastroId]
            );
        }
        return $this->db->fetchAll(
            "SELECT id, nome_razao_social, nome_fantasia, documento
             FROM `clientes` WHERE ativo = 1 ORDER BY nome_razao_social ASC"
        );
    }

    public function contarAtivosPorUsuarioCadastro(int $usuarioId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS t FROM `clientes`
             WHERE `ativo` = 1 AND `usuario_cadastro_id` = ?",
            [$usuarioId]
        );
        return (int)($row['t'] ?? 0);
    }

    public function listarComFiltros(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];

        if (isset($filtros['ativo']) && $filtros['ativo'] !== '') {
            $where[]  = 'c.ativo = ?';
            $params[] = (int)$filtros['ativo'];
        }
        if (!empty($filtros['busca'])) {
            $like     = '%' . $filtros['busca'] . '%';
            $where[]  = '(c.nome_razao_social LIKE ? OR c.nome_fantasia LIKE ? OR c.documento LIKE ? OR c.email LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($filtros['usuario_cadastro_id'])) {
            $where[]  = 'c.usuario_cadastro_id = ?';
            $params[] = (int)$filtros['usuario_cadastro_id'];
        }

        $whereStr = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT c.* FROM `clientes` c WHERE {$whereStr} ORDER BY c.nome_razao_social ASC LIMIT ? OFFSET ?",
            $params
        );
    }

    public function contarComFiltros(array $filtros = []): int
    {
        $where  = ['1=1'];
        $params = [];

        if (isset($filtros['ativo']) && $filtros['ativo'] !== '') {
            $where[]  = 'c.ativo = ?';
            $params[] = (int)$filtros['ativo'];
        }
        if (!empty($filtros['busca'])) {
            $like     = '%' . $filtros['busca'] . '%';
            $where[]  = '(c.nome_razao_social LIKE ? OR c.nome_fantasia LIKE ? OR c.documento LIKE ? OR c.email LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($filtros['usuario_cadastro_id'])) {
            $where[]  = 'c.usuario_cadastro_id = ?';
            $params[] = (int)$filtros['usuario_cadastro_id'];
        }

        $whereStr = implode(' AND ', $where);
        $row = $this->db->fetch("SELECT COUNT(*) AS total FROM `clientes` c WHERE {$whereStr}", $params);
        return (int)($row['total'] ?? 0);
    }

    public static function rotuloExibicao(array $c): string
    {
        $nome = $c['nome_razao_social'] ?? '';
        if (!empty($c['nome_fantasia'])) {
            $nome .= ' — ' . $c['nome_fantasia'];
        }
        if (!empty($c['documento'])) {
            $nome .= ' (' . $c['documento'] . ')';
        }
        return $nome;
    }
}
