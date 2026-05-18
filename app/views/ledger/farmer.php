<?php
ob_start();
$pageTitle  = 'Ledger: ' . $farmer['full_name'];
$activeMenu = 'ledger';
$userRole   = $_SESSION['user']['role'] ?? '';
$canManage  = in_array($userRole, ['admin','manager','gm','finance_user']);
$currentBal = (float)($balance['balance'] ?? 0);
$tc = ['credit'=>'success','debit'=>'danger'];
$cc = ['sale'=>'success','withdrawal'=>'warning','adjustment'=>'info','advance'=>'primary','other'=>'secondary'];
?>

<?php if ($userRole === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all ledger data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>/ledger" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<!-- Farmer Info + Balance -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="table-card p-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="mb-0"><?= htmlspecialchars($farmer['full_name']) ?></h5>
                    <div class="text-muted small"><?= htmlspecialchars($farmer['phone']??'') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($farmer['address']??'') ?></div>
                </div>
                <span class="badge bg-<?= $farmer['status']==='active'?'success':'secondary' ?>"><?= ucfirst($farmer['status']) ?></span>
            </div>
            <div class="text-center p-3 rounded <?= $currentBal >= 0 ? 'bg-success' : 'bg-danger' ?> bg-opacity-10">
                <div class="text-muted small">Current Balance</div>
                <div class="fw-bold fs-3 <?= $currentBal >= 0 ? 'text-success' : 'text-danger' ?>">
                    &#8369;<?= number_format($currentBal, 2) ?>
                </div>
            </div>
            <?php if ($canManage && $userRole !== 'gm'): ?>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-sm btn-success flex-fill" data-bs-toggle="modal" data-bs-target="#creditModal">
                    <i class="bi bi-plus-circle me-1"></i>Credit
                </button>
                <button class="btn btn-sm btn-outline-danger flex-fill" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                    <i class="bi bi-cash-coin me-1"></i>Withdrawal
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Withdrawals -->
    <div class="col-md-8">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-cash-coin me-2 text-warning"></i>Withdrawal Requests</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Date</th><th>Amount</th><th>Reason</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($withdrawals as $w): ?>
                    <tr>
                        <td class="text-muted small"><?= date('M d, Y', strtotime($w['created_at'])) ?></td>
                        <td class="fw-bold">&#8369;<?= number_format($w['amount'],2) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars(mb_strimwidth($w['reason']??'',0,40,'…')) ?></td>
                        <td><span class="badge badge-<?= $w['status']==='released'?'approved':$w['status'] ?>"><?= ucfirst($w['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($withdrawals)): ?><tr><td colspan="4" class="text-center text-muted py-3">No withdrawals.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Ledger Table -->
<div class="table-card">
    <div class="card-header"><span><i class="bi bi-journal-text me-2"></i>Transaction History</span><span class="badge bg-secondary"><?= count($entries) ?> entries</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Description</th><th>Reference</th><th class="text-end">Credit</th><th class="text-end">Debit</th><th class="text-end">Balance</th><th>By</th></tr></thead>
            <tbody>
            <?php foreach ($entries as $e): ?>
            <tr>
                <td class="text-muted small text-nowrap"><?= date('M d, Y', strtotime($e['transaction_date'])) ?></td>
                <td><span class="badge bg-<?= $tc[$e['type']]??'secondary' ?>"><?= ucfirst($e['type']) ?></span></td>
                <td><span class="badge bg-<?= $cc[$e['category']]??'secondary' ?> small"><?= ucfirst($e['category']) ?></span></td>
                <td class="small"><?= htmlspecialchars($e['description']??'') ?></td>
                <td class="text-muted small"><?= htmlspecialchars($e['reference_type']??'') ?> <?= $e['reference_id']?'#'.$e['reference_id']:'' ?></td>
                <td class="text-end text-success"><?= $e['type']==='credit' ? '&#8369;'.number_format($e['amount'],2) : '' ?></td>
                <td class="text-end text-danger"><?= $e['type']==='debit'  ? '&#8369;'.number_format($e['amount'],2) : '' ?></td>
                <td class="text-end fw-semibold <?= $e['running_balance']>=0?'text-success':'text-danger' ?>">&#8369;<?= number_format($e['running_balance'],2) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($e['recorded_by_name']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($entries)): ?><tr><td colspan="9" class="text-center text-muted py-4">No transactions yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Credit Modal -->
<?php if ($canManage && $userRole !== 'gm'): ?>
<div class="modal fade" id="creditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle text-success me-2"></i>Add Credit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/ledger/entry" method="POST" data-ajax data-reload="true">
                <input type="hidden" name="farmer_id" value="<?= $farmer['id'] ?>">
                <input type="hidden" name="type" value="credit">
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="form-label">Category</label>
                        <select name="category" class="form-select"><option value="sale">Sale</option><option value="advance">Advance</option><option value="adjustment">Adjustment</option><option value="other">Other</option></select></div>
                    <div class="col-md-6"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                    <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-12"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Add Credit</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cash-coin text-warning me-2"></i>Request Withdrawal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/ledger/withdrawal" method="POST" data-ajax data-reload="true">
                <input type="hidden" name="farmer_id" value="<?= $farmer['id'] ?>">
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <div class="alert alert-info small mb-0">Available balance: <strong>&#8369;<?= number_format($currentBal,2) ?></strong></div>
                    </div>
                    <div class="col-12"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" step="0.01" max="<?= $currentBal ?>" required></div>
                    <div class="col-12"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning text-dark">Submit Request</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const BASE_URL = '<?= BASE_URL ?>';
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

