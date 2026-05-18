<?php
ob_start();
$pageTitle  = 'Batch: ' . $batch['batch_number'];
$activeMenu = 'operational';
$userRole   = $_SESSION['user']['role'] ?? '';
$canManage  = in_array($userRole, ['admin', 'manager']);  // GM removed from stage management
$canCancel  = in_array($userRole, ['admin', 'manager', 'gm']);  // GM can still cancel
$stageColors  = ['drying'=>'warning','sorting'=>'info','shelling'=>'primary','bagging'=>'success','milling'=>'secondary'];
$statusColors = ['pending'=>'warning','in_progress'=>'info','completed'=>'success','cancelled'=>'secondary','skipped'=>'secondary'];
$sc = array_merge($statusColors, ['approved'=>'success','rejected'=>'danger']);

// Efficiency calc
$efficiency = ($batch['input_quantity'] > 0 && $batch['output_quantity'] > 0)
    ? round(($batch['output_quantity'] / $batch['input_quantity']) * 100, 1) : null;
?>

<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>/operational?tab=processing" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <a href="<?= BASE_URL ?>/operational/processing-print?id=<?= $batch['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer me-1"></i>Print</a>
    <?php if ($canCancel && in_array($batch['status'], ['pending','in_progress'])): ?>
    <button class="btn btn-sm btn-outline-danger" onclick="cancelBatch()"><i class="bi bi-x-circle me-1"></i>Cancel Batch</button>
    <?php endif; ?>
</div>

<div class="row g-3">
    <!-- Left: Batch Info + Stage Pipeline -->
    <div class="col-lg-7">
        <!-- Info -->
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-gear-wide-connected me-2"></i><?= htmlspecialchars($batch['batch_number']) ?></span>
                <span class="badge badge-<?= $batch['status'] === 'in_progress' ? 'pending' : $batch['status'] ?>">
                    <?= ucwords(str_replace('_',' ',$batch['status'])) ?>
                </span>
            </div>
            <div class="p-3">
                <?php if (!empty($batch['batch_number'])): ?>
                <div class="alert alert-info mb-3 py-2">
                    <strong>Document Number:</strong> <?= htmlspecialchars($batch['batch_number']) ?>
                </div>
                <?php endif; ?>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="text-muted small">Product</div>
                        <div class="fw-semibold"><?= htmlspecialchars($batch['product_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Assigned To</div>
                        <div><?= htmlspecialchars($batch['assigned_name'] ?? '&#8369;') ?></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Input Qty</div>
                        <div class="fw-semibold"><?= number_format($batch['input_quantity'], 2) ?> <?= htmlspecialchars($batch['unit']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($batch['input_warehouse_name'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Output Qty</div>
                        <div class="fw-semibold <?= $batch['output_quantity'] ? 'text-success' : 'text-muted' ?>">
                            <?= $batch['output_quantity'] ? number_format($batch['output_quantity'], 2) . ' ' . htmlspecialchars($batch['unit']) : '&#8369;' ?>
                        </div>
                        <div class="text-muted small"><?= htmlspecialchars($batch['output_warehouse_name'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Waste</div>
                        <div class="<?= $batch['waste_quantity'] ? 'text-danger' : 'text-muted' ?>">
                            <?= $batch['waste_quantity'] ? number_format($batch['waste_quantity'], 2) . ' ' . htmlspecialchars($batch['unit']) : '&#8369;' ?>
                        </div>
                    </div>
                    <?php if ($efficiency !== null): ?>
                    <div class="col-12">
                        <div class="text-muted small mb-1">Processing Efficiency</div>
                        <div class="progress" style="height:18px">
                            <div class="progress-bar bg-<?= $efficiency >= 80 ? 'success' : ($efficiency >= 60 ? 'warning' : 'danger') ?>"
                                 style="width:<?= $efficiency ?>%"><?= $efficiency ?>%</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-sm-6">
                        <div class="text-muted small">Start Date</div>
                        <div><?= $batch['start_date'] ? date('M d, Y', strtotime($batch['start_date'])) : '&#8369;' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">End Date</div>
                        <div><?= $batch['end_date'] ? date('M d, Y', strtotime($batch['end_date'])) : '&#8369;' ?></div>
                    </div>
                    <?php if ($batch['notes']): ?>
                    <div class="col-12">
                        <div class="text-muted small">Notes</div>
                        <div class="bg-light rounded p-2 small"><?= nl2br(htmlspecialchars($batch['notes'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stage Pipeline -->
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-diagram-3 me-2"></i>Processing Stages</span></div>
            <?php if ($approval && $approval['status'] === 'pending'): ?>
            <div class="alert alert-warning m-3 mb-0">
                <i class="bi bi-hourglass-split me-2"></i>
                <strong>Awaiting Approval:</strong> This batch must be approved before processing can begin.
            </div>
            <?php endif; ?>
            <div class="p-3">
                <?php foreach ($stages as $i => $stage): ?>
                <?php $isActive = $stage['status'] === 'in_progress'; ?>
                <div class="border rounded p-3 mb-2 <?= $isActive ? 'border-warning' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-<?= $stageColors[$stage['stage']] ?? 'secondary' ?> fs-6"><?= $stage['stage_order'] ?></span>
                            <strong><?= ucfirst($stage['stage']) ?></strong>
                        </div>
                        <span class="badge badge-<?= $stage['status'] === 'in_progress' ? 'pending' : $stage['status'] ?>">
                            <?= ucwords(str_replace('_',' ',$stage['status'])) ?>
                        </span>
                    </div>

                    <div class="row g-2 small mb-2">
                        <div class="col-4"><span class="text-muted">Input:</span> <?= number_format($stage['input_qty'], 2) ?></div>
                        <div class="col-4"><span class="text-muted">Output:</span> <?= $stage['output_qty'] ? number_format($stage['output_qty'], 2) : '&#8369;' ?></div>
                        <div class="col-4"><span class="text-muted">Waste:</span> <?= $stage['waste_qty'] ? number_format($stage['waste_qty'], 2) : '&#8369;' ?></div>
                        <?php if ($stage['started_at']): ?>
                        <div class="col-6"><span class="text-muted">Started:</span> <?= date('M d H:i', strtotime($stage['started_at'])) ?></div>
                        <?php endif; ?>
                        <?php if ($stage['completed_at']): ?>
                        <div class="col-6"><span class="text-muted">Completed:</span> <?= date('M d H:i', strtotime($stage['completed_at'])) ?></div>
                        <?php endif; ?>
                        <?php if ($stage['recorded_by_name']): ?>
                        <div class="col-12"><span class="text-muted">By:</span> <?= htmlspecialchars($stage['recorded_by_name']) ?></div>
                        <?php endif; ?>
                        <?php if ($stage['notes']): ?>
                        <div class="col-12 bg-light rounded p-1"><?= nl2br(htmlspecialchars($stage['notes'])) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Stage Actions -->
                    <?php 
                    $isApproved = !$approval || $approval['status'] === 'approved';
                    $canActOnStage = $canManage && $isApproved && $batch['status'] !== 'cancelled' && $batch['status'] !== 'completed';
                    ?>
                    <?php if ($canActOnStage): ?>
                    <?php if ($stage['status'] === 'pending' && ($i === 0 || $stages[$i-1]['status'] === 'completed')): ?>
                    <button class="btn btn-sm btn-info text-white" onclick="startStage(<?= $stage['id'] ?>)">
                        <i class="bi bi-play-fill me-1"></i>Start Stage
                    </button>
                    <?php elseif ($stage['status'] === 'in_progress'): ?>
                    <button class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#completeForm<?= $stage['id'] ?>">
                        <i class="bi bi-check-lg me-1"></i>Complete Stage
                    </button>
                    <div class="collapse mt-2" id="completeForm<?= $stage['id'] ?>">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Output Qty <span class="text-danger">*</span></label>
                                <input type="number" id="outQty<?= $stage['id'] ?>" class="form-control form-control-sm" step="0.01" max="<?= $stage['input_qty'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Waste Qty</label>
                                <input type="number" id="wasteQty<?= $stage['id'] ?>" class="form-control form-control-sm" step="0.01" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Notes</label>
                                <input type="text" id="stageNotes<?= $stage['id'] ?>" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-sm btn-success" onclick="completeStage(<?= $stage['id'] ?>)">
                                    <i class="bi bi-check-lg me-1"></i>Confirm Complete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php elseif (!$isApproved && $stage['status'] === 'pending' && $i === 0): ?>
                    <div class="text-muted small"><i class="bi bi-lock me-1"></i>Awaiting GM approval to start</div>
                    <?php endif; ?>
                </div>
                <?php if ($i < count($stages) - 1): ?>
                <div class="text-center text-muted mb-2"><i class="bi bi-arrow-down fs-5"></i></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Approval + Audit -->
    <div class="col-lg-5">
        <?php if ($approval): ?>
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-diagram-3 me-2"></i>Approval Chain</span>
                <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $approval['id'] ?>" class="btn btn-xs btn-outline-secondary">Full View</a>
            </div>
            <div class="p-3">
                <?php foreach ($approvalSteps as $i => $step): ?>
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
                <?php if ($i < count($approvalSteps) - 1): ?><hr class="my-1"><?php endif; ?>
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
        <?php endif; ?>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const batchId  = <?= $batch['id'] ?>;

function startStage(stageId) {
    if (!confirm('Start this stage?')) return;
    const fd = new FormData();
    fd.append('stage_id', stageId); fd.append('action', 'start');
    fetch(BASE_URL + '/operational/update-stage', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}

function completeStage(stageId) {
    const outQty   = document.getElementById('outQty'    + stageId)?.value;
    const wasteQty = document.getElementById('wasteQty'  + stageId)?.value || 0;
    const notes    = document.getElementById('stageNotes'+ stageId)?.value || '';
    if (!outQty || parseFloat(outQty) <= 0) { showToast('Output quantity is required.', 'warning'); return; }
    const fd = new FormData();
    fd.append('stage_id',   stageId);
    fd.append('action',     'complete');
    fd.append('output_qty', outQty);
    fd.append('waste_qty',  wasteQty);
    fd.append('notes',      notes);
    fetch(BASE_URL + '/operational/update-stage', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}

function cancelBatch() {
    if (!confirm('Cancel this batch? Input stock will be reversed.')) return;
    const fd = new FormData(); fd.append('id', batchId);
    fetch(BASE_URL + '/operational/cancel-processing', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) setTimeout(() => location.reload(), 800); });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
