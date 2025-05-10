<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// Get teacher ID
$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Teacher not found"]);
    exit;
}

$teacher_id = $row["id"];

// Get messages
$stmt = $conn->prepare("
    SELECT 
        m.id,
        m.date,
        m.subject,
        m.content,
        m.read,
        CONCAT(u.first_name, ' ', u.last_name) as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ?
    ORDER BY m.date DESC
    LIMIT 10
");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        "id" => intval($row["id"]),
        "date" => $row["date"],
        "subject" => $row["subject"],
        "content" => $row["content"],
        "read" => (bool)$row["read"],
        "sender_name" => $row["sender_name"]
    ];
}

echo json_encode([
    "success" => true,
    "data" => $messages
]);
?> 