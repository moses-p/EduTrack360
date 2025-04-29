<?php
/**
 * Database connection for API endpoints
 */

// Include the Database class
require_once __DIR__ . '/../config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// If connection failed, exit
if (!$conn) {
    error_log("Failed to connect to database");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection error'
    ]);
    exit;
}
?> 