<?php
// Admin Branch Management
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
                $name = sanitizeInput($_POST['name']);
                $address = sanitizeInput($_POST['address']);
                $latitude = floatval($_POST['latitude']);
                $longitude = floatval($_POST['longitude']);
                $phone = sanitizeInput($_POST['phone']);
                
                $stmt = executeQuery(
                    "INSERT INTO branches (name, address, latitude, longitude, phone) VALUES (?, ?, ?, ?, ?)",
                    [$name, $address, $latitude, $longitude, $phone]
                );
                
                if ($stmt->affected_rows > 0) {
                    $message = '<div class="alert alert-success">Branch added successfully!</div>';
                } else {
                    $message = '<div class="alert alert-error">Failed to add branch.</div>';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = sanitizeInput($_POST['name']);
                $address = sanitizeInput($_POST['address']);
                $latitude = floatval($_POST['latitude']);
                $longitude = floatval($_POST['longitude']);
                $phone = sanitizeInput($_POST['phone']);
                $status = sanitizeInput($_POST['status']);
                
                $stmt = executeQuery(
                    "UPDATE branches SET name = ?, address = ?, latitude = ?, longitude = ?, phone = ?, status = ? WHERE id = ?",
                    [$name, $address, $latitude, $longitude, $phone, $status, $id]
                );
                
                if ($stmt->affected_rows > 0) {
                    $message = '<div class="alert alert-success">Branch updated successfully!</div>';
                } else {
                    $message = '<div class="alert alert-error">Failed to update branch.</div>';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                // Check if branch has orders
                $hasOrders = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE branch_id = ?", [$id])['count'];
                
                if ($hasOrders > 0) {
                    $message = '<div class="alert alert-error">Cannot delete branch with existing orders. Set status to inactive instead.</div>';
                } else {
                    $stmt = executeQuery("DELETE FROM branches WHERE id = ?", [$id]);
                    if ($stmt->affected_rows > 0) {
                        $message = '<div class="alert alert-success">Branch deleted successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-error">Failed to delete branch.</div>';
                    }
                }
                break;
        }
    }
}

// Get all branches
$branches = getMultipleResults("SELECT * FROM branches ORDER BY name");

include '../includes/header.php';
?>

<div class="container">
    <h1>🏪 Branch Management</h1>
    
    <?php echo $message; ?>

    <!-- Add New Branch Form -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2>➕ Add New Branch</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="name">Branch Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="latitude">Latitude:</label>
                        <input type="number" id="latitude" name="latitude" step="any" required>
                    </div>
                    <div class="form-group">
                        <label for="longitude">Longitude:</label>
                        <input type="number" id="longitude" name="longitude" step="any" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-success">Add Branch</button>
            </form>
        </div>
    </div>

    <!-- Branches List -->
    <div class="card">
        <div class="card-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>📋 All Branches</h2>
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                    <small><strong>Quick Reference:</strong> Branch IDs are shown in blue badges for easy identification</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($branches)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Branch ID</th>
                            <th>Branch Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>GPS Coordinates</th>
                            <th>Status</th>
                            <th>Assigned Staff</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $branch): ?>
                            <?php 
                            // Get assigned moderators for this branch
                            $assignedModerators = getMultipleResults("SELECT username FROM users WHERE branch_id = ? AND role = 'branch_moderator'", [$branch['id']]);
                            ?>
                            <tr>
                                <td>
                                    <strong style="background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.9rem;">
                                        ID: <?php echo $branch['id']; ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($branch['name']); ?></strong>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($branch['address']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($branch['phone']); ?></td>
                                <td>
                                    <small>
                                        <strong>Lat:</strong> <?php echo $branch['latitude']; ?><br>
                                        <strong>Lng:</strong> <?php echo $branch['longitude']; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $branch['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($branch['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($assignedModerators)): ?>
                                        <?php foreach ($assignedModerators as $mod): ?>
                                            <small style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; margin: 1px; display: inline-block;">
                                                👤 <?php echo htmlspecialchars($mod['username']); ?>
                                            </small><br>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <small style="color: #dc3545;">❌ No staff assigned</small>
                                    <?php endif; ?>
                                </td>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-primary" onclick="editBranch(<?php echo htmlspecialchars(json_encode($branch)); ?>)">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this branch?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No branches found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <h3>Edit Branch <span id="editBranchIdDisplay" style="background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; margin-left: 10px;"></span></h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <strong>📋 Branch Information:</strong><br>
                <small><strong>Branch ID:</strong> <span id="editBranchIdText"></span> (This ID is used for staff assignment)</small>
            </div>
            
            <div class="form-group">
                <label for="editName">Branch Name:</label>
                <input type="text" id="editName" name="name" required>
            </div>
            <div class="form-group">
                <label for="editPhone">Phone:</label>
                <input type="tel" id="editPhone" name="phone">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="editLatitude">Latitude:</label>
                    <input type="number" id="editLatitude" name="latitude" step="any" required>
                </div>
                <div class="form-group">
                    <label for="editLongitude">Longitude:</label>
                    <input type="number" id="editLongitude" name="longitude" step="any" required>
                </div>
            </div>
            <div class="form-group">
                <label for="editAddress">Address:</label>
                <textarea id="editAddress" name="address" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="editStatus">Status:</label>
                <select id="editStatus" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Update Branch</button>
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function editBranch(branch) {
    document.getElementById('editId').value = branch.id;
    document.getElementById('editBranchIdDisplay').textContent = 'ID: ' + branch.id;
    document.getElementById('editBranchIdText').textContent = branch.id;
    document.getElementById('editName').value = branch.name;
    document.getElementById('editPhone').value = branch.phone || '';
    document.getElementById('editLatitude').value = branch.latitude;
    document.getElementById('editLongitude').value = branch.longitude;
    document.getElementById('editAddress').value = branch.address;
    document.getElementById('editStatus').value = branch.status;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
