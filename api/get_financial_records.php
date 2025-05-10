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
    $transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : null;
    $payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : null;
    
    // Build query
    $query = "
        SELECT 
            fr.*,
            s.full_name as student_name,
            u.full_name as recorded_by_name,
            (
                SELECT SUM(amount)
                FROM financial_records
                WHERE student_id = fr.student_id
            ) as total_fees,
            (
                SELECT SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END)
                FROM financial_records
                WHERE student_id = fr.student_id
            ) as paid_amount
        FROM financial_records fr
        JOIN students s ON fr.student_id = s.id
        JOIN users u ON fr.recorded_by = u.id
        WHERE 1=1
    ";
    $params = [];
    
    // Add filters
    if ($student_id) {
        $query .= " AND fr.student_id = ?";
        $params[] = $student_id;
    }
    
    if ($start_date) {
        $query .= " AND fr.due_date >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date) {
        $query .= " AND fr.due_date <= ?";
        $params[] = $end_date;
    }
    
    if ($transaction_type) {
        $query .= " AND fr.transaction_type = ?";
        $params[] = $transaction_type;
    }
    
    if ($payment_status) {
        $query .= " AND fr.payment_status = ?";
        $params[] = $payment_status;
    }
    
    // Add sorting
    $query .= " ORDER BY fr.due_date DESC, fr.created_at DESC";
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and add payment status information
    foreach ($records as &$record) {
        $record['due_date'] = date('Y-m-d', strtotime($record['due_date']));
        $record['payment_date'] = $record['payment_date'] ? date('Y-m-d', strtotime($record['payment_date'])) : null;
        $record['created_at'] = date('Y-m-d H:i:s', strtotime($record['created_at']));
        
        // Calculate payment percentage
        $record['payment_percentage'] = ($record['paid_amount'] / $record['total_fees']) * 100;
        
        // Add status color
        switch ($record['payment_status']) {
            case 'paid':
                $record['status_color'] = 'success';
                break;
            case 'partial':
                $record['status_color'] = 'warning';
                break;
            case 'unpaid':
                $record['status_color'] = 'danger';
                break;
            default:
                $record['status_color'] = 'secondary';
        }
        
        // Add payment alert
        if ($record['payment_percentage'] === 0) {
            $record['payment_alert'] = 'No payments made';
        } elseif ($record['payment_percentage'] < 50) {
            $record['payment_alert'] = 'Less than 50% paid';
        } elseif ($record['payment_percentage'] < 100) {
            $record['payment_alert'] = 'Partially paid';
        } else {
            $record['payment_alert'] = 'Fully paid';
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