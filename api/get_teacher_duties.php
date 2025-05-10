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

// Get duties
$stmt = $conn->prepare("
    SELECT 
        id,
        date,
        type,
        location,
        notes,
        CASE 
            WHEN date < CURDATE() THEN 'completed'
            WHEN date = CURDATE() THEN 'pending'
            ELSE 'upcoming'
        END as status
    FROM teacher_duties
    WHERE teacher_id = ?
    ORDER BY date DESC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

$duties = [];
while ($row = $result->fetch_assoc()) {
    $duties[] = [
        "id" => intval($row["id"]),
        "date" => $row["date"],
        "type" => $row["type"],
        "location" => $row["location"],
        "notes" => $row["notes"],
        "status" => $row["status"]
    ];
}

echo json_encode([
    "success" => true,
    "data" => $duties
]);
?> 