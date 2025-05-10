<?php
require_once '../config/database.php';
require_once '../includes/AINotificationManager.php';

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
    // Initialize AI notification manager
    $aiManager = new AINotificationManager($pdo);
    
    // Get student ID from session
    $student_id = $_SESSION['student_id'];
    
    // Get payment patterns
    $patterns = $aiManager->analyzePaymentPatterns($student_id);
    
    // Get upcoming payments
    $stmt = $pdo->prepare("
        SELECT 
            amount,
            due_date,
            payment_status,
            description
        FROM financial_records
        WHERE student_id = ?
            AND payment_status IN ('pending', 'overdue')
            AND due_date >= CURDATE()
        ORDER BY due_date ASC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $upcoming_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate suggestions using OpenAI
    $prompt = "Generate 3-5 personalized payment suggestions based on the following data:\n\n";
    
    if ($patterns) {
        $prompt .= "Payment Patterns:\n";
        $prompt .= "- Usually pays " . ($patterns['avg_days_before_due'] ? $patterns['avg_days_before_due'] . " days before due date" : "on time") . "\n";
        $prompt .= "- Preferred payment day: " . date('l', strtotime("next Monday +" . ($patterns['preferred_payment_day'] - 1) . " days")) . "\n";
    }
    
    $prompt .= "\nUpcoming Payments:\n";
    foreach ($upcoming_payments as $payment) {
        $prompt .= "- " . $payment['description'] . ": $" . $payment['amount'] . " due on " . $payment['due_date'] . "\n";
    }
    
    $prompt .= "\nGenerate friendly, easy-to-understand suggestions that help the parent manage their payments better. Include specific dates and amounts when relevant.";
    
    $response = $aiManager->openai->chat->create([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful school payment assistant. Provide clear, actionable suggestions in simple language.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7
    ]);
    
    // Split suggestions into an array
    $suggestions = array_filter(
        explode("\n", $response->choices[0]->message->content),
        function($line) {
            return !empty(trim($line));
        }
    );
    
    // Return suggestions
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 