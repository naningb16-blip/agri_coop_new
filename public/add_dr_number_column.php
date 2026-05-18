<?php
require_once __DIR__ . '/../config/database.php';

// Direct connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Adding dr_number Column to Deliveries Table</h2>";

// Check if column already exists
$result = $conn->query("SHOW COLUMNS FROM deliveries LIKE 'dr_number'");
if ($result->num_rows > 0) {
    echo "<p style='color:orange'>Column 'dr_number' already exists. No action needed.</p>";
} else {
    // Add the column
    $sql = "ALTER TABLE deliveries ADD COLUMN dr_number VARCHAR(100) UNIQUE AFTER id";
    if ($conn->query($sql)) {
        echo "<p style='color:green'>✓ Column 'dr_number' added successfully!</p>";
        
        // Generate DR numbers for existing deliveries
        $sql2 = "UPDATE deliveries 
                 SET dr_number = CONCAT('DR-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 4, '0'))
                 WHERE dr_number IS NULL";
        if ($conn->query($sql2)) {
            $affected = $conn->affected_rows;
            echo "<p style='color:green'>✓ Generated DR numbers for $affected existing deliveries</p>";
        } else {
            echo "<p style='color:red'>✗ Error generating DR numbers: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Error adding column: " . $conn->error . "</p>";
    }
}

// Verify
echo "<hr><h3>Verification:</h3>";
$result = $conn->query("SHOW COLUMNS FROM deliveries LIKE 'dr_number'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'><strong>✓ dr_number column exists</strong></p>";
    
    // Count deliveries with DR numbers
    $count = $conn->query("SELECT COUNT(*) as total FROM deliveries WHERE dr_number IS NOT NULL")->fetch_assoc()['total'];
    echo "<p>Deliveries with DR numbers: <strong>$count</strong></p>";
} else {
    echo "<p style='color:red'><strong>✗ dr_number column does NOT exist</strong></p>";
}

$conn->close();

echo "<hr>";
echo "<p><a href='check_logistics_columns.php'>← Back to Column Check</a></p>";
