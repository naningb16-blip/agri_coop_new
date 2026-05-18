<?php
// Debug script to check why PO items aren't being added to inventory
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Purchase Order Approval Debug</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style>";

// 1. Check if there are any warehouses
echo "<h3>1. Warehouse Check</h3>";
$warehouses = $db->fetchAll("SELECT * FROM warehouses ORDER BY id");

if (empty($warehouses)) {
    echo "<p class='error'>❌ CRITICAL: No warehouses found!</p>";
    echo "<p>The system needs at least one warehouse to add stock. Create a warehouse first:</p>";
    echo "<pre>INSERT INTO warehouses (name, location) VALUES ('Main Warehouse', 'Main Office');</pre>";
} else {
    echo "<p class='success'>✅ Found " . count($warehouses) . " warehouse(s)</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Location</th></tr>";
    foreach ($warehouses as $w) {
        echo "<tr><td>{$w['id']}</td><td>" . htmlspecialchars($w['name']) . "</td><td>" . htmlspecialchars($w['location'] ?? 'N/A') . "</td></tr>";
    }
    echo "</table>";
    echo "<p class='info'>System will use warehouse ID {$warehouses[0]['id']} for approved POs</p>";
}

// 2. Check recent purchase orders
echo "<h3>2. Recent Purchase Orders</h3>";
$pos = $db->fetchAll(
    "SELECT po.*, s.name as supplier_name, u.full_name as created_by_name
     FROM purchase_orders po
     LEFT JOIN suppliers s ON po.supplier_id = s.id
     LEFT JOIN users u ON po.created_by = u.id
     ORDER BY po.created_at DESC
     LIMIT 5"
);

if (empty($pos)) {
    echo "<p class='warning'>No purchase orders found</p>";
} else {
    echo "<table>";
    echo "<tr><th>PO ID</th><th>Supplier</th><th>Status</th><th>Total</th><th>Created By</th><th>Created At</th></tr>";
    foreach ($pos as $po) {
        $statusClass = $po['status'] === 'approved' ? 'success' : ($po['status'] === 'pending' ? 'warning' : 'info');
        echo "<tr>";
        echo "<td>{$po['id']}</td>";
        echo "<td>" . htmlspecialchars($po['supplier_name'] ?? 'N/A') . "</td>";
        echo "<td class='$statusClass'>{$po['status']}</td>";
        echo "<td>₱" . number_format($po['total_amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($po['created_by_name'] ?? 'N/A') . "</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($po['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. For each approved PO, check items and stock movements
echo "<h3>3. Approved PO Details</h3>";
$approvedPOs = $db->fetchAll(
    "SELECT * FROM purchase_orders WHERE status='approved' ORDER BY created_at DESC LIMIT 5"
);

if (empty($approvedPOs)) {
    echo "<p class='warning'>No approved purchase orders found</p>";
} else {
    foreach ($approvedPOs as $po) {
        echo "<h4>PO #{$po['id']} - Status: {$po['status']}</h4>";
        
        // Get PO items
        $items = $db->fetchAll(
            "SELECT * FROM purchase_order_items WHERE po_id=?", [$po['id']], 'i'
        );
        
        echo "<p><strong>Items in PO:</strong></p>";
        if (empty($items)) {
            echo "<p class='error'>❌ No items found for this PO!</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Item Name</th><th>Quantity</th><th>Unit</th><th>Unit Price</th><th>Total</th></tr>";
            foreach ($items as $item) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['item_name']) . "</td>";
                echo "<td>" . number_format($item['quantity'], 2) . "</td>";
                echo "<td>" . htmlspecialchars($item['unit'] ?? 'unit') . "</td>";
                echo "<td>₱" . number_format($item['unit_price'], 2) . "</td>";
                echo "<td>₱" . number_format($item['total_price'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check stock movements
        $movements = $db->fetchAll(
            "SELECT sm.*, p.name as product_name, w.name as warehouse_name
             FROM stock_movements sm
             LEFT JOIN products p ON sm.product_id = p.id
             LEFT JOIN warehouses w ON sm.warehouse_id = w.id
             WHERE sm.reference_type='purchase_order' AND sm.reference_id=?",
            [$po['id']], 'i'
        );
        
        echo "<p><strong>Stock Movements Created:</strong></p>";
        if (empty($movements)) {
            echo "<p class='error'>❌ No stock movements found! Items were NOT added to inventory.</p>";
            echo "<p class='warning'>This means the approval process did not trigger the inventory sync.</p>";
        } else {
            echo "<p class='success'>✅ Found " . count($movements) . " stock movement(s)</p>";
            echo "<table>";
            echo "<tr><th>Product</th><th>Warehouse</th><th>Quantity</th><th>Type</th><th>Notes</th><th>Created</th></tr>";
            foreach ($movements as $m) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($m['product_name'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($m['warehouse_name'] ?? 'N/A') . "</td>";
                echo "<td class='success'>+" . number_format($m['quantity'], 2) . "</td>";
                echo "<td>{$m['type']}</td>";
                echo "<td class='small'>" . htmlspecialchars($m['notes']) . "</td>";
                echo "<td>" . date('Y-m-d H:i', strtotime($m['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check approval request
        $approval = $db->fetchOne(
            "SELECT ar.*, u.full_name as requester_name
             FROM approval_requests ar
             LEFT JOIN users u ON ar.requested_by = u.id
             WHERE ar.reference_type='purchase_order' AND ar.reference_id=?",
            [$po['id']], 'i'
        );
        
        echo "<p><strong>Approval Request:</strong></p>";
        if (!$approval) {
            echo "<p class='error'>❌ No approval request found for this PO!</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Request ID</th><th>Status</th><th>Requested By</th><th>Created</th><th>Updated</th></tr>";
            echo "<tr>";
            echo "<td>{$approval['id']}</td>";
            echo "<td class='" . ($approval['status'] === 'approved' ? 'success' : 'warning') . "'>{$approval['status']}</td>";
            echo "<td>" . htmlspecialchars($approval['requester_name'] ?? 'N/A') . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($approval['created_at'])) . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($approval['updated_at'])) . "</td>";
            echo "</tr>";
            echo "</table>";
        }
        
        echo "<hr>";
    }
}

// 4. Check approval chain
echo "<h3>4. Purchase Order Approval Chain</h3>";
$chain = $db->fetchAll(
    "SELECT * FROM approval_chains WHERE module='purchase_order' ORDER BY step_order"
);

if (empty($chain)) {
    echo "<p class='error'>❌ No approval chain configured for purchase_order!</p>";
} else {
    echo "<p class='success'>✅ Approval chain exists</p>";
    echo "<table>";
    echo "<tr><th>Step</th><th>Role</th><th>Label</th><th>Is GM</th></tr>";
    foreach ($chain as $c) {
        echo "<tr>";
        echo "<td>{$c['step_order']}</td>";
        echo "<td>{$c['approver_role']}</td>";
        echo "<td>{$c['label']}</td>";
        echo "<td>" . ($c['is_gm_step'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Check current inventory
echo "<h3>5. Current Inventory</h3>";
$inventory = $db->fetchAll(
    "SELECT i.*, p.name as product_name, w.name as warehouse_name
     FROM inventory i
     JOIN products p ON i.product_id = p.id
     JOIN warehouses w ON i.warehouse_id = w.id
     ORDER BY i.updated_at DESC
     LIMIT 10"
);

if (empty($inventory)) {
    echo "<p class='warning'>No inventory records found</p>";
} else {
    echo "<table>";
    echo "<tr><th>Product</th><th>Warehouse</th><th>Quantity</th><th>Last Updated</th></tr>";
    foreach ($inventory as $inv) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($inv['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($inv['warehouse_name']) . "</td>";
        echo "<td>" . number_format($inv['quantity'], 2) . "</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($inv['updated_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Diagnosis</h3>";

$issues = [];
if (empty($warehouses)) {
    $issues[] = "No warehouses configured - CREATE A WAREHOUSE FIRST!";
}
if (empty($chain)) {
    $issues[] = "No approval chain for purchase_order";
}

// Check if approved POs have stock movements
$approvedWithoutMovements = 0;
foreach ($approvedPOs as $po) {
    $movements = $db->fetchAll(
        "SELECT id FROM stock_movements WHERE reference_type='purchase_order' AND reference_id=?",
        [$po['id']], 'i'
    );
    if (empty($movements)) {
        $approvedWithoutMovements++;
    }
}

if ($approvedWithoutMovements > 0) {
    $issues[] = "$approvedWithoutMovements approved PO(s) without stock movements";
}

if (empty($issues)) {
    echo "<p class='success'>✅ System appears to be configured correctly</p>";
} else {
    echo "<p class='error'>❌ Issues found:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li class='error'>$issue</li>";
    }
    echo "</ul>";
}

echo "<h3>Solution Steps:</h3>";
echo "<ol>";
if (empty($warehouses)) {
    echo "<li class='error'><strong>CRITICAL: Create a warehouse first!</strong><br>";
    echo "Go to Inventory → Warehouses tab → Add Warehouse<br>";
    echo "Or run: <pre>INSERT INTO warehouses (name, location) VALUES ('Main Warehouse', 'Main Office');</pre></li>";
}
echo "<li>Create a new purchase order with items</li>";
echo "<li>Submit for approval (creates approval request)</li>";
echo "<li>GM logs in and goes to Approvals section</li>";
echo "<li>GM clicks on the PO approval request</li>";
echo "<li>GM clicks 'Approve' button</li>";
echo "<li>System should automatically add items to inventory</li>";
echo "<li>Check Inventory → Stock tab to see the new items</li>";
echo "<li>Check Inventory → Movements tab to see the stock-in records</li>";
echo "</ol>";

echo "<h3>If Items Still Don't Appear:</h3>";
echo "<p>The issue might be:</p>";
echo "<ul>";
echo "<li>No warehouse exists (check above)</li>";
echo "<li>PO has no items in purchase_order_items table</li>";
echo "<li>Approval didn't complete successfully (check for errors)</li>";
echo "<li>Code issue in ApprovalModel._syncApproval() method</li>";
echo "</ul>";
