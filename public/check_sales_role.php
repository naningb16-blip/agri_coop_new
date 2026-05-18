<?php
session_start();

echo "<h2>Sales Role Diagnostic</h2>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user'])) {
    $userRole = $_SESSION['user']['role'] ?? 'NOT SET';
    $userName = $_SESSION['user']['name'] ?? 'NOT SET';
    $userDept = $_SESSION['user']['department'] ?? 'NOT SET';
    
    echo "<h3>User Details:</h3>";
    echo "Name: $userName<br>";
    echo "Role: <strong>$userRole</strong><br>";
    echo "Department: $userDept<br>";
    
    echo "<h3>Role Check:</h3>";
    $allowedRoles = ['admin', 'sales'];
    $canApprove = in_array($userRole, $allowedRoles);
    
    echo "Allowed roles: " . implode(', ', $allowedRoles) . "<br>";
    echo "User role matches: " . ($canApprove ? 'YES ✓' : 'NO ✗') . "<br>";
    
    echo "<h3>What buttons should show for approved orders:</h3>";
    if ($canApprove) {
        echo "✓ Gear icon (⚙️) - Mark as Processing<br>";
        echo "✓ Truck icon (🚚) - Mark as Delivered<br>";
    } else {
        echo "✗ No status change buttons (role '$userRole' not in allowed list)<br>";
    }
} else {
    echo "<p style='color:red'>No user session found. Please log in first.</p>";
}
?>
