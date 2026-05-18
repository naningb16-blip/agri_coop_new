<?php
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Adding Operational Approval Chain</h2>";

// Check if it already exists
$result = $conn->query("SELECT * FROM approval_chains WHERE module = 'operational'");
if ($result->num_rows > 0) {
    echo "<p style='color:orange'>Approval chain for 'operational' module already exists.</p>";
} else {
    // Add the approval chain
    $sql = "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) 
            VALUES ('operational', 1, 'gm', 'General Manager Approval', 1)";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green'><strong>✓ Operational approval chain added successfully!</strong></p>";
    } else {
        echo "<p style='color:red'>✗ Error: " . $conn->error . "</p>";
    }
}

// Verify
echo "<hr><h3>Verification:</h3>";
$result = $conn->query("SELECT * FROM approval_chains WHERE module = 'operational'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'><strong>✓ Operational approval chain exists</strong></p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Module</th><th>Step</th><th>Role</th><th>Label</th><th>Is GM Step</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['module']}</td>";
        echo "<td>{$row['step_order']}</td>";
        echo "<td>{$row['approver_role']}</td>";
        echo "<td>{$row['label']}</td>";
        echo "<td>" . ($row['is_gm_step'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'><strong>✗ Operational approval chain still missing!</strong></p>";
}

// Show pending requests
echo "<hr><h3>Pending Operational Requests:</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM approval_requests WHERE module = 'operational' AND status = 'pending'");
$row = $result->fetch_assoc();
echo "<p>There are <strong>{$row['count']}</strong> pending operational requests waiting for GM approval.</p>";
echo "<p><strong>→ GM should now see these in the Approvals section at /approvals</strong></p>";

$conn->close();

echo "<hr>";
echo "<p><a href='check_operational_approvals.php'>← Back to Diagnostic</a> | <a href='../approvals'>Go to Approvals Section →</a></p>";
