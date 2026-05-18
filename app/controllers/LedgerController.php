<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ApprovalModel.php';

class LedgerController extends Controller {
    private ApprovalModel $approvalModel;

    public function __construct() {
        parent::__construct();
        $this->approvalModel = new ApprovalModel();
    }

    // &#8369;8369;”€ Farmer list with balances &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function index(): void {
        $this->requirePermission('ledger', 'view');
        $farmers = $this->db->fetchAll(
            "SELECT f.*,
                    COALESCE(SUM(CASE WHEN fl.type='credit' THEN fl.amount ELSE 0 END),0) AS total_credits,
                    COALESCE(SUM(CASE WHEN fl.type='debit'  THEN fl.amount ELSE 0 END),0) AS total_debits,
                    COALESCE(SUM(CASE WHEN fl.type='credit' THEN fl.amount ELSE -fl.amount END),0) AS balance
             FROM farmers f
             LEFT JOIN farmer_ledger fl ON fl.farmer_id=f.id
             GROUP BY f.id ORDER BY f.full_name"
        );

        $summary = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT farmer_id) AS active_accounts,
                    COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE 0 END),0) AS total_credits,
                    COALESCE(SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END),0) AS total_debits
             FROM farmer_ledger"
        );

        $pendingWithdrawals = $this->db->fetchAll(
            "SELECT fw.*, f.full_name AS farmer_name
             FROM farmer_withdrawals fw JOIN farmers f ON fw.farmer_id=f.id
             WHERE fw.status='pending' ORDER BY fw.created_at DESC"
        );

        $this->view('ledger/index', compact('farmers', 'summary', 'pendingWithdrawals'));
    }

    // &#8369;8369;”€ Farmer ledger detail &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function farmer(): void {
        $this->requireAuth();
        $farmerId = (int)($_GET['id'] ?? 0);
        $farmer   = $this->db->fetchOne("SELECT * FROM farmers WHERE id=?", [$farmerId], 'i');
        if (!$farmer) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Farmer not found.']; $this->redirect('/ledger'); }

        $entries = $this->db->fetchAll(
            "SELECT fl.*, u.full_name AS recorded_by_name
             FROM farmer_ledger fl JOIN users u ON fl.recorded_by=u.id
             WHERE fl.farmer_id=? ORDER BY fl.transaction_date DESC, fl.id DESC",
            [$farmerId], 'i'
        );

        $balance = $this->db->fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE -amount END),0) AS balance
             FROM farmer_ledger WHERE farmer_id=?", [$farmerId], 'i'
        );

        $withdrawals = $this->db->fetchAll(
            "SELECT fw.*, u.full_name AS requested_by_name, u2.full_name AS approved_by_name,
                    ar.id AS approval_request_id, ar.status AS approval_status
             FROM farmer_withdrawals fw
             JOIN users u ON fw.requested_by=u.id
             LEFT JOIN users u2 ON fw.approved_by=u2.id
             LEFT JOIN approval_requests ar ON ar.reference_type='withdrawal' AND ar.reference_id=fw.id
             WHERE fw.farmer_id=? ORDER BY fw.created_at DESC",
            [$farmerId], 'i'
        );

        $this->view('ledger/farmer', compact('farmer', 'entries', 'balance', 'withdrawals'));
    }

    // &#8369;8369;”€ Manual credit/debit entry &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function addEntry(): void {
        $this->requireAuth();
        $farmerId = (int)($_POST['farmer_id'] ?? 0);
        $type     = $_POST['type']     ?? '';
        $category = $_POST['category'] ?? 'other';
        $amount   = (float)($_POST['amount'] ?? 0);
        $desc     = trim($_POST['description'] ?? '');
        $date     = $_POST['transaction_date'] ?? date('Y-m-d');

        if (!$farmerId || !in_array($type, ['credit','debit']) || $amount <= 0) {
            $this->json(['success' => false, 'message' => 'Farmer, type and amount are required.']);
        }

        // Check sufficient balance for debit
        if ($type === 'debit') {
            $bal = $this->_getBalance($farmerId);
            if ($bal < $amount) {
                $this->json(['success' => false, 'message' => "Insufficient balance. Current: &#8369;" . number_format($bal, 2)]);
            }
        }

        $this->_recordEntry($farmerId, $type, $category, $amount, $desc, $date, null, null);
        $this->json(['success' => true, 'message' => ucfirst($type) . " of &#8369;" . number_format($amount, 2) . " recorded."]);
    }

    // &#8369;8369;”€ Withdrawal request &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function requestWithdrawal(): void {
        $this->requireAuth();
        $farmerId = (int)($_POST['farmer_id'] ?? 0);
        $amount   = (float)($_POST['amount']    ?? 0);
        $reason   = trim($_POST['reason']       ?? '');

        if (!$farmerId || $amount <= 0) {
            $this->json(['success' => false, 'message' => 'Farmer and amount are required.']);
        }

        $bal = $this->_getBalance($farmerId);
        if ($bal < $amount) {
            $this->json(['success' => false, 'message' => "Insufficient balance. Current: &#8369;" . number_format($bal, 2)]);
        }

        $farmer = $this->db->fetchOne("SELECT full_name FROM farmers WHERE id=?", [$farmerId], 'i');

        $wId = $this->db->insert(
            "INSERT INTO farmer_withdrawals (farmer_id, amount, reason, requested_by) VALUES (?,?,?,?)",
            [$farmerId, $amount, $reason, $_SESSION['user_id']], 'idsi'
        );

        $this->approvalModel->createRequest([
            'module'         => 'withdrawal',
            'reference_type' => 'withdrawal',
            'reference_id'   => $wId,
            'title'          => "Withdrawal: " . ($farmer['full_name'] ?? '') . " ₱" . number_format($amount, 2),
            'description'    => $reason ?: 'No reason provided',
        ], $_SESSION['user_id']);

        $this->json(['success' => true, 'message' => 'Withdrawal request submitted for approval.']);
    }

    // &#8369;8369;”€ Approve/release withdrawal &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function approveWithdrawal(): void {
        $this->requireAuth();
        
        // Only GM can approve withdrawals
        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'gm' && $role !== 'admin') {
            $this->json(['success' => false, 'message' => 'Only GM can approve withdrawals.']);
            return;
        }
        
        $id     = (int)($_POST['id']     ?? 0);
        $action = $_POST['action']       ?? '';

        $w = $this->db->fetchOne("SELECT * FROM farmer_withdrawals WHERE id=?", [$id], 'i');
        if (!$w) {
            $this->json(['success' => false, 'message' => 'Withdrawal not found.']);
            return;
        }

        if ($action === 'approved') {
            if ($w['status'] !== 'pending') {
                $this->json(['success' => false, 'message' => 'Only pending withdrawals can be approved.']);
                return;
            }
            $bal = $this->_getBalance($w['farmer_id']);
            if ($bal < $w['amount']) {
                $this->json(['success' => false, 'message' => 'Insufficient balance at time of approval.']);
                return;
            }
            $this->db->query(
                "UPDATE farmer_withdrawals SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?",
                [$_SESSION['user_id'], $id], 'ii'
            );
            $this->json(['success' => true, 'message' => 'Withdrawal approved.']);
            return;
        }

        if ($action === 'released') {
            if ($w['status'] !== 'approved') {
                $this->json(['success' => false, 'message' => 'Must be approved before releasing.']);
            }
            $farmer = $this->db->fetchOne("SELECT full_name FROM farmers WHERE id=?", [$w['farmer_id']], 'i');
            $this->_recordEntry(
                $w['farmer_id'], 'debit', 'withdrawal',
                $w['amount'], "Withdrawal released: " . ($w['reason'] ?? ''),
                date('Y-m-d'), 'withdrawal', $id
            );
            $this->db->query(
                "UPDATE farmer_withdrawals SET status='released', released_at=NOW() WHERE id=?",
                [$id], 'i'
            );
            $this->json(['success' => true, 'message' => 'Withdrawal released and balance deducted.']);
        }

        if ($action === 'rejected') {
            $this->db->query("UPDATE farmer_withdrawals SET status='rejected' WHERE id=?", [$id], 'i');
            $this->json(['success' => true, 'message' => 'Withdrawal rejected.']);
        }

        $this->json(['success' => false, 'message' => 'Invalid action.']);
    }

    // &#8369;8369;”€ Called by SalesController when SO is delivered &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function creditFromSale(int $farmerId, float $amount, int $soId, string $soNumber): void {
        $this->_recordEntry(
            $farmerId, 'credit', 'sale',
            $amount, "Sale proceeds: $soNumber",
            date('Y-m-d'), 'sales_order', $soId
        );
    }

    // &#8369;8369;”€ Private helpers &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    private function _getBalance(int $farmerId): float {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE -amount END),0) AS bal
             FROM farmer_ledger WHERE farmer_id=?", [$farmerId], 'i'
        );
        return (float)($row['bal'] ?? 0);
    }

    private function _recordEntry(
        int $farmerId, string $type, string $category,
        float $amount, string $desc, string $date,
        ?string $refType, ?int $refId
    ): void {
        $balance = $this->_getBalance($farmerId);
        $newBalance = $type === 'credit' ? $balance + $amount : $balance - $amount;

        $this->db->insert(
            "INSERT INTO farmer_ledger
                (farmer_id, type, category, reference_type, reference_id, amount, running_balance, description, transaction_date, recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$farmerId, $type, $category, $refType, $refId, $amount, $newBalance, $desc, $date, $_SESSION['user_id'] ?? 1],
            'isssiddssi'
        );
    }
}

