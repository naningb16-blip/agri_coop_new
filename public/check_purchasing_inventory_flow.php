<?php
// Diagnostic script for purchasing to inventory flow
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Purchasing to Inventory Flow Diagnostic</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
</style>";

// 1. Check purchase order approval chain
echo "<h3>1. Purchase Order Approval Chain</h3>";
$poChain = $db->fetchAll(
    "SELECT * FROM approval_chains WHERE module='purchase_order' ORDER BY step_order"
);

if (empty($poChain)) {
    echo "<p class='error'>❌ No approval chain for purchase_order</p>";
} else {
    echo "<p class='success'>✅ Approval chain exists</p>";
    echo "<table>";
    echo "<tr><th>Step</th><th>Role</th><th>Label</th><th>Is GM</th></tr>";
    foreach ($poChain as $c) {
        echo "<tr>";
        echo "<td>{$c['step_order']}</td>";
        echo "<td>{$c['approver_role']}</td>";
        echo "<td>{$c['label']}</td>";
        echo "<td>" . ($c['is_gm_step'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Check recent approved POs and their inventory impact
echo "<h3>2. Recent Approved Purchase Orders</h3>";
$approvedPOs = $db->fetchAll(
    "SELECT po.*, s.name as supplier_name,
            (SELECT COUNT(*) FROM purchase_order_items WHERE po_id=po.id) as item_count,
            (SELECT COUNT(*) FROM stock_movements WHERE reference_type='purchase_order' AND reference_id=po.id) as movement_count
     FROM purchase_orders po
     LEFT JOIN suppliers s ON po.supplier_id = s.id
     WHERE po.status='approved'
     ORDER BY po.created_at DESC
     LIMIT 10"
);

if (empty($approvedPOs)) {
    echo "<p class='warning'>No approved purchase orders found</p>";
} else {
    echo "<table>";
    echo "<tr><th>PO #</th><th>Supplier</th><th>Total</th><th>Items</th><th>Stock Movements</th><th>Status</th><th>Date</th></tr>";
    foreach ($approvedPOs as $po) {
        $statusClass = $po['movement_count'] > 0 ? 'success' : 'error';
        echo "<tr>";
        echo "<td>{$po['id']}</td>";
        echo "<td>" . htmlspecialchars($po['supplier_name'] ?? 'N/A') . "</td>";
        echo "<td>₱" . number_format($po['total_amount'], 2) . "</td>";
        echo "<td>{$po['item_count']}</td>";
        echo "<td class='$statusClass'>{$po['movement_count']}</td>";
        echo "<td>{$po['status']}</td>";
        echo "<td>" . date('Y-m-d', strtotime($po['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if any approved POs don't have stock movements
    $orphaned = array_filter($approvedPOs, fn($po) => $po['movement_count'] == 0);
    if (!empty($orphaned)) {
        echo "<p class='error'>⚠️ Found " . count($orphaned) . " approved POs without stock movements!</p>";
        echo "<p>These POs were approved but items were not added to inventory.</p>";
    } else {
        echo "<p class='success'>✅ All approved POs have corresponding stock movements</p>";
    }
}

// 3. Check low stock items
echo "<h3>3. Low Stock Items</h3>";
$lowStock = $db->fetchAll(
    "SELECT p.id, p.name, p.unit, p.reorder_level,
            COALESCE(SUM(i.quantity), 0) AS current_stock,
            p.reorder_level - COALESCE(SUM(i.quantity), 0) AS shortage
     FROM products p
     LEFT JOIN inventory i ON p.id = i.product_id
     WHERE p.reorder_level > 0
     GROUP BY p.id, p.name, p.unit, p.reorder_level
     HAVING current_stock < p.reorder_level
     ORDER BY shortage DESC"
);

if (empty($lowStock)) {
    echo "<p class='success'>✅ No low stock items</p>";
} else {
    echo "<p class='warning'>⚠️ Found " . count($lowStock) . " items below reorder level:</p>";
    echo "<table>";
    echo "<tr><th>Product</th><th>Current Stock</th><th>Reorder Level</th><th>Shortage</th><th>Status</th></tr>";
    foreach ($lowStock as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td class='error'>" . number_format($item['current_stock'], 2) . " " . htmlspecialchars($item['unit']) . "</td>";
        echo "<td>" . number_format($item['reorder_level'], 2) . " " . htmlspecialchars($item['unit']) . "</td>";
        echo "<td class='error'>" . number_format($item['shortage'], 2) . " " . htmlspecialchars($item['unit']) . "</td>";
        echo "<td><span class='error'>⚠️ Low Stock</span></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Check stock movements from POs
echo "<h3>4. Recent Stock Movements from Purchase Orders</h3>";
$movements = $db->fetchAll(
    "SELECT sm.*, p.name as product_name, w.name as warehouse_name
     FROM stock_movements sm
     JOIN products p ON sm.product_id = p.id
     JOIN warehouses w ON sm.warehouse_id = w.id
     WHERE sm.reference_type='purchase_order'
     ORDER BY sm.created_at DESC
     LIMIT 10"
);

if (empty($movements)) {
    echo "<p class='warning'>No stock movements from purchase orders yet</p>";
} else {
    echo "<p class='success'>✅ Found " . count($movements) . " stock movements from POs</p>";
    echo "<table>";
    echo "<tr><th>Product</th><th>Warehouse</th><th>Quantity</th><th>PO Reference</th><th>Notes</th><th>Date</th></tr>";
    foreach ($movements as $m) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($m['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($m['warehouse_name']) . "</td>";
        echo "<td class='success'>+" . number_format($m['quantity'], 2) . "</td>";
        echo "<td>PO #{$m['reference_id']}</td>";
        echo "<td class='small'>" . htmlspecialchars($m['notes']) . "</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($m['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Check warehouses
echo "<h3>5. Warehouse Configuration</h3>";
$warehouses = $db->fetchAll("SELECT * FROM warehouses ORDER BY id");

if (empty($warehouses)) {
    echo "<p class='error'>❌ No warehouses configured! PO approval will fail.</p>";
    echo "<p>Create at least one warehouse in the Inventory module.</p>";
} else {
    echo "<p class='success'>✅ Found " . count($warehouses) . " warehouse(s)</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Location</th><th>Capacity</th></tr>";
    foreach ($warehouses as $w) {
        echo "<tr>";
        echo "<td>{$w['id']}</td>";
        echo "<td>" . htmlspecialchars($w['name']) . "</td>";
        echo "<td>" . htmlspecialchars($w['location'] ?? 'N/A') . "</td>";
        echo "<td>" . ($w['capacity'] ? number_format($w['capacity'], 2) : 'Unlimited') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p class='info'>Note: Approved POs automatically add stock to the first warehouse (ID: {$warehouses[0]['id']})</p>";
}

echo "<hr>";
echo "<h3>Summary</h3>";

$issues = [];
if (empty($poChain)) $issues[] = "No approval chain for purchase orders";
if (empty($warehouses)) $issues[] = "No warehouses configured";
if (!empty($orphaned)) $issues[] = count($orphaned) . " approved POs without stock movements";

if (empty($issues)) {
    echo "<p class='success'>✅ All systems operational!</p>";
    echo "<ul>";
    echo "<li>Purchase order approval chain configured</li>";
    echo "<li>Warehouses configured</li>";
    echo "<li>Approved POs automatically add stock to inventory</li>";
    echo "<li>Low stock detection working</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Issues found:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

echo "<h3>How It Works:</h3>";
echo "<ol>";
echo "<li>Purchasing user creates a purchase order with items</li>";
echo "<li>GM reviews and approves the PO in Approvals section</li>";
echo "<li>System automatically adds all PO items to inventory (first warehouse)</li>";
echo "<li>Stock movements created with reference to the PO</li>";
echo "<li>Low stock alerts shown to GM and Inventory users when stock < reorder level</li>";
echo "</ol>";

echo "<h3>Testing Steps:</h3>";
echo "<ol>";
echo "<li>Create a PO as purchasing user</li>";
echo "<li>Log in as GM and approve it</li>";
echo "<li>Check inventory - stock should increase automatically</li>";
echo "<li>Check stock movements - should show 'Auto stock-in from approved PO #X'</li>";
echo "<li>Set a product's reorder level above current stock</li>";
echo "<li>Check GM dashboard and Inventory page for low stock alert</li>";
echo "</ol>";
