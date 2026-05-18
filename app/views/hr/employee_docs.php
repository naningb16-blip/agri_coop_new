<?php
ob_start();
$pageTitle  = 'Documents &#8369; ' . htmlspecialchars($employee['full_name']);
$activeMenu = 'hr';
$userRole   = $_SESSION['user']['role'] ?? '';
$isReadOnly = in_array($userRole, ['gm', 'manager']);

$docLabels = [
    'sss'                => ['SSS',                'bi-card-text',        'primary'],
    'pagibig'            => ['Pag-IBIG',            'bi-house-heart',      'success'],
    'philhealth'         => ['PhilHealth',          'bi-heart-pulse',      'danger'],
    'application_letter' => ['Application Letter',  'bi-envelope-paper',   'warning'],
    'resume'             => ['Resume / CV',          'bi-file-person',      'info'],
    'contract'           => ['Contract',             'bi-file-earmark-text','secondary'],
    'other'              => ['Other',                'bi-paperclip',        'dark'],
];

// Group docs by type
$grouped = [];
foreach ($docs as $d) $grouped[$d['doc_type']][] = $d;
?>

<div class="mb-3 d-flex align-items-center gap-2">
    <a href="<?= BASE_URL ?>/hr" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to HR
    </a>
    <h5 class="mb-0 ms-2">
        <i class="bi bi-folder2-open me-2 text-warning"></i>
        <?= htmlspecialchars($employee['full_name']) ?>'s Documents
    </h5>
    <span class="badge bg-secondary ms-1"><?= htmlspecialchars($employee['employee_code']) ?></span>
</div>

<div class="row g-3">
    <?php foreach ($docLabels as $type => [$label, $icon, $color]): ?>
    <div class="col-md-6 col-lg-4">
        <div class="table-card h-100">
            <div class="card-header">
                <span><i class="bi <?= $icon ?> me-2 text-<?= $color ?>"></i><?= $label ?></span>
                <?php if (!$isReadOnly): ?>
                <button class="btn btn-xs btn-outline-success" onclick="openUpload('<?= $type ?>')">
                    <i class="bi bi-upload"></i> Upload
                </button>
                <?php endif; ?>
            </div>
            <div class="p-2">
                <?php if (empty($grouped[$type])): ?>
                <p class="text-muted small text-center py-2 mb-0">No files uploaded.</p>
                <?php else: ?>
                <?php foreach ($grouped[$type] as $doc): ?>
                <div class="d-flex align-items-center gap-2 p-2 border-bottom">
                    <i class="bi bi-file-earmark text-<?= $color ?>"></i>
                    <div class="flex-fill overflow-hidden">
                        <div class="small fw-semibold text-truncate"><?= htmlspecialchars($doc['file_name']) ?></div>
                        <div class="text-muted" style="font-size:10px">
                            <?= round($doc['file_size']/1024, 1) ?>KB &bull;
                            <?= date('M d, Y', strtotime($doc['created_at'])) ?> &bull;
                            <?= htmlspecialchars($doc['uploaded_by_name'] ?? '&#8369;') ?>
                        </div>
                        <?php if ($doc['notes']): ?>
                        <div class="text-muted" style="font-size:10px"><?= htmlspecialchars($doc['notes']) ?></div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>/hr/download-doc?id=<?= $doc['id'] ?>" class="btn btn-xs btn-outline-primary" title="Download">
                        <i class="bi bi-download"></i>
                    </a>
                    <?php if (!$isReadOnly): ?>
                    <button class="btn btn-xs btn-outline-danger" onclick="deleteDoc(<?= $doc['id'] ?>, '<?= htmlspecialchars($doc['file_name']) ?>')" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                <input type="hidden" name="doc_type" id="uploadDocType">
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Document Type</label>
                        <input type="text" id="uploadDocLabel" class="form-control" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="document" class="form-control" required
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                        <div class="form-text">PDF, Word, Image, or Text. Max 10MB.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes (optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. SSS No. 12-3456789-0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="uploadBtn">
                        <i class="bi bi-upload me-1"></i>Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const DOC_LABELS = <?= json_encode(array_map(fn($v) => $v[0], $docLabels)) ?>;

function openUpload(type) {
    document.getElementById('uploadDocType').value  = type;
    document.getElementById('uploadDocLabel').value = DOC_LABELS[type] || type;
    new bootstrap.Modal(document.getElementById('uploadModal')).show();
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading...';

    fetch(BASE_URL + '/hr/upload-doc', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('uploadModal'))?.hide();
                setTimeout(() => location.reload(), 600);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-upload me-1"></i>Upload';
            }
        });
});

function deleteDoc(id, name) {
    if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
    const fd = new FormData(); fd.append('id', id);
    fetch(BASE_URL + '/hr/delete-doc', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) location.reload();
        });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
