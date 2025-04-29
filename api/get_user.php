<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Security Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit();
}

// Validate user ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    http_response_code(400);
    exit();
}

$user_id = (int)$_GET['id'];

// Check if the requesting user is allowed to access this user info
// Only admins can access any user, others can only access their own data
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    http_response_code(403);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Basic query to get user data (excluding password)
    $query = "SELECT id, username, role, full_name, email, phone, created_at FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        http_response_code(404);
        exit();
    }
    
    // Get additional role-specific data
    $role_data = [];
    
    switch ($user['role']) {
        case 'teacher':
            $stmt = $db->prepare("
                SELECT t.subject_id, t.class_id, t.is_class_teacher, s.name as subject_name, c.name as class_name 
                FROM teachers t 
                LEFT JOIN subjects s ON t.subject_id = s.id 
                LEFT JOIN classes c ON t.class_id = c.id 
                WHERE t.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $role_data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            break;
            
        case 'staff':
            $stmt = $db->prepare("
                SELECT department, position, salary 
                FROM staff 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $role_data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            break;
    }
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($user, ['role_data' => $role_data])
    ]);
    
} catch (Exception $e) {
    error_log("API Error (get_user): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not retrieve user data']);
    http_response_code(500);
}
?> 