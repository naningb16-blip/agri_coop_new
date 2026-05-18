<?php
ob_start();
$pageTitle  = 'GM Dashboard - Approvals Only';
$activeMenu = 'dashboard';
?>

<!-- GM Dashboard - Approvals Only -->
<div class="alert alert-primary mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-shield-check me-3 fs-4"></i>
        <div>
            <h5 class="mb-1">General Manager Dashboard</h5>
            <p class="mb-0">You are the <strong>sole approver</strong> for all department requests. You can view all department data for context and approve/reject requests directly.</p>
        </div>
    </div>
</div>

<!-- Low Stock Alert -->
<?php if (!empty($low_stock_items)): ?>
<div class="alert alert-danger mb-4">
    <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
        <div class="flex-grow-1">
            <h5 class="mb-2"><i class="bi bi-box-seam"></i> Low Stock Alert (<?= count($low_stock_items) ?> items)</h5>
            <p class="mb-2">The following items are below their reorder levels and need restocking:</p>
            <ul class="mb-0">
            <?php foreach ($low_stock_items as $item): ?>
                <li>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>: 
                    Current stock <span class="badge bg-danger"><?= number_format($item['current_stock'], 2) ?> <?= htmlspecialchars($item['unit']) ?></span>
                    (Reorder at: <?= number_format($item['reorder_level'], 2) ?> <?= htmlspecialchars($item['unit']) ?>)
                    — Shortage: <strong><?= number_format($item['shortage'], 2) ?> <?= htmlspecialchars($item['unit']) ?></strong>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div class="row g-3 flex-grow-1">
        <!-- Approval Statistics -->
        <?php foreach ([
            [$stats['my_pending_approvals'], 'My Pending Approvals', 'warning', 'hourglass-split'],
            [$stats['total_pending_approvals'], 'Total Pending Approvals', 'info', 'clock-history'],
            [$stats['approved_today'], 'Approved Today', 'success', 'check-circle'],
            [$stats['rejected_today'], 'Rejected Today', 'danger', 'x-circle'],
            [$stats['approved_this_month'], 'Approved This Month', 'success', 'check2-all'],
            [$stats['rejected_this_month'], 'Rejected This Month', 'danger', 'x-octagon'],
        ] as [$val,$label,$color,$icon]): ?>
        <div class="col-6 col-xl-2">
            <div class="stat-card <?= $color ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-value"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
                    <i class="bi bi-<?= $icon ?> stat-icon"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('GM Approval Dashboard')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<!-- Main Content Row -->
<div class="row g-3">
    <!-- My Pending Approvals -->
    <div class="col-lg-8">
        <div class="table-card">
            <div class="card-header">
                <span><i class="bi bi-hourglass-split me-2 text-warning"></i>My Pending Approvals</span>
                <a href="<?= BASE_URL ?>/approvals" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Title</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Priority</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($my_approvals as $a): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($a['module']) ?></span></td>
                        <td class="small"><?= htmlspecialchars(mb_strimwidth($a['title']??'',0,40,'…')) ?></td>
                        <td class="small"><?= htmlspecialchars($a['requester']) ?></td>
                        <td class="text-muted small"><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                        <td>
                            <?php 
                            $priority = $a['priority'] ?? 'medium';
                            $priorityColors = ['low' => 'secondary', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
                            ?>
                            <span class="badge bg-<?= $priorityColors[$priority] ?? 'primary' ?>"><?= ucfirst($priority) ?></span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $a['id'] ?>" class="btn btn-xs btn-outline-primary">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($my_approvals)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">No pending approvals.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Module Statistics -->
    <div class="col-lg-4">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-bar-chart me-2 text-info"></i>Approvals by Module (30 days)</span></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Pending</th>
                            <th>Approved</th>
                            <th>Rejected</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($module_stats as $m): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars(ucfirst($m['module'])) ?></td>
                        <td><span class="badge bg-warning text-dark"><?= $m['pending'] ?></span></td>
                        <td><span class="badge bg-success"><?= $m['approved'] ?></span></td>
                        <td><span class="badge bg-danger"><?= $m['rejected'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($module_stats)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No approval data for the last 30 days.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Decisions -->
    <div class="col-lg-8">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-clock-history me-2 text-success"></i>My Recent Decisions</span></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Title</th>
                            <th>Requested By</th>
                            <th>My Decision</th>
                            <th>Decision Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_processed as $r): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['module']) ?></span></td>
                        <td class="small"><?= htmlspecialchars(mb_strimwidth($r['title']??'',0,50,'…')) ?></td>
                        <td class="small"><?= htmlspecialchars($r['requester']) ?></td>
                        <td>
                            <span class="badge bg-<?= $r['my_decision'] === 'approved' ? 'success' : 'danger' ?>">
                                <?= ucfirst($r['my_decision']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= date('M d, Y H:i', strtotime($r['decision_date'])) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/approvals/detail?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-secondary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_processed)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">No recent decisions found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Today's Attendance -->
    <div class="col-lg-4">
        <div class="table-card">
            <div class="card-header">
                <span><i class="bi bi-calendar-check me-2 text-primary"></i>Attendance</span>
                <a href="<?= BASE_URL ?>/hr/attendance" class="btn btn-xs btn-outline-primary">Full View</a>
            </div>
            <div class="p-2 border-bottom">
                <div class="row g-2">
                    <div class="col-8">
                        <input type="date" id="attendanceDate" class="form-control form-control-sm" 
                               value="<?= date('Y-m-d') ?>" onchange="loadAttendanceByDate()">
                    </div>
                    <div class="col-4">
                        <button class="btn btn-sm btn-primary w-100" onclick="loadAttendanceByDate()">
                            <i class="bi bi-search"></i> View
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="sticky-top bg-white">
                        <tr>
                            <th>Employee</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceListBody">
                    <?php 
                    $statusColors = [
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'half_day' => 'info',
                        'leave' => 'secondary'
                    ];
                    foreach ($attendance_today as $att): 
                    ?>
                    <tr style="cursor: pointer;" onclick="viewEmployeeAttendance(<?= $att['id'] ?>, '<?= htmlspecialchars($att['full_name']) ?>')">
                        <td>
                            <div class="small fw-semibold"><?= htmlspecialchars($att['full_name']) ?></div>
                            <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($att['position'] ?? '') ?></div>
                        </td>
                        <td>
                            <?php if ($att['status']): ?>
                            <span class="badge bg-<?= $statusColors[$att['status']] ?? 'secondary' ?>">
                                <?= ucfirst(str_replace('_', ' ', $att['status'])) ?>
                            </span>
                            <?php if ($att['time_in']): ?>
                            <div class="text-muted" style="font-size: 10px;"><?= date('H:i', strtotime($att['time_in'])) ?></div>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="badge bg-secondary">No Record</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($attendance_today)): ?>
                    <tr><td colspan="2" class="text-center text-muted py-3">No employees found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Employee Attendance Modal -->
<div class="modal fade" id="employeeAttendanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-badge me-2"></i>
                    <span id="modalEmployeeName"></span> - Attendance Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">Select Month</label>
                        <input type="month" id="attendanceMonth" class="form-control form-control-sm" value="<?= date('Y-m') ?>" onchange="loadEmployeeAttendance()">
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row g-2 mb-3" id="attendanceSummary"></div>
                
                <!-- Attendance Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody">
                            <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mt-3">
    <div class="col-12">
        <div class="table-card">
            <div class="card-header"><span><i class="bi bi-lightning me-2 text-primary"></i>Quick Actions</span></div>
            <div class="p-3">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>/approvals" class="btn btn-primary">
                        <i class="bi bi-list-check me-1"></i>View All Approvals
                    </a>
                    <a href="<?= BASE_URL ?>/approvals?filter=pending" class="btn btn-warning">
                        <i class="bi bi-hourglass-split me-1"></i>Pending Only
                    </a>
                    <a href="<?= BASE_URL ?>/approvals?filter=urgent" class="btn btn-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i>Urgent Approvals
                    </a>
                    <a href="<?= BASE_URL ?>/approvals/audit" class="btn btn-info">
                        <i class="bi bi-search me-1"></i>Approval Audit
                    </a>
                    <a href="<?= BASE_URL ?>/hr/attendance" class="btn btn-success">
                        <i class="bi bi-calendar-check me-1"></i>Full Attendance</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let currentEmployeeId = null;

// Load attendance by date
function loadAttendanceByDate() {
    const date = document.getElementById('attendanceDate').value;
    const tbody = document.getElementById('attendanceListBody');
    
    tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Loading...</td></tr>';
    
    fetch(`${BASE_URL}/dashboard/attendance-by-date?date=${date}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Error loading data</td></tr>';
                return;
            }
            
            if (data.attendance.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">No employees found.</td></tr>';
                return;
            }
            
            const statusColors = {
                present: 'success',
                absent: 'danger',
                late: 'warning',
                half_day: 'info',
                leave: 'secondary'
            };
            
            tbody.innerHTML = data.attendance.map(att => `
                <tr style="cursor: pointer;" onclick="viewEmployeeAttendance(${att.id}, '${att.full_name.replace(/'/g, "\\'")}')">
                    <td>
                        <div class="small fw-semibold">${att.full_name}</div>
                        <div class="text-muted" style="font-size: 10px;">${att.position || ''}</div>
                    </td>
                    <td>
                        ${att.status ? `
                            <span class="badge bg-${statusColors[att.status] || 'secondary'}">
                                ${att.status.replace('_', ' ').toUpperCase()}
                            </span>
                            ${att.time_in ? `<div class="text-muted" style="font-size: 10px;">${att.time_in.substring(0, 5)}</div>` : ''}
                        ` : '<span class="badge bg-secondary">No Record</span>'}
                    </td>
                </tr>
            `).join('');
        })
        .catch(e => {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Error loading data</td></tr>';
            console.error(e);
        });
}

// View employee attendance
function viewEmployeeAttendance(employeeId, employeeName) {
    currentEmployeeId = employeeId;
    document.getElementById('modalEmployeeName').textContent = employeeName;
    document.getElementById('attendanceMonth').value = '<?= date('Y-m') ?>';
    
    const modal = new bootstrap.Modal(document.getElementById('employeeAttendanceModal'));
    modal.show();
    
    loadEmployeeAttendance();
}

// Load employee attendance data
function loadEmployeeAttendance() {
    if (!currentEmployeeId) return;
    
    const month = document.getElementById('attendanceMonth').value;
    const tbody = document.getElementById('attendanceTableBody');
    const summary = document.getElementById('attendanceSummary');
    
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>';
    summary.innerHTML = '';
    
    fetch(`${BASE_URL}/dashboard/employee-attendance?employee_id=${currentEmployeeId}&month=${month}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>';
                return;
            }
            
            // Update summary
            const s = data.summary;
            summary.innerHTML = `
                <div class="col"><div class="stat-card success"><div class="stat-value">${s.present || 0}</div><div class="stat-label">Present</div></div></div>
                <div class="col"><div class="stat-card danger"><div class="stat-value">${s.absent || 0}</div><div class="stat-label">Absent</div></div></div>
                <div class="col"><div class="stat-card warning"><div class="stat-value">${s.late || 0}</div><div class="stat-label">Late</div></div></div>
                <div class="col"><div class="stat-card info"><div class="stat-value">${s.half_day || 0}</div><div class="stat-label">Half Day</div></div></div>
                <div class="col"><div class="stat-card secondary"><div class="stat-value">${s.leave_days || 0}</div><div class="stat-label">Leave</div></div></div>
            `;
            
            // Update table
            if (data.attendance.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No attendance records for this month</td></tr>';
                return;
            }
            
            const statusColors = {
                present: 'success',
                absent: 'danger',
                late: 'warning',
                half_day: 'info',
                leave: 'secondary'
            };
            
            tbody.innerHTML = data.attendance.map(att => `
                <tr>
                    <td>${new Date(att.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
                    <td><span class="badge bg-${statusColors[att.status] || 'secondary'}">${att.status.replace('_', ' ').toUpperCase()}</span></td>
                    <td>${att.time_in || '—'}</td>
                    <td>${att.time_out || '—'}</td>
                    <td class="small">${att.remarks || '—'}</td>
                </tr>
            `).join('');
        })
        .catch(e => {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>';
            console.error(e);
        });
}

// Auto-refresh pending approvals count every 30 seconds
function refreshApprovalCounts() {
    fetch(BASE_URL + '/dashboard/stats')
        .then(r => r.json())
        .then(data => {
            // Update any dynamic counters if needed
            console.log('Approval counts refreshed');
        })
        .catch(e => console.log('Failed to refresh approval counts'));
}

// Refresh every 30 seconds
setInterval(refreshApprovalCounts, 30000);
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>