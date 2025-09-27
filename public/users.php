<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions/auth_functions.php';
require_once __DIR__ . '/../includes/functions/database_functions.php';

initSession();
requireLogin();
requireRole('admin'); // Only admins can access

$db = getDatabase();
$messages = getFlashMessages();

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $missing = validateRequired($_POST, ['username', 'password', 'first_name', 'last_name']);
        
        if (empty($missing)) {
            $user_id = createUser($db, $_POST);
            if ($user_id && isset($_POST['roles'])) {
                foreach ($_POST['roles'] as $role_id) {
                    assignUserRole($db, $user_id, $role_id);
                }
                showSuccess("User created successfully!");
                header("Location: /users.php");
                exit;
            }
        } else {
            showError("Missing required fields: " . implode(', ', $missing));
        }
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $current_user = getCurrentUser();
        if ($current_user['user_id'] != $_POST['user_id']) { // Can't delete yourself
            if (deleteUser($db, $_POST['user_id'])) {
                showSuccess("User deleted successfully!");
            } else {
                showError("Failed to delete user.");
            }
        } else {
            showError("You cannot delete your own account.");
        }
        header("Location: /users.php");
        exit;
    }
}

// Handle role assignment/removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_roles') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $user_id = $_POST['user_id'];
        
        // Remove all existing roles
        $db->exec("DELETE FROM user_roles WHERE user_id = $user_id");
        
        // Add selected roles
        if (isset($_POST['roles'])) {
            foreach ($_POST['roles'] as $role_id) {
                assignUserRole($db, $user_id, $role_id);
            }
        }
        
        showSuccess("User roles updated successfully!");
        header("Location: /users.php");
        exit;
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $new_status = $_POST['current_status'] == 1 ? 0 : 1;
        if (updateUserStatus($db, $_POST['user_id'], $new_status)) {
            showSuccess("User status updated successfully!");
        }
        header("Location: /users.php");
        exit;
    }
}

// Get all users with their roles
$users_query = $db->query("
    SELECT u.*, GROUP_CONCAT(r.role_name) as roles
    FROM users u
    LEFT JOIN user_roles ur ON u.user_id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.role_id
    GROUP BY u.user_id
    ORDER BY u.username
");
$users = $users_query->fetchAll();

// Get all roles for the form
$roles = getAllRoles($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>User Management - PhOD</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <div class="header">
        <h1>PhOD - User Management</h1>
        <div class="nav">
            <a href="/dashboard.php">Dashboard</a>
            <a href="/logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($messages['success']): ?>
            <div class="success-message"><?php echo htmlspecialchars($messages['success']); ?></div>
        <?php endif; ?>
        
        <?php if ($messages['error']): ?>
            <div class="error-message"><?php echo htmlspecialchars($messages['error']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Create New User</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="create_user">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label>Assign Roles</label>
                    <div class="checkbox-group">
                        <?php foreach ($roles as $role): ?>
                            <label>
                                <input type="checkbox" name="roles[]" value="<?php echo $role['role_id']; ?>">
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit">Create User</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Existing Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Roles</th>
                        <th>Status</th>
                    </tr>
                </thead>
		<tbody>
		    <?php foreach ($users as $user): ?>
		        <tr>
		            <td><?php echo htmlspecialchars($user['username']); ?></td>
		            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
		            <td>
		                <form method="POST" style="display: inline;">
		                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
		                    <input type="hidden" name="action" value="update_roles">
		                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
		                    <div style="display: flex; gap: 10px; align-items: center;">
		                        <?php 
		                        $user_role_ids = [];
		                        if ($user['roles']) {
		                            $user_role_names = explode(',', $user['roles']);
		                            foreach ($roles as $role) {
		                                if (in_array($role['role_name'], $user_role_names)) {
		                                    $user_role_ids[] = $role['role_id'];
		                                }
		                            }
		                        }
		                        
		                        foreach ($roles as $role): 
		                            $checked = in_array($role['role_id'], $user_role_ids) ? 'checked' : '';
		                        ?>
		                            <label style="font-size: 12px;">
		                                <input type="checkbox" name="roles[]" value="<?php echo $role['role_id']; ?>" <?php echo $checked; ?>>
		                                <?php echo htmlspecialchars($role['role_name']); ?>
		                            </label>
		                        <?php endforeach; ?>
		                        <button type="submit" style="padding: 5px 10px; font-size: 12px;">Update</button>
		                    </div>
		                </form>
		            </td>
		            <td>
		                <form method="POST" style="display: inline;">
		                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
		                    <input type="hidden" name="action" value="toggle_status">
		                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
		                    <input type="hidden" name="current_status" value="<?php echo $user['active']; ?>">
		                    <button type="submit" style="padding: 5px 10px; font-size: 12px; background: <?php echo $user['active'] ? '#dc3545' : '#28a745'; ?>">
		                        <?php echo $user['active'] ? 'Deactivate' : 'Activate'; ?>
		                    </button>
		                </form>
		                
		                <?php if (getCurrentUser()['user_id'] != $user['user_id']): ?>
		                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
		                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
		                    <input type="hidden" name="action" value="delete_user">
		                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
		                    <button type="submit" style="padding: 5px 10px; font-size: 12px; background: #dc3545;">Delete</button>
		                </form>
		                <?php endif; ?>
		            </td>
		        </tr>
		    <?php endforeach; ?>
		</tbody>
            </table>
        </div>
    </div>
   <script src="/js/main.js"></script>
</body>
</html>
