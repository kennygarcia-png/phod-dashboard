<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions/auth_functions.php';

initSession();
logoutUser();

showSuccess("You have been logged out successfully.");
redirect('/login.php');
?>
