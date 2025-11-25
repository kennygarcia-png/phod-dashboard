<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions/auth_functions.php';

// Initialize session
initSession();

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    processLogin($_POST);
}

// Get flash messages
$messages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - PhOD Dashboard</title>
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
    
    <style>
        /* Login-specific styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>	PhOD Dashboard</h1>
        <p class="subtitle">Hydrological Operations Dashboard</p>
        
        <?php if ($messages['error']): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($messages['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($messages['success']): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($messages['success']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Sign In</button>
        </form>
        
        <div class="system-info">
            PhOD v<?php echo APP_VERSION; ?> | <?php echo date('Y-m-d H:i:s'); ?> UTC
        </div>
    </div>
</body>
</html>
