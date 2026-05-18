<?php
ob_start();
$pageTitle  = 'Human Resources';
$activeMenu = 'hr';
$userRole   = $_SESSION['user']['role'] ?? '';
$isReadOnly = in_array($userRole, ['gm', 'manager']);
?>

<?php if ($userRole === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all HR data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/hr/attendance" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar-check me-1"></i>Attendance
        </a>
        <a href="<?= BASE_URL ?>/hr/payroll" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-cash me-1"></i>Payroll
        </a>
        <a href="<?= BASE_URL ?>/hr/archive" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-archive me-1"></i>Archive
        </a>
    </div>
    <?php if (!$isReadOnly && $_SESSION['user']['role'] !== 'gm'): ?>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#empModal">
        <i class="bi bi-person-plus me-1"></i>Add Employee
    </button>
    <?php endif; ?>
</div>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-people me-2 text-success"></i>Employees</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Position</th><th>Salary</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($employees as $e): ?>
            <tr>
                <td><code><?= htmlspecialchars($e['employee_code']) ?></code></td>
                <td><?= htmlspecialchars($e['full_name'] ?: '(Unknown)') ?></td>
                <td><?= htmlspecialchars($e['dept_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($e['position'] ?? '-') ?></td>
                <td>&#8369;<?= number_format($e['salary'], 2) ?></td>
                <td><span class="badge badge-<?= $e['status'] === 'active' ? 'approved' : ($e['status'] === 'pending' ? 'pending' : 'rejected') ?>"><?= ucfirst($e['status']) ?></span></td>
                <td>
                    <?php if (!$isReadOnly && $_SESSION['user']['role'] !== 'gm'): ?>
                    <button class="btn btn-xs btn-outline-secondary"
                        onclick="editEmployee(<?= htmlspecialchars(json_encode($e)) ?>)">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-warning ms-1"
                        onclick="archiveEmployee(<?= $e['id'] ?>, '<?= htmlspecialchars($e['full_name']) ?>')"
                        title="Archive">
                        <i class="bi bi-archive"></i>
                    </button>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/hr/employee-docs?id=<?= $e['id'] ?>" class="btn btn-xs btn-outline-info ms-1" title="Documents">
                        <i class="bi bi-folder2"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($employees)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No employees found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Employee Modal -->
<div class="modal fade" id="empModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="empModalTitle">Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>/hr/save-employee" method="POST" data-ajax data-reload="true">
                <input type="hidden" name="id" id="empId" value="0">
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="empName" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <input type="text" name="department_name" id="empDeptInput" class="form-control"
                               list="deptList" placeholder="Type or select department&#8369;" autocomplete="off">
                        <input type="hidden" name="department_id" id="empDept">
                        <datalist id="deptList">
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= htmlspecialchars($d['name']) ?>" data-id="<?= $d['id'] ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" id="empPosition" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Monthly Salary (&#8369;)</label>
                        <input type="number" name="salary" id="empSalary" class="form-control" step="0.01" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
function editEmployee(emp) {
    document.getElementById('empModalTitle').textContent = 'Edit Employee';
    document.getElementById('empId').value = emp.id;
    document.getElementById('empName').value = emp.full_name;
    document.getElementById('empDept').value = emp.department_id || '';
    // populate the visible text input with the dept name
    const deptOpt = [...document.querySelectorAll('#deptList option')].find(o => o.dataset.id == emp.department_id);
    document.getElementById('empDeptInput').value = deptOpt ? deptOpt.value : (emp.dept_name || '');
    document.getElementById('empPosition').value = emp.position || '';
    document.getElementById('empSalary').value = emp.salary || 0;
    new bootstrap.Modal(document.getElementById('empModal')).show();
}
function archiveEmployee(id, name) {
    if (!confirm('Archive ' + name + '? They will be moved to the archive.')) return;
    Ajax.post(BASE_URL + '/hr/archive-employee', { id }, res => {
        showToast(res.message, res.success ? 'warning' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 800);
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>


