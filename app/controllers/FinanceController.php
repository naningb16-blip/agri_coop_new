<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ApprovalModel.php';

class FinanceController extends Controller {
    private ApprovalModel $approvalModel;

    public function __construct() {
        parent::__construct();
        $this->approvalModel = new ApprovalModel();
    }

    // &#8369;8369;”€ Main index (tabbed) &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function index(): void {
        $this->requirePermission('finance', 'view');
        $isGMReadOnly = $this->requireGMReadOnly();
        $tab  = $_GET['tab']  ?? 'overview';
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $summary = $this->_summary($from, $to);
        $receipts  = $this->_receipts($from, $to);
        $expenses  = $this->_expenses($from, $to);
        $payrolls  = $this->_payrolls($from, $to);
        $purchases = $this->_purchases($from, $to);
        $journal   = $this->_journal($from, $to);

        $this->view('finance/index', compact(
            'tab', 'from', 'to', 'summary',
            'receipts', 'expenses', 'payrolls', 'purchases', 'journal', 'isGMReadOnly'
        ));
    }

    // &#8369;8369;”€ Cash Receipts &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function createReceipt(): void {
        $this->requireAuth();
        $refType     = $_POST['reference_type'] ?? 'other';
        $refId       = (int)($_POST['reference_id'] ?? 0) ?: null;
        $amount      = (float)($_POST['amount'] ?? 0);
        $method      = $_POST['payment_method']   ?? 'cash';
        $date        = $_POST['receipt_date']      ?? date('Y-m-d');
        $payer       = trim($_POST['payer_name']   ?? '');
        $notes       = trim($_POST['notes']        ?? '');
        $receiptType = in_array($_POST['receipt_type'] ?? '', ['cash_receipt','charge_invoice'])
                       ? $_POST['receipt_type'] : 'cash_receipt';
        $itemDesc    = trim($_POST['item_description'] ?? '');
        $quantity    = $_POST['quantity'] !== '' ? (float)$_POST['quantity'] : null;
        $unit        = trim($_POST['unit'] ?? '');

        if ($amount <= 0) $this->json(['success' => false, 'message' => 'Amount must be greater than zero.']);
        if (empty($payer)) $this->json(['success' => false, 'message' => 'Name is required.']);

        $rn = 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $id = $this->db->insert(
            "INSERT INTO receipts (reference_type, reference_id, amount, payment_method, receipt_date, notes, received_by)
             VALUES (?,?,?,?,?,?,?)",
            [$refType, $refId, $amount, $method, $date,
             "[$rn] Payer: $payer | Type: $receiptType" . ($itemDesc ? " | $itemDesc" : '') . ($notes ? " | $notes" : ''),
             $_SESSION['user_id']],
            'ssisssi'
        );

        $this->_journal([
            'entry_date'     => $date,
            'reference'      => $rn,
            'description'    => ($receiptType === 'charge_invoice' ? 'Charge Invoice' : 'Cash Receipt') . ": $payer" . ($itemDesc ? " &#8369; $itemDesc" : ''),
            'debit_account'  => $method === 'cash' ? 'Cash' : 'Bank',
            'credit_account' => $refType === 'sale' ? 'Accounts Receivable' : 'Other Income',
            'amount'         => $amount,
            'source_type'    => 'receipt',
            'source_id'      => $id,
        ]);

        $this->json(['success' => true, 'message' => "Receipt $rn recorded."]);
    }

    // &#8369;8369;”€ Expenses &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function createExpense(): void {
        $this->requireAuth();
        $amount       = (float)($_POST['amount'] ?? 0);
        $date         = $_POST['expense_date']   ?? date('Y-m-d');
        $cat          = trim($_POST['category']  ?? 'other');
        $desc         = trim($_POST['description'] ?? '');

        if ($amount <= 0) {
            $this->json(['success' => false, 'message' => 'Amount required.']);
            return;
        }
        if (empty($cat)) {
            $this->json(['success' => false, 'message' => 'Category required.']);
            return;
        }

        $id = $this->db->insert(
            "INSERT INTO expenses (category, description, amount, expense_date, status, created_by)
             VALUES (?,?,?,?,'pending',?)",
            [$cat, $desc, $amount, $date, $_SESSION['user_id']],
            'ssdsi'
        );

        // Build title with category label
        $catLabels = [
            'utilities_electric' => 'Electric Bill',
            'utilities_water' => 'Water Bill',
            'utilities_internet' => 'Internet Bill',
            'utilities_phone' => 'Phone Bill',
            'rent' => 'Rent',
            'supplies' => 'Office Supplies',
            'maintenance' => 'Maintenance',
            'transportation' => 'Transportation',
            'professional_fees' => 'Professional Fees',
            'insurance' => 'Insurance',
            'taxes' => 'Taxes',
            'salaries' => 'Salaries',
            'other' => 'Other Expense'
        ];
        $catLabel = $catLabels[$cat] ?? ucfirst($cat);
        $title = "$catLabel: &#8369;" . number_format($amount, 2);
        if ($vendorName) $title .= " - $vendorName";

        $approvalDesc = $desc ?: 'No description provided';

        // Approval workflow
        $this->approvalModel->createRequest([
            'module'         => 'finance',
            'reference_type' => 'expense',
            'reference_id'   => $id,
            'title'          => $title,
            'description'    => $approvalDesc,
        ], $_SESSION['user_id']);

        $this->json(['success' => true, 'message' => 'Expense submitted for approval.']);
    }

    public function deleteExpense(): void {
        $this->requirePermission('finance', 'approve');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) $this->json(['success' => false, 'message' => 'Invalid expense.']);
        $this->db->query("DELETE FROM expenses WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Expense deleted.']);
    }

    public function approveExpense(): void {
        $this->requireAuth();
        
        // Only GM can approve expenses/withdrawals
        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'gm' && $role !== 'admin') {
            $this->json(['success' => false, 'message' => 'Only GM can approve expenses.']);
            return;
        }
        
        $id     = (int)($_POST['id']     ?? 0);
        $action = $_POST['action']       ?? '';
        if (!in_array($action, ['approved', 'rejected'])) $this->json(['success' => false, 'message' => 'Invalid action.']);

        $exp = $this->db->fetchOne("SELECT * FROM expenses WHERE id=?", [$id], 'i');
        if (!$exp) $this->json(['success' => false, 'message' => 'Expense not found.']);

        $this->db->query(
            "UPDATE expenses SET status=?, approved_by=? WHERE id=?",
            [$action, $_SESSION['user_id'], $id], 'sii'
        );

        if ($action === 'approved') {
            $this->_journal([
                'entry_date'     => $exp['expense_date'],
                'reference'      => 'EXP-' . $id,
                'description'    => "Expense approved: " . ($exp['category'] ?? '') . " &#8369; " . ($exp['description'] ?? ''),
                'debit_account'  => $exp['category'] ?? 'Operating Expense',
                'credit_account' => 'Cash',
                'amount'         => $exp['amount'],
                'source_type'    => 'expense',
                'source_id'      => $id,
            ]);
        }

        $this->json(['success' => true, 'message' => "Expense $action."]);
    }

    // &#8369;8369;”€ Payroll &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function payrollList(): void {
        $this->requireAuth();
        $payrolls  = $this->db->fetchAll(
            "SELECT pr.*, e.full_name, e.position FROM payroll pr
             JOIN employees e ON pr.employee_id=e.id ORDER BY pr.created_at DESC LIMIT 100"
        );
        $employees = $this->db->fetchAll("SELECT * FROM employees WHERE status='active' ORDER BY full_name");
        $this->view('finance/payroll', compact('payrolls', 'employees'));
    }

    public function approvePayroll(): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['admin', 'gm'])) {
            $this->json(['success' => false, 'message' => 'Only GM can approve payroll.']);
        }
        $id = (int)($_POST['id'] ?? 0);
        $pr = $this->db->fetchOne("SELECT * FROM payroll WHERE id=?", [$id], 'i');
        if (!$pr) $this->json(['success' => false, 'message' => 'Not found.']);

        $this->db->query(
            "UPDATE payroll SET status='approved', approved_by=? WHERE id=?",
            [$_SESSION['user_id'], $id], 'ii'
        );

        $this->_journal([
            'entry_date'     => $pr['period_end'],
            'reference'      => 'PAY-' . $id,
            'description'    => "Payroll approved for employee #" . $pr['employee_id'],
            'debit_account'  => 'Salaries Expense',
            'credit_account' => 'Cash',
            'amount'         => $pr['net_pay'],
            'source_type'    => 'payroll',
            'source_id'      => $id,
        ]);

        $this->json(['success' => true, 'message' => 'Payroll approved.']);
    }

    public function markPayrollPaid(): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['admin', 'gm'])) {
            $this->json(['success' => false, 'message' => 'Only GM can mark payroll as paid.']);
        }
        $id = (int)($_POST['id'] ?? 0);
        $this->db->query("UPDATE payroll SET status='paid' WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Payroll marked as paid.']);
    }

    public function approveAllPayroll(): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['admin', 'gm'])) {
            $this->json(['success' => false, 'message' => 'Only GM can approve payroll.']);
        }

        $remarks = trim($_POST['remarks'] ?? '');

        // Get all pending payrolls
        $pending = $this->db->fetchAll("SELECT * FROM payroll WHERE status='pending'");
        if (empty($pending)) {
            $this->json(['success' => false, 'message' => 'No pending payrolls to approve.']);
        }

        foreach ($pending as $pr) {
            $this->db->query(
                "UPDATE payroll SET status='approved', approved_by=? WHERE id=?",
                [$_SESSION['user_id'], $pr['id']], 'ii'
            );
            $this->_journal([
                'entry_date'     => $pr['period_end'],
                'reference'      => 'PAY-' . $pr['id'],
                'description'    => "Payroll approved for employee #" . $pr['employee_id'] . ($remarks ? " | $remarks" : ''),
                'debit_account'  => 'Salaries Expense',
                'credit_account' => 'Cash',
                'amount'         => $pr['net_pay'],
                'source_type'    => 'payroll',
                'source_id'      => $pr['id'],
            ]);
        }

        $this->json(['success' => true, 'message' => count($pending) . ' payroll record(s) approved successfully.']);
    }

    // &#8369;8369;”€ Financial Report (JSON) &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function report(): void {
        $this->requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $this->json([
            'summary'   => $this->_summary($from, $to),
            'receipts'  => $this->_receipts($from, $to),
            'expenses'  => $this->_expenses($from, $to),
            'payrolls'  => $this->_payrolls($from, $to),
            'purchases' => $this->_purchases($from, $to),
            'journal'   => $this->_journal($from, $to),
            'from'      => $from,
            'to'        => $to,
        ]);
    }

    // &#8369;8369;”€ Inventory value snapshot &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function inventoryValue(): void {
        $this->requireAuth();
        $data = $this->db->fetchAll(
            "SELECT p.name, p.unit, p.category,
                    COALESCE(SUM(i.quantity),0) AS total_qty,
                    COALESCE(AVG(poi.unit_price),0) AS avg_cost,
                    COALESCE(SUM(i.quantity) * AVG(poi.unit_price),0) AS inventory_value
             FROM products p
             LEFT JOIN inventory i ON i.product_id=p.id
             LEFT JOIN purchase_order_items poi ON poi.item_name=p.name
             GROUP BY p.id ORDER BY inventory_value DESC"
        );
        $total = array_sum(array_column($data, 'inventory_value'));
        $this->json(['items' => $data, 'total_value' => $total]);
    }

    // &#8369;8369;”€ Print receipt &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function receiptPrint(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $receipt = $this->db->fetchOne(
            "SELECT r.*, u.full_name AS received_by_name
             FROM receipts r LEFT JOIN users u ON r.received_by=u.id
             WHERE r.id=?", [$id], 'i'
        );
        if (!$receipt) { http_response_code(404); echo 'Receipt not found'; exit; }
        $this->view('finance/receipt_print', compact('receipt'));
    }

    // &#8369;8369;”€ Print report &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function reportPrint(): void {
        $this->requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $summary  = $this->_summary($from, $to);
        $receipts = $this->_receipts($from, $to);
        $expenses = $this->_expenses($from, $to);
        $payrolls = $this->_payrolls($from, $to);

        $this->view('finance/report_print', compact('summary', 'receipts', 'expenses', 'payrolls', 'from', 'to'));
    }

    // &#8369;8369;”€ Private helpers &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    private function _summary(string $from, string $to): array {
        // Sales Revenue = Cash received from Sales department (receipts with reference_type='sale')
        $salesRevenue = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) AS t FROM receipts 
             WHERE receipt_date BETWEEN ? AND ? AND reference_type='sale'",
            [$from, $to], 'ss'
        )['t'] ?? 0);

        $expenses = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) AS t FROM expenses WHERE expense_date BETWEEN ? AND ? AND status='approved'",
            [$from, $to], 'ss'
        )['t'] ?? 0);

        $payroll = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(net_pay),0) AS t FROM payroll WHERE period_end BETWEEN ? AND ? AND status IN ('approved','paid')",
            [$from, $to], 'ss'
        )['t'] ?? 0);

        $purchases = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(total_amount),0) AS t FROM purchase_orders WHERE order_date BETWEEN ? AND ? AND status IN ('approved','delivered')",
            [$from, $to], 'ss'
        )['t'] ?? 0);

        $totalCosts = $expenses + $payroll + $purchases;
        $net        = $salesRevenue - $totalCosts;

        return compact('salesRevenue', 'expenses', 'payroll', 'purchases', 'totalCosts', 'net');
    }

    private function _receipts(string $from, string $to): array {
        return $this->db->fetchAll(
            "SELECT r.*, u.full_name AS received_by_name
             FROM receipts r LEFT JOIN users u ON r.received_by=u.id
             WHERE r.receipt_date BETWEEN ? AND ? ORDER BY r.receipt_date DESC",
            [$from, $to], 'ss'
        );
    }

    private function _expenses(string $from, string $to): array {
        return $this->db->fetchAll(
            "SELECT e.*, u.full_name AS created_by_name, u2.full_name AS approved_by_name,
                    ar.id AS approval_request_id, ar.status AS approval_status
             FROM expenses e
             LEFT JOIN users u ON e.created_by=u.id
             LEFT JOIN users u2 ON e.approved_by=u2.id
             LEFT JOIN approval_requests ar ON ar.reference_type='expense' AND ar.reference_id=e.id
             WHERE e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date DESC",
            [$from, $to], 'ss'
        );
    }

    private function _payrolls(string $from, string $to): array {
        return $this->db->fetchAll(
            "SELECT pr.*, e.full_name, e.position, e.department_id,
                    d.name AS dept_name
             FROM payroll pr
             JOIN employees e ON pr.employee_id=e.id
             LEFT JOIN departments d ON e.department_id=d.id
             WHERE pr.period_end BETWEEN ? AND ? ORDER BY pr.created_at DESC",
            [$from, $to], 'ss'
        );
    }

    private function _purchases(string $from, string $to): array {
        return $this->db->fetchAll(
            "SELECT po.*, s.name AS supplier_name
             FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id
             WHERE po.order_date BETWEEN ? AND ? AND po.status IN ('approved','delivered')
             ORDER BY po.order_date DESC",
            [$from, $to], 'ss'
        );
    }

    private function _journal(string|array $fromOrData, string $to = ''): array {
        // When called with array, insert a journal entry
        if (is_array($fromOrData)) {
            $d = $fromOrData;
            $this->db->insert(
                "INSERT INTO journal_entries
                    (entry_date, reference, description, debit_account, credit_account, amount, source_type, source_id, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [$d['entry_date'], $d['reference'] ?? null, $d['description'],
                 $d['debit_account'], $d['credit_account'], $d['amount'],
                 $d['source_type'] ?? null, $d['source_id'] ?? null, $_SESSION['user_id'] ?? null],
                'sssssdsii'
            );
            return [];
        }
        // When called with date range, fetch entries
        return $this->db->fetchAll(
            "SELECT je.*, u.full_name AS created_by_name FROM journal_entries je
             LEFT JOIN users u ON je.created_by=u.id
             WHERE je.entry_date BETWEEN ? AND ? ORDER BY je.entry_date DESC, je.id DESC",
            [$fromOrData, $to], 'ss'
        );
    }
}

