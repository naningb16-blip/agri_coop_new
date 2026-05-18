<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/InventoryModel.php';
require_once __DIR__ . '/../models/ApprovalModel.php';

class InventoryController extends Controller {
    private InventoryModel $model;
    private ApprovalModel  $approvalModel;

    public function __construct() {
        parent::__construct();
        $this->model         = new InventoryModel();
        $this->approvalModel = new ApprovalModel();
    }

    // &#8369;8369;”€ Main index (tabbed) &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function index(): void {
        $this->requirePermission('inventory', 'view');
        $isGMReadOnly = $this->requireGMReadOnly();
        $tab        = $_GET['tab'] ?? 'stock';
        $whId       = (int)($_GET['warehouse'] ?? 0);
        $mine       = isset($_GET['mine']) && $_GET['mine'] === '1';
        $stock      = $this->model->getStockSummary($whId);
        $movements  = $this->model->getMovements(50);
        $products   = $this->model->getProducts();
        $warehouses = $this->model->getWarehouses();
        $returns    = $this->model->getReturns();
        $releases   = $this->model->getReleaseRequests('', '', $mine ? $_SESSION['user_id'] : 0);
        $stats      = $this->model->getSummaryStats();
        $this->view('inventory/index', compact(
            'tab', 'stock', 'movements', 'products', 'warehouses',
            'returns', 'releases', 'stats', 'whId', 'isGMReadOnly', 'mine'
        ));
    }

    // &#8369;8369;”€ Stock In &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function stockIn(): void {
        $this->requirePermission('inventory', 'create');
        $productId   = $this->resolveProduct($_POST['product_id'] ?? 0, $_POST['product_name'] ?? '');
        $warehouseId = $this->resolveWarehouse($_POST['warehouse_id'] ?? 0, $_POST['warehouse_name'] ?? '');
        $data = [
            'product_id'     => $productId,
            'warehouse_id'   => $warehouseId,
            'type'           => 'in',
            'quantity'       => (float)($_POST['quantity']   ?? 0),
            'notes'          => trim($_POST['notes']         ?? ''),
            'reference_type' => $_POST['reference_type']    ?? 'manual',
            'reference_id'   => (int)($_POST['reference_id'] ?? 0) ?: null,
        ];
        if (!$data['product_id'] || !$data['warehouse_id'] || $data['quantity'] <= 0) {
            $this->json(['success' => false, 'message' => 'Product, warehouse and quantity are required.']);
        }
        $this->model->addMovement($data);
        $this->json(['success' => true, 'message' => 'Stock received successfully.']);
    }

    // &#8369;8369;”€ Stock Out (direct, manager+) &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function stockOut(): void {
        $this->requirePermission('inventory', 'update');
        $productId   = $this->resolveProduct($_POST['product_id'] ?? 0, $_POST['product_name'] ?? '');
        $warehouseId = $this->resolveWarehouse($_POST['warehouse_id'] ?? 0, $_POST['warehouse_name'] ?? '');
        $data = [
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'type'         => 'out',
            'quantity'     => (float)($_POST['quantity']   ?? 0),
            'notes'        => trim($_POST['notes']         ?? ''),
        ];
        if (!$data['product_id'] || !$data['warehouse_id'] || $data['quantity'] <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid input.']);
        }
        $current = $this->db->fetchOne(
            "SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=?",
            [$data['product_id'], $data['warehouse_id']], 'ii'
        );
        if (!$current || $current['quantity'] < $data['quantity']) {
            $this->json(['success' => false, 'message' => 'Insufficient stock.']);
        }
        $this->model->addMovement($data);
        $this->json(['success' => true, 'message' => 'Stock released successfully.']);
    }

    // &#8369;8369;”€ Release Request (approval-gated) &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function requestRelease(): void {
        $this->requireAuth();
        $productId   = $this->resolveProduct($_POST['product_id'] ?? 0, $_POST['product_name'] ?? '');
        $warehouseId = $this->resolveWarehouse($_POST['warehouse_id'] ?? 0, $_POST['warehouse_name'] ?? '');
        $quantity    = (float)($_POST['quantity'] ?? 0);
        $purpose     = trim($_POST['purpose'] ?? '');
        $requestingDepartment = trim($_POST['requesting_department'] ?? '');

        if (!$productId || !$warehouseId || $quantity <= 0) {
            $this->json(['success' => false, 'message' => 'All fields are required.']);
        }

        // Validate stock availability at creation time (soft check)
        $current = $this->db->fetchOne(
            "SELECT i.quantity, p.name as product_name 
             FROM inventory i 
             JOIN products p ON i.product_id = p.id
             WHERE i.product_id=? AND i.warehouse_id=?",
            [$productId, $warehouseId], 'ii'
        );
        
        $available = (float)($current['quantity'] ?? 0);
        if (!$current || $available < $quantity) {
            $productName = $current['product_name'] ?? 'Unknown Product';
            $this->json([
                'success' => false, 
                'message' => "Insufficient stock for {$productName}. Available: $available, Requested: $quantity"
            ]);
        }

        $id = $this->db->insert(
            "INSERT INTO stock_release_requests (product_id, warehouse_id, quantity, purpose, requesting_department, requested_by)
             VALUES (?,?,?,?,?,?)",
            [$productId, $warehouseId, $quantity, $purpose, $requestingDepartment, $_SESSION['user_id']], 'iidssi'
        );

        $product   = $this->db->fetchOne("SELECT name FROM products WHERE id=?",   [$productId],   'i');
        $warehouse = $this->db->fetchOne("SELECT name FROM warehouses WHERE id=?", [$warehouseId], 'i');

        $description = "Warehouse: " . ($warehouse['name'] ?? '') . " | Purpose: $purpose";
        if ($requestingDepartment) {
            $description .= " | Department: $requestingDepartment";
        }

        $this->approvalModel->createRequest([
            'module'         => 'stock_release',
            'reference_type' => 'stock_release',
            'reference_id'   => $id,
            'title'          => "Stock Release: " . ($product['name'] ?? '') . " x" . number_format($quantity, 2),
            'description'    => $description,
        ], $_SESSION['user_id']);

        $this->json(['success' => true, 'message' => 'Release request submitted for approval.']);
    }

    public function approveRelease(): void {
        $this->requirePermission('inventory', 'approve');
        $id     = (int)($_POST['id']     ?? 0);
        $action = $_POST['action']       ?? '';
        if (!in_array($action, ['approved', 'rejected'])) $this->json(['success' => false, 'message' => 'Invalid action.']);

        $req = $this->db->fetchOne("SELECT * FROM stock_release_requests WHERE id=?", [$id], 'i');
        if (!$req || $req['status'] !== 'pending') $this->json(['success' => false, 'message' => 'Request not found or already processed.']);

        if ($action === 'approved') {
            // Check stock again
            $current = $this->db->fetchOne(
                "SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=?",
                [$req['product_id'], $req['warehouse_id']], 'ii'
            );
            if (!$current || $current['quantity'] < $req['quantity']) {
                $this->json(['success' => false, 'message' => 'Insufficient stock at time of approval.']);
            }
            $this->model->addMovement([
                'product_id'     => $req['product_id'],
                'warehouse_id'   => $req['warehouse_id'],
                'type'           => 'out',
                'quantity'       => $req['quantity'],
                'reference_type' => 'stock_release',
                'reference_id'   => $id,
                'notes'          => 'Approved release: ' . $req['purpose'],
            ]);
            $this->db->query("UPDATE stock_release_requests SET status='released', released_at=NOW() WHERE id=?", [$id], 'i');
        } else {
            $this->db->query("UPDATE stock_release_requests SET status='rejected' WHERE id=?", [$id], 'i');
        }

        $this->json(['success' => true, 'message' => "Release request $action."]);
    }

    // &#8369;8369;”€ Returns &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function createReturn(): void {
        $this->requireAuth();
        $data = [
            'reference_type' => $_POST['reference_type'] ?? 'internal',
            'reference_id'   => (int)($_POST['reference_id'] ?? 0),
            'product_id'     => (int)($_POST['product_id']   ?? 0),
            'warehouse_id'   => (int)($_POST['warehouse_id'] ?? 0),
            'quantity'       => (float)($_POST['quantity']   ?? 0),
            'reason'         => trim($_POST['reason']        ?? ''),
            'condition_type' => $_POST['condition_type']    ?? 'good',
        ];
        if (!$data['product_id'] || !$data['warehouse_id'] || $data['quantity'] <= 0) {
            $this->json(['success' => false, 'message' => 'Product, warehouse and quantity are required.']);
            return;
        }
        
        $returnId = $this->model->createReturn($data);
        
        // Get product and warehouse names for approval title
        $product = $this->db->fetchOne("SELECT name, unit FROM products WHERE id=?", [$data['product_id']], 'i');
        $warehouse = $this->db->fetchOne("SELECT name FROM warehouses WHERE id=?", [$data['warehouse_id']], 'i');
        
        // Create approval request for GM
        $this->approvalModel->createRequest([
            'module'         => 'stock_return',
            'reference_type' => 'stock_return',
            'reference_id'   => $returnId,
            'title'          => "Stock Return: " . ($product['name'] ?? '') . " - " . number_format($data['quantity'], 2) . " " . ($product['unit'] ?? ''),
            'description'    => "Return from " . ($warehouse['name'] ?? '') . " - Condition: " . ucfirst($data['condition_type']) . " - Reason: " . ($data['reason'] ?: 'No reason provided'),
        ], $_SESSION['user_id']);
        
        $this->json(['success' => true, 'message' => 'Return submitted for GM approval.']);
    }

    public function processReturn(): void {
        $this->requirePermission('inventory', 'approve');
        $id     = (int)($_POST['id']     ?? 0);
        $action = $_POST['action']       ?? '';
        if (!in_array($action, ['restock', 'dispose'])) $this->json(['success' => false, 'message' => 'Invalid action.']);
        $result = $this->model->processReturn($id, $action, $_SESSION['user_id']);
        $this->json($result);
    }

    // &#8369;8369;”€ Products CRUD &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function saveProduct(): void {
        $this->requirePermission('inventory', 'create');
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'          => trim($_POST['name']          ?? ''),
            'category'      => trim($_POST['category']      ?? ''),
            'unit'          => trim($_POST['unit']          ?? ''),
            'description'   => trim($_POST['description']   ?? ''),
            'reorder_level' => (float)($_POST['reorder_level'] ?? 0),
        ];
        if (empty($data['name'])) $this->json(['success' => false, 'message' => 'Product name is required.']);
        $this->model->saveProduct($data, $id);
        $this->json(['success' => true, 'message' => 'Product saved.']);
    }

    public function deleteProduct(): void {
        $this->requirePermission('inventory', 'delete');
        $result = $this->model->deleteProduct((int)($_POST['id'] ?? 0));
        $this->json($result);
    }

    // &#8369;8369;”€ Warehouses CRUD &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function saveWarehouse(): void {
        $this->requirePermission('inventory', 'create');
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'     => trim($_POST['name']     ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'capacity' => (float)($_POST['capacity'] ?? 0),
        ];
        if (empty($data['name'])) $this->json(['success' => false, 'message' => 'Warehouse name is required.']);
        $this->model->saveWarehouse($data, $id);
        $this->json(['success' => true, 'message' => 'Warehouse saved.']);
    }

    public function deleteWarehouse(): void {
        $this->requirePermission('inventory', 'delete');
        $result = $this->model->deleteWarehouse((int)($_POST['id'] ?? 0));
        $this->json($result);
    }

    // &#8369;8369;”€ Movements JSON (for AJAX) &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function movements(): void {
        $this->requireAuth();
        $filters = [
            'product_id'   => (int)($_GET['product_id']   ?? 0) ?: null,
            'warehouse_id' => (int)($_GET['warehouse_id'] ?? 0) ?: null,
            'type'         => $_GET['type'] ?? '',
        ];
        $this->json($this->model->getMovements(200, array_filter($filters)));
    }

    // ── Helpers: resolve typed name to ID, auto-create if new ───────────────

    private function resolveProduct(mixed $id, string $name): int {
        $id = (int)$id;
        if ($id) return $id;
        $name = trim($name);
        if (!$name) return 0;
        $existing = $this->db->fetchOne("SELECT id FROM products WHERE name=?", [$name], 's');
        if ($existing) return (int)$existing['id'];
        return (int)$this->db->insert("INSERT INTO products (name, unit) VALUES (?, 'unit')", [$name], 's');
    }

    private function resolveWarehouse(mixed $id, string $name): int {
        $id = (int)$id;
        if ($id) return $id;
        $name = trim($name);
        if (!$name) return 0;
        $existing = $this->db->fetchOne("SELECT id FROM warehouses WHERE name=?", [$name], 's');
        if ($existing) return (int)$existing['id'];
        return (int)$this->db->insert("INSERT INTO warehouses (name) VALUES (?)", [$name], 's');
    }
}

