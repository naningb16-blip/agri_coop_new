<?php
abstract class Model {
    protected Database $db;
    protected string $table = '';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findAll(string $where = '', array $params = [], string $types = ''): array {
        $sql = "SELECT * FROM {$this->table}" . ($where ? " WHERE $where" : '');
        return $this->db->fetchAll($sql, $params, $types);
    }

    public function findById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM {$this->table} WHERE id = ?", [$id], 'i');
    }

    public function create(array $data): int {
        $cols = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $types = str_repeat('s', count($data));
        return $this->db->insert(
            "INSERT INTO {$this->table} ($cols) VALUES ($placeholders)",
            array_values($data), $types
        );
    }

    public function update(int $id, array $data): bool {
        $set = implode('=?,', array_keys($data)) . '=?';
        $types = str_repeat('s', count($data)) . 'i';
        $result = $this->db->query(
            "UPDATE {$this->table} SET $set WHERE id = ?",
            [...array_values($data), $id], $types
        );
        return (bool)$result;
    }

    public function delete(int $id): bool {
        return (bool)$this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id], 'i');
    }

    public function count(string $where = '', array $params = [], string $types = ''): int {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table}" . ($where ? " WHERE $where" : '');
        $row = $this->db->fetchOne($sql, $params, $types);
        return (int)($row['cnt'] ?? 0);
    }
}
