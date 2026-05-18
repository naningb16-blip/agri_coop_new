<?php
require_once __DIR__ . '/../../core/Controller.php';

class HRController extends Controller {
    public function index(): void {
        $this->requirePermission('hr', 'view');
        $employees = $this->db->fetchAll(
            "SELECT e.*, d.name as dept_name FROM employees e LEFT JOIN departments d ON e.department_id=d.id ORDER BY e.full_name"
        );
        $departments = $this->db->fetchAll("SELECT * FROM departments ORDER BY name");
        $this->view('hr/index', compact('employees', 'departments'));
    }

    public function saveEmployee(): void {
        $this->requireAuth();
        $id       = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $salary   = (float)($_POST['salary'] ?? 0);

        // Resolve department - create if new name typed
        $deptId   = (int)($_POST['department_id'] ?? 0);
        $deptName = trim($_POST['department_name'] ?? '');
        if (!$deptId && $deptName) {
            $existing = $this->db->fetchOne("SELECT id FROM departments WHERE name=?", [$deptName], 's');
            $deptId   = $existing ? (int)$existing['id']
                : (int)$this->db->insert("INSERT INTO departments (name) VALUES (?)", [$deptName], 's');
        }

        if (empty($fullName)) $this->json(['success' => false, 'message' => 'Name required.']);

        if ($id) {
            // Editing existing employee - save directly, no approval needed
            $this->db->query(
                "UPDATE employees SET full_name=?,department_id=?,position=?,salary=? WHERE id=?",
                [$fullName, $deptId ?: 0, $position, $salary, $id], 'sisdi'
            );
            $this->json(['success' => true, 'message' => 'Employee updated.']);
        } else {
            // New employee - set as pending, submit for GM approval
            $code  = 'EMP-' . strtoupper(substr(uniqid(), -6));
            $empId = (int)$this->db->insert(
                "INSERT INTO employees (employee_code,full_name,department_id,position,salary,status) VALUES (?,?,?,?,?,'pending')",
                [$code, $fullName, $deptId ?: 0, $position, $salary], 'ssisd'
            );

            require_once __DIR__ . '/../models/ApprovalModel.php';
            $approvalModel = new ApprovalModel();
            $approvalModel->createRequest([
                'module'         => 'hr',
                'reference_type' => 'employee',
                'reference_id'   => $empId,
                'title'          => "New Employee: $fullName",
                'description'    => "Position: $position | Department: $deptName | Salary: " . number_format($salary, 2),
            ], $_SESSION['user_id']);

            $this->json(['success' => true, 'message' => "Employee $fullName submitted for GM approval."]);
        }
    }

    public function archive(): void {
        $this->requireAuth();
        $employees = $this->db->fetchAll(
            "SELECT e.*, d.name as dept_name FROM employees e LEFT JOIN departments d ON e.department_id=d.id WHERE e.status='inactive' ORDER BY e.full_name"
        );
        $this->view('hr/archive', compact('employees'));
    }

    public function archiveEmployee(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) $this->json(['success' => false, 'message' => 'Invalid employee.']);
        $this->db->query("UPDATE employees SET status='inactive' WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Employee archived.']);
    }

    public function unarchiveEmployee(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) $this->json(['success' => false, 'message' => 'Invalid employee.']);
        $this->db->query("UPDATE employees SET status='active' WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Employee restored to active.']);
    }

    public function deleteEmployee(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) $this->json(['success' => false, 'message' => 'Invalid employee.']);
        $this->db->query("DELETE FROM employees WHERE id=? AND status='inactive'", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Employee deleted.']);
    }

    public function employeeDocs(): void {
        $this->requireAuth();
        $empId = (int)($_GET['id'] ?? 0);
        $employee = $this->db->fetchOne("SELECT * FROM employees WHERE id=?", [$empId], 'i');
        if (!$employee) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Employee not found.']; $this->redirect('/hr'); }

        $docs = $this->db->fetchAll(
            "SELECT id, doc_type, file_name, file_size, file_type, notes, created_at,
                    (SELECT full_name FROM users WHERE id=ed.uploaded_by) AS uploaded_by_name
             FROM employee_documents ed WHERE employee_id=? ORDER BY doc_type, created_at DESC",
            [$empId], 'i'
        );
        $this->view('hr/employee_docs', compact('employee', 'docs'));
    }

    public function uploadDoc(): void {
        $this->requireAuth();
        $empId   = (int)($_POST['employee_id'] ?? 0);
        $docType = $_POST['doc_type'] ?? 'other';
        $notes   = trim($_POST['notes'] ?? '');
        $allowed = ['sss','pagibig','philhealth','application_letter','resume','contract','other'];

        if (!$empId || !in_array($docType, $allowed)) {
            $this->json(['success' => false, 'message' => 'Invalid request.']);
        }
        if (empty($_FILES['document']['name'])) {
            $this->json(['success' => false, 'message' => 'Please select a file.']);
        }

        $file = $_FILES['document'];
        if ($file['size'] > 10 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'File too large. Max 10MB.']);
        }

        $content = file_get_contents($file['tmp_name']);
        $this->db->insert(
            "INSERT INTO employee_documents (employee_id, doc_type, file_name, file_type, file_size, file_content, uploaded_by, notes)
             VALUES (?,?,?,?,?,?,?,?)",
            [$empId, $docType, $file['name'], $file['type'], $file['size'], $content, $_SESSION['user_id'], $notes],
            'isssisss'
        );
        $this->json(['success' => true, 'message' => 'Document uploaded.']);
    }

    public function downloadDoc(): void {
        $this->requireAuth();
        $id  = (int)($_GET['id'] ?? 0);
        $doc = $this->db->fetchOne("SELECT * FROM employee_documents WHERE id=?", [$id], 'i');
        if (!$doc) { http_response_code(404); echo 'Not found'; exit; }

        if (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . ($doc['file_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($doc['file_name']) . '"');
        header('Content-Length: ' . strlen($doc['file_content']));
        echo $doc['file_content'];
        exit;
    }

    public function deleteDoc(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $this->db->query("DELETE FROM employee_documents WHERE id=?", [$id], 'i');
        $this->json(['success' => true, 'message' => 'Document deleted.']);
    }

    public function attendance(): void {
        $this->requireAuth();
        $date = $_GET['date'] ?? date('Y-m-d');
        $records = $this->db->fetchAll(
            "SELECT a.*, e.full_name FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.date=? ORDER BY e.full_name",
            [$date], 's'
        );
        $employees = $this->db->fetchAll("SELECT * FROM employees WHERE status='active' ORDER BY full_name");
        $this->view('hr/attendance', compact('records', 'employees', 'date'));
    }

    public function saveAttendance(): void {
        $this->requireAuth();
        $records = $_POST['records'] ?? [];
        foreach ($records as $r) {
            $existing = $this->db->fetchOne(
                "SELECT id FROM attendance WHERE employee_id=? AND date=?",
                [$r['employee_id'], $r['date']], 'is'
            );
            if ($existing) {
                $this->db->query("UPDATE attendance SET time_in=?,time_out=?,status=?,remarks=? WHERE id=?",
                    [$r['time_in'], $r['time_out'], $r['status'], $r['remarks'] ?? '', $existing['id']], 'ssssi');
            } else {
                $this->db->insert("INSERT INTO attendance (employee_id,date,time_in,time_out,status,remarks) VALUES (?,?,?,?,?,?)",
                    [$r['employee_id'], $r['date'], $r['time_in'], $r['time_out'], $r['status'], $r['remarks'] ?? ''], 'isssss');
            }
        }
        $this->json(['success' => true, 'message' => 'Attendance saved.']);
    }

    public function payroll(): void {
        $this->requireAuth();

        $payrolls = $this->db->fetchAll(
            "SELECT pr.*, e.full_name, e.position, d.name AS dept_name
             FROM payroll pr
             JOIN employees e ON pr.employee_id=e.id
             LEFT JOIN departments d ON e.department_id=d.id
             ORDER BY pr.created_at DESC"
        );

        $employees = $this->db->fetchAll(
            "SELECT * FROM employees WHERE status='active' ORDER BY full_name"
        );

        $totalNetPay = $this->db->fetchOne(
            "SELECT COALESCE(SUM(net_pay),0) AS total FROM payroll"
        )['total'] ?? 0;

        $this->view('hr/payroll', compact('payrolls', 'employees', 'totalNetPay'));
    }

    public function generatePayroll(): void {
        $this->requireAuth();
        $employeeId      = (int)($_POST['employee_id']           ?? 0);
        $periodStart     = $_POST['period_start']                ?? '';
        $periodEnd       = $_POST['period_end']                  ?? '';
        $payType         = $_POST['pay_type']                    ?? 'monthly';
        $daysWorked      = (float)($_POST['days_worked']         ?? 0);
        $dailyRate       = (float)($_POST['daily_rate']          ?? 0);
        $sssPct          = (float)($_POST['sss_pct']             ?? 0);
        $pagibigPct      = (float)($_POST['pagibig_pct']         ?? 0);
        $philhealthPct   = (float)($_POST['philhealth_pct']      ?? 0);
        $otherDeductions = (float)($_POST['other_deductions']    ?? 0);
        $otherDedNote    = trim($_POST['other_deductions_note']  ?? '');
        $restDayPct      = (float)($_POST['rest_day_pct']        ?? 0);
        $spHolidayPct    = (float)($_POST['special_holiday_pct'] ?? 0);
        $regHolidayPct   = (float)($_POST['regular_holiday_pct'] ?? 0);
        $otherBonuses    = (float)($_POST['other_bonuses']       ?? 0);
        $otherBonNote    = trim($_POST['other_bonuses_note']     ?? '');

        $emp = $this->db->fetchOne("SELECT * FROM employees WHERE id=?", [$employeeId], 'i');
        if (!$emp) $this->json(['success' => false, 'message' => 'Employee not found.']);

        $basicPay         = ($payType === 'monthly') ? $emp['salary'] : $daysWorked * $dailyRate;
        $sssAmount        = $basicPay * ($sssPct / 100);
        $pagibigAmount    = $basicPay * ($pagibigPct / 100);
        $philhealthAmount = $basicPay * ($philhealthPct / 100);
        $totalDeductions  = $sssAmount + $pagibigAmount + $philhealthAmount + $otherDeductions;
        $restDayAmount    = $basicPay * ($restDayPct / 100);
        $spHolidayAmount  = $basicPay * ($spHolidayPct / 100);
        $regHolidayAmount = $basicPay * ($regHolidayPct / 100);
        $totalBonuses     = $restDayAmount + $spHolidayAmount + $regHolidayAmount + $otherBonuses;
        $netPay           = $basicPay - $totalDeductions + $totalBonuses;

        $this->db->insert(
            "INSERT INTO payroll
                (employee_id, period_start, period_end, basic_pay,
                 sss_pct, sss_amount, pagibig_pct, pagibig_amount,
                 philhealth_pct, philhealth_amount, other_deductions, other_deductions_note,
                 rest_day_pct, rest_day_amount, special_holiday_pct, special_holiday_amount,
                 regular_holiday_pct, regular_holiday_amount, other_bonuses, other_bonuses_note,
                 deductions, bonuses, net_pay)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$employeeId, $periodStart, $periodEnd, $basicPay,
             $sssPct, $sssAmount, $pagibigPct, $pagibigAmount,
             $philhealthPct, $philhealthAmount, $otherDeductions, $otherDedNote,
             $restDayPct, $restDayAmount, $spHolidayPct, $spHolidayAmount,
             $regHolidayPct, $regHolidayAmount, $otherBonuses, $otherBonNote,
             $totalDeductions, $totalBonuses, $netPay],
            'issddddddddsdddddddsddd'
        );
        $this->json(['success' => true, 'message' => 'Payroll generated.']);
    }
}
