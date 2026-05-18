<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Record - <?= htmlspecialchars($record['production_number'] ?? 'PROD-' . $record['id']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; color: #333; }
        .document { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; background: white; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #27ae60; padding-bottom: 20px; }
        .header h1 { color: #27ae60; font-size: 28px; margin-bottom: 5px; }
        .header .subtitle { color: #7f8c8d; font-size: 14px; }
        .info-section { margin-bottom: 25px; }
        .info-section h3 { color: #27ae60; font-size: 16px; margin-bottom: 15px; border-bottom: 2px solid #ecf0f1; padding-bottom: 5px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .info-item { }
        .info-item .label { color: #7f8c8d; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .info-item .value { font-size: 14px; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        thead { background: #27ae60; color: white; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
        th { font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-planned { background: #3498db; color: white; }
        .status-planted { background: #f39c12; color: white; }
        .status-growing { background: #9b59b6; color: white; }
        .status-harvested { background: #e67e22; color: white; }
        .status-completed { background: #27ae60; color: white; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; text-align: center; color: #7f8c8d; font-size: 12px; }
        @media print {
            body { padding: 0; }
            .document { border: none; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="document">
        <!-- Header -->
        <div class="header">
            <h1>AGRICULTURAL COOPERATIVE</h1>
            <p class="subtitle">Production Record</p>
        </div>

        <!-- Document Info -->
        <div class="info-section">
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Production Number</div>
                    <div class="value"><strong><?= htmlspecialchars($record['production_number'] ?? 'PROD-' . $record['id']) ?></strong></div>
                </div>
                <div class="info-item">
                    <div class="label">Status</div>
                    <div class="value">
                        <span class="status-badge status-<?= $record['status'] ?>">
                            <?= ucfirst($record['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">Created Date</div>
                    <div class="value"><?= date('M d, Y', strtotime($record['created_at'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Created By</div>
                    <div class="value"><?= htmlspecialchars($record['created_by_name'] ?? 'System') ?></div>
                </div>
            </div>
        </div>

        <!-- Farmer & Product Info -->
        <div class="info-section">
            <h3>Farmer & Product Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Farmer</div>
                    <div class="value"><strong><?= htmlspecialchars($record['farmer_name']) ?></strong></div>
                    <?php if ($record['farmer_phone']): ?>
                    <div class="value" style="font-size: 12px; color: #7f8c8d;">Phone: <?= htmlspecialchars($record['farmer_phone']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="info-item">
                    <div class="label">Product</div>
                    <div class="value"><strong><?= htmlspecialchars($record['product_name']) ?></strong></div>
                    <div class="value" style="font-size: 12px; color: #7f8c8d;">Unit: <?= htmlspecialchars($record['unit'] ?? 'kg') ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Farm Location</div>
                    <div class="value"><?= htmlspecialchars($record['farm_location'] ?? '—') ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Season</div>
                    <div class="value"><?= htmlspecialchars($record['season'] ?? '—') ?></div>
                </div>
            </div>
        </div>

        <!-- Planting Details -->
        <div class="info-section">
            <h3>Planting Details</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Planting Date</div>
                    <div class="value"><?= $record['planting_date'] ? date('M d, Y', strtotime($record['planting_date'])) : '—' ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Expected Harvest</div>
                    <div class="value"><?= $record['expected_harvest'] ? date('M d, Y', strtotime($record['expected_harvest'])) : '—' ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Planted Area</div>
                    <div class="value"><strong><?= number_format($record['planted_area_ha'], 2) ?> hectares</strong></div>
                </div>
                <div class="info-item">
                    <div class="label">Expected Yield</div>
                    <div class="value"><strong><?= number_format($record['expected_yield'], 2) ?> <?= htmlspecialchars($record['unit'] ?? 'kg') ?></strong></div>
                </div>
                <?php if ($record['actual_yield']): ?>
                <div class="info-item">
                    <div class="label">Actual Yield</div>
                    <div class="value"><strong><?= number_format($record['actual_yield'], 2) ?> <?= htmlspecialchars($record['unit'] ?? 'kg') ?></strong></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Production Inputs -->
        <?php if (!empty($inputs)): ?>
        <div class="info-section">
            <h3>Production Inputs</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalCost = 0;
                    foreach ($inputs as $input): 
                        $totalCost += $input['total_cost'];
                    ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($input['applied_date'])) ?></td>
                        <td><?= ucfirst($input['input_type']) ?></td>
                        <td><?= htmlspecialchars($input['name']) ?></td>
                        <td><?= number_format($input['quantity'], 2) ?> <?= htmlspecialchars($input['unit']) ?></td>
                        <td>₱<?= number_format($input['unit_cost'], 2) ?></td>
                        <td>₱<?= number_format($input['total_cost'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #ecf0f1; font-weight: bold;">
                        <td colspan="5" style="text-align: right;">Total Input Cost:</td>
                        <td>₱<?= number_format($totalCost, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <!-- Activity Schedule -->
        <?php if (!empty($schedules)): ?>
        <div class="info-section">
            <h3>Activity Schedule</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activity</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($schedule['scheduled_date'])) ?></td>
                        <td><?= htmlspecialchars($schedule['activity']) ?></td>
                        <td><?= htmlspecialchars($schedule['assigned_name'] ?? '—') ?></td>
                        <td><?= ucfirst($schedule['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($record['notes']): ?>
        <div class="info-section">
            <h3>Notes</h3>
            <div style="background: #ecf0f1; padding: 15px; border-radius: 5px;">
                <?= nl2br(htmlspecialchars($record['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>Agricultural Cooperative - Operational Department</p>
            <p style="margin-top: 10px;">This is a computer-generated document.</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
