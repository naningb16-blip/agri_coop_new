<?php
ob_start();
$pageTitle  = 'Operational';
$activeMenu = 'operational';
$currentTab = $_GET['tab'] ?? 'production';
$userRole   = $_SESSION['user']['role'] ?? '';
$isGM       = $userRole === 'gm';
?>

<?php if ($isGM): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all operational data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'production' ? 'active' : '' ?>" href="<?= BASE_URL ?>/operational?tab=production">
            <i class="bi bi-flower1 me-1"></i>Production
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'processing' ? 'active' : '' ?>" href="<?= BASE_URL ?>/operational?tab=processing">
            <i class="bi bi-gear-wide-connected me-1"></i>Processing
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'farmers' ? 'active' : '' ?>" href="<?= BASE_URL ?>/operational?tab=farmers">
            <i class="bi bi-people me-1"></i>Farmers
        </a>
    </li>
</ul>

<!-- Tab Content -->
<?= $tabContent ?? '' ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
