<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/NotificationHelper.php';

class DocumentController extends Controller {

    private NotificationHelper $notif;

    // Department routing order and labels
    private const DEPT_CHAIN = [
        ['role' => 'sales_user',      'label' => 'Sales'],
        ['role' => 'purchasing_user', 'label' => 'Purchasing'],
        ['role' => 'inventory_user',  'label' => 'Inventory'],
        ['role' => 'hr_user',         'label' => 'Human Resources'],
        ['role' => 'finance_user',    'label' => 'Finance'],
        ['role' => 'logistics_user',  'label' => 'Logistics'],
        ['role' => 'production_user', 'label' => 'Production'],
        ['role' => 'processing_user', 'label' => 'Processing'],
        ['role' => 'qa_user',         'label' => 'Quality Assurance'],
    ];

    private function uploadDir(): string {
        return (defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../../public/uploads/') . 'documents/';
    }

    public function __construct() {
        parent::__construct();
        $this->notif = new NotificationHelper();
    }

    // &#8369;8369;”€ List &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function index(): void {
        $this->requireAuth();
        $role   = $_SESSION['user']['role'] ?? '';
        $userId = $_SESSION['user_id'];

        // BOD and GM see all; dept users see docs routed to them + their own uploads
        if (in_array($role, ['admin', 'manager', 'gm', 'bod'])) {
            $docs = $this->db->fetchAll(
                "SELECT rd.*, u.full_name AS uploader_name,
                        (SELECT drs.dept_label FROM document_routing_steps drs
                         WHERE drs.document_id=rd.id AND drs.status='pending'
                         ORDER BY drs.step_order LIMIT 1) AS current_dept
                 FROM routed_documents rd
                 JOIN users u ON rd.uploaded_by=u.id
                 ORDER BY rd.created_at DESC"
            );
        } else {
            // Dept user: their uploads + docs pending their review
            $docs = $this->db->fetchAll(
                "SELECT DISTINCT rd.*, u.full_name AS uploader_name,
                        (SELECT drs2.dept_label FROM document_routing_steps drs2
                         WHERE drs2.document_id=rd.id AND drs2.status='pending'
                         ORDER BY drs2.step_order LIMIT 1) AS current_dept
                 FROM routed_documents rd
                 JOIN users u ON rd.uploaded_by=u.id
                 LEFT JOIN document_routing_steps drs ON drs.document_id=rd.id AND drs.dept_role=?
                 WHERE rd.uploaded_by=? OR drs.id IS NOT NULL
                 ORDER BY rd.created_at DESC",
                [$role, $userId], 'si'
            );
        }

        $this->view('documents/index', compact('docs'));
    }

    // &#8369;8369;”€ Upload &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function upload(): void {
        $this->requireAuth();
        $role  = $_SESSION['user']['role'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');

        if (empty($title)) $this->json(['success' => false, 'message' => 'Title is required.']);
        if (empty($_FILES['document']['name'])) $this->json(['success' => false, 'message' => 'Please select a file.']);

        $file     = $_FILES['document'];
        $allowed  = ['pdf','doc','docx','xls','xlsx','png','jpg','jpeg','txt','csv'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $this->json(['success' => false, 'message' => 'File type not allowed. Allowed: ' . implode(', ', $allowed)]);
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'File too large. Max 10MB.']);
        }

        // Save file to disk (best effort) AND store in DB for persistence
        $uploadDir = $this->uploadDir();
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $safeName = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $destPath = $uploadDir . $safeName;
        move_uploaded_file($file['tmp_name'], $destPath); // non-fatal if fails

        // Read file content for DB storage
        $fileContent = file_exists($destPath) ? file_get_contents($destPath) : file_get_contents($file['tmp_name']);

        // Determine origin dept
        $originDept = $role;
        if (in_array($role, ['admin', 'manager'])) $originDept = 'admin';

        // Try to store file content in DB (requires file_content column from migration)
        $hasFileContentCol = false;
        try {
            $check = $this->db->fetchOne("SHOW COLUMNS FROM routed_documents LIKE 'file_content'");
            $hasFileContentCol = !empty($check);
        } catch (\Throwable $e) {}

        if ($hasFileContentCol) {
            $docId = $this->db->insert(
                "INSERT INTO routed_documents (title, description, file_name, file_path, file_size, file_type, uploaded_by, origin_dept, file_content)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [$title, $desc, $file['name'], 'uploads/documents/' . $safeName,
                 $file['size'], $file['type'], $_SESSION['user_id'], $originDept, $fileContent],
                'ssssissss'
            );
        } else {
            $docId = $this->db->insert(
                "INSERT INTO routed_documents (title, description, file_name, file_path, file_size, file_type, uploaded_by, origin_dept)
                 VALUES (?,?,?,?,?,?,?,?)",
                [$title, $desc, $file['name'], 'uploads/documents/' . $safeName,
                 $file['size'], $file['type'], $_SESSION['user_id'], $originDept],
                'ssssisss'
            );
        }

        // Build routing steps &#8369; skip uploader's own dept
        $order = 1;
        foreach (self::DEPT_CHAIN as $dept) {
            $status = ($dept['role'] === $originDept) ? 'skipped' : 'pending';
            $this->db->insert(
                "INSERT INTO document_routing_steps (document_id, dept_role, dept_label, step_order, status)
                 VALUES (?,?,?,?,?)",
                [$docId, $dept['role'], $dept['label'], $order, $status],
                'issss'
            );
            $order++;
        }

        // Audit
        $this->_log($docId, $_SESSION['user_id'], 'uploaded', $originDept, 'Document uploaded and routing started.');

        // Notify the first department in the chain (skip the uploader's dept)
        $firstStep = $this->db->fetchOne(
            "SELECT * FROM document_routing_steps WHERE document_id=? AND status='pending' ORDER BY step_order LIMIT 1",
            [$docId], 'i'
        );
        if ($firstStep) {
            $this->notif->notifyRole(
                $firstStep['dept_role'],
                'doc_pending',
                'Document Pending Your Review',
                "A new document \"{$title}\" has been submitted and is waiting for your department's approval.",
                BASE_URL . '/documents/detail?id=' . $docId
            );
        }

        $this->json(['success' => true, 'message' => 'Document uploaded and routing started.', 'id' => $docId]);
    }

    // &#8369;8369;”€ Detail &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function detail(): void {
        $this->requireAuth();
        $id  = (int)($_GET['id'] ?? 0);
        $doc = $this->db->fetchOne(
            "SELECT rd.*, u.full_name AS uploader_name
             FROM routed_documents rd JOIN users u ON rd.uploaded_by=u.id
             WHERE rd.id=?", [$id], 'i'
        );
        if (!$doc) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Document not found.']; $this->redirect('/documents'); }

        $role   = $_SESSION['user']['role'] ?? '';
        $userId = $_SESSION['user_id'];

        // Access check: dept users can only view docs they uploaded or that are routed to them
        if (!in_array($role, ['admin', 'manager', 'gm', 'bod'])) {
            $hasAccess = ($doc['uploaded_by'] == $userId);
            if (!$hasAccess) {
                $step = $this->db->fetchOne(
                    "SELECT id FROM document_routing_steps WHERE document_id=? AND dept_role=?",
                    [$id, $role], 'is'
                );
                $hasAccess = !empty($step);
            }
            if (!$hasAccess) {
                $_SESSION['flash'] = ['type'=>'danger','msg'=>'Access denied.'];
                $this->redirect('/documents');
            }
        }

        $steps = $this->db->fetchAll(
            "SELECT drs.*, u.full_name AS actioned_by_name
             FROM document_routing_steps drs
             LEFT JOIN users u ON drs.actioned_by=u.id
             WHERE drs.document_id=? ORDER BY drs.step_order", [$id], 'i'
        );
        $log = $this->db->fetchAll(
            "SELECT drl.*, u.full_name AS actor_name
             FROM document_routing_log drl JOIN users u ON drl.actor_id=u.id
             WHERE drl.document_id=? ORDER BY drl.created_at ASC", [$id], 'i'
        );

        $role     = $_SESSION['user']['role'] ?? '';
        $canAct  = false;
        $myStep  = null;

        if ($doc['status'] === 'routing') {
            // Only GM and admin can act on documents
            if ($role === 'admin' || $role === 'gm') {
                $firstPending = null;
                foreach ($steps as $step) {
                    if ($step['status'] === 'pending') {
                        $firstPending = $step;
                        break;
                    }
                }
                if ($firstPending) {
                    $canAct = true;
                    $myStep = $firstPending;
                }
            }
        }

        $this->view('documents/detail', compact('doc', 'steps', 'log', 'canAct', 'myStep'));
    }

    // &#8369;8369;”€ Act (approve / reject &#8369; hard stop with full visibility) &#8369;

    public function act(): void {
        $this->requireAuth();
        $docId   = (int)($_POST['document_id'] ?? 0);
        $stepId  = (int)($_POST['step_id']     ?? 0);
        $action  = $_POST['action']             ?? '';
        $remarks = trim($_POST['remarks']       ?? '');
        $role    = $_SESSION['user']['role']    ?? '';

        if (!in_array($action, ['approved', 'rejected'])) {
            $this->json(['success' => false, 'message' => 'Invalid action.']);
        }
        if ($action === 'rejected' && empty($remarks)) {
            $this->json(['success' => false, 'message' => 'Remarks are required when rejecting.']);
        }

        $doc  = $this->db->fetchOne("SELECT * FROM routed_documents WHERE id=?", [$docId], 'i');
        $step = $this->db->fetchOne(
            "SELECT * FROM document_routing_steps WHERE id=? AND document_id=?",
            [$stepId, $docId], 'ii'
        );

        if (!$doc || $doc['status'] !== 'routing') {
            $this->json(['success' => false, 'message' => 'Document not in routing.']);
        }
        if (!$step || $step['status'] !== 'pending') {
            $this->json(['success' => false, 'message' => 'Step not found or already actioned.']);
        }
        if ($role !== 'admin' && $role !== 'gm') {
            $this->json(['success' => false, 'message' => 'Only GM can approve or reject documents.']);
        }

        // &#8369;8369;”€ APPROVED &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;
        if ($action === 'approved') {
            $this->db->query(
                "UPDATE document_routing_steps SET status='approved', actioned_by=?, remarks=?, actioned_at=NOW() WHERE id=?",
                [$_SESSION['user_id'], $remarks, $stepId], 'sii'
            );
            $this->_log($docId, $_SESSION['user_id'], 'approved', $step['dept_role'], $remarks);

            // Find next pending step
            $next = $this->db->fetchOne(
                "SELECT * FROM document_routing_steps
                 WHERE document_id=? AND step_order>? AND status='pending'
                 ORDER BY step_order LIMIT 1",
                [$docId, $step['step_order']], 'ii'
            );

            if ($next) {
                // Notify the next department it's their turn
                $this->notif->notifyRole(
                    $next['dept_role'],
                    'doc_pending',
                    'Document Pending Your Review',
                    "Document \"{$doc['title']}\" has been approved by {$step['dept_label']} and is now waiting for your department's review.",
                    BASE_URL . '/documents/detail?id=' . $docId
                );
                $this->json(['success' => true, 'message' => "Approved. Forwarded to: {$next['dept_label']}."]);
            }

            // All steps done
            $this->db->query("UPDATE routed_documents SET status='approved' WHERE id=?", [$docId], 'i');
            $this->_log($docId, $_SESSION['user_id'], 'approved', 'system', 'All departments approved.');

            // Notify the original uploader that their document is fully approved
            $this->notif->notifyUser(
                $doc['uploaded_by'],
                'doc_fully_approved',
                'Document Fully Approved',
                "Your document \"{$doc['title']}\" has been approved by all departments.",
                BASE_URL . '/documents/detail?id=' . $docId
            );

            $this->json(['success' => true, 'message' => 'Document fully approved by all departments.']);
        }

        // &#8369;8369;”€ REJECTED &#8369; hard stop, visible to all in chain &#8369;8369;”€&#8369;8369;”€
        // Mark this step rejected
        $this->db->query(
            "UPDATE document_routing_steps SET status='rejected', actioned_by=?, remarks=?, actioned_at=NOW() WHERE id=?",
            [$_SESSION['user_id'], $remarks, $stepId], 'sii'
        );

        // Mark all remaining pending steps as cancelled (so chain is frozen)
        $this->db->query(
            "UPDATE document_routing_steps SET status='skipped' WHERE document_id=? AND status='pending'",
            [$docId], 'i'
        );

        // Mark document as rejected
        $this->db->query(
            "UPDATE routed_documents SET status='rejected', rejection_reason=?, rejected_by=?, rejected_at=NOW() WHERE id=?",
            [$remarks, $_SESSION['user_id'], $docId], 'sii'
        );

        $this->_log($docId, $_SESSION['user_id'], 'rejected', $step['dept_role'],
            "REJECTED by {$step['dept_label']}: {$remarks}");

        // Notify the original uploader
        $this->notif->notifyUser(
            $doc['uploaded_by'],
            'doc_rejected',
            'Document Rejected',
            "Your document \"{$doc['title']}\" was rejected by {$step['dept_label']}. Reason: {$remarks}",
            BASE_URL . '/documents/detail?id=' . $docId
        );

        // Notify all departments that previously approved
        $approvedSteps = $this->db->fetchAll(
            "SELECT * FROM document_routing_steps WHERE document_id=? AND status='approved'",
            [$docId], 'i'
        );
        foreach ($approvedSteps as $prevStep) {
            $this->notif->notifyRole(
                $prevStep['dept_role'],
                'doc_rejected',
                'Document Rejected After Your Approval',
                "Document \"{$doc['title']}\" which you approved was subsequently rejected by {$step['dept_label']}. Reason: {$remarks}",
                BASE_URL . '/documents/detail?id=' . $docId
            );
        }

        $this->json([
            'success' => true,
            'message' => "Document rejected by {$step['dept_label']}. All parties have been notified."
        ]);
    }

    // &#8369;8369;”€ Download &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function download(): void {
        $this->requireAuth();
        $id  = (int)($_GET['id'] ?? 0);

        // Fetch without file_content first to check existence
        $doc = $this->db->fetchOne("SELECT id, file_name, file_path, file_type, uploaded_by FROM routed_documents WHERE id=?", [$id], 'i');
        if (!$doc) { http_response_code(404); echo 'Not found'; exit; }

        // Access check: dept users can only download docs they uploaded or routed to them
        $role   = $_SESSION['user']['role'] ?? '';
        $userId = $_SESSION['user_id'];
        if (!in_array($role, ['admin', 'manager', 'gm', 'bod'])) {
            $hasAccess = ($doc['uploaded_by'] == $userId);
            if (!$hasAccess) {
                $step = $this->db->fetchOne(
                    "SELECT id FROM document_routing_steps WHERE document_id=? AND dept_role=?",
                    [$id, $role], 'is'
                );
                $hasAccess = !empty($step);
            }
            if (!$hasAccess) {
                http_response_code(403);
                echo 'Access denied.';
                exit;
            }
        }

        // Try to get file_content from DB (may not exist if migration not run)
        $fileContent = null;
        try {
            $row = $this->db->fetchOne("SELECT file_content FROM routed_documents WHERE id=?", [$id], 'i');
            $fileContent = $row['file_content'] ?? null;
        } catch (\Throwable $e) {}

        // Clean output buffer before sending file
        if (ob_get_level()) ob_end_clean();

        header('Content-Type: ' . ($doc['file_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($doc['file_name']) . '"');
        header('Cache-Control: no-cache');

        if (!empty($fileContent)) {
            header('Content-Length: ' . strlen($fileContent));
            echo $fileContent;
            exit;
        }

        $path = __DIR__ . '/../../public/' . $doc['file_path'];
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'File not found. Please re-upload the document.';
            exit;
        }

        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // &#8369;8369;”€ Private &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function delete(): void {
        $this->requireAuth();
        $id     = (int)($_POST['id'] ?? 0);
        $role   = $_SESSION['user']['role'] ?? '';
        $userId = $_SESSION['user_id'];

        $doc = $this->db->fetchOne("SELECT id, uploaded_by, file_path FROM routed_documents WHERE id=?", [$id], 'i');
        if (!$doc) $this->json(['success' => false, 'message' => 'Document not found.']);

        // Only the uploader (qa_user) or admin can delete
        if ($role !== 'admin' && $doc['uploaded_by'] != $userId) {
            $this->json(['success' => false, 'message' => 'You can only delete documents you uploaded.']);
        }

        // Delete file from disk if exists
        $path = __DIR__ . '/../../public/' . $doc['file_path'];
        if (file_exists($path)) @unlink($path);

        // Delete from DB (cascades to routing steps and log)
        $this->db->query("DELETE FROM document_routing_steps WHERE document_id=?", [$id], 'i');
        $this->db->query("DELETE FROM document_routing_log WHERE document_id=?", [$id], 'i');
        $this->db->query("DELETE FROM routed_documents WHERE id=?", [$id], 'i');

        $this->json(['success' => true, 'message' => 'Document deleted.']);
    }

    private function _log(int $docId, int $actorId, string $action, string $deptRole, string $remarks): void {
        $this->db->insert(
            "INSERT INTO document_routing_log (document_id, actor_id, action, dept_role, remarks) VALUES (?,?,?,?,?)",
            [$docId, $actorId, $action, $deptRole, $remarks], 'iisss'
        );
    }
}



