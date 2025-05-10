<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// Get teacher information
$stmt = $conn->prepare("
    SELECT 
        CONCAT(u.first_name, ' ', u.last_name) as name,
        t.subject,
        COUNT(DISTINCT c.id) as total_classes,
        COUNT(DISTINCT sc.student_id) as total_students
    FROM users u
    JOIN teachers t ON u.id = t.user_id
    LEFT JOIN class_teachers ct ON t.id = ct.teacher_id
    LEFT JOIN classes c ON ct.class_id = c.id
    LEFT JOIN student_classes sc ON c.id = sc.class_id
    WHERE u.id = ?
    GROUP BY u.id, t.subject
");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "data" => [
            "name" => $row["name"],
            "subject" => $row["subject"],
            "total_classes" => intval($row["total_classes"]),
            "total_students" => intval($row["total_students"])
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Teacher not found"]);
}
?> 