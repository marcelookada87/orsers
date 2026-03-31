<?php
class OrdemImagem extends Model
{
    protected string $table = 'ordens_imagens';

    public function porOrdem(int $ordemId): array
    {
        return $this->db->fetchAll(
            "SELECT oi.*, u.nome AS usuario_nome
             FROM `ordens_imagens` oi
             LEFT JOIN `usuarios` u ON u.id = oi.criado_por
             WHERE oi.ordem_id = ?
             ORDER BY oi.created_at ASC",
            [$ordemId]
        );
    }

    public function contarPorOrdem(int $ordemId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM `ordens_imagens` WHERE ordem_id = ?",
            [$ordemId]
        );
        return (int)($row['total'] ?? 0);
    }

    public function deletarArquivo(int $id): bool
    {
        $img = $this->find($id);
        if (!$img) return false;

        $path = UPLOAD_PATH . DIRECTORY_SEPARATOR . $img['arquivo'];
        if (file_exists($path)) {
            unlink($path);
        }
        $this->delete($id);
        return true;
    }
}
