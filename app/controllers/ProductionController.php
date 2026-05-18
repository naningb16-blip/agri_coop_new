<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/InventoryModel.php';

class ProductionController extends Controller {
    private InventoryModel $inventoryModel;

    public function __construct() {
        parent::__construct();
        $this->inventoryModel = new InventoryModel();
    }

    public function index(): void {
        $this->requirePermission('production', 'view');
        $status = $_GET['status'] ?? '';
        $where  = $status ? "WHERE pr.status='$status'" : '';

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
             $where ORDER BY pr.created_at DESC"
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

        $this->view('production/index', compact('records', 'summary', 'farmers', 'products', 'warehouses', 'employees', 'status'));
    }

    public function create(): void {
        $this->requireAuth();

        // Resolve farmer — auto-create if new name typed
        $farmerId   = (int)($_POST['farmer_id'] ?? 0);
        $farmerName = trim($_POST['farmer_name'] ?? '');
        if (!$farmerId && $farmerName) {
            $existing = $this->db->fetchOne("SELECT id FROM farmers WHERE full_name=?", [$farmerName], 's');
            $farmerId = $existing
                ? (int)$existing['id']
                : (int)$this->db->insert("INSERT INTO farmers (full_name, status) VALUES (?, 'active')", [$farmerName], 's');
        }

        // Resolve product — auto-create if new name typed
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

        $id = $this->db->insert(
            "INSERT INTO production_records
                (farmer_id, product_id, farm_location, season, planting_date, expected_harvest,
                 planted_area_ha, expected_yield, status, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,'planned',?,?)",
            [
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
            'iissssddsi'
        );
        $this->json(['success' => true, 'message' => 'Production record created.', 'id' => $id]);
    }

    public function detail(): void {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->db->fetchOne(
            "SELECT pr.*, p.name AS product_name, p.unit, f.full_name AS farmer_name,
                    f.phone AS farmer_phone, f.address AS farmer_address, f.farm_area_ha
             FROM production_records pr
             JOIN farmers f ON pr.farmer_id=f.id JOIN products p ON pr.product_id=p.id
             WHERE pr.id=?", [$id], 'i'
        );
        if (!$record) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Not found.']; $this->redirect('/production'); }

        $inputs    = $this->db->fetchAll("SELECT * FROM production_inputs WHERE production_record_id=? ORDER BY applied_date", [$id], 'i');
        $schedules = $this->db->fetchAll(
            "SELECT ps.*, e.full_name AS assigned_name FROM production_schedules ps
             LEFT JOIN employees e ON ps.assigned_to=e.id WHERE ps.production_record_id=? ORDER BY ps.scheduled_date", [$id], 'i'
        );
        $employees = $this->db->fetchAll("SELECT id, full_name FROM employees WHERE status='active' ORDER BY full_name");
        $warehouses = $this->inventoryModel->getWarehouses();

        $this->view('production/detail', compact('record', 'inputs', 'schedules', 'employees', 'warehouses'));
    }

    public function updateStatus(): void {
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
            $expectedYield = (float)($_POST['expected_yield'] ?? 0);
            
            // Update both expected and actual yield
            if ($expectedYield > 0) {
                $extra = ', expected_yield=?, actual_yield=?';
                $params = array_merge($params, [$expectedYield, $actualYield]);
                $types .= 'dd';
            } else {
                $extra = ', actual_yield=?';
                $params = array_merge($params, [$actualYield]);
                $types .= 'd';
            }

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

    public function updateExpectedYield(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $expectedYield = (float)($_POST['expected_yield'] ?? 0);
        
        if ($expectedYield <= 0) {
            $this->json(['success' => false, 'message' => 'Expected yield must be greater than zero.']);
        }
        
        // Check if record is in harvested stage
        $record = $this->db->fetchOne("SELECT status FROM production_records WHERE id=?", [$id], 'i');
        if (!$record || $record['status'] !== 'harvested') {
            $this->json(['success' => false, 'message' => 'Can only update expected yield for harvested records.']);
        }
        
        $this->db->query(
            "UPDATE production_records SET expected_yield=? WHERE id=?",
            [$expectedYield, $id], 'di'
        );
        
        $this->json(['success' => true, 'message' => 'Expected yield updated successfully.']);
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

    // Farmers CRUD
    public function farmers(): void {
        $this->requireAuth();
        $farmers = $this->db->fetchAll(
            "SELECT f.*, COUNT(pr.id) AS record_count,
                    COALESCE(SUM(pr.actual_yield),0) AS total_yield
             FROM farmers f LEFT JOIN production_records pr ON pr.farmer_id=f.id
             GROUP BY f.id ORDER BY f.full_name"
        );
        $this->view('production/farmers', compact('farmers'));
    }

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
}

