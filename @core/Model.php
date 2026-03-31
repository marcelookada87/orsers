<?php
/**
 * Model — base para todos os models
 */
abstract class Model
{
    protected Database $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): array|false
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            [$id]
        );
    }

    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->fetchAll("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
    }

    public function create(array $data): string
    {
        $cols   = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $places = implode(', ', array_fill(0, count($data), '?'));
        $this->db->query(
            "INSERT INTO `{$this->table}` ({$cols}) VALUES ({$places})",
            array_values($data)
        );
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): int
    {
        $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        return $this->db->execute(
            "UPDATE `{$this->table}` SET {$sets} WHERE `{$this->primaryKey}` = ?",
            [...array_values($data), $id]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        $row = $this->db->fetch($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    public function where(string $conditions, array $params = [], string $orderBy = ''): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE {$conditions}";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        return $this->db->fetchAll($sql, $params);
    }

    public function findWhere(string $conditions, array $params = []): array|false
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE {$conditions} LIMIT 1",
            $params
        );
    }
}
