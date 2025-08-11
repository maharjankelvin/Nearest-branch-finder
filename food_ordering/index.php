<?php
// Landing page
include 'includes/auth.php';

// Check for error messages
$errorMessage = '';
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Redirect if already logged in
autoRedirectByRole();

include 'includes/header.php';
?>

<div class="container">
    <?php if ($errorMessage): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; padding: 3rem 0;">
        <h1>Welcome to Food Ordering System</h1>
        <p style="font-size: 1.2rem; margin: 2rem 0; color: #666;">
            Order delicious food from multiple café branches with cash on delivery option.
        </p>
        
        <div style="margin: 2rem 0;">
            <a href="login.php" class="btn btn-primary" style="margin: 0 1rem;">Login</a>
            <a href="register.php" class="btn btn-success" style="margin: 0 1rem;">Register</a>
        </div>
    </div>
    
    <div class="dashboard-grid" style="margin-top: 3rem;">
        <div class="dashboard-card">
            <h3>🍕 Multiple Cuisines</h3>
            <p>Choose from pizza, burgers, pasta, salads and more!</p>
        </div>
        <div class="dashboard-card">
            <h3>📍 Multiple Branches</h3>
            <p>We automatically find the nearest branch to deliver your order.</p>
        </div>
        <div class="dashboard-card">
            <h3>💰 Cash on Delivery</h3>
            <p>Pay when you receive your order. No online payment required.</p>
        </div>
        <div class="dashboard-card">
            <h3>⚡ Fast Delivery</h3>
            <p>Quick delivery from the nearest café branch to your location.</p>
        </div>
    </div>
    
    <div class="card" style="margin-top: 3rem;">
        <div class="card-header">
            <h2>How It Works</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                <div>
                    <h4>1. Register</h4>
                    <p>Create your account to start ordering</p>
                </div>
                <div>
                    <h4>2. Browse Menu</h4>
                    <p>Select your favorite food items</p>
                </div>
                <div>
                    <h4>3. Enter Location</h4>
                    <p>Provide your delivery address</p>
                </div>
                <div>
                    <h4>4. Place Order</h4>
                    <p>We'll route it to the nearest branch</p>
                </div>
                <div>
                    <h4>5. Enjoy!</h4>
                    <p>Pay cash on delivery and enjoy your meal</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
