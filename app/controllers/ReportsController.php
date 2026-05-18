<?php
require_once __DIR__ . '/../../core/Controller.php';

class ReportsController extends Controller {

    public function index(): void {
        $this->requirePermission('reports', 'view');
        $this->view('reports/index');
    }

    public function financial(): void {
        $this->requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $revenue  = $this->db->fetchOne("SELECT COALESCE(SUM(total_amount),0) AS t FROM sales_orders WHERE order_date BETWEEN ? AND ? AND status!='cancelled'", [$from,$to],'ss')['t'];
        $expenses = $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) AS t FROM expenses WHERE expense_date BETWEEN ? AND ? AND status='approved'", [$from,$to],'ss')['t'];
        $payroll  = $this->db->fetchOne("SELECT COALESCE(SUM(net_pay),0) AS t FROM payroll WHERE period_start>=? AND period_end<=? AND status='paid'", [$from,$to],'ss')['t'];
        $batchCosts = 0;
        try {
            $batchCosts = $this->db->fetchOne("SELECT COALESCE(SUM(bc.amount),0) AS t FROM batch_costs bc JOIN processing_batches pb ON bc.batch_id=pb.id WHERE pb.created_at BETWEEN ? AND ?", [$from,$to],'ss')['t'] ?? 0;
        } catch (\Exception $e) {}
        $inputCosts = 0;
        try {
            $inputCosts = $this->db->fetchOne("SELECT COALESCE(SUM(total_cost),0) AS t FROM production_inputs pi JOIN production_records pr ON pi.production_record_id=pr.id WHERE pr.planting_date BETWEEN ? AND ?", [$from,$to],'ss')['t'] ?? 0;
        } catch (\Exception $e) {}

        $salesByProduct = $this->db->fetchAll(
            "SELECT p.id, p.name, SUM(soi.quantity) AS qty_sold, SUM(soi.total_price) AS revenue
             FROM sales_order_items soi JOIN products p ON soi.product_id=p.id
             JOIN sales_orders so ON soi.so_id=so.id
             WHERE so.order_date BETWEEN ? AND ? AND so.status!='cancelled'
             GROUP BY p.id, p.name ORDER BY revenue DESC LIMIT 10", [$from,$to],'ss'
        );

        $monthlySales = $this->db->fetchAll(
            "SELECT YEAR(order_date) AS yr, MONTH(order_date) AS mo,
                    DATE_FORMAT(MIN(order_date),'%b %Y') AS month,
                    SUM(total_amount) AS total, COUNT(*) AS orders
             FROM sales_orders WHERE order_date BETWEEN ? AND ? AND status!='cancelled'
             GROUP BY YEAR(order_date), MONTH(order_date)
             ORDER BY yr ASC, mo ASC", [$from,$to],'ss'
        );

        $this->json(compact('revenue','expenses','payroll','batchCosts','inputCosts','salesByProduct','monthlySales','from','to'));
    }

    public function inventory(): void {
        $this->requireAuth();
        $stock = $this->db->fetchAll(
            "SELECT p.name, p.unit, p.reorder_level, w.name AS warehouse,
                    COALESCE(i.quantity,0) AS quantity,
                    CASE WHEN COALESCE(i.quantity,0) <= p.reorder_level THEN 'Low' ELSE 'OK' END AS status
             FROM products p CROSS JOIN warehouses w
             LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=w.id
             ORDER BY p.name, w.name"
        );
        $movements = $this->db->fetchAll(
            "SELECT sm.type, COUNT(*) AS count, SUM(sm.quantity) AS total_qty
             FROM stock_movements sm WHERE sm.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY sm.type"
        );
        $this->json(compact('stock','movements'));
    }

    public function sales(): void {
        $this->requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $orders = $this->db->fetchAll(
            "SELECT so.*, c.name AS customer_name FROM sales_orders so
             JOIN customers c ON so.customer_id=c.id
             WHERE so.order_date BETWEEN ? AND ? ORDER BY so.order_date DESC", [$from,$to],'ss'
        );
        $byCustomer = $this->db->fetchAll(
            "SELECT c.id, c.name, COUNT(so.id) AS orders, SUM(so.total_amount) AS total
             FROM sales_orders so JOIN customers c ON so.customer_id=c.id
             WHERE so.order_date BETWEEN ? AND ? AND so.status!='cancelled'
             GROUP BY c.id, c.name ORDER BY total DESC LIMIT 10", [$from,$to],'ss'
        );
        $this->json(compact('orders','byCustomer','from','to'));
    }

    public function production(): void {
        $this->requireAuth();
        $records = $this->db->fetchAll(
            "SELECT pr.*, p.name AS product_name, f.full_name AS farmer_name,
                    COALESCE(SUM(pi.total_cost),0) AS total_cost,
                    CASE WHEN pr.actual_yield>0 THEN COALESCE(SUM(pi.total_cost),0)/pr.actual_yield ELSE 0 END AS cost_per_unit
             FROM production_records pr
             JOIN products p ON pr.product_id=p.id JOIN farmers f ON pr.farmer_id=f.id
             LEFT JOIN production_inputs pi ON pi.production_record_id=pr.id
             GROUP BY pr.id ORDER BY pr.planting_date DESC"
        );
        $this->json($records);
    }

    public function approvals(): void {
        $this->requireAuth();
        $data = $this->db->fetchAll(
            "SELECT aw.*, u1.full_name AS requester, u2.full_name AS reviewer
             FROM approval_workflows aw JOIN users u1 ON aw.requested_by=u1.id
             LEFT JOIN users u2 ON aw.reviewed_by=u2.id
             ORDER BY aw.requested_at DESC LIMIT 100"
        );
        $this->json($data);
    }

    public function archive(): void {
        $this->requireAuth();
        $tab  = $_GET['tab']  ?? 'sales';
        $from = $_GET['from'] ?? date('Y-01-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $data = match ($tab) {
            'sales' => $this->db->fetchAll(
                "SELECT so.*, c.name AS customer_name
                 FROM sales_orders so JOIN customers c ON so.customer_id=c.id
                 WHERE so.order_date BETWEEN ? AND ?
                 ORDER BY so.order_date DESC",
                [$from, $to], 'ss'
            ),
            'purchasing' => $this->db->fetchAll(
                "SELECT po.*, s.name AS supplier_name
                 FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id
                 WHERE DATE(po.created_at) BETWEEN ? AND ?
                 ORDER BY po.created_at DESC",
                [$from, $to], 'ss'
            ),
            'logistics' => $this->db->fetchAll(
                "SELECT * FROM deliveries
                 WHERE DATE(created_at) BETWEEN ? AND ?
                 ORDER BY created_at DESC",
                [$from, $to], 'ss'
            ),
            'production' => $this->db->fetchAll(
                "SELECT pr.*, p.name AS product_name, f.full_name AS farmer_name
                 FROM production_records pr
                 JOIN products p ON pr.product_id=p.id
                 JOIN farmers f ON pr.farmer_id=f.id
                 WHERE DATE(pr.created_at) BETWEEN ? AND ?
                 ORDER BY pr.created_at DESC",
                [$from, $to], 'ss'
            ),
            'processing' => $this->db->fetchAll(
                "SELECT pb.*, p.name AS product_name
                 FROM processing_batches pb JOIN products p ON pb.product_id=p.id
                 WHERE DATE(pb.created_at) BETWEEN ? AND ?
                 ORDER BY pb.created_at DESC",
                [$from, $to], 'ss'
            ),
            'qa' => $this->db->fetchAll(
                "SELECT qi.*, p.name AS product_name, u.full_name AS inspector_name
                 FROM qa_inspections qi
                 JOIN products p ON qi.product_id=p.id
                 JOIN users u ON qi.inspected_by=u.id
                 WHERE DATE(qi.created_at) BETWEEN ? AND ?
                 ORDER BY qi.created_at DESC",
                [$from, $to], 'ss'
            ),
            'finance' => $this->_financeArchive($from, $to),
            'inventory' => $this->db->fetchAll(
                "SELECT sm.*, p.name AS product_name, w.name AS warehouse_name
                 FROM stock_movements sm
                 JOIN products p ON sm.product_id=p.id
                 JOIN warehouses w ON sm.warehouse_id=w.id
                 WHERE DATE(sm.created_at) BETWEEN ? AND ?
                 ORDER BY sm.created_at DESC",
                [$from, $to], 'ss'
            ),
            'ledger' => $this->db->fetchAll(
                "SELECT fl.*, f.full_name AS farmer_name
                 FROM farmer_ledger fl JOIN farmers f ON fl.farmer_id=f.id
                 WHERE DATE(fl.created_at) BETWEEN ? AND ?
                 ORDER BY fl.created_at DESC",
                [$from, $to], 'ss'
            ),
            'hr' => $this->db->fetchAll(
                "SELECT pr.*, e.full_name, d.name AS dept_name
                 FROM payroll pr
                 JOIN employees e ON pr.employee_id=e.id
                 LEFT JOIN departments d ON e.department_id=d.id
                 WHERE DATE(pr.created_at) BETWEEN ? AND ?
                 ORDER BY pr.created_at DESC",
                [$from, $to], 'ss'
            ),
            default => []
        };

        $this->view('reports/archive', compact('data', 'tab', 'from', 'to'));
    }

    private function _financeArchive(string $from, string $to): array {
        $receipts = $this->db->fetchAll(
            "SELECT id, amount, notes AS description, payment_method AS method_or_category,
                    'recorded' AS status, receipt_date AS txn_date, 'receipt' AS _type
             FROM receipts WHERE receipt_date BETWEEN ? AND ? ORDER BY receipt_date DESC",
            [$from, $to], 'ss'
        );
        $expenses = $this->db->fetchAll(
            "SELECT id, amount, description, category AS method_or_category,
                    status, expense_date AS txn_date, 'expense' AS _type
             FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC",
            [$from, $to], 'ss'
        );
        $payrolls = $this->db->fetchAll(
            "SELECT pr.id, pr.net_pay AS amount,
                    CONCAT(e.full_name, ' (', pr.period_start, ' to ', pr.period_end, ')') AS description,
                    'payroll' AS method_or_category, pr.status, pr.created_at AS txn_date, 'payroll' AS _type
             FROM payroll pr JOIN employees e ON pr.employee_id=e.id
             WHERE DATE(pr.created_at) BETWEEN ? AND ? ORDER BY pr.created_at DESC",
            [$from, $to], 'ss'
        );
        $merged = array_merge($receipts, $expenses, $payrolls);
        usort($merged, fn($a, $b) => strcmp($b['txn_date'], $a['txn_date']));
        return $merged;
    }
}

