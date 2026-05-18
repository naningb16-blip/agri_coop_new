<?php
require_once __DIR__ . '/../../core/Model.php';

class UserModel extends Model {
    protected string $table = 'users';

    public function findByUsername(string $username): ?array {
        return $this->db->fetchOne(
            "SELECT u.*, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?",
            [$username], 's'
        );
    }

    public function findByEmail(string $email): ?array {
        return $this->db->fetchOne(
            "SELECT u.*, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?",
            [$email], 's'
        );
    }

    public function updateLastLogin(int $id): void {
        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$id], 'i');
    }

    public function getAllWithRoles(): array {
        return $this->db->fetchAll(
            "SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC"
        );
    }

    public function getAllRoles(): array {
        return $this->db->fetchAll("SELECT id, name, description FROM roles ORDER BY name");
    }

    public function findById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id], 'i');
    }

    public function createUser(array $data): void {
        $this->db->query(
            "INSERT INTO users (username, email, password, full_name, role_id, status) VALUES (?,?,?,?,?,?)",
            [$data['username'], $data['email'], $data['password'], $data['full_name'], $data['role_id'], $data['status']],
            'ssssis'
        );
    }

    public function updateUser(int $id, array $data): void {
        $fields = ['username=?', 'email=?', 'full_name=?', 'role_id=?', 'status=?'];
        $params = [$data['username'], $data['email'], $data['full_name'], $data['role_id'], $data['status']];
        $types  = 'sssis';

        if (isset($data['password'])) {
            $fields[] = 'password=?';
            $params[] = $data['password'];
            $types   .= 's';
        }

        $params[] = $id;
        $types   .= 'i';

        $this->db->query(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id=?",
            $params, $types
        );
    }

    public function deleteUser(int $id): void {
        $this->db->query("DELETE FROM users WHERE id = ?", [$id], 'i');
    }
}

