<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get recent reports
    $query = "
        SELECT 
            r.id,
            r.report_type,
            r.period,
            r.start_date,
            r.end_date,
            r.generated_at,
            u.full_name as generated_by
        FROM reports r
        JOIN users u ON r.generated_by = u.id
        ORDER BY r.generated_at DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $formattedReports = array_map(function($report) {
        return [
            'id' => $report['id'],
            'name' => ucfirst($report['report_type']) . ' Report (' . $report['period'] . ')',
            'date' => date('M d, Y H:i', strtotime($report['generated_at'])),
            'type' => $report['report_type'],
            'period' => $report['period'],
            'date_range' => date('M d', strtotime($report['start_date'])) . ' - ' . date('M d, Y', strtotime($report['end_date'])),
            'generated_by' => $report['generated_by']
        ];
    }, $reports);
    
    echo json_encode($formattedReports);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 