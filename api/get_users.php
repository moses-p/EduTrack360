<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
// require_once '../models/User.php'; // Assuming a User model

// Security Check: Ensure user is logged in (maybe admin only?)
if (!isset($_SESSION['user_id'])) { // Basic check, refine permissions as needed
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit();
}

// Filter by role if provided
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : null;

try {
    $database = new Database();
    $db = $database->getConnection();

    // Basic query without a model
    $query = "SELECT id, full_name, username, role, email FROM users";
    $params = [];
    if ($role_filter !== null) {
        // Basic validation for allowed roles
        $allowed_roles = ['admin', 'teacher', 'parent', 'staff', 'ceo']; 
        if (in_array($role_filter, $allowed_roles)) {
            $query .= " WHERE role = ?";
            $params[] = $role_filter;
        } else {
             // Handle invalid role filter - maybe return empty or error?
             echo json_encode(['success' => false, 'message' => 'Invalid role specified']);
             http_response_code(400);
             exit();
        }
    }
    $query .= " ORDER BY full_name ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $users ?: []
    ]); // Return empty array if no users found

} catch (Exception $e) {
    error_log("API Error (get_users): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not retrieve users']);
    http_response_code(500);
}
?> 