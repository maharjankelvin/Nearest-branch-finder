<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/food_ordering/assets/css/style.css">
    <title>Food Ordering System</title>
</head>
<body>
<header>
    <div class="header-container">
        <h1><a href="/food_ordering/">Food Ordering System</a></h1>
        <nav>
            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="/food_ordering/admin/dashboard.php">Admin Dashboard</a>
                    <a href="/food_ordering/admin/manage_menu.php">Manage Menu</a>
                    <a href="/food_ordering/admin/manage_branches.php">Manage Branches</a>
                    <a href="/food_ordering/admin/manage_users.php">Manage Users</a>
                <?php elseif ($_SESSION['role'] === 'branch_moderator'): ?>
                    <a href="/food_ordering/branch/dashboard.php">Branch Dashboard</a>
                    <a href="/food_ordering/branch/orders.php">Orders</a>
                <?php else: ?>
                    <a href="/food_ordering/user/menu.php">Menu</a>
                    <a href="/food_ordering/user/orders.php">My Orders</a>
                <?php endif; ?>
                <a href="/food_ordering/logout.php">Logout</a>
            <?php else: ?>
                <a href="/food_ordering/login.php">Login</a>
                <a href="/food_ordering/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
