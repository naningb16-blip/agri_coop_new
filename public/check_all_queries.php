<?php
// Comprehensive database query checker
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Database Query Checker - All Modules</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; }
.error { color: red; background: #fee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
.warning { color: orange; }
.info { color: blue; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
</style>";

$errors = [];
$warnings = [];

// Test queries from each module
$tests = [
    'Sales' => [
        "SELECT so.*, c.name AS customer_name FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id LIMIT 1",
    ],
    'Purchasing' => [
        "SELECT po.*, s.name AS supplier_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id LIMIT 1",
        "SELECT pr.*, u.full_name AS requester_name FROM purchase_requisitions pr JOIN users u ON pr.requested_by = u.id LIMIT 1",
    ],
    'Inventory' => [
        "SELECT i.*, p.name AS product_name, w.name AS warehouse_name FROM inventory i JOIN products p ON i.product_id = p.id JOIN warehouses w ON i.warehouse_id = w.id LIMIT 1",
        "SELECT sr.*, p.name AS product_name FROM stock_release_requests sr JOIN products p ON sr.product_id = p.id LIMIT 1",
        "SELECT sr.*, p.name AS product_name FROM stock_returns sr JOIN products p ON sr.product_id = p.id LIMIT 1",
    ],
    'Logistics' => [
        "SELECT d.* FROM deliveries d LIMIT 1",
    ],
    'Production' => [
        "SELECT pb.*, p.name AS product_name FROM processing_batches pb LEFT JOIN products p ON pb.product_id = p.id WHERE pb.status='completed' LIMIT 1",
    ],
    'Processing' => [
        "SELECT pb.*, p.name AS product_name FROM processing_batches pb LEFT JOIN products p ON pb.product_id = p.id LIMIT 1",
    ],
    'QA' => [
        "SELECT qi.* FROM quality_inspections qi LIMIT 1",
    ],
    'HR' => [
        "SELECT e.*, d.name AS dept_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id LIMIT 1",
        "SELECT pr.*, e.full_name AS employee_name FROM payroll pr JOIN employees e ON pr.employee_id = e.id LIMIT 1",
    ],
    'Finance' => [
        "SELECT e.* FROM expenses e LIMIT 1",
        "SELECT r.* FROM receipts r LIMIT 1",
    ],
    'Ledger' => [
        "SELECT fl.*, f.full_name AS farmer_name FROM farmer_ledger fl JOIN farmers f ON fl.farmer_id = f.id LIMIT 1",
        "SELECT fw.*, f.full_name AS farmer_name FROM farmer_withdrawals fw JOIN farmers f ON fw.farmer_id = f.id LIMIT 1",
    ],
    'Operational' => [
        "SELECT pb.*, p.name AS product_name FROM processing_batches pb LEFT JOIN products p ON pb.product_id = p.id WHERE pb.status='in_progress' LIMIT 1",
    ],
    'Approvals' => [
        "SELECT ar.*, u.full_name AS requester_name FROM approval_requests ar JOIN users u ON ar.requested_by = u.id LIMIT 1",
        "SELECT ast.*, ar.title FROM approval_steps ast JOIN approval_requests ar ON ast.request_id = ar.id LIMIT 1",
    ],
    'Monitoring' => [
        "SELECT bc.*, pb.batch_number FROM batch_costs bc JOIN processing_batches pb ON bc.batch_id = pb.id LIMIT 1",
    ],
];

echo "<h3>Testing Database Queries...</h3>";

foreach ($tests as $module => $queries) {
    echo "<h4>$module Module</h4>";
    
    foreach ($queries as $query) {
        try {
            $result = $db->query($query);
            echo "<p class='success'>✓ Query OK: " . substr($query, 0, 80) . "...</p>";
        } catch (Exception $e) {
            $error = [
                'module' => $module,
                'query' => $query,
                'error' => $e->getMessage()
            ];
            $errors[] = $error;
            echo "<div class='error'>";
            echo "<strong>❌ Error in $module:</strong><br>";
            echo "<code>" . htmlspecialchars($query) . "</code><br>";
            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
    }
}

// Check approval chains
echo "<h3>Checking Approval Chains</h3>";
$modules = ['sales_order', 'purchase_order', 'prs', 'stock_release', 'stock_return', 'expense', 'withdrawal', 'delivery', 'processing_batch', 'operational', 'employee'];

echo "<table>";
echo "<tr><th>Module</th><th>Steps</th><th>Status</th></tr>";

foreach ($modules as $mod) {
    $chain = $db->fetchAll("SELECT * FROM approval_chains WHERE module=? ORDER BY step_order", [$mod], 's');
    
    if (empty($chain)) {
        echo "<tr><td>$mod</td><td colspan='2' class='warning'>⚠️ No approval chain</td></tr>";
        $warnings[] = "No approval chain for module: $mod";
    } else {
        $steps = [];
        foreach ($chain as $c) {
            $steps[] = $c['label'] . " (" . $c['approver_role'] . ")";
        }
        echo "<tr><td>$mod</td><td>" . implode(' → ', $steps) . "</td><td class='success'>✓</td></tr>";
    }
}
echo "</table>";

// Summary
echo "<hr>";
echo "<h3>Summary</h3>";

if (empty($errors)) {
    echo "<p class='success'>✅ All database queries are working correctly!</p>";
} else {
    echo "<p class='error'>❌ Found " . count($errors) . " error(s) that need to be fixed:</p>";
    echo "<ol>";
    foreach ($errors as $err) {
        echo "<li><strong>{$err['module']}:</strong> {$err['error']}</li>";
    }
    echo "</ol>";
}

if (!empty($warnings)) {
    echo "<p class='warning'>⚠️ Found " . count($warnings) . " warning(s):</p>";
    echo "<ul>";
    foreach ($warnings as $warn) {
        echo "<li>$warn</li>";
    }
    echo "</ul>";
}

echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If any queries failed, check the table structure and column names</li>";
echo "<li>If approval chains are missing, run the appropriate fix scripts</li>";
echo "<li>Test each module by logging in as different user roles</li>";
echo "</ul>";
