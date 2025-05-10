<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/NotificationManager.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get and validate input data
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$transaction_type = isset($_POST['transaction_type']) ? $_POST['transaction_type'] : '';
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';
$payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : null;
$payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Validate required fields
if (!$student_id || !$transaction_type || !$amount || !$due_date || !$payment_status) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

// Validate transaction type
$valid_types = ['tuition', 'transport', 'library', 'other'];
if (!in_array($transaction_type, $valid_types)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid transaction type']);
    exit();
}

// Validate payment status
$valid_statuses = ['paid', 'partial', 'unpaid'];
if (!in_array($payment_status, $valid_statuses)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payment status']);
    exit();
}

// Validate payment date based on status
if ($payment_status !== 'unpaid' && !$payment_date) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Payment date is required for paid or partial payments']);
    exit();
}

try {
    // Check if student exists
    $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Student not found');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert financial record
    $stmt = $pdo->prepare("
        INSERT INTO financial_records (
            student_id,
            transaction_type,
            amount,
            due_date,
            payment_date,
            payment_status,
            description,
            recorded_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $student_id,
        $transaction_type,
        $amount,
        $due_date,
        $payment_date,
        $payment_status,
        $description,
        $_SESSION['user_id']
    ]);
    
    $record_id = $pdo->lastInsertId();
    
    // Initialize notification manager
    $notificationManager = new NotificationManager($pdo);
    
    // Schedule payment reminders if status is unpaid or partial
    if ($payment_status !== 'paid') {
        $notificationManager->schedulePaymentReminders($record_id);
    } else {
        // Send payment confirmation if status is paid
        $notificationManager->sendPaymentConfirmation($record_id);
    }
    
    // Get student's total fees and paid amount
    $stmt = $pdo->prepare("
        SELECT 
            SUM(amount) as total_fees,
            SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as paid_amount
        FROM financial_records
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate payment percentage
    $payment_percentage = ($totals['paid_amount'] / $totals['total_fees']) * 100;
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response with payment status
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Financial record added successfully',
        'record_id' => $record_id,
        'payment_status' => [
            'total_fees' => $totals['total_fees'],
            'paid_amount' => $totals['paid_amount'],
            'payment_percentage' => $payment_percentage
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 