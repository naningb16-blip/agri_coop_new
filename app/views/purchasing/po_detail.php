<?php
ob_start();
$pageTitle  = 'PO: ' . $po['po_number'];
$activeMenu = 'purchasing';
$userRole   = $_SESSION['user']['role'] ?? '';
$canApprove = in_array($userRole, ['admin', 'manager', 'gm']);
$statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'skipped' => 'secondary', 'delivered' => 'info', 'cancelled' => 'secondary'];
$returnUrl  = $_GET['return'] ?? '/purchasing';
?>

<div class="mb-3">
    <a href="<?= BASE_URL . htmlspecialchars($returnUrl) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row g-3">
    <!-- Left: PO Info + Items -->
    <div class="col-lg-7">
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-bag-check me-2"></i><?= htmlspecialchars($po['po_number']) ?></span>
                <span class="badge badge-<?= $po['status'] ?>"><?= ucfirst($po['status']) ?></span>
            </div>
            <div class="p-3">
                <?php if ($po['supplier_invoice_number']): ?>
                <div class="alert alert-info mb-3">
                    <strong><i class="bi bi-file-earmark-text me-2"></i>Supplier Invoice Number:</strong> <?= htmlspecialchars($po['supplier_invoice_number']) ?>
                    <a href="<?= BASE_URL ?>/purchasing/invoice-print?id=<?= $po['id'] ?>" target="_blank" class="btn btn-xs btn-outline-primary ms-2">
                        <i class="bi bi-printer me-1"></i>Print
                    </a>
                </div>
                <?php endif; ?>
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Supplier</div>
                        <div class="fw-semibold"><?= htmlspecialchars($po['supplier_name']) ?></div>
                        <?php if ($po['contact_person']): ?><div class="small"><?= htmlspecialchars($po['contact_person']) ?></div><?php endif; ?>
                        <?php if ($po['phone']): ?><div class="small text-muted"><?= htmlspecialchars($po['phone']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Order Date</div>
                        <div><?= date('M d, Y', strtotime($po['order_date'])) ?></div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Expected Delivery</div>
                        <div><?= $po['expected_delivery'] ? date('M d, Y', strtotime($po['expected_delivery'])) : '—' ?></div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Item</th><th>Qty</th><th>Unit</th><th>Unit Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= number_format((float)($item['quantity'] ?? 0), 2) ?></td>
                        <td><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                        <td>&#8369;<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                        <td>&#8369;<?= number_format((float)($item['total_price'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">Grand Total</td>
                            <td>&#8369;<?= number_format((float)($po['total_amount'] ?? 0), 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: GM Approval + Audit -->
    <div class="col-lg-5">
        <?php if ($approval && $approval['status'] === 'pending' && $po['status'] !== 'rejected'): ?>
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

        <!-- Supplier Invoice Recording (for delivered POs) -->
        <?php if ($po['status'] === 'delivered' && !$po['supplier_invoice_number'] && $canApprove): ?>
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-file-earmark-text me-2 text-primary"></i>Record Supplier Invoice</span>
            </div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label">Supplier Invoice Number <span class="text-danger">*</span></label>
                    <input type="text" id="supplierInvoiceNumber" class="form-control" placeholder="e.g., SI-2026-001">
                </div>
                <div class="mb-3">
                    <label class="form-label">Invoice Date</label>
                    <input type="date" id="supplierInvoiceDate" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Terms</label>
                    <select id="paymentTerms" class="form-select">
                        <option value="Net 30">Net 30</option>
                        <option value="Net 60">Net 60</option>
                        <option value="Net 90">Net 90</option>
                        <option value="COD">Cash on Delivery</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" id="paymentDueDate" class="form-control">
                </div>
                <button class="btn btn-primary w-100" onclick="recordInvoice()">
                    <i class="bi bi-file-earmark-check me-1"></i>Record Invoice & Create Journal Entry
                </button>
                <small class="text-muted d-block mt-2">This will create an Accounts Payable journal entry</small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Invoice Info & Payment (if invoice recorded) -->
        <?php if ($po['supplier_invoice_number']): ?>
        <div class="table-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-text me-2"></i>Supplier Invoice</span>
                <a href="<?= BASE_URL ?>/purchasing/invoice-print?id=<?= $po['id'] ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                    <i class="bi bi-printer me-1"></i>Print
                </a>
            </div>
            <div class="p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width:40%">Invoice #</th><td><strong><?= htmlspecialchars($po['supplier_invoice_number'] ?? 'N/A') ?></strong></td></tr>
                    <tr><th class="text-muted">Invoice Date</th><td><?= $po['supplier_invoice_date'] ? date('M d, Y', strtotime($po['supplier_invoice_date'])) : 'N/A' ?></td></tr>
                    <tr><th class="text-muted">Payment Terms</th><td><?= htmlspecialchars($po['payment_terms'] ?? 'Net 30') ?></td></tr>
                    <tr><th class="text-muted">Due Date</th><td><strong><?= $po['payment_due_date'] ? date('M d, Y', strtotime($po['payment_due_date'])) : 'N/A' ?></strong></td></tr>
                    <tr><th class="text-muted">Total Amount</th><td><strong>₱<?= number_format($po['total_amount'], 2) ?></strong></td></tr>
                    <tr><th class="text-muted">Amount Paid</th><td>₱<?= number_format($po['amount_paid'] ?? 0, 2) ?></td></tr>
                    <tr><th class="text-muted">Balance Due</th><td><strong class="text-danger">₱<?= number_format($po['total_amount'] - ($po['amount_paid'] ?? 0), 2) ?></strong></td></tr>
                    <tr><th class="text-muted">Payment Status</th><td>
                        <span class="badge bg-<?= ($po['payment_status'] ?? 'unpaid') === 'paid' ? 'success' : (($po['payment_status'] ?? '') === 'partial' ? 'warning' : 'danger') ?>">
                            <?= ucfirst($po['payment_status'] ?? 'unpaid') ?>
                        </span>
                    </td></tr>
                </table>
            </div>
        </div>

        <!-- Record Payment (if not fully paid) -->
        <?php if (($po['payment_status'] ?? 'unpaid') !== 'paid' && $canApprove): ?>
        <div class="table-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-cash-coin me-2 text-success"></i>Record Payment</span>
            </div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" id="paymentAmount" class="form-control" step="0.01" min="0.01" 
                           max="<?= $po['total_amount'] - ($po['amount_paid'] ?? 0) ?>" 
                           placeholder="Enter amount">
                    <small class="text-muted">Max: ₱<?= number_format($po['total_amount'] - ($po['amount_paid'] ?? 0), 2) ?></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select id="paymentMethod" class="form-select">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="cash">Cash</option>
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
                    <i class="bi bi-check-circle me-1"></i>Record Payment & Update Journal
                </button>
                <small class="text-muted d-block mt-2">This will debit Accounts Payable and credit Cash/Bank</small>
            </div>
        </div>
        <?php endif; ?>
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
                        <td><span class="badge bg-<?= $statusColors[$log['action']] ?? 'secondary' ?>"><?= ucfirst($log['action']) ?></span></td>
                        <td class="text-muted small"><?= htmlspecialchars($log['remarks']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$approval): ?>
        <div class="table-card">
            <div class="p-4 text-center text-muted">No approval workflow linked to this PO.</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const poId = <?= $po['id'] ?>;

function gmApprove(action) {
    const remarks = document.getElementById('gmRemarks').value;
    if (action === 'rejected' && !remarks.trim()) {
        alert('Please provide remarks when rejecting.');
        return;
    }
    if (!confirm(`Are you sure you want to ${action} this purchase order?`)) return;

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

function quickAction(action) {
    if (!confirm(`${action === 'approved' ? 'Approve' : 'Reject'} this PO?`)) return;
    const fd = new FormData();
    fd.append('id', poId); fd.append('action', action);
    fetch(BASE_URL + '/purchasing/approve', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) setTimeout(() => location.reload(), 800); });
}

function updateStatus(status) {
    if (!confirm(`Mark as ${status}?`)) return;
    const fd = new FormData();
    fd.append('id', poId); fd.append('status', status);
    fetch(BASE_URL + '/purchasing/status', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) setTimeout(() => location.reload(), 800); });
}

function recordInvoice() {
    const invoiceNumber = document.getElementById('supplierInvoiceNumber').value.trim();
    const invoiceDate = document.getElementById('supplierInvoiceDate').value;
    const paymentTerms = document.getElementById('paymentTerms').value;
    const dueDate = document.getElementById('paymentDueDate').value;
    
    if (!invoiceNumber) {
        showToast('Please enter supplier invoice number', 'danger');
        return;
    }
    
    const fd = new FormData();
    fd.append('po_id', poId);
    fd.append('supplier_invoice_number', invoiceNumber);
    fd.append('supplier_invoice_date', invoiceDate);
    fd.append('payment_terms', paymentTerms);
    if (dueDate) fd.append('payment_due_date', dueDate);
    
    fetch(BASE_URL + '/purchasing/record-invoice', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) setTimeout(() => location.reload(), 800);
        });
}

function recordPayment() {
    const amount = parseFloat(document.getElementById('paymentAmount').value);
    const method = document.getElementById('paymentMethod').value;
    const date = document.getElementById('paymentDate').value;
    const notes = document.getElementById('paymentNotes').value;
    
    if (!amount || amount <= 0) {
        showToast('Please enter a valid payment amount', 'danger');
        return;
    }
    
    const fd = new FormData();
    fd.append('po_id', poId);
    fd.append('amount', amount);
    fd.append('payment_method', method);
    fd.append('payment_date', date);
    fd.append('notes', notes);
    
    fetch(BASE_URL + '/purchasing/record-payment', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) setTimeout(() => location.reload(), 800);
        });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

