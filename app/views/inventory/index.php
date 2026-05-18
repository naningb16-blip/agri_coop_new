<?php
ob_start();
$pageTitle  = 'Inventory & Warehouse';
$activeMenu = 'inventory';
$userRole   = $_SESSION['user']['role'] ?? '';
$canManage  = in_array($userRole, ['admin']) && !($isGMReadOnly ?? false); // only admin can edit inventory data, GM cannot
$canDelete  = $userRole === 'admin' && !($isGMReadOnly ?? false);
$canReceive = in_array($userRole, ['admin', 'gm', 'purchasing_user']) && !($isGMReadOnly ?? false);
?>

<?php if ($isGMReadOnly ?? false): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all inventory data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<?php
// Low Stock Alert - Only for inventory users
if (in_array($userRole, ['admin', 'inventory'])):
    $lowStockItems = $this->db->fetchAll(
        "SELECT p.id, p.name, p.unit, p.reorder_level,
                COALESCE(SUM(i.quantity), 0) AS current_stock,
                p.reorder_level - COALESCE(SUM(i.quantity), 0) AS shortage
         FROM products p
         LEFT JOIN inventory i ON p.id = i.product_id
         WHERE p.reorder_level > 0
         GROUP BY p.id, p.name, p.unit, p.reorder_level
         HAVING current_stock < p.reorder_level
         ORDER BY shortage DESC"
    );
    if (!empty($lowStockItems)):
?>
<div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Low Stock Alert</h5>
    <p class="mb-2">The following products are below their reorder level:</p>
    <ul class="mb-2">
        <?php foreach (array_slice($lowStockItems, 0, 5) as $item): ?>
        <li>
            <strong><?= htmlspecialchars($item['name']) ?></strong>: 
            Current stock <span class="badge bg-danger"><?= number_format($item['current_stock'], 2) ?> <?= htmlspecialchars($item['unit']) ?></span>
            (Reorder at: <?= number_format($item['reorder_level'], 2) ?> <?= htmlspecialchars($item['unit']) ?>)
            — Shortage: <strong><?= number_format($item['shortage'], 2) ?> <?= htmlspecialchars($item['unit']) ?></strong>
        </li>
        <?php endforeach; ?>
        <?php if (count($lowStockItems) > 5): ?>
        <li class="text-muted">... and <?= count($lowStockItems) - 5 ?> more product(s)</li>
        <?php endif; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; endif; ?>

<!-- Summary Cards -->
<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <?php foreach ([
        [$stats['total_products'],  'Products',        '',        'box-seam'],
        [$stats['total_warehouses'],'Warehouses',      '',        'building'],
        [$stats['low_stock'],       'Low Stock',       'danger',  'exclamation-triangle'],
        [$stats['pending_returns'], 'Pending Returns', 'warning', 'arrow-return-left'],
        [$stats['pending_releases'],'Pending Releases','warning', 'box-arrow-up'],
    ] as [$val, $label, $color, $icon]): ?>
    <div class="col-6 col-md">
        <div class="stat-card <?= $color ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="stat-value"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
                <i class="bi bi-<?= $icon ?> stat-icon"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('Inventory Analytics')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<!-- Tab Nav -->
<ul class="nav nav-tabs mb-3">
    <?php foreach ([
        ['stock',    'bi-boxes',            'Stock Levels'],
        ['movements','bi-arrow-left-right', 'Movements'],
        ['releases', 'bi-box-arrow-up',     'Release Requests'],
        ['returns',  'bi-arrow-return-left','Returns'],
        ['products', 'bi-box-seam',         'Products'],
        ['warehouses','bi-building',        'Warehouses'],
    ] as [$key, $icon, $label]): ?>
    <li class="nav-item">
        <a class="nav-link <?= $tab === $key ? 'active' : '' ?>" href="<?= BASE_URL ?>/inventory?tab=<?= $key ?>">
            <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- &#8369;8369;”€ STOCK LEVELS &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€ -->
<div class="<?= $tab !== 'stock' ? 'd-none' : '' ?>">
    <div class="table-card">
        <div class="card-header">
            <span><i class="bi bi-boxes me-2 text-success"></i>Current Stock</span>
            <div class="d-flex gap-2">
                <select id="whFilter" class="form-select form-select-sm w-auto" onchange="location.href='<?= BASE_URL ?>/inventory?tab=stock&warehouse='+this.value">
                    <option value="0">All Warehouses</option>
                    <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $whId == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="stockSearch" class="form-control form-control-sm" placeholder="Search" style="width:160px">
                <?php if ($canReceive && $_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#stockInModal"><i class="bi bi-plus-circle me-1"></i>Receive</button><?php endif; ?>
                <?php if ($_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#releaseModal"><i class="bi bi-box-arrow-up me-1"></i>Request Release</button><?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="stockTable">
                <thead><tr><th>Product</th><th>Category</th><th>Warehouse</th><th>Qty</th><th>Unit</th><th>Reorder Level</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($stock as $s): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['product_name']) ?></strong></td>
                    <td class="text-muted small"><?= htmlspecialchars($s['category'] ?? '&#8369;') ?></td>
                    <td><?= htmlspecialchars($s['warehouse_name']) ?></td>
                    <td class="<?= $s['stock_status'] === 'low' ? 'text-danger fw-bold' : '' ?>"><?= number_format($s['quantity'], 2) ?></td>
                    <td><?= htmlspecialchars($s['unit']) ?></td>
                    <td><?= number_format($s['reorder_level'], 2) ?></td>
                    <td>
                        <?php if ($s['stock_status'] === 'low'): ?>
                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Low Stock</span>
                        <?php else: ?>
                        <span class="badge bg-success">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($stock)): ?><tr><td colspan="7" class="text-center text-muted py-4">No stock records.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- &#8369;8369;”€ MOVEMENTS &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369; -->
<div class="<?= $tab !== 'movements' ? 'd-none' : '' ?>">
    <div class="table-card">
        <div class="card-header">
            <span><i class="bi bi-arrow-left-right me-2 text-info"></i>Stock Movements</span>
            <div class="d-flex gap-2">
                <?php if ($canReceive && $_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-success"       data-bs-toggle="modal" data-bs-target="#stockInModal"><i class="bi bi-plus-circle me-1"></i>Stock In</button><?php endif; ?>
                <?php if ($canManage && $_SESSION['user']['role'] !== 'gm'): ?>
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#stockOutModal"><i class="bi bi-dash-circle me-1"></i>Stock Out</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Date</th><th>Product</th><th>Warehouse</th><th>Type</th><th>Qty</th><th>Reference</th><th>By</th><th>Notes</th></tr></thead>
                <tbody>
                <?php foreach ($movements as $m): ?>
                <?php $tc = ['in'=>'success','out'=>'danger','return'=>'info','adjustment'=>'warning']; ?>
                <tr>
                    <td class="text-muted small text-nowrap"><?= date('M d, Y H:i', strtotime($m['created_at'])) ?></td>
                    <td><?= htmlspecialchars($m['product_name']) ?></td>
                    <td><?= htmlspecialchars($m['warehouse_name']) ?></td>
                    <td><span class="badge bg-<?= $tc[$m['type']] ?? 'secondary' ?>"><?= strtoupper($m['type']) ?></span></td>
                    <td><?= number_format($m['quantity'], 2) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($m['reference_type'] ?? '') ?> <?= $m['reference_id'] ? '#'.$m['reference_id'] : '' ?></td>
                    <td class="small"><?= htmlspecialchars($m['created_by_name'] ?? '') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($m['notes']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($movements)): ?><tr><td colspan="8" class="text-center text-muted py-4">No movements yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- &#8369;8369;”€ RELEASE REQUESTS &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369; -->
<div class="<?= $tab !== 'releases' ? 'd-none' : '' ?>">
    <div class="table-card">
        <div class="card-header">
            <span><i class="bi bi-box-arrow-up me-2 text-warning"></i>Stock Release Requests</span>
            <button class="btn btn-sm <?= $mine ? 'btn-primary' : 'btn-outline-secondary' ?>" onclick="location.href='<?= BASE_URL ?>/inventory?tab=releases&mine=<?= $mine ? '0' : '1' ?>'">
                <i class="bi bi-person-check me-1"></i><?= $mine ? 'Show All' : 'My Submissions' ?>
            </button>
            <?php if ($_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#releaseModal"><i class="bi bi-plus-circle me-1"></i>New Request</button><?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Product</th><th>Warehouse</th><th>Qty</th><th>Purpose</th><th>Requested By</th><th>Status</th><th>Approval</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($releases as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['product_name']) ?></td>
                    <td><?= htmlspecialchars($r['warehouse_name']) ?></td>
                    <td><?= number_format($r['quantity'], 2) ?> <?= htmlspecialchars($r['unit']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['purpose'] ?? '') ?></td>
                    <td class="small"><?= htmlspecialchars($r['requested_by_name']) ?></td>
                    <td><span class="badge badge-<?= $r['status'] === 'released' ? 'approved' : $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <?php if ($r['approval_request_id']): ?>
                        <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $r['approval_request_id'] ?>" class="badge badge-<?= $r['approval_status'] ?> text-decoration-none">
                            <?= $r['approval_status'] === 'pending' ? htmlspecialchars($r['approval_step_label'] ?? 'Pending') : ucfirst($r['approval_status']) ?>
                        </a>
                        <?php else: ?><span class="text-muted small"></span><?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td>
                        <?php if ($r['approval_request_id']): ?>
                        <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $r['approval_request_id'] ?>" class="btn btn-xs btn-outline-primary">
                            <i class="bi bi-eye"></i> Review
                        </a>
                        <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($releases)): ?><tr><td colspan="9" class="text-center text-muted py-4">No release requests.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- &#8369;8369;”€ RETURNS &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€ -->
<div class="<?= $tab !== 'returns' ? 'd-none' : '' ?>">
    <div class="table-card">
        <div class="card-header">
            <span><i class="bi bi-arrow-return-left me-2 text-info"></i>Stock Returns</span>
            <?php if ($_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#returnModal"><i class="bi bi-plus-circle me-1"></i>New Return</button><?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Product</th><th>Warehouse</th><th>Qty</th><th>Type</th><th>Condition</th><th>Reason</th><th>Status</th><th>Approval</th><th>By</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($returns as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['product_name']) ?></td>
                    <td><?= htmlspecialchars($r['warehouse_name']) ?></td>
                    <td><?= number_format($r['quantity'], 2) ?> <?= htmlspecialchars($r['unit']) ?></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($r['reference_type']) ?></span></td>
                    <td>
                        <?php $cc = ['good'=>'success','damaged'=>'danger','expired'=>'warning']; ?>
                        <span class="badge bg-<?= $cc[$r['condition_type']] ?? 'secondary' ?>"><?= ucfirst($r['condition_type']) ?></span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars(mb_strimwidth($r['reason'] ?? '', 0, 40, '&#8369;')) ?></td>
                    <td><span class="badge badge-<?= $r['status'] === 'restocked' ? 'approved' : ($r['status'] === 'disposed' ? 'rejected' : $r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <?php if ($r['approval_request_id']): ?>
                        <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $r['approval_request_id'] ?>" class="badge badge-<?= $r['approval_status'] ?> text-decoration-none">
                            <?= $r['approval_status'] === 'pending' ? htmlspecialchars($r['approval_step_label'] ?? 'Pending') : ucfirst($r['approval_status']) ?>
                        </a>
                        <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars($r['created_by_name']) ?></td>
                    <td class="text-muted small"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td class="text-nowrap">
                        <?php if ($r['approval_request_id']): ?>
                        <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $r['approval_request_id'] ?>" class="btn btn-xs btn-outline-primary">
                            <i class="bi bi-eye"></i> Review
                        </a>
                        <?php elseif ($canManage && $r['status'] === 'pending'): ?>
                        <button class="btn btn-xs btn-success" onclick="processReturn(<?= $r['id'] ?>,'restock')" title="Restock"><i class="bi bi-check-lg"></i></button>
                        <button class="btn btn-xs btn-danger"  onclick="processReturn(<?= $r['id'] ?>,'dispose')" title="Dispose"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($returns)): ?><tr><td colspan="11" class="text-center text-muted py-4">No returns yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- &#8369;8369;”€ PRODUCTS &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369; -->
<div class="<?= $tab !== 'products' ? 'd-none' : '' ?>">
    <div class="table-card">
        <div class="card-header">
            <span><i class="bi bi-box-seam me-2"></i>Products</span>
            <?php if ($_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetProductForm()"><i class="bi bi-plus-circle me-1"></i>Add Product</button><?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th>Category</th><th>Unit</th><th>Reorder Level</th><th>Description</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                    <td><?= htmlspecialchars($p['category'] ?? '&#8369;') ?></td>
                    <td><?= htmlspecialchars($p['unit']) ?></td>
                    <td><?= number_format($p['reorder_level'], 2) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 50, '&#8369;')) ?></td>
                    <td class="text-nowrap">
                        <?php if ($canManage): ?>
                        <button class="btn btn-xs btn-outline-secondary" onclick="editProduct(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="bi bi-pencil"></i></button>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                        <button class="btn btn-xs btn-outline-danger" onclick="deleteProduct(<?= $p['id'] ?>)"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?><tr><td colspan="6" class="text-center text-muted py-4">No products yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- &#8369;8369;”€ WAREHOUSES &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369; -->
<div class="<?= $tab !== 'warehouses' ? 'd-none' : '' ?>">
    <div class="table-card">
        <div class="card-header">
            <span><i class="bi bi-building me-2"></i>Warehouses</span>
            <?php if ($_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#warehouseModal" onclick="resetWarehouseForm()"><i class="bi bi-plus-circle me-1"></i>Add Warehouse</button><?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th>Location</th><th>Capacity</th><th>Products</th><th>Total Stock</th><th></th></tr></thead>
                <tbody>
                <?php
                $whStats = [];
                foreach ($warehouses as $w) $whStats[$w['id']] = $w;
                // Re-fetch with stats via model &#8369; use stock summary grouped
                $whSummary = [];
                foreach ($stock as $s) {
                    $wid = $s['warehouse_id'];
                    if (!isset($whSummary[$wid])) $whSummary[$wid] = ['products' => 0, 'total' => 0];
                    $whSummary[$wid]['products']++;
                    $whSummary[$wid]['total'] += $s['quantity'];
                }
                foreach ($warehouses as $w):
                    $ws = $whSummary[$w['id']] ?? ['products' => 0, 'total' => 0];
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                    <td><?= htmlspecialchars($w['location'] ?? '&#8369;') ?></td>
                    <td><?= $w['capacity'] ? number_format($w['capacity'], 2) : '&#8369;' ?></td>
                    <td><?= $ws['products'] ?></td>
                    <td><?= number_format($ws['total'], 2) ?></td>
                    <td class="text-nowrap">
                        <?php if ($canManage): ?>
                        <button class="btn btn-xs btn-outline-secondary" onclick="editWarehouse(<?= htmlspecialchars(json_encode($w)) ?>)"><i class="bi bi-pencil"></i></button>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                        <button class="btn btn-xs btn-outline-danger" onclick="deleteWarehouse(<?= $w['id'] ?>)"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($warehouses)): ?><tr><td colspan="6" class="text-center text-muted py-4">No warehouses yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- &#8369;8369;• MODALS &#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;•&#8369;8369;• -->

<!-- Stock In -->
<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle text-success me-2"></i>Receive Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/inventory/stock-in" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Product</label>
                        <input type="text" name="product_name" class="form-control" list="siProductList" placeholder="Type or select product&#8369;" required autocomplete="off">
                        <input type="hidden" name="product_id" id="siProductId">
                        <datalist id="siProductList">
                        <?php foreach ($products as $p): ?><option value="<?= htmlspecialchars($p['name']) ?>" data-id="<?= $p['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6"><label class="form-label">Warehouse</label>
                        <input type="text" name="warehouse_name" class="form-control" list="siWarehouseList" placeholder="Type or select warehouse&#8369;" required autocomplete="off">
                        <input type="hidden" name="warehouse_id" id="siWarehouseId">
                        <datalist id="siWarehouseList">
                        <?php foreach ($warehouses as $w): ?><option value="<?= htmlspecialchars($w['name']) ?>" data-id="<?= $w['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required></div>
                    <div class="col-md-6"><label class="form-label">Reference Type</label>
                        <select name="reference_type" class="form-select">
                            <option value="manual">Manual</option>
                            <option value="purchase_order">Purchase Order</option>
                            <option value="delivery">Delivery</option>
                            <option value="return">Return</option>
                            <option value="production">Production</option>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Reference ID</label><input type="number" name="reference_id" class="form-control" placeholder="Optional"></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Receive Stock</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Stock Out (direct, manager+) -->
<?php if ($canManage): ?>
<div class="modal fade" id="stockOutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-dash-circle text-danger me-2"></i>Direct Stock Out</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/inventory/stock-out" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Product</label>
                        <input type="text" name="product_name" class="form-control" list="soProductList" placeholder="Type or select product&#8369;" required autocomplete="off">
                        <input type="hidden" name="product_id" id="soProductId">
                        <datalist id="soProductList">
                        <?php foreach ($products as $p): ?><option value="<?= htmlspecialchars($p['name']) ?>" data-id="<?= $p['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6"><label class="form-label">Warehouse</label>
                        <input type="text" name="warehouse_name" class="form-control" list="soWarehouseList" placeholder="Type or select warehouse&#8369;" required autocomplete="off">
                        <input type="hidden" name="warehouse_id" id="soWarehouseId">
                        <datalist id="soWarehouseList">
                        <?php foreach ($warehouses as $w): ?><option value="<?= htmlspecialchars($w['name']) ?>" data-id="<?= $w['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Release Stock</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Release Request -->
<div class="modal fade" id="releaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-box-arrow-up text-warning me-2"></i>Request Stock Release</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/inventory/request-release" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Product</label>
                        <input type="text" name="product_name" class="form-control" list="rlProductList" placeholder="Type or select product" required autocomplete="off">
                        <input type="hidden" name="product_id" id="rlProductId">
                        <datalist id="rlProductList">
                        <?php foreach ($products as $p): ?><option value="<?= htmlspecialchars($p['name']) ?>" data-id="<?= $p['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6"><label class="form-label">Warehouse</label>
                        <input type="text" name="warehouse_name" class="form-control" list="rlWarehouseList" placeholder="Type or select warehouse" required autocomplete="off">
                        <input type="hidden" name="warehouse_id" id="rlWarehouseId">
                        <datalist id="rlWarehouseList">
                        <?php foreach ($warehouses as $w): ?><option value="<?= htmlspecialchars($w['name']) ?>" data-id="<?= $w['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required></div>
                    <div class="col-12"><label class="form-label">Requesting Department</label>
                        <select name="requesting_department" class="form-select">
                            <option value="">Select Department (Optional)</option>
                            <option value="Logistics">Logistics</option>
                            <option value="Production">Production</option>
                            <option value="Processing">Processing</option>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Purpose / Reason <span class="text-danger">*</span></label><input type="text" name="purpose" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning text-dark">Submit Request</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Return -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-return-left text-info me-2"></i>Submit Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/inventory/return" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="form-label">Return Type</label>
                        <select name="reference_type" class="form-select">
                            <option value="internal">Internal</option>
                            <option value="sale">From Sale</option>
                            <option value="purchase">From Purchase</option>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Reference ID</label><input type="number" name="reference_id" class="form-control" placeholder="SO/PO ID (optional)"></div>
                    <div class="col-12"><label class="form-label">Product</label>
                        <select name="product_id" class="form-select" required><option value="">Select</option>
                        <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['unit'] ?>)</option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select" required><option value="">Select</option>
                        <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required></div>
                    <div class="col-md-6"><label class="form-label">Condition</label>
                        <select name="condition_type" class="form-select">
                            <option value="good">Good</option>
                            <option value="damaged">Damaged</option>
                            <option value="expired">Expired</option>
                        </select></div>
                    <div class="col-12"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-info text-white">Submit Return</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="productModalTitle">Add Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/inventory/save-product" method="POST" data-ajax data-reload="true">
                <input type="hidden" name="id" id="productId" value="0">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" id="productName" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Category</label><input type="text" name="category" id="productCategory" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Unit</label><input type="text" name="unit" id="productUnit" class="form-control" placeholder="kg/bag/pc"></div>
                    <div class="col-md-6"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" id="productReorder" class="form-control" step="0.01" value="10.00" placeholder="Alert when stock falls below">
                        <small class="text-muted">System will alert when stock is below this level</small>
                    </div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="productDesc" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Product</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Warehouse Modal -->
<div class="modal fade" id="warehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="warehouseModalTitle">Add Warehouse</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/inventory/save-warehouse" method="POST" data-ajax data-reload="true">
                <input type="hidden" name="id" id="warehouseId" value="0">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" id="warehouseName" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Location</label><input type="text" name="location" id="warehouseLocation" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Capacity</label><input type="number" name="capacity" id="warehouseCapacity" class="form-control" step="0.01" placeholder="Optional"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Warehouse</button></div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

// Stock search
document.getElementById('stockSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#stockTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Release request
function approveRelease(id, action) {
    if (!confirm(`${action === 'approved' ? 'Approve' : 'Reject'} this release request?`)) return;
    const fd = new FormData(); fd.append('id', id); fd.append('action', action);
    fetch(BASE_URL + '/inventory/approve-release', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}

// Returns
function processReturn(id, action) {
    const label = action === 'restock' ? 'restock this item' : 'mark as disposed';
    if (!confirm(`Are you sure you want to ${label}?`)) return;
    const fd = new FormData(); fd.append('id', id); fd.append('action', action);
    fetch(BASE_URL + '/inventory/process-return', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}

// Product CRUD
function resetProductForm() {
    document.getElementById('productModalTitle').textContent = 'Add Product';
    document.getElementById('productId').value = 0;
    ['productName','productCategory','productUnit','productDesc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('productReorder').value = 0;
}
function editProduct(p) {
    document.getElementById('productModalTitle').textContent = 'Edit Product';
    document.getElementById('productId').value       = p.id;
    document.getElementById('productName').value     = p.name;
    document.getElementById('productCategory').value = p.category || '';
    document.getElementById('productUnit').value     = p.unit || '';
    document.getElementById('productReorder').value  = p.reorder_level || 0;
    document.getElementById('productDesc').value     = p.description || '';
    new bootstrap.Modal(document.getElementById('productModal')).show();
}
function deleteProduct(id) {
    if (!confirm('Delete this product?')) return;
    const fd = new FormData(); fd.append('id', id);
    fetch(BASE_URL + '/inventory/delete-product', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}

// Warehouse CRUD
function resetWarehouseForm() {
    document.getElementById('warehouseModalTitle').textContent = 'Add Warehouse';
    document.getElementById('warehouseId').value = 0;
    ['warehouseName','warehouseLocation','warehouseCapacity'].forEach(id => document.getElementById(id).value = '');
}
function editWarehouse(w) {
    document.getElementById('warehouseModalTitle').textContent = 'Edit Warehouse';
    document.getElementById('warehouseId').value       = w.id;
    document.getElementById('warehouseName').value     = w.name;
    document.getElementById('warehouseLocation').value = w.location || '';
    document.getElementById('warehouseCapacity').value = w.capacity || '';
    new bootstrap.Modal(document.getElementById('warehouseModal')).show();
}
function deleteWarehouse(id) {
    if (!confirm('Delete this warehouse?')) return;
    const fd = new FormData(); fd.append('id', id);
    fetch(BASE_URL + '/inventory/delete-warehouse', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { showToast(res.message, res.success ? 'success' : 'danger'); if (res.success) location.reload(); });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

