<?php
require_once __DIR__ . '/../../core/Controller.php';

class BODController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['bod', 'admin', 'gm'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Access restricted to Board of Directors.'];
            $this->redirect('/dashboard');
        }

        // All approval requests across all departments with full chain info
        $allRequests = $this->db->fetchAll(
            "SELECT ar.*, u.full_name AS requester_name,
                    um.full_name AS manager_name, ams.actioned_at AS manager_at, ams.status AS manager_status,
                    ugm.full_name AS gm_name, ags.actioned_at AS gm_at, ags.status AS gm_status
             FROM approval_requests ar
             JOIN users u ON ar.requested_by = u.id
             LEFT JOIN approval_steps ams ON ams.request_id = ar.id AND ams.approver_role = 'manager'
             LEFT JOIN users um ON um.id = ams.actioned_by
             LEFT JOIN approval_steps ags ON ags.request_id = ar.id AND ags.approver_role = 'gm'
             LEFT JOIN users ugm ON ugm.id = ags.actioned_by
             ORDER BY ar.created_at DESC"
        );

        // Summary stats
        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(status='pending')  AS pending,
                    SUM(status='approved') AS approved,
                    SUM(status='rejected') AS rejected
             FROM approval_requests"
        );

        // By module breakdown
        $byModule = $this->db->fetchAll(
            "SELECT module, COUNT(*) AS total,
                    SUM(status='pending')  AS pending,
                    SUM(status='approved') AS approved,
                    SUM(status='rejected') AS rejected
             FROM approval_requests
             GROUP BY module ORDER BY module"
        );

        // Recent activity (last 30 days) — all actions by anyone
        $recentActivity = $this->db->fetchAll(
            "SELECT al.*, ar.title, ar.module, u.full_name AS actor_name, ur.full_name AS requester_name
             FROM approval_audit_log al
             JOIN approval_requests ar ON al.request_id = ar.id
             JOIN users u ON al.actor_id = u.id
             JOIN users ur ON ar.requested_by = ur.id
             WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             ORDER BY al.created_at DESC LIMIT 100"
        );

        // Monthly sales data for the last 12 months
        $monthlySales = $this->db->fetchAll(
            "SELECT DATE_FORMAT(order_date, '%Y-%m') AS month,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(total_amount), 0) AS total_sales,
                    COALESCE(SUM(CASE WHEN payment_status='paid' THEN total_amount ELSE 0 END), 0) AS paid_amount
             FROM sales_orders
             WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(order_date, '%Y-%m')
             ORDER BY month ASC"
        );

        $this->view('bod/index', compact('allRequests', 'summary', 'byModule', 'recentActivity', 'monthlySales'));
    }
}

