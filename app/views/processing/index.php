<?php
ob_start();
$pageTitle  = 'Processing';
$activeMenu = 'processing';
$userRole   = $_SESSION['user']['role'] ?? '';
$isReadOnly = in_array($userRole, ['gm', 'manager']);
$canManage  = in_array($userRole, ['admin']) && !$isReadOnly;
$stageColors = ['drying'=>'warning','sorting'=>'info','shelling'=>'primary','bagging'=>'success','milling'=>'secondary'];
$statusColors = ['pending'=>'warning','in_progress'=>'info','completed'=>'success','cancelled'=>'secondary'];
?>

<?php if ($userRole === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all processing data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <?php foreach ([
        [$summary['total'],       'Total Batches',  '',        'layers'],
        [$summary['pending'],     'Pending',        'warning', 'hourglass-split'],
        [$summary['in_progress'], 'In Progress',    'info',    'gear-wide-connected'],
        [$summary['completed'],   'Completed',      '',        'check-circle'],
        [number_format($summary['total_input'],2),  'Total Input',  'purple', 'box-arrow-in-down'],
        [number_format($summary['total_output'],2), 'Total Output', '',       'box-arrow-up'],
    ] as [$val, $label, $color, $icon]): ?>
    <div class="col-6 col-md-2">
        <div class="stat-card <?= $color ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="stat-value" style="font-size:1.1rem"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
                <i class="bi bi-<?= $icon ?> stat-icon"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('Processing Analytics')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<!-- Filters + New -->
<div class="table-card mb-3">
    <div class="p-3">
        <form method="GET" action="<?= BASE_URL ?>/processing" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['pending','in_progress','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small">Process Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach (['drying','sorting','shelling','bagging','milling'] as $t): ?>
                    <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3"><button class="btn btn-sm btn-primary w-100 mt-3">Filter</button></div>
            <div class="col-sm-3 text-end">
                <?php if ($_SESSION['user']['role'] !== 'gm'): ?>
                <button type="button" class="btn btn-sm btn-success w-100 mt-3" data-bs-toggle="modal" data-bs-target="#newBatchModal">
                    <i class="bi bi-plus-circle me-1"></i>New Batch
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Batches Table -->
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-gear-wide-connected me-2 text-success"></i>Processing Batches</span>
        <span class="badge bg-secondary"><?= count($batches) ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Batch #</th><th>Product</th><th>Stages</th><th>Input</th><th>Output</th><th>Waste</th><th>Assigned</th><th>Start</th><th>Status</th><th>Approval</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($batches as $b): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/processing/detail?id=<?= $b['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($b['batch_number']) ?></a></td>
                <td><?= htmlspecialchars($b['product_name']) ?></td>
                <td>
                    <span class="badge bg-<?= $stageColors[$b['process_type']] ?? 'secondary' ?>"><?= ucfirst($b['process_type']) ?></span>
                    <?php if ($b['stage_count'] > 1): ?>
                    <span class="text-muted small ms-1"><?= $b['stages_done'] ?>/<?= $b['stage_count'] ?></span>
                    <?php endif; ?>
                </td>
                <td><?= number_format($b['input_quantity'], 2) ?> <?= htmlspecialchars($b['unit']) ?></td>
                <td><?= $b['output_quantity'] ? number_format($b['output_quantity'], 2) : '' ?></td>
                <td><?= $b['waste_quantity']  ? number_format($b['waste_quantity'],  2) : '' ?></td>
                <td class="small"><?= htmlspecialchars($b['assigned_name'] ?? '') ?></td>
                <td class="text-muted small"><?= $b['start_date'] ? date('M d, Y', strtotime($b['start_date'])) : '' ?></td>
                <td><span class="badge badge-<?= $b['status'] === 'in_progress' ? 'pending' : $b['status'] ?>"><?= ucwords(str_replace('_',' ',$b['status'])) ?></span></td>
                <td>
                    <?php if ($b['approval_request_id']): ?>
                    <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $b['approval_request_id'] ?>" class="badge badge-<?= $b['approval_status'] ?> text-decoration-none">
                        <?= $b['approval_status'] === 'pending' ? htmlspecialchars($b['approval_step_label'] ?? 'Pending') : ucfirst($b['approval_status']) ?>
                    </a>
                    <?php else: ?><span class="text-muted small"></span><?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <a href="<?= BASE_URL ?>/processing/detail?id=<?= $b['id'] ?>" class="btn btn-xs btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                    <?php if ($canManage && in_array($b['status'], ['pending','in_progress'])): ?>
                    <button class="btn btn-xs btn-outline-danger" onclick="cancelBatch(<?= $b['id'] ?>)" title="Cancel"><i class="bi bi-x-circle"></i></button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($batches)): ?>
            <tr><td colspan="11" class="text-center text-muted py-4">No processing batches found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Batch Modal -->
<div class="modal fade" id="newBatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear-wide-connected me-2"></i>New Processing Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <input type="text" id="bProductInput" class="form-control" list="bProductList" placeholder="Type or select product" required autocomplete="off">
                        <input type="hidden" id="bProduct">
                        <datalist id="bProductList">
                            <?php foreach ($products as $p): ?>
                            <option value="<?= htmlspecialchars($p['name']) ?>" data-id="<?= $p['id'] ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assigned To</label>
                        <input type="text" id="bAssignedInput" class="form-control" list="bAssignedList" placeholder="Type or select employee" autocomplete="off">
                        <input type="hidden" id="bAssigned">
                        <datalist id="bAssignedList">
                            <?php foreach ($employees as $e): ?>
                            <option value="<?= htmlspecialchars($e['full_name']) ?>" data-id="<?= $e['id'] ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Input Quantity <span class="text-danger">*</span></label>
                        <input type="number" id="bInputQty" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Input Warehouse</label>
                        <input type="text" id="bInputWhInput" class="form-control" list="bWhList" placeholder="Type or select warehouse" autocomplete="off">
                        <input type="hidden" id="bInputWh">
                        <datalist id="bWhList">
                            <?php foreach ($warehouses as $w): ?>
                            <option value="<?= htmlspecialchars($w['name']) ?>" data-id="<?= $w['id'] ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Output Warehouse</label>
                        <input type="text" id="bOutputWhInput" class="form-control" list="bWhList" placeholder="Type or select warehouse" autocomplete="off">
                        <input type="hidden" id="bOutputWh">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" id="bStartDate" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Notes</label>
                        <input type="text" id="bNotes" class="form-control" placeholder="Optional">
                    </div>

                    <!-- Stage Selection -->
                    <div class="col-12">
                        <label class="form-label">Processing Stages <span class="text-danger">*</span> <small class="text-muted">(select in order)</small></label>
                        <div class="d-flex flex-wrap gap-2" id="stageSelector">
                            <?php foreach (['drying','sorting','shelling','bagging','milling'] as $stage): ?>
                            <div class="stage-chip border rounded px-3 py-2 cursor-pointer user-select-none"
                                 data-stage="<?= $stage ?>" onclick="toggleStage(this)"
                                 style="cursor:pointer">
                                <i class="bi bi-circle me-1 stage-icon"></i><?= ucfirst($stage) ?>
                                <span class="badge bg-secondary ms-1 stage-order" style="display:none"></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-muted small mt-1" id="stagePreview">No stages selected.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitBatch()"><i class="bi bi-send me-1"></i>Create Batch</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let selectedStages = [];

function toggleStage(el) {
    const stage = el.dataset.stage;
    const idx   = selectedStages.indexOf(stage);
    if (idx === -1) {
        selectedStages.push(stage);
        el.classList.add('bg-success', 'text-white', 'border-success');
        el.querySelector('.stage-icon').className = 'bi bi-check-circle-fill me-1 stage-icon';
    } else {
        selectedStages.splice(idx, 1);
        el.classList.remove('bg-success', 'text-white', 'border-success');
        el.querySelector('.stage-icon').className = 'bi bi-circle me-1 stage-icon';
    }
    // Update order badges
    document.querySelectorAll('#stageSelector .stage-chip').forEach(chip => {
        const i = selectedStages.indexOf(chip.dataset.stage);
        const badge = chip.querySelector('.stage-order');
        if (i !== -1) { badge.textContent = i + 1; badge.style.display = ''; }
        else badge.style.display = 'none';
    });
    document.getElementById('stagePreview').textContent = selectedStages.length
        ? 'Order: ' + selectedStages.map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(' &#8369; ')
        : 'No stages selected.';
}

function resolveId(listId, inputVal) {
    const opt = [...document.querySelectorAll(`#${listId} option`)].find(o => o.value === inputVal);
    return opt ? (opt.dataset.id || '') : '';
}

function submitBatch() {
    const productInput = document.getElementById('bProductInput').value.trim();
    const inputQty     = document.getElementById('bInputQty').value;
    if (!productInput) { showToast('Enter a product name.', 'warning'); return; }
    if (!inputQty || parseFloat(inputQty) <= 0) { showToast('Enter input quantity.', 'warning'); return; }
    if (!selectedStages.length) { showToast('Select at least one stage.', 'warning'); return; }

    const productId  = resolveId('bProductList',  productInput);
    const assignedId = resolveId('bAssignedList', document.getElementById('bAssignedInput').value.trim());
    const inputWhId  = resolveId('bWhList',        document.getElementById('bInputWhInput').value.trim());
    const outputWhId = resolveId('bWhList',        document.getElementById('bOutputWhInput').value.trim());

    const fd = new FormData();
    fd.append('product_id',          productId);
    fd.append('product_name',        productInput);
    fd.append('input_quantity',      inputQty);
    fd.append('input_warehouse_id',  inputWhId);
    fd.append('input_warehouse_name', document.getElementById('bInputWhInput').value.trim());
    fd.append('output_warehouse_id', outputWhId);
    fd.append('output_warehouse_name', document.getElementById('bOutputWhInput').value.trim());
    fd.append('assigned_to',         assignedId);
    fd.append('assigned_name',       document.getElementById('bAssignedInput').value.trim());
    fd.append('start_date',          document.getElementById('bStartDate').value);
    fd.append('notes',               document.getElementById('bNotes').value);
    selectedStages.forEach(s => fd.append('stages[]', s));

    fetch(BASE_URL + '/processing/create', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) setTimeout(() => location.reload(), 800);
        });
}

function cancelBatch(id) {
    if (!confirm('Cancel this batch? Input stock will be reversed.')) return;
    const fd = new FormData(); fd.append('id', id);
    fetch(BASE_URL + '/processing/cancel', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>



