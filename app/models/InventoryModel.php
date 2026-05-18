<?php
require_once __DIR__ . '/../../core/Model.php';

class InventoryModel extends Model {
    protected string $table = 'inventory';

    // &#8369;8369;”€ Stock summary &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function getStockSummary(int $warehouseId = 0): array {
        $where = $warehouseId ? "AND i.warehouse_id = $warehouseId" : '';
        return $this->db->fetchAll(
            "SELECT i.*, p.name AS product_name, p.unit, p.reorder_level,
                    p.category, w.name AS warehouse_name,
                    CASE WHEN i.quantity <= p.reorder_level THEN 'low' ELSE 'ok' END AS stock_status
             FROM inventory i
             JOIN products p ON i.product_id = p.id
             JOIN warehouses w ON i.warehouse_id = w.id
             WHERE 1=1 $where
             ORDER BY p.name, w.name"
        );
    }

    // &#8369;8369;”€ Movements &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function getMovements(int $limit = 50, array $filters = []): array {
        $where  = '1=1';
        $params = [];
        $types  = '';
        if (!empty($filters['product_id']))   { $where .= ' AND sm.product_id=?';   $params[] = $filters['product_id'];   $types .= 'i'; }
        if (!empty($filters['warehouse_id'])) { $where .= ' AND sm.warehouse_id=?'; $params[] = $filters['warehouse_id']; $types .= 'i'; }
        if (!empty($filters['type']))         { $where .= ' AND sm.type=?';          $params[] = $filters['type'];         $types .= 's'; }
        $params[] = $limit; $types .= 'i';
        return $this->db->fetchAll(
            "SELECT sm.*, p.name AS product_name, w.name AS warehouse_name, u.full_name AS created_by_name
             FROM stock_movements sm
             JOIN products p ON sm.product_id = p.id
             JOIN warehouses w ON sm.warehouse_id = w.id
             LEFT JOIN users u ON sm.created_by = u.id
             WHERE $where ORDER BY sm.created_at DESC LIMIT ?",
            $params, $types
        );
    }

    public function addMovement(array $data): int {
        $id = $this->db->insert(
            "INSERT INTO stock_movements
                (product_id, warehouse_id, type, quantity, reference_type, reference_id, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?)",
            [$data['product_id'], $data['warehouse_id'], $data['type'], $data['quantity'],
             $data['reference_type'] ?? 'manual', $data['reference_id'] ?? null,
             $data['notes'] ?? '', $_SESSION['user_id'] ?? null],
            'iisdsiss'
        );

        $op = ($data['type'] === 'in' || $data['type'] === 'return') ? '+' : '-';
        $this->db->query(
            "INSERT INTO inventory (product_id, warehouse_id, quantity) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE quantity = GREATEST(0, quantity $op ?)",
            [$data['product_id'], $data['warehouse_id'], $data['quantity'], $data['quantity']],
            'iidd'
        );

        return $id;
    }

    // &#8369;8369;”€ Products &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function getProducts(): array {
        return $this->db->fetchAll("SELECT * FROM products ORDER BY name");
    }

    public function saveProduct(array $data, int $id = 0): bool|int {
        if ($id) {
            return $this->db->query(
                "UPDATE products SET name=?,category=?,unit=?,description=?,reorder_level=? WHERE id=?",
                [$data['name'], $data['category'], $data['unit'], $data['description'], $data['reorder_level'], $id],
                'ssssdi'
            );
        }
        return $this->db->insert(
            "INSERT INTO products (name,category,unit,description,reorder_level) VALUES (?,?,?,?,?)",
            [$data['name'], $data['category'], $data['unit'], $data['description'], $data['reorder_level']],
            'ssssd'
        );
    }

    public function deleteProduct(int $id): array {
        $used = $this->db->fetchOne("SELECT id FROM inventory WHERE product_id=? LIMIT 1", [$id], 'i');
        if ($used) return ['success' => false, 'message' => 'Product has stock records. Cannot delete.'];
        $this->db->query("DELETE FROM products WHERE id=?", [$id], 'i');
        return ['success' => true, 'message' => 'Product deleted.'];
    }

    // &#8369;8369;”€ Warehouses &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function getWarehouses(): array {
        return $this->db->fetchAll("SELECT * FROM warehouses ORDER BY name");
    }

    public function getWarehousesWithStats(): array {
        return $this->db->fetchAll(
            "SELECT w.*,
                    COUNT(DISTINCT i.product_id) AS product_count,
                    COALESCE(SUM(i.quantity), 0) AS total_stock
             FROM warehouses w
             LEFT JOIN inventory i ON i.warehouse_id = w.id
             GROUP BY w.id ORDER BY w.name"
        );
    }

    public function saveWarehouse(array $data, int $id = 0): bool|int {
        if ($id) {
            return $this->db->query(
                "UPDATE warehouses SET name=?,location=?,capacity=? WHERE id=?",
                [$data['name'], $data['location'], $data['capacity'], $id], 'ssdi'
            );
        }
        return $this->db->insert(
            "INSERT INTO warehouses (name,location,capacity) VALUES (?,?,?)",
            [$data['name'], $data['location'], $data['capacity']], 'ssd'
        );
    }

    public function deleteWarehouse(int $id): array {
        $used = $this->db->fetchOne("SELECT id FROM inventory WHERE warehouse_id=? LIMIT 1", [$id], 'i');
        if ($used) return ['success' => false, 'message' => 'Warehouse has stock records. Cannot delete.'];
        $this->db->query("DELETE FROM warehouses WHERE id=?", [$id], 'i');
        return ['success' => true, 'message' => 'Warehouse deleted.'];
    }

    // &#8369;8369;”€ Returns &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function getReturns(string $status = ''): array {
        $where  = $status ? "WHERE sr.status='$status'" : '';
        return $this->db->fetchAll(
            "SELECT sr.*, p.name AS product_name, p.unit, w.name AS warehouse_name,
                    u.full_name AS created_by_name, u2.full_name AS reviewed_by_name,
                    ar.id AS approval_request_id, ar.status AS approval_status,
                    ast.label AS approval_step_label
             FROM stock_returns sr
             JOIN products p ON sr.product_id = p.id
             JOIN warehouses w ON sr.warehouse_id = w.id
             JOIN users u ON sr.created_by = u.id
             LEFT JOIN users u2 ON sr.reviewed_by = u2.id
             LEFT JOIN approval_requests ar ON ar.reference_type='stock_return' AND ar.reference_id=sr.id
             LEFT JOIN approval_steps ast ON ast.request_id = ar.id AND ast.step_order = ar.current_step
             $where ORDER BY sr.created_at DESC"
        );
    }

    public function createReturn(array $data): int {
        return $this->db->insert(
            "INSERT INTO stock_returns
                (reference_type, reference_id, product_id, warehouse_id, quantity, reason, condition_type, created_by)
             VALUES (?,?,?,?,?,?,?,?)",
            [$data['reference_type'], $data['reference_id'] ?? 0,
             $data['product_id'], $data['warehouse_id'],
             $data['quantity'], $data['reason'] ?? '', $data['condition_type'] ?? 'good',
             $_SESSION['user_id']],
            'siiidssi'
        );
    }

    public function processReturn(int $id, string $action, int $userId): array {
        $ret = $this->db->fetchOne("SELECT * FROM stock_returns WHERE id=?", [$id], 'i');
        if (!$ret || $ret['status'] !== 'pending') return ['success' => false, 'message' => 'Invalid return.'];

        if ($action === 'restock') {
            $this->addMovement([
                'product_id'     => $ret['product_id'],
                'warehouse_id'   => $ret['warehouse_id'],
                'type'           => 'return',
                'quantity'       => $ret['quantity'],
                'reference_type' => 'return',
                'reference_id'   => $id,
                'notes'          => 'Return restocked: ' . ($ret['reason'] ?? ''),
            ]);
            $status = 'restocked';
        } else {
            $status = 'disposed';
        }

        $this->db->query(
            "UPDATE stock_returns SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?",
            [$status, $userId, $id], 'sii'
        );
        return ['success' => true, 'message' => "Return $status."];
    }

    // &#8369;8369;”€ Release requests &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function getReleaseRequests(string $status = '', string $department = '', int $userId = 0): array {
        $where = [];
        $params = [];
        $types = '';
        
        if ($status) {
            $where[] = "srr.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        if ($department) {
            $where[] = "srr.requesting_department = ?";
            $params[] = $department;
            $types .= 's';
        }
        
        if ($userId > 0) {
            $where[] = "srr.requested_by = ?";
            $params[] = $userId;
            $types .= 'i';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return $this->db->fetchAll(
            "SELECT srr.*, p.name AS product_name, p.unit, w.name AS warehouse_name,
                    u.full_name AS requested_by_name,
                    ar.id AS approval_request_id, ar.status AS approval_status,
                    acs.label AS approval_step_label
             FROM stock_release_requests srr
             JOIN products p ON srr.product_id = p.id
             JOIN warehouses w ON srr.warehouse_id = w.id
             JOIN users u ON srr.requested_by = u.id
             LEFT JOIN approval_requests ar ON ar.reference_type='stock_release' AND ar.reference_id=srr.id
             LEFT JOIN approval_steps acs ON acs.request_id=ar.id AND acs.step_order=ar.current_step
             $whereClause ORDER BY srr.created_at DESC",
            $params, $types
        );
    }

    // &#8369;8369;”€ Summary stats &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function getSummaryStats(): array {
        return $this->db->fetchOne(
            "SELECT
                (SELECT COUNT(*) FROM products) AS total_products,
                (SELECT COUNT(*) FROM warehouses) AS total_warehouses,
                (SELECT COUNT(*) FROM inventory WHERE quantity > 0) AS stocked_items,
                (SELECT COUNT(*) FROM inventory i JOIN products p ON i.product_id=p.id
                 WHERE i.quantity <= p.reorder_level AND i.quantity > 0) AS low_stock,
                (SELECT COUNT(*) FROM stock_returns WHERE status='pending') AS pending_returns,
                (SELECT COUNT(*) FROM stock_release_requests WHERE status='pending') AS pending_releases"
        );
    }
}

