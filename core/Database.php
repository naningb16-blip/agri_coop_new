<?php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        require_once __DIR__ . '/../config/database.php';

        $this->conn = mysqli_init();

        // Use SSL if a CA cert path is provided (e.g. Aiven)
        if (DB_SSL_CA && file_exists(DB_SSL_CA)) {
            mysqli_ssl_set($this->conn, null, null, DB_SSL_CA, null, null);
        }

        $connected = mysqli_real_connect(
            $this->conn,
            DB_HOST, DB_USER, DB_PASS, DB_NAME,
            DB_PORT,
            null,
            DB_SSL_CA ? MYSQLI_CLIENT_SSL : 0
        );

        if (!$connected || $this->conn->connect_error) {
            die(json_encode(['error' => 'DB connection failed: ' . $this->conn->connect_error]));
        }

        $this->conn->set_charset('utf8mb4');
        $this->conn->query("SET time_zone = '+08:00'");
    }

    public static function getInstance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function getConnection(): mysqli { return $this->conn; }

    public function query(string $sql, array $params = [], string $types = ''): mysqli_result|bool {
        if (empty($params)) return $this->conn->query($sql);
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        if ($params) $stmt->bind_param($types ?: str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        return $stmt->get_result() ?: true;
    }

    public function fetchAll(string $sql, array $params = [], string $types = ''): array {
        $result = $this->query($sql, $params, $types);
        return ($result instanceof mysqli_result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function fetchOne(string $sql, array $params = [], string $types = ''): ?array {
        $result = $this->query($sql, $params, $types);
        return ($result instanceof mysqli_result) ? ($result->fetch_assoc() ?: null) : null;
    }

    public function insert(string $sql, array $params = [], string $types = ''): int {
        $this->query($sql, $params, $types);
        return $this->conn->insert_id;
    }

    public function lastInsertId(): int { return $this->conn->insert_id; }
    public function escape(string $val): string { return $this->conn->real_escape_string($val); }
}
