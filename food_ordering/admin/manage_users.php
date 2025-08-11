<?php
// Admin User Management
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/functions.php';

requireAdmin();

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = sanitizeInput($_POST['username']);
                $email = sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                $role = sanitizeInput($_POST['role']);
                $branch_id = $_POST['branch_id'] ? intval($_POST['branch_id']) : null;
                
                if (register($username, $email, $password, $role, $branch_id)) {
                    $message = '<div class="alert alert-success">User added successfully!</div>';
                } else {
                    $message = '<div class="alert alert-error">Failed to add user. Username or email may already exist.</div>';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $username = sanitizeInput($_POST['username']);
                $email = sanitizeInput($_POST['email']);
                $role = sanitizeInput($_POST['role']);
                $branch_id = $_POST['branch_id'] ? intval($_POST['branch_id']) : null;
                
                $stmt = executeQuery(
                    "UPDATE users SET username = ?, email = ?, role = ?, branch_id = ? WHERE id = ?",
                    [$username, $email, $role, $branch_id, $id]
                );
                
                if ($stmt->affected_rows > 0) {
                    $message = '<div class="alert alert-success">User updated successfully!</div>';
                } else {
                    $message = '<div class="alert alert-error">Failed to update user.</div>';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                // Check if user has orders
                $hasOrders = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$id])['count'];
                
                if ($hasOrders > 0) {
                    $message = '<div class="alert alert-error">Cannot delete user with existing orders.</div>';
                } else {
                    $stmt = executeQuery("DELETE FROM users WHERE id = ?", [$id]);
                    if ($stmt->affected_rows > 0) {
                        $message = '<div class="alert alert-success">User deleted successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-error">Failed to delete user.</div>';
                    }
                }
                break;
        }
    }
}

// Get all users and branches
$users = getMultipleResults("SELECT u.*, b.name as branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.id ORDER BY u.created_at DESC");
$branches = getMultipleResults("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name");

include '../includes/header.php';
?>

<div class="container">
    <h1>Manage Users</h1>
    
    <?php echo $message; ?>

    <!-- Add New User Form -->
    <div class="card">
        <div class="card-header">
            <h2>Add New User</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" required onchange="toggleBranchField()">
                            <option value="">Select Role</option>
                            <option value="user">Regular User</option>
                            <option value="branch_moderator">Branch Moderator</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="branchField" style="display: none;">
                    <label for="branch_id">Branch:</label>
                    <select id="branch_id" name="branch_id">
                        <option value="">Select Branch</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Add User</button>
            </form>
        </div>
    </div>

    <!-- Users List -->
    <div class="card">
        <div class="card-header">
            <h2>Users</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($users)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-danger' : ($user['role'] === 'branch_moderator' ? 'badge-warning' : 'badge-info'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['branch_name'] ? htmlspecialchars($user['branch_name']) : '-'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <h3>Edit User</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="form-group">
                <label for="editUsername">Username:</label>
                <input type="text" id="editUsername" name="username" required>
            </div>
            <div class="form-group">
                <label for="editEmail">Email:</label>
                <input type="email" id="editEmail" name="email" required>
            </div>
            <div class="form-group">
                <label for="editRole">Role:</label>
                <select id="editRole" name="role" required onchange="toggleEditBranchField()">
                    <option value="user">Regular User</option>
                    <option value="branch_moderator">Branch Moderator</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group" id="editBranchField">
                <label for="editBranchId">Branch:</label>
                <select id="editBranchId" name="branch_id">
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Update</button>
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function toggleBranchField() {
    const role = document.getElementById('role').value;
    const branchField = document.getElementById('branchField');
    if (role === 'branch_moderator') {
        branchField.style.display = 'block';
    } else {
        branchField.style.display = 'none';
    }
}

function toggleEditBranchField() {
    const role = document.getElementById('editRole').value;
    const branchField = document.getElementById('editBranchField');
    if (role === 'branch_moderator') {
        branchField.style.display = 'block';
    } else {
        branchField.style.display = 'none';
    }
}

function editUser(user) {
    document.getElementById('editId').value = user.id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editRole').value = user.role;
    document.getElementById('editBranchId').value = user.branch_id || '';
    toggleEditBranchField();
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
