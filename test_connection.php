<?php
require_once 'includes/config.php';
require_once 'includes/functions/database_functions.php';

echo "<h1>PhOD Connection Test</h1>";

try {
    // Test database connection
    $db = getDatabase();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test basic queries
    $roles = getAllRoles($db);
    echo "<p style='color: green;'>✓ Retrieved " . count($roles) . " roles</p>";
    
    $sample_types = getAllSampleTypes($db);
    echo "<p style='color: green;'>✓ Retrieved " . count($sample_types) . " sample types</p>";
    
    // Test database stats
    $stats = getDatabaseStats($db);
    echo "<h3>Database Statistics:</h3>";
    foreach ($stats as $table => $count) {
        echo "<p>$table: $count records</p>";
    }
    
    echo "<h2 style='color: green;'>All systems working!</h2>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
