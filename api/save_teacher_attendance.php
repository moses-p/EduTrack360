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

if (!isset($data["date"]) || !isset($data["status"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit;
}

// Check if attendance record already exists for this date
$stmt = $conn->prepare("SELECT id FROM teacher_attendance WHERE teacher_id = ? AND date = ?");
$stmt->bind_param("is", $teacher_id, $data["date"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing record
    $stmt = $conn->prepare("
        UPDATE teacher_attendance 
        SET status = ?, 
            check_in_time = ?, 
            check_out_time = ?, 
            notes = ?,
            updated_at = NOW()
        WHERE teacher_id = ? AND date = ?
    ");
    $stmt->bind_param(
        "ssssis",
        $data["status"],
        $data["check_in_time"],
        $data["check_out_time"],
        $data["notes"],
        $teacher_id,
        $data["date"]
    );
} else {
    // Insert new record
    $stmt = $conn->prepare("
        INSERT INTO teacher_attendance (
            teacher_id, date, status, check_in_time, 
            check_out_time, notes, created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param(
        "isssss",
        $teacher_id,
        $data["date"],
        $data["status"],
        $data["check_in_time"],
        $data["check_out_time"],
        $data["notes"]
    );
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Attendance record saved successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to save attendance record"
    ]);
}
?> 