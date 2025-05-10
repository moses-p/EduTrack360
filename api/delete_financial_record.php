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
        SELECT recorded_by, student_id 
        FROM financial_records 
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
    $stmt = $pdo->prepare("DELETE FROM financial_records WHERE id = ?");
    $stmt->execute([$record_id]);
    
    // Get updated payment status for the student
    $stmt = $pdo->prepare("
        SELECT 
            SUM(amount) as total_fees,
            SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as paid_amount
        FROM financial_records
        WHERE student_id = ?
    ");
    $stmt->execute([$record['student_id']]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate payment percentage
    $payment_percentage = ($totals['paid_amount'] / $totals['total_fees']) * 100;
    
    // Return success response with updated payment status
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Financial record deleted successfully',
        'payment_status' => [
            'total_fees' => $totals['total_fees'],
            'paid_amount' => $totals['paid_amount'],
            'payment_percentage' => $payment_percentage
        ]
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 