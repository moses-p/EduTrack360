<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// Get messages for a student
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
    
    // Get messages
    $stmt = $conn->prepare("
        SELECT m.*, 
               CONCAT(u.first_name, ' ', u.last_name) as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.student_id = ?
        ORDER BY m.date DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    echo json_encode(["success" => true, "data" => $messages]);
}

// Send a new message
else if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data["student_id"]) || !isset($data["recipient_id"]) || 
        !isset($data["subject"]) || !isset($data["content"])) {
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
    
    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO messages (student_id, sender_id, recipient_id, subject, content, date)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiiss", 
        $data["student_id"],
        $_SESSION["user_id"],
        $data["recipient_id"],
        $data["subject"],
        $data["content"]
    );
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Message sent successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to send message"]);
    }
}

// Mark message as read
else if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data["message_id"])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Message ID is required"]);
        exit;
    }
    
    // Verify parent has access to this message
    $stmt = $conn->prepare("
        SELECT m.id 
        FROM messages m
        JOIN students s ON m.student_id = s.id
        WHERE m.id = ? AND s.parent_id = ?
    ");
    $stmt->bind_param("ii", $data["message_id"], $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Access denied"]);
        exit;
    }
    
    // Update message
    $stmt = $conn->prepare("UPDATE messages SET read = 1 WHERE id = ?");
    $stmt->bind_param("i", $data["message_id"]);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Message marked as read"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to update message"]);
    }
}

else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
}
?> 