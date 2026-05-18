<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ApprovalModel.php';
require_once __DIR__ . '/../models/InventoryModel.php';

class LogisticsController extends Controller {
    private ApprovalModel  $approvalModel;
    private InventoryModel $inventoryModel;

    public function __construct() {
        parent::__construct();
        $this->approvalModel  = new ApprovalModel();
        $this->inventoryModel = new InventoryModel();
    }

    // &#8369;8369;”€ List &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function index(): void {
        $this->requirePermission('logistics', 'view');
        $status = $_GET['status'] ?? '';
        $type   = $_GET['type']   ?? '';

        $where  = '1=1';
        $params = [];
        $types  = '';
        if ($status) { $where .= ' AND d.status=?';         $params[] = $status; $types .= 's'; }
        if ($type)   { $where .= ' AND d.reference_type=?'; $params[] = $type;   $types .= 's'; }

        $deliveries = $this->db->fetchAll(
            "SELECT d.*,
                    ar.id   AS approval_request_id,
                    ar.status AS approval_status,
                    acs.label AS approval_step_label
             FROM deliveries d
             LEFT JOIN approval_requests ar
                    ON ar.reference_type = 'delivery' AND ar.reference_id = d.id
             LEFT JOIN approval_steps acs
                    ON acs.request_id = ar.id AND acs.step_order = ar.current_step
             WHERE $where
             ORDER BY d.created_at DESC",
            $params, $types
        );

        $summary = $this->db->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(status='pending')    AS pending,
                SUM(status='in_transit') AS in_transit,
                SUM(status='delivered')  AS delivered,
                SUM(status='failed')     AS failed
             FROM deliveries"
        );

        // Source documents for creating deliveries
        $approvedPOs = $this->db->fetchAll(
            "SELECT po.id, po.po_number AS label, 'purchase_order' AS type, s.name AS party
             FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id
             WHERE po.status='approved' ORDER BY po.po_number"
        );
        $approvedSOs = $this->db->fetchAll(
            "SELECT so.id, so.so_number AS label, 'sales_order' AS type, c.name AS party
             FROM sales_orders so JOIN customers c ON so.customer_id=c.id
             WHERE so.status='approved' ORDER BY so.so_number"
        );

        $products   = $this->inventoryModel->getProducts();
        $warehouses = $this->inventoryModel->getWarehouses();

        $this->view('logistics/index', compact(
            'deliveries', 'summary', 'approvedPOs', 'approvedSOs',
            'products', 'warehouses', 'status', 'type'
        ));
    }

    // &#8369;8369;”€ Create delivery &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function create(): void {
        $this->requireAuth();
        $refType  = $_POST['reference_type'] ?? '';
        $refIdRaw = trim($_POST['reference_id'] ?? '');
        $origin   = trim($_POST['origin']      ?? '');
        $dest     = trim($_POST['destination'] ?? '');
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0) ?: null;
        $receiptNumber = trim($_POST['receipt_number'] ?? '');

        // reference_id is now always a numeric ID from the dropdown
        $refId = (int)$refIdRaw;

        $driver   = trim($_POST['driver_name']  ?? '');
        $plate    = trim($_POST['vehicle_plate'] ?? '');
        $dispatch = $_POST['dispatch_date']      ?? null;
        $notes    = trim($_POST['notes']         ?? '');
        $items    = $_POST['items']              ?? [];

        if (!$refType || !$refId || !$origin || !$dest) {
            $this->json(['success' => false, 'message' => 'Reference, origin and destination are required.']);
        }

        // Map form values to DB ENUM values
        $refTypeDb = match($refType) {
            'sales_order'    => 'sale',
            'purchase_order' => 'purchase',
            default          => $refType
        };
        
        // Determine delivery type
        $deliveryType = $refType === 'purchase_order' ? 'inbound' : 'outbound';
        
        // For inbound deliveries, warehouse_id is required
        if ($deliveryType === 'inbound' && !$warehouseId) {
            $this->json(['success' => false, 'message' => 'Warehouse is required for inbound deliveries.']);
        }

        // Generate DR number if not provided
        $drNumber = $receiptNumber ?: ('DR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));

        $id = $this->db->insert(
            "INSERT INTO deliveries
                (dr_number, reference_type, reference_id, delivery_type, warehouse_id, driver_name, vehicle_plate, origin, destination, dispatch_date, notes, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending')",
            [$drNumber, $refTypeDb, $refId, $deliveryType, $warehouseId, $driver, $plate, $origin, $dest, $dispatch ?: null, $notes],
            'ssisississs'
        );

        // Save delivery items with unit cost and total amount
        foreach ($items as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) continue;
            
            $unitCost = (float)($item['unit_cost'] ?? 0);
            $quantity = (float)$item['quantity'];
            $totalAmount = (float)($item['total_amount'] ?? ($unitCost * $quantity));
            
            $this->db->insert(
                "INSERT INTO delivery_items (delivery_id, product_id, quantity, unit, unit_cost, total_amount, notes) VALUES (?,?,?,?,?,?,?)",
                [$id, (int)$item['product_id'], $quantity, $item['unit'] ?? '', $unitCost, $totalAmount, $item['notes'] ?? ''],
                'iidsdds'
            );
        }

        // Approval workflow for ALL deliveries (both inbound and outbound)
        $typeLabel = $deliveryType === 'inbound' ? 'Inbound' : 'Outbound';
        $refLabel = $refType === 'sales_order' ? "SO #$refId" : "PO #$refId";
        
        $this->approvalModel->createRequest([
            'module'         => 'logistics',
            'reference_type' => 'delivery',
            'reference_id'   => $id,
            'title'          => "$typeLabel Delivery - $refLabel - $drNumber",
            'description'    => "From: $origin | To: $dest" . ($driver ? " | Driver: $driver" : ""),
        ], $_SESSION['user_id']);

        $this->json(['success' => true, 'message' => "Delivery $drNumber created and submitted for approval.", 'id' => $id]);
    }

    // &#8369;8369;”€ Update status &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function updateStatus(): void {
        $this->requireAuth();
        $id     = (int)($_POST['id']     ?? 0);
        $status = $_POST['status']       ?? '';
        $allowed = ['pending', 'in_transit', 'delivered', 'failed'];
        if (!in_array($status, $allowed)) $this->json(['success' => false, 'message' => 'Invalid status.']);

        $delivery = $this->db->fetchOne("SELECT * FROM deliveries WHERE id=?", [$id], 'i');
        if (!$delivery) $this->json(['success' => false, 'message' => 'Delivery not found.']);

        // Check if delivery has been approved by GM before allowing status changes
        $approval = $this->db->fetchOne(
            "SELECT * FROM approval_requests WHERE reference_type='delivery' AND reference_id=? ORDER BY id DESC LIMIT 1",
            [$id], 'i'
        );
        
        if ($approval && $approval['status'] !== 'approved') {
            $this->json(['success' => false, 'message' => 'Delivery must be approved by GM before status can be updated.']);
        }

        $extra = $status === 'delivered' ? ', delivery_date = NOW()' : '';
        $this->db->query("UPDATE deliveries SET status=? $extra WHERE id=?", [$status, $id], 'si');

        // When in_transit for outbound (sales) — deduct stock immediately
        if ($status === 'in_transit' && $delivery['reference_type'] === 'sale') {
            $items = $this->db->fetchAll(
                "SELECT * FROM delivery_items WHERE delivery_id=?", [$id], 'i'
            );
            // Use the warehouse specified in the delivery, or default to first warehouse
            $warehouseId = $delivery['warehouse_id'] ?? null;
            if (!$warehouseId) {
                $warehouses = $this->inventoryModel->getWarehouses();
                $warehouseId = $warehouses[0]['id'] ?? null;
            }
            
            // Check if stock already deducted (idempotency)
            $existing = $this->db->fetchOne(
                "SELECT id FROM stock_movements WHERE reference_type='delivery' AND reference_id=? AND type='out'",
                [$id], 'i'
            );
            
            if (!$existing && $warehouseId) {
                foreach ($items as $item) {
                    $this->inventoryModel->addMovement([
                        'product_id'     => $item['product_id'],
                        'warehouse_id'   => $warehouseId,
                        'type'           => 'out',
                        'quantity'       => $item['quantity'],
                        'reference_type' => 'delivery',
                        'reference_id'   => $id,
                        'notes'          => 'Stock out for delivery #' . $id . ' (SO #' . $delivery['reference_id'] . ')',
                    ]);
                }
            }
        }

        // When delivered from a PO — update PO status and trigger stock-in
        if ($status === 'delivered') {
            if ($delivery['reference_type'] === 'purchase') {
                $this->db->query(
                    "UPDATE purchase_orders SET status='delivered' WHERE id=?",
                    [$delivery['reference_id']], 'i'
                );
                // Auto stock-in for each delivery item
                $items = $this->db->fetchAll(
                    "SELECT * FROM delivery_items WHERE delivery_id=?", [$id], 'i'
                );
                // Use the warehouse specified in the delivery, or default to first warehouse
                $warehouseId = $delivery['warehouse_id'] ?? null;
                if (!$warehouseId) {
                    $warehouses = $this->inventoryModel->getWarehouses();
                    $warehouseId = $warehouses[0]['id'] ?? null;
                }
                
                // Check if stock already added (idempotency)
                $existing = $this->db->fetchOne(
                    "SELECT id FROM stock_movements WHERE reference_type='delivery' AND reference_id=? AND type='in'",
                    [$id], 'i'
                );
                
                if (!$existing && $warehouseId) {
                    foreach ($items as $item) {
                        $this->inventoryModel->addMovement([
                            'product_id'     => $item['product_id'],
                            'warehouse_id'   => $warehouseId,
                            'type'           => 'in',
                            'quantity'       => $item['quantity'],
                            'reference_type' => 'delivery',
                            'reference_id'   => $id,
                            'notes'          => 'Auto stock-in from delivery #' . $id . ' to warehouse',
                        ]);
                    }
                }
            }
            // When delivered from a SO — update SO status
            if ($delivery['reference_type'] === 'sale') {
                $this->db->query(
                    "UPDATE sales_orders SET status='delivered' WHERE id=?",
                    [$delivery['reference_id']], 'i'
                );
                // Stock was already deducted when status changed to in_transit
            }
        }

        $this->json(['success' => true, 'message' => "Status updated to $status."]);
    }

    // &#8369;8369;”€ Detail &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function detail(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);

        $delivery = $this->db->fetchOne(
            "SELECT d.* FROM deliveries d WHERE d.id=?", [$id], 'i'
        );
        if (!$delivery) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Delivery not found.'];
            $this->redirect('/logistics');
        }

        $items    = $this->db->fetchAll(
            "SELECT di.*, p.name AS product_name, p.unit FROM delivery_items di
             JOIN products p ON di.product_id = p.id WHERE di.delivery_id=?", [$id], 'i'
        );
        $receipt  = $this->db->fetchOne(
            "SELECT dr.*, u.full_name AS received_by_name FROM delivery_receipts dr
             JOIN users u ON dr.received_by = u.id WHERE dr.delivery_id=?", [$id], 'i'
        );
        $approval = $this->db->fetchOne(
            "SELECT ar.* FROM approval_requests ar
             WHERE ar.reference_type='delivery' AND ar.reference_id=? ORDER BY ar.id DESC LIMIT 1",
            [$id], 'i'
        );
        $steps = $approval ? $this->approvalModel->getSteps($approval['id']) : [];
        $audit = $approval ? $this->approvalModel->getAuditLog($approval['id']) : [];

        // Source document label
        $sourceDoc = null;
        if ($delivery['reference_type'] === 'purchase_order') {
            $sourceDoc = $this->db->fetchOne(
                "SELECT po.po_number AS label, s.name AS party FROM purchase_orders po
                 JOIN suppliers s ON po.supplier_id=s.id WHERE po.id=?",
                [$delivery['reference_id']], 'i'
            );
        } elseif ($delivery['reference_type'] === 'sales_order') {
            $sourceDoc = $this->db->fetchOne(
                "SELECT so.so_number AS label, c.name AS party FROM sales_orders so
                 JOIN customers c ON so.customer_id=c.id WHERE so.id=?",
                [$delivery['reference_id']], 'i'
            );
        }

        $this->view('logistics/detail', compact('delivery', 'items', 'receipt', 'approval', 'steps', 'audit', 'sourceDoc'));
    }

    // &#8369;8369;”€ Generate delivery receipt &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function generateReceipt(): void {
        $this->requireAuth();
        $deliveryId    = (int)($_POST['delivery_id']    ?? 0);
        $sigName       = trim($_POST['signature_name']  ?? '');
        $conditionNotes = trim($_POST['condition_notes'] ?? '');

        $delivery = $this->db->fetchOne("SELECT * FROM deliveries WHERE id=?", [$deliveryId], 'i');
        if (!$delivery || $delivery['status'] !== 'delivered') {
            $this->json(['success' => false, 'message' => 'Delivery must be marked as delivered first.']);
        }

        $existing = $this->db->fetchOne("SELECT id FROM delivery_receipts WHERE delivery_id=?", [$deliveryId], 'i');
        if ($existing) $this->json(['success' => false, 'message' => 'Receipt already generated.']);

        $drNumber = 'DR-' . date('Ymd') . '-' . str_pad($deliveryId, 4, '0', STR_PAD_LEFT);
        $this->db->insert(
            "INSERT INTO delivery_receipts (delivery_id, dr_number, received_by, condition_notes, signature_name)
             VALUES (?,?,?,?,?)",
            [$deliveryId, $drNumber, $_SESSION['user_id'], $conditionNotes, $sigName],
            'isiss'
        );

        $this->json(['success' => true, 'message' => "Receipt $drNumber generated.", 'dr_number' => $drNumber]);
    }

    // &#8369;8369;”€ Print receipt (printable page) &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function printReceipt(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);

        $receipt = $this->db->fetchOne(
            "SELECT dr.*, u.full_name AS received_by_name
             FROM delivery_receipts dr JOIN users u ON dr.received_by=u.id
             WHERE dr.id=?", [$id], 'i'
        );
        if (!$receipt) { $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Receipt not found.']; $this->redirect('/logistics'); }

        $delivery = $this->db->fetchOne("SELECT * FROM deliveries WHERE id=?", [$receipt['delivery_id']], 'i');
        $items    = $this->db->fetchAll(
            "SELECT di.*, p.name AS product_name, p.unit FROM delivery_items di
             JOIN products p ON di.product_id=p.id WHERE di.delivery_id=?",
            [$receipt['delivery_id']], 'i'
        );

        $sourceDoc = null;
        if ($delivery['reference_type'] === 'purchase_order') {
            $sourceDoc = $this->db->fetchOne(
                "SELECT po.po_number AS label, s.name AS party, s.address FROM purchase_orders po
                 JOIN suppliers s ON po.supplier_id=s.id WHERE po.id=?",
                [$delivery['reference_id']], 'i'
            );
        } elseif ($delivery['reference_type'] === 'sales_order') {
            $sourceDoc = $this->db->fetchOne(
                "SELECT so.so_number AS label, c.name AS party, c.address FROM sales_orders so
                 JOIN customers c ON so.customer_id=c.id WHERE so.id=?",
                [$delivery['reference_id']], 'i'
            );
        }

        $this->view('logistics/receipt_print', compact('receipt', 'delivery', 'items', 'sourceDoc'));
    }

    // &#8369;8369;”€ Delete &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function delete(): void {
        $this->requirePermission('logistics', 'delete');
        $id = (int)($_POST['id'] ?? 0);
        $d  = $this->db->fetchOne("SELECT status FROM deliveries WHERE id=?", [$id], 'i');
        if (!$d || $d['status'] !== 'pending') {
            $this->json(['success' => false, 'message' => 'Only pending deliveries can be deleted.']);
        }
        $this->db->query("DELETE FROM deliveries WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Delivery deleted.']);
    }
}

