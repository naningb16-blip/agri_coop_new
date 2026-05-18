<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ApprovalModel.php';

class ApprovalController extends Controller {
    private ApprovalModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new ApprovalModel();
    }

    public function index(): void {
        $this->requireAuth();
        $role    = $_SESSION['user']['role'] ?? '';
        $filters = [];

        $status = $_GET['status'] ?? '';
        $module = $_GET['module'] ?? '';
        if ($status) $filters['status'] = $status;
        if ($module) $filters['module'] = $module;

        // Dept users only see their own submissions for their own module
        if (str_ends_with($role, '_user')) {
            $filters['requested_by'] = $_SESSION['user_id'];
            if (empty($filters['module'])) {
                $filters['module'] = str_replace('_user', '', $role);
            }
        }

        $all     = $this->model->getAll($filters);
        // Manager and GM see ALL pending requests across all departments
        $pending = $this->model->getPendingForRole($role);
        $modules = $this->db->fetchAll("SELECT DISTINCT module FROM approval_chains ORDER BY module");

        $this->view('approvals/index', compact('all', 'pending', 'modules', 'filters'));
    }

    public function detail(): void {
        $this->requireAuth();
        $id      = (int)($_GET['id'] ?? 0);
        $request = $this->model->findById($id);
        if (!$request) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Request not found.']; $this->redirect('/approvals'); }

        $steps = $this->model->getSteps($id);
        $audit = $this->model->getAuditLog($id);
        $user  = $_SESSION['user'];

        $canAct = false;
        if ($request['status'] === 'pending') {
            foreach ($steps as $s) {
                if ($s['step_order'] == $request['current_step'] && $s['status'] === 'pending') {
                    // Admin, GM, and manager can approve anything
                    $canAct = in_array($user['role'], ['admin', 'gm', 'manager']) || $user['role'] === $s['approver_role'];
                    break;
                }
            }
        }

        $this->view('approvals/detail', compact('request', 'steps', 'audit', 'canAct'));
    }

    public function comment(): void {
        $this->requireAuth();
        $id      = (int)($_POST['request_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $role    = $_SESSION['user']['role'] ?? '';

        if (!$comment) $this->json(['success' => false, 'message' => 'Comment cannot be empty.']);
        if (!in_array($role, ['bod', 'manager', 'gm', 'admin'])) {
            $this->json(['success' => false, 'message' => 'You are not allowed to comment.']);
        }

        $req = $this->model->findById($id);
        if (!$req) $this->json(['success' => false, 'message' => 'Request not found.']);

        // Insert into audit log as a comment
        $this->db->insert(
            "INSERT INTO approval_audit_log (request_id, step_order, actor_id, action, remarks) VALUES (?,?,?,?,?)",
            [$id, 0, $_SESSION['user_id'], 'commented', $comment],
            'iiiss'
        );

        // Notify the requester
        require_once __DIR__ . '/../../core/NotificationHelper.php';
        $notif = new NotificationHelper();
        $notif->notifyUser(
            $req['requested_by'],
            'doc_pending',
            'New Comment on Your Request',
            "A comment was added to your request \"{$req['title']}\": $comment",
            BASE_URL . '/approvals/detail?id=' . $id
        );

        $this->json(['success' => true, 'message' => 'Comment posted.']);
    }

    public function act(): void {
        $this->requireAuth();
        $id      = (int)($_POST['request_id'] ?? 0);
        $action  = $_POST['action'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');

        if (!in_array($action, ['approved', 'rejected'])) {
            $this->json(['success' => false, 'message' => 'Invalid action.']);
        }

        $result = $this->model->actOnStep($id, $_SESSION['user_id'], $action, $remarks);
        $this->json($result);
    }

    public function submit(): void {
        $this->requireAuth();
        $data = [
            'module'         => trim($_POST['module'] ?? ''),
            'reference_type' => trim($_POST['reference_type'] ?? ''),
            'reference_id'   => (int)($_POST['reference_id'] ?? 0),
            'title'          => trim($_POST['title'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
        ];

        if (!$data['module'] || !$data['reference_type'] || !$data['reference_id'] || !$data['title']) {
            $this->json(['success' => false, 'message' => 'Missing required fields.']);
        }

        $chain = $this->model->getChain($data['module']);
        if (empty($chain)) {
            $this->json(['success' => false, 'message' => "No approval chain configured for module: {$data['module']}"]);
        }

        $reqId = $this->model->createRequest($data, $_SESSION['user_id']);
        $this->json(['success' => true, 'message' => 'Approval request submitted.', 'request_id' => $reqId]);
    }

    public function audit(): void {
        $this->requireAuth();
        $user = $_SESSION['user'];
        if (!in_array($user['role'], ['admin', 'gm'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Access denied.'];
            $this->redirect('/approvals');
        }
        $log = $this->model->getFullAuditLog(500);
        $this->view('approvals/audit', compact('log'));
    }
}
