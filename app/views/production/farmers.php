<?php
ob_start();
$pageTitle  = 'Farmers';
$activeMenu = 'production';
?>

<?php if (($_SESSION['user']['role'] ?? '') === 'gm'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>GM View Mode:</strong> You can view all farmer data for approval context, but can only approve/reject requests through the Approvals section.
</div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/production" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <?php if (($_SESSION['user']['role'] ?? '') !== 'gm'): ?>
    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#farmerModal" onclick="resetForm()"><i class="bi bi-plus-circle me-1"></i>Add Farmer</button>
    <?php endif; ?>
</div>
<div class="table-card">
    <div class="card-header"><span><i class="bi bi-people me-2 text-success"></i>Farmers</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Phone</th><th>Address</th><th>Farm Area (ha)</th><th>Records</th><th>Total Yield</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($farmers as $f): ?>
            <tr>
                <td><strong><?= htmlspecialchars($f['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($f['phone'] ?? '&#8369;') ?></td>
                <td class="small text-muted"><?= htmlspecialchars(mb_strimwidth($f['address'] ?? '', 0, 40, '&#8369;')) ?></td>
                <td><?= number_format($f['farm_area_ha'],2) ?></td>
                <td><?= $f['record_count'] ?></td>
                <td><?= number_format($f['total_yield'],2) ?></td>
                <td><span class="badge badge-<?= $f['status']==='active' ? 'approved' : 'rejected' ?>"><?= ucfirst($f['status']) ?></span></td>
                <td>
                    <?php if (($_SESSION['user']['role'] ?? '') !== 'gm'): ?>
                    <button class="btn btn-xs btn-outline-secondary" onclick="editFarmer(<?= htmlspecialchars(json_encode($f)) ?>)"><i class="bi bi-pencil"></i></button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($farmers)): ?><tr><td colspan="8" class="text-center text-muted py-4">No farmers yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="farmerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="farmerModalTitle">Add Farmer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>/production/save-farmer" method="POST" data-ajax data-reload="true">
                <input type="hidden" name="id" id="farmerId" value="0">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" name="full_name" id="farmerName" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" id="farmerPhone" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Farm Area (ha)</label><input type="number" name="farm_area_ha" id="farmerArea" class="form-control" step="0.01" value="0"></div>
                    <div class="col-12"><label class="form-label">Address</label><textarea name="address" id="farmerAddress" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Status</label><select name="status" id="farmerStatus" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save</button></div>
            </form>
        </div>
    </div>
</div>
<script>
const BASE_URL = '<?= BASE_URL ?>';
function resetForm() { document.getElementById('farmerModalTitle').textContent='Add Farmer'; document.getElementById('farmerId').value=0; ['farmerName','farmerPhone','farmerAddress'].forEach(id=>document.getElementById(id).value=''); document.getElementById('farmerArea').value=0; document.getElementById('farmerStatus').value='active'; }
function editFarmer(f) { document.getElementById('farmerModalTitle').textContent='Edit Farmer'; document.getElementById('farmerId').value=f.id; document.getElementById('farmerName').value=f.full_name; document.getElementById('farmerPhone').value=f.phone||''; document.getElementById('farmerArea').value=f.farm_area_ha||0; document.getElementById('farmerAddress').value=f.address||''; document.getElementById('farmerStatus').value=f.status||'active'; new bootstrap.Modal(document.getElementById('farmerModal')).show(); }
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>


