<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/InventoryModel.php';
require_once __DIR__ . '/../models/ApprovalModel.php';

class QAController extends Controller {
    private InventoryModel $inventoryModel;
    private ApprovalModel  $approvalModel;

    public function __construct() {
        parent::__construct();
        $this->inventoryModel = new InventoryModel();
        $this->approvalModel  = new ApprovalModel();
    }

    public function index(): void {
        $this->requirePermission('qa', 'view');
        $filter = $_GET['result'] ?? '';
        $where  = $filter ? "WHERE qi.result='$filter'" : '';

        $inspections = $this->db->fetchAll(
            "SELECT qi.*, p.name AS product_name, p.unit, u.full_name AS inspector_name,
                    w.name AS warehouse_name
             FROM qa_inspections qi
             JOIN products p ON qi.product_id = p.id
             JOIN users u ON qi.inspected_by = u.id
             LEFT JOIN warehouses w ON qi.warehouse_id = w.id
             $where ORDER BY qi.created_at DESC"
        );

        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(result='passed')      AS passed,
                    SUM(result='failed')      AS failed,
                    SUM(result='conditional') AS conditional,
                    COALESCE(SUM(approved_qty),0) AS total_approved,
                    COALESCE(SUM(rejected_qty),0) AS total_rejected
             FROM qa_inspections"
        );

        $products   = $this->inventoryModel->getProducts();
        $warehouses = $this->inventoryModel->getWarehouses();
        $returns    = $this->db->fetchAll(
            "SELECT sr.*, p.name AS product_name FROM stock_returns sr
             JOIN products p ON sr.product_id=p.id WHERE sr.status='pending' ORDER BY sr.created_at DESC"
        );

        $this->view('qa/index', compact('inspections', 'summary', 'products', 'warehouses', 'returns', 'filter'));
    }

    public function create(): void {
        $this->requireAuth();
        $data = [
            'reference_type'  => $_POST['reference_type']  ?? 'batch',
            'reference_id'    => (int)($_POST['reference_id']    ?? 0),
            'product_id'      => (int)($_POST['product_id']      ?? 0),
            'warehouse_id'    => (int)($_POST['warehouse_id']    ?? 0) ?: null,
            'inspection_date' => $_POST['inspection_date']  ?? date('Y-m-d'),
            'result'          => $_POST['result']           ?? 'passed',
            'moisture_pct'    => (float)($_POST['moisture_pct']    ?? 0),
            'foreign_matter'  => (float)($_POST['foreign_matter']  ?? 0),
            'germination_pct' => (float)($_POST['germination_pct'] ?? 0),
            'sample_qty'      => (float)($_POST['sample_qty']      ?? 0),
            'approved_qty'    => (float)($_POST['approved_qty']    ?? 0),
            'rejected_qty'    => (float)($_POST['rejected_qty']    ?? 0),
            'remarks'         => trim($_POST['remarks']     ?? ''),
            'inspected_by'    => $_SESSION['user_id'],
        ];

        if (!$data['product_id']) $this->json(['success' => false, 'message' => 'Product is required.']);

        $id = $this->db->insert(
            "INSERT INTO qa_inspections
                (reference_type, reference_id, product_id, warehouse_id, inspected_by,
                 inspection_date, result, moisture_pct, foreign_matter, germination_pct,
                 sample_qty, approved_qty, rejected_qty, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['reference_type'],
                $data['reference_id'],
                $data['product_id'],
                $data['warehouse_id'],
                $data['inspected_by'],
                $data['inspection_date'],
                $data['result'],
                $data['moisture_pct'],
                $data['foreign_matter'],
                $data['germination_pct'],
                $data['sample_qty'],
                $data['approved_qty'],
                $data['rejected_qty'],
                $data['remarks'],
            ],
            'siiiissdddddds'
        );

        // If failed &#8369; move rejected qty to a separate stock adjustment
        if ($data['result'] === 'failed' && $data['rejected_qty'] > 0 && $data['warehouse_id']) {
            $this->inventoryModel->addMovement([
                'product_id'     => $data['product_id'],
                'warehouse_id'   => $data['warehouse_id'],
                'type'           => 'adjustment',
                'quantity'       => $data['rejected_qty'],
                'reference_type' => 'qa_inspection',
                'reference_id'   => $id,
                'notes'          => 'QA rejected: ' . $data['remarks'],
            ]);
        }

        // If return reference &#8369; update return status
        if ($data['reference_type'] === 'return' && $data['reference_id']) {
            $status = $data['result'] === 'passed' ? 'restocked' : 'disposed';
            $this->db->query("UPDATE stock_returns SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?",
                [$status, $_SESSION['user_id'], $data['reference_id']], 'sii');
            if ($status === 'restocked' && $data['approved_qty'] > 0 && $data['warehouse_id']) {
                $this->inventoryModel->addMovement([
                    'product_id'     => $data['product_id'],
                    'warehouse_id'   => $data['warehouse_id'],
                    'type'           => 'return',
                    'quantity'       => $data['approved_qty'],
                    'reference_type' => 'qa_inspection',
                    'reference_id'   => $id,
                    'notes'          => 'QA approved return restock',
                ]);
            }
        }

        $this->json(['success' => true, 'message' => 'Inspection recorded.']);
    }

    public function detail(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $inspection = $this->db->fetchOne(
            "SELECT qi.*, p.name AS product_name, p.unit, u.full_name AS inspector_name, w.name AS warehouse_name
             FROM qa_inspections qi JOIN products p ON qi.product_id=p.id
             JOIN users u ON qi.inspected_by=u.id LEFT JOIN warehouses w ON qi.warehouse_id=w.id
             WHERE qi.id=?", [$id], 'i'
        );
        if (!$inspection) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Not found.']; $this->redirect('/qa'); }
        $this->view('qa/detail', compact('inspection'));
    }
}

