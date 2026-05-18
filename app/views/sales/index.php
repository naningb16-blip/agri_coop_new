<?php
// Force fresh deployment - updated cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

ob_start();
$pageTitle  = 'Sales';
$activeMenu = 'sales';
$userRole   = $_SESSION['user']['role'] ?? '';
$isReadOnly = ($isGMReadOnly ?? false);
$canApprove = in_array($userRole, ['admin', 'sales']) && !$isReadOnly;
?>

<?php if ($isGMReadOnly ?? false): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all sales data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<?php if ($canApprove): ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    <strong>Sales Control:</strong> You can mark approved orders as delivered and record payments.
</div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="row g-3 w-100">
    <?php foreach ([[$summary['total'],'Total Orders','','cart3'],[$summary['pending'],'Pending','warning','hourglass-split'],[$summary['approved'],'Approved','','check-circle'],[$summary['delivered'],'Delivered','info','truck'],['&#8369;'.number_format($summary['total_revenue'],0),'Revenue','','cash-stack'],['&#8369;'.number_format($summary['total_paid']??0,0),'Collected','success','cash-coin'],['&#8369;'.number_format($summary['total_outstanding']??0,0),'Outstanding','danger','exclamation-circle']] as [$v,$l,$c,$i]): ?>
    <div class="col-6 col-md"><div class="stat-card <?= $c ?>"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" style="font-size:1rem"><?= $v ?></div><div class="stat-label"><?= $l ?></div></div><i class="bi bi-<?= $i ?> stat-icon"></i></div></div></div>
    <?php endforeach; ?>
    </div>
    <button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0" onclick="printPage('Sales Analytics')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<div class="table-card mb-3">
    <div class="p-3 d-flex gap-2 flex-wrap align-items-center">
        <?php foreach ([''=>'All','pending'=>'Pending','approved'=>'Approved','processing'=>'Processing','delivered'=>'Delivered','cancelled'=>'Cancelled','rejected'=>'Rejected'] as $v=>$l): ?>
        <a href="<?= BASE_URL ?>/sales?status=<?= $v ?>" class="btn btn-sm <?= $status===$v ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $l ?></a>
        <?php endforeach; ?>
        <div class="ms-auto d-flex gap-2">
            <?php if (($_SESSION['user']['role'] ?? '') !== 'gm'): ?>
            <a href="<?= BASE_URL ?>/sales/customers" class="btn btn-sm btn-outline-secondary"><i class="bi bi-people me-1"></i>Customers</a>
            <?php endif; ?>
            <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'sales'])): ?>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/mark_order_paid.php" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-cash-coin me-1"></i>Mark as Paid</a>
            <?php endif; ?>
            <?php if (!$isReadOnly): ?>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#newOrderModal"><i class="bi bi-plus-circle me-1"></i>New Order</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="card-header"><span><i class="bi bi-cart3 me-2 text-success"></i>Sales Orders</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>SO #</th><th>Customer</th><th>Order Date</th><th>Delivery</th><th>Amount</th><th>Payment</th><th>Delivery Status</th><th>GM Approval</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/sales/detail?id=<?= $o['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($o['so_number']) ?></a></td>
                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                <td class="text-muted small"><?= $o['delivery_date'] ? date('M d, Y', strtotime($o['delivery_date'])) : '&#8369;' ?></td>
                <td>&#8369;<?= number_format($o['total_amount'],2) ?></td>
                <td>
                    <span class="badge bg-<?= ($o['payment_type']??'cash')==='cash'?'success':(($o['payment_type']??'')==='charge'?'warning':'info') ?>"><?= ucfirst($o['payment_type']??'cash') ?></span>
                    <?php if (isset($o['payment_status'])): ?>
                    <span class="badge bg-<?= $o['payment_status']==='paid'?'success':($o['payment_status']==='partial'?'warning':'secondary') ?> ms-1"><?= ucfirst($o['payment_status']) ?></span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                <td>
                    <?php if ($o['approval_request_id']): ?>
                    <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $o['approval_request_id'] ?>" class="badge badge-<?= $o['approval_status'] ?> text-decoration-none">
                        <?= $o['approval_status']==='pending' ? htmlspecialchars($o['approval_step_label']??'Pending') : ucfirst($o['approval_status']) ?>
                    </a>
                    <?php else: ?><span class="text-muted small">&#8369;</span><?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <a href="<?= BASE_URL ?>/sales/detail?id=<?= $o['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="bi bi-eye"></i></a>
                    
                    <!-- Print Receipt Button - Always visible for approved orders -->
                    <?php if (in_array($o['approval_status'], ['approved'])): ?>
                    <a href="<?= BASE_URL ?>/sales/invoice-print?id=<?= $o['id'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary" title="Print Receipt"><i class="bi bi-printer"></i></a>
                    <?php endif; ?>
                    
                    <!-- Mark as Paid Button - Always visible, but validates on click -->
                    <?php if (($o['payment_status'] ?? 'unpaid') !== 'paid' && in_array($userRole, ['admin', 'sales'])): ?>
                    <button class="btn btn-xs btn-success" 
                            onclick="markAsPaid(<?= $o['id'] ?>, '<?= $o['status'] ?>', '<?= $o['approval_status'] ?? '' ?>')" 
                            title="Mark as Paid">
                        <i class="bi bi-cash-coin"></i> Paid
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($canApprove && $o['status']==='pending'): ?>
                    <button class="btn btn-xs btn-success" onclick="approveOrder(<?= $o['id'] ?>,'approved')"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-xs btn-danger"  onclick="approveOrder(<?= $o['id'] ?>,'rejected')"><i class="bi bi-x-lg"></i></button>
                    <?php endif; ?>
                    <?php if ($canApprove && $o['status']==='approved'): ?>
                    <button class="btn btn-xs btn-info text-white" onclick="setStatus(<?= $o['id'] ?>,'processing')" title="Mark as Processing"><i class="bi bi-gear"></i></button>
                    <button class="btn btn-xs btn-success" onclick="setStatus(<?= $o['id'] ?>,'delivered')" title="Mark as Delivered"><i class="bi bi-truck"></i> Deliver</button>
                    <?php endif; ?>
                    <?php if ($canApprove && $o['status']==='processing'): ?>
                    <button class="btn btn-xs btn-success" onclick="setStatus(<?= $o['id'] ?>,'delivered')"><i class="bi bi-truck"></i></button>
                    <?php endif; ?>
                    <?php if (!$isReadOnly && in_array($o['status'], ['pending', 'rejected'])): ?>
                    <button class="btn btn-xs btn-outline-danger" onclick="deleteOrder(<?= $o['id'] ?>, '<?= htmlspecialchars($o['so_number']) ?>')"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>

                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?><tr><td colspan="9" class="text-center text-muted py-4">No orders found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Order Modal -->
<div class="modal fade" id="newOrderModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cart-plus me-2"></i>New Sales Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-5"><label class="form-label">Customer <span class="text-danger">*</span></label>
                        <input type="text" id="soCustomerInput" class="form-control" list="customerList" placeholder="Type or select customer&#8369;" required autocomplete="off">
                        <datalist id="customerList">
                        <?php foreach ($customers as $c): ?><option value="<?= htmlspecialchars($c['name']) ?>" data-id="<?= $c['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                        <input type="hidden" id="soCustomer">
                    </div>
                    <div class="col-md-3"><label class="form-label">Payment Type <span class="text-danger">*</span></label>
                        <select id="soPaymentType" class="form-select" onchange="toggleCashInvoiceField()">
                            <option value="cash">Cash</option>
                            <option value="charge">Charge</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Delivery Date</label><input type="date" id="soDelivery" class="form-control"></div>
                </div>
                <div class="row g-3 mb-3" id="cashInvoiceRow" style="display:none;">
                    <div class="col-md-6">
                        <label class="form-label">Cash Sale Invoice Number</label>
                        <input type="text" id="soCashInvoice" class="form-control" placeholder="e.g., CSI-20260506-001">
                        <small class="text-muted">Optional: For cash sales only</small>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-12"><label class="form-label">Notes</label><input type="text" id="soNotes" class="form-control" placeholder="Optional"></div>
                </div>
                <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light"><tr><th>Product</th><th style="width:100px">Qty</th><th style="width:130px">Unit Price</th><th style="width:130px">Total</th><th style="width:40px"></th></tr></thead>
                    <tbody id="soItems">
                        <tr>
                            <td><select name="product_id" class="form-select form-select-sm" required><option value="">Select&#8369;</option>
                                <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>"><?= htmlspecialchars($p['name']) ?> (Stock: <?= number_format($p['stock'],2) ?>)</option><?php endforeach; ?>
                                </select></td>
                            <td><input type="number" class="form-control form-control-sm qty" step="0.01" min="0.01" oninput="calcSORow(this)" required></td>
                            <td><input type="number" class="form-control form-control-sm price" step="0.01" min="0" oninput="calcSORow(this)" required></td>
                            <td><input type="number" class="form-control form-control-sm total" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addSORow()"><i class="bi bi-plus me-1"></i>Add Item</button>
                    <span class="text-muted">Total: <strong id="soGrandTotal">&#8369;0.00</strong></span>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-success" onclick="submitOrder()"><i class="bi bi-send me-1"></i>Submit Order</button></div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

function calcSORow(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
    const price = parseFloat(row.querySelector('.price')?.value) || 0;
    const total = row.querySelector('.total');
    if (total) total.value = (qty * price).toFixed(2);
    let sum = 0;
    document.querySelectorAll('#soItems .total').forEach(t => sum += parseFloat(t.value) || 0);
    document.getElementById('soGrandTotal').textContent = '₱' + sum.toLocaleString('en-PH', {minimumFractionDigits:2});
}
function addSORow() {
    const tbody = document.getElementById('soItems');
    const clone = tbody.querySelector('tr').cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelector('select').value = '';
    clone.querySelectorAll('.qty,.price').forEach(i => i.setAttribute('oninput','calcSORow(this)'));
    tbody.appendChild(clone);
}
window.removeItemRow = function(btn) { const t = btn.closest('tbody'); if (t.querySelectorAll('tr').length > 1) btn.closest('tr').remove(); };

function toggleCashInvoiceField() {
    const paymentType = document.getElementById('soPaymentType').value;
    const cashInvoiceRow = document.getElementById('cashInvoiceRow');
    if (paymentType === 'cash') {
        cashInvoiceRow.style.display = 'block';
    } else {
        cashInvoiceRow.style.display = 'none';
        document.getElementById('soCashInvoice').value = '';
    }
}

function submitOrder() {
    const customerInput = document.getElementById('soCustomerInput').value.trim();
    if (!customerInput) { showToast('Enter a customer name.','warning'); return; }
    // resolve id from datalist, or 0 for new
    const opt = [...document.querySelectorAll('#customerList option')].find(o => o.value === customerInput);
    const customerId = opt ? opt.dataset.id : '0';
    document.getElementById('soCustomer').value = customerId;
    const items = [];
    document.querySelectorAll('#soItems tr').forEach(row => {
        const pid = row.querySelector('[name=product_id]')?.value;
        const qty = row.querySelector('.qty')?.value;
        const price = row.querySelector('.price')?.value;
        const total = row.querySelector('.total')?.value;
        if (pid && qty && price) items.push({product_id:pid,quantity:qty,unit_price:price,total_price:total});
    });
    if (!items.length) { showToast('Add at least one item.','warning'); return; }
    const fd = new FormData();
    fd.append('customer_id', customerId);
    fd.append('customer_name', customerInput);
    fd.append('payment_type', document.getElementById('soPaymentType').value);
    fd.append('delivery_date', document.getElementById('soDelivery').value);
    fd.append('notes', document.getElementById('soNotes').value);
    fd.append('cash_invoice_number', document.getElementById('soCashInvoice').value);
    items.forEach((item,i) => Object.entries(item).forEach(([k,v]) => fd.append(`items[${i}][${k}]`,v)));
    fetch(BASE_URL+'/sales/create',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{showToast(res.message,res.success?'success':'danger');if(res.success)setTimeout(()=>location.reload(),800);});
}

// Show cash invoice field on page load if payment type is cash
document.addEventListener('DOMContentLoaded', function() {
    toggleCashInvoiceField();
});

function approveOrder(id, action) {
    if (!confirm(`${action==='approved'?'Approve':'Reject'} this order?`)) return;
    const fd = new FormData(); fd.append('id',id); fd.append('action',action);
    fetch(BASE_URL+'/sales/approve',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{showToast(res.message,res.success?'success':'danger');if(res.success)location.reload();});
}
function deleteOrder(id, soNumber) {
    if (!confirm(`Delete ${soNumber}? This cannot be undone.`)) return;
    const fd = new FormData(); fd.append('id', id);
    fetch(BASE_URL+'/sales/delete',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{showToast(res.message,res.success?'success':'danger');if(res.success)setTimeout(()=>location.reload(),800);});
}
function setStatus(id, status) {
    if (!confirm(`Set order to ${status}?`)) return;
    const fd = new FormData(); fd.append('id',id); fd.append('status',status);
    fetch(BASE_URL+'/sales/status',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{showToast(res.message,res.success?'success':'danger');if(res.success)location.reload();});
}
function markAsPaid(id, deliveryStatus, approvalStatus) {
    // Validate order can be marked as paid
    if (deliveryStatus === 'rejected') {
        alert('Cannot mark as paid: This order has been REJECTED by GM.');
        return;
    }
    if (deliveryStatus === 'cancelled') {
        alert('Cannot mark as paid: This order has been CANCELLED.');
        return;
    }
    if (deliveryStatus === 'pending') {
        alert('Cannot mark as paid: This order is still PENDING GM approval.');
        return;
    }
    if (approvalStatus === 'pending') {
        alert('Cannot mark as paid: This order is waiting for GM APPROVAL.');
        return;
    }
    if (approvalStatus === 'rejected') {
        alert('Cannot mark as paid: This order was REJECTED by GM.');
        return;
    }
    if (deliveryStatus !== 'approved' && approvalStatus !== 'approved') {
        alert('Cannot mark as paid: This order must be APPROVED first.');
        return;
    }
    
    if (!confirm('Mark this order as PAID?')) return;
    const fd = new FormData();
    fd.append('id', id);
    fd.append('payment_status', 'paid');
    fd.append('payment_date', new Date().toISOString().split('T')[0]);
    fetch(BASE_URL+'/sales/mark-paid',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
        showToast(res.message, res.success?'success':'danger');
        if(res.success) location.reload();
    });
}

</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>


