<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get total users
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Get active classes
    $stmt = $conn->query("SELECT COUNT(*) as active_classes FROM classes WHERE status = 'active'");
    $active_classes = $stmt->fetch(PDO::FETCH_ASSOC)['active_classes'];

    // Get total subjects
    $stmt = $conn->query("SELECT COUNT(*) as total_subjects FROM subjects");
    $total_subjects = $stmt->fetch(PDO::FETCH_ASSOC)['total_subjects'];

    // Get pending tasks
    $stmt = $conn->query("SELECT COUNT(*) as pending_tasks FROM tasks WHERE status = 'pending'");
    $pending_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['pending_tasks'];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_users' => $total_users,
            'active_classes' => $active_classes,
            'total_subjects' => $total_subjects,
            'pending_tasks' => $pending_tasks
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching system overview: ' . $e->getMessage()
    ]);
} 