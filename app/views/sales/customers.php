<?php
ob_start();
$pageTitle = 'Customers';
$activeMenu = 'sales';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/sales" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Sales</a>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#custModal">
        <i class="bi bi-person-plus me-1"></i>Add Customer
    </button>
</div>

<div class="table-card">
    <div class="card-header"><span><i class="bi bi-people me-2 text-success"></i>Customers</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td><?= htmlspecialchars($c['contact_person'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                <td><span class="badge badge-<?= $c['status'] === 'active' ? 'approved' : 'rejected' ?>"><?= ucfirst($c['status']) ?></span></td>
                <td>
                    <button class="btn btn-xs btn-outline-secondary" onclick="editCustomer(<?= htmlspecialchars(json_encode($c)) ?>)">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="custModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="custModalTitle">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>/sales/customers" method="POST" data-ajax data-reload="true">
                <input type="hidden" name="id" id="custId" value="0">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Name</label><input type="text" name="name" id="custName" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Contact Person</label><input type="text" name="contact_person" id="custContact" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" id="custPhone" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" id="custEmail" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Address</label><textarea name="address" id="custAddress" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
function editCustomer(c) {
    document.getElementById('custModalTitle').textContent = 'Edit Customer';
    document.getElementById('custId').value = c.id;
    document.getElementById('custName').value = c.name;
    document.getElementById('custContact').value = c.contact_person || '';
    document.getElementById('custPhone').value = c.phone || '';
    document.getElementById('custEmail').value = c.email || '';
    document.getElementById('custAddress').value = c.address || '';
    new bootstrap.Modal(document.getElementById('custModal')).show();
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>


