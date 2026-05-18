<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Report — <?= htmlspecialchars($from) ?> to <?= htmlspecialchars($to) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; font-size: 13px; }
        .report-header { border-bottom: 2px solid #198754; padding-bottom: 12px; margin-bottom: 20px; }
        .section-title { background: #f8f9fa; padding: 6px 12px; font-weight: 700; margin: 16px 0 8px; border-left: 4px solid #198754; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="p-4">
<div class="no-print mb-3">
    <button onclick="window.print()" class="btn btn-sm btn-primary">Print</button>
    <a href="<?= BASE_URL ?>/finance" class="btn btn-sm btn-outline-secondary ms-2">Back to Finance</a>
</div>

<div class="report-header d-flex justify-content-between">
    <div><h4 class="text-success fw-bold mb-0"><?= APP_NAME ?></h4><div class="text-muted">Financial Report</div></div>
    <div class="text-end"><div class="fw-bold">Period: <?= date('M d, Y', strtotime($from)) ?> – <?= date('M d, Y', strtotime($to)) ?></div><div class="text-muted small">Generated: <?= date('M d, Y H:i') ?></div></div>
</div>

<!-- P&L Summary -->
<div class="section-title">Profit & Loss Summary</div>
<table class="table table-sm table-bordered mb-0" style="max-width:400px">
    <tr><td>Sales Revenue</td><td class="text-end text-success fw-bold">&#8369;<?= number_format($summary['salesRevenue'] ?? 0, 2) ?></td></tr>
    <tr class="table-danger"><td>Expenses</td><td class="text-end">&#8369;<?= number_format($summary['expenses'] ?? 0, 2) ?></td></tr>
    <tr class="table-warning"><td>Payroll</td><td class="text-end">&#8369;<?= number_format($summary['payroll'] ?? 0, 2) ?></td></tr>
    <tr class="table-info"><td>Purchases</td><td class="text-end">&#8369;<?= number_format($summary['purchases'] ?? 0, 2) ?></td></tr>
    <tr class="table-secondary fw-bold"><td>Total Costs</td><td class="text-end">&#8369;<?= number_format($summary['totalCosts'] ?? 0, 2) ?></td></tr>
    <tr class="table-light fw-bold"><td>Net Income</td><td class="text-end <?= ($summary['net'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">&#8369;<?= number_format($summary['net'] ?? 0, 2) ?></td></tr>
</table>

<!-- Receipts -->
<div class="section-title">Cash Receipts (<?= count($receipts) ?>)</div>
<table class="table table-sm table-bordered mb-0">
    <thead class="table-light"><tr><th>Receipt #</th><th>Date</th><th>Type</th><th>Payer</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($receipts as $r): ?>
    <tr><td><?= htmlspecialchars($r['receipt_number']??'#'.$r['id']) ?></td><td><?= date('M d, Y', strtotime($r['receipt_date'])) ?></td><td><?= ucfirst($r['reference_type']) ?></td><td><?= htmlspecialchars($r['payer_name']??'—') ?></td><td><?= ucwords(str_replace('_',' ',$r['payment_method'])) ?></td><td class="text-end">&#8369;<?= number_format($r['amount'],2) ?></td></tr>
    <?php endforeach; ?>
    <tr class="table-light fw-bold"><td colspan="5" class="text-end">Total</td><td class="text-end">&#8369;<?= number_format(array_sum(array_column($receipts,'amount')),2) ?></td></tr>
    </tbody>
</table>

<!-- Expenses -->
<div class="section-title">Expenses (<?= count($expenses) ?>)</div>
<table class="table table-sm table-bordered mb-0">
    <thead class="table-light"><tr><th>Date</th><th>Category</th><th>Description</th><th>Method</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($expenses as $e): ?>
    <tr><td><?= date('M d, Y', strtotime($e['expense_date'])) ?></td><td><?= htmlspecialchars($e['category']??'—') ?></td><td><?= htmlspecialchars(mb_strimwidth($e['description']??'',0,50,'…')) ?></td><td><?= ucwords(str_replace('_',' ',$e['payment_method']??'cash')) ?></td><td><?= ucfirst($e['status']) ?></td><td class="text-end">&#8369;<?= number_format($e['amount'],2) ?></td></tr>
    <?php endforeach; ?>
    <tr class="table-light fw-bold"><td colspan="5" class="text-end">Approved Total</td><td class="text-end">&#8369;<?= number_format(array_sum(array_map(fn($e)=>$e['status']==='approved'?(float)$e['amount']:0,$expenses)),2) ?></td></tr>
    </tbody>
</table>

<!-- Payroll -->
<div class="section-title">Payroll (<?= count($payrolls) ?>)</div>
<table class="table table-sm table-bordered mb-0">
    <thead class="table-light"><tr><th>Employee</th><th>Period</th><th>Basic Pay</th><th>Deductions</th><th>Bonuses</th><th>Status</th><th class="text-end">Net Pay</th></tr></thead>
    <tbody>
    <?php foreach ($payrolls as $p): ?>
    <tr><td><?= htmlspecialchars($p['full_name']) ?></td><td><?= date('M d', strtotime($p['period_start'])) ?>–<?= date('M d, Y', strtotime($p['period_end'])) ?></td><td>&#8369;<?= number_format($p['basic_pay'],2) ?></td><td>&#8369;<?= number_format($p['deductions'],2) ?></td><td>&#8369;<?= number_format($p['bonuses'],2) ?></td><td><?= ucfirst($p['status']) ?></td><td class="text-end fw-bold">&#8369;<?= number_format($p['net_pay'],2) ?></td></tr>
    <?php endforeach; ?>
    <tr class="table-light fw-bold"><td colspan="6" class="text-end">Total Net Pay</td><td class="text-end">&#8369;<?= number_format(array_sum(array_column($payrolls,'net_pay')),2) ?></td></tr>
    </tbody>
</table>

<div class="text-center text-muted mt-4" style="font-size:10px"><?= APP_NAME ?> &bull; Generated <?= date('M d, Y H:i:s') ?></div>
</body>
</html>

