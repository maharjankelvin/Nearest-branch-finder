<?php
// User Orders List
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/functions.php';

requireUser(); // Ensure only regular users can access this page

// Get user orders
$orders = getMultipleResults("
    SELECT o.*, b.name as branch_name, b.phone as branch_phone
    FROM orders o
    JOIN branches b ON o.branch_id = b.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
", [$_SESSION['user_id']]);

include '../includes/header.php';
?>

<div class="container">
    <h1>My Orders</h1>
    
    <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $order): ?>
            <div class="card" style="margin-bottom: 1rem;">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Order #<?php echo $order['id']; ?></h3>
                        <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <strong>Order Date:</strong><br>
                            <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?>
                        </div>
                        <div>
                            <strong>Branch:</strong><br>
                            <?php echo htmlspecialchars($order['branch_name']); ?>
                            <?php if ($order['branch_phone']): ?>
                                <br><small><?php echo htmlspecialchars($order['branch_phone']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong>Delivery Address:</strong><br>
                            <?php echo htmlspecialchars($order['delivery_address']); ?>
                        </div>
                        <div>
                            <strong>Total Amount:</strong><br>
                            <?php echo formatCurrency($order['total_amount']); ?>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <?php
                    $orderItems = getMultipleResults("
                        SELECT oi.*, mi.name as item_name
                        FROM order_items oi
                        JOIN menu_items mi ON oi.menu_item_id = mi.id
                        WHERE oi.order_id = ?
                    ", [$order['id']]);
                    ?>
                    
                    <?php if (!empty($orderItems)): ?>
                        <div style="margin-top: 1rem;">
                            <strong>Items Ordered:</strong>
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
                    <?php endif; ?>
                    
                    <!-- Status-specific information -->
                    <?php if ($order['status'] === 'pending'): ?>
                        <div class="alert alert-warning" style="margin-top: 1rem;">
                            Your order is pending confirmation from the branch.
                        </div>
                    <?php elseif ($order['status'] === 'confirmed'): ?>
                        <div class="alert alert-info" style="margin-top: 1rem;">
                            Your order has been confirmed and is being prepared.
                        </div>
                    <?php elseif ($order['status'] === 'preparing'): ?>
                        <div class="alert alert-info" style="margin-top: 1rem;">
                            Your order is being prepared by the kitchen.
                        </div>
                    <?php elseif ($order['status'] === 'out_for_delivery'): ?>
                        <div class="alert alert-info" style="margin-top: 1rem;">
                            Your order is out for delivery! Please have cash ready.
                        </div>
                    <?php elseif ($order['status'] === 'delivered'): ?>
                        <div class="alert alert-success" style="margin-top: 1rem;">
                            Order delivered successfully! Thank you for choosing us.
                        </div>
                    <?php elseif ($order['status'] === 'cancelled'): ?>
                        <div class="alert alert-error" style="margin-top: 1rem;">
                            This order has been cancelled.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders yet.</p>
                <a href="menu.php" class="btn btn-primary">Browse Menu</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
