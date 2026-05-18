<?php
ob_start();
$pageTitle  = 'PRS-' . str_pad($prs['id'], 4, '0', STR_PAD_LEFT);
$activeMenu = 'purchasing';
$userRole   = $_SESSION['user']['role'] ?? '';
$isOwner    = ($prs['requested_by'] == $_SESSION['user_id']);
$canEdit    = in_array($userRole, ['admin']);
$statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'skipped' => 'secondary'];
?>

<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>/purchasing?tab=prs" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <?php if ($canEdit && $prs['status'] === 'pending'): ?>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPRSModal">
        <i class="bi bi-pencil me-1"></i>Edit
    </button>
    <button class="btn btn-sm btn-outline-danger" onclick="deletePRS()">
        <i class="bi bi-trash me-1"></i>Delete
    </button>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-file-earmark-text me-2"></i><?= $pageTitle ?></span>
                <span class="badge badge-<?= $prs['status'] ?>"><?= ucfirst($prs['status']) ?></span>
            </div>
            <div class="p-3">
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Requested By</div>
                        <div class="fw-semibold"><?= htmlspecialchars($prs['requester_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Department</div>
                        <div><?= htmlspecialchars($prs['dept_name'] ?? '—') ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Description / Purpose</div>
                        <div><?= nl2br(htmlspecialchars($prs['description'] ?? '—')) ?></div>
                    </div>
                    <?php if ($prs['notes']): ?>
                    <div class="col-12">
                        <div class="text-muted small">Notes</div>
                        <div class="bg-light rounded p-2 small"><?= nl2br(htmlspecialchars($prs['notes'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="col-sm-6">
                        <div class="text-muted small">Submitted</div>
                        <div><?= date('M d, Y H:i', strtotime($prs['created_at'])) ?></div>
                    </div>
                </div>

                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Item</th><th>Qty</th><th>Unit</th><th>Est. Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                    <?php $grandTotal = 0; foreach ($items as $i => $item): $grandTotal += $item['total_price']; ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= number_format($item['quantity'], 2) ?></td>
                        <td><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                        <td>&#8369;<?= number_format($item['estimated_price'], 2) ?></td>
                        <td>&#8369;<?= number_format($item['total_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">Est. Grand Total</td>
                            <td>&#8369;<?= number_format($grandTotal, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if ($approval): ?>
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-diagram-3 me-2"></i>Approval Chain</span>
                <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $approval['id'] ?>" class="btn btn-xs btn-outline-secondary">Full View</a>
            </div>
            <div class="p-3">
                <?php foreach ($steps as $i => $step): ?>
                <?php $isCurrent = ($step['step_order'] == $approval['current_step'] && $approval['status'] === 'pending'); ?>
                <div class="d-flex gap-2 py-2 <?= $isCurrent ? 'border-start border-warning border-3 ps-2' : '' ?>">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                                bg-<?= $statusColors[$step['status']] ?? 'secondary' ?> text-white"
                         style="width:28px;height:28px;font-size:12px">
                        <?php if ($step['status'] === 'approved'): ?><i class="bi bi-check-lg"></i>
                        <?php elseif ($step['status'] === 'rejected'): ?><i class="bi bi-x-lg"></i>
                        <?php else: ?><?= $step['step_order'] ?><?php endif; ?>
                    </div>
                    <div class="flex-fill">
                        <div class="d-flex justify-content-between">
                            <strong class="small"><?= htmlspecialchars($step['label']) ?></strong>
                            <span class="badge bg-<?= $statusColors[$step['status']] ?? 'secondary' ?> small"><?= ucfirst($step['status']) ?></span>
                        </div>
                        <?php if ($step['actor_name']): ?>
                        <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($step['actor_name']) ?> &bull; <?= date('M d H:i', strtotime($step['actioned_at'])) ?></div>
                        <?php endif; ?>
                        <?php if ($step['remarks']): ?>
                        <div class="bg-light rounded p-1 mt-1 small"><?= htmlspecialchars($step['remarks']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($i < count($steps) - 1): ?><hr class="my-1"><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-journal-text me-2"></i>Audit Log</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($audit as $log): ?>
                    <tr>
                        <td class="text-muted small text-nowrap"><?= date('M d H:i', strtotime($log['created_at'])) ?></td>
                        <td class="small"><?= htmlspecialchars($log['actor_name']) ?></td>
                        <td><span class="badge bg-<?= $statusColors[$log['action']] ?? 'secondary' ?>"><?= ucfirst($log['action']) ?></span></td>
                        <td class="text-muted small"><?= htmlspecialchars($log['remarks']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit PRS Modal -->
<?php if ($canEdit && $prs['status'] === 'pending'): ?>
<div class="modal fade" id="editPRSModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label">Description / Purpose</label>
                        <input type="text" id="editPrsDesc" class="form-control" value="<?= htmlspecialchars($prs['description'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea id="editPrsNotes" class="form-control" rows="2"><?= htmlspecialchars($prs['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr><th>Item Name</th><th style="width:90px">Qty</th><th style="width:80px">Unit</th><th style="width:130px">Est. Price</th><th style="width:130px">Total</th><th style="width:40px"></th></tr>
                    </thead>
                    <tbody id="editPrsItems">
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><input type="text" class="form-control form-control-sm" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>"></td>
                        <td><input type="number" class="form-control form-control-sm qty" step="0.01" value="<?= $item['quantity'] ?>" oninput="calcRow(this,'editPrsItems')"></td>
                        <td><input type="text" class="form-control form-control-sm" name="unit" value="<?= htmlspecialchars($item['unit'] ?? '') ?>"></td>
                        <td><input type="number" class="form-control form-control-sm price" step="0.01" value="<?= $item['estimated_price'] ?>" oninput="calcRow(this,'editPrsItems')"></td>
                        <td><input type="number" class="form-control form-control-sm total" step="0.01" value="<?= $item['total_price'] ?>" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)"><i class="bi bi-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItemRow('editPrsItems')">
                    <i class="bi bi-plus me-1"></i>Add Row
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEditPRS()">Save Changes</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const prsId = <?= $prs['id'] ?>;

window.calcRow = function(input, tbodyId) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
    const price = parseFloat(row.querySelector('.price')?.value) || 0;
    const total = row.querySelector('.total');
    if (total) total.value = (qty * price).toFixed(2);
};
window.addItemRow = function(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    const clone = tbody.querySelector('tr').cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelectorAll('.qty,.price').forEach(i => i.setAttribute('oninput', `calcRow(this,'${tbodyId}')`));
    tbody.appendChild(clone);
};
window.removeItemRow = function(btn) {
    const tbody = btn.closest('tbody');
    if (tbody.querySelectorAll('tr').length > 1) btn.closest('tr').remove();
};

function collectItems(tbodyId) {
    const items = [];
    document.querySelectorAll(`#${tbodyId} tr`).forEach(row => {
        const name  = row.querySelector('[name=item_name]')?.value?.trim();
        const qty   = row.querySelector('.qty')?.value;
        const unit  = row.querySelector('[name=unit]')?.value?.trim();
        const price = row.querySelector('.price')?.value || '0';
        const total = row.querySelector('.total')?.value || '0';
        if (name && qty) items.push({ item_name: name, quantity: qty, unit, estimated_price: price, total_price: total });
    });
    return items;
}

function saveEditPRS() {
    const items = collectItems('editPrsItems');
    if (!items.length) { alert('Add at least one item.'); return; }
    const fd = new FormData();
    fd.append('id', prsId);
    fd.append('description', document.getElementById('editPrsDesc').value);
    fd.append('notes', document.getElementById('editPrsNotes').value);
    items.forEach((item, i) => Object.entries(item).forEach(([k, v]) => fd.append(`items[${i}][${k}]`, v)));
    fetch(BASE_URL + '/purchasing/edit-prs', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) setTimeout(() => location.reload(), 800);
        });
}

function deletePRS() {
    if (!confirm('Delete this requisition?')) return;
    const fd = new FormData(); fd.append('id', prsId);
    fetch(BASE_URL + '/purchasing/delete-prs', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) setTimeout(() => window.location.href = BASE_URL + '/purchasing?tab=prs', 800);
        });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

