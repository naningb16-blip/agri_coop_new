<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/logo.png" alt="Logo" style="height:32px;width:32px;object-fit:contain;margin-right:8px;">
        <span><?= APP_NAME ?></span>
    </div>

    <nav class="sidebar-nav">
        <?php
        $role = $_SESSION['user']['role'] ?? '';
        $isDept = str_ends_with($role, '_user');
        $deptModule = $isDept ? str_replace('_user', '', $role) : null;
        ?>

        <?php if (!$isDept): ?>
        <a href="<?= BASE_URL ?>/dashboard" class="nav-item <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <?php endif; ?>

        <?php if ($role === 'bod'): ?>
        <!-- BOD: only their dashboard -->
        <div class="nav-section">Board of Directors</div>
        <a href="<?= BASE_URL ?>/bod" class="nav-item <?= ($activeMenu ?? '') === 'bod' ? 'active' : '' ?>">
            <i class="bi bi-journal-check"></i> Transaction Monitor
        </a>

        <?php elseif ($role === 'gm'): ?>
        <!-- GM: Can view all modules (read-only) + approvals -->
        <div class="nav-section">Approvals</div>
        <a href="<?= BASE_URL ?>/approvals" class="nav-item <?= ($activeMenu ?? '') === 'approvals' ? 'active' : '' ?>">
            <i class="bi bi-check2-square"></i> Pending Approvals
            <?php
            $pc = Database::getInstance()->fetchOne(
                "SELECT COUNT(*) AS cnt FROM approval_requests ar
                 JOIN approval_steps acs ON acs.request_id=ar.id AND acs.step_order=ar.current_step AND acs.status='pending' AND acs.approver_role='gm'
                 WHERE ar.status='pending'", [], ''
            );
            if (($pc['cnt'] ?? 0) > 0): ?>
            <span class="badge bg-warning text-dark ms-auto"><?= $pc['cnt'] ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/approvals/audit" class="nav-item <?= ($activeMenu ?? '') === 'audit' ? 'active' : '' ?>">
            <i class="bi bi-search"></i> Approval Audit
        </a>
        <div class="nav-section">View Departments (Read-Only)</div>
        <a href="<?= BASE_URL ?>/sales"      class="nav-item <?= ($activeMenu??'') === 'sales'      ? 'active' : '' ?>"><i class="bi bi-cart3"></i> Sales <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/purchasing" class="nav-item <?= ($activeMenu??'') === 'purchasing' ? 'active' : '' ?>"><i class="bi bi-bag-check"></i> Purchasing <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/inventory"  class="nav-item <?= ($activeMenu??'') === 'inventory'  ? 'active' : '' ?>"><i class="bi bi-boxes"></i> Inventory <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/logistics"  class="nav-item <?= ($activeMenu??'') === 'logistics'  ? 'active' : '' ?>"><i class="bi bi-truck"></i> Logistics <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/operational" class="nav-item <?= ($activeMenu??'') === 'operational' ? 'active' : '' ?>"><i class="bi bi-gear-wide-connected"></i> Operational <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/qa"         class="nav-item <?= ($activeMenu??'') === 'quality'    ? 'active' : '' ?>"><i class="bi bi-patch-check"></i> QA <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/finance"    class="nav-item <?= ($activeMenu??'') === 'finance'    ? 'active' : '' ?>"><i class="bi bi-cash-stack"></i> Finance <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/hr"         class="nav-item <?= ($activeMenu??'') === 'hr'         ? 'active' : '' ?>"><i class="bi bi-people"></i> HR <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/monitoring" class="nav-item <?= ($activeMenu??'') === 'monitoring' ? 'active' : '' ?>"><i class="bi bi-graph-up-arrow"></i> Cost Monitoring <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/ledger"     class="nav-item <?= ($activeMenu??'') === 'ledger'     ? 'active' : '' ?>"><i class="bi bi-journal-text"></i> Farmer Ledger <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/reports"    class="nav-item <?= ($activeMenu??'') === 'reports'    ? 'active' : '' ?>"><i class="bi bi-bar-chart-line"></i> Reports <small class="text-muted">(View Only)</small></a>
        <a href="<?= BASE_URL ?>/documents"  class="nav-item <?= ($activeMenu??'') === 'documents'  ? 'active' : '' ?>"><i class="bi bi-file-earmark-arrow-up"></i> Document Routing</a>

        <?php elseif ($isDept): ?>
        <!-- Dept user: only their own module -->
        <div class="nav-section">My Department</div>
        <?php
        $deptLinks = [
            'sales'       => ['bi-cart3',         'Sales',       '/sales'],
            'purchasing'  => ['bi-bag-check',      'Purchasing',  '/purchasing'],
            'inventory'   => ['bi-boxes',          'Inventory',   '/inventory'],
            'hr'          => ['bi-people',         'HR',          '/hr'],
            'finance'     => ['bi-cash-stack',     'Finance',     '/finance'],
            'logistics'   => ['bi-truck',          'Logistics',   '/logistics'],
            'production'  => ['bi-flower1',        'Production',  '/production'],
            'processing'  => ['bi-gear',           'Processing',  '/processing'],
            'operational' => ['bi-gear-wide-connected', 'Operational', '/operational'],
            'qa'          => ['bi-patch-check',    'Quality Assurance', '/qa'],
        ];
        if (isset($deptLinks[$deptModule])):
            [$icon, $label, $path] = $deptLinks[$deptModule];
        ?>
        <a href="<?= BASE_URL . $path ?>" class="nav-item <?= ($activeMenu??'') === $deptModule ? 'active' : '' ?>">
            <i class="bi <?= $icon ?>"></i> <?= $label ?>
        </a>
        <?php if ($deptModule === 'sales'): ?>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/mark_order_paid.php" class="nav-item" target="_blank">
            <i class="bi bi-cash-coin"></i> Mark Paid
        </a>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($deptModule === 'finance'): ?>
        <a href="<?= BASE_URL ?>/monitoring" class="nav-item <?= ($activeMenu??'') === 'monitoring' ? 'active' : '' ?>"><i class="bi bi-graph-up-arrow"></i> Cost Monitoring</a>
        <a href="<?= BASE_URL ?>/ledger"     class="nav-item <?= ($activeMenu??'') === 'ledger'     ? 'active' : '' ?>"><i class="bi bi-journal-text"></i> Farmer Ledger</a>
        <a href="<?= BASE_URL ?>/reports"    class="nav-item <?= ($activeMenu??'') === 'reports'    ? 'active' : '' ?>"><i class="bi bi-bar-chart-line"></i> Reports</a>
        <?php endif; ?>
        <div class="nav-section">My Requests</div>
        <a href="<?= BASE_URL ?>/documents" class="nav-item <?= ($activeMenu??'') === 'documents' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-arrow-up"></i> Document Routing
        </a>
        <?php if ($deptModule !== 'quality'): ?>
        <a href="<?= BASE_URL ?>/approvals" class="nav-item <?= ($activeMenu??'') === 'approvals' ? 'active' : '' ?>">
            <i class="bi bi-hourglass-split"></i> My Submissions
        </a>
        <?php endif; ?>

        <?php else: ?>
        <!-- Admin / Manager: full nav -->
        <div class="nav-section">Operations</div>
        <a href="<?= BASE_URL ?>/sales"      class="nav-item <?= ($activeMenu??'') === 'sales'      ? 'active' : '' ?>"><i class="bi bi-cart3"></i> Sales</a>
        <a href="<?= BASE_URL ?>/purchasing" class="nav-item <?= ($activeMenu??'') === 'purchasing' ? 'active' : '' ?>"><i class="bi bi-bag-check"></i> Purchasing</a>
        <a href="<?= BASE_URL ?>/inventory"  class="nav-item <?= ($activeMenu??'') === 'inventory'  ? 'active' : '' ?>"><i class="bi bi-boxes"></i> Inventory</a>
        <a href="<?= BASE_URL ?>/logistics"  class="nav-item <?= ($activeMenu??'') === 'logistics'  ? 'active' : '' ?>"><i class="bi bi-truck"></i> Logistics</a>
        <a href="<?= BASE_URL ?>/operational" class="nav-item <?= ($activeMenu??'') === 'operational' ? 'active' : '' ?>"><i class="bi bi-gear-wide-connected"></i> Operational</a>
        <a href="<?= BASE_URL ?>/qa"         class="nav-item <?= ($activeMenu??'') === 'quality'    ? 'active' : '' ?>"><i class="bi bi-patch-check"></i> Quality Assurance</a>
        <a href="<?= BASE_URL ?>/monitoring" class="nav-item <?= ($activeMenu??'') === 'monitoring' ? 'active' : '' ?>"><i class="bi bi-graph-up-arrow"></i> Cost Monitoring</a>
        <div class="nav-section">Administration</div>
        <a href="<?= BASE_URL ?>/approvals" class="nav-item <?= ($activeMenu??'') === 'approvals' ? 'active' : '' ?>">
            <i class="bi bi-check2-square"></i> Approvals
            <?php
            $pendingCount = Database::getInstance()->fetchOne(
                "SELECT COUNT(*) as cnt FROM approval_requests ar
                 JOIN approval_steps acs ON acs.request_id=ar.id AND acs.step_order=ar.current_step AND acs.status='pending'
                 WHERE ar.status='pending'"
            );
            if (($pendingCount['cnt'] ?? 0) > 0): ?>
            <span class="badge bg-warning text-dark ms-auto"><?= $pendingCount['cnt'] ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/hr"      class="nav-item <?= ($activeMenu??'') === 'hr'      ? 'active' : '' ?>"><i class="bi bi-people"></i> Human Resources</a>
        <a href="<?= BASE_URL ?>/finance" class="nav-item <?= ($activeMenu??'') === 'finance' ? 'active' : '' ?>"><i class="bi bi-cash-stack"></i> Finance</a>
        <a href="<?= BASE_URL ?>/ledger" class="nav-item <?= ($activeMenu??'') === 'ledger' ? 'active' : '' ?>"><i class="bi bi-journal-text"></i> Farmer Ledger</a>
        <a href="<?= BASE_URL ?>/reports" class="nav-item <?= ($activeMenu??'') === 'reports' ? 'active' : '' ?>"><i class="bi bi-bar-chart-line"></i> Reports</a>
        <a href="<?= BASE_URL ?>/reports/archive" class="nav-item <?= ($activeMenu??'') === 'archive' ? 'active' : '' ?>"><i class="bi bi-archive"></i> Archives</a>
        <a href="<?= BASE_URL ?>/documents" class="nav-item <?= ($activeMenu??'') === 'documents' ? 'active' : '' ?>"><i class="bi bi-file-earmark-arrow-up"></i> Document Routing</a>
        <?php if ($role === 'admin'): ?>
        <div class="nav-section">System</div>
        <a href="<?= BASE_URL ?>/users" class="nav-item <?= ($activeMenu??'') === 'users' ? 'active' : '' ?>"><i class="bi bi-person-gear"></i> Users</a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Top Navbar -->
    <nav class="topbar">
        <button class="btn btn-sm btn-outline-secondary me-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
        <div class="ms-auto d-flex align-items-center gap-3">
            <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gm'])): ?>
            <span class="badge bg-warning text-dark" id="pendingBadge" title="Pending Approvals"></span>
            <?php endif; ?>

            <!-- Notification Bell -->
            <div class="dropdown" id="notifDropdown">
                <button class="btn btn-sm btn-outline-secondary position-relative" id="notifBtn" data-bs-toggle="dropdown" aria-expanded="false" onclick="loadNotifications()">
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifBadge"></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width:360px;max-height:480px;overflow-y:auto" id="notifMenu">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <strong class="small">Notifications</strong>
                        <button class="btn btn-xs btn-link text-muted p-0" onclick="markAllRead()">Mark all read</button>
                    </div>
                    <div id="notifList">
                        <div class="text-center text-muted py-4 small">Loading&#8369;</div>
                    </div>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'User') ?>
                    <span class="badge bg-success ms-1"><?= htmlspecialchars($_SESSION['user']['role'] ?? '') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show mx-3 mt-3" role="alert">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash']); endif; ?>

    <!-- Page Content -->
    <div class="page-content">
        <?= $content ?? '' ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/js/app.js"></script>
<script>
// &#8369;8369;”€ Notification system &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€
const NOTIF_BASE = '<?= BASE_URL ?>';
const NOTIF_ICONS = {
    doc_pending:        'bi-bell-fill text-warning',
    doc_approved:       'bi-check-circle-fill text-success',
    doc_rejected:       'bi-x-circle-fill text-danger',
    doc_fully_approved: 'bi-check2-all text-success',
    request_approved:   'bi-check-circle-fill text-success',
    request_rejected:   'bi-x-circle-fill text-danger',
};

function loadNotifications() {
    fetch(NOTIF_BASE + '/notifications')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notifBadge');
            if (data.unread > 0) {
                badge.textContent = data.unread > 9 ? '9+' : data.unread;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }

            const list = document.getElementById('notifList');
            if (!data.items.length) {
                list.innerHTML = '<div class="text-center text-muted py-4 small">No notifications</div>';
                return;
            }
            list.innerHTML = data.items.map(n => `
                <a href="${n.link || '#'}" class="d-flex gap-2 px-3 py-2 border-bottom text-decoration-none ${n.is_read == 0 ? 'bg-warning bg-opacity-10' : ''}"
                   onclick="markRead(${n.id})">
                    <i class="bi ${NOTIF_ICONS[n.type] || 'bi-info-circle text-secondary'} mt-1 flex-shrink-0"></i>
                    <div class="flex-fill">
                        <div class="small fw-semibold text-dark">${n.title}</div>
                        <div class="text-muted" style="font-size:11px">${n.message.substring(0,80)}${n.message.length>80?'&#8369;':''}</div>
                        <div class="text-muted" style="font-size:10px">${new Date(n.created_at).toLocaleString()}</div>
                    </div>
                    ${n.is_read == 0 ? '<span class="badge bg-warning text-dark align-self-start mt-1" style="font-size:9px">New</span>' : ''}
                </a>
            `).join('');
        });
}

function markRead(id) {
    const fd = new FormData(); fd.append('id', id);
    fetch(NOTIF_BASE + '/notifications/read', { method: 'POST', body: fd });
}

function markAllRead() {
    fetch(NOTIF_BASE + '/notifications/read-all', { method: 'POST' })
        .then(() => loadNotifications());
}

// Poll unread count every 30s and update badge without opening dropdown
function pollNotifCount() {
    fetch(NOTIF_BASE + '/notifications')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notifBadge');
            if (data.unread > 0) {
                badge.textContent = data.unread > 9 ? '9+' : data.unread;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }).catch(() => {});
}

// Initial load + poll every 30s
if (typeof NOTIF_BASE !== 'undefined') {
    pollNotifCount();
    setInterval(pollNotifCount, 30000);
}
</script>
</body>
</html>
