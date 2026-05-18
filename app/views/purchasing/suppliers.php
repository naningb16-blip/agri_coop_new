<?php
ob_start();
$pageTitle  = 'Suppliers';
$activeMenu = 'purchasing';
$userRole   = $_SESSION['user']['role'] ?? '';
$isReadOnly = in_array($userRole, ['gm', 'manager']);
$canDelete  = in_array($userRole, ['admin']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/purchasing" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Purchasing
    </a>
    <?php if (!$isReadOnly): ?>
    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#suppModal">
        <i class="bi bi-plus-circle me-1"></i>Add Supplier
    </button>
    <?php endif; ?>
</div>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-building me-2 text-success"></i>Suppliers</span>
        <span class="badge bg-secondary"><?= count($suppliers) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Orders</th><th>Total Value</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
            <tr>
                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                <td><?= htmlspecialchars($s['contact_person'] ?? '&#8369;') ?></td>
                <td><?= htmlspecialchars($s['phone'] ?? '&#8369;') ?></td>
                <td><?= htmlspecialchars($s['email'] ?? '&#8369;') ?></td>
                <td class="text-center"><?= $s['total_orders'] ?></td>
                <td>&#8369;<?= number_format($s['total_value'], 2) ?></td>
                <td>
                    <span class="badge badge-<?= $s['status'] === 'active' ? 'approved' : 'rejected' ?>">
                        <?= ucfirst($s['status']) ?>
                    </span>
                </td>
                <td class="text-nowrap">
                    <?php if (!$isReadOnly): ?>
                    <button class="btn btn-xs btn-outline-secondary" onclick="editSupplier(<?= htmlspecialchars(json_encode($s)) ?>)" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-<?= $s['status'] === 'active' ? 'warning' : 'success' ?>"
                            onclick="toggleStatus(<?= $s['id'] ?>, '<?= $s['status'] === 'active' ? 'inactive' : 'active' ?>')"
                            title="<?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                        <i class="bi bi-<?= $s['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                    </button>
                    <?php if ($canDelete): ?>
                    <button class="btn btn-xs btn-outline-danger" onclick="deleteSupplier(<?= $s['id'] ?>)" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($suppliers)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No suppliers yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Supplier Modal -->
<div class="modal fade" id="suppModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="suppModalTitle">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form data-ajax data-reload="true" id="suppForm" action="<?= BASE_URL ?>/purchasing/suppliers" method="POST">
                <input type="hidden" name="id" id="suppId" value="0">
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="suppName" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="suppContact" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="suppPhone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="suppEmail" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" id="suppStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="suppAddress" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

function editSupplier(s) {
    document.getElementById('suppModalTitle').textContent = 'Edit Supplier';
    document.getElementById('suppId').value      = s.id;
    document.getElementById('suppName').value    = s.name;
    document.getElementById('suppContact').value = s.contact_person || '';
    document.getElementById('suppPhone').value   = s.phone || '';
    document.getElementById('suppEmail').value   = s.email || '';
    document.getElementById('suppAddress').value = s.address || '';
    document.getElementById('suppStatus').value  = s.status || 'active';
    new bootstrap.Modal(document.getElementById('suppModal')).show();
}

function toggleStatus(id, newStatus) {
    if (!confirm(`Set supplier to ${newStatus}?`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fd.append('status', newStatus);
    fetch(BASE_URL + '/purchasing/toggle-supplier', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}

function deleteSupplier(id) {
    if (!confirm('Delete this supplier? This cannot be undone.')) return;
    const fd = new FormData(); fd.append('id', id);
    fetch(BASE_URL + '/purchasing/delete-supplier', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>


