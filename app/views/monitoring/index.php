<?php
ob_start();
$pageTitle  = 'Cost Monitoring';
$activeMenu = 'monitoring';
$totalCosts = ($totals['total_batch_cost'] ?? 0) + ($totals['total_input_cost'] ?? 0);
?>

<?php if (($_SESSION['user']['role'] ?? '') === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all cost monitoring data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <div class="col-md-4"><div class="stat-card"><div class="stat-value" style="font-size:1rem">&#8369;<?= number_format($totals['total_batch_cost']??0,2) ?></div><div class="stat-label">Batch Processing Costs</div></div></div>
    <div class="col-md-4"><div class="stat-card warning"><div class="stat-value" style="font-size:1rem">&#8369;<?= number_format($totals['total_input_cost']??0,2) ?></div><div class="stat-label">Production Input Costs</div></div></div>
    <div class="col-md-4"><div class="stat-card info"><div class="stat-value" style="font-size:1rem">&#8369;<?= number_format($totalCosts,2) ?></div><div class="stat-label">Total Costs</div></div></div>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('Cost Monitoring')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<!-- Date Filter -->
<div class="table-card mb-3">
    <div class="p-3">
        <form method="GET" action="<?= BASE_URL ?>/monitoring" class="row g-2 align-items-end">
            <div class="col-sm-3"><label class="form-label small">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>"></div>
            <div class="col-sm-3"><label class="form-label small">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>"></div>
            <div class="col-sm-3"><button class="btn btn-sm btn-primary w-100 mt-3">Apply</button></div>
            <div class="col-sm-3 text-end">
                <?php if (($_SESSION['user']['role'] ?? '') !== 'gm'): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-3" data-bs-toggle="modal" data-bs-target="#addCostModal"><i class="bi bi-plus-circle me-1"></i>Add Batch Cost</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- Cost by Type -->
    <div class="col-md-4">
        <div class="table-card h-100">
            <div class="card-header"><span><i class="bi bi-pie-chart me-2"></i>Batch Cost Breakdown</span></div>
            <div class="p-3">
                <?php foreach ($costByType as $c): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small"><?= ucfirst($c['cost_type']) ?></span>
                    <span class="fw-semibold">&#8369;<?= number_format($c['total'],2) ?></span>
                </div>
                <?php if ($totalCosts > 0): ?>
                <div class="progress mb-2" style="height:6px">
                    <div class="progress-bar" style="width:<?= round(($c['total']/$totalCosts)*100) ?>%"></div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php if (empty($costByType)): ?><div class="text-muted small text-center py-3">No data.</div><?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Input Cost by Type -->
    <div class="col-md-4">
        <div class="table-card h-100">
            <div class="card-header"><span><i class="bi bi-droplet me-2"></i>Input Cost Breakdown</span></div>
            <div class="p-3">
                <?php foreach ($inputCosts as $c): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small"><?= ucfirst($c['input_type']) ?></span>
                    <span class="fw-semibold">&#8369;<?= number_format($c['total'],2) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($inputCosts)): ?><div class="text-muted small text-center py-3">No data.</div><?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Upcoming Schedules -->
    <div class="col-md-4">
        <div class="table-card h-100">
            <div class="card-header"><span><i class="bi bi-calendar3 me-2"></i>Schedules</span></div>
            <div style="max-height:280px;overflow-y:auto">
                <?php foreach ($schedules as $s): ?>
                <div class="px-3 py-2 border-bottom">
                    <div class="fw-semibold small"><?= htmlspecialchars($s['activity']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($s['farmer_name']) ?> — <?= htmlspecialchars($s['product_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= date('M d, Y', strtotime($s['scheduled_date'])) ?><?= $s['assigned_name'] ? ' | '.$s['assigned_name'] : '' ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($schedules)): ?><div class="text-center text-muted py-3 small">No schedules.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Batch Cost Table -->
<div class="table-card">
    <div class="card-header"><span><i class="bi bi-gear me-2"></i>Batch Cost Analysis</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Batch #</th><th>Product</th><th>Status</th><th>Input Qty</th><th>Output Qty</th><th>Total Cost</th><th>Cost/Unit</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($batchCosts as $b): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/processing/detail?id=<?= $b['batch_id'] ?? '' ?>" class="text-decoration-none fw-semibold"><?= htmlspecialchars($b['batch_number']) ?></a></td>
                <td><?= htmlspecialchars($b['product_name']) ?></td>
                <td><span class="badge badge-<?= $b['status'] === 'in_progress' ? 'pending' : $b['status'] ?>"><?= ucwords(str_replace('_',' ',$b['status'])) ?></span></td>
                <td><?= number_format($b['input_quantity'],2) ?></td>
                <td><?= $b['output_quantity'] ? number_format($b['output_quantity'],2) : '—' ?></td>
                <td>&#8369;<?= number_format($b['total_cost'],2) ?></td>
                <td><?= $b['cost_per_unit'] > 0 ? '&#8369;'.number_format($b['cost_per_unit'],4) : '—' ?></td>
                <td>
                    <?php if ($b['total_cost'] > 0): ?>
                    <button class="btn btn-xs btn-outline-info" onclick="viewBatchCostDetails(<?= $b['batch_id'] ?? 0 ?>, '<?= htmlspecialchars($b['batch_number']) ?>')">
                        <i class="bi bi-list-ul me-1"></i>Details
                    </button>
                    <?php else: ?>
                    <span class="text-muted small">No costs</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($batchCosts)): ?><tr><td colspan="8" class="text-center text-muted py-4">No data for selected period.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Batch Cost Modal -->
<div class="modal fade" id="addCostModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Batch Cost</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/monitoring/add-cost" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Processing Batch</label>
                        <select name="batch_id" class="form-select" required>
                            <option value="">-- Select Batch --</option>
                            <?php foreach (($availableBatches ?? []) as $batch): ?>
                            <option value="<?= $batch['id'] ?>">
                                Batch #<?= htmlspecialchars($batch['batch_number']) ?> - 
                                <?= htmlspecialchars($batch['product_name']) ?> 
                                (<?= ucfirst($batch['status']) ?>) - 
                                <?= date('M d, Y', strtotime($batch['created_at'])) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($availableBatches ?? [])): ?>
                        <div class="form-text text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No processing batches available. Create a batch first in the Processing module.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6"><label class="form-label">Cost Type</label>
                        <select name="cost_type" class="form-select"><option value="labor">Labor</option><option value="material">Material</option><option value="overhead">Overhead</option><option value="utility">Utility</option><option value="other">Other</option></select></div>
                    <div class="col-md-6"><label class="form-label">Amount</label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                    <div class="col-12"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Cost</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Batch Cost Details Modal -->
<div class="modal fade" id="batchCostDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-list-ul me-2"></i>
                    Cost Details - <span id="modalBatchNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="costDetailsLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Loading cost details...</p>
                </div>
                <div id="costDetailsContent" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Cost Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody id="costDetailsTableBody">
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th id="costDetailsTotal">₱0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div id="costDetailsError" style="display:none;" class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="costDetailsErrorMessage"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

function viewBatchCostDetails(batchId, batchNumber) {
    document.getElementById('modalBatchNumber').textContent = 'Batch #' + batchNumber;
    document.getElementById('costDetailsLoading').style.display = 'block';
    document.getElementById('costDetailsContent').style.display = 'none';
    document.getElementById('costDetailsError').style.display = 'none';
    
    const modal = new bootstrap.Modal(document.getElementById('batchCostDetailsModal'));
    modal.show();
    
    fetch(`${BASE_URL}/monitoring/batch-cost-details?batch_id=${batchId}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('costDetailsLoading').style.display = 'none';
            
            if (!data.success) {
                document.getElementById('costDetailsError').style.display = 'block';
                document.getElementById('costDetailsErrorMessage').textContent = data.message || 'Failed to load cost details';
                return;
            }
            
            const tbody = document.getElementById('costDetailsTableBody');
            tbody.innerHTML = '';
            
            if (data.costs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No cost entries found</td></tr>';
                document.getElementById('costDetailsTotal').textContent = '₱0.00';
            } else {
                let total = 0;
                data.costs.forEach(cost => {
                    total += parseFloat(cost.amount);
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="small">${new Date(cost.recorded_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
                        <td><span class="badge bg-secondary">${cost.cost_type}</span></td>
                        <td class="small">${cost.description || '—'}</td>
                        <td class="fw-semibold">₱${parseFloat(cost.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td class="small">${cost.recorded_by_name || 'Unknown'}</td>
                    `;
                    tbody.appendChild(row);
                });
                document.getElementById('costDetailsTotal').textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            
            document.getElementById('costDetailsContent').style.display = 'block';
        })
        .catch(e => {
            document.getElementById('costDetailsLoading').style.display = 'none';
            document.getElementById('costDetailsError').style.display = 'block';
            document.getElementById('costDetailsErrorMessage').textContent = 'Error loading cost details: ' + e.message;
        });
}
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

