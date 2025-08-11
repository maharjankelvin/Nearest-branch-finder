<?php
// Login page
include 'includes/auth.php';

// Redirect if already logged in
autoRedirectByRole();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (login($username, $password)) {
        // Check if there's a redirect URL stored
        $redirectUrl = '';
        if (isset($_SESSION['redirect_after_login'])) {
            $redirectUrl = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            
            // Validate redirect URL to prevent open redirect attacks
            if (strpos($redirectUrl, '/food_ordering/') === 0) {
                header('Location: ' . $redirectUrl);
                exit();
            }
        }
        
        // Default redirect based on role
        if (hasRole('admin')) {
            header('Location: admin/dashboard.php');
        } elseif (hasRole('branch_moderator')) {
            header('Location: branch/dashboard.php');
        } else {
            header('Location: user/menu.php');
        }
        exit();
    } else {
        $error = 'Invalid username or password.';
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h1>Login</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['redirect_after_login'])): ?>
            <div class="alert alert-info">
                You will be redirected to your requested page after login.
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <p style="margin-top: 1rem; text-align: center;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
