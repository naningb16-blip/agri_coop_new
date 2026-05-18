<?php
ob_start();
$pageTitle = 'Employee Archive';
$activeMenu = 'hr';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/hr" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Employees
    </a>
</div>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-archive me-2 text-warning"></i>Archived Employees</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Position</th><th>Salary</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($employees as $e): ?>
            <tr>
                <td><code><?= htmlspecialchars($e['employee_code']) ?></code></td>
                <td><?= htmlspecialchars($e['full_name'] ?: '(Unknown)') ?></td>
                <td><?= htmlspecialchars($e['dept_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($e['position'] ?? '-') ?></td>
                <td>&#8369;<?= number_format($e['salary'], 2) ?></td>
                <td>
                    <button class="btn btn-xs btn-outline-success me-1"
                        onclick="unarchiveEmployee(<?= $e['id'] ?>, '<?= htmlspecialchars($e['full_name']) ?>')">
                        <i class="bi bi-arrow-counterclockwise"></i> Unarchive
                    </button>
                    <button class="btn btn-xs btn-outline-danger"
                        onclick="deleteEmployee(<?= $e['id'] ?>, '<?= htmlspecialchars($e['full_name']) ?>')">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($employees)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No archived employees.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
function unarchiveEmployee(id, name) {
    if (!confirm('Restore ' + name + ' as an active employee?')) return;
    Ajax.post(BASE_URL + '/hr/unarchive-employee', { id }, res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 800);
    });
}
function deleteEmployee(id, name) {
    if (!confirm('Permanently delete ' + name + '? This cannot be undone.')) return;
    Ajax.post(BASE_URL + '/hr/delete-employee', { id }, res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 800);
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

