<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /user/dashboard.php');
    exit;
}

$page_title = 'User Management';

// Get all users
$stmt = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
    <div></div>
    <button class="btn btn-primary" onclick="document.getElementById('createUserModal').style.display='block'">
        <i class="fas fa-user-plus"></i> Add User
    </button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= h($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= h($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">No users found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= h($user['username']) ?></td>
                        <td>
                            <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y H:i', strtotime($user['created_at'])) ?></td>
                        <td>
                            <button class="btn-icon" onclick="editUser(<?= $user['id'] ?>, '<?= h($user['username']) ?>', '<?= $user['role'] ?>')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn-icon" onclick="deleteUser(<?= $user['id'] ?>, '<?= h($user['username']) ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add User</h3>
            <span class="close" onclick="document.getElementById('createUserModal').style.display='none'">&times;</span>
        </div>
        <form action="create_user.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="form-control">
                        <option value="user">Normal User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createUserModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit User</h3>
            <span class="close" onclick="document.getElementById('editUserModal').style.display='none'">&times;</span>
        </div>
        <form action="edit_user.php" method="POST">
            <div class="modal-body">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="edit_password" name="password" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role" class="form-control">
                        <option value="user">Normal User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editUserModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, username, role) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_role').value = role;
    document.getElementById('editUserModal').style.display = 'block';
}

function deleteUser(id, username) {
    if (confirm('Are you sure you want to delete user "' + username + '"? This action cannot be undone.')) {
        window.location.href = 'delete_user.php?id=' + id;
    }
}

window.onclick = function(event) {
    const modals = ['createUserModal', 'editUserModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>

