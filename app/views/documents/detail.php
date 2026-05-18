<?php
ob_start();
$pageTitle  = 'Document: ' . $doc['title'];
$activeMenu = 'documents';
$sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','skipped'=>'secondary'];
$isUploader  = ($doc['uploaded_by'] == $_SESSION['user_id']);
$wasApprover = false;
foreach ($steps as $s) {
    if ($s['actioned_by'] == $_SESSION['user_id'] && $s['status'] === 'approved') {
        $wasApprover = true; break;
    }
}
?>

<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>/documents" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <a href="<?= BASE_URL ?>/documents/download?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-download me-1"></i>Download File
    </a>
</div>

<?php if ($doc['status'] === 'rejected'): ?>
<?php
// Find the rejected step
$rejectedStep = null;
foreach ($steps as $s) { if ($s['status'] === 'rejected') { $rejectedStep = $s; break; } }
?>
<div class="alert alert-danger mb-3">
    <div class="d-flex gap-2 align-items-start">
        <i class="bi bi-x-octagon-fill fs-4 mt-1 flex-shrink-0"></i>
        <div class="flex-fill">
            <strong class="d-block fs-6">Document Rejected &#8369; Full Stop</strong>
            <?php if ($rejectedStep): ?>
            <div class="mt-1">
                Rejected by <strong><?= htmlspecialchars($rejectedStep['dept_label']) ?></strong>
                <?php if ($rejectedStep['actioned_by_name']): ?>
                (<?= htmlspecialchars($rejectedStep['actioned_by_name']) ?>)
                <?php endif; ?>
                on <?= date('M d, Y H:i', strtotime($rejectedStep['actioned_at'])) ?>
            </div>
            <?php if ($rejectedStep['remarks']): ?>
            <div class="mt-2 p-2 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25">
                <strong>Reason:</strong> <?= nl2br(htmlspecialchars($rejectedStep['remarks'])) ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($isUploader): ?>
            <div class="mt-2 small text-danger fw-semibold">
                <i class="bi bi-info-circle me-1"></i>You uploaded this document. Please review the rejection reason and upload a revised version if needed.
            </div>
            <?php elseif ($wasApprover): ?>
            <div class="mt-2 small">
                <i class="bi bi-info-circle me-1"></i>You previously approved this document. It has been rejected by a subsequent department.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- Left: Document Info + Action -->
    <div class="col-lg-5">
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-file-earmark me-2"></i><?= htmlspecialchars($doc['title']) ?></span>
                <span class="badge badge-<?= $doc['status'] === 'routing' ? 'pending' : $doc['status'] ?>">
                    <?= ucfirst($doc['status']) ?>
                </span>
            </div>
            <div class="p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width:40%">File</th><td><?= htmlspecialchars($doc['file_name']) ?></td></tr>
                    <tr><th class="text-muted">Size</th><td><?= number_format($doc['file_size'] / 1024, 1) ?> KB</td></tr>
                    <tr><th class="text-muted">Uploaded By</th><td><?= htmlspecialchars($doc['uploader_name']) ?></td></tr>
                    <tr><th class="text-muted">Origin Dept</th><td><span class="badge bg-secondary"><?= ucwords(str_replace('_user','',$doc['origin_dept'])) ?></span></td></tr>
                    <tr><th class="text-muted">Uploaded</th><td><?= date('M d, Y H:i', strtotime($doc['created_at'])) ?></td></tr>
                    <?php if ($doc['description']): ?>
                    <tr><th class="text-muted">Description</th><td><?= nl2br(htmlspecialchars($doc['description'])) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Action Panel -->
        <?php if ($canAct && $myStep): ?>
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-check2-circle me-2 text-success"></i>Your Review &#8369; <?= htmlspecialchars($myStep['dept_label']) ?></span></div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea id="actionRemarks" class="form-control" rows="3" placeholder="Add remarks (required for rejection)&#8369;"></textarea>
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

    <!-- Right: Audit Log -->
    <div class="col-lg-7">

        <!-- Audit Log -->
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-journal-text me-2"></i>Activity Log</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Time</th><th>Actor</th><th>Department</th><th>Action</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($log as $entry): ?>
                    <tr>
                        <td class="text-muted small text-nowrap"><?= date('M d H:i', strtotime($entry['created_at'])) ?></td>
                        <td class="small"><?= htmlspecialchars($entry['actor_name']) ?></td>
                        <td class="small text-muted"><?= ucwords(str_replace('_user','',$entry['dept_role']??'')) ?></td>
                        <td>
                            <span class="badge bg-<?= $sc[$entry['action']] ?? 'secondary' ?>">
                                <?= ucfirst($entry['action']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($entry['remarks']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($log)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No activity yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const docId    = <?= $doc['id'] ?>;
const stepId   = <?= $myStep['id'] ?? 0 ?>;

function doAction(action) {
    const remarks = document.getElementById('actionRemarks')?.value?.trim() ?? '';
    if (action === 'rejected' && !remarks) {
        showToast('Please provide remarks when rejecting.', 'warning');
        return;
    }
    if (!confirm(`${action === 'approved' ? 'Approve' : 'Reject'} this document for your department?`)) return;

    const fd = new FormData();
    fd.append('document_id', docId);
    fd.append('step_id',     stepId);
    fd.append('action',      action);
    fd.append('remarks',     remarks);

    fetch(BASE_URL + '/documents/act', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) setTimeout(() => location.reload(), 800);
        });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
