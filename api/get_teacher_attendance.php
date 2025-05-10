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

// Get attendance summary
if (isset($_GET["summary"]) && $_GET["summary"] === "true") {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
            COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
            COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused
        FROM teacher_attendance
        WHERE teacher_id = ?
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "success" => true,
            "data" => [
                "present" => intval($row["present"]),
                "absent" => intval($row["absent"]),
                "late" => intval($row["late"]),
                "excused" => intval($row["excused"])
            ]
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "data" => [
                "present" => 0,
                "absent" => 0,
                "late" => 0,
                "excused" => 0
            ]
        ]);
    }
}
// Get detailed attendance records
else {
    $stmt = $conn->prepare("
        SELECT 
            date,
            status,
            check_in_time,
            check_out_time,
            notes
        FROM teacher_attendance
        WHERE teacher_id = ?
        ORDER BY date DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = [
            "date" => $row["date"],
            "status" => $row["status"],
            "check_in_time" => $row["check_in_time"],
            "check_out_time" => $row["check_out_time"],
            "notes" => $row["notes"]
        ];
    }
    
    echo json_encode([
        "success" => true,
        "data" => $records
    ]);
}
?> 