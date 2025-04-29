<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->query("
        SELECT id, username, role, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching recent users: ' . $e->getMessage()
    ]);
} 