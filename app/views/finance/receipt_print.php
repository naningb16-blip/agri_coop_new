<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php
        // Parse packed notes for old records missing dedicated columns
        $notes = $receipt['notes'] ?? '';
        preg_match('/\[([^\]]+)\]/', $notes, $rnMatch);
        preg_match('/Payer:\s*([^|]+)/', $notes, $payerMatch);
        preg_match('/Type:\s*([^|]+)/', $notes, $typeMatch);
        $rNum    = $receipt['receipt_number'] ?? ($rnMatch[1]  ?? 'REC-#'.$receipt['id']);
        $payer   = $receipt['payer_name']     ?? trim($payerMatch[1] ?? '—');
        $rType   = trim($receipt['receipt_type'] ?? $typeMatch[1] ?? 'cash_receipt');
        $itemDesc = $receipt['item_description'] ?? (preg_match('/(?:cash_receipt|charge_invoice)\s*\|\s*(.+)$/i', $notes, $dm) ? trim($dm[1]) : '');
        $isCharge = $rType === 'charge_invoice';
    ?>
    <title><?= $isCharge ? 'Charge Invoice' : 'Cash Receipt' ?> — <?= htmlspecialchars($rNum) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; font-size: 13px; background: #fff; }
        .receipt-box { max-width: 480px; margin: 40px auto; border: 2px solid #198754; border-radius: 8px; padding: 32px; }
        .receipt-label { font-size: 1.4rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; }
        .divider { border-top: 1px dashed #aaa; margin: 16px 0; }
        .amount-box { background: #f0fdf4; border: 1px solid #198754; border-radius: 6px; padding: 12px 20px; text-align: center; }
        .sig-line { border-top: 1px solid #000; width: 180px; margin-top: 40px; padding-top: 4px; font-size: 11px; text-align: center; }
        @media print { .no-print { display: none !important; } body { margin: 0; } }
    </style>
</head>
<body>

<div class="no-print text-center mt-3 mb-2">
    <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="bi bi-printer me-1"></i>Print</button>
    <a href="<?= BASE_URL ?>/finance" class="btn btn-sm btn-outline-secondary ms-2">Back to Finance</a>
</div>

<div class="receipt-box">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="text-success fw-bold fs-5"><?= APP_NAME ?></div>
            <div class="receipt-label text-<?= $isCharge ? 'warning' : 'success' ?>">
                <?= $isCharge ? 'Charge Invoice' : 'Cash Receipt' ?>
            </div>
        </div>
        <div class="text-end">
            <div class="fw-bold"><?= htmlspecialchars($rNum) ?></div>
            <div class="text-muted small"><?= date('F d, Y', strtotime($receipt['receipt_date'])) ?></div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Payer Info -->
    <table class="table table-sm table-borderless mb-0">
        <tr>
            <td class="text-muted fw-semibold" style="width:40%">Received From</td>
            <td class="fw-bold"><?= htmlspecialchars($payer) ?></td>
        </tr>
        <tr>
            <td class="text-muted fw-semibold">Date</td>
            <td><?= date('F d, Y', strtotime($receipt['receipt_date'])) ?></td>
        </tr>
        <?php if ($itemDesc): ?>
        <tr>
            <td class="text-muted fw-semibold">For / Description</td>
            <td><?= htmlspecialchars($itemDesc) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($receipt['quantity'] ?? null): ?>
        <tr>
            <td class="text-muted fw-semibold">Quantity</td>
            <td><?= number_format($receipt['quantity'], 2) ?> <?= htmlspecialchars($receipt['unit'] ?? '') ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td class="text-muted fw-semibold">Payment Method</td>
            <td><?= ucwords(str_replace('_', ' ', $receipt['payment_method'] ?? 'cash')) ?></td>
        </tr>
        <?php if ($receipt['notes']): ?>
        <tr>
            <td class="text-muted fw-semibold">Notes</td>
            <td><?= nl2br(htmlspecialchars($receipt['notes'])) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="divider"></div>

    <!-- Amount -->
    <div class="amount-box mb-4">
        <div class="text-muted small text-uppercase letter-spacing-1">Amount <?= $isCharge ? 'Charged' : 'Received' ?></div>
        <div class="fw-bold text-success" style="font-size:2rem">&#8369;<?= number_format($receipt['amount'], 2) ?></div>
    </div>

    <!-- Signatures -->
    <div class="d-flex justify-content-between mt-4">
        <div>
            <div class="sig-line"><?= htmlspecialchars($receipt['received_by_name'] ?? '___________________') ?><br>Received By</div>
        </div>
        <div>
            <div class="sig-line">___________________<br>Authorized By</div>
        </div>
    </div>

    <div class="text-center text-muted mt-4" style="font-size:10px">
        <?= APP_NAME ?> &bull; <?= htmlspecialchars($rNum) ?> &bull; Generated <?= date('M d, Y H:i') ?>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>

