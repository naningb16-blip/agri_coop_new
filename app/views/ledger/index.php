<?php
ob_start();
$pageTitle  = 'Farmer Ledger';
$activeMenu = 'ledger';
$userRole   = $_SESSION['user']['role'] ?? '';
$canApprove = in_array($userRole, ['admin','manager','gm','finance_user']);
?>

<?php if ($userRole === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all ledger data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<!-- Summary -->
<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= $summary['active_accounts'] ?? 0 ?></div><div class="stat-label">Active Accounts</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value" style="font-size:1rem">&#8369;<?= number_format($summary['total_credits']??0,2) ?></div><div class="stat-label">Total Credits</div></div></div>
    <div class="col-md-4"><div class="stat-card danger"><div class="stat-value" style="font-size:1rem">&#8369;<?= number_format($summary['total_debits']??0,2) ?></div><div class="stat-label">Total Debits</div></div></div>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('Farmer Ledger')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<!-- Pending Withdrawals -->
<?php if (!empty($pendingWithdrawals) && $canApprove): ?>
<div class="table-card mb-3">
    <div class="card-header">
        <span><i class="bi bi-cash-coin me-2 text-warning"></i>Pending Withdrawals</span>
        <span class="badge bg-warning text-dark"><?= count($pendingWithdrawals) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Farmer</th><th>Amount</th><th>Reason</th><th>Requested</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pendingWithdrawals as $w): ?>
            <tr>
                <td><?= htmlspecialchars($w['farmer_name']) ?></td>
                <td class="fw-bold">&#8369;<?= number_format($w['amount'],2) ?></td>
                <td class="text-muted small"><?= htmlspecialchars(mb_strimwidth($w['reason']??'',0,50,'…')) ?></td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($w['created_at'])) ?></td>
                <td class="text-nowrap">
                    <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gm'])): ?>
                    <button class="btn btn-xs btn-success" onclick="actWithdrawal(<?= $w['id'] ?>,'approved')"><i class="bi bi-check-lg"></i> Approve</button>
                    <button class="btn btn-xs btn-danger"  onclick="actWithdrawal(<?= $w['id'] ?>,'rejected')"><i class="bi bi-x-lg"></i> Reject</button>
                    <?php else: ?>
                    <span class="text-muted small">Awaiting GM approval</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Farmer Accounts -->
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-people me-2 text-success"></i>Farmer Accounts</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Farmer</th><th>Phone</th><th>Farm Area</th><th>Total Credits</th><th>Total Debits</th><th>Balance</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($farmers as $f): ?>
            <?php $bal = $f['balance'] ?? 0; ?>
            <tr>
                <td><strong><?= htmlspecialchars($f['full_name']) ?></strong></td>
                <td class="text-muted small"><?= htmlspecialchars($f['phone']??'—') ?></td>
                <td class="text-muted small"><?= number_format($f['farm_area_ha']??0,2) ?> ha</td>
                <td class="text-success">&#8369;<?= number_format($f['total_credits']??0,2) ?></td>
                <td class="text-danger">&#8369;<?= number_format($f['total_debits']??0,2) ?></td>
                <td class="fw-bold <?= $bal >= 0 ? 'text-success' : 'text-danger' ?>">&#8369;<?= number_format($bal,2) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/ledger/farmer?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-primary">
                        <i class="bi bi-journal-text me-1"></i>Ledger
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($farmers)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No farmers yet. Add them in the Production module.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
function actWithdrawal(id, action) {
    if (!confirm(`${action === 'approved' ? 'Approve' : 'Reject'} this withdrawal?`)) return;
    const fd = new FormData(); fd.append('id', id); fd.append('action', action);
    fetch(BASE_URL + '/ledger/approve-withdrawal', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

