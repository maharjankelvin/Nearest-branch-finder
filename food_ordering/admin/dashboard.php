<?php
// Admin Dashboard
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/functions.php';

requireAdmin();

// Get statistics
$totalUsers = getSingleResult("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'];
$totalBranches = getSingleResult("SELECT COUNT(*) as count FROM branches WHERE status = 'active'")['count'];
$totalMenuItems = getSingleResult("SELECT COUNT(*) as count FROM menu_items WHERE status = 'available'")['count'];
$totalOrders = getSingleResult("SELECT COUNT(*) as count FROM orders")['count'];
$pendingOrders = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'];
$todayOrders = getSingleResult("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()")['count'];
$todayRevenue = getSingleResult("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(order_date) = CURDATE() AND status != 'cancelled'")['revenue'];

// Get recent orders
$recentOrders = getMultipleResults("
    SELECT o.id, o.total_amount, o.status, o.order_date, u.username, b.name as branch_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN branches b ON o.branch_id = b.id
    ORDER BY o.order_date DESC
    LIMIT 10
");

include '../includes/header.php';
?>

<div class="container">
    <h1>Admin Dashboard</h1>
    
    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3>Total Users</h3>
            <div class="number"><?php echo $totalUsers; ?></div>
        </div>
        <div class="dashboard-card">
            <h3>Active Branches</h3>
            <div class="number"><?php echo $totalBranches; ?></div>
        </div>
        <div class="dashboard-card">
            <h3>Menu Items</h3>
            <div class="number"><?php echo $totalMenuItems; ?></div>
        </div>
        <div class="dashboard-card">
            <h3>Total Orders</h3>
            <div class="number"><?php echo $totalOrders; ?></div>
        </div>
        <div class="dashboard-card">
            <h3>Pending Orders</h3>
            <div class="number"><?php echo $pendingOrders; ?></div>
        </div>
        <div class="dashboard-card">
            <h3>Today's Orders</h3>
            <div class="number"><?php echo $todayOrders; ?></div>
        </div>
        <div class="dashboard-card">
            <h3>Today's Revenue</h3>
            <div class="number"><?php echo formatCurrency($todayRevenue); ?></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2>Quick Actions</h2>
        </div>
        <div class="card-body">
            <a href="manage_menu.php" class="btn btn-primary">Manage Menu</a>
            <a href="manage_branches.php" class="btn btn-success">Manage Branches</a>
            <a href="manage_users.php" class="btn btn-warning">Manage Users</a>
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
                            <th>Branch</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo htmlspecialchars($order['branch_name']); ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><span class="badge <?php echo getStatusBadgeClass($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No orders found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
