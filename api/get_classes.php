<?php
/**
 * API endpoint to get all classes
 * GET /api/get_classes.php
 * 
 * Optional parameters:
 * - teacher_id: Filter classes by teacher ID (admin only)
 * - year: Filter classes by academic year
 * - term: Filter classes by term
 * - status: Filter classes by status (active/inactive)
 * 
 * Returns:
 * - List of classes with teacher information
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user role and ID
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    // Build query based on user role
    $params = [];
    $sql = "SELECT id, name, year, term FROM classes WHERE status = 'active'";
    
    // Filter classes based on user role
    if ($user_role === 'teacher') {
        // Teachers can only see their assigned classes
        $sql = "
            SELECT c.id, c.name, c.year, c.term 
            FROM classes c
            JOIN teachers t ON t.class_id = c.id
            WHERE t.user_id = ? AND c.status = 'active'
        ";
        $params[] = $user_id;
    } elseif ($user_role === 'parent') {
        // Parents can only see classes of their children
        $sql = "
            SELECT DISTINCT c.id, c.name, c.year, c.term 
            FROM classes c
            JOIN students s ON s.class_id = c.id
            WHERE s.parent_id = ? AND c.status = 'active'
        ";
        $params[] = $user_id;
    }
    
    // Order by name
    $sql .= " ORDER BY c.year DESC, c.term ASC, c.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with classes
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_classes.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
} catch (Exception $e) {
    error_log("General error in get_classes.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
?> 