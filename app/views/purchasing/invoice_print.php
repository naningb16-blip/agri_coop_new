<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice - <?= htmlspecialchars($po['supplier_invoice_number'] ?: $po['po_number']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; color: #333; }
        .invoice { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; background: white; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c3e50; padding-bottom: 20px; }
        .header h1 { color: #2c3e50; font-size: 28px; margin-bottom: 5px; }
        .header .subtitle { color: #7f8c8d; font-size: 14px; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .invoice-info div { flex: 1; }
        .invoice-info h3 { color: #2c3e50; font-size: 14px; margin-bottom: 10px; border-bottom: 2px solid #ecf0f1; padding-bottom: 5px; }
        .invoice-info p { margin: 5px 0; font-size: 13px; line-height: 1.6; }
        .invoice-info .label { color: #7f8c8d; font-weight: 600; display: inline-block; width: 120px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        thead { background: #34495e; color: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { font-size: 13px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 20px; text-align: right; }
        .totals table { width: 350px; margin-left: auto; }
        .totals td { padding: 8px; font-size: 14px; }
        .totals .grand-total { background: #2c3e50; color: white; font-weight: bold; font-size: 16px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; text-align: center; color: #7f8c8d; font-size: 12px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-paid { background: #27ae60; color: white; }
        .status-unpaid { background: #e74c3c; color: white; }
        .status-partial { background: #f39c12; color: white; }
        .payable-box { background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .payable-box h4 { color: #856404; margin-bottom: 10px; }
        @media print {
            body { padding: 0; }
            .invoice { border: none; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <h1>AGRICULTURAL COOPERATIVE</h1>
            <p class="subtitle">Purchase Invoice / Accounts Payable</p>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div>
                <h3>Supplier:</h3>
                <p><strong><?= htmlspecialchars($po['supplier_name']) ?></strong></p>
                <?php if ($po['contact_person']): ?>
                <p>Contact: <?= htmlspecialchars($po['contact_person']) ?></p>
                <?php endif; ?>
                <?php if ($po['phone']): ?>
                <p>Phone: <?= htmlspecialchars($po['phone']) ?></p>
                <?php endif; ?>
                <?php if ($po['email']): ?>
                <p>Email: <?= htmlspecialchars($po['email']) ?></p>
                <?php endif; ?>
                <?php if ($po['address']): ?>
                <p><?= nl2br(htmlspecialchars($po['address'])) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h3>Invoice Details:</h3>
                <?php if ($po['supplier_invoice_number']): ?>
                <p><span class="label">Supplier Invoice #:</span> <strong><?= htmlspecialchars($po['supplier_invoice_number']) ?></strong></p>
                <p><span class="label">Invoice Date:</span> <?= date('M d, Y', strtotime($po['supplier_invoice_date'])) ?></p>
                <?php endif; ?>
                <p><span class="label">Our PO Number:</span> <?= htmlspecialchars($po['po_number']) ?></p>
                <p><span class="label">Order Date:</span> <?= date('M d, Y', strtotime($po['order_date'])) ?></p>
                <?php if ($po['expected_delivery']): ?>
                <p><span class="label">Expected Delivery:</span> <?= date('M d, Y', strtotime($po['expected_delivery'])) ?></p>
                <?php endif; ?>
                <?php if ($po['payment_terms']): ?>
                <p><span class="label">Payment Terms:</span> <?= htmlspecialchars($po['payment_terms']) ?></p>
                <?php endif; ?>
                <?php if ($po['payment_due_date']): ?>
                <p><span class="label">Due Date:</span> <strong><?= date('M d, Y', strtotime($po['payment_due_date'])) ?></strong></p>
                <?php endif; ?>
                <?php if (isset($po['payment_status'])): ?>
                <p><span class="label">Payment Status:</span> 
                    <span class="status-badge status-<?= $po['payment_status'] ?>">
                        <?= ucfirst($po['payment_status']) ?>
                    </span>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Description</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Unit</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                    <td class="text-center"><?= number_format((float)($item['quantity'] ?? 0), 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                    <td class="text-right">₱<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                    <td class="text-right">₱<?= number_format((float)($item['total_price'] ?? 0), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">₱<?= number_format($po['total_amount'], 2) ?></td>
                </tr>
                <?php if (isset($po['amount_paid']) && $po['amount_paid'] > 0): ?>
                <tr>
                    <td>Amount Paid:</td>
                    <td class="text-right">₱<?= number_format($po['amount_paid'], 2) ?></td>
                </tr>
                <tr>
                    <td><strong>Balance Due:</strong></td>
                    <td class="text-right"><strong>₱<?= number_format($po['total_amount'] - $po['amount_paid'], 2) ?></strong></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total">
                    <td>TOTAL PAYABLE:</td>
                    <td class="text-right">₱<?= number_format($po['total_amount'], 2) ?></td>
                </tr>
            </table>
        </div>

        <!-- Accounts Payable Notice -->
        <?php if (($po['payment_status'] ?? 'unpaid') !== 'paid'): ?>
        <div class="payable-box">
            <h4>⚠ Accounts Payable</h4>
            <p><strong>Amount Due:</strong> ₱<?= number_format($po['total_amount'] - ($po['amount_paid'] ?? 0), 2) ?></p>
            <?php if ($po['payment_due_date']): ?>
            <p><strong>Due Date:</strong> <?= date('M d, Y', strtotime($po['payment_due_date'])) ?></p>
            <?php endif; ?>
            <p style="margin-top: 10px; font-size: 12px;">This invoice has been recorded in the journal as Accounts Payable. Payment must be recorded to clear the liability.</p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>Agricultural Cooperative - Purchasing Department</p>
            <p style="margin-top: 10px;">Prepared by: <?= htmlspecialchars($po['created_by_name'] ?? 'System') ?></p>
            <p style="margin-top: 20px; font-size: 11px;">This is a computer-generated document.</p>
        </div>
    </div>

    <script>
        // Auto-print on load
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
