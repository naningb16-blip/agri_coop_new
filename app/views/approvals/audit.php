<?php
ob_start();
$pageTitle = 'Approval Audit Log';
$activeMenu = 'approvals';
$actionColors = ['submitted' => 'primary', 'approved' => 'success', 'rejected' => 'danger', 'commented' => 'secondary'];
?>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-journal-text me-2"></i>Full Audit Log</span>
        <a href="<?= BASE_URL ?>/approvals" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Request</th>
                    <th>Module</th>
                    <th>Actor</th>
                    <th>Step</th>
                    <th>Action</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($log as $entry): ?>
            <tr>
                <td class="text-muted small text-nowrap"><?= date('M d, Y H:i:s', strtotime($entry['created_at'])) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $entry['request_id'] ?>" class="text-decoration-none">
                        #<?= $entry['request_id'] ?> &#8369; <?= htmlspecialchars($entry['title']) ?>
                    </a>
                </td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($entry['module']) ?></span></td>
                <td><?= htmlspecialchars($entry['actor_name']) ?></td>
                <td class="text-center"><?= $entry['step_order'] ?></td>
                <td>
                    <span class="badge bg-<?= $actionColors[$entry['action']] ?? 'secondary' ?>">
                        <?= ucfirst($entry['action']) ?>
                    </span>
                </td>
                <td class="text-muted small"><?= htmlspecialchars($entry['remarks']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($log)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No audit entries yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
