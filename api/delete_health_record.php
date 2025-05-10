<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get record ID from URL
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$record_id) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid record ID']);
    exit();
}

try {
    // Check if record exists and user has permission to delete it
    $stmt = $pdo->prepare("
        SELECT recorded_by 
        FROM health_records 
        WHERE id = ?
    ");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }
    
    // Only allow deletion by the user who created the record or an admin
    if ($record['recorded_by'] !== $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        throw new Exception('Permission denied');
    }
    
    // Delete the record
    $stmt = $pdo->prepare("DELETE FROM health_records WHERE id = ?");
    $stmt->execute([$record_id]);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Health record deleted successfully'
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 