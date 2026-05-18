<?php
ob_start();
$pageTitle = 'Attendance';
$activeMenu = 'hr';
$userRole = $_SESSION['user']['role'] ?? '';
$isGM = ($userRole === 'gm');
$canEdit = !$isGM; // GM cannot edit
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/hr" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to HR</a>
    <form method="GET" class="d-flex gap-2">
        <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date) ?>">
        <button class="btn btn-success btn-sm">Load</button>
    </form>
</div>

<?php if ($isGM): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>View Only Mode:</strong> As GM, you can view and filter attendance records but cannot edit them. Only HR department can modify attendance.
</div>
<?php endif; ?>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-calendar-check me-2 text-success"></i>Attendance - <?= date('F d, Y', strtotime($date)) ?></span>
        <?php if ($canEdit): ?>
        <button class="btn btn-success btn-sm" onclick="saveAll()"><i class="bi bi-save me-1"></i>Save All</button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Time In</th><th>Time Out</th><th>Status</th><th>Remarks</th></tr></thead>
            <tbody id="attBody">
            <?php
            $recordMap = [];
            foreach ($records as $r) $recordMap[$r['employee_id']] = $r;
            foreach ($employees as $e):
                $r = $recordMap[$e['id']] ?? null;
            ?>
            <tr data-emp="<?= $e['id'] ?>">
                <td><?= htmlspecialchars($e['full_name']) ?></td>
                <td>
                    <?php if ($canEdit): ?>
                    <input type="time" class="form-control form-control-sm att-in" value="<?= $r['time_in'] ?? '08:00' ?>">
                    <?php else: ?>
                    <span class="text-muted"><?= $r['time_in'] ?? '—' ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($canEdit): ?>
                    <input type="time" class="form-control form-control-sm att-out" value="<?= $r['time_out'] ?? '17:00' ?>">
                    <?php else: ?>
                    <span class="text-muted"><?= $r['time_out'] ?? '—' ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($canEdit): ?>
                    <select class="form-select form-select-sm att-status">
                        <?php foreach (['present','absent','late','half_day','leave'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($r['status'] ?? 'present') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <?php 
                    $statusColors = ['present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'half_day' => 'info', 'leave' => 'secondary'];
                    $status = $r['status'] ?? 'present';
                    ?>
                    <span class="badge bg-<?= $statusColors[$status] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($canEdit): ?>
                    <input type="text" class="form-control form-control-sm att-remarks" value="<?= htmlspecialchars($r['remarks'] ?? '') ?>">
                    <?php else: ?>
                    <span class="text-muted small"><?= htmlspecialchars($r['remarks'] ?? '—') ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canEdit): ?>
<script>
const BASE_URL = '<?= BASE_URL ?>';
function saveAll() {
    const rows = document.querySelectorAll('#attBody tr');
    const records = [];
    rows.forEach(row => {
        records.push({
            employee_id: row.dataset.emp,
            date: '<?= $date ?>',
            time_in:  row.querySelector('.att-in').value,
            time_out: row.querySelector('.att-out').value,
            status:   row.querySelector('.att-status').value,
            remarks:  row.querySelector('.att-remarks').value,
        });
    });
    const form = new FormData();
    records.forEach((r, i) => Object.entries(r).forEach(([k,v]) => form.append(`records[${i}][${k}]`, v)));
    fetch(BASE_URL + '/hr/attendance', { method: 'POST', body: form })
        .then(r => r.json())
        .then(res => alert(res.message));
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

