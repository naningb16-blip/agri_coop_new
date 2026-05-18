<?php
ob_start();
$pageTitle  = 'Finance';
$activeMenu = 'finance';
$userRole   = $_SESSION['user']['role'] ?? '';
$isReadOnly = in_array($userRole, ['gm', 'manager']);
$canApprove = in_array($userRole, ['admin', 'gm']);
$net        = $summary['net'] ?? 0;
?>

<?php if ($userRole === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all finance data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<!-- Date Filter -->
<form method="GET" action="<?= BASE_URL ?>/finance" class="row g-2 align-items-end mb-3">
    <div class="col-auto"><label class="form-label small">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>"></div>
    <div class="col-auto"><label class="form-label small">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>"></div>
    <div class="col-auto"><input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>"><button class="btn btn-sm btn-primary mt-3">Apply</button></div>
    <div class="col-auto ms-auto mt-3">
        <a href="<?= BASE_URL ?>/finance/report-print?from=<?= $from ?>&to=<?= $to ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print Report</a>
    </div>
</form>

<!-- Summary Cards -->
<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <?php foreach ([
        [number_format($summary['salesRevenue'],2), 'Sales Revenue (from Sales)', 'success', 'cash-coin'],
        [number_format($summary['expenses'],2),     'Expenses',         'danger',  'receipt-cutoff'],
        [number_format($summary['payroll'],2),      'Payroll',          'warning', 'people'],
        [number_format($summary['purchases'],2),    'Purchases',        'info',    'bag-check'],
        [number_format($summary['totalCosts'],2),   'Total Costs',      'warning', 'calculator'],
        [number_format($net,2),                     'Net Income',       $net>=0?'success':'danger', 'graph-up'],
    ] as [$val,$label,$color,$icon]): ?>
    <div class="col-6 col-md-2">
        <div class="stat-card <?= $color ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="stat-value" style="font-size:.95rem"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
                <i class="bi bi-<?= $icon ?> stat-icon"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('Finance Analytics')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach ([
        ['overview',  'bi-speedometer2',  'Overview'],
        ['expenses',  'bi-receipt-cutoff','Expenses'],
        ['payroll',   'bi-people',        'Payroll'],
        ['purchases', 'bi-bag-check',     'Purchases'],
        ['journal',   'bi-journal-text',  'Journal'],
    ] as [$key,$icon,$label]): ?>
    <li class="nav-item">
        <a class="nav-link <?= $tab===$key?'active':'' ?>" href="<?= BASE_URL ?>/finance?tab=<?= $key ?>&from=<?= $from ?>&to=<?= $to ?>">
            <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
            <?php if ($key==='expenses'): ?>
            <span class="badge bg-warning text-dark ms-1"><?= count(array_filter($expenses, fn($e)=>$e['status']==='pending')) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<?php if ($tab === 'overview'): ?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-bar-chart me-2"></i>Income vs Costs</span></div>
            <div class="p-3"><div class="chart-container"><canvas id="finChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-pie-chart me-2"></i>Cost Breakdown</span></div>
            <div class="p-3"><div class="chart-container"><canvas id="costChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-calculator me-2"></i>P&L Summary</span></div>
            <div class="p-3">
                <?php
                $rows = [
                    ['Sales Revenue',  $summary['salesRevenue'], 'success'],
                    ['Expenses',      -$summary['expenses'],     'danger'],
                    ['Payroll',       -$summary['payroll'],      'danger'],
                    ['Purchases',     -$summary['purchases'],    'danger'],
                ];
                foreach ($rows as [$label,$val,$color]): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small"><?= $label ?></span>
                    <span class="fw-semibold text-<?= $color ?>">&#8369;<?= number_format(abs($val),2) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between py-2 mt-1">
                    <span class="fw-bold">Net Income</span>
                    <span class="fw-bold fs-5 text-<?= $net>=0?'success':'danger' ?>">&#8369;<?= number_format($net,2) ?></span>
                </div>
                <?php if ($summary['salesRevenue'] > 0): ?>
                <div class="text-muted small mb-1">Margin: <?= round(($net/$summary['salesRevenue'])*100,1) ?>%</div>
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-<?= $net>=0?'success':'danger' ?>" style="width:<?= min(100,max(0,abs(round(($net/$summary['salesRevenue'])*100)))) ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Inventory Value -->
    <div class="col-12">
        <div class="table-card">
            <div class="card-header">
                <span><i class="bi bi-boxes me-2 text-info"></i>Inventory Value Snapshot</span>
                <button class="btn btn-sm btn-outline-info" onclick="loadInventoryValue()"><i class="bi bi-arrow-clockwise me-1"></i>Load</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="invValueTable">
                    <thead><tr><th>Product</th><th>Category</th><th>Qty on Hand</th><th>Unit</th><th>Avg Cost</th><th>Inventory Value</th></tr></thead>
                    <tbody><tr><td colspan="6" class="text-center text-muted py-3">Click Load to calculate</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const sumData = <?= json_encode($summary) ?>;

window.addEventListener('load', function() {
    const finCtx = document.getElementById('finChart');
    if (finCtx) {
        new Chart(finCtx, {
            type: 'bar',
            data: {
                labels: ['Sales Revenue','Total Costs','Net Income'],
                datasets: [{
                    data: [sumData.salesRevenue, sumData.totalCosts, sumData.net],
                    backgroundColor: ['rgba(76,175,80,.7)','rgba(244,67,54,.7)', sumData.net>=0?'rgba(76,175,80,.7)':'rgba(244,67,54,.7)'],
                    borderRadius: 6
                }]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{callback:v=>'\u20B1'+v.toLocaleString()}}} }
        });
    }

    const costCtx = document.getElementById('costChart');
    if (costCtx) {
        const costVals = [parseFloat(sumData.expenses||0), parseFloat(sumData.payroll||0), parseFloat(sumData.purchases||0)];
        if (costVals.some(v => v > 0)) {
            new Chart(costCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Expenses','Payroll','Purchases'],
                    datasets: [{ data: costVals, backgroundColor: ['#f44336','#ff9800','#2196f3'] }]
                },
                options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom',labels:{font:{size:10}}}} }
            });
        } else {
            costCtx.closest('.chart-container').innerHTML = '<div class="text-center text-muted pt-4 small">No cost data for this period.</div>';
        }
    }
});

function loadInventoryValue() {
    fetch(BASE_URL + '/finance/inventory-value').then(r=>r.json()).then(d => {
        const tbody = document.querySelector('#invValueTable tbody');
        tbody.innerHTML = d.items.map(i =>
            `<tr><td>${i.name}</td><td class="text-muted small">${i.category||'&mdash;'}</td><td>${parseFloat(i.total_qty).toFixed(2)}</td><td>${i.unit}</td><td>\u20B1${parseFloat(i.avg_cost).toFixed(2)}</td><td class="fw-semibold">\u20B1${parseFloat(i.inventory_value).toFixed(2)}</td></tr>`
        ).join('') + `<tr class="table-light fw-bold"><td colspan="5" class="text-end">Total Inventory Value</td><td>\u20B1${parseFloat(d.total_value).toFixed(2)}</td></tr>`;
    });
}
</script>
<?php endif; ?>

<!-- &#8369;8369;”€ CASH RECEIPTS &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€ -->
<?php if ($tab === 'receipts'): ?>
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-cash-coin me-2 text-success"></i>Receipts</span>
        <?php if (!$isReadOnly && $_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#receiptModal"><i class="bi bi-plus-circle me-1"></i>New Receipt</button><?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Receipt #</th><th>Label</th><th>Date</th><th>Name</th><th>Item / Description</th><th>Qty</th><th>Amount</th><th>Method</th><th>By</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($receipts as $r): ?>
            <tr>
                <?php
                    // Parse packed notes fallback: "[REC-xxx] Payer: name | Type: type | desc"
                    $notes = $r['notes'] ?? '';
                    preg_match('/\[([^\]]+)\]/', $notes, $rnMatch);
                    preg_match('/Payer:\s*([^|]+)/', $notes, $payerMatch);
                    preg_match('/Type:\s*([^|]+)/', $notes, $typeMatch);
                    $rNum    = $r['receipt_number'] ?? ($rnMatch[1]  ?? '#'.$r['id']);
                    $payer   = $r['payer_name']     ?? trim($payerMatch[1] ?? '&#8369;');
                    $rType   = $r['receipt_type']   ?? trim($typeMatch[1]  ?? 'cash_receipt');
                    // description is everything after the last |
                    $desc    = $r['item_description'] ?? (preg_match('/(?:cash_receipt|charge_invoice)\s*\|\s*(.+)$/i', $notes, $dm) ? trim($dm[1]) : ($notes ?: '&#8369;'));
                ?>
                <td class="fw-semibold"><?= htmlspecialchars($rNum) ?></td>
                <td>
                    <?php if (trim($rType) === 'charge_invoice'): ?>
                    <span class="badge bg-warning text-dark">Charge Invoice</span>
                    <?php else: ?>
                    <span class="badge bg-success">Cash Receipt</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($r['receipt_date'])) ?></td>
                <td><?= htmlspecialchars($payer) ?></td>
                <td class="small"><?= htmlspecialchars($desc) ?></td>
                <td class="text-muted small"><?= ($r['quantity'] ?? null) ? number_format($r['quantity'],2).' '.htmlspecialchars($r['unit']??'') : '&#8369;' ?></td>
                <td class="text-success fw-semibold">&#8369;<?= number_format($r['amount'],2) ?></td>
                <td><span class="badge bg-info text-dark"><?= ucwords(str_replace('_',' ',$r['payment_method'])) ?></span></td>
                <td class="small"><?= htmlspecialchars($r['received_by_name']??'&#8369;') ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/finance/receipt-print?id=<?= $r['id'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary" title="Print">
                        <i class="bi bi-printer"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($receipts)): ?><tr><td colspan="10" class="text-center text-muted py-4">No receipts in this period.</td></tr><?php endif; ?>
            </tbody>
            <?php if (!empty($receipts)): ?>
            <tfoot><tr class="table-light fw-bold"><td colspan="6" class="text-end">Total</td><td class="text-success">&#8369;<?= number_format(array_sum(array_column($receipts,'amount')),2) ?></td><td colspan="3"></td></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>New Receipt</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/finance/receipt" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <!-- Receipt Type -->
                    <div class="col-12">
                        <label class="form-label">Receipt Label <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="receipt_type" id="rtCash" value="cash_receipt" checked>
                            <label class="btn btn-outline-success" for="rtCash"><i class="bi bi-cash me-1"></i>Cash Receipt</label>
                            <input type="radio" class="btn-check" name="receipt_type" id="rtCharge" value="charge_invoice">
                            <label class="btn btn-outline-warning" for="rtCharge"><i class="bi bi-file-earmark-text me-1"></i>Charge Invoice</label>
                        </div>
                    </div>
                    <!-- Name / Payer -->
                    <div class="col-12">
                        <label class="form-label">Name (Payer / Customer) <span class="text-danger">*</span></label>
                        <input type="text" name="payer_name" class="form-control" placeholder="Full name of payer" required>
                    </div>
                    <!-- Date -->
                    <div class="col-md-6">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="receipt_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <!-- Reference -->
                    <div class="col-md-6">
                        <label class="form-label">Reference Type</label>
                        <select name="reference_type" class="form-select">
                            <option value="sale">Sale</option>
                            <option value="purchase">Purchase</option>
                            <option value="payroll">Payroll</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <!-- Item Description -->
                    <div class="col-12">
                        <label class="form-label">Item / Description</label>
                        <input type="text" name="item_description" class="form-control" placeholder="What was received for?">
                    </div>
                    <!-- Quantity (optional) -->
                    <div class="col-md-4">
                        <label class="form-label">Quantity <span class="text-muted small">(optional)</span></label>
                        <input type="number" name="quantity" class="form-control" step="0.01" min="0" placeholder="e.g. 50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" class="form-control" placeholder="kg / bags / pcs">
                    </div>
                    <!-- Amount -->
                    <div class="col-md-4">
                        <label class="form-label">Amount (&#8369; <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <!-- Payment Method -->
                    <div class="col-12">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Receipt</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- &#8369;8369;”€ EXPENSES &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369; -->
<?php if ($tab === 'expenses'): ?>
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-receipt-cutoff me-2 text-danger"></i>Expenses</span>
        <?php if (!$isReadOnly && $_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#expenseModal"><i class="bi bi-plus-circle me-1"></i>New Expense</button><?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Category</th><th>Vendor</th><th>Description</th><th>Amount</th><th>Due Date</th><th>Status</th><th>By</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($expenses as $e): ?>
            <tr>
                <td class="text-muted small text-nowrap"><?= date('M d, Y', strtotime($e['expense_date'])) ?></td>
                <td>
                    <?php 
                    $catLabels = [
                        'utilities_electric' => 'Electric',
                        'utilities_water' => 'Water',
                        'utilities_internet' => 'Internet',
                        'utilities_phone' => 'Phone',
                        'rent' => 'Rent',
                        'supplies' => 'Supplies',
                        'maintenance' => 'Maintenance',
                        'transportation' => 'Transport',
                        'professional_fees' => 'Prof. Fees',
                        'insurance' => 'Insurance',
                        'taxes' => 'Taxes',
                        'salaries' => 'Salaries',
                        'other' => 'Other'
                    ];
                    $cat = $e['category'] ?? 'other';
                    ?>
                    <span class="badge bg-secondary"><?= $catLabels[$cat] ?? ucfirst($cat) ?></span>
                    <?php if (!empty($e['billing_month'])): ?>
                    <div class="text-muted small"><?= date('M Y', strtotime($e['billing_month'].'-01')) ?></div>
                    <?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars($e['vendor_name'] ?? '—') ?></td>
                <td class="small"><?= htmlspecialchars(mb_strimwidth($e['description']??'',0,40,'…')) ?></td>
                <td class="text-danger fw-semibold">&#8369;<?= number_format($e['amount'],2) ?></td>
                <td class="text-muted small"><?= !empty($e['due_date']) ? date('M d', strtotime($e['due_date'])) : '—' ?></td>
                <td><?php 
                    // Show approval status if it exists and is different from expense status
                    $displayStatus = $e['status'];
                    if ($e['approval_request_id'] && $e['approval_status']) {
                        // If approval is approved/rejected, use that status
                        if (in_array($e['approval_status'], ['approved', 'rejected'])) {
                            $displayStatus = $e['approval_status'];
                        }
                    }
                    $badge = $displayStatus === 'approved' ? 'success' : ($displayStatus === 'rejected' ? 'danger' : 'warning');
                    ?>
                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($displayStatus) ?></span>
                    <?php if ($e['approval_request_id']): ?>
                    <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $e['approval_request_id'] ?>" class="ms-1" title="View approval details">
                        <i class="bi bi-info-circle text-muted"></i>
                    </a>
                    <?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars($e['created_by_name']??'&#8369;') ?></td>
                <td class="text-nowrap">
                    <?php if ($canApprove && $e['status']==='pending'): ?>
                    <button class="btn btn-xs btn-success" onclick="approveExpense(<?= $e['id'] ?>,'approved')"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-xs btn-danger"  onclick="approveExpense(<?= $e['id'] ?>,'rejected')"><i class="bi bi-x-lg"></i></button>
                    <?php endif; ?>
                    <?php if ($canApprove): ?>
                    <button class="btn btn-xs btn-outline-danger ms-1" onclick="deleteExpense(<?= $e['id'] ?>, '<?= htmlspecialchars($e['category']??'expense') ?>')" title="Delete"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($expenses)): ?><tr><td colspan="9" class="text-center text-muted py-4">No expenses in this period.</td></tr><?php endif; ?>
            </tbody>
            <?php $approvedTotal = array_sum(array_map(fn($e)=>$e['status']==='approved'||(isset($e['approval_status'])&&$e['approval_status']==='approved')?(float)$e['amount']:0, $expenses)); ?>
            <?php if (!empty($expenses)): ?>
            <tfoot><tr class="table-light fw-bold"><td colspan="4" class="text-end">Approved Total</td><td class="text-danger">&#8369;<?= number_format($approvedTotal,2) ?></td><td colspan="4"></td></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-receipt-cutoff me-2"></i>New Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/finance/expense" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category</option>
                            <optgroup label="Utilities">
                                <option value="utilities_electric">Electric Bill</option>
                                <option value="utilities_water">Water Bill</option>
                                <option value="utilities_internet">Internet Bill</option>
                                <option value="utilities_phone">Phone Bill</option>
                            </optgroup>
                            <optgroup label="Operating Expenses">
                                <option value="rent">Rent</option>
                                <option value="supplies">Office Supplies</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="transportation">Transportation</option>
                            </optgroup>
                            <optgroup label="Other">
                                <option value="professional_fees">Professional Fees</option>
                                <option value="insurance">Insurance</option>
                                <option value="taxes">Taxes</option>
                                <option value="salaries">Salaries</option>
                                <option value="other">Other</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                    <div class="col-md-6"><label class="form-label">Expense Date <span class="text-danger">*</span></label><input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Billing Month <span class="text-muted small">(for recurring bills)</span></label><input type="month" name="billing_month" class="form-control" placeholder="YYYY-MM"></div>
                    <div class="col-md-6"><label class="form-label">Due Date <span class="text-muted small">(optional)</span></label><input type="date" name="due_date" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="check">Check</option></select></div>
                    <div class="col-md-6"><label class="form-label">Vendor Name <span class="text-muted small">(optional)</span></label><input type="text" name="vendor_name" class="form-control" placeholder="e.g. Manila Electric Company"></div>
                    <div class="col-md-6"><label class="form-label">Account Number <span class="text-muted small">(optional)</span></label><input type="text" name="account_number" class="form-control" placeholder="e.g. 1234-5678-9012"></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" placeholder="Additional notes or details"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Submit Expense</button></div>
            </form>
        </div>
    </div>
</div>
<script>
const BASE_URL = '<?= BASE_URL ?>';
function approveExpense(id, action) {
    if (!confirm(`${action==='approved'?'Approve':'Reject'} this expense?`)) return;
    const fd = new FormData(); fd.append('id',id); fd.append('action',action);
    fetch(BASE_URL+'/finance/approve-expense',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{showToast(res.message,res.success?'success':'danger');if(res.success)location.reload();});
}
function deleteExpense(id, label) {
    if (!confirm(`Delete "${label}" expense? This cannot be undone.`)) return;
    const fd = new FormData(); fd.append('id', id);
    fetch(BASE_URL+'/finance/delete-expense',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{showToast(res.message,res.success?'success':'danger');if(res.success)location.reload();});
}
</script>
<?php endif; ?>

<!-- &#8369;8369;”€ PAYROLL &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€ -->
<?php if ($tab === 'payroll'): ?>
<?php $pendingCount = count(array_filter($payrolls, fn($p) => $p['status'] === 'pending')); ?>
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-people me-2 text-warning"></i>Payroll Records</span>
        <?php if (in_array($userRole, ['admin', 'gm']) && $pendingCount > 0): ?>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveAllModal">
            <i class="bi bi-check-all me-1"></i>Approve All Pending <span class="badge bg-white text-success ms-1"><?= $pendingCount ?></span>
        </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Position</th><th>Department</th><th>Period</th><th>Basic Pay</th><th>Deductions</th><th>Bonuses</th><th>Net Pay</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($payrolls as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($p['position']??'&#8369;') ?></td>
                <td class="small text-muted"><?= htmlspecialchars($p['dept_name']??'&#8369;') ?></td>
                <td class="small"><?= date('M d', strtotime($p['period_start'])) ?> &#8369; <?= date('M d, Y', strtotime($p['period_end'])) ?></td>
                <td>&#8369;<?= number_format($p['basic_pay'],2) ?></td>
                <td class="text-danger">&#8369;<?= number_format($p['deductions'],2) ?></td>
                <td class="text-success">&#8369;<?= number_format($p['bonuses'],2) ?></td>
                <td class="fw-bold">&#8369;<?= number_format($p['net_pay'],2) ?></td>
                <td><span class="badge badge-<?= $p['status']==='paid'?'approved':$p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                <td class="text-nowrap">
                    <?php if (in_array($userRole, ['admin', 'gm']) && $p['status']==='pending'): ?>
                    <button class="btn btn-xs btn-success" onclick="approvePayroll(<?= $p['id'] ?>)"><i class="bi bi-check-lg"></i> Approve</button>
                    <?php endif; ?>
                    <?php if (in_array($userRole, ['admin', 'gm']) && $p['status']==='approved'): ?>
                    <button class="btn btn-xs btn-info text-white" onclick="markPaid(<?= $p['id'] ?>)"><i class="bi bi-cash"></i> Mark Paid</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($payrolls)): ?><tr><td colspan="10" class="text-center text-muted py-4">No payroll records in this period.</td></tr><?php endif; ?>
            </tbody>
            <?php if (!empty($payrolls)): ?>
            <tfoot><tr class="table-light fw-bold"><td colspan="7" class="text-end">Total Net Pay</td><td>&#8369;<?= number_format(array_sum(array_column($payrolls,'net_pay')),2) ?></td><td colspan="2"></td></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<script>
const BASE_URL = '<?= BASE_URL ?>';
function approvePayroll(id) {
    if (!confirm('Approve this payroll?')) return;
    const fd = new FormData(); fd.append('id',id);
    fetch(BASE_URL+'/finance/approve-payroll',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{showToast(res.message,res.success?'success':'danger');if(res.success)location.reload();});
}
function markPaid(id) {
    if (!confirm('Mark payroll as paid?')) return;
    const fd = new FormData(); fd.append('id',id);
    fetch(BASE_URL+'/finance/mark-paid',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{showToast(res.message,res.success?'success':'danger');if(res.success)location.reload();});
}
function approveAllPayroll() {
    const remarks = document.getElementById('approveAllRemarks').value.trim();
    const fd = new FormData();
    fd.append('remarks', remarks);
    fetch(BASE_URL+'/finance/approve-all-payroll',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(res=>{
            showToast(res.message, res.success?'success':'danger');
            if(res.success) { bootstrap.Modal.getInstance(document.getElementById('approveAllModal'))?.hide(); location.reload(); }
        });
}
</script>

<!-- Approve All Modal -->
<div class="modal fade" id="approveAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-check-all text-success me-2"></i>Approve All Pending Payrolls</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">This will approve all <strong><?= $pendingCount ?? 0 ?></strong> pending payroll record(s) in the current period.</p>
                <label class="form-label">Remarks <span class="text-muted small">(Optional)</span></label>
                <textarea id="approveAllRemarks" class="form-control" rows="3" placeholder="e.g. Approved for this pay period..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="approveAllPayroll()">
                    <i class="bi bi-check-all me-1"></i>Approve All
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- &#8369;8369;”€ PURCHASES &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€ -->
<?php if ($tab === 'purchases'): ?>
<div class="table-card">
    <div class="card-header"><span><i class="bi bi-bag-check me-2 text-info"></i>Purchase Transactions</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>PO #</th><th>Supplier</th><th>Order Date</th><th>Total Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $p): ?>
            <tr>
                <td class="fw-semibold"><a href="<?= BASE_URL ?>/purchasing/po-detail?id=<?= $p['id'] ?>&return=<?= urlencode('/finance?tab=purchases') ?>" class="text-decoration-none"><?= htmlspecialchars($p['po_number']) ?></a></td>
                <td><?= htmlspecialchars($p['supplier_name']) ?></td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($p['order_date'])) ?></td>
                <td class="fw-semibold">&#8369;<?= number_format($p['total_amount'],2) ?></td>
                <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                <td><a href="<?= BASE_URL ?>/purchasing/po-detail?id=<?= $p['id'] ?>&return=<?= urlencode('/finance?tab=purchases') ?>" class="btn btn-xs btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($purchases)): ?><tr><td colspan="6" class="text-center text-muted py-4">No purchase transactions in this period.</td></tr><?php endif; ?>
            </tbody>
            <?php if (!empty($purchases)): ?>
            <tfoot><tr class="table-light fw-bold"><td colspan="3" class="text-end">Total</td><td>&#8369;<?= number_format(array_sum(array_column($purchases,'total_amount')),2) ?></td><td colspan="2"></td></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- &#8369;8369;”€ JOURNAL &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€ -->
<?php if ($tab === 'journal'): ?>
<div class="table-card">
    <div class="card-header"><span><i class="bi bi-journal-text me-2"></i>Journal Entries</span><span class="badge bg-secondary"><?= count($journal) ?> entries</span></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th>Debit Account</th><th>Credit Account</th><th>Amount</th><th>Source</th><th>By</th></tr></thead>
            <tbody>
            <?php foreach ($journal as $j): ?>
            <tr>
                <td class="text-muted small text-nowrap"><?= date('M d, Y', strtotime($j['entry_date'])) ?></td>
                <td class="small fw-semibold"><?= htmlspecialchars($j['reference']??'&#8369;') ?></td>
                <td class="small"><?= htmlspecialchars(mb_strimwidth($j['description'],0,50,'&#8369;')) ?></td>
                <td class="small text-success"><?= htmlspecialchars($j['debit_account']) ?></td>
                <td class="small text-danger"><?= htmlspecialchars($j['credit_account']) ?></td>
                <td class="fw-semibold">&#8369;<?= number_format($j['amount'],2) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($j['source_type']??'&#8369;') ?> <?= $j['source_id']?'#'.$j['source_id']:'' ?></td>
                <td class="small"><?= htmlspecialchars($j['created_by_name']??'&#8369;') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($journal)): ?><tr><td colspan="8" class="text-center text-muted py-4">No journal entries in this period.</td></tr><?php endif; ?>
            </tbody>
            <?php if (!empty($journal)): ?>
            <tfoot><tr class="table-light fw-bold"><td colspan="5" class="text-end">Total Debits</td><td>&#8369;<?= number_format(array_sum(array_column($journal,'amount')),2) ?></td><td colspan="2"></td></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>





