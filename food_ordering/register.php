<?php
// Registration page
include 'includes/auth.php';

// Redirect if already logged in
autoRedirectByRole();

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    if (strlen($username) < 3) {
        $message = 'Username must be at least 3 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        if (register($username, $email, $password)) {
            $message = 'Registration successful! You can now log in.';
            $success = true;
        } else {
            $message = 'Registration failed. Username or email may already exist.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h1>Register</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           minlength="3">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-success">Register</button>
            </form>
        <?php endif; ?>
        
        <p style="margin-top: 1rem; text-align: center;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
