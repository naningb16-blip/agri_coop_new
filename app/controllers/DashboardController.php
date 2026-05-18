﻿<?php
require_once __DIR__ . '/../../core/Controller.php';

class DashboardController extends Controller {

    public function index(): void {
            $this->requireAuth();

            $role = $_SESSION['user']['role'] ?? '';

            // Dept users go straight to their module — no dashboard for them
            if (str_ends_with($role, '_user')) {
                $module = str_replace('_user', '', $role);
                $moduleRoutes = [
                    'sales'      => '/sales',
                    'purchasing' => '/purchasing',
                    'inventory'  => '/inventory',
                    'hr'         => '/hr',
                    'finance'    => '/finance',
                    'logistics'  => '/logistics',
                    'production' => '/production',
                    'processing' => '/processing',
                    'qa'         => '/qa',
                ];
                $this->redirect($moduleRoutes[$module] ?? '/approvals');
            }

            // GM gets a simplified approval-only dashboard
            if ($role === 'gm') {
                $this->requirePermission('dashboard', 'view');
                $this->gmDashboard();
                return;
            }

            // Manager, admin, bod get the full dashboard
            // Only admin, bod see the full dashboard

            $stats = [
                'total_employees'   => $this->_safe("SELECT COUNT(*) AS c FROM employees WHERE status='active'"),
                'pending_approvals' => $this->_safe("SELECT COUNT(*) AS c FROM approval_requests WHERE status='pending'"),
                'low_stock'         => $this->_safe("SELECT COUNT(*) AS c FROM inventory i JOIN products p ON i.product_id=p.id WHERE i.quantity <= p.reorder_level AND i.quantity > 0"),
                'open_sales'        => $this->_safe("SELECT COUNT(*) AS c FROM sales_orders WHERE status IN ('pending','approved','processing')"),
                'monthly_revenue'   => $this->_safe("SELECT COALESCE(SUM(total_amount),0) AS c FROM sales_orders WHERE MONTH(order_date)=MONTH(NOW()) AND YEAR(order_date)=YEAR(NOW()) AND status!='cancelled'"),
                'monthly_expenses'  => $this->_safe("SELECT COALESCE(SUM(amount),0) AS c FROM expenses WHERE MONTH(expense_date)=MONTH(NOW()) AND YEAR(expense_date)=YEAR(NOW()) AND status='approved'"),
                'active_batches'    => $this->_safe("SELECT COUNT(*) AS c FROM processing_batches WHERE status='in_progress'"),
                'pending_deliveries'=> $this->_safe("SELECT COUNT(*) AS c FROM deliveries WHERE status IN ('pending','in_transit')"),
                'total_yield_month' => $this->_safe("SELECT COALESCE(SUM(actual_yield),0) AS c FROM production_records WHERE MONTH(actual_harvest)=MONTH(NOW()) AND YEAR(actual_harvest)=YEAR(NOW())"),
                'qa_failed_month'   => $this->_safe("SELECT COUNT(*) AS c FROM qa_inspections WHERE result='failed' AND MONTH(inspection_date)=MONTH(NOW())"),
            ];

            $recent_sales     = $this->_safeAll("SELECT so.*, c.name AS customer_name FROM sales_orders so JOIN customers c ON so.customer_id=c.id ORDER BY so.created_at DESC LIMIT 6");
            $recent_approvals = $this->_safeAll("SELECT ar.*, u.full_name AS requester FROM approval_requests ar JOIN users u ON ar.requested_by=u.id WHERE ar.status='pending' ORDER BY ar.created_at DESC LIMIT 6");
            $chart_data       = $this->_safeAll("SELECT YEAR(order_date) AS yr, MONTH(order_date) AS mo, DATE_FORMAT(MIN(order_date),'%b') AS month, SUM(total_amount) AS total FROM sales_orders WHERE YEAR(order_date) >= YEAR(NOW())-3 AND status!='cancelled' GROUP BY YEAR(order_date), MONTH(order_date) ORDER BY yr ASC, mo ASC");
            $chart_data_daily = $this->_safeAll("SELECT YEAR(order_date) AS yr, MONTH(order_date) AS mo, DAY(order_date) AS day, SUM(total_amount) AS total FROM sales_orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND status!='cancelled' GROUP BY YEAR(order_date), MONTH(order_date), DAY(order_date) ORDER BY yr ASC, mo ASC, day ASC");
            $inventory_chart  = $this->_safeAll("SELECT w.id, w.name AS warehouse, COALESCE(SUM(i.quantity),0) AS total FROM warehouses w LEFT JOIN inventory i ON i.warehouse_id=w.id GROUP BY w.id, w.name ORDER BY total DESC LIMIT 6");
            $production_chart = $this->_safeAll("SELECT status, COUNT(*) AS count FROM production_records GROUP BY status");
            $top_products     = $this->_safeAll("SELECT p.id, p.name, SUM(soi.quantity) AS qty_sold, SUM(soi.total_price) AS revenue FROM sales_order_items soi JOIN products p ON soi.product_id=p.id JOIN sales_orders so ON soi.so_id=so.id WHERE so.status!='cancelled' AND so.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY p.id, p.name ORDER BY revenue DESC LIMIT 5");

            $this->view('dashboard/index', compact('stats', 'recent_sales', 'recent_approvals', 'chart_data', 'chart_data_daily', 'inventory_chart', 'production_chart', 'top_products'));
        }

    private function _safe(string $sql): int|float {
        try { return $this->db->fetchOne($sql)['c'] ?? 0; } catch (\Throwable $e) { return 0; }
    }

    private function _safeAll(string $sql): array {
        try { return $this->db->fetchAll($sql); } catch (\Throwable $e) { return []; }
    }

    public function ajaxStats(): void {
        $this->requireAuth();
        $this->json([
            'pending_approvals'  => $this->_safe("SELECT COUNT(*) AS c FROM approval_requests WHERE status='pending'"),
            'low_stock'          => $this->_safe("SELECT COUNT(*) AS c FROM inventory i JOIN products p ON i.product_id=p.id WHERE i.quantity <= p.reorder_level AND i.quantity > 0"),
            'open_sales'         => $this->_safe("SELECT COUNT(*) AS c FROM sales_orders WHERE status IN ('pending','approved','processing')"),
            'active_batches'     => $this->_safe("SELECT COUNT(*) AS c FROM processing_batches WHERE status='in_progress'"),
            'pending_deliveries' => $this->_safe("SELECT COUNT(*) AS c FROM deliveries WHERE status IN ('pending','in_transit')"),
            'monthly_revenue'    => $this->_safe("SELECT COALESCE(SUM(total_amount),0) AS c FROM sales_orders WHERE MONTH(order_date)=MONTH(NOW()) AND YEAR(order_date)=YEAR(NOW()) AND status!='cancelled'"),
        ]);
    }

    private function gmDashboard(): void {
        // GM-specific dashboard with only approval-related data
        $stats = [
            'total_pending_approvals' => $this->_safe("SELECT COUNT(*) AS c FROM approval_requests WHERE status='pending'"),
            'my_pending_approvals' => $this->_safe("SELECT COUNT(*) AS c FROM approval_requests WHERE status='pending'"), // All pending go to GM now
            'approved_today' => $this->_safe("SELECT COUNT(*) AS c FROM approval_requests WHERE status='approved' AND DATE(updated_at) = CURDATE()"),
            'rejected_today' => $this->_safe("SELECT COUNT(*) AS c FROM approval_requests WHERE status='rejected' AND DATE(updated_at) = CURDATE()"),
            'approved_this_month' => $this->_safe("SELECT COUNT(*) AS c FROM approval_requests WHERE status='approved' AND MONTH(updated_at)=MONTH(NOW()) AND YEAR(updated_at)=YEAR(NOW())"),
            'rejected_this_month' => $this->_safe("SELECT COUNT(*) AS c FROM approval_requests WHERE status='rejected' AND MONTH(updated_at)=MONTH(NOW()) AND YEAR(updated_at)=YEAR(NOW())"),
            'low_stock' => $this->_safe("SELECT COUNT(*) AS c FROM inventory i JOIN products p ON i.product_id=p.id WHERE i.quantity <= p.reorder_level AND i.quantity > 0"),
        ];
        
        // Get low stock items for alert
        $low_stock_items = $this->_safeAll(
            "SELECT p.id, p.name, p.unit, p.reorder_level,
                    COALESCE(SUM(i.quantity), 0) AS current_stock,
                    p.reorder_level - COALESCE(SUM(i.quantity), 0) AS shortage
             FROM products p
             LEFT JOIN inventory i ON p.id = i.product_id
             WHERE p.reorder_level > 0
             GROUP BY p.id, p.name, p.unit, p.reorder_level
             HAVING current_stock < p.reorder_level
             ORDER BY shortage DESC
             LIMIT 10"
        );

        // Get all pending approvals (all go to GM now)
        $my_approvals = $this->_safeAll("
            SELECT ar.*, u.full_name AS requester
            FROM approval_requests ar 
            JOIN users u ON ar.requested_by=u.id 
            WHERE ar.status='pending' 
            ORDER BY ar.created_at DESC 
            LIMIT 10
        ");

        // Get recent approvals processed by GM
        $recent_processed = $this->_safeAll("
            SELECT ar.*, u.full_name AS requester, acs.status AS my_decision, acs.actioned_at AS decision_date
            FROM approval_requests ar 
            JOIN users u ON ar.requested_by=u.id 
            JOIN approval_steps acs ON acs.request_id=ar.id AND acs.approver_role='gm'
            WHERE acs.status IN ('approved', 'rejected') 
            ORDER BY acs.actioned_at DESC 
            LIMIT 10
        ");

        // Approval statistics by module
        $module_stats = $this->_safeAll("
            SELECT ar.module, 
                   COUNT(*) AS total,
                   SUM(CASE WHEN ar.status='pending' THEN 1 ELSE 0 END) AS pending,
                   SUM(CASE WHEN ar.status='approved' THEN 1 ELSE 0 END) AS approved,
                   SUM(CASE WHEN ar.status='rejected' THEN 1 ELSE 0 END) AS rejected
            FROM approval_requests ar 
            WHERE ar.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY ar.module 
            ORDER BY total DESC
        ");

        // Get today's attendance summary
        $attendance_today = $this->_safeAll("
            SELECT e.id, e.full_name, e.position, d.name AS department,
                   a.status, a.time_in, a.time_out, a.remarks
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN attendance a ON a.employee_id = e.id AND a.date = CURDATE()
            WHERE e.status = 'active'
            ORDER BY d.name, e.full_name
        ");

        $this->view('dashboard/gm', compact('stats', 'my_approvals', 'recent_processed', 'module_stats', 'attendance_today', 'low_stock_items'));
    }

    public function employeeAttendance(): void {
        $this->requireAuth();
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $month = $_GET['month'] ?? date('Y-m');
        
        if (!$employeeId) {
            $this->json(['success' => false, 'message' => 'Employee ID required']);
        }
        
        $employee = $this->db->fetchOne(
            "SELECT e.*, d.name AS department FROM employees e 
             LEFT JOIN departments d ON e.department_id = d.id 
             WHERE e.id = ?",
            [$employeeId], 'i'
        );
        
        if (!$employee) {
            $this->json(['success' => false, 'message' => 'Employee not found']);
        }
        
        $attendance = $this->db->fetchAll(
            "SELECT * FROM attendance 
             WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
             ORDER BY date DESC",
            [$employeeId, $month], 'is'
        );
        
        $summary = $this->db->fetchOne(
            "SELECT 
                COUNT(*) AS total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) AS half_day,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) AS leave_days
             FROM attendance 
             WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?",
            [$employeeId, $month], 'is'
        );
        
        $this->json([
            'success' => true,
            'employee' => $employee,
            'attendance' => $attendance,
            'summary' => $summary,
            'month' => $month
        ]);
    }

    public function attendanceByDate(): void {
        $this->requireAuth();
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $attendance = $this->db->fetchAll(
            "SELECT e.id, e.full_name, e.position, d.name AS department,
                    a.status, a.time_in, a.time_out, a.remarks
             FROM employees e
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN attendance a ON a.employee_id = e.id AND a.date = ?
             WHERE e.status = 'active'
             ORDER BY d.name, e.full_name",
            [$date], 's'
        );
        
        $this->json([
            'success' => true,
            'attendance' => $attendance,
            'date' => $date
        ]);
    }
}