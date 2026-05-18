<?php
ob_start();
$pageTitle  = 'Reports & Analytics';
$activeMenu = 'reports';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <ul class="nav nav-tabs mb-0" id="reportTabs">
        <?php foreach ([['financial','bi-cash-stack','Financial'],['sales','bi-cart3','Sales'],['inventory','bi-boxes','Inventory'],['production','bi-flower1','Production'],['approvals','bi-check2-all','Approvals']] as [$key,$icon,$label]): ?>
        <li class="nav-item"><a class="nav-link <?= $key==='financial'?'active':'' ?>" href="#" onclick="showTab('<?= $key ?>');return false"><i class="bi <?= $icon ?> me-1"></i><?= $label ?></a></li>
        <?php endforeach; ?>
    </ul>
    <button class="btn btn-sm btn-outline-secondary no-print" onclick="printReport()">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>
<div class="mb-3"></div>

<!-- Financial -->
<div id="tab-financial">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="table-card p-3">
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label small">From</label><input type="date" id="finFrom" class="form-control form-control-sm" value="<?= date('Y-01-01') ?>"></div>
                    <div class="col-6"><label class="form-label small">To</label><input type="date" id="finTo" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
                </div>
                <button class="btn btn-success btn-sm w-100" onclick="loadFinancial()"><i class="bi bi-search me-1"></i>Generate</button>
            </div>
        </div>
        <div class="col-md-8">
            <div class="table-card p-3" id="finResults" style="display:none">
                <div class="row g-2 text-center mb-3">
                    <?php foreach ([['finRevenue','Revenue','success'],['finExpenses','Total Costs','danger'],['finNet','Net Income','primary']] as [$id,$label,$color]): ?>
                    <div class="col-4"><div class="p-2 bg-<?= $color ?> bg-opacity-10 rounded"><div class="fw-bold text-<?= $color ?> fs-5" id="<?= $id ?>">&#8369;0</div><div class="small text-muted"><?= $label ?></div></div></div>
                    <?php endforeach; ?>
                </div>
                <div class="chart-container mb-3"><canvas id="finChart"></canvas></div>
                <table class="table table-sm mb-0" id="finProducts"><thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead><tbody></tbody></table>
            </div>
        </div>
    </div>
</div>

<!-- Sales -->
<div id="tab-sales" style="display:none">
    <div class="mb-3 d-flex gap-2">
        <input type="date" id="salesFrom" class="form-control form-control-sm w-auto" value="<?= date('Y-m-01') ?>">
        <input type="date" id="salesTo"   class="form-control form-control-sm w-auto" value="<?= date('Y-m-d') ?>">
        <button class="btn btn-sm btn-primary" onclick="loadSales()">Load</button>
    </div>
    <div class="row g-3">
        <div class="col-md-8">
            <div class="table-card"><div class="card-header"><span>Orders</span></div>
            <div class="table-responsive"><table class="table table-sm mb-0" id="salesTable"><thead><tr><th>SO #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody><tr><td colspan="5" class="text-center text-muted py-3">Click Load</td></tr></tbody></table></div></div>
        </div>
        <div class="col-md-4">
            <div class="table-card"><div class="card-header"><span>Top Customers</span></div>
            <div class="table-responsive"><table class="table table-sm mb-0" id="custTable"><thead><tr><th>Customer</th><th>Orders</th><th>Total</th></tr></thead><tbody></tbody></table></div></div>
        </div>
    </div>
</div>

<!-- Inventory -->
<div id="tab-inventory" style="display:none">
    <div class="mb-3"><button class="btn btn-sm btn-outline-info" onclick="loadInventory()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button></div>
    <div class="table-card"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="invTable">
            <thead><tr><th>Product</th><th>Warehouse</th><th>Qty</th><th>Unit</th><th>Reorder Level</th><th>Status</th></tr></thead>
            <tbody><tr><td colspan="6" class="text-center text-muted py-3">Click Refresh</td></tr></tbody>
        </table>
    </div></div>
</div>

<!-- Production -->
<div id="tab-production" style="display:none">
    <div class="mb-3"><button class="btn btn-sm btn-outline-success" onclick="loadProduction()"><i class="bi bi-arrow-clockwise me-1"></i>Load</button></div>
    <div class="table-card"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="prodTable">
            <thead><tr><th>Farmer</th><th>Product</th><th>Location</th><th>Planted Area</th><th>Expected Yield</th><th>Actual Yield</th><th>Input Cost</th><th>Cost/Unit</th><th>Status</th></tr></thead>
            <tbody><tr><td colspan="9" class="text-center text-muted py-3">Click Load</td></tr></tbody>
        </table>
    </div></div>
</div>

<!-- Approvals -->
<div id="tab-approvals" style="display:none">
    <div class="mb-3"><button class="btn btn-sm btn-outline-warning" onclick="loadApprovals()"><i class="bi bi-arrow-clockwise me-1"></i>Load</button></div>
    <div class="table-card"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="approvalTable">
            <thead><tr><th>Module</th><th>Ref ID</th><th>Requested By</th><th>Reviewed By</th><th>Status</th><th>Date</th></tr></thead>
            <tbody><tr><td colspan="6" class="text-center text-muted py-3">Click Load</td></tr></tbody>
        </table>
    </div></div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let finChartInst = null;

function showTab(tab) {
    document.querySelectorAll('[id^=tab-]').forEach(el => el.style.display = 'none');
    document.getElementById('tab-' + tab).style.display = '';
    document.querySelectorAll('#reportTabs .nav-link').forEach((el, i) => {
        el.classList.toggle('active', ['financial','sales','inventory','production','approvals'][i] === tab);
    });
}

function fmt(n) { return '₱' + parseFloat(n).toLocaleString('en-PH', {minimumFractionDigits:2}); }

function loadFinancial() {
    const from = document.getElementById('finFrom').value, to = document.getElementById('finTo').value;
    document.getElementById('finResults').style.display = '';
    document.getElementById('finRevenue').textContent = '…';
    document.getElementById('finExpenses').textContent = '…';
    document.getElementById('finNet').textContent = '…';
    fetch(`${BASE_URL}/reports/financial?from=${from}&to=${to}`)
        .then(r => r.json())
        .then(d => {
            if (d.error) { showToast('Report error: ' + d.error, 'danger'); return; }
            const totalCosts = parseFloat(d.expenses||0) + parseFloat(d.payroll||0) + parseFloat(d.batchCosts||0) + parseFloat(d.inputCosts||0);
            const net = parseFloat(d.revenue||0) - totalCosts;
            document.getElementById('finRevenue').textContent  = fmt(d.revenue||0);
            document.getElementById('finExpenses').textContent = fmt(totalCosts);
            document.getElementById('finNet').textContent      = fmt(net);
            document.getElementById('finNet').className = 'fw-bold fs-5 text-' + (net >= 0 ? 'primary' : 'danger');

            if (finChartInst) finChartInst.destroy();
            const labels = (d.monthlySales||[]).map(m=>m.month);
            const values = (d.monthlySales||[]).map(m=>parseFloat(m.total));
            finChartInst = new Chart(document.getElementById('finChart'), {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Revenue', data: values, backgroundColor: 'rgba(76,175,80,0.7)', borderRadius: 4 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '₱'+v.toLocaleString() } } } }
            });

            document.querySelector('#finProducts tbody').innerHTML = (d.salesByProduct||[]).map(p =>
                `<tr><td>${p.name}</td><td>${parseFloat(p.qty_sold).toFixed(2)}</td><td>${fmt(p.revenue)}</td></tr>`
            ).join('') || '<tr><td colspan="3" class="text-muted text-center">No sales data</td></tr>';
        })
        .catch(e => showToast('Failed to load report. Check console.', 'danger'));
}

function loadSales() {
    const from = document.getElementById('salesFrom').value, to = document.getElementById('salesTo').value;
    fetch(`${BASE_URL}/reports/sales?from=${from}&to=${to}`).then(r=>r.json()).then(d => {
        document.querySelector('#salesTable tbody').innerHTML = d.orders.map(o =>
            `<tr><td><a href="${BASE_URL}/sales/detail?id=${o.id}">${o.so_number}</a></td><td>${o.customer_name}</td><td>${fmt(o.total_amount)}</td><td><span class="badge badge-${o.status}">${o.status}</span></td><td class="text-muted small">${new Date(o.order_date).toLocaleDateString()}</td></tr>`
        ).join('') || '<tr><td colspan="5" class="text-center text-muted">No data</td></tr>';
        document.querySelector('#custTable tbody').innerHTML = d.byCustomer.map(c =>
            `<tr><td>${c.name}</td><td>${c.orders}</td><td>${fmt(c.total)}</td></tr>`
        ).join('') || '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>';
    });
}

function loadInventory() {
    fetch(`${BASE_URL}/reports/inventory`).then(r=>r.json()).then(d => {
        document.querySelector('#invTable tbody').innerHTML = d.stock.map(r =>
            `<tr><td>${r.name}</td><td>${r.warehouse}</td><td>${parseFloat(r.quantity).toFixed(2)}</td><td>${r.unit}</td><td>${parseFloat(r.reorder_level).toFixed(2)}</td><td><span class="badge ${r.status==='Low'?'bg-danger':'bg-success'}">${r.status}</span></td></tr>`
        ).join('') || '<tr><td colspan="6" class="text-center text-muted">No data</td></tr>';
    });
}

function loadProduction() {
    fetch(`${BASE_URL}/reports/production`).then(r=>r.json()).then(rows => {
        document.querySelector('#prodTable tbody').innerHTML = rows.map(r =>
            `<tr><td>${r.farmer_name}</td><td>${r.product_name}</td><td class="small text-muted">${r.farm_location||'—'}</td><td>${parseFloat(r.planted_area_ha||0).toFixed(2)} ha</td><td>${parseFloat(r.expected_yield||0).toFixed(2)}</td><td class="text-success">${r.actual_yield?parseFloat(r.actual_yield).toFixed(2):'—'}</td><td>${fmt(r.total_cost)}</td><td>${r.cost_per_unit>0?fmt(r.cost_per_unit):'—'}</td><td><span class="badge bg-secondary">${r.status}</span></td></tr>`
        ).join('') || '<tr><td colspan="9" class="text-center text-muted">No data</td></tr>';
    });
}

function loadApprovals() {
    fetch(`${BASE_URL}/reports/approvals`).then(r=>r.json()).then(rows => {
        document.querySelector('#approvalTable tbody').innerHTML = rows.map(r =>
            `<tr><td><span class="badge bg-secondary">${r.module}</span></td><td>#${r.reference_id}</td><td>${r.requester}</td><td>${r.reviewer||'—'}</td><td><span class="badge badge-${r.status}">${r.status}</span></td><td class="text-muted small">${new Date(r.requested_at).toLocaleDateString()}</td></tr>`
        ).join('') || '<tr><td colspan="6" class="text-center text-muted">No data</td></tr>';
    });
}

// Auto-load financial on page open
loadFinancial();

function printReport() {
    // Find the active tab
    const activeTab = document.querySelector('[id^="tab-"]:not([style*="display: none"]):not([style*="display:none"])');
    if (!activeTab) return;

    const tabName = activeTab.id.replace('tab-', '');
    const tabLabels = {financial:'Financial Report',sales:'Sales Report',inventory:'Inventory Report',production:'Production Report',approvals:'Approvals Report'};
    const title = tabLabels[tabName] || 'Report';

    // Convert chart canvas to image if present
    const canvas = activeTab.querySelector('canvas');
    if (canvas) {
        const img = document.createElement('img');
        img.src = canvas.toDataURL('image/png');
        img.style.cssText = 'width:100%;max-height:300px;object-fit:contain;margin-bottom:12px';
        img.id = '__chartImg';
        canvas.parentNode.insertBefore(img, canvas);
        canvas.style.display = 'none';
    }

    // Build print window
    const styles = Array.from(document.styleSheets).map(ss => {
        try { return Array.from(ss.cssRules).map(r => r.cssText).join('\n'); } catch(e) { return ''; }
    }).join('\n');

    const win = window.open('', '_blank', 'width=900,height=700');
    win.document.write(`<!DOCTYPE html><html><head>
        <meta charset="UTF-8">
        <title>${title}</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        <style>
            body { font-family: 'Segoe UI', sans-serif; padding: 24px; background:#fff; }
            .print-header { display:block; border-bottom:2px solid #4caf50; padding-bottom:12px; margin-bottom:20px; }
            .no-print, .btn, .form-control, .nav-tabs { display:none !important; }
            .table-card { box-shadow:none; border:1px solid #ddd; border-radius:6px; overflow:hidden; margin-bottom:16px; }
            .table th { font-size:0.78rem; text-transform:uppercase; letter-spacing:0.5px; color:#666; }
            .badge-pending  { background:#ffc107; color:#000; padding:2px 6px; border-radius:4px; font-size:0.75rem; }
            .badge-approved { background:#198754; color:#fff; padding:2px 6px; border-radius:4px; font-size:0.75rem; }
            .badge-rejected { background:#dc3545; color:#fff; padding:2px 6px; border-radius:4px; font-size:0.75rem; }
            .badge { padding:2px 6px; border-radius:4px; font-size:0.75rem; }
            .bg-success { background:#198754 !important; color:#fff; }
            .bg-danger  { background:#dc3545 !important; color:#fff; }
            .bg-secondary { background:#6c757d !important; color:#fff; }
            .text-success { color:#198754 !important; }
            .text-danger  { color:#dc3545 !important; }
            .text-muted   { color:#666 !important; }
            .fw-bold { font-weight:700; }
            .fs-5 { font-size:1.1rem; }
            .p-2 { padding:8px; }
            .rounded { border-radius:6px; }
            .bg-success.bg-opacity-10 { background:rgba(25,135,84,0.1) !important; }
            .bg-danger.bg-opacity-10  { background:rgba(220,53,69,0.1) !important; }
            .bg-primary.bg-opacity-10 { background:rgba(13,110,253,0.1) !important; }
            .text-primary { color:#0d6efd !important; }
            img.__chartImg { width:100%; max-height:300px; object-fit:contain; }
            @media print { body { padding:0; } }
        </style>
    </head><body>
        <div class="print-header d-flex justify-content-between align-items-start">
            <div>
                <h4 class="mb-0" style="color:#1a2e1a">${title}</h4>
                <div class="text-muted small">Generated: ${new Date().toLocaleString('en-PH')}</div>
            </div>
            <div style="font-size:0.8rem;color:#666">AgriCoop ERP</div>
        </div>
        ${activeTab.innerHTML}
    </body></html>`);
    win.document.close();

    // Restore canvas
    if (canvas) {
        canvas.style.display = '';
        const img = document.getElementById('__chartImg');
        if (img) img.remove();
    }

    win.focus();
    setTimeout(() => { win.print(); }, 600);
}
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

