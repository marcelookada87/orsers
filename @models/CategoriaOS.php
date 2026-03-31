<?php
class CategoriaOS extends Model
{
    protected string $table = 'categorias_os';

    public function allAtivas(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `categorias_os` WHERE ativo = 1 ORDER BY nome"
        );
    }

    public function listarTodas(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `categorias_os` ORDER BY nome ASC"
        );
    }
}
