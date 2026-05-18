<?php
ob_start();
$pageTitle  = 'QA Inspection #' . $inspection['id'];
$activeMenu = 'quality';
$rc = ['passed'=>'success','failed'=>'danger','conditional'=>'warning'];
?>
<div class="mb-3"><a href="<?= BASE_URL ?>/qa" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a></div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-card">
            <div class="card-header">
                <span><i class="bi bi-patch-check me-2"></i>Inspection #<?= $inspection['id'] ?></span>
                <span class="badge bg-<?= $rc[$inspection['result']] ?>"><?= ucfirst($inspection['result']) ?></span>
            </div>
            <div class="p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted w-40">Product</th><td><?= htmlspecialchars($inspection['product_name']) ?> (<?= htmlspecialchars($inspection['unit']) ?>)</td></tr>
                    <tr><th class="text-muted">Reference</th><td><?= ucfirst($inspection['reference_type']) ?> #<?= $inspection['reference_id'] ?></td></tr>
                    <tr><th class="text-muted">Warehouse</th><td><?= htmlspecialchars($inspection['warehouse_name'] ?? '&#8369;') ?></td></tr>
                    <tr><th class="text-muted">Date</th><td><?= date('M d, Y', strtotime($inspection['inspection_date'])) ?></td></tr>
                    <tr><th class="text-muted">Inspector</th><td><?= htmlspecialchars($inspection['inspector_name']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-bar-chart me-2"></i>Measurements</span></div>
            <div class="p-3">
                <div class="row g-3 text-center">
                    <?php foreach ([
                        ['Sample Qty',    $inspection['sample_qty'],      'secondary'],
                        ['Approved Qty',  $inspection['approved_qty'],    'success'],
                        ['Rejected Qty',  $inspection['rejected_qty'],    'danger'],
                        ['Moisture %',    $inspection['moisture_pct'].'%','info'],
                        ['Foreign Matter',$inspection['foreign_matter'].'%','warning'],
                        ['Germination %', $inspection['germination_pct'].'%','primary'],
                    ] as [$label,$val,$color]): ?>
                    <div class="col-4">
                        <div class="p-2 bg-<?= $color ?> bg-opacity-10 rounded">
                            <div class="fw-bold text-<?= $color ?>"><?= $val ?></div>
                            <div class="small text-muted"><?= $label ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($inspection['remarks']): ?>
                <div class="mt-3"><div class="text-muted small">Remarks</div><div class="bg-light rounded p-2"><?= nl2br(htmlspecialchars($inspection['remarks'])) ?></div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
