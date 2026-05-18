<?php
ob_start();
$pageTitle  = 'SO: ' . $order['so_number'];
$activeMenu = 'sales';
$sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','skipped'=>'secondary','processing'=>'info','delivered'=>'success','cancelled'=>'secondary'];
?>
<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>/sales" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <?php if ($order['invoice_number']): ?>
    <a href="<?= BASE_URL ?>/sales/invoice-print?id=<?= $order['id'] ?>" target="_blank" class="btn btn-sm btn-primary">
        <i class="bi bi-printer me-1"></i>Print Invoice
    </a>
    <?php endif; ?>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-cart3 me-2"></i><?= htmlspecialchars($order['so_number']) ?></span>
                <span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
            </div>
            <div class="p-3">
                <?php if ($order['invoice_number']): ?>
                <div class="alert alert-info mb-3">
                    <strong><i class="bi bi-file-earmark-text me-2"></i>Invoice Number:</strong> <?= htmlspecialchars($order['invoice_number']) ?>
                    <span class="text-muted ms-2">| Date: <?= date('M d, Y', strtotime($order['invoice_date'])) ?></span>
                </div>
                <?php endif; ?>
                <div class="row g-2 mb-3">
                    <div class="col-sm-6"><div class="text-muted small">Customer</div><div class="fw-semibold"><?= htmlspecialchars($order['customer_name']) ?></div><div class="text-muted small"><?= htmlspecialchars($order['customer_phone']??'') ?></div></div>
                    <div class="col-sm-3"><div class="text-muted small">Order Date</div><div><?= date('M d, Y', strtotime($order['order_date'])) ?></div></div>
                    <div class="col-sm-3"><div class="text-muted small">Delivery Date</div><div><?= $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : '—' ?></div></div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-sm-4"><div class="text-muted small">Payment Type</div><span class="badge bg-<?= ($order['payment_type']??'cash')==='cash'?'success':(($order['payment_type']??'')==='charge'?'warning':'info') ?>"><?= ucfirst($order['payment_type']??'cash') ?></span></div>
                    <div class="col-sm-4"><div class="text-muted small">Payment Status</div><span class="badge bg-<?= ($order['payment_status']??'unpaid')==='paid'?'success':(($order['payment_status']??'')==='partial'?'warning':'secondary') ?>"><?= ucfirst($order['payment_status']??'unpaid') ?></span></div>
                    <div class="col-sm-4"><div class="text-muted small">Amount Paid</div><div class="fw-semibold">&#8369;<?= number_format($order['amount_paid']??0,2) ?> / &#8369;<?= number_format($order['total_amount'],2) ?></div></div>
                </div>
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit</th><th>Unit Price</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= number_format($item['quantity'],2) ?></td>
                        <td><?= htmlspecialchars($item['unit']??'') ?></td>
                        <td>&#8369;<?= number_format($item['unit_price'],2) ?></td>
                        <td>&#8369;<?= number_format($item['total_price'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr class="table-light fw-bold"><td colspan="5" class="text-end">Total</td><td>&#8369;<?= number_format($order['total_amount'],2) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <!-- Payment Recording Section -->
        <?php if ($order['status'] === 'delivered' && ($order['payment_status'] ?? 'unpaid') !== 'paid' && $_SESSION['user']['role'] !== 'gm'): ?>
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-cash-coin me-2 text-success"></i>Record Payment</span>
                <span class="badge bg-warning text-dark">Balance: &#8369;<?= number_format($order['total_amount'] - ($order['amount_paid']??0), 2) ?></span>
            </div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" id="paymentAmount" class="form-control" step="0.01" min="0.01" max="<?= $order['total_amount'] - ($order['amount_paid']??0) ?>" placeholder="Enter amount">
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select id="paymentMethod" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input type="date" id="paymentDate" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="paymentNotes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
                <button class="btn btn-success w-100" onclick="recordPayment()">
                    <i class="bi bi-check-circle me-1"></i>Record Payment
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($approval && $approval['status'] === 'pending' && $order['status'] !== 'rejected'): ?>
        <!-- GM Approval Section -->
        <?php if ($_SESSION['user']['role'] === 'gm'): ?>
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-check2-square me-2 text-warning"></i>GM Approval Required</span>
                <span class="badge bg-warning text-dark">Pending</span>
            </div>
            <div class="p-3">
                <div class="mb-3">
                    <strong>Request:</strong> <?= htmlspecialchars($approval['title']) ?><br>
                    <small class="text-muted"><?= htmlspecialchars($approval['description']) ?></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea id="gmRemarks" class="form-control" rows="3" placeholder="Optional remarks..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success flex-fill" onclick="gmApprove('approved')">
                        <i class="bi bi-check-lg me-1"></i>Approve
                    </button>
                    <button class="btn btn-danger flex-fill" onclick="gmApprove('rejected')">
                        <i class="bi bi-x-lg me-1"></i>Reject
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Status for Non-GM Users -->
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-hourglass-split me-2 text-warning"></i>Approval Status</span>
                <span class="badge bg-warning text-dark">Awaiting GM Approval</span>
            </div>
            <div class="p-3">
                <div class="text-center py-3">
                    <i class="bi bi-clock-history text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">This request is waiting for General Manager approval.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php elseif ($approval): ?>
        <!-- Completed Approval Status -->
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-check-circle me-2"></i>Approval Status</span>
                <span class="badge badge-<?= $approval['status'] === 'approved' ? 'success' : 'danger' ?>"><?= ucfirst($approval['status']) ?></span>
            </div>
            <div class="p-3">
                <div class="text-center py-2">
                    <?php if ($approval['status'] === 'approved'): ?>
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                        <p class="text-success mt-2 mb-0">Approved by General Manager</p>
                    <?php else: ?>
                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 2rem;"></i>
                        <p class="text-danger mt-2 mb-0">Rejected by General Manager</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Receipts Section -->
        <?php if (!empty($receipts)): ?>
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-receipt me-2 text-success"></i>Payment Receipts</span>
                <span class="badge bg-success"><?= count($receipts) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($receipts as $receipt): ?>
                    <tr>
                        <td class="small fw-semibold"><?= htmlspecialchars($receipt['receipt_number']) ?></td>
                        <td class="text-muted small"><?= date('M d, Y', strtotime($receipt['receipt_date'])) ?></td>
                        <td class="fw-semibold">&#8369;<?= number_format($receipt['amount'], 2) ?></td>
                        <td class="small"><?= ucwords(str_replace('_', ' ', $receipt['payment_method'])) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/finance/receipt-print?id=<?= $receipt['id'] ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary"
                               title="Print Receipt">
                                <i class="bi bi-printer"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="text-end fw-semibold">Total Paid:</td>
                            <td colspan="3" class="fw-bold text-success">&#8369;<?= number_format(array_sum(array_column($receipts, 'amount')), 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Audit Log -->
        <?php if ($approval && !empty($audit)): ?>
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-journal-text me-2"></i>Approval History</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($audit as $log): ?>
                    <tr>
                        <td class="text-muted small text-nowrap"><?= date('M d H:i', strtotime($log['created_at'])) ?></td>
                        <td class="small"><?= htmlspecialchars($log['actor_name']) ?></td>
                        <td><span class="badge bg-<?= $sc[$log['action']]??'secondary' ?>"><?= ucfirst($log['action']) ?></span></td>
                        <td class="text-muted small"><?= htmlspecialchars($log['remarks']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

<script>
function gmApprove(action) {
    const remarks = document.getElementById('gmRemarks').value;
    if (action === 'rejected' && !remarks.trim()) {
        alert('Please provide remarks when rejecting.');
        return;
    }
    if (!confirm(`Are you sure you want to ${action} this sales order?`)) return;

    fetch('<?= BASE_URL ?>/approvals/act', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            request_id: <?= $approval['id'] ?? 0 ?>,
            action,
            remarks
        })
    })
    .then(r => r.json())
    .then(res => {
        alert(res.message);
        if (res.success) location.reload();
    })
    .catch(err => {
        console.error('Error:', err);
        alert('An error occurred. Please try again.');
    });
}

function recordPayment() {
    const amount = parseFloat(document.getElementById('paymentAmount').value);
    const method = document.getElementById('paymentMethod').value;
    const date = document.getElementById('paymentDate').value;
    const notes = document.getElementById('paymentNotes').value;
    
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount.');
        return;
    }
    
    const maxAmount = <?= $order['total_amount'] - ($order['amount_paid']??0) ?>;
    if (amount > maxAmount) {
        alert(`Amount cannot exceed balance of ₱${maxAmount.toFixed(2)}`);
        return;
    }
    
    if (!confirm(`Record payment of ₱${amount.toFixed(2)}?`)) return;
    
    const fd = new FormData();
    fd.append('so_id', <?= $order['id'] ?>);
    fd.append('amount', amount);
    fd.append('payment_method', method);
    fd.append('payment_date', date);
    fd.append('notes', notes);
    
    fetch('<?= BASE_URL ?>/sales/record-payment', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        alert(res.message);
        if (res.success) location.reload();
    })
    .catch(err => {
        console.error('Error:', err);
        alert('An error occurred. Please try again.');
    });
}
</script>

