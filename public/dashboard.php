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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - PhOD</title>
    <link rel="stylesheet" href="/css/main.css">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- iOS Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PhOD">
    <link rel="apple-touch-icon" href="/assets/icon-192.png">
    
    <!-- Theme color -->
    <meta name="theme-color" content="#667eea">
</head>
<body>
    <div class="header">
    <h1>
        PhOD Dashboard
        <button class="nav-toggle">☰</button>
    </h1>
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

	   <button id="install-button" style="display: none; margin-top: 15px;">
           	📱 Install PhOD App
           </button>
        </div>
        
        <h2>System Overview</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['users']; ?></h3>
                <p>Active Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['niskin_bottles']; ?></h3>
                <p>Niskin Bottles</p>
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
	    <?php if (userHasRole('admin')): ?>
	        <!-- Admin sees management options -->
	        <a href="/users.php" class="action-card">
	            <h3>User Management</h3>
	            <p>Manage team members and roles</p>
	        </a>
	        <a href="/ships.php" class="action-card">
	            <h3>Ships Management</h3>
	            <p>Manage vessel information</p>
	        </a>
	        <a href="/stations.php" class="action-card">
	            <h3>Stations Management</h3>
	            <p>Manage sampling stations</p>
	        </a>
	        <a href="/cruises.php" class="action-card">
	            <h3>Cruises Management</h3>
	            <p>Manage cruise operations</p>
	        </a>
	        <a href="/niskin.php" class="action-card">
	            <h3>Niskin Management</h3>
	            <p>Manage Niskin bottle inventory</p>
	        </a>
	        <a href="/sensors.php" class="action-card">
	            <h3>Sensor Inventory</h3>
	            <p>Manage equipment and sensors</p>
	        </a>
	        <a href="/sample_types.php" class="action-card">
	            <h3>Sample Types</h3>
	            <p>Manage sample type configurations</p>
	        </a>
	        <a href="/view_operations.php" class="action-card">
	            <h3>View Operations</h3>
	            <p>Monitor all CTD casts and sampling</p>
	        </a>

	    <?php elseif (userHasRole('console') || userHasRole('observer')): ?>
	        <!-- Observer/Console operations -->
	        <a href="/ctd_cast.php" class="action-card">
	            <h3>New CTD Cast</h3>
	            <p>Start logging a new CTD cast</p>
	        </a>
	        <a href="/stations.php" class="action-card">
	            <h3>Stations Management</h3>
	            <p>Manage sampling stations</p>
	        </a>
	        <a href="/ships.php" class="action-card">
	            <h3>Ships Management</h3>
	            <p>Manage vessel information</p>
	        </a>
	        <a href="/sensors.php" class="action-card">
	            <h3>Sensor Inventory</h3>
	            <p>Manage equipment and sensors</p>
	        </a>
	        <a href="/niskin.php" class="action-card">
	            <h3>Niskin Bottles</h3>
	            <p>Manage bottle inventory</p>
	        </a>

	    <?php elseif (userHasRole('bottlecop')): ?>
	        <!-- Bottlecop - sampling operations only -->
	        <a href="/sampling.php" class="action-card">
	            <h3>Sample Management</h3>
	            <p>Track and manage water samples</p>
	        </a>
	        <a href="/bottles.php" class="action-card">
	            <h3>Bottle Operations</h3>
	            <p>Manage bottle assignments</p>
	        </a>
	        <a href="/view_casts.php" class="action-card">
	            <h3>View CTD Casts</h3>
	            <p>View cast information (read-only)</p>
	        </a>

	    <?php elseif (userHasRole('analyst') || userHasRole('sampler')): ?>
	        <!-- Analyst/Sampler - view and download only -->
	        <a href="/view_data.php" class="action-card">
	            <h3>View Data</h3>
	            <p>Browse all collected data</p>
	        </a>
	        <a href="/reports.php" class="action-card">
	            <h3>Generate Reports</h3>
	            <p>Create and download reports</p>
	        </a>
	        <a href="/export.php" class="action-card">
	            <h3>Export Data</h3>
	            <p>Download data in various formats</p>
	        </a>
	    <?php endif; ?>
	</div>

	<script src="/js/main.js"></script>
	<script src="/js/install-prompt.js"></script>
</body>
</html>

