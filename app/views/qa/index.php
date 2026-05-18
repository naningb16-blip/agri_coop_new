<?php
ob_start();
$pageTitle  = 'Quality Assurance';
$activeMenu = 'quality';
$rc = ['passed'=>'success','failed'=>'danger','conditional'=>'warning'];
?>

<?php if (($_SESSION['user']['role'] ?? '') === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all QA data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <?php foreach ([[$summary['total'],'Total','','patch-check'],[$summary['passed'],'Passed','','check-circle'],[$summary['failed'],'Failed','danger','x-circle'],[$summary['conditional'],'Conditional','warning','exclamation-circle']] as [$v,$l,$c,$i]): ?>
    <div class="col-6 col-md-3"><div class="stat-card <?= $c ?>"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value"><?= $v ?></div><div class="stat-label"><?= $l ?></div></div><i class="bi bi-<?= $i ?> stat-icon"></i></div></div></div>
    <?php endforeach; ?>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('QA Analytics')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<div class="table-card mb-3">
    <div class="p-3 d-flex gap-2 align-items-center flex-wrap">
        <?php foreach ([''=>'All','passed'=>'Passed','failed'=>'Failed','conditional'=>'Conditional'] as $v=>$l): ?>
        <a href="<?= BASE_URL ?>/qa?result=<?= $v ?>" class="btn btn-sm <?= $filter===$v ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $l ?></a>
        <?php endforeach; ?>
        <?php if ($_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#newQAModal"><i class="bi bi-plus-circle me-1"></i>New Inspection</button><?php endif; ?>
    </div>
</div>

<div class="table-card">
    <div class="card-header"><span><i class="bi bi-patch-check me-2 text-success"></i>Inspections</span><span class="badge bg-secondary"><?= count($inspections) ?></span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Product</th><th>Ref</th><th>Warehouse</th><th>Sample</th><th>Approved</th><th>Rejected</th><th>Moisture%</th><th>Germination%</th><th>Result</th><th>Inspector</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($inspections as $q): ?>
            <tr>
                <td class="text-muted small"><?= date('M d, Y', strtotime($q['inspection_date'])) ?></td>
                <td><?= htmlspecialchars($q['product_name']) ?></td>
                <td class="small text-muted"><?= ucfirst($q['reference_type']) ?> #<?= $q['reference_id'] ?></td>
                <td class="small"><?= htmlspecialchars($q['warehouse_name'] ?? '&#8369;') ?></td>
                <td><?= number_format($q['sample_qty'],2) ?></td>
                <td class="text-success"><?= number_format($q['approved_qty'],2) ?></td>
                <td class="text-danger"><?= number_format($q['rejected_qty'],2) ?></td>
                <td><?= $q['moisture_pct'] ?>%</td>
                <td><?= $q['germination_pct'] ?>%</td>
                <td><span class="badge bg-<?= $rc[$q['result']] ?>"><?= ucfirst($q['result']) ?></span></td>
                <td class="small"><?= htmlspecialchars($q['inspector_name']) ?></td>
                <td><a href="<?= BASE_URL ?>/qa/detail?id=<?= $q['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($inspections)): ?><tr><td colspan="12" class="text-center text-muted py-4">No inspections yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Inspection Modal -->
<div class="modal fade" id="newQAModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-patch-check me-2"></i>New QA Inspection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/qa/create" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <div class="col-md-4"><label class="form-label">Reference Type</label>
                        <select name="reference_type" class="form-select">
                            <option value="batch">Processing Batch</option>
                            <option value="return">Stock Return</option>
                            <option value="seed">Seed Inspection</option>
                            <option value="delivery">Delivery</option>
                            <option value="purchase_order">Purchase Order</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Reference ID</label><input type="number" name="reference_id" class="form-control" placeholder="Batch/Return/PO ID"></div>
                    <div class="col-md-4"><label class="form-label">Inspection Date</label><input type="date" name="inspection_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" required><option value="">Select&#8369;</option>
                        <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select"><option value="">None</option>
                        <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-3"><label class="form-label">Sample Qty</label><input type="number" name="sample_qty" class="form-control" step="0.01" value="0"></div>
                    <div class="col-md-3"><label class="form-label">Approved Qty</label><input type="number" name="approved_qty" class="form-control" step="0.01" value="0"></div>
                    <div class="col-md-3"><label class="form-label">Rejected Qty</label><input type="number" name="rejected_qty" class="form-control" step="0.01" value="0"></div>
                    <div class="col-md-3"><label class="form-label">Result</label>
                        <select name="result" class="form-select">
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                            <option value="conditional">Conditional</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Moisture %</label><input type="number" name="moisture_pct" class="form-control" step="0.01" value="0"></div>
                    <div class="col-md-4"><label class="form-label">Foreign Matter %</label><input type="number" name="foreign_matter" class="form-control" step="0.01" value="0"></div>
                    <div class="col-md-4"><label class="form-label">Germination %</label><input type="number" name="germination_pct" class="form-control" step="0.01" value="0"></div>
                    <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Inspection</button></div>
            </form>
        </div>
    </div>
</div>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>



