<?php
// API to get system status information
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

try {
    // Database status (simple check - using placeholder values)
    $database_status = 30; // 30% usage
    
    // Storage usage (placeholder)
    $storage_usage = 45; // 45% usage
    
    // Active sessions (placeholder)
    $active_sessions = 20; // 20% of capacity
    
    // System load (placeholder)
    $system_load = 35; // 35% load
    
    // Prepare response
    echo json_encode([
        'success' => true,
        'data' => [
            'database_status' => $database_status,
            'storage_usage' => $storage_usage,
            'active_sessions' => $active_sessions,
            'system_load' => $system_load,
            'last_updated' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error getting system status: ' . $e->getMessage()
    ]);
} 