<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ApprovalModel.php';
require_once __DIR__ . '/../models/InventoryModel.php';

class OperationalController extends Controller {
    private ApprovalModel  $approvalModel;
    private InventoryModel $inventoryModel;

    private const PROCESSING_STAGES = ['drying', 'sorting', 'bagging', 'milling', 'shelling'];

    public function __construct() {
        parent::__construct();
        $this->approvalModel  = new ApprovalModel();
        $this->inventoryModel = new InventoryModel();
    }

    // ═══ Main Index with Tabs ═══════════════════════════════════════════════

    public function index(): void {
        $this->requirePermission('operational', 'view');
        $tab = $_GET['tab'] ?? 'production';

        if ($tab === 'production') {
            $this->productionTab();
        } elseif ($tab === 'processing') {
            $this->processingTab();
        } elseif ($tab === 'farmers') {
            $this->farmersTab();
        } else {
            $this->productionTab();
        }
    }

    private function renderTabLayout(string $tabView, array $data): void {
        // Start output buffering for tab content
        ob_start();
        extract($data);
        require __DIR__ . '/../views/operational/tabs/' . $tabView . '.php';
        $tabContent = ob_get_clean();
        
        // Pass to main index with tab content
        $this->view('operational/index', array_merge($data, ['tabContent' => $tabContent]));
    }

    // ═══ PRODUCTION TAB ═════════════════════════════════════════════════════

    private function productionTab(): void {
        $status = $_GET['status'] ?? '';
        $mine   = isset($_GET['mine']) && $_GET['mine'] === '1';
        
        $where  = [];
        if ($status) $where[] = "pr.status='$status'";
        if ($mine) $where[] = "pr.created_by=" . (int)$_SESSION['user_id'];
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $records = $this->db->fetchAll(
            "SELECT pr.*, p.name AS product_name, p.unit,
                    f.full_name AS farmer_name, f.farm_area_ha,
                    u.full_name AS created_by_name,
                    (SELECT COUNT(*) FROM production_inputs pi WHERE pi.production_record_id=pr.id) AS input_count,
                    (SELECT COALESCE(SUM(total_cost),0) FROM production_inputs pi WHERE pi.production_record_id=pr.id) AS total_cost,
                    (SELECT COUNT(*) FROM production_schedules ps WHERE ps.production_record_id=pr.id) AS schedule_count
             FROM production_records pr
             JOIN farmers f ON pr.farmer_id = f.id
             JOIN products p ON pr.product_id = p.id
             LEFT JOIN users u ON pr.created_by = u.id
             $whereClause ORDER BY pr.created_at DESC"
        );

        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(status='planted')   AS planted,
                    SUM(status='growing')   AS growing,
                    SUM(status='harvested') AS harvested,
                    SUM(status='completed') AS completed,
                    COALESCE(SUM(actual_yield),0) AS total_yield,
                    COALESCE(SUM(planted_area_ha),0) AS total_area
             FROM production_records"
        );

        $farmers    = $this->db->fetchAll("SELECT * FROM farmers WHERE status='active' ORDER BY full_name");
        $products   = $this->inventoryModel->getProducts();
        $warehouses = $this->inventoryModel->getWarehouses();
        $employees  = $this->db->fetchAll("SELECT id, full_name FROM employees WHERE status='active' ORDER BY full_name");

        $this->renderTabLayout('production', compact('records', 'summary', 'farmers', 'products', 'warehouses', 'employees', 'status', 'mine'));
    }

    // ═══ PROCESSING TAB ═════════════════════════════════════════════════════

    private function processingTab(): void {
        $status = $_GET['status'] ?? '';
        $type   = $_GET['type']   ?? '';
        $mine   = isset($_GET['mine']) && $_GET['mine'] === '1';

        $where  = '1=1';
        $params = []; $types = '';
        if ($status) { $where .= ' AND pb.status=?';       $params[] = $status; $types .= 's'; }
        if ($type)   { $where .= ' AND pb.process_type=?'; $params[] = $type;   $types .= 's'; }
        if ($mine)   { $where .= ' AND pb.created_by=?';   $params[] = (int)$_SESSION['user_id']; $types .= 'i'; }

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

        $this->renderTabLayout('processing', compact(
            'batches', 'summary', 'products', 'warehouses', 'employees', 'status', 'type', 'mine'
        ));
    }

    // ═══ FARMERS TAB ════════════════════════════════════════════════════════

    private function farmersTab(): void {
        $farmers = $this->db->fetchAll(
            "SELECT f.*, COUNT(pr.id) AS record_count,
                    COALESCE(SUM(pr.actual_yield),0) AS total_yield
             FROM farmers f LEFT JOIN production_records pr ON pr.farmer_id=f.id
             GROUP BY f.id ORDER BY f.full_name"
        );
        $this->renderTabLayout('farmers', compact('farmers'));
    }


    // ═══ PRODUCTION METHODS ═════════════════════════════════════════════════

    public function createProduction(): void {
        $this->requireAuth();

        // Resolve farmer
        $farmerId   = (int)($_POST['farmer_id'] ?? 0);
        $farmerName = trim($_POST['farmer_name'] ?? '');
        if (!$farmerId && $farmerName) {
            $existing = $this->db->fetchOne("SELECT id FROM farmers WHERE full_name=?", [$farmerName], 's');
            $farmerId = $existing
                ? (int)$existing['id']
                : (int)$this->db->insert("INSERT INTO farmers (full_name, status) VALUES (?, 'active')", [$farmerName], 's');
        }

        // Resolve product
        $productId   = (int)($_POST['product_id'] ?? 0);
        $productName = trim($_POST['product_name'] ?? '');
        if (!$productId && $productName) {
            $existing  = $this->db->fetchOne("SELECT id FROM products WHERE name=?", [$productName], 's');
            $productId = $existing
                ? (int)$existing['id']
                : (int)$this->db->insert("INSERT INTO products (name, unit) VALUES (?, 'kg')", [$productName], 's');
        }

        if (!$farmerId || !$productId) {
            $this->json(['success' => false, 'message' => 'Farmer and product are required.']);
        }

        // Generate production number
        $productionNumber = 'PROD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $id = $this->db->insert(
            "INSERT INTO production_records
                (production_number, farmer_id, product_id, farm_location, season, planting_date, expected_harvest,
                 planted_area_ha, expected_yield, status, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,'planned',?,?)",
            [
                $productionNumber,
                $farmerId,
                $productId,
                trim($_POST['farm_location']    ?? ''),
                trim($_POST['season']           ?? ''),
                $_POST['planting_date']         ?? null,
                $_POST['expected_harvest']      ?? null,
                (float)($_POST['planted_area_ha'] ?? 0),
                (float)($_POST['expected_yield']  ?? 0),
                trim($_POST['notes']            ?? ''),
                $_SESSION['user_id'],
            ],
            'siissssddsi'
        );

        // Create approval request for GM
        $farmer = $this->db->fetchOne("SELECT full_name FROM farmers WHERE id=?", [$farmerId], 'i');
        $product = $this->db->fetchOne("SELECT name FROM products WHERE id=?", [$productId], 'i');
        
        $this->approvalModel->createRequest([
            'module'         => 'operational',
            'reference_type' => 'production_record',
            'reference_id'   => $id,
            'title'          => "Production: " . ($product['name'] ?? 'Product') . " - " . ($farmer['full_name'] ?? 'Farmer'),
            'description'    => "Area: " . number_format((float)($_POST['planted_area_ha'] ?? 0), 2) . " ha | Expected Yield: " . number_format((float)($_POST['expected_yield'] ?? 0), 2) . " kg",
        ], $_SESSION['user_id']);

        $this->json(['success' => true, 'message' => 'Production record created and submitted for approval.', 'id' => $id]);
    }

    public function productionDetail(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->db->fetchOne(
            "SELECT pr.*, p.name AS product_name, p.unit, f.full_name AS farmer_name,
                    f.phone AS farmer_phone, f.address AS farmer_address, f.farm_area_ha
             FROM production_records pr
             JOIN farmers f ON pr.farmer_id=f.id JOIN products p ON pr.product_id=p.id
             WHERE pr.id=?", [$id], 'i'
        );
        if (!$record) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Not found.']; $this->redirect('/operational'); }

        $inputs    = $this->db->fetchAll("SELECT * FROM production_inputs WHERE production_record_id=? ORDER BY applied_date", [$id], 'i');
        $schedules = $this->db->fetchAll(
            "SELECT ps.*, e.full_name AS assigned_name FROM production_schedules ps
             LEFT JOIN employees e ON ps.assigned_to=e.id WHERE ps.production_record_id=? ORDER BY ps.scheduled_date", [$id], 'i'
        );
        $employees = $this->db->fetchAll("SELECT id, full_name FROM employees WHERE status='active' ORDER BY full_name");
        $warehouses = $this->inventoryModel->getWarehouses();

        $this->view('operational/production_detail', compact('record', 'inputs', 'schedules', 'employees', 'warehouses'));
    }


    public function updateProductionStatus(): void {
        $this->requireAuth();
        $id     = (int)($_POST['id']     ?? 0);
        $status = $_POST['status']       ?? '';
        $allowed = ['planned','planted','growing','harvested','completed'];
        if (!in_array($status, $allowed)) $this->json(['success' => false, 'message' => 'Invalid status.']);

        $extra = '';
        $params = [$status];
        $types  = 's';

        // When planting — deduct seeds from inventory
        if ($status === 'planted') {
            $seedQuantity = (float)($_POST['seed_quantity'] ?? 0);
            $seedProductId = (int)($_POST['seed_product_id'] ?? 0);
            $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
            
            if ($warehouseId && $seedQuantity > 0 && $seedProductId) {
                $this->inventoryModel->addMovement([
                    'product_id'     => $seedProductId,
                    'warehouse_id'   => $warehouseId,
                    'type'           => 'out',
                    'quantity'       => $seedQuantity,
                    'reference_type' => 'production',
                    'reference_id'   => $id,
                    'notes'          => "Seeds used for planting in production record #$id",
                ]);
            }
        }

        if ($status === 'harvested' || $status === 'completed') {
            $actualYield = (float)($_POST['actual_yield'] ?? 0);
            $extra = ', actual_yield=?';
            $params = array_merge($params, [$actualYield]);
            $types .= 'd';

            // Stock in harvested yield
            $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
            if ($warehouseId && $actualYield > 0) {
                $record = $this->db->fetchOne("SELECT product_id FROM production_records WHERE id=?", [$id], 'i');
                $this->inventoryModel->addMovement([
                    'product_id'     => $record['product_id'],
                    'warehouse_id'   => $warehouseId,
                    'type'           => 'in',
                    'quantity'       => $actualYield,
                    'reference_type' => 'production',
                    'reference_id'   => $id,
                    'notes'          => "Harvest yield from production record #$id",
                ]);
            }
        }

        $params[] = $id; $types .= 'i';
        $this->db->query("UPDATE production_records SET status=? $extra WHERE id=?", $params, $types);
        $this->json(['success' => true, 'message' => "Status updated to $status."]);
    }

    public function addInput(): void {
        $this->requireAuth();
        $id = (int)($_POST['production_record_id'] ?? 0);
        $qty  = (float)($_POST['quantity']   ?? 0);
        $cost = (float)($_POST['unit_cost']  ?? 0);
        $this->db->insert(
            "INSERT INTO production_inputs
                (production_record_id, input_type, name, quantity, unit, unit_cost, total_cost, applied_date, notes)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [$id, $_POST['input_type'] ?? 'other', trim($_POST['name'] ?? ''),
             $qty, trim($_POST['unit'] ?? ''), $cost, $qty * $cost,
             $_POST['applied_date'] ?? date('Y-m-d'), trim($_POST['notes'] ?? '')],
            'issdsdds s'
        );
        $this->json(['success' => true, 'message' => 'Input recorded.']);
    }

    public function addSchedule(): void {
        $this->requireAuth();
        $this->db->insert(
            "INSERT INTO production_schedules (production_record_id, activity, scheduled_date, assigned_to, notes)
             VALUES (?,?,?,?,?)",
            [(int)($_POST['production_record_id'] ?? 0), trim($_POST['activity'] ?? ''),
             $_POST['scheduled_date'] ?? date('Y-m-d'),
             (int)($_POST['assigned_to'] ?? 0) ?: null, trim($_POST['notes'] ?? '')],
            'issss'
        );
        $this->json(['success' => true, 'message' => 'Schedule added.']);
    }

    public function updateSchedule(): void {
        $this->requireAuth();
        $id     = (int)($_POST['id']     ?? 0);
        $status = $_POST['status']       ?? '';
        $this->db->query(
            "UPDATE production_schedules SET status=?, completed_date=? WHERE id=?",
            [$status, $status === 'completed' ? date('Y-m-d') : null, $id], 'ssi'
        );
        $this->json(['success' => true, 'message' => 'Schedule updated.']);
    }


    // ═══ PROCESSING METHODS ═════════════════════════════════════════════════

    public function createProcessing(): void {
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

        $validStages = array_values(array_filter($stages, fn($s) => in_array($s, self::PROCESSING_STAGES)));
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
            'module'         => 'operational',
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


    public function processingDetail(): void {
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
        if (!$batch) { $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Batch not found.']; $this->redirect('/operational?tab=processing'); }

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

        $this->view('operational/processing_detail', compact('batch', 'stages', 'approval', 'approvalSteps', 'audit', 'warehouses'));
    }

    public function updateStage(): void {
        $this->requireAuth();
        
        // Only operational dept (manager) and admin can update stages, NOT GM
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, ['admin', 'manager'])) {
            $this->json(['success' => false, 'message' => 'Only operational department can manage stages.']);
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

    public function cancelProcessing(): void {
        $this->requirePermission('operational', 'approve');
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
                'notes'          => "Cancelled batch " . $b['batch_number'] . " - stock reversed",
            ]);
        }
        $this->json(['success' => true, 'message' => 'Batch cancelled and stock reversed.']);
    }

    public function deleteProcessing(): void {
        $this->requirePermission('operational', 'delete');
        $id = (int)($_POST['id'] ?? 0);
        $b  = $this->db->fetchOne("SELECT status FROM processing_batches WHERE id=?", [$id], 'i');
        if (!$b || $b['status'] !== 'pending') $this->json(['success' => false, 'message' => 'Only pending batches can be deleted.']);
        $this->db->query("DELETE FROM processing_batches WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Batch deleted.']);
    }

    // ═══ FARMERS CRUD ═══════════════════════════════════════════════════════

    public function saveFarmer(): void {
        $this->requireAuth();
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'full_name'    => trim($_POST['full_name']    ?? ''),
            'phone'        => trim($_POST['phone']        ?? ''),
            'address'      => trim($_POST['address']      ?? ''),
            'farm_area_ha' => (float)($_POST['farm_area_ha'] ?? 0),
            'status'       => $_POST['status'] ?? 'active',
        ];
        if (empty($data['full_name'])) $this->json(['success' => false, 'message' => 'Name is required.']);
        if ($id) {
            $this->db->query("UPDATE farmers SET full_name=?,phone=?,address=?,farm_area_ha=?,status=? WHERE id=?",
                [...array_values($data), $id], 'sssdsi');
        } else {
            $this->db->insert("INSERT INTO farmers (full_name,phone,address,farm_area_ha,status) VALUES (?,?,?,?,?)",
                array_values($data), 'sssds');
        }
        $this->json(['success' => true, 'message' => 'Farmer saved.']);
    }

    // ═══ GM APPROVAL METHODS ════════════════════════════════════════════════

    public function approveProduction(): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'gm') {
            $this->json(['success' => false, 'message' => 'Only GM can approve production records.']);
        }

        $id = (int)($_POST['id'] ?? 0);
        $record = $this->db->fetchOne("SELECT * FROM production_records WHERE id=?", [$id], 'i');
        if (!$record) $this->json(['success' => false, 'message' => 'Production record not found.']);

        // Update status to 'planned' (approved and ready for planting)
        $this->db->query("UPDATE production_records SET status='planned' WHERE id=?", [$id], 'i');

        $this->json(['success' => true, 'message' => 'Production record approved.']);
    }

    public function approveProcessing(): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'gm') {
            $this->json(['success' => false, 'message' => 'Only GM can approve processing batches.']);
        }

        $id = (int)($_POST['id'] ?? 0);
        $batch = $this->db->fetchOne("SELECT * FROM processing_batches WHERE id=?", [$id], 'i');
        if (!$batch) $this->json(['success' => false, 'message' => 'Processing batch not found.']);

        // Update status to 'in_progress' (approved and ready for processing)
        $this->db->query("UPDATE processing_batches SET status='in_progress' WHERE id=?", [$id], 'i');

        $this->json(['success' => true, 'message' => 'Processing batch approved and ready for processing.']);
    }

    // ═══ PRINT METHODS ══════════════════════════════════════════════════════

    public function productionPrint(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        
        $record = $this->db->fetchOne(
            "SELECT pr.*, p.name AS product_name, p.unit, f.full_name AS farmer_name,
                    f.phone AS farmer_phone, f.address AS farmer_address, f.farm_area_ha,
                    u.full_name AS created_by_name
             FROM production_records pr
             JOIN farmers f ON pr.farmer_id=f.id
             JOIN products p ON pr.product_id=p.id
             LEFT JOIN users u ON pr.created_by=u.id
             WHERE pr.id=?",
            [$id], 'i'
        );
        
        if (!$record) {
            http_response_code(404);
            echo 'Production record not found';
            exit;
        }
        
        $inputs = $this->db->fetchAll(
            "SELECT * FROM production_inputs WHERE production_record_id=? ORDER BY applied_date",
            [$id], 'i'
        );
        
        $schedules = $this->db->fetchAll(
            "SELECT ps.*, e.full_name AS assigned_name
             FROM production_schedules ps
             LEFT JOIN employees e ON ps.assigned_to=e.id
             WHERE ps.production_record_id=? ORDER BY ps.scheduled_date",
            [$id], 'i'
        );
        
        $this->view('operational/production_print', compact('record', 'inputs', 'schedules'));
    }

    public function processingPrint(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        
        $batch = $this->db->fetchOne(
            "SELECT pb.*, p.name AS product_name, p.unit,
                    e.full_name AS assigned_name,
                    wi.name AS input_warehouse_name,
                    wo.name AS output_warehouse_name,
                    u.full_name AS created_by_name
             FROM processing_batches pb
             JOIN products p ON pb.product_id=p.id
             LEFT JOIN employees e ON pb.assigned_to=e.id
             LEFT JOIN warehouses wi ON pb.input_warehouse_id=wi.id
             LEFT JOIN warehouses wo ON pb.output_warehouse_id=wo.id
             LEFT JOIN users u ON pb.created_by=u.id
             WHERE pb.id=?",
            [$id], 'i'
        );
        
        if (!$batch) {
            http_response_code(404);
            echo 'Processing batch not found';
            exit;
        }
        
        $stages = $this->db->fetchAll(
            "SELECT sl.*, u.full_name AS recorded_by_name
             FROM processing_stage_logs sl
             LEFT JOIN users u ON sl.recorded_by=u.id
             WHERE sl.batch_id=? ORDER BY sl.stage_order",
            [$id], 'i'
        );
        
        $this->view('operational/processing_print', compact('batch', 'stages'));
    }
}
