<?php
require_once __DIR__ . '/../../core/Controller.php';

class MonitoringController extends Controller {

    public function index(): void {
        $this->requirePermission('monitoring', 'view');
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        // Batch cost summary
        $batchCosts = $this->db->fetchAll(
            "SELECT pb.id AS batch_id, pb.batch_number, pb.status, p.name AS product_name,
                    pb.input_quantity, pb.output_quantity,
                    COALESCE(SUM(bc.amount),0) AS total_cost,
                    CASE WHEN pb.output_quantity > 0
                         THEN COALESCE(SUM(bc.amount),0) / pb.output_quantity
                         ELSE 0 END AS cost_per_unit
             FROM processing_batches pb
             JOIN products p ON pb.product_id=p.id
             LEFT JOIN batch_costs bc ON bc.batch_id=pb.id
             WHERE pb.created_at BETWEEN ? AND ?
             GROUP BY pb.id ORDER BY pb.created_at DESC",
            [$from, $to], 'ss'
        );

        // Cost breakdown by type
        $costByType = $this->db->fetchAll(
            "SELECT bc.cost_type, COALESCE(SUM(bc.amount),0) AS total
             FROM batch_costs bc
             JOIN processing_batches pb ON bc.batch_id=pb.id
             WHERE pb.created_at BETWEEN ? AND ?
             GROUP BY bc.cost_type ORDER BY total DESC",
            [$from, $to], 'ss'
        );

        // Production input costs
        $inputCosts = $this->db->fetchAll(
            "SELECT pi.input_type, COALESCE(SUM(pi.total_cost),0) AS total
             FROM production_inputs pi
             JOIN production_records pr ON pi.production_record_id=pr.id
             WHERE pr.planting_date BETWEEN ? AND ?
             GROUP BY pi.input_type ORDER BY total DESC",
            [$from, $to], 'ss'
        );

        // Schedules
        $schedules = $this->db->fetchAll(
            "SELECT ps.*, pr.farm_location, p.name AS product_name,
                    f.full_name AS farmer_name, e.full_name AS assigned_name
             FROM production_schedules ps
             JOIN production_records pr ON ps.production_record_id=pr.id
             JOIN products p ON pr.product_id=p.id
             JOIN farmers f ON pr.farmer_id=f.id
             LEFT JOIN employees e ON ps.assigned_to=e.id
             WHERE ps.scheduled_date BETWEEN ? AND ?
             ORDER BY ps.scheduled_date ASC",
            [$from, $to], 'ss'
        );

        // Summary totals
        $totals = $this->db->fetchOne(
            "SELECT COALESCE(SUM(bc.amount),0) AS total_batch_cost,
                    (SELECT COALESCE(SUM(total_cost),0) FROM production_inputs pi
                     JOIN production_records pr ON pi.production_record_id=pr.id
                     WHERE pr.planting_date BETWEEN ? AND ?) AS total_input_cost
             FROM batch_costs bc
             JOIN processing_batches pb ON bc.batch_id=pb.id
             WHERE pb.created_at BETWEEN ? AND ?",
            [$from, $to, $from, $to], 'ssss'
        );

        // Get available batches for the cost modal
        $availableBatches = $this->db->fetchAll(
            "SELECT pb.id, pb.batch_number, pb.status, p.name AS product_name, pb.created_at
             FROM processing_batches pb
             JOIN products p ON pb.product_id = p.id
             WHERE pb.status IN ('in_progress', 'completed')
             ORDER BY pb.created_at DESC
             LIMIT 100"
        );

        $this->view('monitoring/index', compact('batchCosts', 'costByType', 'inputCosts', 'schedules', 'totals', 'from', 'to', 'availableBatches'));
    }

    public function addBatchCost(): void {
        $this->requireAuth();
        $batchId = (int)($_POST['batch_id'] ?? 0);
        $amount  = (float)($_POST['amount'] ?? 0);
        
        if (!$batchId || $amount <= 0) {
            $this->json(['success' => false, 'message' => 'Batch and amount required.']);
            return;
        }

        // Validate that the batch exists
        $batch = $this->db->fetchOne(
            "SELECT id, batch_number FROM processing_batches WHERE id = ?",
            [$batchId], 'i'
        );
        
        if (!$batch) {
            $this->json(['success' => false, 'message' => 'Invalid batch ID. Batch does not exist.']);
            return;
        }

        $this->db->insert(
            "INSERT INTO batch_costs (batch_id, cost_type, description, amount, recorded_by) VALUES (?,?,?,?,?)",
            [$batchId, $_POST['cost_type'] ?? 'other', trim($_POST['description'] ?? ''), $amount, $_SESSION['user_id']],
            'issdi'
        );
        $this->json(['success' => true, 'message' => 'Cost recorded for batch #' . $batch['batch_number'] . '.']);
    }

    public function batchCostDetails(): void {
        $this->requireAuth();
        $batchId = (int)($_GET['batch_id'] ?? 0);
        
        if (!$batchId) {
            $this->json(['success' => false, 'message' => 'Batch ID required.']);
            return;
        }
        
        // Get batch info
        $batch = $this->db->fetchOne(
            "SELECT pb.id, pb.batch_number, p.name AS product_name
             FROM processing_batches pb
             JOIN products p ON pb.product_id = p.id
             WHERE pb.id = ?",
            [$batchId], 'i'
        );
        
        if (!$batch) {
            $this->json(['success' => false, 'message' => 'Batch not found.']);
            return;
        }
        
        // Get all cost entries for this batch
        $costs = $this->db->fetchAll(
            "SELECT bc.*, u.full_name AS recorded_by_name
             FROM batch_costs bc
             LEFT JOIN users u ON bc.recorded_by = u.id
             WHERE bc.batch_id = ?
             ORDER BY bc.recorded_at DESC",
            [$batchId], 'i'
        );
        
        $this->json([
            'success' => true,
            'batch' => $batch,
            'costs' => $costs
        ]);
    }

    public function costReport(): void {
        $this->requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $data = [
            'batch_costs'    => $this->db->fetchAll(
                "SELECT pb.batch_number, p.name AS product, SUM(bc.amount) AS total,
                        pb.input_quantity, pb.output_quantity,
                        CASE WHEN pb.output_quantity>0 THEN SUM(bc.amount)/pb.output_quantity ELSE 0 END AS cost_per_unit
                 FROM batch_costs bc JOIN processing_batches pb ON bc.batch_id=pb.id
                 JOIN products p ON pb.product_id=p.id
                 WHERE bc.recorded_at BETWEEN ? AND ? GROUP BY pb.id ORDER BY total DESC",
                [$from, $to], 'ss'
            ),
            'production_costs' => $this->db->fetchAll(
                "SELECT pr.farm_location, p.name AS product, f.full_name AS farmer,
                        SUM(pi.total_cost) AS total_input_cost, pr.actual_yield,
                        CASE WHEN pr.actual_yield>0 THEN SUM(pi.total_cost)/pr.actual_yield ELSE 0 END AS cost_per_unit
                 FROM production_inputs pi JOIN production_records pr ON pi.production_record_id=pr.id
                 JOIN products p ON pr.product_id=p.id JOIN farmers f ON pr.farmer_id=f.id
                 WHERE pr.planting_date BETWEEN ? AND ? GROUP BY pr.id ORDER BY total_input_cost DESC",
                [$from, $to], 'ss'
            ),
            'from' => $from, 'to' => $to,
        ];
        $this->json($data);
    }
}

