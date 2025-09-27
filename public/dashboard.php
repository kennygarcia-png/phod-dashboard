<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions/auth_functions.php';
require_once __DIR__ . '/../includes/functions/database_functions.php';

// Initialize session and require login
initSession();
requireLogin();

// Get current user info
$user = getCurrentUser();

// Get flash messages
$messages = getFlashMessages();

// Get database statistics
$db = getDatabase();
$stats = getDatabaseStats($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PhOD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: #667eea;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .action-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .action-card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .action-card p {
            color: #666;
            font-size: 14px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .roles-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PhOD Dashboard</h1>
        <div class="user-info">
            <div>
                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                <br>
                <small>
                    <?php foreach ($user['roles'] as $role): ?>
                        <span class="roles-badge"><?php echo htmlspecialchars($role); ?></span>
                    <?php endforeach; ?>
                </small>
            </div>
            <a href="/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($messages['success']): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($messages['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
            <p>PhOD Hydrological Operations Dashboard - System Status: Online</p>
        </div>
        
        <h2>System Overview</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['users']; ?></h3>
                <p>Active Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['ctd_cast_log']; ?></h3>
                <p>CTD Casts</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['bottles']; ?></h3>
                <p>Sample Bottles</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['ships']; ?></h3>
                <p>Ships</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['cruises']; ?></h3>
                <p>Cruises</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['stations']; ?></h3>
                <p>Stations</p>
            </div>
        </div>
        
        <h2 style="margin-top: 40px;">Quick Actions</h2>
        <div class="quick-actions">
            <a href="#" class="action-card">
                <h3>New CTD Cast</h3>
                <p>Start logging a new CTD cast operation</p>
            </a>
            <a href="#" class="action-card">
                <h3>Sample Management</h3>
                <p>Track and manage water samples</p>
            </a>
            <a href="/users.php" class="action-card">
                <h3>User Management</h3>
                <p>Manage team members and roles</p>
            </a>
            <a href="#" class="action-card">
                <h3>System Settings</h3>
                <p>Configure ships, stations, and equipment</p>
            </a>
        </div>
    </div>
</body>
</html>
