<?php
echo "Testing config load...<br>";
require_once '/var/www/phod/includes/config.php';
echo "Config loaded successfully<br>";

echo "Testing database connection...<br>";
$db = getDatabase();
echo "Database connected<br>";

echo "Testing basic query...<br>";
$result = $db->query("SELECT COUNT(*) as count FROM roles");
$row = $result->fetch();
echo "Found " . $row['count'] . " roles<br>";

echo "All tests passed!";
?>
