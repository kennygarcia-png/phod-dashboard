<?php
/**
 * PhOD Hydrological Operations Dashboard
 * Configuration File
 */

// Database Configuration
define('DB_PATH', '/var/www/phod/database/phod.db');
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Application Configuration
define('APP_NAME', 'PhOD Dashboard');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'UTC'); // Important for ship operations

// Session Configuration
define('SESSION_TIMEOUT', 3600 * 8); // 8 hours for long operations

// File Upload Configuration (if needed later)
define('UPLOAD_DIR', '/var/www/phod/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Set timezone
date_default_timezone_set(TIMEZONE);

/**
 * Get database connection
 * @return PDO Database connection
 */
function getDatabase() {
    try {
        $db = new PDO('sqlite:' . DB_PATH, null, null, DB_OPTIONS);
        // Enable foreign key support
        $db->exec("PRAGMA foreign_keys = ON");
        return $db;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Check logs for details.");
    }
}

/**
 * Initialize session with security settings
 */
function initSession() {
    if (session_status() == PHP_SESSION_NONE) {
        // Security settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
        
        session_start();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['last_activity'] = time();
    }
    return true;
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Get current user info
 * @return array|null User information or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'roles' => $_SESSION['roles'] ?? []
    ];
}

/**
 * Log user activity
 * @param string $action Action performed
 * @param string $details Additional details
 */
function logActivity($action, $details = '') {
    $user = getCurrentUser();
    $user_id = $user ? $user['user_id'] : null;
    $username = $user ? $user['username'] : 'anonymous';
    
    $log_entry = date('Y-m-d H:i:s') . " | User: $username | Action: $action | Details: $details" . PHP_EOL;
    error_log($log_entry, 3, '/var/www/phod/logs/activity.log');
}

/**
 * Sanitize input data
 * @param mixed $data Input data
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields
 * @param array $data Input data
 * @param array $required Required field names
 * @return array Array of missing fields
 */
function validateRequired($data, $required) {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to another page
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Display error message
 * @param string $message Error message
 */
function showError($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Display success message
 * @param string $message Success message
 */
function showSuccess($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Get and clear flash messages
 * @return array Array with 'error' and 'success' keys
 */
function getFlashMessages() {
    $messages = [
        'error' => $_SESSION['error_message'] ?? null,
        'success' => $_SESSION['success_message'] ?? null
    ];
    
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    return $messages;
}
?>
