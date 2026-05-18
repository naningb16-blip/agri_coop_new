<?php
ob_start();
$pageTitle = 'Approvals';
$activeMenu = 'approvals';
$user = $_SESSION['user'];
?>

<!-- Filter Bar -->
<div class="table-card mb-3">
    <div class="card-header">
        <span><i class="bi bi-funnel me-2"></i>Filter</span>
    </div>
    <div class="p-3">
        <form method="GET" action="<?= BASE_URL ?>/approvals" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','approved','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4">
                <label class="form-label small">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                    <option value="<?= htmlspecialchars($m['module']) ?>" <?= ($filters['module'] ?? '') === $m['module'] ? 'selected' : '' ?>><?= ucfirst($m['module']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4">
                <button class="btn btn-sm btn-primary w-100">Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Pending for My Role -->
<?php if (!empty($pending)): ?>
<div class="table-card mb-3">
    <div class="card-header">
        <span><i class="bi bi-bell-fill text-warning me-2"></i>Awaiting My Action
            <span class="badge bg-warning text-dark ms-1"><?= count($pending) ?></span>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Module</th><th>Title</th><th>Requested By</th><th>Step</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pending as $r): ?>
            <tr>
                <td>#<?= $r['id'] ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['module']) ?></span></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['requester_name']) ?></td>
                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($r['current_step_label']) ?></span></td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Review</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- All Requests -->
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-list-check me-2"></i>All Approval Requests</span>
        <?php if (in_array($user['role'], ['admin','gm'])): ?>
        <a href="<?= BASE_URL ?>/approvals/audit" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-journal-text me-1"></i>Audit Log
        </a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>ID</th><th>Module</th><th>Title</th><th>Requested By</th><th>Current Step</th><th>Status</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($all as $r): ?>
            <tr>
                <td>#<?= $r['id'] ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['module']) ?></span></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['requester_name']) ?></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                    <span class="badge bg-info text-dark"><?= htmlspecialchars($r['current_step_label'] ?? '&#8369;') ?></span>
                    <?php else: ?>
                    <span class="text-muted"></span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                <td><a href="<?= BASE_URL ?>/approvals/detail?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($all)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No approval requests found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
