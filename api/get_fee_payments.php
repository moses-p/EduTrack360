<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// Get fee payments for a student
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $student_id = isset($_GET["student_id"]) ? intval($_GET["student_id"]) : 0;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Student ID is required"]);
        exit;
    }
    
    // Verify parent has access to this student
    $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND parent_id = ?");
    $stmt->bind_param("ii", $student_id, $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Access denied"]);
        exit;
    }
    
    // Get fee summary
    $stmt = $conn->prepare("
        SELECT 
            SUM(amount) as total,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid,
            SUM(CASE WHEN status IN ('pending', 'overdue') THEN amount ELSE 0 END) as pending
        FROM fee_payments
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    
    // Get payment history
    $stmt = $conn->prepare("
        SELECT *
        FROM fee_payments
        WHERE student_id = ?
        ORDER BY due_date DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "data" => [
            "total" => floatval($summary["total"]),
            "paid" => floatval($summary["paid"]),
            "pending" => floatval($summary["pending"]),
            "payments" => $payments
        ]
    ]);
}

// Add a new payment
else if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data["student_id"]) || !isset($data["amount"]) || 
        !isset($data["description"]) || !isset($data["due_date"])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Missing required fields"]);
        exit;
    }
    
    // Verify parent has access to this student
    $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND parent_id = ?");
    $stmt->bind_param("ii", $data["student_id"], $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Access denied"]);
        exit;
    }
    
    // Insert payment
    $stmt = $conn->prepare("
        INSERT INTO fee_payments (student_id, amount, description, due_date, status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->bind_param("idss", 
        $data["student_id"],
        $data["amount"],
        $data["description"],
        $data["due_date"]
    );
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Payment added successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to add payment"]);
    }
}

// Update payment status
else if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data["payment_id"]) || !isset($data["status"])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Payment ID and status are required"]);
        exit;
    }
    
    // Verify parent has access to this payment
    $stmt = $conn->prepare("
        SELECT fp.id 
        FROM fee_payments fp
        JOIN students s ON fp.student_id = s.id
        WHERE fp.id = ? AND s.parent_id = ?
    ");
    $stmt->bind_param("ii", $data["payment_id"], $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Access denied"]);
        exit;
    }
    
    // Update payment
    $stmt = $conn->prepare("UPDATE fee_payments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $data["status"], $data["payment_id"]);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Payment status updated"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to update payment"]);
    }
}

else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
}
?> 