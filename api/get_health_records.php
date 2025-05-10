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

try {
    // Get filter parameters
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $health_status = isset($_GET['health_status']) ? $_GET['health_status'] : null;
    
    // Build query
    $query = "
        SELECT 
            hr.*,
            s.full_name as student_name,
            u.full_name as recorded_by_name
        FROM health_records hr
        JOIN students s ON hr.student_id = s.id
        JOIN users u ON hr.recorded_by = u.id
        WHERE 1=1
    ";
    $params = [];
    
    // Add filters
    if ($student_id) {
        $query .= " AND hr.student_id = ?";
        $params[] = $student_id;
    }
    
    if ($start_date) {
        $query .= " AND hr.record_date >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date) {
        $query .= " AND hr.record_date <= ?";
        $params[] = $end_date;
    }
    
    if ($health_status) {
        $query .= " AND hr.health_status = ?";
        $params[] = $health_status;
    }
    
    // Add sorting
    $query .= " ORDER BY hr.record_date DESC, hr.created_at DESC";
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and add status colors
    foreach ($records as &$record) {
        $record['record_date'] = date('Y-m-d', strtotime($record['record_date']));
        $record['created_at'] = date('Y-m-d H:i:s', strtotime($record['created_at']));
        
        // Add status color
        switch ($record['health_status']) {
            case 'healthy':
                $record['status_color'] = 'success';
                break;
            case 'sick':
                $record['status_color'] = 'danger';
                break;
            case 'injured':
                $record['status_color'] = 'warning';
                break;
            default:
                $record['status_color'] = 'secondary';
        }
    }
    
    // Return records
    header('Content-Type: application/json');
    echo json_encode($records);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 