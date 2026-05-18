<?php
require_once __DIR__ . '/../config/database.php';

// Direct connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Checking Logistics Table Columns</h2>";

// Check deliveries table
$result = $conn->query("DESCRIBE deliveries");
echo "<h3>Deliveries Table Columns:</h3><ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li>{$row['Field']} - {$row['Type']}</li>";
}
echo "</ul>";

// Check if required columns exist
$columns = $conn->query("SHOW COLUMNS FROM deliveries LIKE 'dr_number'")->num_rows;
echo "<p><strong>dr_number exists:</strong> " . ($columns > 0 ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</p>";

$columns = $conn->query("SHOW COLUMNS FROM deliveries LIKE 'delivery_type'")->num_rows;
echo "<p><strong>delivery_type exists:</strong> " . ($columns > 0 ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</p>";

$columns = $conn->query("SHOW COLUMNS FROM deliveries LIKE 'warehouse_id'")->num_rows;
echo "<p><strong>warehouse_id exists:</strong> " . ($columns > 0 ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</p>";

echo "<hr>";
echo "<p><strong>If any column shows NO, you need to run the migrations:</strong></p>";
echo "<ol>";
echo "<li>database/logistics_delivery_receipt_enhancement.sql</li>";
echo "<li>database/logistics_inbound_outbound_feature.sql</li>";
echo "</ol>";

$conn->close();
