<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice - <?= htmlspecialchars($order['invoice_number'] ?: $order['so_number']) ?></title>
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
        .invoice-info .label { color: #7f8c8d; font-weight: 600; display: inline-block; width: 100px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        thead { background: #34495e; color: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { font-size: 13px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 20px; text-align: right; }
        .totals table { width: 300px; margin-left: auto; }
        .totals td { padding: 8px; font-size: 14px; }
        .totals .grand-total { background: #2c3e50; color: white; font-weight: bold; font-size: 16px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; text-align: center; color: #7f8c8d; font-size: 12px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-paid { background: #27ae60; color: white; }
        .status-unpaid { background: #e74c3c; color: white; }
        .status-partial { background: #f39c12; color: white; }
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
            <p class="subtitle">Sales Invoice</p>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div>
                <h3>Bill To:</h3>
                <p><strong><?= htmlspecialchars($order['customer_name']) ?></strong></p>
                <?php if ($order['customer_address']): ?>
                <p><?= nl2br(htmlspecialchars($order['customer_address'])) ?></p>
                <?php endif; ?>
                <?php if ($order['customer_phone']): ?>
                <p>Phone: <?= htmlspecialchars($order['customer_phone']) ?></p>
                <?php endif; ?>
                <?php if ($order['customer_email']): ?>
                <p>Email: <?= htmlspecialchars($order['customer_email']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h3>Invoice Details:</h3>
                <?php if ($order['invoice_number']): ?>
                <p><span class="label">Invoice #:</span> <strong><?= htmlspecialchars($order['invoice_number']) ?></strong></p>
                <p><span class="label">Invoice Date:</span> <?= date('M d, Y', strtotime($order['invoice_date'])) ?></p>
                <?php endif; ?>
                <p><span class="label">SO Number:</span> <?= htmlspecialchars($order['so_number']) ?></p>
                <p><span class="label">Order Date:</span> <?= date('M d, Y', strtotime($order['order_date'])) ?></p>
                <?php if ($order['delivery_date']): ?>
                <p><span class="label">Delivery Date:</span> <?= date('M d, Y', strtotime($order['delivery_date'])) ?></p>
                <?php endif; ?>
                <p><span class="label">Payment Type:</span> <?= ucwords(str_replace('_', ' ', $order['payment_type'] ?? 'cash')) ?></p>
                <?php if (isset($order['payment_status'])): ?>
                <p><span class="label">Status:</span> 
                    <span class="status-badge status-<?= $order['payment_status'] ?>">
                        <?= ucfirst($order['payment_status']) ?>
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
                    <th>Product</th>
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
                    <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
                    <td class="text-center"><?= number_format($item['quantity'], 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['unit'] ?? 'kg') ?></td>
                    <td class="text-right">₱<?= number_format($item['unit_price'], 2) ?></td>
                    <td class="text-right">₱<?= number_format($item['total_price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">₱<?= number_format($order['total_amount'], 2) ?></td>
                </tr>
                <?php if (isset($order['amount_paid']) && $order['amount_paid'] > 0): ?>
                <tr>
                    <td>Amount Paid:</td>
                    <td class="text-right">₱<?= number_format($order['amount_paid'], 2) ?></td>
                </tr>
                <tr>
                    <td>Balance Due:</td>
                    <td class="text-right">₱<?= number_format($order['total_amount'] - $order['amount_paid'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total">
                    <td>TOTAL:</td>
                    <td class="text-right">₱<?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p style="margin-top: 10px;">Prepared by: <?= htmlspecialchars($order['created_by_name'] ?? 'System') ?></p>
            <p style="margin-top: 20px; font-size: 11px;">This is a computer-generated invoice and does not require a signature.</p>
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
