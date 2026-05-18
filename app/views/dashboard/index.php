<?php
ob_start();
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
?>

<!-- KPI Cards Row 1 -->
<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <?php foreach ([
        [$stats['total_employees'],   'Active Employees',   '',        'people'],
        [$stats['pending_approvals'], 'Pending Approvals',  'warning', 'hourglass-split'],
        [$stats['low_stock'],         'Low Stock Items',    'danger',  'exclamation-triangle'],
        [$stats['open_sales'],        'Open Sales Orders',  'info',    'cart3'],
        ['&#8369;'.number_format($stats['monthly_revenue'],0),  'Monthly Revenue',  '',       'graph-up'],
        ['&#8369;'.number_format($stats['monthly_expenses'],0), 'Monthly Expenses', 'purple', 'cash-stack'],
    ] as [$val,$label,$color,$icon]): ?>
    <div class="col-6 col-xl-2">
        <div class="stat-card <?= $color ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="stat-value"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
                <i class="bi bi-<?= $icon ?> stat-icon"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('Dashboard Analytics')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<!-- KPI Cards Row 2 -->
<div class="row g-3 mb-3">
    <?php foreach ([
        [$stats['active_batches'],    'Active Batches',     'info',    'gear-wide-connected'],
        [$stats['pending_deliveries'],'Pending Deliveries', 'warning', 'truck'],
        [number_format($stats['total_yield_month'],2), 'Yield This Month', '', 'basket'],
        [$stats['qa_failed_month'],   'QA Failed (Month)',  'danger',  'x-circle'],
    ] as [$val,$label,$color,$icon]): ?>
    <div class="col-6 col-md-3">
        <div class="stat-card <?= $color ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="stat-value"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
                <i class="bi bi-<?= $icon ?> stat-icon"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-3">
    <!-- Sales Chart -->
    <div class="col-lg-5">
        <div class="table-card h-100">
            <div class="card-header d-flex align-items-center gap-2 flex-wrap">
                <span><i class="bi bi-bar-chart me-2 text-success"></i>Monthly Sales</span>
                <div class="ms-auto d-flex gap-1">
                    <select id="salesYearFilter" class="form-select form-select-sm" onchange="renderSalesChart()" style="width:80px">
                        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="salesMonthFilter" class="form-select form-select-sm" onchange="renderSalesChart()" style="width:100px">
                        <option value="0">All Months</option>
                        <?php foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $mi => $mn): ?>
                        <option value="<?= $mi+1 ?>" <?= ($mi+1)==date('n') ? 'selected' : '' ?>><?= $mn ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="p-3"><div class="chart-container"><canvas id="salesChart"></canvas></div></div>
        </div>
    </div>
    <!-- Inventory by Warehouse -->
    <div class="col-lg-4">
        <div class="table-card h-100">
            <div class="card-header"><span><i class="bi bi-boxes me-2 text-info"></i>Stock by Warehouse</span></div>
            <div class="p-3"><div class="chart-container"><canvas id="inventoryChart"></canvas></div></div>
        </div>
    </div>
    <!-- Production Status -->
    <div class="col-lg-3">
        <div class="table-card h-100">
            <div class="card-header"><span><i class="bi bi-flower1 me-2 text-warning"></i>Production Status</span></div>
            <div class="p-3"><div class="chart-container"><canvas id="productionChart"></canvas></div></div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row g-3">
    <!-- Recent Sales -->
    <div class="col-lg-6">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-receipt me-2 text-success"></i>Recent Sales Orders</span><a href="<?= BASE_URL ?>/sales" class="btn btn-sm btn-outline-success">View All</a></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>SO #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_sales as $s): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/sales/detail?id=<?= $s['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($s['so_number']) ?></a></td>
                        <td><?= htmlspecialchars($s['customer_name']) ?></td>
                        <td>&#8369;<?= number_format($s['total_amount'],2) ?></td>
                        <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                        <td class="text-muted small"><?= date('M d', strtotime($s['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_sales)): ?><tr><td colspan="5" class="text-center text-muted py-3">No orders yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="col-lg-6">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-clock-history me-2 text-warning"></i>Pending Approvals</span><a href="<?= BASE_URL ?>/approvals" class="btn btn-sm btn-outline-warning">View All</a></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Module</th><th>Title</th><th>Requested By</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_approvals as $a): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($a['module']) ?></span></td>
                        <td class="small"><?= htmlspecialchars(mb_strimwidth($a['title']??'',0,35,'…')) ?></td>
                        <td class="small"><?= htmlspecialchars($a['requester']) ?></td>
                        <td class="text-muted small"><?= date('M d', strtotime($a['created_at'])) ?></td>
                        <td><a href="<?= BASE_URL ?>/approvals/detail?id=<?= $a['id'] ?>" class="btn btn-xs btn-outline-primary">Review</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_approvals)): ?><tr><td colspan="5" class="text-center text-muted py-3">No pending approvals.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-lg-6">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-trophy me-2 text-warning"></i>Top Products (30 days)</span></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($top_products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= number_format($p['qty_sold'],2) ?></td>
                        <td>&#8369;<?= number_format($p['revenue'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($top_products)): ?><tr><td colspan="3" class="text-center text-muted py-3">No sales data.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Net Income Summary -->
    <div class="col-lg-6">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-cash-stack me-2 text-success"></i>This Month Summary</span><a href="<?= BASE_URL ?>/reports" class="btn btn-sm btn-outline-secondary">Full Report</a></div>
            <div class="p-3">
                <?php
                $net = $stats['monthly_revenue'] - $stats['monthly_expenses'];
                $margin = $stats['monthly_revenue'] > 0 ? round(($net / $stats['monthly_revenue']) * 100, 1) : 0;
                ?>
                <div class="row g-3 text-center mb-3">
                    <div class="col-4"><div class="p-2 bg-success bg-opacity-10 rounded"><div class="fw-bold text-success">&#8369;<?= number_format($stats['monthly_revenue'],0) ?></div><div class="small text-muted">Revenue</div></div></div>
                    <div class="col-4"><div class="p-2 bg-danger bg-opacity-10 rounded"><div class="fw-bold text-danger">&#8369;<?= number_format($stats['monthly_expenses'],0) ?></div><div class="small text-muted">Expenses</div></div></div>
                    <div class="col-4"><div class="p-2 bg-primary bg-opacity-10 rounded"><div class="fw-bold text-primary">&#8369;<?= number_format($net,0) ?></div><div class="small text-muted">Net Income</div></div></div>
                </div>
                <div class="text-muted small mb-1">Profit Margin: <?= $margin ?>%</div>
                <div class="progress" style="height:12px">
                    <div class="progress-bar bg-<?= $margin >= 20 ? 'success' : ($margin >= 0 ? 'warning' : 'danger') ?>" style="width:<?= max(0,min(100,$margin)) ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL    = '<?= BASE_URL ?>';
const chartData   = <?= json_encode($chart_data) ?>;
const invData     = <?= json_encode($inventory_chart) ?>;
const prodData    = <?= json_encode($production_chart) ?>;

// Build lookup: { "2026-4": 1234, "2026-4-15": 500, ... }
const salesRaw = <?= json_encode($chart_data) ?>;
const salesLookup = {};
salesRaw.forEach(d => { salesLookup[d.yr + '-' + d.mo] = parseFloat(d.total); });

// Daily data from controller
const salesDaily = <?= json_encode($chart_data_daily ?? []) ?>;
const dailyLookup = {}; // "2026-4-1": total
salesDaily.forEach(d => { dailyLookup[d.yr + '-' + d.mo + '-' + d.day] = parseFloat(d.total); });

const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
let salesChartInst = null;

function renderSalesChart() {
    const year  = parseInt(document.getElementById('salesYearFilter').value);
    const month = parseInt(document.getElementById('salesMonthFilter').value);

    let labels, values;

    if (month === 0) {
        // All months view
        labels = MONTHS;
        values = MONTHS.map((_, i) => salesLookup[year + '-' + (i + 1)] || 0);
    } else {
        // Daily view for selected month
        const daysInMonth = new Date(year, month, 0).getDate();
        labels = Array.from({length: daysInMonth}, (_, i) => i + 1);
        values = labels.map(d => dailyLookup[year + '-' + month + '-' + d] || 0);
    }

    if (salesChartInst) salesChartInst.destroy();
    salesChartInst = new Chart(document.getElementById('salesChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{ label: 'Sales &#8369;', data: values, backgroundColor: 'rgba(76,175,80,0.7)', borderColor: '#4caf50', borderWidth: 1, borderRadius: 4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => '&#8369;' + v.toLocaleString() } } }
        }
    });
}
renderSalesChart();

// Inventory doughnut
if (invData.length > 0) {
    new Chart(document.getElementById('inventoryChart'), {
        type: 'doughnut',
        data: {
            labels: invData.map(d => d.warehouse),
            datasets: [{ data: invData.map(d => parseFloat(d.total)), backgroundColor: ['#4caf50','#2196f3','#ff9800','#9c27b0','#f44336','#00bcd4'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
    });
} else {
    document.getElementById('inventoryChart').closest('.chart-container').innerHTML = '<div class="text-center text-muted pt-5">No warehouses found.</div>';
}

// Production pie
if (prodData.length > 0) {
    const prodColors = { planned:'#9e9e9e', planted:'#2196f3', growing:'#4caf50', harvested:'#ff9800', completed:'#8bc34a', cancelled:'#f44336' };
    new Chart(document.getElementById('productionChart'), {
        type: 'pie',
        data: {
            labels: prodData.map(d => d.status),
            datasets: [{ data: prodData.map(d => d.count), backgroundColor: prodData.map(d => prodColors[d.status] || '#ccc') }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
    });
} else {
    document.getElementById('productionChart').closest('.chart-container').innerHTML = '<div class="text-center text-muted pt-5">No production records yet.</div>';
}

// Real-time polling every 30s
function pollStats() {
    fetch(BASE_URL + '/dashboard/stats').then(r => r.json()).then(data => {
        const badge = document.getElementById('pendingBadge');
        if (badge) badge.textContent = data.pending_approvals > 0 ? data.pending_approvals + ' pending' : '';
    });
}
setInterval(pollStats, 30000);
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

