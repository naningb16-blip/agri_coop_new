<?php
ob_start();
$pageTitle  = 'Production Record #' . $record['id'];
$activeMenu = 'operational';
$sc = ['planned'=>'secondary','planted'=>'info','growing'=>'primary','harvested'=>'warning','completed'=>'success'];
$ic = ['fertilizer'=>'success','pesticide'=>'danger','seed'=>'primary','labor'=>'warning','other'=>'secondary'];
$ssc = ['pending'=>'warning','in_progress'=>'info','completed'=>'success','cancelled'=>'secondary'];
?>
<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>/operational?tab=production" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <a href="<?= BASE_URL ?>/operational/production-print?id=<?= $record['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer me-1"></i>Print</a>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <!-- Record Info -->
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-flower1 me-2"></i>Production #<?= $record['id'] ?></span>
                <span class="badge bg-<?= $sc[$record['status']] ?? 'secondary' ?>"><?= ucfirst($record['status']) ?></span>
            </div>
            <div class="p-3">
                <?php if (!empty($record['production_number'])): ?>
                <div class="alert alert-info mb-3 py-2">
                    <strong>Document Number:</strong> <?= htmlspecialchars($record['production_number']) ?>
                </div>
                <?php endif; ?>
                <div class="row g-2">
                    <div class="col-sm-6"><div class="text-muted small">Farmer / Seed Grower</div><div class="fw-semibold"><?= htmlspecialchars($record['farmer_name']) ?></div><div class="text-muted small"><?= htmlspecialchars($record['farmer_phone'] ?? '') ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Land Owner</div><div><?= htmlspecialchars($record['land_owner'] ?? '—') ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Location</div><div><?= htmlspecialchars($record['farm_location'] ?? '—') ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Season</div><div><?= htmlspecialchars($record['season'] ?? '—') ?></div></div>
                    <div class="col-sm-3"><div class="text-muted small">Area (ha)</div><div><?= number_format($record['planted_area_ha'],2) ?></div></div>
                    <div class="col-sm-3"><div class="text-muted small">Variety</div><div><?= htmlspecialchars($record['variety'] ?? '—') ?></div></div>
                    <div class="col-sm-3"><div class="text-muted small">Seed Kgs</div><div><?= number_format($record['no_of_seed_kgs'] ?? 0, 2) ?></div></div>
                    <div class="col-sm-3"><div class="text-muted small">Product</div><div><?= htmlspecialchars($record['product_name']) ?></div></div>
                    <div class="col-sm-4"><div class="text-muted small">Date Planted</div><div><?= $record['planting_date'] ? date('M d, Y', strtotime($record['planting_date'])) : '—' ?></div></div>
                    <div class="col-sm-4"><div class="text-muted small">Expected Harvest</div><div><?= $record['expected_harvest'] ? date('M d, Y', strtotime($record['expected_harvest'])) : '—' ?></div></div>
                    <div class="col-sm-4"><div class="text-muted small">Date Harvested</div><div><?= $record['actual_harvest'] ? date('M d, Y', strtotime($record['actual_harvest'])) : '—' ?></div></div>
                    <div class="col-12"><div class="text-muted small">Fertilizer Used</div><div><?= htmlspecialchars($record['fertilizer_used'] ?? '—') ?></div></div>
                    <div class="col-sm-4"><div class="text-muted small">Expected Yield</div><div><?= number_format($record['expected_yield'],2) ?></div></div>
                    <div class="col-sm-4"><div class="text-muted small">Actual Yield</div><div class="<?= $record['actual_yield'] ? 'text-success fw-bold' : 'text-muted' ?>"><?= $record['actual_yield'] ? number_format($record['actual_yield'],2) : '—' ?></div></div>
                    <div class="col-sm-4"><div class="text-muted small">Total Input Cost</div><div>—</div></div>
                    <div class="col-sm-6"><div class="text-muted small">Corn Milling (kgs)</div><div><?= number_format($record['milling_kgs'] ?? 0, 2) ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Bagging (bags)</div><div><?= number_format($record['bagging_bags'] ?? 0, 2) ?></div></div>
                </div>
                <!-- Status Actions -->
                <?php if (!in_array($record['status'],['completed','harvested'])): ?>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <?php $next = ['planned'=>'planted','planted'=>'growing','growing'=>'harvested']; ?>
                    <?php if (isset($next[$record['status']])): ?>
                    <?php if ($next[$record['status']] === 'planted'): ?>
                    <button class="btn btn-sm btn-info text-white" data-bs-toggle="collapse" data-bs-target="#plantingForm">Mark as Planted</button>
                    <div class="collapse w-100 mt-2" id="plantingForm">
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label small">Seed Product</label>
                                <select id="seedProduct" class="form-select form-select-sm">
                                    <option value="">Select seed...</option>
                                    <?php 
                                    $products = (new InventoryModel())->getProducts();
                                    foreach ($products as $p): 
                                    ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['unit']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label small">Seed Quantity</label><input type="number" id="seedQuantity" class="form-control form-control-sm" step="0.01" placeholder="0.00"></div>
                            <div class="col-md-3"><label class="form-label small">From Warehouse</label>
                                <select id="plantWh" class="form-select form-select-sm"><option value="">None</option>
                                <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12"><button class="btn btn-sm btn-info text-white" onclick="updateStatus('planted')">Confirm Planting</button></div>
                        </div>
                    </div>
                    <?php elseif ($next[$record['status']] === 'harvested'): ?>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="collapse" data-bs-target="#harvestForm">Mark Harvested</button>
                    <div class="collapse w-100 mt-2" id="harvestForm">
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label small">Actual Yield</label><input type="number" id="actualYield" class="form-control form-control-sm" step="0.01"></div>
                            <div class="col-md-4"><label class="form-label small">Harvest Date</label><input type="date" id="actualHarvest" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Stock to Warehouse</label>
                                <select id="harvestWh" class="form-select form-select-sm"><option value="">None</option>
                                <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-12"><button class="btn btn-sm btn-warning" onclick="updateStatus('harvested')">Confirm Harvest</button></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <button class="btn btn-sm btn-info text-white" onclick="updateStatus('<?= $next[$record['status']] ?>')">Move to <?= ucfirst($next[$record['status']]) ?></button>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($record['status'] === 'harvested'): ?>
                    <button class="btn btn-sm btn-success" onclick="updateStatus('completed')">Mark Completed</button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Inputs (fertilizers, etc.) -->
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-droplet me-2"></i>Production Inputs</span>
                <?php if (($_SESSION['user']['role'] ?? '') !== 'gm'): ?>
                <button class="btn btn-xs btn-outline-success" data-bs-toggle="collapse" data-bs-target="#addInputForm"><i class="bi bi-plus"></i></button>
                <?php endif; ?>
            </div>
            <div class="collapse p-3 border-bottom" id="addInputForm">
                <form action="<?= BASE_URL ?>/operational/add-input" method="POST" data-ajax data-reload="true">
                    <input type="hidden" name="production_record_id" value="<?= $record['id'] ?>">
                    <div class="row g-2">
                        <div class="col-md-3"><select name="input_type" class="form-select form-select-sm"><option value="fertilizer">Fertilizer</option><option value="pesticide">Pesticide</option><option value="seed">Seed</option><option value="labor">Labor</option><option value="other">Other</option></select></div>
                        <div class="col-md-3"><input type="text" name="name" class="form-control form-control-sm" placeholder="Name" required></div>
                        <div class="col-md-2"><input type="number" name="quantity" class="form-control form-control-sm" placeholder="Qty" step="0.01"></div>
                        <div class="col-md-1"><input type="text" name="unit" class="form-control form-control-sm" placeholder="Unit"></div>
                        <div class="col-md-2"><input type="number" name="unit_cost" class="form-control form-control-sm" placeholder="Unit Cost" step="0.01"></div>
                        <div class="col-md-1"><button type="submit" class="btn btn-sm btn-success w-100">Add</button></div>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Type</th><th>Name</th><th>Qty</th><th>Unit</th><th>Unit Cost</th><th>Total</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php $totalInputCost = 0; foreach ($inputs as $inp): $totalInputCost += $inp['total_cost']; ?>
                    <tr>
                        <td><span class="badge bg-<?= $ic[$inp['input_type']] ?? 'secondary' ?>"><?= ucfirst($inp['input_type']) ?></span></td>
                        <td><?= htmlspecialchars($inp['name']) ?></td>
                        <td><?= number_format($inp['quantity'],2) ?></td>
                        <td><?= htmlspecialchars($inp['unit'] ?? '') ?></td>
                        <td>₱<?= number_format($inp['unit_cost'],2) ?></td>
                        <td>₱<?= number_format($inp['total_cost'],2) ?></td>
                        <td class="text-muted small"><?= $inp['applied_date'] ? date('M d', strtotime($inp['applied_date'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($inputs)): ?><tr><td colspan="7" class="text-center text-muted py-2">No inputs recorded.</td></tr><?php endif; ?>
                    </tbody>
                    <?php if (!empty($inputs)): ?>
                    <tfoot><tr class="table-light fw-bold"><td colspan="5" class="text-end">Total Input Cost</td><td>₱<?= number_format($totalInputCost,2) ?></td><td></td></tr></tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <!-- Schedules -->
        <div class="table-card">
            <div class="card-header">
                <span><i class="bi bi-calendar3 me-2"></i>Schedule</span>
                <?php if (($_SESSION['user']['role'] ?? '') !== 'gm'): ?>
                <button class="btn btn-xs btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#addSchedForm"><i class="bi bi-plus"></i></button>
                <?php endif; ?>
            </div>
            <div class="collapse p-3 border-bottom" id="addSchedForm">
                <form action="<?= BASE_URL ?>/operational/add-schedule" method="POST" data-ajax data-reload="true">
                    <input type="hidden" name="production_record_id" value="<?= $record['id'] ?>">
                    <div class="row g-2">
                        <div class="col-12"><input type="text" name="activity" class="form-control form-control-sm" placeholder="Activity" required></div>
                        <div class="col-md-6"><input type="date" name="scheduled_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-6"><select name="assigned_to" class="form-select form-select-sm"><option value="">Unassigned</option><?php foreach ($employees as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-12"><button type="submit" class="btn btn-sm btn-primary w-100">Add</button></div>
                    </div>
                </form>
            </div>
            <div class="p-2">
                <?php foreach ($schedules as $s): ?>
                <div class="d-flex gap-2 align-items-start py-2 border-bottom">
                    <div class="flex-fill">
                        <div class="fw-semibold small"><?= htmlspecialchars($s['activity']) ?></div>
                        <div class="text-muted" style="font-size:11px"><?= date('M d, Y', strtotime($s['scheduled_date'])) ?><?= $s['assigned_name'] ? ' — '.$s['assigned_name'] : '' ?></div>
                    </div>
                    <span class="badge bg-<?= $ssc[$s['status']] ?? 'secondary' ?>"><?= ucfirst($s['status']) ?></span>
                    <?php if ($s['status'] !== 'completed'): ?>
                    <button class="btn btn-xs btn-outline-success" onclick="completeSchedule(<?= $s['id'] ?>)" title="Mark Done"><i class="bi bi-check"></i></button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (empty($schedules)): ?><div class="text-center text-muted py-3 small">No schedules yet.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const recordId = <?= $record['id'] ?>;

function updateStatus(status) {
    const fd = new FormData();
    fd.append('id', recordId); fd.append('status', status);
    if (status === 'planted') {
        fd.append('seed_product_id', document.getElementById('seedProduct')?.value || 0);
        fd.append('seed_quantity',   document.getElementById('seedQuantity')?.value || 0);
        fd.append('warehouse_id',    document.getElementById('plantWh')?.value || 0);
    }
    if (status === 'harvested') {
        fd.append('actual_yield',   document.getElementById('actualYield')?.value || 0);
        fd.append('actual_harvest', document.getElementById('actualHarvest')?.value || '');
        fd.append('warehouse_id',   document.getElementById('harvestWh')?.value || 0);
    }
    fetch(BASE_URL + '/operational/production-status', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}

function completeSchedule(id) {
    const fd = new FormData(); fd.append('id', id); fd.append('status', 'completed');
    fetch(BASE_URL + '/operational/update-schedule', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
