<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ApprovalModel.php';
require_once __DIR__ . '/../models/InventoryModel.php';

class ProcessingController extends Controller {
    private ApprovalModel  $approvalModel;
    private InventoryModel $inventoryModel;

    private const STAGES = ['drying', 'sorting', 'bagging', 'milling', 'shelling'];
    private const STATUS_FLOW = ['pending' => 'in_progress', 'in_progress' => 'completed'];

    public function __construct() {
        parent::__construct();
        $this->approvalModel  = new ApprovalModel();
        $this->inventoryModel = new InventoryModel();
    }

    // &#8369;8369;�� Index &#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��

    public function index(): void {
        $this->requirePermission('processing', 'view');
        $status = $_GET['status'] ?? '';
        $type   = $_GET['type']   ?? '';

        $where  = '1=1';
        $params = []; $types = '';
        if ($status) { $where .= ' AND pb.status=?';       $params[] = $status; $types .= 's'; }
        if ($type)   { $where .= ' AND pb.process_type=?'; $params[] = $type;   $types .= 's'; }

        $batches = $this->db->fetchAll(
            "SELECT pb.*, p.name AS product_name, p.unit,
                    e.full_name AS assigned_name,
                    ar.id AS approval_request_id,
                    ar.status AS approval_status,
                    acs.label AS approval_step_label,
                    (SELECT COUNT(*) FROM processing_stage_logs sl WHERE sl.batch_id=pb.id) AS stage_count,
                    (SELECT COUNT(*) FROM processing_stage_logs sl WHERE sl.batch_id=pb.id AND sl.status='completed') AS stages_done
             FROM processing_batches pb
             JOIN products p ON pb.product_id = p.id
             LEFT JOIN employees e ON pb.assigned_to = e.id
             LEFT JOIN approval_requests ar ON ar.reference_type='processing_batch' AND ar.reference_id=pb.id
             LEFT JOIN approval_steps acs ON acs.request_id=ar.id AND acs.step_order=ar.current_step
             WHERE $where ORDER BY pb.created_at DESC",
            $params, $types
        );

        $summary = $this->db->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(status='pending')     AS pending,
                SUM(status='in_progress') AS in_progress,
                SUM(status='completed')   AS completed,
                SUM(status='cancelled')   AS cancelled,
                COALESCE(SUM(input_quantity),0)  AS total_input,
                COALESCE(SUM(output_quantity),0) AS total_output
             FROM processing_batches"
        );

        $products   = $this->inventoryModel->getProducts();
        $warehouses = $this->inventoryModel->getWarehouses();
        $employees  = $this->db->fetchAll("SELECT id, full_name FROM employees WHERE status='active' ORDER BY full_name");

        $this->view('processing/index', compact(
            'batches', 'summary', 'products', 'warehouses', 'employees', 'status', 'type'
        ));
    }

    // &#8369;8369;�� Create batch &#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;

    public function create(): void {
        $this->requireAuth();

        // Resolve product
        $productId   = (int)($_POST['product_id'] ?? 0);
        $productName = trim($_POST['product_name'] ?? '');
        if (!$productId && $productName) {
            $ex = $this->db->fetchOne("SELECT id FROM products WHERE name=?", [$productName], 's');
            $productId = $ex ? (int)$ex['id']
                : (int)$this->db->insert("INSERT INTO products (name, unit) VALUES (?, 'kg')", [$productName], 's');
        }

        // Resolve warehouses
        $inputWhId  = $this->resolveWarehouse((int)($_POST['input_warehouse_id']  ?? 0), trim($_POST['input_warehouse_name']  ?? ''));
        $outputWhId = $this->resolveWarehouse((int)($_POST['output_warehouse_id'] ?? 0), trim($_POST['output_warehouse_name'] ?? ''));

        // Resolve employee
        $assignedTo  = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $assignedName = trim($_POST['assigned_name'] ?? '');
        if (!$assignedTo && $assignedName) {
            $ex = $this->db->fetchOne("SELECT id FROM employees WHERE full_name=?", [$assignedName], 's');
            if ($ex) $assignedTo = (int)$ex['id'];
        }

        $stages    = $_POST['stages']             ?? [];
        $inputQty  = (float)($_POST['input_quantity'] ?? 0);
        $startDate = $_POST['start_date']         ?? null;
        $notes     = trim($_POST['notes']         ?? '');

        if (!$productId || empty($stages) || $inputQty <= 0) {
            $this->json(['success' => false, 'message' => 'Product, stages and input quantity are required.']);
        }

        $validStages = array_values(array_filter($stages, fn($s) => in_array($s, self::STAGES)));
        if (empty($validStages)) $this->json(['success' => false, 'message' => 'Select at least one valid stage.']);

        if ($inputWhId) {
            $stock = $this->db->fetchOne(
                "SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=?",
                [$productId, $inputWhId], 'ii'
            );
            if (!$stock || $stock['quantity'] < $inputQty) {
                $this->json(['success' => false, 'message' => 'Insufficient stock in selected warehouse.']);
            }
        }

        $batchNumber = 'BATCH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $firstStage  = $validStages[0];

        $batchId = $this->db->insert(
            "INSERT INTO processing_batches
                (batch_number, product_id, process_type, input_quantity, input_warehouse_id,
                 output_warehouse_id, assigned_to, start_date, notes, status, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,'pending',?)",
            [$batchNumber, $productId, $firstStage, $inputQty,
             $inputWhId ?: null, $outputWhId ?: null, $assignedTo, $startDate ?: null,
             $notes, $_SESSION['user_id']],
            'sisdiiissi'
        );

        foreach ($validStages as $order => $stage) {
            $this->db->insert(
                "INSERT INTO processing_stage_logs (batch_id, stage, stage_order, input_qty, status) VALUES (?,?,?,?,?)",
                [$batchId, $stage, $order + 1, $order === 0 ? $inputQty : 0, 'pending'],
                'isids'
            );
        }

        if ($inputWhId) {
            $this->inventoryModel->addMovement([
                'product_id'     => $productId,
                'warehouse_id'   => $inputWhId,
                'type'           => 'out',
                'quantity'       => $inputQty,
                'reference_type' => 'processing_batch',
                'reference_id'   => $batchId,
                'notes'          => "Input for batch $batchNumber",
            ]);
        }

        $product = $this->db->fetchOne("SELECT name FROM products WHERE id=?", [$productId], 'i');
        $this->approvalModel->createRequest([
            'module'         => 'processing',
            'reference_type' => 'processing_batch',
            'reference_id'   => $batchId,
            'title'          => "Processing Batch $batchNumber",
            'description'    => ($product['name'] ?? $productName) . " | Stages: " . implode(' > ', $validStages) . " | Input: " . number_format($inputQty, 2),
        ], $_SESSION['user_id']);

        $this->json(['success' => true, 'message' => "Batch $batchNumber created.", 'id' => $batchId]);
    }

    private function resolveWarehouse(int $id, string $name): int {
        if ($id) return $id;
        if (!$name) return 0;
        $ex = $this->db->fetchOne("SELECT id FROM warehouses WHERE name=?", [$name], 's');
        if ($ex) return (int)$ex['id'];
        return (int)$this->db->insert("INSERT INTO warehouses (name) VALUES (?)", [$name], 's');
    }

    // &#8369;8369;�� Detail &#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;

    public function detail(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);

        $batch = $this->db->fetchOne(
            "SELECT pb.*, p.name AS product_name, p.unit,
                    e.full_name AS assigned_name,
                    wi.name AS input_warehouse_name,
                    wo.name AS output_warehouse_name
             FROM processing_batches pb
             JOIN products p ON pb.product_id = p.id
             LEFT JOIN employees e ON pb.assigned_to = e.id
             LEFT JOIN warehouses wi ON pb.input_warehouse_id  = wi.id
             LEFT JOIN warehouses wo ON pb.output_warehouse_id = wo.id
             WHERE pb.id=?", [$id], 'i'
        );
        if (!$batch) { $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Batch not found.']; $this->redirect('/processing'); }

        $stages   = $this->db->fetchAll(
            "SELECT sl.*, u.full_name AS recorded_by_name
             FROM processing_stage_logs sl
             LEFT JOIN users u ON sl.recorded_by = u.id
             WHERE sl.batch_id=? ORDER BY sl.stage_order", [$id], 'i'
        );
        $approval = $this->db->fetchOne(
            "SELECT ar.* FROM approval_requests ar
             WHERE ar.reference_type='processing_batch' AND ar.reference_id=? ORDER BY ar.id DESC LIMIT 1",
            [$id], 'i'
        );
        $approvalSteps = $approval ? $this->approvalModel->getSteps($approval['id']) : [];
        $audit         = $approval ? $this->approvalModel->getAuditLog($approval['id']) : [];
        $warehouses    = $this->inventoryModel->getWarehouses();

        $this->view('processing/detail', compact('batch', 'stages', 'approval', 'approvalSteps', 'audit', 'warehouses'));
    }

    // &#8369;8369;�� Update stage &#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;

    public function updateStage(): void {
        $this->requireAuth();
        
        // Only processing dept (manager) and admin can update stages, NOT GM
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, ['admin', 'manager'])) {
            $this->json(['success' => false, 'message' => 'Only processing department can manage stages.']);
        }
        
        $stageId   = (int)($_POST['stage_id']    ?? 0);
        $action    = $_POST['action']             ?? '';   // start | complete
        $outputQty = (float)($_POST['output_qty'] ?? 0);
        $wasteQty  = (float)($_POST['waste_qty']  ?? 0);
        $notes     = trim($_POST['notes']         ?? '');

        $stage = $this->db->fetchOne("SELECT * FROM processing_stage_logs WHERE id=?", [$stageId], 'i');
        if (!$stage) $this->json(['success' => false, 'message' => 'Stage not found.']);

        $batch = $this->db->fetchOne("SELECT * FROM processing_batches WHERE id=?", [$stage['batch_id']], 'i');
        
        // Check if batch is approved before allowing stage actions
        $approval = $this->db->fetchOne(
            "SELECT status FROM approval_requests 
             WHERE reference_type='processing_batch' AND reference_id=? 
             ORDER BY id DESC LIMIT 1",
            [$batch['id']], 'i'
        );
        if ($approval && $approval['status'] !== 'approved') {
            $this->json(['success' => false, 'message' => 'Batch must be approved before processing can begin.']);
        }

        if ($action === 'start') {
            if ($stage['status'] !== 'pending') $this->json(['success' => false, 'message' => 'Stage already started.']);
            $this->db->query(
                "UPDATE processing_stage_logs SET status='in_progress', started_at=NOW(), recorded_by=? WHERE id=?",
                [$_SESSION['user_id'], $stageId], 'ii'
            );
            // Update batch status
            $this->db->query("UPDATE processing_batches SET status='in_progress' WHERE id=?", [$stage['batch_id']], 'i');
            $this->json(['success' => true, 'message' => 'Stage started.']);
        }

        if ($action === 'complete') {
            if ($stage['status'] !== 'in_progress') $this->json(['success' => false, 'message' => 'Stage not in progress.']);
            if ($outputQty <= 0) $this->json(['success' => false, 'message' => 'Output quantity is required.']);

            $this->db->query(
                "UPDATE processing_stage_logs
                 SET status='completed', output_qty=?, waste_qty=?, notes=?, completed_at=NOW(), recorded_by=?
                 WHERE id=?",
                [$outputQty, $wasteQty, $notes, $_SESSION['user_id'], $stageId],
                'ddsii'
            );

            // Feed output as input to next stage
            $nextStage = $this->db->fetchOne(
                "SELECT * FROM processing_stage_logs WHERE batch_id=? AND stage_order=? AND status='pending'",
                [$stage['batch_id'], $stage['stage_order'] + 1], 'ii'
            );
            if ($nextStage) {
                $this->db->query(
                    "UPDATE processing_stage_logs SET input_qty=? WHERE id=?",
                    [$outputQty, $nextStage['id']], 'di'
                );
            }

            // Check if all stages done
            $remaining = $this->db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM processing_stage_logs WHERE batch_id=? AND status NOT IN ('completed','skipped')",
                [$stage['batch_id']], 'i'
            );
            if (($remaining['cnt'] ?? 1) == 0) {
                $this->db->query(
                    "UPDATE processing_batches SET status='completed', output_quantity=?, waste_quantity=?, end_date=NOW() WHERE id=?",
                    [$outputQty, $wasteQty, $stage['batch_id']], 'ddi'
                );
                // Stock output into output warehouse
                if ($batch['output_warehouse_id']) {
                    $this->inventoryModel->addMovement([
                        'product_id'     => $batch['product_id'],
                        'warehouse_id'   => $batch['output_warehouse_id'],
                        'type'           => 'in',
                        'quantity'       => $outputQty,
                        'reference_type' => 'processing_batch',
                        'reference_id'   => $batch['id'],
                        'notes'          => "Output from batch " . $batch['batch_number'],
                    ]);
                }
            }

            $this->json(['success' => true, 'message' => 'Stage completed.']);
        }

        $this->json(['success' => false, 'message' => 'Invalid action.']);
    }

    // &#8369;8369;�� Cancel batch &#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;

    public function cancel(): void {
        $this->requirePermission('processing', 'approve');
        $id = (int)($_POST['id'] ?? 0);
        $b  = $this->db->fetchOne("SELECT * FROM processing_batches WHERE id=?", [$id], 'i');
        if (!$b || $b['status'] === 'completed') $this->json(['success' => false, 'message' => 'Cannot cancel this batch.']);

        $this->db->query("UPDATE processing_batches SET status='cancelled' WHERE id=?", [$id], 'i');
        $this->db->query("UPDATE processing_stage_logs SET status='skipped' WHERE batch_id=? AND status='pending'", [$id], 'i');

        // Reverse input stock deduction
        if ($b['input_warehouse_id']) {
            $this->inventoryModel->addMovement([
                'product_id'     => $b['product_id'],
                'warehouse_id'   => $b['input_warehouse_id'],
                'type'           => 'in',
                'quantity'       => $b['input_quantity'],
                'reference_type' => 'processing_batch',
                'reference_id'   => $id,
                'notes'          => "Cancelled batch " . $b['batch_number'] . " &#8369; stock reversed",
            ]);
        }
        $this->json(['success' => true, 'message' => 'Batch cancelled and stock reversed.']);
    }

    // &#8369;8369;�� Delete &#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;8369;��&#8369;

    public function delete(): void {
        $this->requirePermission('processing', 'delete');
        $id = (int)($_POST['id'] ?? 0);
        $b  = $this->db->fetchOne("SELECT status FROM processing_batches WHERE id=?", [$id], 'i');
        if (!$b || $b['status'] !== 'pending') $this->json(['success' => false, 'message' => 'Only pending batches can be deleted.']);
        $this->db->query("DELETE FROM processing_batches WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Batch deleted.']);
    }
}


