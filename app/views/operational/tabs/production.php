<?php
$sc = ['planned'=>'secondary','planted'=>'info','growing'=>'primary','harvested'=>'warning','completed'=>'success'];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
<div class="row g-3 flex-grow-1">
    <?php foreach ([[$summary['total'],'Total','','flower1'],[$summary['planted'],'Planted','info','tree'],[$summary['growing'],'Growing','','leaf'],[$summary['harvested'],'Harvested','warning','basket'],[$summary['completed'],'Completed','','check-circle']] as [$v,$l,$c,$i]): ?>
    <div class="col-6 col-md"><div class="stat-card <?= $c ?>"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" style="font-size:1rem"><?= $v ?></div><div class="stat-label"><?= $l ?></div></div><i class="bi bi-<?= $i ?> stat-icon"></i></div></div></div>
    <?php endforeach; ?>
    <div class="col-6 col-md"><div class="stat-card purple"><div class="stat-value" style="font-size:1rem"><?= number_format($summary['total_yield'],2) ?></div><div class="stat-label">Total Yield</div></div></div>
</div>
<button class="btn btn-sm btn-outline-secondary no-print ms-2 flex-shrink-0 align-self-start" onclick="printPage('Production Analytics')"><i class="bi bi-printer me-1"></i>Print</button>
</div>

<div class="table-card mb-3">
    <div class="p-3 d-flex gap-2 flex-wrap align-items-center">
        <?php foreach ([''=>'All','planted'=>'Planted','growing'=>'Growing','harvested'=>'Harvested','completed'=>'Completed'] as $v=>$l): ?>
        <a href="<?= BASE_URL ?>/operational?tab=production&status=<?= $v ?><?= isset($mine) && $mine ? '&mine=1' : '' ?>" class="btn btn-sm <?= $status===$v ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $l ?></a>
        <?php endforeach; ?>
        <div class="vr"></div>
        <a href="<?= BASE_URL ?>/operational?tab=production<?= $status ? '&status='.$status : '' ?>&mine=<?= isset($mine) && $mine ? '0' : '1' ?>" class="btn btn-sm <?= isset($mine) && $mine ? 'btn-info' : 'btn-outline-info' ?>">
            <i class="bi bi-person-check me-1"></i>My Submissions
        </a>
        <div class="ms-auto d-flex gap-2">
            <?php if ($_SESSION['user']['role'] !== 'gm'): ?><button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#newProdModal"><i class="bi bi-plus-circle me-1"></i>New Record</button><?php endif; ?>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="card-header"><span><i class="bi bi-flower1 me-2 text-success"></i>Production Records</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Farmer</th><th>Product</th><th>Location</th><th>Land Owner</th><th>Variety</th><th>Area (ha)</th><th>Planted</th><th>Harvested</th><th>Milling (kg)</th><th>Bags</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['farmer_name']) ?></td>
                <td><?= htmlspecialchars($r['product_name']) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($r['farm_location'] ?? '') ?></td>
                <td class="small"><?= htmlspecialchars($r['land_owner'] ?? '') ?></td>
                <td class="small"><?= htmlspecialchars($r['variety'] ?? '') ?></td>
                <td><?= number_format($r['planted_area_ha'],2) ?></td>
                <td class="text-muted small"><?= $r['planting_date'] ? date('M d, Y', strtotime($r['planting_date'])) : '' ?></td>
                <td class="text-muted small"><?= $r['actual_harvest'] ? date('M d, Y', strtotime($r['actual_harvest'])) : ($r['expected_harvest'] ? '<span class="text-warning">Exp: '.date('M d, Y', strtotime($r['expected_harvest'])).'</span>' : '') ?></td>
                <td><?= number_format($r['milling_kgs'] ?? 0, 2) ?></td>
                <td><?= number_format($r['bagging_bags'] ?? 0, 2) ?></td>
                <td><span class="badge bg-<?= $sc[$r['status']] ?? 'secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
                <td><a href="<?= BASE_URL ?>/operational/production-detail?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($records)): ?><tr><td colspan="12" class="text-center text-muted py-4">No production records.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Production Modal -->
<div class="modal fade" id="newProdModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-flower1 me-2"></i>New Production Record — Planting to Harvesting</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/operational/create-production" method="POST" data-ajax data-reload="true">
                <div class="modal-body row g-3">

                    <div class="col-12"><h6 class="text-muted fw-semibold border-bottom pb-1">Farm Details</h6></div>

                    <div class="col-md-4"><label class="form-label">Farmer / Seed Grower Name <span class="text-danger">*</span></label>
                        <input type="text" name="farmer_name" class="form-control" list="farmerList" placeholder="Type or select—" required autocomplete="off">
                        <input type="hidden" name="farmer_id" id="newFarmerId">
                        <datalist id="farmerList">
                        <?php foreach ($farmers as $f): ?><option value="<?= htmlspecialchars($f['full_name']) ?>" data-id="<?= $f['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-4"><label class="form-label">Land Owner</label>
                        <input type="text" name="land_owner" class="form-control" placeholder="Name of land owner">
                    </div>
                    <div class="col-md-4"><label class="form-label">Location</label>
                        <input type="text" name="farm_location" class="form-control" placeholder="Barangay / Municipality">
                    </div>

                    <div class="col-md-3"><label class="form-label">No. of Hectares</label>
                        <input type="number" name="planted_area_ha" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-3"><label class="form-label">Variety</label>
                        <input type="text" name="variety" class="form-control" placeholder="e.g. DK9133, P3482W">
                    </div>
                    <div class="col-md-3"><label class="form-label">No. of Kgs of Seeds</label>
                        <input type="number" name="no_of_seed_kgs" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-3"><label class="form-label">Product <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" class="form-control" list="prodList" placeholder="Type or select—" required autocomplete="off">
                        <input type="hidden" name="product_id" id="newProductId">
                        <datalist id="prodList">
                        <?php foreach ($products as $p): ?><option value="<?= htmlspecialchars($p['name']) ?>" data-id="<?= $p['id'] ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="col-12"><h6 class="text-muted fw-semibold border-bottom pb-1 mt-2">Planting & Harvest</h6></div>

                    <div class="col-md-3"><label class="form-label">Date Planted</label>
                        <input type="date" name="planting_date" class="form-control">
                    </div>
                    <div class="col-md-3"><label class="form-label">Expected Harvest</label>
                        <input type="date" name="expected_harvest" class="form-control">
                    </div>
                    <div class="col-md-3"><label class="form-label">Date Harvested</label>
                        <input type="date" name="actual_harvest" class="form-control">
                    </div>
                    <div class="col-md-3"><label class="form-label">Season</label>
                        <input type="text" name="season" class="form-control" placeholder="e.g. Wet 2025">
                    </div>

                    <div class="col-12"><label class="form-label">Fertilizer Used</label>
                        <input type="text" name="fertilizer_used" class="form-control" placeholder="e.g. Urea 46-0-0, Complete 14-14-14">
                    </div>

                    <div class="col-12"><h6 class="text-muted fw-semibold border-bottom pb-1 mt-2">Milling & Bagging</h6></div>

                    <div class="col-md-4"><label class="form-label">Expected Yield (kg)</label>
                        <input type="number" name="expected_yield" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-4"><label class="form-label">Corn Milling (kgs)</label>
                        <input type="number" name="milling_kgs" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-4"><label class="form-label">Bagging (no. of bags)</label>
                        <input type="number" name="bagging_bags" class="form-control" step="0.01" min="0" value="0">
                    </div>

                    <div class="col-12"><label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Create Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
