<?php
// Admin Menu Management
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
                $description = sanitizeInput($_POST['description']);
                $price = floatval($_POST['price']);
                $category = sanitizeInput($_POST['category']);
                $image_url = '';
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../assets/images/';
                    $fileTmp = $_FILES['image_file']['tmp_name'];
                    $fileName = basename($_FILES['image_file']['name']);
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($fileExt, $allowed)) {
                        $newName = uniqid('menu_', true) . '.' . $fileExt;
                        $destPath = $uploadDir . $newName;
                        if (move_uploaded_file($fileTmp, $destPath)) {
                            $image_url = 'assets/images/' . $newName;
                        }
                    }
                } else if (!empty($_POST['image_url'])) {
                    $image_url = sanitizeInput($_POST['image_url']);
                }
                $stmt = executeQuery(
                    "INSERT INTO menu_items (name, description, price, category, image_url) VALUES (?, ?, ?, ?, ?)",
                    [$name, $description, $price, $category, $image_url]
                );
                
                if ($stmt->affected_rows > 0) {
                    $message = '<div class="alert alert-success">Menu item added successfully!</div>';
                } else {
                    $message = '<div class="alert alert-error">Failed to add menu item.</div>';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description']);
                $price = floatval($_POST['price']);
                $category = sanitizeInput($_POST['category']);
                $image_url = '';
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../assets/images/';
                    $fileTmp = $_FILES['image_file']['tmp_name'];
                    $fileName = basename($_FILES['image_file']['name']);
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($fileExt, $allowed)) {
                        $newName = uniqid('menu_', true) . '.' . $fileExt;
                        $destPath = $uploadDir . $newName;
                        if (move_uploaded_file($fileTmp, $destPath)) {
                            $image_url = 'assets/images/' . $newName;
                        }
                    }
                } else if (!empty($_POST['image_url'])) {
                    $image_url = sanitizeInput($_POST['image_url']);
                }
                $status = sanitizeInput($_POST['status']);
                
                $stmt = executeQuery(
                    "UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, image_url = ?, status = ? WHERE id = ?",
                    [$name, $description, $price, $category, $image_url, $status, $id]
                );
                
                if ($stmt->affected_rows > 0) {
                    $message = '<div class="alert alert-success">Menu item updated successfully!</div>';
                } else {
                    $message = '<div class="alert alert-error">Failed to update menu item.</div>';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $stmt = executeQuery("DELETE FROM menu_items WHERE id = ?", [$id]);
                
                if ($stmt->affected_rows > 0) {
                    $message = '<div class="alert alert-success">Menu item deleted successfully!</div>';
                } else {
                    $message = '<div class="alert alert-error">Failed to delete menu item.</div>';
                }
                break;
        }
    }
}

// Get all menu items
$menuItems = getMultipleResults("SELECT * FROM menu_items ORDER BY category, name");
$categories = getMultipleResults("SELECT DISTINCT category FROM menu_items WHERE category IS NOT NULL ORDER BY category");

include '../includes/header.php';
?>

<div class="container">
    <h1>Manage Menu</h1>
    
    <?php echo $message; ?>

    <!-- Add New Menu Item Form -->
    <div class="card">
        <div class="card-header">
            <h2>Add New Menu Item</h2>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <input type="text" id="category" name="category" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price:</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="image_file">Image Upload:</label>
                        <input type="file" id="image_file" name="image_file" accept="image/*">
                        <br>
                        <label for="image_url">Or Image URL:</label>
                        <input type="url" id="image_url" name="image_url">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Add Menu Item</button>
            </form>
        </div>
    </div>

    <!-- Menu Items List -->
    <div class="card">
        <div class="card-header">
            <h2>Menu Items</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($menuItems)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menuItems as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo formatCurrency($item['price']); ?></td>
                                <td>
                                    <span class="badge <?php echo $item['status'] === 'available' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-primary" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No menu items found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <h3>Edit Menu Item</h3>
        <form method="POST" id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="form-group">
                <label for="editName">Name:</label>
                <input type="text" id="editName" name="name" required>
            </div>
            <div class="form-group">
                <label for="editCategory">Category:</label>
                <input type="text" id="editCategory" name="category" required>
            </div>
            <div class="form-group">
                <label for="editPrice">Price:</label>
                <input type="number" id="editPrice" name="price" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="editImageFile">Image Upload:</label>
                <input type="file" id="editImageFile" name="image_file" accept="image/*">
                <br>
                <label for="editImageUrl">Or Image URL:</label>
                <input type="url" id="editImageUrl" name="image_url">
            </div>
            <div class="form-group">
                <label for="editDescription">Description:</label>
                <textarea id="editDescription" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="editStatus">Status:</label>
                <select id="editStatus" name="status">
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Update</button>
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function editItem(item) {
    document.getElementById('editId').value = item.id;
    document.getElementById('editName').value = item.name;
    document.getElementById('editCategory').value = item.category;
    document.getElementById('editPrice').value = item.price;
    document.getElementById('editImageUrl').value = item.image_url || '';
    document.getElementById('editDescription').value = item.description || '';
    document.getElementById('editStatus').value = item.status;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
