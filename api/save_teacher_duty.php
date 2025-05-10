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

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["date"]) || !isset($data["type"]) || !isset($data["location"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit;
}

// Check if duty record already exists for this date and type
$stmt = $conn->prepare("
    SELECT id FROM teacher_duties 
    WHERE teacher_id = ? AND date = ? AND type = ?
");
$stmt->bind_param("iss", $teacher_id, $data["date"], $data["type"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing record
    $stmt = $conn->prepare("
        UPDATE teacher_duties 
        SET location = ?, 
            notes = ?,
            updated_at = NOW()
        WHERE teacher_id = ? AND date = ? AND type = ?
    ");
    $stmt->bind_param(
        "ssiss",
        $data["location"],
        $data["notes"],
        $teacher_id,
        $data["date"],
        $data["type"]
    );
} else {
    // Insert new record
    $stmt = $conn->prepare("
        INSERT INTO teacher_duties (
            teacher_id, date, type, location, 
            notes, created_at
        )
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param(
        "issss",
        $teacher_id,
        $data["date"],
        $data["type"],
        $data["location"],
        $data["notes"]
    );
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Duty record saved successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to save duty record"
    ]);
}
?> 