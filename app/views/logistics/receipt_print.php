<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Receipt &#8369; <?= htmlspecialchars($receipt['dr_number']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; font-size: 13px; background: #fff; }
        .receipt-header { border-bottom: 2px solid #198754; padding-bottom: 12px; margin-bottom: 20px; }
        .label { color: #6c757d; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
        .sig-box { border-top: 1px solid #000; width: 200px; margin-top: 40px; padding-top: 4px; text-align: center; font-size: 11px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body class="p-4">

<div class="no-print mb-3">
    <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="bi bi-printer me-1"></i>Print</button>
    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary ms-2">Back</a>
</div>

<div class="receipt-header d-flex justify-content-between align-items-start">
    <div>
        <h4 class="mb-0 text-success fw-bold"><?= APP_NAME ?></h4>
        <div class="text-muted small">Delivery Receipt</div>
    </div>
    <div class="text-end">
        <div class="fw-bold fs-5"><?= htmlspecialchars($receipt['dr_number']) ?></div>
        <div class="text-muted small"><?= date('F d, Y', strtotime($receipt['received_at'])) ?></div>
    </div>
</div>

<!-- Delivery Info -->
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="label">Reference</div>
        <div class="fw-semibold">
            <?= ucwords(str_replace('_',' ',$delivery['reference_type'])) ?> #<?= $delivery['reference_id'] ?>
            <?php if ($sourceDoc): ?> &#8369; <?= htmlspecialchars($sourceDoc['label']) ?><?php endif; ?>
        </div>
        <?php if ($sourceDoc): ?><div><?= htmlspecialchars($sourceDoc['party']) ?></div><?php endif; ?>
        <?php if (!empty($sourceDoc['address'])): ?><div class="text-muted small"><?= htmlspecialchars($sourceDoc['address']) ?></div><?php endif; ?>
    </div>
    <div class="col-6">
        <div class="label">Delivery Status</div>
        <div class="fw-semibold text-success">DELIVERED</div>
        <div class="text-muted small"><?= $delivery['delivery_date'] ? date('M d, Y H:i', strtotime($delivery['delivery_date'])) : '' ?></div>
    </div>
    <div class="col-6">
        <div class="label">Origin</div>
        <div><?= htmlspecialchars($delivery['origin']) ?></div>
    </div>
    <div class="col-6">
        <div class="label">Destination</div>
        <div><?= htmlspecialchars($delivery['destination']) ?></div>
    </div>
    <div class="col-6">
        <div class="label">Driver</div>
        <div><?= htmlspecialchars($delivery['driver_name'] ?: '&#8369;') ?></div>
    </div>
    <div class="col-6">
        <div class="label">Vehicle Plate</div>
        <div><?= htmlspecialchars($delivery['vehicle_plate'] ?: '&#8369;') ?></div>
    </div>
</div>

<!-- Items Table -->
<table class="table table-bordered table-sm mb-4">
    <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Product</th>
            <th class="text-center">Quantity</th>
            <th class="text-center">Unit</th>
            <th class="text-end">Unit Cost</th>
            <th class="text-end">Total Amount</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $grandTotal = 0;
    foreach ($items as $i => $item): 
        $unitCost = (float)($item['unit_cost'] ?? 0);
        $totalAmount = (float)($item['total_amount'] ?? 0);
        $grandTotal += $totalAmount;
    ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($item['product_name']) ?></td>
        <td class="text-center"><?= number_format($item['quantity'], 2) ?></td>
        <td class="text-center"><?= htmlspecialchars($item['unit'] ?? '') ?></td>
        <td class="text-end">₱<?= number_format($unitCost, 2) ?></td>
        <td class="text-end">₱<?= number_format($totalAmount, 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
    <tr><td colspan="6" class="text-center text-muted">No items recorded.</td></tr>
    <?php endif; ?>
    </tbody>
    <?php if ($grandTotal > 0): ?>
    <tfoot>
        <tr class="table-light fw-bold">
            <td colspan="5" class="text-end">GRAND TOTAL:</td>
            <td class="text-end">₱<?= number_format($grandTotal, 2) ?></td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>

<!-- Condition Notes -->
<?php if ($receipt['condition_notes']): ?>
<div class="mb-4">
    <div class="label">Condition / Remarks</div>
    <div class="border rounded p-2"><?= nl2br(htmlspecialchars($receipt['condition_notes'])) ?></div>
</div>
<?php endif; ?>

<!-- Signatures -->
<div class="d-flex justify-content-between mt-5">
    <div>
        <div class="sig-box">
            <?= htmlspecialchars($receipt['received_by_name']) ?><br>Received By
        </div>
    </div>
    <div>
        <div class="sig-box">
            <?= htmlspecialchars($receipt['signature_name'] ?: '___________________') ?><br>Acknowledged By
        </div>
    </div>
    <div>
        <div class="sig-box">
            ___________________<br>Authorized Signatory
        </div>
    </div>
</div>

<div class="text-center text-muted mt-4" style="font-size:10px">
    Generated by <?= APP_NAME ?> &bull; <?= date('M d, Y H:i:s') ?> &bull; <?= htmlspecialchars($receipt['dr_number']) ?>
</div>

</body>
</html>
