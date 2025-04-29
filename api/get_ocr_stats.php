<?php
// API to get OCR statistics
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Ensure user is logged in and has admin privileges
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get date range (default to last 30 days)
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-$days days"));

try {
    // Get total scans
    $total_sql = "SELECT COUNT(*) as total FROM ocr_attempts 
                  WHERE created_at BETWEEN :start_date AND :end_date";
    $total_stmt = $pdo->prepare($total_sql);
    $total_stmt->bindParam(':start_date', $start_date);
    $total_stmt->bindParam(':end_date', $end_date);
    $total_stmt->execute();
    $total_scans = $total_stmt->fetchColumn();
    
    // Placeholder response if table doesn't exist yet
    if ($total_scans === false) {
        echo json_encode([
            'success' => true,
            'data' => [
                'total_scans' => 0,
                'correct_scans' => 0,
                'incorrect_scans' => 0,
                'adjusted_scans' => 0,
                'pending_scans' => 0,
                'accuracy_rate' => 0,
                'avg_confidence' => 0,
                'needs_review' => 0,
                'needs_review_rate' => 0,
                'trend' => [
                    'dates' => [],
                    'counts' => []
                ]
            ]
        ]);
        exit;
    }
    
    // Get accuracy statistics
    $accuracy_sql = "SELECT 
                      COUNT(CASE WHEN feedback = 'correct' THEN 1 END) as correct_scans,
                      COUNT(CASE WHEN feedback = 'incorrect' THEN 1 END) as incorrect_scans,
                      COUNT(CASE WHEN feedback = 'adjusted' THEN 1 END) as adjusted_scans,
                      COUNT(CASE WHEN feedback IS NULL THEN 1 END) as pending_scans,
                      AVG(confidence) as avg_confidence
                     FROM ocr_attempts
                     WHERE created_at BETWEEN :start_date AND :end_date";
    
    $accuracy_stmt = $pdo->prepare($accuracy_sql);
    $accuracy_stmt->bindParam(':start_date', $start_date);
    $accuracy_stmt->bindParam(':end_date', $end_date);
    $accuracy_stmt->execute();
    $accuracy_data = $accuracy_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate accuracy rate
    $correct_scans = (int)$accuracy_data['correct_scans'];
    $incorrect_scans = (int)$accuracy_data['incorrect_scans'];
    $adjusted_scans = (int)$accuracy_data['adjusted_scans'];
    $pending_scans = (int)$accuracy_data['pending_scans'];
    
    $reviewed_scans = $correct_scans + $incorrect_scans + $adjusted_scans;
    $accuracy_rate = $reviewed_scans > 0 ? 
        round(($correct_scans / $reviewed_scans) * 100) : 0;
    
    // Calculate needs review rate (pending + incorrect)
    $needs_review = $pending_scans + $incorrect_scans;
    $needs_review_rate = $total_scans > 0 ? 
        round(($needs_review / $total_scans) * 100) : 0;
    
    // Get daily scan counts for trending
    $daily_sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM ocr_attempts 
                 WHERE created_at BETWEEN :start_date AND :end_date 
                 GROUP BY DATE(created_at)
                 ORDER BY date";
    
    $daily_stmt = $pdo->prepare($daily_sql);
    $daily_stmt->bindParam(':start_date', $start_date);
    $daily_stmt->bindParam(':end_date', $end_date);
    $daily_stmt->execute();
    $daily_data = $daily_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare daily trending data
    $trend_dates = [];
    $trend_counts = [];
    
    foreach ($daily_data as $row) {
        $trend_dates[] = date('M d', strtotime($row['date']));
        $trend_counts[] = (int)$row['count'];
    }
    
    // Prepare response data
    $response = [
        'success' => true,
        'data' => [
            'total_scans' => $total_scans,
            'correct_scans' => $correct_scans,
            'incorrect_scans' => $incorrect_scans,
            'adjusted_scans' => $adjusted_scans,
            'pending_scans' => $pending_scans,
            'accuracy_rate' => $accuracy_rate,
            'avg_confidence' => round($accuracy_data['avg_confidence'] ?? 0),
            'needs_review' => $needs_review,
            'needs_review_rate' => $needs_review_rate,
            'trend' => [
                'dates' => $trend_dates,
                'counts' => $trend_counts
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 