<?php
/**
 * PhOD Hydrological Operations Dashboard
 * Database Setup Script
 * 
 * Table Creation structure: Core tables, Junction & Dependant tables, Position tables (CTD), Sampling tables
 */

echo "<h1>PhOD Database Setup</h1>";
echo "<p>Setting up PhOD Hydrological Operations Dashboard database...</p>";

try {
    // Connect to SQLite database
    $db = new PDO('sqlite:/var/www/phod/database/phod.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Connected to database</p>";

    // Enable foreign key support
    $db->exec("PRAGMA foreign_keys = ON");
    
    // Users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        active INTEGER DEFAULT 1 CHECK (active IN (0, 1))
    )");
    
    // Roles table
    $db->exec("CREATE TABLE IF NOT EXISTS roles (
        role_id INTEGER PRIMARY KEY AUTOINCREMENT,
        role_name TEXT NOT NULL UNIQUE CHECK (role_name IN ('admin', 'bottlecop', 'console', 'observer', 'analyst', 'sampler')),
        role_description TEXT
    )");
    
    // Ships table
    $db->exec("CREATE TABLE IF NOT EXISTS ships (
        ship_id INTEGER PRIMARY KEY AUTOINCREMENT,
        ship_name TEXT NOT NULL UNIQUE,
        ship_number INTEGER UNIQUE,
        ship_abbreviation TEXT,
        active INTEGER DEFAULT 1 CHECK (active IN (0, 1))
    )");
    
    // Cruises table
    $db->exec("CREATE TABLE IF NOT EXISTS cruises (
        cruise_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cruise_number INTEGER NOT NULL,
        cruise_name TEXT NOT NULL,
        cruise_abbreviation TEXT,
        active INTEGER DEFAULT 1 CHECK (active IN (0, 1))
    )");
    
    // Stations table
    $db->exec("CREATE TABLE IF NOT EXISTS stations (
        station_id INTEGER PRIMARY KEY AUTOINCREMENT,
        station_number TEXT NOT NULL,
        station_name TEXT NOT NULL,
        station_abbreviation TEXT,
        latitude REAL,
        longitude REAL,
        active INTEGER DEFAULT 1 CHECK (active IN (0, 1))
    )");
    
    // Sensor_Inventory table
    $db->exec("CREATE TABLE IF NOT EXISTS sensor_inventory (
        sensor_id INTEGER PRIMARY KEY AUTOINCREMENT,
        sensor_type TEXT NOT NULL,
        vin_number TEXT UNIQUE,
        status TEXT CHECK (status IN ('operational', 'maintenance', 'broken', 'retired')),
        in_use INTEGER DEFAULT 0 CHECK (in_use IN (0, 1)),
        backup_available INTEGER DEFAULT 0 CHECK (backup_available IN (0, 1)),
        notes TEXT
    )");
    
    // Niskin_Bottles table
    $db->exec("CREATE TABLE IF NOT EXISTS niskin_bottles (
        niskin_id INTEGER PRIMARY KEY AUTOINCREMENT,
        niskin_number INTEGER NOT NULL UNIQUE,
        active INTEGER DEFAULT 1 CHECK (active IN (0, 1)),
        status TEXT CHECK (status IN ('ready', 'deployed', 'maintenance', 'broken')),
        notes TEXT
    )");
    
    // Sample_Types table
    $db->exec("CREATE TABLE IF NOT EXISTS sample_types (
        sample_type_id INTEGER PRIMARY KEY AUTOINCREMENT,
        type_name TEXT NOT NULL UNIQUE,
        abbreviation TEXT,
        description TEXT,
        active INTEGER DEFAULT 1 CHECK (active IN (0, 1))
    )");
    
	// User_Roles junction table
    $db->exec("CREATE TABLE IF NOT EXISTS user_roles (
        user_role_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        role_id INTEGER NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
        UNIQUE(user_id, role_id)
    )");
	
    // CTD_Cast_Log table (central table)
    $db->exec("CREATE TABLE IF NOT EXISTS ctd_cast_log (
        ctd_cast_log_id INTEGER PRIMARY KEY AUTOINCREMENT,
        ship_id INTEGER NOT NULL,
        station_id INTEGER NOT NULL,
        cruise_id INTEGER NOT NULL,
        observer_user_id INTEGER NOT NULL,
        cast_number INTEGER NOT NULL,
        cast_date TEXT DEFAULT (date('now')),
        notes TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ship_id) REFERENCES ships(ship_id),
        FOREIGN KEY (station_id) REFERENCES stations(station_id),
        FOREIGN KEY (cruise_id) REFERENCES cruises(cruise_id),
        FOREIGN KEY (observer_user_id) REFERENCES users(user_id)
    )");
    
    // Cast_Sensors table
    $db->exec("CREATE TABLE IF NOT EXISTS cast_sensors (
        cast_sensor_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL,
        sensor_id INTEGER NOT NULL,
        position_order INTEGER CHECK (position_order > 0),
        sequence_number INTEGER CHECK (sequence_number > 0),
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE,
        FOREIGN KEY (sensor_id) REFERENCES sensor_inventory(sensor_id)
    )");
    
    // Pre_Cast table
    $db->exec("CREATE TABLE IF NOT EXISTS pre_cast (
        pre_cast_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL UNIQUE,
        pre_cast_pressure_test REAL CHECK (pre_cast_pressure_test >= 0),
        pre_cast_datetime TEXT,
        pre_cast_latitude REAL CHECK (pre_cast_latitude BETWEEN -90 AND 90),
        pre_cast_longitude REAL CHECK (pre_cast_longitude BETWEEN -180 AND 180),
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE
    )");
    
    // Beginning_Position table
    $db->exec("CREATE TABLE IF NOT EXISTS beginning_position (
        begin_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL UNIQUE,
        begin_datetime TEXT,
        begin_latitude REAL CHECK (begin_latitude BETWEEN -90 AND 90),
        begin_longitude REAL CHECK (begin_longitude BETWEEN -180 AND 180),
        begin_depth REAL CHECK (begin_depth >= 0),
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE
    )");
    
    // At_Depth_Position table
    $db->exec("CREATE TABLE IF NOT EXISTS at_depth_position (
        at_depth_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL UNIQUE,
        at_depth_datetime TEXT,
        at_depth_latitude REAL CHECK (at_depth_latitude BETWEEN -90 AND 90),
        at_depth_longitude REAL CHECK (at_depth_longitude BETWEEN -180 AND 180),
        at_depth_depth REAL CHECK (at_depth_depth >= 0),
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE
    )");
    
    // Capture_Start table
    $db->exec("CREATE TABLE IF NOT EXISTS capture_start (
        capture_start_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL UNIQUE,
        markscan_start INTEGER CHECK (markscan_start >= 0),
        markscan_start_datetime TEXT,
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE
    )");
    
    // Bottom_Depth_Position table
    $db->exec("CREATE TABLE IF NOT EXISTS bottom_depth_position (
        bottom_position_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL UNIQUE,
        height_above_bottom REAL CHECK (height_above_bottom >= 0),
        max_pressure REAL CHECK (max_pressure >= 0),
        winch_payout REAL CHECK (winch_payout >= 0),
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE
    )");
    
    // Sample_Pressure table
    $db->exec("CREATE TABLE IF NOT EXISTS sample_pressure (
        sample_pressure_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL,
        niskin_id INTEGER NOT NULL,
        sample_pressure_value REAL CHECK (sample_pressure_value >= 0),
        sample_captured INTEGER DEFAULT 0 CHECK (sample_captured IN (0, 1)),
        sample_captured_datetime TEXT,
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE,
        FOREIGN KEY (niskin_id) REFERENCES niskin_bottles(niskin_id)
    )");
    
    // Ending_Position table
    $db->exec("CREATE TABLE IF NOT EXISTS ending_position (
        ending_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL UNIQUE,
        end_datetime TEXT,
        end_latitude REAL CHECK (end_latitude BETWEEN -90 AND 90),
        end_longitude REAL CHECK (end_longitude BETWEEN -180 AND 180),
        end_depth REAL CHECK (end_depth >= 0),
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE
    )");
    
    // On_Deck_Position table
    $db->exec("CREATE TABLE IF NOT EXISTS on_deck_position (
        on_deck_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cast_log_id INTEGER NOT NULL UNIQUE,
        on_deck_datetime TEXT,
        on_deck_latitude REAL CHECK (on_deck_latitude BETWEEN -90 AND 90),
        on_deck_longitude REAL CHECK (on_deck_longitude BETWEEN -180 AND 180),
        notes TEXT,
        FOREIGN KEY (cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE
    )");
    
    // Post_Cast table
    $db->exec("CREATE TABLE IF NOT EXISTS post_cast (
        post_cast_id INTEGER PRIMARY KEY AUTOINCREMENT,
        ctd_cast_log_id INTEGER NOT NULL UNIQUE,
        post_cast_pressure_check REAL CHECK (post_cast_pressure_check >= 0),
        real_time_data_stop INTEGER DEFAULT 0 CHECK (real_time_data_stop IN (0, 1)),
        real_time_data_stop_datetime TEXT,
        deck_unit_off INTEGER DEFAULT 0 CHECK (deck_unit_off IN (0, 1)),
        deck_unit_off_datetime TEXT,
        notes TEXT,
        FOREIGN KEY (ctd_cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE
    )");
    
    // Bottles table
    $db->exec("CREATE TABLE IF NOT EXISTS bottles (
        bottle_id INTEGER PRIMARY KEY AUTOINCREMENT,
        niskin_id INTEGER NOT NULL,
        sample_type_id INTEGER NOT NULL,
        bottle_number INTEGER NOT NULL,
        is_duplicate INTEGER DEFAULT 0 CHECK (is_duplicate IN (0, 1)),
        duplicate_sequence INTEGER CHECK (duplicate_sequence > 0),
        capacity_ml INTEGER CHECK (capacity_ml > 0),
        status TEXT CHECK (status IN ('empty', 'filled', 'processed', 'archived')),
        collected_datetime TEXT,
        FOREIGN KEY (niskin_id) REFERENCES niskin_bottles(niskin_id),
        FOREIGN KEY (sample_type_id) REFERENCES sample_types(sample_type_id)
    )");
    
    // Bottle_Replacements table
    $db->exec("CREATE TABLE IF NOT EXISTS bottle_replacements (
        replacement_id INTEGER PRIMARY KEY AUTOINCREMENT,
        original_bottle_id INTEGER NOT NULL,
        replacement_bottle_id INTEGER NOT NULL,
        replacement_datetime TEXT DEFAULT CURRENT_TIMESTAMP,
        reason TEXT,
        notes TEXT,
        FOREIGN KEY (original_bottle_id) REFERENCES bottles(bottle_id),
        FOREIGN KEY (replacement_bottle_id) REFERENCES bottles(bottle_id)
    )");
    
    // Sampling_Session table
    $db->exec("CREATE TABLE IF NOT EXISTS sampling_session (
        session_id INTEGER PRIMARY KEY AUTOINCREMENT,
        ctd_cast_log_id INTEGER NOT NULL,
        on_deck_position_id INTEGER,
        sampling_start_datetime TEXT,
        sampling_end_datetime TEXT,
        notes TEXT,
        FOREIGN KEY (ctd_cast_log_id) REFERENCES ctd_cast_log(ctd_cast_log_id) ON DELETE CASCADE,
        FOREIGN KEY (on_deck_position_id) REFERENCES on_deck_position(on_deck_id)
    )");
    
    // Sample_Timing table
    $db->exec("CREATE TABLE IF NOT EXISTS sample_timing (
        timing_id INTEGER PRIMARY KEY AUTOINCREMENT,
        sample_type_id INTEGER NOT NULL,
        session_id INTEGER NOT NULL,
        set_by_user_id INTEGER NOT NULL,
        time_limit_hours INTEGER CHECK (time_limit_hours > 0),
        deadline_datetime TEXT,
        set_datetime TEXT DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        FOREIGN KEY (sample_type_id) REFERENCES sample_types(sample_type_id),
        FOREIGN KEY (session_id) REFERENCES sampling_session(session_id) ON DELETE CASCADE,
        FOREIGN KEY (set_by_user_id) REFERENCES users(user_id)
    )");

    echo "<p style='color: green;'>✓ All tables created successfully!</p>";
    
    // Insert default roles
    $roles = [
        ['admin', 'System administrator with full access'],
        ['bottlecop', 'Bottle operations coordinator'],
        ['console', 'Observer/Console operator'],
        ['analyst', 'Sample analyzer'],
        ['sampler', 'Sample collector']
    ];
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO roles (role_name, role_description) VALUES (?, ?)");
    foreach ($roles as $role) {
        $stmt->execute($role);
    }
    
    echo "<p style='color: green;'>✓ Default roles inserted</p>";
    
    // Insert sample types
    $sample_types = [
	['Oxygen', 'O2', 'Dissolved oxygen samples'],
	['Oxygen Duplicate', 'O2-DUP', 'Duplicate dissolved oxygen samples'],
	['Total Alkalinity', 'TA', 'Total alkalinity measurement'],
	['Total Alkalinity Duplicate', 'TA-DUP', 'Duplicate total alkalinity samples'],
	['Dissolved Inorganic Carbon', 'DIC', 'Dissolved inorganic carbon measurement'], 
	['Dissolved Inorganic Carbon Duplicate', 'DIC-DUP', 'Duplicate dissolved inorganic carbon samples'],
	['pH', 'PH', 'Potential of hydrogen measurement'],
	['pH Duplicate', 'PH-DUP', 'Duplicate pH samples'],
	['Nutrients', 'NUTS', 'Nutrient analysis samples'],
	['Nutrients Duplicate', 'NUTS-DUP', 'Duplicate nutrient samples'],
	['Salinity', 'SAL', 'Salinity measurement samples'],
	['Salinity Duplicate', 'SAL-DUP', 'Duplicate Salinity samples'],
	['MAC Samples', 'MAC', 'MAC analysis samples'],
	['Reference Water', 'REF', 'Reference water samples']
    ];
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO sample_types (type_name, abbreviation, description) VALUES (?, ?, ?)");
    foreach ($sample_types as $type) {
        $stmt->execute($type);
    }
    
    echo "<p style='color: green;'>✓ Default sample types inserted</p>";
   
    // Show created tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll();
    echo "<h3>Created Tables (" . count($tables) . "):</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table['name'] . "</li>";
    }
    echo "</ul>";
    
    // Show database info
    $db_size = filesize('/var/www/phod/database/phod.db');
    echo "<p><strong>Database file:</strong> /var/www/phod/database/phod.db</p>";
    echo "<p><strong>Database size:</strong> " . number_format($db_size) . " bytes</p>";
    
    echo "<h2 style='color: green;'>PhOD Database Setup Complete!</h2>";
    echo "<p>Your database is ready for use. You can now:</p>";
    echo "<ul>";
    echo "<li>Create user accounts and assign roles</li>";
    echo "<li>Add ships, cruises, and stations</li>";
    echo "<li>Register sensor inventory</li>";
    echo "<li>Start logging CTD casts and sampling sessions</li>";
    echo "</ul>";

} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>
