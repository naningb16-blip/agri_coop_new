<?php
ob_start();
$pageTitle = 'Approval Request #' . $request['id'];
$activeMenu = 'approvals';

$statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'skipped' => 'secondary'];
?>

<div class="row g-3">
    <!-- Request Info -->
    <div class="col-lg-5">
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-file-earmark-text me-2"></i>Request Details</span>
                <span class="badge badge-<?= $request['status'] ?>"><?= ucfirst($request['status']) ?></span>
            </div>
            <div class="p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width:40%">ID</th><td>#<?= $request['id'] ?></td></tr>
                    <tr><th class="text-muted">Module</th><td><span class="badge bg-secondary"><?= htmlspecialchars($request['module'] ?? '') ?></span></td></tr>
                    <tr><th class="text-muted">Reference</th><td><?= htmlspecialchars($request['reference_type'] ?? '') ?> #<?= $request['reference_id'] ?? '' ?></td></tr>
                    <tr><th class="text-muted">Title</th><td><?= htmlspecialchars($request['title'] ?? '') ?></td></tr>
                    <tr><th class="text-muted">Description</th><td><?= nl2br(htmlspecialchars($request['description'] ?? '')) ?></td></tr>
                    <tr><th class="text-muted">Requested By</th><td><?= htmlspecialchars($request['requester_name'] ?? '') ?></td></tr>
                    <tr><th class="text-muted">Submitted</th><td><?= date('M d, Y H:i', strtotime($request['created_at'])) ?></td></tr>
                    <tr><th class="text-muted">Last Updated</th><td><?= date('M d, Y H:i', strtotime($request['updated_at'])) ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Action Panel -->
        <?php if ($canAct): ?>
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-check2-circle me-2 text-success"></i>Take Action</span></div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea id="actionRemarks" class="form-control" rows="3" placeholder="Optional remarks..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success flex-fill" onclick="doAction('approved')">
                        <i class="bi bi-check-lg me-1"></i>Approve
                    </button>
                    <button class="btn btn-danger flex-fill" onclick="doAction('rejected')">
                        <i class="bi bi-x-lg me-1"></i>Reject
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Steps + Audit -->
    <div class="col-lg-7">
        <!-- Audit Log -->
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-journal-text me-2"></i>Audit Log & Comments</span></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Time</th><th>Actor</th><th>Step</th><th>Action</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($audit as $log): ?>
                    <tr class="<?= $log['action'] === 'commented' ? 'table-info' : '' ?>">
                        <td class="text-muted small"><?= date('M d H:i', strtotime($log['created_at'])) ?></td>
                        <td><?= htmlspecialchars($log['actor_name']) ?></td>
                        <td class="text-center"><?= $log['action'] === 'commented' ? '<i class="bi bi-chat-dots text-info"></i>' : $log['step_order'] ?></td>
                        <td>
                            <span class="badge bg-<?= $log['action'] === 'commented' ? 'info' : ($statusColors[$log['action']] ?? 'secondary') ?>">
                                <?= ucfirst($log['action']) ?>
                            </span>
                        </td>
                        <td class="small"><?= nl2br(htmlspecialchars($log['remarks'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($audit)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No audit entries.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Comment Box &#8369; visible to BOD, manager, gm, admin -->
            <?php
            $userRole = $_SESSION['user']['role'] ?? '';
            $canComment = in_array($userRole, ['bod','manager','gm','admin']);
            ?>
            <?php if ($canComment): ?>
            <div class="p-3 border-top">
                <label class="form-label small fw-semibold"><i class="bi bi-chat-dots me-1 text-info"></i>Add Comment</label>
                <textarea id="commentText" class="form-control form-control-sm mb-2" rows="2" placeholder="Write a comment visible to all parties"></textarea>
                <button class="btn btn-sm btn-info text-white" onclick="submitComment()">
                    <i class="bi bi-send me-1"></i>Post Comment
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function doAction(action) {
    const remarks = document.getElementById('actionRemarks').value;
    if (action === 'rejected' && !remarks.trim()) {
        alert('Please provide remarks when rejecting.');
        return;
    }
    if (!confirm(`Are you sure you want to ${action} this request?`)) return;

    fetch('<?= BASE_URL ?>/approvals/act', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            request_id: <?= $request['id'] ?>,
            action,
            remarks
        })
    })
    .then(r => r.json())
    .then(res => {
        alert(res.message);
        if (res.success) location.reload();
    });
}

function submitComment() {
    const text = document.getElementById('commentText').value.trim();
    if (!text) { alert('Please write a comment first.'); return; }
    fetch('<?= BASE_URL ?>/approvals/comment', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ request_id: <?= $request['id'] ?>, comment: text })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) location.reload();
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
