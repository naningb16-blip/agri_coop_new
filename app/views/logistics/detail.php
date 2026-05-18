<?php
ob_start();
$pageTitle  = 'Delivery #' . $delivery['id'];
$activeMenu = 'logistics';
$userRole   = $_SESSION['user']['role'] ?? '';
$canManage  = in_array($userRole, ['admin', 'manager', 'gm']);
$sc = ['pending'=>'warning','in_transit'=>'info','delivered'=>'success','failed'=>'danger',
       'approved'=>'success','rejected'=>'danger','skipped'=>'secondary'];
?>

<div class="mb-3 d-flex gap-2 align-items-center">
    <a href="<?= BASE_URL ?>/logistics" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <?php if ($receipt): ?>
    <a href="<?= BASE_URL ?>/logistics/receipt-print?id=<?= $receipt['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-printer me-1"></i>Print Receipt
    </a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <!-- Left: Delivery Info + Items + Actions -->
    <div class="col-lg-7">
        <!-- Info Card -->
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-truck me-2"></i>Delivery #<?= $delivery['id'] ?></span>
                <span class="badge badge-<?= $delivery['status'] === 'in_transit' ? 'pending' : $delivery['status'] ?>">
                    <?= ucwords(str_replace('_', ' ', $delivery['status'])) ?>
                </span>
            </div>
            <div class="p-3">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="text-muted small">Reference</div>
                        <div class="fw-semibold">
                            <?= ucwords(str_replace('_',' ',$delivery['reference_type'])) ?> #<?= $delivery['reference_id'] ?>
                            <?php if ($sourceDoc): ?>
                            &#8369; <?= htmlspecialchars($sourceDoc['label']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($sourceDoc): ?>
                        <div class="text-muted small"><?= htmlspecialchars($sourceDoc['party']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Driver / Vehicle</div>
                        <div><?= htmlspecialchars($delivery['driver_name'] ?: '&#8369;') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($delivery['vehicle_plate'] ?: '') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Origin</div>
                        <div><?= htmlspecialchars($delivery['origin']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Destination</div>
                        <div><?= htmlspecialchars($delivery['destination']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Dispatch Date</div>
                        <div><?= $delivery['dispatch_date'] ? date('M d, Y H:i', strtotime($delivery['dispatch_date'])) : '' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Delivery Date</div>
                        <div><?= $delivery['delivery_date'] ? date('M d, Y H:i', strtotime($delivery['delivery_date'])) : '' ?></div>
                    </div>
                    <?php if ($delivery['notes']): ?>
                    <div class="col-12">
                        <div class="text-muted small">Notes</div>
                        <div class="bg-light rounded p-2 small"><?= nl2br(htmlspecialchars($delivery['notes'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="table-card mb-3">
            <div class="card-header"><span><i class="bi bi-box-seam me-2"></i>Items</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= number_format($item['quantity'], 2) ?></td>
                        <td><?= htmlspecialchars($item['unit'] ?? $item['unit'] ?? '') ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($item['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No items recorded.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Status Actions -->
        <?php if ($canManage): ?>
        <div class="table-card mb-3">
            <div class="card-header"><span><i class="bi bi-sliders me-2"></i>Actions</span></div>
            <div class="p-3 d-flex flex-wrap gap-2">
                <?php if ($delivery['status'] === 'pending'): ?>
                <button class="btn btn-info text-white" onclick="setStatus('in_transit')"><i class="bi bi-truck me-1"></i>Dispatch</button>
                <?php endif; ?>
                <?php if ($delivery['status'] === 'in_transit'): ?>
                <button class="btn btn-success" onclick="setStatus('delivered')"><i class="bi bi-check-lg me-1"></i>Mark Delivered</button>
                <button class="btn btn-danger"  onclick="setStatus('failed')"><i class="bi bi-x-lg me-1"></i>Mark Failed</button>
                <?php endif; ?>
                <?php if ($delivery['status'] === 'delivered' && !$receipt): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#receiptModal">
                    <i class="bi bi-file-earmark-check me-1"></i>Generate Receipt
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Delivery Receipt Summary -->
        <?php if ($receipt): ?>
        <div class="table-card">
            <div class="card-header">
                <span><i class="bi bi-file-earmark-check me-2 text-success"></i>Delivery Receipt</span>
                <a href="<?= BASE_URL ?>/logistics/receipt-print?id=<?= $receipt['id'] ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                    <i class="bi bi-printer me-1"></i>Print
                </a>
            </div>
            <div class="p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width:40%">DR Number</th><td><strong><?= htmlspecialchars($receipt['dr_number']) ?></strong></td></tr>
                    <tr><th class="text-muted">Received By</th><td><?= htmlspecialchars($receipt['received_by_name']) ?></td></tr>
                    <tr><th class="text-muted">Received At</th><td><?= date('M d, Y H:i', strtotime($receipt['received_at'])) ?></td></tr>
                    <tr><th class="text-muted">Signed By</th><td><?= htmlspecialchars($receipt['signature_name'] ?: '&#8369;') ?></td></tr>
                    <?php if ($receipt['condition_notes']): ?>
                    <tr><th class="text-muted">Condition</th><td><?= nl2br(htmlspecialchars($receipt['condition_notes'])) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Approval Chain + Audit -->
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
                                bg-<?= $sc[$step['status']] ?? 'secondary' ?> text-white"
                         style="width:28px;height:28px;font-size:12px">
                        <?php if ($step['status'] === 'approved'): ?><i class="bi bi-check-lg"></i>
                        <?php elseif ($step['status'] === 'rejected'): ?><i class="bi bi-x-lg"></i>
                        <?php else: ?><?= $step['step_order'] ?><?php endif; ?>
                    </div>
                    <div class="flex-fill">
                        <div class="d-flex justify-content-between">
                            <strong class="small"><?= htmlspecialchars($step['label']) ?></strong>
                            <span class="badge bg-<?= $sc[$step['status']] ?? 'secondary' ?> small"><?= ucfirst($step['status']) ?></span>
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
                        <td><span class="badge bg-<?= $sc[$log['action']] ?? 'secondary' ?>"><?= ucfirst($log['action']) ?></span></td>
                        <td class="text-muted small"><?= htmlspecialchars($log['remarks']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="table-card">
            <div class="p-4 text-center text-muted small">No approval workflow for this delivery.</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Generate Receipt Modal -->
<?php if ($canManage && $delivery['status'] === 'delivered' && !$receipt): ?>
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-check me-2"></i>Generate Delivery Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Received / Signed By</label>
                    <input type="text" id="sigName" class="form-control" placeholder="Full name of receiver">
                </div>
                <div class="mb-3">
                    <label class="form-label">Condition Notes</label>
                    <textarea id="condNotes" class="form-control" rows="3" placeholder="Any damage, shortage, or remarks&#8369;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateReceipt()"><i class="bi bi-file-earmark-check me-1"></i>Generate</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const BASE_URL   = '<?= BASE_URL ?>';
const deliveryId = <?= $delivery['id'] ?>;

function setStatus(status) {
    const labels = { in_transit: 'dispatch', delivered: 'mark as delivered', failed: 'mark as failed' };
    if (!confirm(`${labels[status] || status} this delivery?`)) return;
    const fd = new FormData();
    fd.append('id', deliveryId); fd.append('status', status);
    fetch(BASE_URL + '/logistics/status', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) setTimeout(() => location.reload(), 800); });
}

function generateReceipt() {
    const fd = new FormData();
    fd.append('delivery_id',    deliveryId);
    fd.append('signature_name', document.getElementById('sigName').value);
    fd.append('condition_notes',document.getElementById('condNotes').value);
    fetch(BASE_URL + '/logistics/generate-receipt', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) setTimeout(() => location.reload(), 800); });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
