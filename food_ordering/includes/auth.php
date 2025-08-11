<?php
// Authentication and session management - Include guard to prevent redeclaration
if (!defined('AUTH_INCLUDED')) {
    define('AUTH_INCLUDED', true);
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include_once 'db.php';

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
    }
}

// Validate session integrity
if (!function_exists('validateSession')) {
    function validateSession() {
        if (isset($_SESSION['user_id'])) {
            // Check if user still exists in database
            $user = getSingleResult("SELECT id, username, role FROM users WHERE id = ?", [$_SESSION['user_id']]);
            if (!$user) {
                // User no longer exists, destroy session
                session_destroy();
                return false;
            }
            
            // Update session if role changed
            if ($user['role'] !== $_SESSION['role']) {
                $_SESSION['role'] = $user['role'];
            }
        }
        return isLoggedIn();
    }
}

// Check user role
if (!function_exists('hasRole')) {
    function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
}

// Redirect if not logged in
if (!function_exists('requireLogin')) {
    function requireLogin($redirectTo = null) {
        if (!validateSession()) {
            // Store the current page to redirect back after login
            if ($redirectTo) {
                $_SESSION['redirect_after_login'] = $redirectTo;
            } else {
                // Get current page path
                $currentPath = $_SERVER['REQUEST_URI'];
                if (strpos($currentPath, '/food_ordering/') !== false) {
                    $_SESSION['redirect_after_login'] = $currentPath;
                }
            }
            header('Location: /food_ordering/login.php');
            exit();
        }
    }
}

// Require specific user role(s)
if (!function_exists('requireRole')) {
    function requireRole($allowedRoles) {
        requireLogin();
        
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            $_SESSION['error_message'] = 'Access denied. You do not have permission to access this page.';
            header('Location: /food_ordering/index.php');
            exit();
        }
    }
}

// Redirect if not admin
if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        requireRole('admin');
    }
}

// Redirect if not branch moderator
if (!function_exists('requireBranchModerator')) {
    function requireBranchModerator() {
        requireRole('branch_moderator');
    }
}

// Require user role (regular user)
if (!function_exists('requireUser')) {
    function requireUser() {
        requireRole('user');
    }
}

// Login function
if (!function_exists('login')) {
    function login($username, $password) {
        $user = getSingleResult(
            "SELECT id, username, email, password, role, branch_id FROM users WHERE username = ? OR email = ?",
            [$username, $username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];
            return true;
        }
        return false;
    }
}

// Register function
if (!function_exists('register')) {
    function register($username, $email, $password, $role = 'user', $branch_id = null) {
        // Check if user already exists
        $existing = getSingleResult(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existing) {
            return false;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = executeQuery(
            "INSERT INTO users (username, email, password, role, branch_id) VALUES (?, ?, ?, ?, ?)",
            [$username, $email, $hashedPassword, $role, $branch_id]
        );
        
        return $stmt->affected_rows > 0;
    }
}

// Auto-redirect based on user role
if (!function_exists('autoRedirectByRole')) {
    function autoRedirectByRole() {
        if (isLoggedIn()) {
            if (hasRole('admin')) {
                header('Location: /food_ordering/admin/dashboard.php');
            } elseif (hasRole('branch_moderator')) {
                header('Location: /food_ordering/branch/dashboard.php');
            } else {
                header('Location: /food_ordering/user/menu.php');
            }
            exit();
        }
    }
}

// Logout function
if (!function_exists('logout')) {
    function logout() {
        session_destroy();
        header('Location: /food_ordering/login.php');
        exit();
    }
}

} // End include guard
