<?php
// Branch Orders Management
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/functions.php';

requireBranchModerator();

$branchId = $_SESSION['branch_id'];

// Validate branch assignment
if (!$branchId) {
    $_SESSION['error_message'] = 'You are not assigned to any branch. Please contact the administrator.';
    header('Location: /food_ordering/index.php');
    exit();
}

// Verify branch exists and is active
$branch = getSingleResult("SELECT name FROM branches WHERE id = ? AND status = 'active'", [$branchId]);
if (!$branch) {
    $_SESSION['error_message'] = 'Branch not found or inactive. Please contact the administrator.';
    header('Location: /food_ordering/index.php');
    exit();
}

$message = '';
$success = false;

// Helper function for status badge classes
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'badge-warning';
        case 'confirmed': return 'badge-info';
        case 'preparing': return 'badge-info';
        case 'out_for_delivery': return 'badge-primary';
        case 'delivered': return 'badge-success';
        case 'cancelled': return 'badge-danger';
        default: return 'badge-secondary';
    }
}

// Helper function for status icons
function getStatusIcon($status) {
    switch ($status) {
        case 'pending': return '⏰';
        case 'confirmed': return '✅';
        case 'preparing': return '👨‍🍳';
        case 'out_for_delivery': return '🚚';
        case 'delivered': return '📦';
        case 'cancelled': return '❌';
        default: return '📋';
    }
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $orderId = intval($_POST['order_id']);
        $newStatus = sanitizeInput($_POST['status']);
        
        // Validate status transitions
        $validStatuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            $message = '<div class="alert alert-error">Invalid status selected.</div>';
        } else {
            // Verify order belongs to this branch
            $order = getSingleResult("SELECT status FROM orders WHERE id = ? AND branch_id = ?", [$orderId, $branchId]);
            
            if ($order) {
                // Check if status transition is valid
                $currentStatus = $order['status'];
                $validTransitions = [
                    'pending' => ['confirmed', 'cancelled'],
                    'confirmed' => ['preparing', 'cancelled'],
                    'preparing' => ['out_for_delivery', 'cancelled'],
                    'out_for_delivery' => ['delivered', 'cancelled'],
                    'delivered' => [], // No transitions from delivered
                    'cancelled' => [] // No transitions from cancelled
                ];
                
                if (in_array($newStatus, $validTransitions[$currentStatus]) || $newStatus === $currentStatus) {
                    $stmt = executeQuery("UPDATE orders SET status = ? WHERE id = ?", [$newStatus, $orderId]);
                    if ($stmt->affected_rows > 0) {
                        $message = '<div class="alert alert-success">Order #' . $orderId . ' status updated to ' . ucfirst($newStatus) . ' successfully!</div>';
                        $success = true;
                    } else {
                        $message = '<div class="alert alert-error">Failed to update order status.</div>';
                    }
                } else {
                    $message = '<div class="alert alert-error">Invalid status transition from ' . ucfirst($currentStatus) . ' to ' . ucfirst($newStatus) . '.</div>';
                }
            } else {
                $message = '<div class="alert alert-error">Order not found or unauthorized access.</div>';
            }
        }
    }
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$viewOrder = isset($_GET['view']) ? intval($_GET['view']) : 0;

// Build query
$whereClause = "WHERE o.branch_id = ?";
$params = [$branchId];

if ($statusFilter) {
    $whereClause .= " AND o.status = ?";
    $params[] = $statusFilter;
}

// Get orders for this branch
$orders = getMultipleResults("
    SELECT o.*, u.username, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $whereClause
    ORDER BY o.order_date DESC
", $params);

// Get specific order details if viewing
$viewOrderDetails = null;
$orderItems = [];
if ($viewOrder) {
    $viewOrderDetails = getSingleResult("
        SELECT o.*, u.username, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.branch_id = ?
    ", [$viewOrder, $branchId]);
    
    if ($viewOrderDetails) {
        $orderItems = getMultipleResults("
            SELECT oi.*, mi.name as item_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ", [$viewOrder]);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>📋 Order Management - <?php echo htmlspecialchars($branch['name']); ?></h1>
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
    
    <?php if ($message): ?>
        <?php echo $message; ?>
        <?php if ($success): ?>
            <script>
                // Auto refresh after successful update
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Filter Options -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2>🔍 Filter Orders</h2>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="orders.php" class="btn <?php echo !$statusFilter ? 'btn-primary' : 'btn-secondary'; ?>">
                    📊 All Orders
                </a>
                <a href="orders.php?status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-warning' : 'btn-secondary'; ?>">
                    ⏰ Pending
                </a>
                <a href="orders.php?status=confirmed" class="btn <?php echo $statusFilter === 'confirmed' ? 'btn-info' : 'btn-secondary'; ?>">
                    ✅ Confirmed
                </a>
                <a href="orders.php?status=preparing" class="btn <?php echo $statusFilter === 'preparing' ? 'btn-info' : 'btn-secondary'; ?>">
                    👨‍🍳 Preparing
                </a>
                <a href="orders.php?status=out_for_delivery" class="btn <?php echo $statusFilter === 'out_for_delivery' ? 'btn-primary' : 'btn-secondary'; ?>">
                    🚚 Out for Delivery
                </a>
                <a href="orders.php?status=delivered" class="btn <?php echo $statusFilter === 'delivered' ? 'btn-success' : 'btn-secondary'; ?>">
                    ✅ Delivered
                </a>
                <a href="orders.php?status=cancelled" class="btn <?php echo $statusFilter === 'cancelled' ? 'btn-danger' : 'btn-secondary'; ?>">
                    ❌ Cancelled
                </a>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <?php if ($viewOrderDetails): ?>
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Order #<?php echo $viewOrderDetails['id']; ?> Details</h2>
                    <a href="orders.php<?php echo $statusFilter ? '?status=' . $statusFilter : ''; ?>" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div>
                        <strong>Customer:</strong><br>
                        <?php echo htmlspecialchars($viewOrderDetails['username']); ?><br>
                        <small><?php echo htmlspecialchars($viewOrderDetails['email']); ?></small>
                    </div>
                    <div>
                        <strong>Order Date:</strong><br>
                        <?php echo date('M j, Y g:i A', strtotime($viewOrderDetails['order_date'])); ?>
                    </div>
                    <div>
                        <strong>Total Amount:</strong><br>
                        <?php echo formatCurrency($viewOrderDetails['total_amount']); ?>
                    </div>
                    <div>
                        <strong>Current Status:</strong><br>
                        <span class="badge <?php echo getStatusBadgeClass($viewOrderDetails['status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $viewOrderDetails['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <strong>Delivery Address:</strong><br>
                    <?php echo htmlspecialchars($viewOrderDetails['delivery_address']); ?>
                    <?php if ($viewOrderDetails['delivery_latitude'] && $viewOrderDetails['delivery_longitude']): ?>
                        <br><small>Coordinates: <?php echo $viewOrderDetails['delivery_latitude'] . ', ' . $viewOrderDetails['delivery_longitude']; ?></small>
                    <?php endif; ?>
                </div>
                
                <!-- Order Items -->
                <div style="margin-bottom: 2rem;">
                    <strong>Order Items:</strong>
                    <table class="table" style="margin-top: 0.5rem;">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo formatCurrency($item['price']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Status Update Form -->
                <form method="POST" style="margin-top: 2rem;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?php echo $viewOrderDetails['id']; ?>">
                    <div class="form-group">
                        <label for="status">Update Order Status:</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="pending" <?php echo $viewOrderDetails['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $viewOrderDetails['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="preparing" <?php echo $viewOrderDetails['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="out_for_delivery" <?php echo $viewOrderDetails['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $viewOrderDetails['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $viewOrderDetails['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Orders List -->
    <div class="card">
        <div class="card-header">
            <h2>
                📋 Orders 
                <?php if ($statusFilter): ?>
                    - <?php echo getStatusIcon($statusFilter) . ' ' . ucfirst(str_replace('_', ' ', $statusFilter)); ?>
                    (<?php echo count($orders); ?> orders)
                <?php else: ?>
                    (<?php echo count($orders); ?> total orders)
                <?php endif; ?>
            </h2>
        </div>
        <div class="card-body">
            <?php if (!empty($orders)): ?>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 700px;">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr class="order-row" data-status="<?php echo $order['status']; ?>">
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['username']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                            <?php echo getStatusIcon($order['status']) . ' ' . ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($order['order_date'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($order['delivery_address'], 0, 30)); ?>
                                        <?php echo strlen($order['delivery_address']) > 30 ? '...' : ''; ?></small>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <a href="orders.php?view=<?php echo $order['id']; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" 
                                               class="btn btn-primary btn-sm">
                                                👁️ View
                                            </a>
                                            <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                                <!-- Quick status update buttons -->
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirm this order?')">
                                                            ✅ Confirm
                                                        </button>
                                                    </form>
                                                <?php elseif ($order['status'] === 'confirmed'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="preparing">
                                                        <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Start preparing this order?')">
                                                            👨‍🍳 Prepare
                                                        </button>
                                                    </form>
                                                <?php elseif ($order['status'] === 'preparing'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="out_for_delivery">
                                                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Send out for delivery?')">
                                                            🚚 Send Out
                                                        </button>
                                                    </form>
                                                <?php elseif ($order['status'] === 'out_for_delivery'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="delivered">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark as delivered?')">
                                                            📦 Delivered
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem;">
                    <h3>📋 No orders found</h3>
                    <p>
                        <?php if ($statusFilter): ?>
                            No orders with status "<?php echo ucfirst(str_replace('_', ' ', $statusFilter)); ?>" found.
                        <?php else: ?>
                            No orders have been placed for your branch yet.
                        <?php endif; ?>
                    </p>
                    <?php if ($statusFilter): ?>
                        <a href="orders.php" class="btn btn-primary">View All Orders</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
