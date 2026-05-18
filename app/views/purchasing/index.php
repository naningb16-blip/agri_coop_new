<?php
// Low Stock Alert Query
$lowStockItems = $this->db->fetchAll(
    "SELECT p.id, p.name, p.unit, p.reorder_level,
            COALESCE(SUM(i.quantity), 0) AS current_stock,
            p.reorder_level - COALESCE(SUM(i.quantity), 0) AS shortage
     FROM products p
     LEFT JOIN inventory i ON p.id = i.product_id
     WHERE p.reorder_level > 0
     GROUP BY p.id, p.name, p.unit, p.reorder_level
     HAVING current_stock < p.reorder_level
     ORDER BY shortage DESC
     LIMIT 10"
);
?>
﻿<?php
ob_start();
$pageTitle  = 'Purchasing';
$activeMenu = 'purchasing';
$userRole   = $_SESSION['user']['role'] ?? '';
$isReadOnly = in_array($userRole, ['gm', 'manager']);
$canApprove = in_array($userRole, ['admin']) && !$isReadOnly;
$canDelete  = in_array($userRole, ['admin', 'purchasing_user']);
?>

<!-- Tab Nav -->
<ul class="nav nav-tabs mb-3" id="purchasingTabs">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'po'  || $tab === '' ? 'active' : '' ?>"
           href="<?= BASE_URL ?>/purchasing?tab=po">
            <i class="bi bi-bag-check me-1"></i>Purchase Orders
            <span class="badge bg-secondary ms-1"><?= count($orders) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'prs' ? 'active' : '' ?>"
           href="<?= BASE_URL ?>/purchasing?tab=prs">
            <i class="bi bi-file-earmark-text me-1"></i>Requisitions
            <span class="badge bg-secondary ms-1"><?= count($requisitions) ?></span>
        </a>
    </li>
    <li class="nav-item ms-auto d-flex gap-2 align-items-center">
        <a href="<?= BASE_URL ?>/purchasing/tracking" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-graph-up me-1"></i>Tracking
        </a>
        <a href="<?= BASE_URL ?>/purchasing/suppliers" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-building me-1"></i>Suppliers
        </a>
    </li>
</ul>
<!-- Low Stock Alert for Purchasing -->
<?php if (!empty($lowStockItems)): ?>
<div class="alert alert-danger mb-4">
    <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
        <div class="flex-grow-1">
            <h5 class="mb-2"><i class="bi bi-box-seam"></i> Low Stock Alert (<?= count($lowStockItems) ?> items)</h5>
            <p class="mb-2">The following items are below their reorder levels and need restocking:</p>
            <ul class="mb-0">
            <?php foreach ($lowStockItems as $item): ?>
                <li>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>: 
                    Current stock <span class="badge bg-danger"><?= number_format($item['current_stock'], 2) ?> <?= htmlspecialchars($item['unit']) ?></span>
                    (Reorder at: <?= number_format($item['reorder_level'], 2) ?> <?= htmlspecialchars($item['unit']) ?>)
                     Shortage: <strong><?= number_format($item['shortage'], 2) ?> <?= htmlspecialchars($item['unit']) ?></strong>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php if ($userRole !== 'gm'): ?>
            <div class="mt-2">
                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#newPOModal">
                    <i class="bi bi-plus-circle me-1"></i>Create Purchase Order
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- &#8369;8369;”€ Purchase Orders Tab &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369; -->
<div id="tabPO" class="<?= ($tab === 'prs') ? 'd-none' : '' ?>">
    <div class="table-card">
        <div class="card-header">
            <span><i class="bi bi-bag-check me-2 text-success"></i>Purchase Orders</span>
            <?php if ($_SESSION['user']['role'] !== 'gm'): ?>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#newPOModal">
                <i class="bi bi-plus-circle me-1"></i>New PO
            </button>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>PO #</th><th>Supplier</th><th>Order Date</th><th>Expected Delivery</th><th>Total</th><th>Status</th><th>Approval</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/purchasing/po-detail?id=<?= $o['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($o['po_number']) ?></a></td>
                    <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                    <td class="text-muted small"><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                    <td class="text-muted small"><?= $o['expected_delivery'] ? date('M d, Y', strtotime($o['expected_delivery'])) : '&#8369;' ?></td>
                    <td>&#8369;<?= number_format($o['total_amount'], 2) ?></td>
                    <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td>
                        <?php if ($o['approval_request_id']): ?>
                        <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $o['approval_request_id'] ?>" class="badge badge-<?= $o['approval_status'] ?> text-decoration-none">
                            <?= $o['approval_status'] === 'pending' ? htmlspecialchars($o['approval_step_label'] ?? 'Pending') : ucfirst($o['approval_status']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted small">&#8369;</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/purchasing/po-detail?id=<?= $o['id'] ?>" class="btn btn-xs btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                        <?php if ($o['status'] === 'pending' && $_SESSION['user']['role'] !== 'gm'): ?>
                        <button class="btn btn-xs btn-outline-secondary" title="Edit"
                            onclick="openEditPO(<?= htmlspecialchars(json_encode($o)) ?>)"><i class="bi bi-pencil"></i></button>
                        <?php endif; ?>
                        <?php if ($canApprove && $o['status'] === 'pending'): ?>
                        <button class="btn btn-xs btn-success" title="Approve" onclick="quickApprovePO(<?= $o['id'] ?>, 'approved')"><i class="bi bi-check-lg"></i></button>
                        <button class="btn btn-xs btn-danger"  title="Reject"  onclick="quickApprovePO(<?= $o['id'] ?>, 'rejected')"><i class="bi bi-x-lg"></i></button>
                        <?php endif; ?>
                        <?php if ($canApprove && $o['status'] === 'approved'): ?>
                        <button class="btn btn-xs btn-info text-white" title="Mark Delivered" onclick="updatePOStatus(<?= $o['id'] ?>, 'delivered')"><i class="bi bi-truck"></i></button>
                        <?php endif; ?>
                        <?php if ($canDelete && in_array($o['status'], ['pending', 'rejected', 'cancelled']) && $_SESSION['user']['role'] !== 'gm'): ?>
                        <button class="btn btn-xs btn-outline-danger" title="Delete" onclick="deletePO(<?= $o['id'] ?>)"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No purchase orders yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- &#8369;8369;”€ Requisitions Tab &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€ -->
<div id="tabPRS" class="<?= ($tab !== 'prs') ? 'd-none' : '' ?>">
    <div class="table-card">
        <div class="card-header">
            <span><i class="bi bi-file-earmark-text me-2 text-primary"></i>Purchase Requisitions</span>
            <?php if ($_SESSION['user']['role'] !== 'gm'): ?>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newPRSModal">
                <i class="bi bi-plus-circle me-1"></i>New Requisition
            </button>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>PRS #</th><th>Requested By</th><th>Department</th><th>Description</th><th>Status</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($requisitions as $r): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/purchasing/prs-detail?id=<?= $r['id'] ?>" class="fw-semibold text-decoration-none">PRS-<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?></a></td>
                    <td><?= htmlspecialchars($r['requester_name']) ?></td>
                    <td><?= htmlspecialchars($r['dept_name'] ?? '—') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars(mb_strimwidth($r['description'] ?? '', 0, 50, '…')) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td class="text-muted small"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/purchasing/prs-detail?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                        <?php if (in_array($r['status'], ['pending', 'rejected']) && $canDelete && $_SESSION['user']['role'] !== 'gm'): ?>
                        <button class="btn btn-xs btn-outline-secondary" title="Edit"
                            onclick="openEditPRS(<?= $r['id'] ?>)"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-xs btn-outline-danger" title="Delete" onclick="deletePRS(<?= $r['id'] ?>)"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($requisitions)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No requisitions yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- &#8369;8369;• New PO Modal &#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;• -->
<div class="modal fade" id="newPOModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-bag-plus me-2"></i>New Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <input type="text" id="poSupplierInput" class="form-control" list="supplierList" placeholder="Type or select supplier" required autocomplete="off">
                        <datalist id="supplierList">
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= htmlspecialchars($s['name']) ?>" data-id="<?= $s['id'] ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <input type="hidden" id="poSupplier">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier Invoice Number</label>
                        <input type="text" id="poSupplierInvoice" class="form-control" placeholder="e.g., SI-2026-001">
                        <small class="text-muted">Optional - Invoice # from supplier</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expected Delivery</label>
                        <input type="date" id="poDelivery" class="form-control" min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Notes</label>
                        <input type="text" id="poNotes" class="form-control" placeholder="Optional notes">
                    </div>
                </div>
                <?= _itemsTable('poItems') ?>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="text-muted small">Grand Total: <strong id="poGrandTotal">&#8369;0.00</strong></span>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitPO()"><i class="bi bi-send me-1"></i>Submit PO</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- &#8369;8369;• Edit PO Modal &#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369; -->
<div class="modal fade" id="editPOModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editPOId">
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select id="editPOSupplier" class="form-select" required>
                            <option value="">Select supplier</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expected Delivery</label>
                        <input type="date" id="editPODelivery" class="form-control">
                    </div>
                </div>
                <?= _itemsTable('editPOItems') ?>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="text-muted small">Grand Total: <strong id="editPOGrandTotal">&#8369;0.00</strong></span>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveEditPO()"><i class="bi bi-save me-1"></i>Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- &#8369;8369;• New PRS Modal &#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369; -->
<div class="modal fade" id="newPRSModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>New Purchase Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select id="prsDept" class="form-select">
                            <option value="">Select department</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Description / Purpose <span class="text-danger">*</span></label>
                        <input type="text" id="prsDesc" class="form-control" placeholder="What is this requisition for?" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea id="prsNotes" class="form-control" rows="2" placeholder="Additional notes&#8369;"></textarea>
                    </div>
                </div>
                <?= _itemsTable('prsItems', true) ?>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="text-muted small">Est. Total: <strong id="prsGrandTotal">&#8369;0.00</strong></span>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitPRS()"><i class="bi bi-send me-1"></i>Submit Requisition</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper: renders the items table HTML
function _itemsTable(string $tbodyId, bool $isPRS = false): string {
    $priceLabel = $isPRS ? 'Est. Unit Price' : 'Unit Price';
    return <<<HTML
    <div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th style="min-width:180px">Item Name <span class="text-danger">*</span></th>
                <th style="width:90px">Qty <span class="text-danger">*</span></th>
                <th style="width:80px">Unit</th>
                <th style="width:130px">$priceLabel</th>
                <th style="width:130px">Total</th>
                <th style="width:40px"></th>
            </tr>
        </thead>
        <tbody id="$tbodyId">
            <tr>
                <td><input type="text" class="form-control form-control-sm" name="item_name" placeholder="Item description" required></td>
                <td><input type="number" class="form-control form-control-sm qty" step="0.01" min="0.01" oninput="calcRow(this,'$tbodyId')" required></td>
                <td><input type="text" class="form-control form-control-sm" name="unit" placeholder="kg/bag/pc"></td>
                <td><input type="number" class="form-control form-control-sm price" step="0.01" min="0" oninput="calcRow(this,'$tbodyId')"></td>
                <td><input type="number" class="form-control form-control-sm total" step="0.01" readonly tabindex="-1"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)"><i class="bi bi-trash"></i></button></td>
            </tr>
        </tbody>
    </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItemRow('$tbodyId')">
        <i class="bi bi-plus me-1"></i>Add Row
    </button>
HTML;
}
?>

<script>
const BASE_URL = '<?= BASE_URL ?>';

// &#8369;8369;”€ Row helpers &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€
window.calcRow = function(input, tbodyId) {
    const row   = input.closest('tr');
    const qty   = parseFloat(row.querySelector('.qty')?.value)   || 0;
    const price = parseFloat(row.querySelector('.price')?.value) || 0;
    const total = row.querySelector('.total');
    if (total) total.value = (qty * price).toFixed(2);
    updateGrandTotal(tbodyId);
};

function updateGrandTotal(tbodyId) {
    const map = { poItems: 'poGrandTotal', editPOItems: 'editPOGrandTotal', prsItems: 'prsGrandTotal' };
    const el  = document.getElementById(map[tbodyId]);
    if (!el) return;
    let sum = 0;
    document.querySelectorAll(`#${tbodyId} .total`).forEach(t => sum += parseFloat(t.value) || 0);
    el.textContent = '₱' + sum.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

window.addItemRow = function(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    const clone = tbody.querySelector('tr').cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    // re-bind oninput
    clone.querySelectorAll('.qty, .price').forEach(i => i.setAttribute('oninput', `calcRow(this,'${tbodyId}')`));
    tbody.appendChild(clone);
};

window.removeItemRow = function(btn) {
    const tbody = btn.closest('tbody');
    if (tbody.querySelectorAll('tr').length > 1) btn.closest('tr').remove();
};

// &#8369;8369;”€ Collect items from a tbody &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;
function collectItems(tbodyId) {
    const items = [];
    document.querySelectorAll(`#${tbodyId} tr`).forEach(row => {
        const name  = row.querySelector('[name=item_name]')?.value?.trim();
        const qty   = row.querySelector('.qty')?.value;
        const unit  = row.querySelector('[name=unit]')?.value?.trim();
        const price = row.querySelector('.price')?.value || '0';
        const total = row.querySelector('.total')?.value || '0';
        if (name && qty) items.push({ item_name: name, quantity: qty, unit, unit_price: price, total_price: total });
    });
    return items;
}

function buildFormData(obj) {
    const fd = new FormData();
    Object.entries(obj).forEach(([k, v]) => {
        if (Array.isArray(v)) v.forEach((item, i) => Object.entries(item).forEach(([ik, iv]) => fd.append(`${k}[${i}][${ik}]`, iv)));
        else fd.append(k, v ?? '');
    });
    return fd;
}

function postJSON(url, fd, onSuccess) {
    fetch(url, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success && onSuccess) onSuccess(res);
        });
}

// &#8369;8369;”€ PO actions &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;
function submitPO() {
    const supplierInput = document.getElementById('poSupplierInput').value.trim();
    if (!supplierInput) { showToast('Enter a supplier name.', 'warning'); return; }
    const opt = [...document.querySelectorAll('#supplierList option')].find(o => o.value === supplierInput);
    const supplierId = opt ? opt.dataset.id : '0';
    const items = collectItems('poItems');
    if (!items.length) { showToast('Add at least one item.', 'warning'); return; }

    postJSON(BASE_URL + '/purchasing/create', buildFormData({
        supplier_id: supplierId,
        supplier_name: supplierInput,
        supplier_invoice_number: document.getElementById('poSupplierInvoice').value,
        expected_delivery: document.getElementById('poDelivery').value,
        notes: document.getElementById('poNotes').value,
        items
    }), () => location.reload());
}

function openEditPO(po) {
    document.getElementById('editPOId').value = po.id;
    document.getElementById('editPOSupplier').value = po.supplier_id;
    document.getElementById('editPODelivery').value = po.expected_delivery ?? '';

    // Load existing items via detail page data
    fetch(BASE_URL + '/purchasing/po-detail?id=' + po.id)
        .then(r => r.text()).then(() => {
            // Fallback: just open modal with one blank row
            const tbody = document.getElementById('editPOItems');
            tbody.querySelectorAll('tr').forEach((r, i) => { if (i > 0) r.remove(); });
            tbody.querySelector('tr').querySelectorAll('input').forEach(i => i.value = '');
            updateGrandTotal('editPOItems');
        });
    new bootstrap.Modal(document.getElementById('editPOModal')).show();
}

function saveEditPO() {
    const id = document.getElementById('editPOId').value;
    const supplierId = document.getElementById('editPOSupplier').value;
    if (!supplierId) { showToast('Select a supplier.', 'warning'); return; }
    const items = collectItems('editPOItems');
    if (!items.length) { showToast('Add at least one item.', 'warning'); return; }

    postJSON(BASE_URL + '/purchasing/edit', buildFormData({
        id, supplier_id: supplierId,
        expected_delivery: document.getElementById('editPODelivery').value,
        items
    }), () => location.reload());
}

function quickApprovePO(id, action) {
    if (!confirm(`${action === 'approved' ? 'Approve' : 'Reject'} this PO?`)) return;
    postJSON(BASE_URL + '/purchasing/approve', buildFormData({ id, action }), () => location.reload());
}

function updatePOStatus(id, status) {
    if (!confirm(`Mark PO as ${status}?`)) return;
    postJSON(BASE_URL + '/purchasing/status', buildFormData({ id, status }), () => location.reload());
}

function deletePO(id) {
    if (!confirm('Delete this PO? This cannot be undone.')) return;
    postJSON(BASE_URL + '/purchasing/delete-po', buildFormData({ id }), () => location.reload());
}

// &#8369;8369;”€ PRS actions &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€
function submitPRS() {
    const desc = document.getElementById('prsDesc').value.trim();
    if (!desc) { showToast('Description is required.', 'warning'); return; }
    const items = collectItems('prsItems');
    if (!items.length) { showToast('Add at least one item.', 'warning'); return; }

    postJSON(BASE_URL + '/purchasing/create-prs', buildFormData({
        department_id: document.getElementById('prsDept').value,
        description: desc,
        notes: document.getElementById('prsNotes').value,
        items
    }), () => location.reload());
}

function openEditPRS(id) {
    window.location.href = BASE_URL + '/purchasing/prs-detail?id=' + id;
}

function deletePRS(id) {
    if (!confirm('Delete this requisition?')) return;
    postJSON(BASE_URL + '/purchasing/delete-prs', buildFormData({ id }), () => location.reload());
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>


