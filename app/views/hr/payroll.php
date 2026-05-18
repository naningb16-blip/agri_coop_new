<?php
ob_start();
$pageTitle = 'Payroll';
$activeMenu = 'hr';
?>

<?php if (($_SESSION['user']['role'] ?? '') === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all payroll data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/hr" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to HR</a>
    <?php if (($_SESSION['user']['role'] ?? '') !== 'gm'): ?>
    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payrollModal">
        <i class="bi bi-plus-circle me-1"></i>Generate Payroll
    </button>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value" style="font-size:1.1rem">&#8369;<?= number_format($totalNetPay, 2) ?></div>
                    <div class="stat-label">Total Net Pay</div>
                </div>
                <i class="bi bi-cash-stack stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-cash me-2 text-success"></i>Payroll Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.82rem">
            <thead>
                <tr>
                    <th>Employee</th><th>Dept</th><th>Period</th><th>Basic Pay</th>
                    <th class="text-danger">SSS</th>
                    <th class="text-danger">Pag-ibig</th>
                    <th class="text-danger">PhilHealth</th>
                    <th class="text-danger">Other Ded.</th>
                    <th class="text-danger">Total Ded.</th>
                    <th class="text-success">Rest Day</th>
                    <th class="text-success">Sp. Holiday</th>
                    <th class="text-success">Reg. Holiday</th>
                    <th class="text-success">Other Bon.</th>
                    <th class="text-success">Total Bon.</th>
                    <th>Net Pay</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payrolls as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($p['dept_name'] ?? '-') ?></td>
                <td class="text-muted small"><?= date('M d', strtotime($p['period_start'])) ?> - <?= date('M d, Y', strtotime($p['period_end'])) ?></td>
                <td>&#8369;<?= number_format($p['basic_pay'], 2) ?></td>
                <td class="text-danger small">&#8369;<?= number_format($p['sss_amount'] ?? 0, 2) ?><?php if (($p['sss_pct'] ?? 0) > 0): ?> <span class="text-muted">(<?= $p['sss_pct'] ?>%)</span><?php endif; ?></td>
                <td class="text-danger small">&#8369;<?= number_format($p['pagibig_amount'] ?? 0, 2) ?><?php if (($p['pagibig_pct'] ?? 0) > 0): ?> <span class="text-muted">(<?= $p['pagibig_pct'] ?>%)</span><?php endif; ?></td>
                <td class="text-danger small">&#8369;<?= number_format($p['philhealth_amount'] ?? 0, 2) ?><?php if (($p['philhealth_pct'] ?? 0) > 0): ?> <span class="text-muted">(<?= $p['philhealth_pct'] ?>%)</span><?php endif; ?></td>
                <td class="text-danger small">&#8369;<?= number_format($p['other_deductions'] ?? 0, 2) ?><?php if (!empty($p['other_deductions_note'])): ?><span class="text-muted d-block" style="font-size:.72rem"><?= htmlspecialchars($p['other_deductions_note']) ?></span><?php endif; ?></td>
                <td class="text-danger fw-semibold">-&#8369;<?= number_format($p['deductions'], 2) ?></td>
                <td class="text-success small">&#8369;<?= number_format($p['rest_day_amount'] ?? 0, 2) ?><?php if (($p['rest_day_pct'] ?? 0) > 0): ?> <span class="text-muted">(<?= $p['rest_day_pct'] ?>%)</span><?php endif; ?></td>
                <td class="text-success small">&#8369;<?= number_format($p['special_holiday_amount'] ?? 0, 2) ?><?php if (($p['special_holiday_pct'] ?? 0) > 0): ?> <span class="text-muted">(<?= $p['special_holiday_pct'] ?>%)</span><?php endif; ?></td>
                <td class="text-success small">&#8369;<?= number_format($p['regular_holiday_amount'] ?? 0, 2) ?><?php if (($p['regular_holiday_pct'] ?? 0) > 0): ?> <span class="text-muted">(<?= $p['regular_holiday_pct'] ?>%)</span><?php endif; ?></td>
                <td class="text-success small">&#8369;<?= number_format($p['other_bonuses'] ?? 0, 2) ?><?php if (!empty($p['other_bonuses_note'])): ?><span class="text-muted d-block" style="font-size:.72rem"><?= htmlspecialchars($p['other_bonuses_note']) ?></span><?php endif; ?></td>
                <td class="text-success fw-semibold">+&#8369;<?= number_format($p['bonuses'], 2) ?></td>
                <td class="fw-bold">&#8369;<?= number_format($p['net_pay'], 2) ?></td>
                <td><span class="badge badge-<?= $p['status'] === 'paid' ? 'approved' : 'pending' ?>"><?= ucfirst($p['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($payrolls)): ?>
            <tr><td colspan="16" class="text-center text-muted py-4">No payroll records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="payrollModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash me-2"></i>Generate Payroll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>/hr/payroll/generate" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="payrollEmployee" class="form-select" required onchange="calcPay()">
                            <option value="">Select employee...</option>
                            <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>" data-salary="<?= $e['salary'] ?>">
                                <?= htmlspecialchars($e['full_name']) ?> - &#8369;<?= number_format($e['salary'],2) ?>/mo
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Period Start</label>
                        <input type="date" name="period_start" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Period End</label>
                        <input type="date" name="period_end" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Pay Type</label>
                        <select name="pay_type" id="payType" class="form-select" onchange="togglePayType()">
                            <option value="monthly">Monthly</option>
                            <option value="daily">Daily Rate</option>
                            <option value="piece_rate">Piece Rate</option>
                        </select>
                    </div>
                    <div id="laborFields" style="display:none" class="col-12">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" id="daysLabel">Days Worked</label>
                                <input type="number" name="days_worked" id="daysWorked" class="form-control" step="0.5" value="0" oninput="calcPay()">
                            </div>
                            <div class="col-6">
                                <label class="form-label" id="rateLabel">Daily Rate</label>
                                <input type="number" name="daily_rate" id="dailyRate" class="form-control" step="0.01" value="0" oninput="calcPay()">
                            </div>
                        </div>
                    </div>

                    <!-- DEDUCTIONS -->
                    <div class="col-12"><div class="fw-semibold border-bottom pb-1 text-danger"><i class="bi bi-dash-circle me-1"></i>Deductions</div></div>

                    <div class="col-md-6">
                        <label class="form-label">SSS (%)</label>
                        <input type="number" name="sss_pct" id="sssPct" class="form-control" value="0" step="0.01" min="0" max="100" oninput="calcPay()">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="p-2 bg-danger bg-opacity-10 rounded w-100 text-center">
                            <div class="small text-muted">SSS Amount</div>
                            <div class="fw-semibold text-danger" id="sssAmt">&#8369;0.00</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pag-ibig (%)</label>
                        <input type="number" name="pagibig_pct" id="pagibigPct" class="form-control" value="0" step="0.01" min="0" max="100" oninput="calcPay()">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="p-2 bg-danger bg-opacity-10 rounded w-100 text-center">
                            <div class="small text-muted">Pag-ibig Amount</div>
                            <div class="fw-semibold text-danger" id="pagibigAmt">&#8369;0.00</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">PhilHealth (%)</label>
                        <input type="number" name="philhealth_pct" id="philhealthPct" class="form-control" value="0" step="0.01" min="0" max="100" oninput="calcPay()">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="p-2 bg-danger bg-opacity-10 rounded w-100 text-center">
                            <div class="small text-muted">PhilHealth Amount</div>
                            <div class="fw-semibold text-danger" id="philhealthAmt">&#8369;0.00</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Other Deductions (&#8369;)</label>
                        <input type="number" name="other_deductions" id="otherDed" class="form-control" value="0" step="0.01" min="0" oninput="calcPay()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Other Deductions Note</label>
                        <input type="text" name="other_deductions_note" class="form-control" placeholder="e.g. Loan, Cash advance">
                    </div>

                    <!-- BONUSES -->
                    <div class="col-12"><div class="fw-semibold border-bottom pb-1 text-success"><i class="bi bi-plus-circle me-1"></i>Bonuses / Allowances</div></div>

                    <div class="col-md-6">
                        <label class="form-label">Rest Day (%)</label>
                        <input type="number" name="rest_day_pct" id="restDayPct" class="form-control" value="0" step="0.01" min="0" oninput="calcPay()">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="p-2 bg-success bg-opacity-10 rounded w-100 text-center">
                            <div class="small text-muted">Rest Day Amount</div>
                            <div class="fw-semibold text-success" id="restDayAmt">&#8369;0.00</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Special Holiday (%)</label>
                        <input type="number" name="special_holiday_pct" id="spHolidayPct" class="form-control" value="0" step="0.01" min="0" oninput="calcPay()">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="p-2 bg-success bg-opacity-10 rounded w-100 text-center">
                            <div class="small text-muted">Special Holiday Amount</div>
                            <div class="fw-semibold text-success" id="spHolidayAmt">&#8369;0.00</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Regular Holiday (%)</label>
                        <input type="number" name="regular_holiday_pct" id="regHolidayPct" class="form-control" value="0" step="0.01" min="0" oninput="calcPay()">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="p-2 bg-success bg-opacity-10 rounded w-100 text-center">
                            <div class="small text-muted">Regular Holiday Amount</div>
                            <div class="fw-semibold text-success" id="regHolidayAmt">&#8369;0.00</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Other Bonuses (&#8369;)</label>
                        <input type="number" name="other_bonuses" id="otherBon" class="form-control" value="0" step="0.01" min="0" oninput="calcPay()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Other Bonuses Note</label>
                        <input type="text" name="other_bonuses_note" class="form-control" placeholder="e.g. 13th month, Incentive">
                    </div>

                    <!-- NET PAY PREVIEW -->
                    <div class="col-12">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <div class="row text-center g-2 mb-2">
                                <div class="col-4">
                                    <div class="small text-muted">Basic Pay</div>
                                    <div class="fw-semibold" id="basicPayPreview">&#8369;0.00</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Total Deductions</div>
                                    <div class="fw-semibold text-danger" id="totalDedPreview">-&#8369;0.00</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Total Bonuses</div>
                                    <div class="fw-semibold text-success" id="totalBonPreview">+&#8369;0.00</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                <span class="fw-semibold">Net Pay</span>
                                <span class="fw-bold fs-5 text-success" id="netPayPreview">&#8369;0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
function fmt(n) { return '\u20B1' + parseFloat(n).toLocaleString('en-PH', {minimumFractionDigits:2}); }
function togglePayType() {
    const pt = document.getElementById('payType').value;
    document.getElementById('laborFields').style.display = pt === 'monthly' ? 'none' : '';
    document.getElementById('daysLabel').textContent = pt === 'piece_rate' ? 'Units Produced' : 'Days Worked';
    document.getElementById('rateLabel').textContent = pt === 'piece_rate' ? 'Rate per Unit' : 'Daily Rate';
    calcPay();
}
function calcPay() {
    const pt  = document.getElementById('payType').value;
    const sel = document.getElementById('payrollEmployee');
    const opt = sel.options[sel.selectedIndex];
    let basic = pt === 'monthly'
        ? parseFloat(opt?.dataset?.salary || 0)
        : parseFloat(document.getElementById('daysWorked').value || 0) * parseFloat(document.getElementById('dailyRate').value || 0);

    // Deductions
    const sssPct        = parseFloat(document.getElementById('sssPct').value || 0);
    const pagibigPct    = parseFloat(document.getElementById('pagibigPct').value || 0);
    const philhealthPct = parseFloat(document.getElementById('philhealthPct').value || 0);
    const otherDed      = parseFloat(document.getElementById('otherDed').value || 0);
    const sssAmt        = basic * (sssPct / 100);
    const pagibigAmt    = basic * (pagibigPct / 100);
    const philhealthAmt = basic * (philhealthPct / 100);
    const totalDed      = sssAmt + pagibigAmt + philhealthAmt + otherDed;

    // Bonuses
    const restDayPct    = parseFloat(document.getElementById('restDayPct').value || 0);
    const spHolidayPct  = parseFloat(document.getElementById('spHolidayPct').value || 0);
    const regHolidayPct = parseFloat(document.getElementById('regHolidayPct').value || 0);
    const otherBon      = parseFloat(document.getElementById('otherBon').value || 0);
    const restDayAmt    = basic * (restDayPct / 100);
    const spHolidayAmt  = basic * (spHolidayPct / 100);
    const regHolidayAmt = basic * (regHolidayPct / 100);
    const totalBon      = restDayAmt + spHolidayAmt + regHolidayAmt + otherBon;

    const net = basic - totalDed + totalBon;

    document.getElementById('sssAmt').textContent        = fmt(sssAmt);
    document.getElementById('pagibigAmt').textContent    = fmt(pagibigAmt);
    document.getElementById('philhealthAmt').textContent = fmt(philhealthAmt);
    document.getElementById('restDayAmt').textContent    = fmt(restDayAmt);
    document.getElementById('spHolidayAmt').textContent  = fmt(spHolidayAmt);
    document.getElementById('regHolidayAmt').textContent = fmt(regHolidayAmt);
    document.getElementById('basicPayPreview').textContent  = fmt(basic);
    document.getElementById('totalDedPreview').textContent  = '-' + fmt(totalDed);
    document.getElementById('totalBonPreview').textContent  = '+' + fmt(totalBon);
    document.getElementById('netPayPreview').textContent    = fmt(net);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
