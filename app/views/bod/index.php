<?php
ob_start();
$pageTitle  = 'Board of Directors &#8369; Transaction Monitor';
$activeMenu = 'bod';
$statusColors = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
?>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="d-flex justify-content-between align-items-start">
            <div><div class="stat-value"><?= $summary['total'] ?? 0 ?></div><div class="stat-label">Total Requests</div></div>
            <i class="bi bi-journal-text stat-icon"></i>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card warning"><div class="d-flex justify-content-between align-items-start">
            <div><div class="stat-value"><?= $summary['pending'] ?? 0 ?></div><div class="stat-label">Pending</div></div>
            <i class="bi bi-hourglass-split stat-icon"></i>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="d-flex justify-content-between align-items-start">
            <div><div class="stat-value"><?= $summary['approved'] ?? 0 ?></div><div class="stat-label">Approved</div></div>
            <i class="bi bi-check-circle stat-icon text-success"></i>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card danger"><div class="d-flex justify-content-between align-items-start">
            <div><div class="stat-value"><?= $summary['rejected'] ?? 0 ?></div><div class="stat-label">Rejected</div></div>
            <i class="bi bi-x-circle stat-icon"></i>
        </div></div>
    </div>
</div>

<!-- By Department -->
<div class="row g-3 mb-3">
    <?php foreach ($byModule as $m): ?>
    <div class="col-6 col-md-2">
        <div class="table-card p-3 text-center">
            <div class="fw-bold text-capitalize"><?= htmlspecialchars($m['module']) ?></div>
            <div class="small text-muted mt-1">
                <span class="text-warning"><?= $m['pending'] ?> pending</span> &bull;
                <span class="text-success"><?= $m['approved'] ?> approved</span> &bull;
                <span class="text-danger"><?= $m['rejected'] ?> rejected</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Monthly Sales Chart -->
<div class="table-card mb-3">
    <div class="card-header">
        <span><i class="bi bi-graph-up me-2 text-success"></i>Monthly Sales (Last 12 Months)</span>
    </div>
    <div class="p-3">
        <canvas id="monthlySalesChart" style="max-height:300px"></canvas>
    </div>
</div>

<!-- All Transactions -->
<div class="table-card mb-3">
    <div class="card-header">
        <span><i class="bi bi-journal-check me-2 text-primary"></i>All Department Transactions</span>
        <span class="badge bg-secondary"><?= count($allRequests) ?> total</span>
        <input type="text" id="bodSearch" class="form-control form-control-sm ms-auto" placeholder="Search&#8369;" style="width:200px" oninput="filterBOD()">
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="bodTable">
            <thead>
                <tr>
                    <th>Date</th><th>Module</th><th>Title</th><th>Requested By</th>
                    <th>Manager</th><th>Manager Action</th>
                    <th>GM</th><th>GM Action</th>
                    <th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allRequests as $r): ?>
            <tr>
                <td class="text-muted small text-nowrap"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                <td><span class="badge bg-secondary"><?= ucfirst($r['module']) ?></span></td>
                <td class="small"><?= htmlspecialchars(mb_strimwidth($r['title'], 0, 45, '&#8369;')) ?></td>
                <td class="small"><?= htmlspecialchars($r['requester_name']) ?></td>
                <td class="small"><?= htmlspecialchars($r['manager_name'] ?? '&#8369;') ?></td>
                <td>
                    <?php if ($r['manager_status']): ?>
                    <span class="badge badge-<?= $r['manager_status'] ?>"><?= ucfirst($r['manager_status']) ?></span>
                    <?php if ($r['manager_at']): ?>
                    <div class="text-muted" style="font-size:10px"><?= date('M d H:i', strtotime($r['manager_at'])) ?></div>
                    <?php endif; ?>
                    <?php else: ?><span class="text-muted small">Pending</span><?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars($r['gm_name'] ?? '&#8369;') ?></td>
                <td>
                    <?php if ($r['gm_status']): ?>
                    <span class="badge badge-<?= $r['gm_status'] ?>"><?= ucfirst($r['gm_status']) ?></span>
                    <?php if ($r['gm_at']): ?>
                    <div class="text-muted" style="font-size:10px"><?= date('M d H:i', strtotime($r['gm_at'])) ?></div>
                    <?php endif; ?>
                    <?php else: ?><span class="text-muted small">&#8369;</span><?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td>
                    <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($allRequests)): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">No transactions yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Activity Feed -->
<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-activity me-2 text-info"></i>Recent Activity (Last 30 Days)</span>
        <span class="badge bg-secondary"><?= count($recentActivity) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Date/Time</th><th>Module</th><th>Title</th><th>Action By</th><th>Requester</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($recentActivity as $a): ?>
            <tr>
                <td class="text-muted small text-nowrap"><?= date('M d, Y H:i', strtotime($a['created_at'])) ?></td>
                <td><span class="badge bg-secondary"><?= ucfirst($a['module']) ?></span></td>
                <td class="small"><?= htmlspecialchars(mb_strimwidth($a['title'], 0, 40, '&#8369;')) ?></td>
                <td class="small fw-semibold"><?= htmlspecialchars($a['actor_name']) ?></td>
                <td class="small"><?= htmlspecialchars($a['requester_name']) ?></td>
                <td>
                    <?php $ac = $a['action']; $bc = $ac==='approved'?'success':($ac==='rejected'?'danger':'secondary'); ?>
                    <span class="badge bg-<?= $bc ?>"><?= ucfirst($ac) ?></span>
                    <?php if ($a['remarks']): ?>
                    <div class="text-muted" style="font-size:10px"><?= htmlspecialchars(mb_strimwidth($a['remarks'],0,40,'&#8369;')) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentActivity)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No activity in the last 30 days.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
function filterBOD() {
    const q = document.getElementById('bodSearch').value.toLowerCase();
    document.querySelectorAll('#bodTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// Monthly Sales Chart
const monthlySalesData = <?= json_encode($monthlySales ?? []) ?>;
const months = monthlySalesData.map(d => {
    const [year, month] = d.month.split('-');
    return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
});
const salesAmounts = monthlySalesData.map(d => parseFloat(d.total_sales));
const paidAmounts = monthlySalesData.map(d => parseFloat(d.paid_amount));

new Chart(document.getElementById('monthlySalesChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            {
                label: 'Total Sales',
                data: salesAmounts,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Paid Amount',
                data: paidAmounts,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
