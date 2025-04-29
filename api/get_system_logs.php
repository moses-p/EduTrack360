<?php
// API to get system logs/activities
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Ensure user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Default limit is 10 records, can be set via parameter
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit = max(1, min(50, $limit)); // Between 1 and 50

try {
    // Check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    $table_exists = $table_check->rowCount() > 0;
    
    if (!$table_exists) {
        // Return empty data if table doesn't exist yet
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }
    
    // Query latest logs with user information
    $sql = "SELECT l.*, u.full_name as user_name
            FROM system_logs l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.created_at DESC
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no logs found, provide a fallback response
    if (empty($logs)) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }
    
    // Format timestamps and prepare response
    foreach ($logs as &$log) {
        // Ensure timestamp is in a consistent format
        if (isset($log['created_at'])) {
            $timestamp = strtotime($log['created_at']);
            if ($timestamp) {
                $log['created_at'] = date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        // Truncate message if needed for displaying in UI
        if (isset($log['message']) && strlen($log['message']) > 100) {
            $log['short_message'] = substr($log['message'], 0, 97) . '...';
        } else {
            $log['short_message'] = $log['message'] ?? '';
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $logs
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 