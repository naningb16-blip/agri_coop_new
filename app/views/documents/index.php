<?php
ob_start();
$pageTitle  = 'Document Routing';
$activeMenu = 'documents';
$role       = $_SESSION['user']['role'] ?? '';
$sc = ['routing'=>'warning','approved'=>'success','rejected'=>'danger'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small">Files routed through all departments for approval</span>
    <?php if ($role !== 'bod'): ?>
    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-upload me-1"></i>Upload Document
    </button>
    <?php endif; ?>
</div>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Documents</span>
        <span class="badge bg-secondary"><?= count($docs) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Title</th><th>File</th><th>Uploaded By</th><th>Origin Dept</th><th>Current Step</th><th>Status</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($docs as $d): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($d['title']) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($d['file_name']) ?></td>
                <td class="small"><?= htmlspecialchars($d['uploader_name']) ?></td>
                <td><span class="badge bg-secondary"><?= ucwords(str_replace('_user','',$d['origin_dept'])) ?></span></td>
                <td>
                    <?php if ($d['status'] === 'routing' && $d['current_dept']): ?>
                    <span class="badge bg-warning text-dark"><?= htmlspecialchars($d['current_dept']) ?></span>
                    <?php elseif ($d['status'] === 'approved'): ?>
                    <span class="text-success small"><i class="bi bi-check-all"></i> Complete</span>
                    <?php else: ?>
                    <span class="text-danger small"><i class="bi bi-x-circle"></i> Rejected</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $d['status'] === 'routing' ? 'pending' : $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($d['created_at'])) ?></td>
                <td class="text-nowrap">
                    <a href="<?= BASE_URL ?>/documents/detail?id=<?= $d['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="bi bi-eye"></i></a>
                    <a href="<?= BASE_URL ?>/documents/download?id=<?= $d['id'] ?>" class="btn btn-xs btn-outline-secondary"><i class="bi bi-download"></i></a>
                    <?php if ($role === 'qa_user' && $d['uploaded_by'] == $_SESSION['user_id']): ?>
                    <button class="btn btn-xs btn-outline-danger" onclick="deleteDoc(<?= $d['id'] ?>, '<?= htmlspecialchars($d['title']) ?>')"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($docs)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No documents yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Document for Routing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="Document title">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="What is this document about?"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="document" class="form-control" required
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.txt,.csv">
                        <div class="form-text">Allowed: PDF, Word, Excel, Images, CSV. Max 10MB.</div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Your department's step will be <strong>automatically skipped</strong>.
                            The document will route through all other departments for approval.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="uploadBtn">
                        <i class="bi bi-send me-1"></i>Upload & Start Routing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading…';

    const fd = new FormData(this);
    fetch(BASE_URL + '/documents/upload', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Upload & Start Routing';
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('uploadModal'))?.hide();
                setTimeout(() => location.reload(), 800);
            }
        });
});

function deleteDoc(id, title) {
    if (!confirm('Delete "' + title + '"? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch(BASE_URL + '/documents/delete', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) setTimeout(() => location.reload(), 800);
        });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
