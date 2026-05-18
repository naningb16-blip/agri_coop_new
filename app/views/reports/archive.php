<?php
ob_start();
$pageTitle  = 'Transaction Archives';
$activeMenu = 'archive';

$tabs = [
    ['key' => 'sales',      'icon' => 'bi-cart3',           'label' => 'Sales'],
    ['key' => 'purchasing', 'icon' => 'bi-bag-check',        'label' => 'Purchasing'],
    ['key' => 'logistics',  'icon' => 'bi-truck',            'label' => 'Logistics'],
    ['key' => 'production', 'icon' => 'bi-flower1',          'label' => 'Production'],
    ['key' => 'processing', 'icon' => 'bi-gear',             'label' => 'Processing'],
    ['key' => 'qa',         'icon' => 'bi-patch-check',      'label' => 'QA'],
    ['key' => 'finance',    'icon' => 'bi-cash-stack',       'label' => 'Finance'],
    ['key' => 'inventory',  'icon' => 'bi-boxes',            'label' => 'Inventory'],
    ['key' => 'ledger',     'icon' => 'bi-journal-text',     'label' => 'Ledger'],
    ['key' => 'hr',         'icon' => 'bi-people',           'label' => 'HR'],
];
$activeTab = $_GET['tab'] ?? 'sales';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-archive me-2 text-secondary"></i>Transaction Archives</h5>
    <button class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<ul class="nav nav-tabs mb-3 flex-wrap" id="archiveTabs">
    <?php foreach ($tabs as $t): ?>
    <li class="nav-item">
        <a class="nav-link <?= $t['key'] === $activeTab ? 'active' : '' ?>"
           href="<?= BASE_URL ?>/reports/archive?tab=<?= $t['key'] ?>">
            <i class="bi <?= $t['icon'] ?> me-1"></i><?= $t['label'] ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Date filter bar -->
<div class="table-card mb-3 p-3">
    <form method="GET" action="<?= BASE_URL ?>/reports/archive" class="row g-2 align-items-end">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
        <div class="col-auto">
            <label class="form-label small mb-1">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['from'] ?? date('Y-01-01')) ?>">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['to'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>
</div>

<div class="table-card">
    <div class="table-responsive">

    <?php if ($activeTab === 'sales'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>SO #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/sales/detail?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['so_number']) ?></a></td>
                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                <td>&#8369;<?= number_format($r['total_amount'], 2) ?></td>
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td class="text-muted small"><?= $r['order_date'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="5" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'purchasing'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>PO #</th><th>Supplier</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/purchasing/po-detail?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['po_number']) ?></a></td>
                <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                <td>&#8369;<?= number_format($r['total_amount'], 2) ?></td>
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td class="text-muted small"><?= $r['order_date'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="5" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'logistics'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Ref #</th><th>Type</th><th>Origin</th><th>Destination</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/logistics/detail?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['reference_number'] ?? '#'.$r['id']) ?></a></td>
                <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_',' ',$r['reference_type'])) ?></span></td>
                <td class="small"><?= htmlspecialchars($r['origin'] ?? '—') ?></td>
                <td class="small"><?= htmlspecialchars($r['destination'] ?? '—') ?></td>
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
                <td class="text-muted small"><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'production'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Farmer</th><th>Product</th><th>Planted Area</th><th>Actual Yield</th><th>Status</th><th>Harvest Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/production/detail?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['farmer_name']) ?></a></td>
                <td><?= htmlspecialchars($r['product_name']) ?></td>
                <td><?= number_format($r['planted_area_ha'] ?? 0, 2) ?> ha</td>
                <td><?= $r['actual_yield'] ? number_format($r['actual_yield'], 2) : '—' ?></td>
                <td><span class="badge bg-secondary"><?= ucfirst($r['status']) ?></span></td>
                <td class="text-muted small"><?= $r['harvest_date'] ?? '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'processing'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Batch #</th><th>Product</th><th>Type</th><th>Input Qty</th><th>Output Qty</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/processing/detail?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['batch_number']) ?></a></td>
                <td><?= htmlspecialchars($r['product_name']) ?></td>
                <td><span class="badge bg-secondary"><?= ucfirst($r['process_type']) ?></span></td>
                <td><?= number_format($r['input_quantity'], 2) ?></td>
                <td><?= $r['output_quantity'] ? number_format($r['output_quantity'], 2) : '—' ?></td>
                <td><span class="badge badge-<?= $r['status'] === 'completed' ? 'approved' : $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td class="text-muted small"><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'qa'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Ref Type</th><th>Product</th><th>Inspector</th><th>Approved Qty</th><th>Rejected Qty</th><th>Result</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/qa/detail?id=<?= $r['id'] ?>"><span class="badge bg-secondary"><?= ucfirst(str_replace('_',' ',$r['reference_type'])) ?></span></a></td>
                <td><?= htmlspecialchars($r['product_name']) ?></td>
                <td><?= htmlspecialchars($r['inspector_name']) ?></td>
                <td><?= number_format($r['approved_qty'] ?? 0, 2) ?></td>
                <td class="text-danger"><?= number_format($r['rejected_qty'] ?? 0, 2) ?></td>
                <td><span class="badge badge-<?= $r['result'] === 'passed' ? 'approved' : ($r['result'] === 'failed' ? 'rejected' : 'pending') ?>"><?= ucfirst($r['result']) ?></span></td>
                <td class="text-muted small"><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'finance'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Type</th><th>Description</th><th>Amount</th><th>Method/Category</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><span class="badge bg-<?= $r['_type'] === 'receipt' ? 'success' : ($r['_type'] === 'expense' ? 'danger' : 'primary') ?>"><?= ucfirst($r['_type']) ?></span></td>
                <td class="small"><?= htmlspecialchars(substr($r['description'] ?? $r['notes'] ?? '—', 0, 60)) ?></td>
                <td>&#8369;<?= number_format($r['amount'], 2) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($r['method_or_category'] ?? '—') ?></td>
                <td><span class="badge badge-<?= ($r['status'] ?? 'pending') === 'approved' ? 'approved' : (($r['status'] ?? '') === 'paid' ? 'approved' : 'pending') ?>"><?= ucfirst($r['status'] ?? 'recorded') ?></span></td>
                <td class="text-muted small"><?= $r['txn_date'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'inventory'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Product</th><th>Warehouse</th><th>Type</th><th>Quantity</th><th>Reference</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['product_name']) ?></td>
                <td><?= htmlspecialchars($r['warehouse_name']) ?></td>
                <td><span class="badge bg-<?= $r['type'] === 'in' ? 'success' : 'danger' ?>"><?= strtoupper($r['type']) ?></span></td>
                <td><?= number_format($r['quantity'], 2) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($r['reference_type'] ?? '—') ?> <?= $r['reference_id'] ? '#'.$r['reference_id'] : '' ?></td>
                <td class="text-muted small"><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'ledger'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Farmer</th><th>Type</th><th>Category</th><th>Amount</th><th>Notes</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/ledger/farmer?id=<?= $r['farmer_id'] ?>"><?= htmlspecialchars($r['farmer_name']) ?></a></td>
                <td><span class="badge bg-<?= $r['type'] === 'credit' ? 'success' : 'danger' ?>"><?= ucfirst($r['type']) ?></span></td>
                <td class="small text-muted"><?= htmlspecialchars($r['category'] ?? '—') ?></td>
                <td>&#8369;<?= number_format($r['amount'], 2) ?></td>
                <td class="small"><?= htmlspecialchars(substr($r['notes'] ?? '', 0, 60)) ?></td>
                <td class="text-muted small"><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($activeTab === 'hr'): ?>
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Employee</th><th>Department</th><th>Period</th><th>Basic Pay</th><th>Deductions</th><th>Net Pay</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($r['dept_name'] ?? '—') ?></td>
                <td class="small text-muted"><?= $r['period_start'] ?> – <?= $r['period_end'] ?></td>
                <td>&#8369;<?= number_format($r['basic_pay'], 2) ?></td>
                <td class="text-danger">-&#8369;<?= number_format($r['deductions'], 2) ?></td>
                <td class="fw-bold">&#8369;<?= number_format($r['net_pay'], 2) ?></td>
                <td><span class="badge badge-<?= $r['status'] === 'paid' ? 'approved' : 'pending' ?>"><?= ucfirst($r['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?><tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    </div>
    <div class="px-3 py-2 text-muted small border-top">
        <?= count($data) ?> record(s) found
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
