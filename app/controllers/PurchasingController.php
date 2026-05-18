<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ApprovalModel.php';

class PurchasingController extends Controller {
    private ApprovalModel $approvalModel;

    public function __construct() {
        parent::__construct();
        $this->approvalModel = new ApprovalModel();
    }

    // &#8369;8369;”€ Purchase Orders &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function index(): void {
        $this->requirePermission('purchasing', 'view');
        $isGMReadOnly = $this->requireGMReadOnly();
        $tab = $_GET['tab'] ?? 'po';

        $orders = $this->db->fetchAll(
            "SELECT po.*, s.name AS supplier_name, u.full_name AS approved_by_name,
                    ar.id AS approval_request_id, ar.status AS approval_status,
                    acs.label AS approval_step_label
             FROM purchase_orders po
             JOIN suppliers s ON po.supplier_id = s.id
             LEFT JOIN users u ON po.approved_by = u.id
             LEFT JOIN approval_requests ar ON ar.reference_type = 'purchase_order' AND ar.reference_id = po.id
             LEFT JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
             ORDER BY po.created_at DESC"
        );

        $requisitions = $this->db->fetchAll(
            "SELECT pr.*, d.name AS dept_name, u.full_name AS requester_name,
                    ar.id AS approval_request_id, ar.status AS approval_status,
                    acs.label AS approval_step_label
             FROM purchase_requisitions pr
             LEFT JOIN departments d ON pr.department_id = d.id
             JOIN users u ON pr.requested_by = u.id
             LEFT JOIN approval_requests ar ON ar.reference_type = 'purchase_requisition' AND ar.reference_id = pr.id
             LEFT JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
             ORDER BY pr.created_at DESC"
        );

        $suppliers    = $this->db->fetchAll("SELECT * FROM suppliers WHERE status='active' ORDER BY name");
        $departments  = $this->db->fetchAll("SELECT * FROM departments ORDER BY name");
        $products     = $this->db->fetchAll("SELECT * FROM products ORDER BY name");

        $this->view('purchasing/index', compact('orders', 'requisitions', 'suppliers', 'departments', 'products', 'tab', 'isGMReadOnly'));
    }

    public function createPO(): void {
        $this->requireAuth();
        $supplierId   = (int)($_POST['supplier_id'] ?? 0);
        $supplierName = trim($_POST['supplier_name'] ?? '');
        $supplierInvoiceNumber = trim($_POST['supplier_invoice_number'] ?? '');
        $items        = $_POST['items'] ?? [];
        $notes        = trim($_POST['notes'] ?? '');

        // Auto-create supplier if typed a new name
        if (!$supplierId && $supplierName) {
            $existing = $this->db->fetchOne("SELECT id FROM suppliers WHERE name=?", [$supplierName], 's');
            if ($existing) {
                $supplierId = $existing['id'];
            } else {
                $supplierId = $this->db->insert(
                    "INSERT INTO suppliers (name, status) VALUES (?, 'active')",
                    [$supplierName], 's'
                );
            }
        }

        if (!$supplierId || empty($items)) {
            $this->json(['success' => false, 'message' => 'Supplier and at least one item are required.']);
        }

        $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $total    = 0;
        foreach ($items as $item) $total += (float)($item['total_price'] ?? 0);

        // If supplier invoice provided, set payment status and date
        $paymentStatus = $supplierInvoiceNumber ? 'unpaid' : null;
        $invoiceDate = $supplierInvoiceNumber ? date('Y-m-d') : null;

        $poId = $this->db->insert(
            "INSERT INTO purchase_orders (supplier_id, po_number, supplier_invoice_number, supplier_invoice_date, order_date, expected_delivery, total_amount, payment_status, status)
             VALUES (?,?,?,?,NOW(),?,?,?,'pending')",
            [$supplierId, $poNumber, $supplierInvoiceNumber ?: null, $invoiceDate, $_POST['expected_delivery'] ?? null, $total, $paymentStatus],
            'issssds'
        );

        foreach ($items as $item) {
            $this->db->insert(
                "INSERT INTO purchase_order_items (po_id, item_name, quantity, unit, unit_price, total_price)
                 VALUES (?,?,?,?,?,?)",
                [$poId, $item['item_name'], $item['quantity'], $item['unit'] ?? '', $item['unit_price'], $item['total_price']],
                'isdsdd'
            );
        }

        // Create journal entry for accounts payable if supplier invoice provided
        if ($supplierInvoiceNumber) {
            $this->db->insert(
                "INSERT INTO journal_entries
                    (entry_date, reference, description, debit_account, credit_account, amount, source_type, source_id, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    date('Y-m-d'),
                    $supplierInvoiceNumber,
                    "Accounts Payable for PO $poNumber - Supplier Invoice: $supplierInvoiceNumber - " . $this->_supplierName($supplierId),
                    'Inventory', // or 'Purchases' depending on accounting method
                    'Accounts Payable',
                    $total,
                    'purchase_order',
                    $poId,
                    $_SESSION['user_id']
                ],
                'sssssdsii'
            );
        }

        // Legacy workflow entry
        $this->db->insert(
            "INSERT INTO approval_workflows (module, reference_id, reference_type, requested_by) VALUES ('purchasing',?,?,?)",
            [$poId, 'purchase_order', $_SESSION['user_id']], 'isi'
        );

        // Multi-level approval
        $this->approvalModel->createRequest([
            'module'         => 'purchasing',
            'reference_type' => 'purchase_order',
            'reference_id'   => $poId,
            'title'          => "Purchase Order $poNumber" . ($supplierInvoiceNumber ? " / Supplier Invoice: $supplierInvoiceNumber" : ''),
            'description'    => "Supplier: " . $this->_supplierName($supplierId) . " | Total: " . number_format($total, 2) . ($notes ? " | Notes: $notes" : ''),
        ], $_SESSION['user_id']);

        $message = "PO $poNumber created and submitted for approval.";
        if ($supplierInvoiceNumber) {
            $message .= " Supplier Invoice: $supplierInvoiceNumber recorded. Journal entry created for Accounts Payable.";
        }

        $this->json(['success' => true, 'message' => $message]);
    }

    public function editPO(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id=?", [$id], 'i');
        if (!$po || $po['status'] !== 'pending') {
            $this->json(['success' => false, 'message' => 'Only pending POs can be edited.']);
        }

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $items      = $_POST['items'] ?? [];
        if (!$supplierId || empty($items)) {
            $this->json(['success' => false, 'message' => 'Supplier and items are required.']);
        }

        $total = 0;
        foreach ($items as $item) $total += (float)($item['total_price'] ?? 0);

        $this->db->query(
            "UPDATE purchase_orders SET supplier_id=?, expected_delivery=?, total_amount=? WHERE id=?",
            [$supplierId, $_POST['expected_delivery'] ?? null, $total, $id], 'isdi'
        );

        $this->db->query("DELETE FROM purchase_order_items WHERE po_id=?", [$id], 'i');
        foreach ($items as $item) {
            $this->db->insert(
                "INSERT INTO purchase_order_items (po_id, item_name, quantity, unit, unit_price, total_price) VALUES (?,?,?,?,?,?)",
                [$id, $item['item_name'], $item['quantity'], $item['unit'] ?? '', $item['unit_price'], $item['total_price']],
                'isdsdd'
            );
        }

        $this->json(['success' => true, 'message' => 'PO updated.']);
    }

    public function deletePO(): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id=?", [$id], 'i');
        if (!$po) $this->json(['success' => false, 'message' => 'PO not found.']);
        if (!in_array($po['status'], ['pending', 'cancelled', 'rejected'])) {
            $this->json(['success' => false, 'message' => 'Only pending or rejected POs can be deleted.']);
        }
        if (!in_array($role, ['admin', 'purchasing_user'])) {
            $this->json(['success' => false, 'message' => 'Access denied.']);
        }
        // Clean up related records
        $this->db->query("DELETE FROM purchase_order_items WHERE po_id=?", [$id], 'i');
        $ar = $this->db->fetchOne("SELECT id FROM approval_requests WHERE reference_type='purchase_order' AND reference_id=?", [$id], 'i');
        if ($ar) {
            $this->db->query("DELETE FROM approval_steps WHERE request_id=?", [$ar['id']], 'i');
            $this->db->query("DELETE FROM approval_audit_log WHERE request_id=?", [$ar['id']], 'i');
            $this->db->query("DELETE FROM approval_requests WHERE id=?", [$ar['id']], 'i');
        }
        $this->db->query("DELETE FROM approval_workflows WHERE reference_id=? AND reference_type='purchase_order'", [$id], 'i');
        $this->db->query("DELETE FROM purchase_orders WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'PO deleted.']);
    }

    public function approvePO(): void {
        $this->requirePermission('purchasing', 'approve');
        $poId    = (int)($_POST['id'] ?? 0);
        $action  = $_POST['action'] ?? 'approved';
        $remarks = trim($_POST['remarks'] ?? '');

        $this->db->query(
            "UPDATE purchase_orders SET status=?, approved_by=? WHERE id=?",
            [$action, $_SESSION['user_id'], $poId], 'sii'
        );
        $this->db->query(
            "UPDATE approval_workflows SET status=?, reviewed_by=?, reviewed_at=NOW()
             WHERE reference_id=? AND reference_type='purchase_order' AND status='pending'",
            [$action, $_SESSION['user_id'], $poId], 'sii'
        );
        $this->json(['success' => true, 'message' => "PO $action."]);
    }

    public function updatePOStatus(): void {
        $this->requirePermission('purchasing', 'approve');
        $id          = (int)($_POST['id'] ?? 0);
        $status      = $_POST['status'] ?? '';
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $allowed = ['pending', 'approved', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed)) $this->json(['success' => false, 'message' => 'Invalid status.']);

        $this->db->query("UPDATE purchase_orders SET status=? WHERE id=?", [$status, $id], 'si');

        // Auto stock-in when PO is marked delivered
        if ($status === 'delivered') {
            $items = $this->db->fetchAll(
                "SELECT poi.*, p.id AS product_id FROM purchase_order_items poi
                 LEFT JOIN products p ON p.name = poi.item_name
                 WHERE poi.po_id = ?", [$id], 'i'
            );
            // Use first available warehouse if not specified
            if (!$warehouseId) {
                $wh = $this->db->fetchOne("SELECT id FROM warehouses LIMIT 1");
                $warehouseId = $wh ? (int)$wh['id'] : 0;
            }
            if ($warehouseId) {
                require_once __DIR__ . '/../models/InventoryModel.php';
                $inv = new InventoryModel();
                foreach ($items as $item) {
                    if (!$item['product_id']) continue;
                    $inv->addMovement([
                        'product_id'     => $item['product_id'],
                        'warehouse_id'   => $warehouseId,
                        'type'           => 'in',
                        'quantity'       => $item['quantity'],
                        'reference_type' => 'purchase_order',
                        'reference_id'   => $id,
                        'notes'          => "Received from PO #$id",
                    ]);
                }
            }
        }

        $this->json(['success' => true, 'message' => "Status updated to $status."]);
    }

    public function poDetail(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $po = $this->db->fetchOne(
            "SELECT po.*, s.name AS supplier_name, s.contact_person, s.phone, s.email,
                    u.full_name AS approved_by_name, uc.full_name AS created_by_name
             FROM purchase_orders po
             JOIN suppliers s ON po.supplier_id = s.id
             LEFT JOIN users u ON po.approved_by = u.id
             LEFT JOIN users uc ON uc.id = ?
             WHERE po.id = ?",
            [$_SESSION['user_id'], $id], 'ii'
        );
        if (!$po) { $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'PO not found.']; $this->redirect('/purchasing'); }

        $items    = $this->db->fetchAll("SELECT * FROM purchase_order_items WHERE po_id=? ORDER BY id", [$id], 'i');
        $approval = $this->db->fetchOne(
            "SELECT ar.* FROM approval_requests ar WHERE ar.reference_type='purchase_order' AND ar.reference_id=? ORDER BY ar.id DESC LIMIT 1",
            [$id], 'i'
        );
        $steps    = $approval ? $this->approvalModel->getSteps($approval['id']) : [];
        $audit    = $approval ? $this->approvalModel->getAuditLog($approval['id']) : [];

        $this->view('purchasing/po_detail', compact('po', 'items', 'approval', 'steps', 'audit'));
    }

    // &#8369;8369;”€ Purchase Requisitions &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function createPRS(): void {
        $this->requireAuth();
        $deptId = (int)($_POST['department_id'] ?? 0);
        $items  = $_POST['items'] ?? [];
        $desc   = trim($_POST['description'] ?? '');
        $notes  = trim($_POST['notes'] ?? '');

        if (empty($items)) $this->json(['success' => false, 'message' => 'At least one item is required.']);

        $prsId = $this->db->insert(
            "INSERT INTO purchase_requisitions (requested_by, department_id, description, notes, status)
             VALUES (?,?,?,?,'pending')",
            [$_SESSION['user_id'], $deptId ?: null, $desc, $notes], 'iiss'
        );

        $total = 0;
        foreach ($items as $item) {
            $rowTotal = (float)($item['quantity'] ?? 0) * (float)($item['estimated_price'] ?? 0);
            $total += $rowTotal;
            $this->db->insert(
                "INSERT INTO purchase_requisition_items (requisition_id, item_name, quantity, unit, estimated_price, total_price)
                 VALUES (?,?,?,?,?,?)",
                [$prsId, $item['item_name'], $item['quantity'], $item['unit'] ?? '', $item['estimated_price'] ?? 0, $rowTotal],
                'isdsdd'
            );
        }

        $prsNumber = 'PRS-' . date('Ymd') . '-' . str_pad($prsId, 4, '0', STR_PAD_LEFT);

        $this->approvalModel->createRequest([
            'module'         => 'prs',
            'reference_type' => 'purchase_requisition',
            'reference_id'   => $prsId,
            'title'          => "Purchase Requisition $prsNumber",
            'description'    => ($desc ?: 'No description') . " | Est. Total: &#8369;" . number_format($total, 2),
        ], $_SESSION['user_id']);

        $this->json(['success' => true, 'message' => "Requisition $prsNumber submitted for approval."]);
    }

    public function editPRS(): void {
        $this->requireAuth();
        $id  = (int)($_POST['id'] ?? 0);
        $prs = $this->db->fetchOne("SELECT * FROM purchase_requisitions WHERE id=?", [$id], 'i');
        if (!$prs || $prs['status'] !== 'pending') {
            $this->json(['success' => false, 'message' => 'Only pending requisitions can be edited.']);
        }
        if ($prs['requested_by'] != $_SESSION['user_id'] && $_SESSION['user']['role'] !== 'admin') {
            $this->json(['success' => false, 'message' => 'You can only edit your own requisitions.']);
        }

        $items = $_POST['items'] ?? [];
        $this->db->query(
            "UPDATE purchase_requisitions SET department_id=?, description=?, notes=? WHERE id=?",
            [(int)($_POST['department_id'] ?? 0) ?: null, trim($_POST['description'] ?? ''), trim($_POST['notes'] ?? ''), $id],
            'issi'
        );
        $this->db->query("DELETE FROM purchase_requisition_items WHERE requisition_id=?", [$id], 'i');
        foreach ($items as $item) {
            $rowTotal = (float)($item['quantity'] ?? 0) * (float)($item['estimated_price'] ?? 0);
            $this->db->insert(
                "INSERT INTO purchase_requisition_items (requisition_id, item_name, quantity, unit, estimated_price, total_price) VALUES (?,?,?,?,?,?)",
                [$id, $item['item_name'], $item['quantity'], $item['unit'] ?? '', $item['estimated_price'] ?? 0, $rowTotal],
                'isdsdd'
            );
        }
        $this->json(['success' => true, 'message' => 'Requisition updated.']);
    }

    public function deletePRS(): void {
        $this->requireAuth();
        $id  = (int)($_POST['id'] ?? 0);
        $prs = $this->db->fetchOne("SELECT * FROM purchase_requisitions WHERE id=?", [$id], 'i');
        if (!$prs) $this->json(['success' => false, 'message' => 'Requisition not found.']);
        if (!in_array($prs['status'], ['pending', 'rejected'])) {
            $this->json(['success' => false, 'message' => 'Only pending or rejected requisitions can be deleted.']);
        }
        $role = $_SESSION['user']['role'] ?? '';
        if ($prs['requested_by'] != $_SESSION['user_id'] && !in_array($role, ['admin', 'purchasing_user'])) {
            $this->json(['success' => false, 'message' => 'Access denied.']);
        }
        // Clean up related records
        $ar = $this->db->fetchOne("SELECT id FROM approval_requests WHERE reference_type='purchase_requisition' AND reference_id=?", [$id], 'i');
        if ($ar) {
            $this->db->query("DELETE FROM approval_steps WHERE request_id=?", [$ar['id']], 'i');
            $this->db->query("DELETE FROM approval_audit_log WHERE request_id=?", [$ar['id']], 'i');
            $this->db->query("DELETE FROM approval_requests WHERE id=?", [$ar['id']], 'i');
        }
        $this->db->query("DELETE FROM purchase_requisition_items WHERE requisition_id=?", [$id], 'i');
        $this->db->query("DELETE FROM purchase_requisitions WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Requisition deleted.']);
    }

    public function prsDetail(): void {
        $this->requireAuth();
        $id  = (int)($_GET['id'] ?? 0);
        $prs = $this->db->fetchOne(
            "SELECT pr.*, d.name AS dept_name, u.full_name AS requester_name
             FROM purchase_requisitions pr
             LEFT JOIN departments d ON pr.department_id = d.id
             JOIN users u ON pr.requested_by = u.id
             WHERE pr.id = ?",
            [$id], 'i'
        );
        if (!$prs) { $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Requisition not found.']; $this->redirect('/purchasing?tab=prs'); }

        $items    = $this->db->fetchAll("SELECT * FROM purchase_requisition_items WHERE requisition_id=? ORDER BY id", [$id], 'i');
        $approval = $this->db->fetchOne(
            "SELECT ar.* FROM approval_requests ar WHERE ar.reference_type='purchase_requisition' AND ar.reference_id=? ORDER BY ar.id DESC LIMIT 1",
            [$id], 'i'
        );
        $steps = $approval ? $this->approvalModel->getSteps($approval['id']) : [];
        $audit = $approval ? $this->approvalModel->getAuditLog($approval['id']) : [];

        $this->view('purchasing/prs_detail', compact('prs', 'items', 'approval', 'steps', 'audit'));
    }

    // &#8369;8369;”€ Suppliers &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function suppliers(): void {
        $this->requireAuth();
        $suppliers = $this->db->fetchAll(
            "SELECT s.*, COUNT(po.id) AS total_orders,
                    COALESCE(SUM(po.total_amount),0) AS total_value
             FROM suppliers s
             LEFT JOIN purchase_orders po ON po.supplier_id = s.id
             GROUP BY s.id ORDER BY s.name"
        );
        $this->view('purchasing/suppliers', compact('suppliers'));
    }

    public function saveSupplier(): void {
        $this->requireAuth();
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'           => trim($_POST['name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'email'          => trim($_POST['email'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
            'status'         => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
        ];
        if (empty($data['name'])) $this->json(['success' => false, 'message' => 'Supplier name is required.']);

        if ($id) {
            $this->db->query(
                "UPDATE suppliers SET name=?,contact_person=?,phone=?,email=?,address=?,status=? WHERE id=?",
                [...array_values($data), $id], 'ssssssi'
            );
        } else {
            $this->db->insert(
                "INSERT INTO suppliers (name,contact_person,phone,email,address,status) VALUES (?,?,?,?,?,?)",
                array_values($data), 'ssssss'
            );
        }
        $this->json(['success' => true, 'message' => 'Supplier saved.']);
    }

    public function deleteSupplier(): void {
        $this->requirePermission('purchasing', 'delete');
        $id = (int)($_POST['id'] ?? 0);
        $used = $this->db->fetchOne("SELECT id FROM purchase_orders WHERE supplier_id=? LIMIT 1", [$id], 'i');
        if ($used) $this->json(['success' => false, 'message' => 'Cannot delete supplier with existing orders. Deactivate instead.']);
        $this->db->query("DELETE FROM suppliers WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Supplier deleted.']);
    }

    public function toggleSupplier(): void {
        $this->requireAuth();
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['active', 'inactive'])) $this->json(['success' => false, 'message' => 'Invalid status.']);
        $this->db->query("UPDATE suppliers SET status=? WHERE id=?", [$status, $id], 'si');
        $this->json(['success' => true, 'message' => "Supplier set to $status."]);
    }
    // &#8369;8369;”€ Order Tracking &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function tracking(): void {
        $this->requireAuth();
        $status = $_GET['status'] ?? '';
        $from   = $_GET['from'] ?? date('Y-m-01');
        $to     = $_GET['to']   ?? date('Y-m-d');

        $where  = 'po.order_date BETWEEN ? AND ?';
        $params = [$from, $to];
        $types  = 'ss';
        if ($status) { $where .= ' AND po.status=?'; $params[] = $status; $types .= 's'; }

        $orders = $this->db->fetchAll(
            "SELECT po.*, s.name AS supplier_name,
                    COUNT(poi.id) AS item_count,
                    u.full_name AS approved_by_name
             FROM purchase_orders po
             JOIN suppliers s ON po.supplier_id = s.id
             LEFT JOIN purchase_order_items poi ON poi.po_id = po.id
             LEFT JOIN users u ON po.approved_by = u.id
             WHERE $where GROUP BY po.id ORDER BY po.order_date DESC",
            $params, $types
        );

        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status='pending'   THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status='approved'  THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered,
                    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    COALESCE(SUM(total_amount),0) AS total_value
             FROM purchase_orders po WHERE $where",
            $params, $types
        );

        $this->view('purchasing/tracking', compact('orders', 'summary', 'status', 'from', 'to'));
    }

    // &#8369;8369;”€ Private helpers &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    private function _supplierName(int $id): string {
        $s = $this->db->fetchOne("SELECT name FROM suppliers WHERE id=?", [$id], 'i');
        return $s['name'] ?? 'Unknown';
    }

    // Record Supplier Invoice
    public function recordInvoice(): void {
        $this->requireAuth();
        $poId = (int)($_POST['po_id'] ?? 0);
        $invoiceNumber = trim($_POST['supplier_invoice_number'] ?? '');
        $invoiceDate = $_POST['supplier_invoice_date'] ?? date('Y-m-d');
        $paymentTerms = $_POST['payment_terms'] ?? 'Net 30';
        $dueDate = $_POST['payment_due_date'] ?? null;

        if (!$poId || !$invoiceNumber) {
            $this->json(['success' => false, 'message' => 'PO ID and invoice number are required.']);
        }

        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id=?", [$poId], 'i');
        if (!$po) $this->json(['success' => false, 'message' => 'Purchase order not found.']);

        // Calculate due date if not provided
        if (!$dueDate) {
            $days = 30; // Default Net 30
            if (preg_match('/Net (\d+)/', $paymentTerms, $matches)) {
                $days = (int)$matches[1];
            }
            $dueDate = date('Y-m-d', strtotime($invoiceDate . " +$days days"));
        }

        // Update PO with invoice details
        $this->db->query(
            "UPDATE purchase_orders 
             SET supplier_invoice_number=?, supplier_invoice_date=?, payment_terms=?, payment_due_date=?, payment_status='unpaid'
             WHERE id=?",
            [$invoiceNumber, $invoiceDate, $paymentTerms, $dueDate, $poId],
            'ssssi'
        );

        // Create journal entry for accounts payable
        $this->db->insert(
            "INSERT INTO journal_entries
                (entry_date, reference, description, debit_account, credit_account, amount, source_type, source_id, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $invoiceDate,
                "PO-$poId-INV",
                "Supplier Invoice: $invoiceNumber for PO {$po['po_number']}",
                'Inventory', // or 'Purchases' depending on accounting method
                'Accounts Payable',
                $po['total_amount'],
                'purchase_order',
                $poId,
                $_SESSION['user_id']
            ],
            'sssssdsii'
        );

        $this->json(['success' => true, 'message' => 'Supplier invoice recorded and journal entry created.']);
    }

    // Record Payment for PO
    public function recordPayment(): void {
        $this->requireAuth();
        $poId = (int)($_POST['po_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';
        $notes = trim($_POST['notes'] ?? '');

        if (!$poId || $amount <= 0) {
            $this->json(['success' => false, 'message' => 'PO ID and amount are required.']);
        }

        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id=?", [$poId], 'i');
        if (!$po) $this->json(['success' => false, 'message' => 'Purchase order not found.']);

        $currentPaid = (float)($po['amount_paid'] ?? 0);
        $newPaid = $currentPaid + $amount;
        $balance = $po['total_amount'] - $newPaid;

        // Determine payment status
        $paymentStatus = 'unpaid';
        if ($newPaid >= $po['total_amount']) {
            $paymentStatus = 'paid';
            $newPaid = $po['total_amount']; // Cap at total amount
        } elseif ($newPaid > 0) {
            $paymentStatus = 'partial';
        }

        // Update PO payment status
        $this->db->query(
            "UPDATE purchase_orders SET amount_paid=?, payment_status=? WHERE id=?",
            [$newPaid, $paymentStatus, $poId],
            'dsi'
        );

        // Create journal entry for payment
        $this->db->insert(
            "INSERT INTO journal_entries
                (entry_date, reference, description, debit_account, credit_account, amount, source_type, source_id, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $paymentDate,
                "PO-$poId-PAY",
                "Payment for PO {$po['po_number']}" . ($notes ? " | $notes" : ''),
                'Accounts Payable',
                $paymentMethod === 'cash' ? 'Cash' : 'Bank',
                $amount,
                'purchase_order',
                $poId,
                $_SESSION['user_id']
            ],
            'sssssdsii'
        );

        $this->json([
            'success' => true,
            'message' => "Payment of ₱" . number_format($amount, 2) . " recorded. Balance: ₱" . number_format($balance, 2)
        ]);
    }

    // Print Supplier Invoice
    public function invoicePrint(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);

        $po = $this->db->fetchOne(
            "SELECT po.*, s.name AS supplier_name, s.contact_person, s.phone, s.email, s.address
             FROM purchase_orders po
             JOIN suppliers s ON po.supplier_id = s.id
             WHERE po.id = ?",
            [$id], 'i'
        );

        if (!$po) {
            http_response_code(404);
            echo 'Purchase order not found';
            exit;
        }

        $items = $this->db->fetchAll(
            "SELECT * FROM purchase_order_items WHERE po_id=? ORDER BY id",
            [$id], 'i'
        );

        $this->view('purchasing/invoice_print', compact('po', 'items'));
    }
}

