<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Processing Batch - <?= htmlspecialchars($batch['batch_number']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; color: #333; }
        .document { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; background: white; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #9b59b6; padding-bottom: 20px; }
        .header h1 { color: #9b59b6; font-size: 28px; margin-bottom: 5px; }
        .header .subtitle { color: #7f8c8d; font-size: 14px; }
        .info-section { margin-bottom: 25px; }
        .info-section h3 { color: #9b59b6; font-size: 16px; margin-bottom: 15px; border-bottom: 2px solid #ecf0f1; padding-bottom: 5px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .info-item .label { color: #7f8c8d; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .info-item .value { font-size: 14px; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        thead { background: #9b59b6; color: white; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #f39c12; color: white; }
        .status-in_progress { background: #3498db; color: white; }
        .status-completed { background: #27ae60; color: white; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; text-align: center; color: #7f8c8d; font-size: 12px; }
        @media print { body { padding: 0; } .document { border: none; } @page { margin: 1cm; } }
    </style>
</head>
<body>
    <div class="document">
        <div class="header">
            <h1>AGRICULTURAL COOPERATIVE</h1>
            <p class="subtitle">Processing Batch Record</p>
        </div>

        <div class="info-section">
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Batch Number</div>
                    <div class="value"><strong><?= htmlspecialchars($batch['batch_number']) ?></strong></div>
                </div>
                <div class="info-item">
                    <div class="label">Status</div>
                    <div class="value"><span class="status-badge status-<?= $batch['status'] ?>"><?= ucfirst(str_replace('_', ' ', $batch['status'])) ?></span></div>
                </div>
                <div class="info-item">
                    <div class="label">Product</div>
                    <div class="value"><strong><?= htmlspecialchars($batch['product_name']) ?></strong></div>
                </div>
                <div class="info-item">
                    <div class="label">Process Type</div>
                    <div class="value"><?= ucfirst($batch['process_type']) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Start Date</div>
                    <div class="value"><?= $batch['start_date'] ? date('M d, Y', strtotime($batch['start_date'])) : '—' ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Assigned To</div>
                    <div class="value"><?= htmlspecialchars($batch['assigned_name'] ?? '—') ?></div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3>Quantity Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Input Quantity</div>
                    <div class="value"><strong><?= number_format($batch['input_quantity'], 2) ?> <?= htmlspecialchars($batch['unit'] ?? 'kg') ?></strong></div>
                </div>
                <div class="info-item">
                    <div class="label">Output Quantity</div>
                    <div class="value"><strong><?= number_format($batch['output_quantity'] ?? 0, 2) ?> <?= htmlspecialchars($batch['unit'] ?? 'kg') ?></strong></div>
                </div>
                <div class="info-item">
                    <div class="label">Input Warehouse</div>
                    <div class="value"><?= htmlspecialchars($batch['input_warehouse_name'] ?? '—') ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Output Warehouse</div>
                    <div class="value"><?= htmlspecialchars($batch['output_warehouse_name'] ?? '—') ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($stages)): ?>
        <div class="info-section">
            <h3>Processing Stages</h3>
            <table>
                <thead>
                    <tr><th>Stage</th><th>Order</th><th>Input Qty</th><th>Output Qty</th><th>Status</th><th>Completed</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stages as $stage): ?>
                    <tr>
                        <td><?= ucfirst($stage['stage']) ?></td>
                        <td><?= $stage['stage_order'] ?></td>
                        <td><?= number_format($stage['input_qty'], 2) ?></td>
                        <td><?= number_format($stage['output_qty'] ?? 0, 2) ?></td>
                        <td><span class="status-badge status-<?= $stage['status'] ?>"><?= ucfirst($stage['status']) ?></span></td>
                        <td><?= $stage['completed_at'] ? date('M d, Y', strtotime($stage['completed_at'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($batch['notes']): ?>
        <div class="info-section">
            <h3>Notes</h3>
            <div style="background: #ecf0f1; padding: 15px; border-radius: 5px;">
                <?= nl2br(htmlspecialchars($batch['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Agricultural Cooperative - Operational Department</p>
            <p style="margin-top: 10px;">Created by: <?= htmlspecialchars($batch['created_by_name'] ?? 'System') ?></p>
        </div>
    </div>
    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
