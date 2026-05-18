<?php
ob_start();
$pageTitle = 'User Management';
$activeMenu = 'users';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small"><?= count($users) ?> user(s) registered</span>
    <button class="btn btn-success btn-sm" onclick="openModal()">
        <i class="bi bi-person-plus me-1"></i>Add User
    </button>
</div>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-person-gear me-2 text-success"></i>System Users</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($u['role_name']) ?></span></td>
                <td><span class="badge badge-<?= $u['status'] === 'active' ? 'approved' : 'rejected' ?>"><?= ucfirst($u['status']) ?></span></td>
                <td class="text-muted small"><?= $u['last_login'] ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never' ?></td>
                <td>
                    <button class="btn btn-xs btn-outline-secondary me-1" onclick='editUser(<?= htmlspecialchars(json_encode($u)) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <button class="btn btn-xs btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="userId">
                    <div id="formAlert" class="alert d-none"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="full_name" id="fullName" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label fw-semibold">Role</label>
                            <select name="role_id" id="roleId" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Password <span id="pwdHint" class="text-muted fw-normal small">(required)</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="saveBtn">
                        <i class="bi bi-check-lg me-1"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';

function openModal(user = null) {
    document.getElementById('userForm').reset();
    document.getElementById('formAlert').className = 'alert d-none';
    document.getElementById('userId').value = '';

    if (user) {
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('userId').value  = user.id;
        document.getElementById('fullName').value = user.full_name;
        document.getElementById('username').value = user.username;
        document.getElementById('email').value    = user.email;
        document.getElementById('roleId').value   = user.role_id;
        document.getElementById('status').value   = user.status;
        document.getElementById('pwdHint').textContent = '(leave blank to keep current)';
        document.getElementById('password').removeAttribute('required');
    } else {
        document.getElementById('modalTitle').textContent = 'Add User';
        document.getElementById('pwdHint').textContent = '(required)';
        document.getElementById('password').setAttribute('required', '');
    }

    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function editUser(user) { openModal(user); }

function deleteUser(id, username) {
    if (!confirm(`Delete user "${username}"? This cannot be undone.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch(BASE + '/users/delete', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) location.reload();
            else alert(res.message);
        });
}

document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;

    fetch(BASE + '/users/save', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
                location.reload();
            } else {
                const a = document.getElementById('formAlert');
                a.className = 'alert alert-danger';
                a.textContent = res.message;
            }
        })
        .catch(() => { btn.disabled = false; });
});

function togglePwd() {
    const inp = document.getElementById('password');
    const ico = document.getElementById('eyeIcon');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
