﻿<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ApprovalModel.php';
require_once __DIR__ . '/../models/InventoryModel.php';

class SalesController extends Controller {
    private ApprovalModel  $approvalModel;
    private InventoryModel $inventoryModel;

    public function __construct() {
        parent::__construct();
        $this->approvalModel  = new ApprovalModel();
        $this->inventoryModel = new InventoryModel();
    }

    public function index(): void {
        $this->requirePermission('sales', 'view');
        $isGMReadOnly = $this->requireGMReadOnly();
        $status = $_GET['status'] ?? '';
        $where  = $status ? "WHERE so.status='$status'" : '';
        $orders = $this->db->fetchAll(
            "SELECT so.*, c.name AS customer_name, u.full_name AS created_by_name,
                    ar.id AS approval_request_id, ar.status AS approval_status,
                    acs.label AS approval_step_label
             FROM sales_orders so
             JOIN customers c ON so.customer_id=c.id
             JOIN users u ON so.created_by=u.id
             LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
             LEFT JOIN approval_steps acs ON acs.request_id=ar.id AND acs.step_order=ar.current_step
             $where ORDER BY so.created_at DESC"
        );
        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(status='pending') AS pending, SUM(status='approved') AS approved,
                    SUM(status='delivered') AS delivered, SUM(status='cancelled') AS cancelled,
                    SUM(status='rejected') AS rejected,
                    COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','rejected') THEN total_amount END),0) AS total_revenue,
                    COALESCE(SUM(CASE WHEN payment_status='paid' THEN amount_paid END),0) AS total_paid,
                    COALESCE(SUM(CASE WHEN payment_status IN ('unpaid','partial') AND status NOT IN ('cancelled','rejected') THEN total_amount - amount_paid END),0) AS total_outstanding
             FROM sales_orders"
        );
        $customers = $this->db->fetchAll("SELECT * FROM customers WHERE status='active' ORDER BY name");
        $products  = $this->db->fetchAll(
            "SELECT p.*, COALESCE(SUM(i.quantity),0) AS stock FROM products p
             LEFT JOIN inventory i ON p.id=i.product_id GROUP BY p.id ORDER BY p.name"
        );
        $this->view('sales/index', compact('orders', 'summary', 'customers', 'products', 'status', 'isGMReadOnly'));
    }

    public function create(): void {
        $this->requirePermission('sales', 'create');
        $customerId   = (int)($_POST['customer_id'] ?? 0);
        $customerName = trim($_POST['customer_name'] ?? '');
        $items        = $_POST['items'] ?? [];
        $delivDate    = $_POST['delivery_date'] ?? null;
        $notes        = trim($_POST['notes'] ?? '');
        $paymentType  = $_POST['payment_type'] ?? 'cash';
        $cashInvoiceNumber = trim($_POST['cash_invoice_number'] ?? '');

        // Auto-create customer if typed a new name
        if (!$customerId && $customerName) {
            $existing = $this->db->fetchOne("SELECT id FROM customers WHERE name=?", [$customerName], 's');
            if ($existing) {
                $customerId = $existing['id'];
            } else {
                $customerId = $this->db->insert(
                    "INSERT INTO customers (name, status) VALUES (?, 'active')",
                    [$customerName], 's'
                );
            }
        }

        if (!$customerId || empty($items)) $this->json(['success' => false, 'message' => 'Customer and items are required.']);
        
        $soNumber = 'SO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $invoiceNumber = null;
        $invoiceDate = null;
        
        // Generate invoice number for cash sales
        if ($paymentType === 'cash') {
            // Use provided cash invoice number or generate one
            if ($cashInvoiceNumber) {
                $invoiceNumber = $cashInvoiceNumber;
            } else {
                $invoiceNumber = 'CSI-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            }
            $invoiceDate = date('Y-m-d H:i:s');
        }
        
        $total = 0;
        foreach ($items as $item) $total += (float)($item['total_price'] ?? 0);
        
        $soId = $this->db->insert(
            "INSERT INTO sales_orders (customer_id, so_number, invoice_number, invoice_date, order_date, delivery_date, total_amount, payment_type, created_by) VALUES (?,?,?,?,NOW(),?,?,?,?)",
            [$customerId, $soNumber, $invoiceNumber, $invoiceDate, $delivDate, $total, $paymentType, $_SESSION['user_id']], 'issssdsi'
        );
        foreach ($items as $item) {
            $this->db->insert(
                "INSERT INTO sales_order_items (so_id, product_id, quantity, unit_price, total_price) VALUES (?,?,?,?,?)",
                [$soId, (int)$item['product_id'], (float)$item['quantity'], (float)$item['unit_price'], (float)$item['total_price']], 'iiddd'
            );
        }
        $this->db->insert(
            "INSERT INTO approval_workflows (module, reference_id, reference_type, requested_by) VALUES ('sales',?,?,?)",
            [$soId, 'sales_order', $_SESSION['user_id']], 'isi'
        );
        $this->approvalModel->createRequest([
            'module' => 'sales', 'reference_type' => 'sales_order', 'reference_id' => $soId,
            'title' => "Sales Order $soNumber" . ($invoiceNumber ? " / $invoiceNumber" : ''),
            'description' => "Total: " . number_format($total, 2) . " | Payment: " . ucfirst($paymentType) . ($notes ? " | $notes" : ''),
        ], $_SESSION['user_id']);
        $this->json(['success' => true, 'message' => "Order $soNumber created." . ($invoiceNumber ? " Invoice: $invoiceNumber" : '')]);
    }

    public function detail(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $order = $this->db->fetchOne(
            "SELECT so.*, c.name AS customer_name, c.phone AS customer_phone,
                    c.address AS customer_address, u.full_name AS created_by_name
             FROM sales_orders so JOIN customers c ON so.customer_id=c.id
             JOIN users u ON so.created_by=u.id WHERE so.id=?", [$id], 'i'
        );
        if (!$order) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Order not found.']; $this->redirect('/sales'); }
        $items = $this->db->fetchAll(
            "SELECT soi.*, p.name AS product_name, p.unit FROM sales_order_items soi
             JOIN products p ON soi.product_id=p.id WHERE soi.so_id=?", [$id], 'i'
        );
        $approval = $this->db->fetchOne(
            "SELECT ar.* FROM approval_requests ar WHERE ar.reference_type='sales_order' AND ar.reference_id=? ORDER BY ar.id DESC LIMIT 1", [$id], 'i'
        );
        $steps = $approval ? $this->approvalModel->getSteps($approval['id']) : [];
        $audit = $approval ? $this->approvalModel->getAuditLog($approval['id']) : [];
        
        // Fetch receipts for this order
        $receipts = $this->db->fetchAll(
            "SELECT r.*, u.full_name AS received_by_name 
             FROM receipts r 
             LEFT JOIN users u ON r.received_by = u.id
             WHERE r.reference_type='sale' AND r.reference_id=?
             ORDER BY r.receipt_date DESC",
            [$id], 'i'
        );
        
        $this->view('sales/detail', compact('order', 'items', 'approval', 'steps', 'audit', 'receipts'));
    }

    public function approve(): void {
        $this->requirePermission('sales', 'approve');
        $soId    = (int)($_POST['id'] ?? 0);
        $action  = $_POST['action'] ?? 'approved';
        $remarks = trim($_POST['remarks'] ?? '');

        // 'rejected' is now in the ENUM after migration
        $soStatus = $action === 'rejected' ? 'rejected' : 'approved';

        $this->db->query("UPDATE sales_orders SET status=?, approved_by=? WHERE id=?", [$soStatus, $_SESSION['user_id'], $soId], 'sii');
        $this->db->query(
            "UPDATE approval_workflows SET status=?, reviewed_by=?, remarks=?, reviewed_at=NOW() WHERE reference_id=? AND reference_type='sales_order' AND status='pending'",
            [$action, $_SESSION['user_id'], $remarks, $soId], 'siis'
        );
        $this->json(['success' => true, 'message' => "Order $action."]);
    }

    public function updateStatus(): void {
        $this->requirePermission('sales', 'update');
        $id     = (int)($_POST['id']     ?? 0);
        $status = $_POST['status']       ?? '';
        $allowed = ['pending','approved','processing','delivered','cancelled'];
        if (!in_array($status, $allowed)) $this->json(['success' => false, 'message' => 'Invalid status.']);

        $this->db->query("UPDATE sales_orders SET status=? WHERE id=?", [$status, $id], 'si');

        // Deduct stock from inventory when order is delivered
        if ($status === 'delivered') {
            $items = $this->db->fetchAll(
                "SELECT soi.product_id, soi.quantity FROM sales_order_items soi WHERE soi.so_id=?",
                [$id], 'i'
            );
            $warehouseId = (int)($_POST['warehouse_id'] ?? 0);

            foreach ($items as $item) {
                // Find warehouse with enough stock if not specified
                if (!$warehouseId) {
                    $wh = $this->db->fetchOne(
                        "SELECT warehouse_id FROM inventory WHERE product_id=? AND quantity>=? ORDER BY quantity DESC LIMIT 1",
                        [$item['product_id'], $item['quantity']], 'id'
                    );
                    $warehouseId = $wh ? (int)$wh['warehouse_id'] : 0;
                }

                if ($warehouseId) {
                    $this->inventoryModel->addMovement([
                        'product_id'     => $item['product_id'],
                        'warehouse_id'   => $warehouseId,
                        'type'           => 'out',
                        'quantity'       => $item['quantity'],
                        'reference_type' => 'sales_order',
                        'reference_id'   => $id,
                        'notes'          => "Stock out for SO #$id",
                    ]);
                }
            }
        }

        $this->json(['success' => true, 'message' => "Status updated to $status."]);
    }

    public function recordPayment(): void {
        $this->requirePermission('sales', 'update');
        $soId         = (int)($_POST['so_id'] ?? 0);
        $amount       = (float)($_POST['amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $paymentDate  = $_POST['payment_date'] ?? date('Y-m-d');
        $notes        = trim($_POST['notes'] ?? '');

        // Get sales order details
        $order = $this->db->fetchOne(
            "SELECT so.*, c.name AS customer_name FROM sales_orders so 
             JOIN customers c ON so.customer_id=c.id WHERE so.id=?",
            [$soId], 'i'
        );
        if (!$order) $this->json(['success' => false, 'message' => 'Order not found.']);
        if ($amount <= 0) $this->json(['success' => false, 'message' => 'Amount must be greater than zero.']);

        // Calculate new amount paid
        $currentPaid = (float)($order['amount_paid'] ?? 0);
        $newPaid = $currentPaid + $amount;
        $totalAmount = (float)$order['total_amount'];

        // Determine payment status
        $paymentStatus = 'unpaid';
        if ($newPaid >= $totalAmount) {
            $paymentStatus = 'paid';
            $newPaid = $totalAmount; // Cap at total amount
        } elseif ($newPaid > 0) {
            $paymentStatus = 'partial';
        }

        // Determine receipt type based on order payment_type
        $receiptType = ($order['payment_type'] === 'charge' || $order['payment_type'] === 'credit') 
                       ? 'charge_invoice' 
                       : 'cash_receipt';

        // Generate receipt number
        $receiptNum = 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Get items summary for description
        $items = $this->db->fetchAll(
            "SELECT soi.*, p.name AS product_name FROM sales_order_items soi
             JOIN products p ON soi.product_id=p.id WHERE soi.so_id=?",
            [$soId], 'i'
        );
        $itemDesc = count($items) . ' item(s): ' . implode(', ', array_map(fn($i) => $i['product_name'], array_slice($items, 0, 3)));
        if (count($items) > 3) $itemDesc .= '...';

        // Create receipt in finance
        $receiptId = $this->db->insert(
            "INSERT INTO receipts (reference_type, reference_id, amount, payment_method, receipt_date, 
                                   receipt_number, payer_name, receipt_type, item_description, received_by, notes, created_by)
             VALUES ('sale', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$soId, $amount, $paymentMethod, $paymentDate, $receiptNum, $order['customer_name'], 
             $receiptType, $itemDesc, $_SESSION['user_id'], $notes, $_SESSION['user_id']],
            'idssssssiis'
        );

        // Update sales order payment info
        $this->db->query(
            "UPDATE sales_orders SET payment_status=?, amount_paid=?, receipt_id=? WHERE id=?",
            [$paymentStatus, $newPaid, $receiptId, $soId], 'sdii'
        );

        // Create journal entry
        $this->_createJournalEntry([
            'entry_date'     => $paymentDate,
            'reference'      => $receiptNum,
            'description'    => ($receiptType === 'charge_invoice' ? 'Charge Invoice' : 'Cash Receipt') . 
                               " for SO {$order['so_number']} - {$order['customer_name']}",
            'debit_account'  => $paymentMethod === 'cash' ? 'Cash' : 'Bank',
            'credit_account' => $receiptType === 'charge_invoice' ? 'Accounts Receivable' : 'Sales Revenue',
            'amount'         => $amount,
            'source_type'    => 'receipt',
            'source_id'      => $receiptId,
        ]);

        $this->json([
            'success' => true, 
            'message' => "Payment recorded. Receipt: $receiptNum",
            'receipt_id' => $receiptId,
            'payment_status' => $paymentStatus
        ]);
    }

    private function _createJournalEntry(array $data): void {
        $this->db->insert(
            "INSERT INTO journal_entries
                (entry_date, reference, description, debit_account, credit_account, amount, source_type, source_id, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [$data['entry_date'], $data['reference'] ?? null, $data['description'],
             $data['debit_account'], $data['credit_account'], $data['amount'],
             $data['source_type'] ?? null, $data['source_id'] ?? null, $_SESSION['user_id'] ?? null],
            'sssssdsii'
        );
    }

    public function delete(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $order = $this->db->fetchOne("SELECT * FROM sales_orders WHERE id=?", [$id], 'i');
        if (!$order) $this->json(['success' => false, 'message' => 'Order not found.']);

        // Only allow deleting pending or rejected orders
        if (!in_array($order['status'], ['pending', 'rejected'])) {
            $this->json(['success' => false, 'message' => 'Only pending or rejected orders can be deleted.']);
        }

        // Delete related records
        $this->db->query("DELETE FROM sales_order_items WHERE so_id=?", [$id], 'i');
        $this->db->query("DELETE FROM approval_workflows WHERE reference_id=? AND reference_type='sales_order'", [$id], 'i');
        // Delete approval request and steps if exists
        $ar = $this->db->fetchOne("SELECT id FROM approval_requests WHERE reference_type='sales_order' AND reference_id=?", [$id], 'i');
        if ($ar) {
            $this->db->query("DELETE FROM approval_steps WHERE request_id=?", [$ar['id']], 'i');
            $this->db->query("DELETE FROM approval_audit_log WHERE request_id=?", [$ar['id']], 'i');
            $this->db->query("DELETE FROM approval_requests WHERE id=?", [$ar['id']], 'i');
        }
        $this->db->query("DELETE FROM sales_orders WHERE id=?", [$id], 'i');

        $this->json(['success' => true, 'message' => "Order {$order['so_number']} deleted."]);
    }

    public function markPaid(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        
        $order = $this->db->fetchOne("SELECT * FROM sales_orders WHERE id=?", [$id], 'i');
        if (!$order) {
            $this->json(['success' => false, 'message' => 'Order not found.']);
            return;
        }

        // Update order to paid status and delivered
        $this->db->query(
            "UPDATE sales_orders SET payment_status='paid', amount_paid=total_amount, status='delivered' WHERE id=?",
            [$id], 'i'
        );

        $this->json(['success' => true, 'message' => 'Order marked as paid successfully.']);
    }


    public function customers(): void {
        $this->requireAuth();
        $customers = $this->db->fetchAll(
            "SELECT c.*, COUNT(so.id) AS order_count, COALESCE(SUM(so.total_amount),0) AS total_spent
             FROM customers c LEFT JOIN sales_orders so ON so.customer_id=c.id AND so.status!='cancelled'
             GROUP BY c.id ORDER BY c.name"
        );
        $this->view('sales/customers', compact('customers'));
    }

    public function saveCustomer(): void {
        $this->requirePermission('sales', 'create');
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name'           => trim($_POST['name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'email'          => trim($_POST['email'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
            'status'         => $_POST['status'] ?? 'active',
        ];
        if (empty($data['name'])) $this->json(['success' => false, 'message' => 'Name is required.']);
        if ($id) {
            $this->db->query("UPDATE customers SET name=?,contact_person=?,phone=?,email=?,address=?,status=? WHERE id=?",
                [...array_values($data), $id], 'ssssssi');
        } else {
            $this->db->insert("INSERT INTO customers (name,contact_person,phone,email,address,status) VALUES (?,?,?,?,?,?)",
                array_values($data), 'ssssss');
        }
        $this->json(['success' => true, 'message' => 'Customer saved.']);
    }

    // Print Invoice
    public function invoicePrint(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        
        $order = $this->db->fetchOne(
            "SELECT so.*, c.name AS customer_name, c.address AS customer_address,
                    c.phone AS customer_phone, c.email AS customer_email,
                    u.full_name AS created_by_name
             FROM sales_orders so
             JOIN customers c ON so.customer_id = c.id
             LEFT JOIN users u ON so.created_by = u.id
             WHERE so.id = ?",
            [$id], 'i'
        );
        
        if (!$order) {
            http_response_code(404);
            echo 'Sales order not found';
            exit;
        }
        
        $items = $this->db->fetchAll(
            "SELECT soi.*, p.name AS product_name, p.unit
             FROM sales_order_items soi
             JOIN products p ON soi.product_id = p.id
             WHERE soi.so_id = ?
             ORDER BY soi.id",
            [$id], 'i'
        );
        
        $this->view('sales/invoice_print', compact('order', 'items'));
    }
}

