<?php
/**
 * PhOD Database Functions
 * All database operations for the PhOD system
 */

require_once __DIR__ . '/../config.php';

// ============================================================================
// USER FUNCTIONS
// ============================================================================

/**
 * Get user by username
 * @param PDO $db Database connection
 * @param string $username Username
 * @return array|false User data or false if not found
 */
function getUserByUsername($db, $username) {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Get user by ID
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @return array|false User data or false if not found
 */
function getUserById($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND active = 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get user roles
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @return array Array of role names
 */
function getUserRoles($db, $user_id) {
    $stmt = $db->prepare("
        SELECT r.role_name 
        FROM user_roles ur 
        JOIN roles r ON ur.role_id = r.role_id 
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Create new user
 * @param PDO $db Database connection
 * @param array $data User data
 * @return int|false New user ID or false on failure
 */
function createUser($db, $data) {
    $stmt = $db->prepare("
        INSERT INTO users (username, password, first_name, last_name) 
        VALUES (?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['username'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['first_name'],
        $data['last_name']
    ]);
    
    return $result ? $db->lastInsertId() : false;
}

/**
 * Update user status (activate/deactivate)
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @param int $active Active status (0 or 1)
 * @return bool Success status
 */
function updateUserStatus($db, $user_id, $active) {
    $stmt = $db->prepare("UPDATE users SET active = ? WHERE user_id = ?");
    return $stmt->execute([$active, $user_id]);
}

/**
 * Remove role from user
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @param int $role_id Role ID
 * @return bool Success status
 */
function removeUserRole($db, $user_id, $role_id) {
    $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = ?");
    return $stmt->execute([$user_id, $role_id]);
}

/**
 * Delete user (also removes role assignments due to CASCADE)
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @return bool Success status
 */
function deleteUser($db, $user_id) {
    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * Assign role to user
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @param int $role_id Role ID
 * @return bool Success status
 */
function assignUserRole($db, $user_id, $role_id) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
    return $stmt->execute([$user_id, $role_id]);
}

// ============================================================================
// DROPDOWN DATA FUNCTIONS
// ============================================================================

/**
 * Get all active roles
 * @param PDO $db Database connection
 * @return array Array of roles
 */
function getAllRoles($db) {
    $stmt = $db->query("SELECT role_id, role_name, role_description FROM roles ORDER BY role_name");
    return $stmt->fetchAll();
}

/**
 * Get all active ships
 * @param PDO $db Database connection
 * @return array Array of ships
 */
function getAllShips($db) {
    $stmt = $db->query("SELECT ship_id, ship_name, ship_abbreviation FROM ships WHERE active = 1 ORDER BY ship_name");
    return $stmt->fetchAll();
}

/**
 * Get all active cruises
 * @param PDO $db Database connection
 * @return array Array of cruises
 */
function getAllCruises($db) {
    $stmt = $db->query("SELECT cruise_id, cruise_name, cruise_abbreviation FROM cruises WHERE active = 1 ORDER BY cruise_name");
    return $stmt->fetchAll();
}

/**
 * Get all active stations for specific cruise
 * @param PDO $db Database connection
 * @param int $cruise_id Cruise ID
 * @return array Array of stations
 */
function getStationsByCruise($db, $cruise_id) {
    $stmt = $db->prepare("
        SELECT station_id, station_name, station_abbreviation,
               station_number, latitude, longitude
	FROM stations
	WHERE cruise_id = ? AND active = 1
	ORDER BY station_number
    ");
    $stmt->execute([$cruise_id]);
    return $stmt->fetchAll();
}

/**
 * Get all stations (for admin view)
 * @param PDO $db Database connection
 * @return array Array of stations with cruise info
 */
function getAllStations($db) {
    $stmt = $db->query("
        SELECT s.*, c.cruise_name 
        FROM stations s
        JOIN cruises c ON s.cruise_id = c.cruise_id
        WHERE s.active = 1 
        ORDER BY c.cruise_name, s.station_number
    ");
    return $stmt->fetchAll();
}

/**
 * Create new station
 * @param PDO $db Database connection
 * @param array $data Station data (must include cruise_id)
 * @return int|false New station ID or false on failure
 */
function createStation($db, $data) {
    $stmt = $db->prepare("
        INSERT INTO stations 
        (cruise_id, station_number, station_name, station_abbreviation, latitude, longitude)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([
        $data['cruise_id'],
        $data['station_number'],
        $data['station_name'],
        $data['station_abbreviation'] ?? null,
        $data['latitude'] ?? null,
        $data['longitude'] ?? null
    ]);
    return $result ? $db->lastInsertId() : false;
}

/**
 * Get target depths for a station
 * @param PDO $db Database connection
 * @param int $station_id Station ID
 * @return array Target depths for the station
 */
function getStationTargetDepths($db, $station_id) {
    $stmt = $db->prepare("
        SELECT target_depth_id, target_depth_pressure, sequence_order, 
               niskin_position, notes
        FROM station_target_depths
        WHERE station_id = ?
        ORDER BY sequence_order
    ");
    $stmt->execute([$station_id]);
    return $stmt->fetchAll();
}

/**
 * Add target depth for station
 * @param PDO $db Database connection
 * @param array $data Target depth data
 * @return int|false New target depth ID or false
 */
function addStationTargetDepth($db, $data) {
    $stmt = $db->prepare("
        INSERT INTO station_target_depths
        (station_id, target_depth_pressure, sequence_order, niskin_position, notes)
        VALUES (?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $data['station_id'],
        $data['target_depth_pressure'],
        $data['sequence_order'],
        $data['niskin_position'] ?? null,
        $data['notes'] ?? ''
    ]);

    return $result ? $db->lastInsertId() : false;
}

/**
 * Record actual sample capture with target reference
 * @param PDO $db Database connection
 * @param int $cast_id Cast ID
 * @param int $niskin_id Niskin bottle ID
 * @param int $target_depth_id Target depth ID
 * @param array $data Capture data
 * @return bool Success status
 */
function recordSampleCapture($db, $cast_id, $niskin_id, $target_depth_id, $data) {
    $stmt = $db->prepare("
        INSERT INTO sample_pressure
        (cast_log_id, niskin_id, target_depth_id,
         sample_pressure_value, sample_captured,
         sample_captured_datetime, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    return $stmt->execute([
        $cast_id,
        $niskin_id,
        $target_depth_id,
        $data['actual_pressure'] ?? null,
        $data['captured'] ?? 0,
        $data['datetime'] ?? date('Y-m-d H:i:s'),
        $data['notes'] ?? ''
    ]);
}

/**
 * Get capture summary for a cast
 * @param PDO $db Database connection
 * @param int $cast_id Cast ID
 * @return array Summary with target vs actual depths
 */
function getCastCaptureSummary($db, $cast_id) {
    $stmt = $db->prepare("
        SELECT
            sp.sample_pressure_id,
            nb.niskin_number,
            std.target_depth_pressure,
            sp.sample_pressure_value as actual_depth_pressure,
            (sp.sample_pressure_value - std.target_depth_pressure) as depth_variance,
            sp.sample_captured,
            sp.sample_captured_datetime,
            std.sequence_order
        FROM sample_pressure sp
        JOIN niskin_bottles nb ON sp.niskin_id = nb.niskin_id
        LEFT JOIN station_target_depths std ON sp.target_depth_id = std.target_depth_id
        WHERE sp.cast_log_id = ?
        ORDER BY std.sequence_order, nb.niskin_number
    ");
    $stmt->execute([$cast_id]);
    return $stmt->fetchAll();
}

/**
 * Get all active sample types
 * @param PDO $db Database connection
 * @return array Array of sample types
 */
function getAllSampleTypes($db) {
    $stmt = $db->query("SELECT sample_type_id, type_name, abbreviation FROM sample_types WHERE active = 1 ORDER BY type_name");
    return $stmt->fetchAll();
}

/**
 * Get all active niskin bottles
 * @param PDO $db Database connection
 * @return array Array of niskin bottles
 */
function getAllNiskinBottles($db) {
    $stmt = $db->query("SELECT niskin_id, niskin_number, status FROM niskin_bottles WHERE active = 1 ORDER BY niskin_number");
    return $stmt->fetchAll();
}

/**
 * Get available sensors
 * @param PDO $db Database connection
 * @return array Array of sensors
 */
function getAvailableSensors($db) {
    $stmt = $db->query("
        SELECT sensor_id, sensor_type, vin_number, status 
        FROM sensor_inventory 
        WHERE status = 'operational' 
        ORDER BY sensor_type
    ");
    return $stmt->fetchAll();
}

// ============================================================================
// CTD CAST FUNCTIONS
// ============================================================================

/**
 * Create new CTD cast
 * @param PDO $db Database connection
 * @param array $data Cast data
 * @return int|false New cast ID or false on failure
 */
function createCTDCast($db, $data) {
    $stmt = $db->prepare("
        INSERT INTO ctd_cast_log (ship_id, station_id, cruise_id, observer_user_id, cast_number, notes) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['ship_id'],
        $data['station_id'],
        $data['cruise_id'],
        $data['observer_user_id'],
        $data['cast_number'],
        $data['notes'] ?? ''
    ]);
    
    return $result ? $db->lastInsertId() : false;
}

/**
 * Get CTD cast by ID
 * @param PDO $db Database connection
 * @param int $cast_id Cast ID
 * @return array|false Cast data or false if not found
 */
function getCTDCastById($db, $cast_id) {
    $stmt = $db->prepare("
        SELECT 
            c.*,
            s.ship_name,
            st.station_name,
            cr.cruise_name,
            u.first_name || ' ' || u.last_name as observer_name
        FROM ctd_cast_log c
        JOIN ships s ON c.ship_id = s.ship_id
        JOIN stations st ON c.station_id = st.station_id
        JOIN cruises cr ON c.cruise_id = cr.cruise_id
        JOIN users u ON c.observer_user_id = u.user_id
        WHERE c.ctd_cast_log_id = ?
    ");
    $stmt->execute([$cast_id]);
    return $stmt->fetch();
}

/**
 * Get recent CTD casts
 * @param PDO $db Database connection
 * @param int $limit Number of casts to retrieve
 * @return array Array of casts
 */
function getRecentCTDCasts($db, $limit = 20) {
    $stmt = $db->prepare("
        SELECT 
            c.ctd_cast_log_id,
            c.cast_number,
            c.cast_date,
            s.ship_name,
            st.station_name,
            cr.cruise_name,
            u.first_name || ' ' || u.last_name as observer_name
        FROM ctd_cast_log c
        JOIN ships s ON c.ship_id = s.ship_id
        JOIN stations st ON c.station_id = st.station_id
        JOIN cruises cr ON c.cruise_id = cr.cruise_id
        JOIN users u ON c.observer_user_id = u.user_id
        ORDER BY c.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// ============================================================================
// POSITION FUNCTIONS
// ============================================================================

/**
 * Save pre-cast data
 * @param PDO $db Database connection
 * @param int $cast_id Cast ID
 * @param array $data Position data
 * @return bool Success status
 */
function savePreCastData($db, $cast_id, $data) {
    $stmt = $db->prepare("
        INSERT OR REPLACE INTO pre_cast 
        (cast_log_id, pre_cast_pressure_test, pre_cast_datetime, pre_cast_latitude, pre_cast_longitude, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $cast_id,
        $data['pressure_test'] ?? null,
        $data['datetime'] ?? date('Y-m-d H:i:s'),
        $data['latitude'] ?? null,
        $data['longitude'] ?? null,
        $data['notes'] ?? ''
    ]);
}

/**
 * Save beginning position
 * @param PDO $db Database connection
 * @param int $cast_id Cast ID
 * @param array $data Position data
 * @return bool Success status
 */
function saveBeginningPosition($db, $cast_id, $data) {
    $stmt = $db->prepare("
        INSERT OR REPLACE INTO beginning_position 
        (cast_log_id, begin_datetime, begin_latitude, begin_longitude, begin_depth, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $cast_id,
        $data['datetime'] ?? date('Y-m-d H:i:s'),
        $data['latitude'] ?? null,
        $data['longitude'] ?? null,
        $data['depth'] ?? null,
        $data['notes'] ?? ''
    ]);
}

/**
 * Save sample pressure data
 * @param PDO $db Database connection
 * @param int $cast_id Cast ID
 * @param int $niskin_id Niskin bottle ID
 * @param array $data Sample data
 * @return bool Success status
 */
function saveSamplePressure($db, $cast_id, $niskin_id, $data) {
    $stmt = $db->prepare("
        INSERT INTO sample_pressure 
        (cast_log_id, niskin_id, sample_pressure_value, sample_captured, sample_captured_datetime, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $cast_id,
        $niskin_id,
        $data['pressure_value'] ?? null,
        $data['captured'] ?? 0,
        $data['datetime'] ?? date('Y-m-d H:i:s'),
        $data['notes'] ?? ''
    ]);
}

// ============================================================================
// BOTTLE AND SAMPLING FUNCTIONS
// ============================================================================

/**
 * Create bottle entry
 * @param PDO $db Database connection
 * @param array $data Bottle data
 * @return int|false New bottle ID or false on failure
 */
function createBottle($db, $data) {
    $stmt = $db->prepare("
        INSERT INTO bottles 
        (niskin_id, sample_type_id, bottle_number, is_duplicate, duplicate_sequence, capacity_ml, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['niskin_id'],
        $data['sample_type_id'],
        $data['bottle_number'],
        $data['is_duplicate'] ?? 0,
        $data['duplicate_sequence'] ?? null,
        $data['capacity_ml'] ?? null,
        $data['status'] ?? 'empty'
    ]);
    
    return $result ? $db->lastInsertId() : false;
}

/**
 * Update bottle status
 * @param PDO $db Database connection
 * @param int $bottle_id Bottle ID
 * @param string $status New status
 * @return bool Success status
 */
function updateBottleStatus($db, $bottle_id, $status) {
    $stmt = $db->prepare("UPDATE bottles SET status = ? WHERE bottle_id = ?");
    return $stmt->execute([$status, $bottle_id]);
}

/**
 * Get bottles for cast
 * @param PDO $db Database connection
 * @param int $cast_id Cast ID
 * @return array Array of bottles
 */
function getBottlesForCast($db, $cast_id) {
    $stmt = $db->prepare("
        SELECT 
            b.*,
            nb.niskin_number,
            st.type_name,
            st.abbreviation
        FROM bottles b
        JOIN niskin_bottles nb ON b.niskin_id = nb.niskin_id
        JOIN sample_types st ON b.sample_type_id = st.sample_type_id
        JOIN sample_pressure sp ON b.niskin_id = sp.niskin_id
        WHERE sp.cast_log_id = ?
        ORDER BY nb.niskin_number, b.bottle_number
    ");
    $stmt->execute([$cast_id]);
    return $stmt->fetchAll();
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Search function for any table
 * @param PDO $db Database connection
 * @param string $table Table name
 * @param string $search_field Field to search
 * @param string $search_term Search term
 * @param int $limit Limit results
 * @return array Search results
 */
function searchTable($db, $table, $search_field, $search_term, $limit = 20) {
    // Basic security check for table and field names
    $allowed_tables = ['users', 'ships', 'cruises', 'stations', 'ctd_cast_log'];
    if (!in_array($table, $allowed_tables)) {
        return [];
    }
    
    $sql = "SELECT * FROM $table WHERE $search_field LIKE ? LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(["%$search_term%", $limit]);
    return $stmt->fetchAll();
}

/**
 * Get database statistics
 * @param PDO $db Database connection
 * @return array Database stats
 */
function getDatabaseStats($db) {
    $stats = [];
    
    // Get table counts
    $tables = ['users', 'niskin_bottles', 'bottles', 'ships', 'cruises', 'stations'];
    foreach ($tables as $table) {
	try { 
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $stats[$table] = $stmt->fetchColumn();
    } catch (PD0Exception $e) {
	$stats[$table] = 0;
    }
  }
    return $stats;
}
?>
