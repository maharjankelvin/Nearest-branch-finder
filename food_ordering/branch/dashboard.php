<?php
// Branch Moderator Dashboard
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

// Get branch information
$branch = getSingleResult("SELECT * FROM branches WHERE id = ? AND status = 'active'", [$branchId]);

if (!$branch) {
    $_SESSION['error_message'] = 'Branch not found or inactive. Please contact the administrator.';
    header('Location: /food_ordering/index.php');
    exit();
}

// Get statistics for this branch
$todayOrders = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE branch_id = ? AND DATE(order_date) = CURDATE()", [$branchId])['count'];
$pendingOrders = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE branch_id = ? AND status = 'pending'", [$branchId])['count'];
$preparingOrders = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE branch_id = ? AND status IN ('confirmed', 'preparing')", [$branchId])['count'];
$outForDelivery = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE branch_id = ? AND status = 'out_for_delivery'", [$branchId])['count'];
$todayRevenue = getSingleResult("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE branch_id = ? AND DATE(order_date) = CURDATE() AND status != 'cancelled'", [$branchId])['revenue'];
$totalOrders = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE branch_id = ?", [$branchId])['count'];

// Get recent orders for this branch
$recentOrders = getMultipleResults("
    SELECT o.id, o.total_amount, o.status, o.order_date, o.delivery_address, u.username
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.branch_id = ?
    ORDER BY o.order_date DESC
    LIMIT 10
", [$branchId]);

include '../includes/header.php';
?>

<div class="container">
    <h1><?php echo htmlspecialchars($branch['name']); ?> - Dashboard</h1>
    
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2>Branch Information</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <strong>Address:</strong><br>
                    <?php echo htmlspecialchars($branch['address']); ?>
                </div>
                <div>
                    <strong>Phone:</strong><br>
                    <?php echo htmlspecialchars($branch['phone']); ?>
                </div>
                <div>
                    <strong>Status:</strong><br>
                    <span class="badge <?php echo $branch['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo ucfirst($branch['status']); ?>
                    </span>
                </div>
                <div>
                    <strong>Coordinates:</strong><br>
                    <?php echo $branch['latitude'] . ', ' . $branch['longitude']; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="dashboard-grid" style="margin-bottom: 2rem;">
        <div class="dashboard-card">
            <div class="dashboard-card-icon">📅</div>
            <h3>Today's Orders</h3>
            <div class="number"><?php echo $todayOrders; ?></div>
            <small>Orders received today</small>
        </div>
        <div class="dashboard-card <?php echo $pendingOrders > 0 ? 'status-warning' : ''; ?>">
            <div class="dashboard-card-icon">⏰</div>
            <h3>Pending Orders</h3>
            <div class="number"><?php echo $pendingOrders; ?></div>
            <small>Awaiting confirmation</small>
        </div>
        <div class="dashboard-card <?php echo $preparingOrders > 0 ? 'status-info' : ''; ?>">
            <div class="dashboard-card-icon">👨‍🍳</div>
            <h3>Preparing</h3>
            <div class="number"><?php echo $preparingOrders; ?></div>
            <small>In kitchen</small>
        </div>
        <div class="dashboard-card <?php echo $outForDelivery > 0 ? 'status-primary' : ''; ?>">
            <div class="dashboard-card-icon">🚚</div>
            <h3>Out for Delivery</h3>
            <div class="number"><?php echo $outForDelivery; ?></div>
            <small>On the way</small>
        </div>
        <div class="dashboard-card status-success">
            <div class="dashboard-card-icon">💰</div>
            <h3>Today's Revenue</h3>
            <div class="number"><?php echo formatCurrency($todayRevenue); ?></div>
            <small>Total earnings today</small>
        </div>
        <div class="dashboard-card">
            <div class="dashboard-card-icon">📊</div>
            <h3>Total Orders</h3>
            <div class="number"><?php echo $totalOrders; ?></div>
            <small>All time orders</small>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2>Quick Actions</h2>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="orders.php" class="btn btn-primary">
                    📋 View All Orders
                </a>
                <a href="orders.php?status=pending" class="btn btn-warning">
                    ⏰ Pending Orders (<?php echo $pendingOrders; ?>)
                </a>
                <a href="orders.php?status=preparing" class="btn btn-info">
                    👨‍🍳 Preparing Orders (<?php echo $preparingOrders; ?>)
                </a>
                <a href="orders.php?status=out_for_delivery" class="btn btn-success">
                    🚚 Out for Delivery (<?php echo $outForDelivery; ?>)
                </a>
            </div>
        </div>
    </div>
        <div class="dashboard-card">
            <h3>Total Orders</h3>
            <div class="number"><?php echo $totalOrders; ?></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2>Quick Actions</h2>
        </div>
        <div class="card-body">
            <a href="orders.php" class="btn btn-primary">Manage Orders</a>
            <a href="orders.php?status=pending" class="btn btn-warning">View Pending Orders</a>
            <a href="orders.php?status=preparing" class="btn btn-info">View Preparing Orders</a>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h2>Recent Orders</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($recentOrders)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Delivery Address</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><span class="badge <?php echo getStatusBadgeClass($order['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></td>
                                <td><?php echo htmlspecialchars(substr($order['delivery_address'], 0, 50)) . (strlen($order['delivery_address']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo date('M j, g:i A', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No orders found for this branch.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
