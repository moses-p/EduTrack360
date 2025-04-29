<?php
/**
 * Database connection using mysqli
 */

// Database credentials
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'edutrack360';

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection error'
    ]);
    exit;
}
?> 