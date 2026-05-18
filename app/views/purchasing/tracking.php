<?php
ob_start();
$pageTitle  = 'Order Tracking';
$activeMenu = 'purchasing';
$statusColors = ['pending' => 'warning', 'approved' => 'success', 'delivered' => 'info', 'cancelled' => 'secondary'];
?>

<div class="mb-3">
    <a href="<?= BASE_URL ?>/purchasing" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Purchasing
    </a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-value"><?= $summary['total'] ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card warning">
            <div class="stat-value"><?= $summary['pending'] ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-value"><?= $summary['approved'] ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card info">
            <div class="stat-value"><?= $summary['delivered'] ?></div>
            <div class="stat-label">Delivered</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card danger">
            <div class="stat-value"><?= $summary['cancelled'] ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card purple">
            <div class="stat-value" style="font-size:1rem">&#8369;<?= number_format($summary['total_value'], 0) ?></div>
            <div class="stat-label">Total Value</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="table-card mb-3">
    <div class="p-3">
        <form method="GET" action="<?= BASE_URL ?>/purchasing/tracking" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['pending','approved','delivered','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label small">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-sm-3">
                <button class="btn btn-sm btn-primary w-100">Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-graph-up me-2 text-success"></i>Purchase Orders</span>
        <span class="badge bg-secondary"><?= count($orders) ?> results</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>PO #</th><th>Supplier</th><th>Order Date</th><th>Expected Delivery</th><th>Items</th><th>Total</th><th>Status</th><th>Approved By</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/purchasing/po-detail?id=<?= $o['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($o['po_number']) ?></a></td>
                <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                <td class="text-muted small">
                    <?php if ($o['expected_delivery']): ?>
                    <?php $overdue = $o['status'] === 'approved' && strtotime($o['expected_delivery']) < time(); ?>
                    <span class="<?= $overdue ? 'text-danger fw-semibold' : '' ?>">
                        <?= date('M d, Y', strtotime($o['expected_delivery'])) ?>
                        <?= $overdue ? ' <i class="bi bi-exclamation-triangle-fill"></i>' : '' ?>
                    </span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-center"><?= $o['item_count'] ?></td>
                <td>&#8369;<?= number_format($o['total_amount'], 2) ?></td>
                <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                <td class="text-muted small"><?= htmlspecialchars($o['approved_by_name'] ?? '—') ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/purchasing/po-detail?id=<?= $o['id'] ?>" class="btn btn-xs btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No orders found for the selected filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

