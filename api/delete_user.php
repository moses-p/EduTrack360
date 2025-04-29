<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Security Check: Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    http_response_code(405);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Validate user ID
if (!isset($input['user_id']) || !is_numeric($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    http_response_code(400);
    exit();
}

$user_id = (int)$input['user_id'];

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    http_response_code(403);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        http_response_code(404);
        exit();
    }
    
    // Start transaction to ensure data integrity
    $db->beginTransaction();
    
    // Delete related records based on role
    if ($user['role'] == 'teacher') {
        // Remove teacher assignments
        $stmt = $db->prepare("DELETE FROM teachers WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else if ($user['role'] == 'staff') {
        // Remove staff records
        $stmt = $db->prepare("DELETE FROM staff WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    // Delete user attendance
    $stmt = $db->prepare("DELETE FROM attendance WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete any system logs
    $stmt = $db->prepare("DELETE FROM system_logs WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete sessions
    $stmt = $db->prepare("DELETE FROM sessions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Update tasks (set assigned_to to NULL)
    $stmt = $db->prepare("UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?");
    $stmt->execute([$user_id]);
    
    // Delete the user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Commit transaction
    $db->commit();
    
    // Log the action
    $logStmt = $db->prepare("
        INSERT INTO system_logs (level, message, user_id, source) 
        VALUES ('info', ?, ?, 'user_management')
    ");
    $logMessage = "User ID $user_id was deleted by admin (ID: {$_SESSION['user_id']})";
    $logStmt->execute(['info', $logMessage, $_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Roll back the transaction if an error occurred
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error deleting user: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    http_response_code(500);
}
?> 