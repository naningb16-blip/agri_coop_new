<?php
ob_start();
$pageTitle  = 'Logistics';
$activeMenu = 'logistics';
$userRole   = $_SESSION['user']['role'] ?? '';
$isReadOnly = in_array($userRole, ['gm', 'manager']);
$canManage  = in_array($userRole, ['admin']) && !$isReadOnly;
$statusColors = ['pending'=>'warning','in_transit'=>'info','delivered'=>'success','failed'=>'danger'];
?>

<?php if ($userRole === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all logistics data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <?php foreach ([
        ['pending',    'Pending',    'warning', 'hourglass-split'],
        ['in_transit', 'In Transit', 'info',    'truck'],
        ['delivered',  'Delivered',  'success', 'check-circle'],
        ['failed',     'Failed',     'danger',  'x-circle'],
    ] as [$key, $label, $color, $icon]): ?>
    <div class="col-6 col-md-3">
        <div class="stat-card <?= $color === 'info' ? 'info' : ($color === 'danger' ? 'danger' : ($color === 'warning' ? 'warning' : '')) ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value"><?= $summary[$key] ?? 0 ?></div>
                    <div class="stat-label"><?= $label ?></div>
                </div>
                <i class="bi bi-<?= $icon ?> stat-icon"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('Logistics Analytics')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<!-- Filters + New -->
<div class="table-card mb-3">
    <div class="p-3">
        <form method="GET" action="<?= BASE_URL ?>/logistics" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','in_transit','delivered','failed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="purchase_order" <?= $type === 'purchase_order' ? 'selected' : '' ?>>Purchase Order</option>
                    <option value="sales_order"    <?= $type === 'sales_order'    ? 'selected' : '' ?>>Sales Order</option>
                </select>
            </div>
            <div class="col-sm-3">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
            <div class="col-sm-3 text-end">
                <?php if ($_SESSION['user']['role'] !== 'gm'): ?>
                <button type="button" class="btn btn-sm btn-success w-100" data-bs-toggle="modal" data-bs-target="#newDeliveryModal">
                    <i class="bi bi-plus-circle me-1"></i>New Delivery
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Deliveries Table -->
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-truck me-2 text-success"></i>Deliveries</span>
        <span class="badge bg-secondary"><?= count($deliveries) ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Ref</th><th>Type</th><th>Origin ; Destination</th><th>Driver</th><th>Dispatch</th><th>Delivery</th><th>Status</th><th>Approval</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($deliveries as $d): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($d['reference_type'] === 'purchase_order' ? 'PO' : 'SO') ?> #<?= $d['reference_id'] ?></td>
                <td>
                    <?php if ($d['reference_type'] === 'purchase'): ?>
                    <span class="badge bg-success">
                        <i class="bi bi-arrow-down-circle me-1"></i>Inbound
                    </span>
                    <small class="text-muted d-block">To Warehouse</small>
                    <?php else: ?>
                    <span class="badge bg-primary">
                        <i class="bi bi-arrow-up-circle me-1"></i>Outbound
                    </span>
                    <small class="text-muted d-block">To Customer</small>
                    <?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars($d['origin']) ?> <i class="bi bi-arrow-right"></i> <?= htmlspecialchars($d['destination']) ?></td>
                <td class="small"><?= htmlspecialchars($d['driver_name'] ?: '') ?></td>
                <td class="text-muted small"><?= $d['dispatch_date'] ? date('M d, Y', strtotime($d['dispatch_date'])) : '' ?></td>
                <td class="text-muted small"><?= $d['delivery_date'] ? date('M d, Y', strtotime($d['delivery_date'])) : '' ?></td>
                <td>
                    <span class="badge badge-<?= $d['status'] === 'in_transit' ? 'pending' : $d['status'] ?>" <?= $d['status'] === 'failed' ? 'style="background-color: #dc3545 !important; color: white !important;"' : '' ?>>
                        <?= ucwords(str_replace('_',' ',$d['status'])) ?>
                    </span>
                </td>
                <td>
                    <?php if ($d['approval_request_id']): ?>
                    <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $d['approval_request_id'] ?>" class="badge badge-<?= $d['approval_status'] ?> text-decoration-none">
                        <?= $d['approval_status'] === 'pending' ? htmlspecialchars($d['approval_step_label'] ?? 'Pending') : ucfirst($d['approval_status']) ?>
                    </a>
                    <?php else: ?><span class="text-muted small"></span><?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <a href="<?= BASE_URL ?>/logistics/detail?id=<?= $d['id'] ?>" class="btn btn-xs btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                    <?php if ($canManage && $d['status'] === 'pending'): ?>
                    <button class="btn btn-xs btn-info text-white" onclick="setStatus(<?= $d['id'] ?>,'in_transit')" title="Dispatch"><i class="bi bi-truck"></i></button>
                    <?php endif; ?>
                    <?php if ($canManage && $d['status'] === 'in_transit'): ?>
                    <button class="btn btn-xs btn-success" onclick="setStatus(<?= $d['id'] ?>,'delivered')" title="Mark Delivered"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-xs btn-danger"  onclick="setStatus(<?= $d['id'] ?>,'failed')"    title="Mark Failed"><i class="bi bi-x-lg"></i></button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deliveries)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No deliveries found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Delivery Modal -->
<div class="modal fade" id="newDeliveryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>New Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Reference Type <span class="text-danger">*</span></label>
                        <select id="dlvRefType" class="form-select" required onchange="updateRefDocs()">
                            <option value="">Select type</option>
                            <option value="purchase_order">📦 Purchase Order (Inbound to Warehouse)</option>
                            <option value="sales_order">🚚 Sales Order (Outbound to Customer)</option>
                        </select>
                        <small class="text-muted">Inbound = goods coming TO warehouse | Outbound = goods going TO customer</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference Document <span class="text-danger">*</span></label>
                        <select id="dlvRefId" class="form-select" required>
                            <option value="">Select reference type first</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Delivery Receipt Number</label>
                        <input type="text" id="dlvReceiptNumber" class="form-control" placeholder="e.g., DR-2026-001">
                        <small class="text-muted">Optional - Auto-generated if blank</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dispatch Date</label>
                        <input type="datetime-local" id="dlvDispatch" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Driver Name</label>
                        <input type="text" id="dlvDriver" class="form-control" placeholder="Driver full name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vehicle Plate</label>
                        <input type="text" id="dlvPlate" class="form-control" placeholder="e.g. ABC 1234">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Notes</label>
                        <input type="text" id="dlvNotes" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Origin <span class="text-danger">*</span></label>
                        <input type="text" id="dlvOrigin" class="form-control" placeholder="Warehouse / address" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Destination <span class="text-danger">*</span></label>
                        <input type="text" id="dlvDest" class="form-control" placeholder="Delivery address" required>
                    </div>
                    <div class="col-md-6" id="warehouseSelector" style="display:none;">
                        <label class="form-label">Destination Warehouse <span class="text-danger">*</span></label>
                        <select id="dlvWarehouse" class="form-select">
                            <option value="">Select warehouse</option>
                            <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> - <?= htmlspecialchars($w['location'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">For inbound deliveries, select which warehouse will receive the goods</small>
                    </div>
                </div>

                <!-- Items -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product <span class="text-danger">*</span></th>
                                <th style="width:100px">Qty</th>
                                <th style="width:80px">Unit</th>
                                <th style="width:120px">Unit Cost</th>
                                <th style="width:130px">Total Amount</th>
                                <th style="width:160px">Notes</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="dlvItems">
                            <tr>
                                <td>
                                    <select class="form-select form-select-sm" name="product_id" required>
                                        <option value="">Select product</option>
                                        <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" class="form-control form-control-sm qty" name="quantity" step="0.01" min="0.01" oninput="calcDlvRow(this)" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="unit" placeholder="kg/bag"></td>
                                <td><input type="number" class="form-control form-control-sm unit-cost" name="unit_cost" step="0.01" min="0" oninput="calcDlvRow(this)" placeholder="0.00"></td>
                                <td><input type="number" class="form-control form-control-sm total-amount" name="total_amount" step="0.01" readonly tabindex="-1"></td>
                                <td><input type="text" class="form-control form-control-sm" name="item_notes" placeholder="Optional"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addDlvRow()">
                        <i class="bi bi-plus me-1"></i>Add Item
                    </button>
                    <div class="text-end">
                        <span class="text-muted">Grand Total: </span>
                        <strong id="dlvGrandTotal" class="fs-5 text-success">₱0.00</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitDelivery()"><i class="bi bi-send me-1"></i>Create Delivery</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const PO_DOCS  = <?= json_encode($approvedPOs) ?>;
const SO_DOCS  = <?= json_encode($approvedSOs) ?>;

function updateRefDocs() {
    const type = document.getElementById('dlvRefType').value;
    const sel  = document.getElementById('dlvRefId');
    const warehouseDiv = document.getElementById('warehouseSelector');
    
    // Show warehouse selector for inbound (purchase orders)
    if (type === 'purchase_order') {
        warehouseDiv.style.display = 'block';
    } else {
        warehouseDiv.style.display = 'none';
    }
    
    sel.innerHTML = '<option value="">Select document</option>';
    const docs = type === 'purchase_order' ? PO_DOCS : (type === 'sales_order' ? SO_DOCS : []);
    if (!docs.length) {
        sel.innerHTML = '<option value="">No approved ' + (type || 'documents') + ' found</option>';
        return;
    }
    docs.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.id;
        opt.textContent = d.label + (d.party ? ' — ' + d.party : '');
        sel.appendChild(opt);
    });
}

function addDlvRow() {
    const tbody = document.getElementById('dlvItems');
    const clone = tbody.querySelector('tr').cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelector('select').value = '';
    // Re-bind oninput events
    clone.querySelectorAll('.qty, .unit-cost').forEach(i => i.setAttribute('oninput', 'calcDlvRow(this)'));
    tbody.appendChild(clone);
}

window.calcDlvRow = function(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
    const unitCost = parseFloat(row.querySelector('.unit-cost')?.value) || 0;
    const totalAmount = row.querySelector('.total-amount');
    if (totalAmount) totalAmount.value = (qty * unitCost).toFixed(2);
    updateDlvGrandTotal();
};

function updateDlvGrandTotal() {
    let sum = 0;
    document.querySelectorAll('#dlvItems .total-amount').forEach(t => sum += parseFloat(t.value) || 0);
    document.getElementById('dlvGrandTotal').textContent = '₱' + sum.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

window.removeItemRow = function(btn) {
    const tbody = btn.closest('tbody');
    if (tbody.querySelectorAll('tr').length > 1) {
        btn.closest('tr').remove();
        updateDlvGrandTotal();
    }
};

function submitDelivery() {
    const refType = document.getElementById('dlvRefType').value;
    const refId   = document.getElementById('dlvRefId').value;
    const origin  = document.getElementById('dlvOrigin').value.trim();
    const dest    = document.getElementById('dlvDest').value.trim();
    const warehouseId = document.getElementById('dlvWarehouse')?.value || '';
    const receiptNumber = document.getElementById('dlvReceiptNumber').value.trim();
    
    if (!refType) { showToast('Select a reference type.', 'warning'); return; }
    if (!refId)   { showToast('Select a reference document.', 'warning'); return; }
    if (!origin || !dest) { showToast('Origin and destination are required.', 'warning'); return; }
    
    // For inbound deliveries, warehouse is required
    if (refType === 'purchase_order' && !warehouseId) {
        showToast('Please select a destination warehouse for inbound delivery.', 'warning');
        return;
    }

    const items = [];
    document.querySelectorAll('#dlvItems tr').forEach(row => {
        const pid = row.querySelector('[name=product_id]')?.value;
        const qty = row.querySelector('[name=quantity]')?.value;
        const unit = row.querySelector('[name=unit]')?.value;
        const unitCost = row.querySelector('[name=unit_cost]')?.value || '0';
        const totalAmount = row.querySelector('[name=total_amount]')?.value || '0';
        const notes = row.querySelector('[name=item_notes]')?.value;
        if (pid && qty) {
            items.push({ 
                product_id: pid, 
                quantity: qty, 
                unit: unit || '', 
                unit_cost: unitCost,
                total_amount: totalAmount,
                notes: notes || '' 
            });
        }
    });

    if (!items.length) {
        showToast('Add at least one item.', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append('reference_type',  refType);
    fd.append('reference_id',    refId);
    fd.append('origin',          origin);
    fd.append('destination',     dest);
    fd.append('driver_name',     document.getElementById('dlvDriver').value);
    fd.append('vehicle_plate',   document.getElementById('dlvPlate').value);
    fd.append('dispatch_date',   document.getElementById('dlvDispatch').value);
    fd.append('notes',           document.getElementById('dlvNotes').value);
    if (receiptNumber) fd.append('receipt_number', receiptNumber);
    if (warehouseId) fd.append('warehouse_id', warehouseId);
    items.forEach((item, i) => Object.entries(item).forEach(([k,v]) => fd.append(`items[${i}][${k}]`, v)));

    fetch(BASE_URL + '/logistics/create', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) setTimeout(() => location.reload(), 800);
        });
}

function setStatus(id, status) {
    const labels = { in_transit: 'dispatch', delivered: 'mark as delivered', failed: 'mark as failed' };
    if (!confirm(`Are you sure you want to ${labels[status] || status} this delivery?`)) return;
    const fd = new FormData();
    fd.append('id', id); fd.append('status', status);
    fetch(BASE_URL + '/logistics/status', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>



