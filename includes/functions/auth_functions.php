<?php
/**
 * Authentication Functions for PhOD
 * Simple username/password authentication
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . 'database_functions.php';

/**
 * Authenticate user login
 * @param PDO $db Database connection
 * @param string $username Username
 * @param string $password Plain text password
 * @return array|false User data if successful, false if failed
 */
function authenticateUser($db, $username, $password) {
    // Get user from database
    $user = getUserByUsername($db, $username);
    
    if (!$user) {
        // User not found
        logActivity("Failed login attempt", "Username: $username (user not found)");
        return false;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Wrong password
        logActivity("Failed login attempt", "Username: $username (wrong password)");
        return false;
    }
    
    // Check if user is active
    if ($user['active'] != 1) {
        logActivity("Failed login attempt", "Username: $username (account inactive)");
        return false;
    }
    
    // Successful login
    logActivity("Successful login", "Username: $username");
    return $user;
}

/**
 * Login user and start session
 * @param array $user User data from database
 * @return bool Success status
 */
function loginUser($user) {
    // Start session if not already started
    if (!initSession()) {
        return false;
    }
    
    // Get user roles
    $db = getDatabase();
    $roles = getUserRoles($db, $user['user_id']);
    
    // Store user data in session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['roles'] = $roles;
    $_SESSION['login_time'] = time();
    
    return true;
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    $user = getCurrentUser();
    if ($user) {
        logActivity("User logout", "Username: " . $user['username']);
    }
    
    // Clear session data
    session_unset();
    session_destroy();
    
    // Start new session for flash messages
    session_start();
}

/**
 * Check if user has specific role
 * @param string $role_name Role name to check
 * @return bool True if user has role, false otherwise
 */
function userHasRole($role_name) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    return in_array($role_name, $user['roles']);
}

/**
 * Require login - redirect if not logged in
 * @param string $redirect_url URL to redirect to after login
 */
function requireLogin($redirect_url = '/') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        showError("Please log in to access this page.");
        redirect('/login.php');
    }
}

/**
 * Require specific role - show error if user doesn't have it
 * @param string $role_name Required role
 */
function requireRole($role_name) {
    requireLogin();
    
    if (!userHasRole($role_name)) {
        showError("Access denied. You need '$role_name' role to access this page.");
        redirect('/dashboard.php');
    }
}

/**
 * Process login form
 * @param array $post_data POST data from login form
 * @return bool True if login successful, false otherwise
 */
function processLogin($post_data) {
    // Validate CSRF token
    if (!isset($post_data['csrf_token']) || !verifyCSRFToken($post_data['csrf_token'])) {
        showError("Security token mismatch. Please try again.");
        return false;
    }
    
    // Sanitize input
    $username = sanitizeInput($post_data['username'] ?? '');
    $password = $post_data['password'] ?? '';
    
    // Validate required fields
    if (empty($username) || empty($password)) {
        showError("Please enter both username and password.");
        return false;
    }
    
    // Attempt authentication
    $db = getDatabase();
    $user = authenticateUser($db, $username, $password);
    
    if ($user) {
        // Login successful
        if (loginUser($user)) {
            showSuccess("Welcome back, " . $user['first_name'] . "!");
            
            // Redirect to original page or dashboard
            $redirect_url = $_SESSION['redirect_after_login'] ?? '/dashboard.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect_url);
            return true;
        } else {
            showError("Login failed. Please try again.");
            return false;
        }
    } else {
        // Login failed
        showError("Invalid username or password.");
        return false;
    }
}

/**
 * Create default admin user if none exists
 * @param PDO $db Database connection
 * @return bool True if admin created or already exists
 */
function createDefaultAdmin($db) {
    // Check if any admin users exist
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM users u 
        JOIN user_roles ur ON u.user_id = ur.user_id 
        JOIN roles r ON ur.role_id = r.role_id 
        WHERE r.role_name = 'admin' AND u.active = 1
    ");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();
    
    if ($admin_count > 0) {
        return true; // Admin already exists
    }
    
    // Create default admin user
    $admin_data = [
        'username' => 'admin',
        'password' => 'admin123', // Change this in production!
        'first_name' => 'System',
        'last_name' => 'Administrator'
    ];
    
    $user_id = createUser($db, $admin_data);
    if (!$user_id) {
        return false;
    }
    
    // Get admin role ID
    $stmt = $db->prepare("SELECT role_id FROM roles WHERE role_name = 'admin'");
    $stmt->execute();
    $admin_role_id = $stmt->fetchColumn();
    
    if ($admin_role_id) {
        assignUserRole($db, $user_id, $admin_role_id);
        logActivity("Default admin created", "Username: admin");
        return true;
    }
    
    return false;
}

/**
 * Change user password
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @param string $new_password New password
 * @return bool Success status
 */
function changePassword($db, $user_id, $new_password) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $result = $stmt->execute([$hashed_password, $user_id]);
    
    if ($result) {
        logActivity("Password changed", "User ID: $user_id");
    }
    
    return $result;
}
?>
