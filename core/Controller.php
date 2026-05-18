<?php
class Controller {
    protected Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    protected function view(string $view, array $data = []): void {
        extract($data);
        $viewFile = __DIR__ . '/../app/views/' . $view . '.php';
        if (!file_exists($viewFile)) die("View not found: $view");
        require $viewFile;
    }

    protected function json(mixed $data, int $code = 200): void {
        if (ob_get_level()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void {
        header("Location: " . BASE_URL . $url);
        exit;
    }

    protected function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) $this->redirect('/login');
    }

    protected function requirePermission(string $module, string $action): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';

        if (in_array($role, ['admin'])) return;

        // Finance users can access finance, ledger, monitoring, and reports (financial oversight)
        if ($role === 'finance_user' && in_array($module, ['finance', 'ledger', 'monitoring', 'reports'])) return;

        // GM can view all modules (for approval context) but can only perform approval actions
        if ($role === 'gm') {
            // GM can view any module content for approval context
            if ($action === 'view') return;
            
            // GM can only perform approval/rejection actions
            if (!in_array($module, ['approvals', 'documents', 'dashboard'])) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Access denied. GM can only approve/reject requests, not perform department operations.'];
                $this->redirect('/approvals');
            }
            return;
        }

        if ($role === 'bod') {
            if ($action !== 'view') {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Board of Directors has read-only access.'];
                $this->redirect('/bod');
            }
            return;
        }

        $perm = $this->db->fetchOne(
            "SELECT rp.* FROM role_permissions rp
             JOIN permissions p ON rp.permission_id = p.id
             JOIN users u ON u.role_id = rp.role_id
             WHERE u.id = ? AND p.module = ? AND p.action = ?",
            [$_SESSION['user_id'], $module, $action], 'iss'
        );
        if (!$perm) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Access denied.'];
            $this->redirect('/dashboard');
        }
    }

    protected function requireDeptAccess(string $module): void {
        $this->requireAuth();
        $role = $_SESSION['user']['role'] ?? '';
        if (in_array($role, ['admin', 'gm', 'bod'])) return;
        $expected = $module . '_user';
        if ($role !== $expected) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Access denied. This module belongs to another department.'];
            $this->redirect('/dashboard');
        }
    }

    protected function requireGMReadOnly(): bool {
        $role = $_SESSION['user']['role'] ?? '';
        return $role === 'gm';
    }
}
