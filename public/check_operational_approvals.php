<?php
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Operational Approvals Diagnostic</h2>";

// Check approval chain
echo "<h3>1. Approval Chain for Operational Module:</h3>";
$result = $conn->query("SELECT * FROM approval_chains WHERE module = 'operational'");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Step</th><th>Role</th><th>Label</th><th>Is GM Step</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['step_order']}</td><td>{$row['approver_role']}</td><td>{$row['label']}</td><td>" . ($row['is_gm_step'] ? 'Yes' : 'No') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'><strong>✗ No approval chain found for 'operational' module!</strong></p>";
    echo "<p>Run: <code>database/operational_approval_workflow.sql</code></p>";
}

// Check approval requests
echo "<h3>2. Operational Approval Requests:</h3>";
$result = $conn->query("SELECT ar.*, u.full_name as requester 
                        FROM approval_requests ar 
                        JOIN users u ON ar.requested_by = u.id
                        WHERE ar.module = 'operational' 
                        ORDER BY ar.created_at DESC LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Type</th><th>Ref ID</th><th>Status</th><th>Requester</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['reference_type']}</td><td>{$row['reference_id']}</td><td>{$row['status']}</td><td>{$row['requester']}</td><td>{$row['created_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>No operational approval requests found. Create a production record or processing batch to test.</p>";
}

// Check pending requests for GM
echo "<h3>3. Pending Requests for GM:</h3>";
$result = $conn->query("SELECT ar.*, u.full_name as requester, acs.label as step_label
                        FROM approval_requests ar
                        JOIN users u ON ar.requested_by = u.id
                        JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
                        WHERE ar.status = 'pending' AND ar.module = 'operational'
                        ORDER BY ar.created_at ASC");
if ($result->num_rows > 0) {
    echo "<p style='color:green'><strong>✓ Found " . $result->num_rows . " pending operational request(s) for GM</strong></p>";
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Type</th><th>Description</th><th>Requester</th><th>Current Step</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['reference_type']}</td><td>{$row['description']}</td><td>{$row['requester']}</td><td>{$row['step_label']}</td></tr>";
    }
    echo "</table>";
    echo "<p><strong>→ GM should see these in the Approvals section</strong></p>";
} else {
    echo "<p>No pending operational requests. Either all are approved/rejected, or none have been created yet.</p>";
}

$conn->close();
