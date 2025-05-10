<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// Get co-curricular activities for a student
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
    
    // Get activity summary
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT activity_id) as total_activities,
            COUNT(DISTINCT CASE WHEN achievement_type IS NOT NULL THEN activity_id END) as total_achievements
        FROM co_curricular_activities
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    
    // Get activities with details
    $stmt = $conn->prepare("
        SELECT 
            cca.*,
            a.name as activity_name,
            a.category as activity_category,
            a.description as activity_description
        FROM co_curricular_activities cca
        JOIN activities a ON cca.activity_id = a.id
        WHERE cca.student_id = ?
        ORDER BY cca.date DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "data" => [
            "total_activities" => intval($summary["total_activities"]),
            "total_achievements" => intval($summary["total_achievements"]),
            "activities" => $activities
        ]
    ]);
}

// Add a new activity participation
else if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data["student_id"]) || !isset($data["activity_id"]) || 
        !isset($data["date"]) || !isset($data["role"])) {
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
    
    // Insert activity participation
    $stmt = $conn->prepare("
        INSERT INTO co_curricular_activities (
            student_id, activity_id, date, role, achievement_type, 
            achievement_description, created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iissss", 
        $data["student_id"],
        $data["activity_id"],
        $data["date"],
        $data["role"],
        $data["achievement_type"] ?? null,
        $data["achievement_description"] ?? null
    );
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Activity participation recorded successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to record activity participation"]);
    }
}

// Update activity achievement
else if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data["participation_id"]) || !isset($data["achievement_type"]) || 
        !isset($data["achievement_description"])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Missing required fields"]);
        exit;
    }
    
    // Verify parent has access to this participation record
    $stmt = $conn->prepare("
        SELECT cca.id 
        FROM co_curricular_activities cca
        JOIN students s ON cca.student_id = s.id
        WHERE cca.id = ? AND s.parent_id = ?
    ");
    $stmt->bind_param("ii", $data["participation_id"], $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Access denied"]);
        exit;
    }
    
    // Update achievement
    $stmt = $conn->prepare("
        UPDATE co_curricular_activities 
        SET achievement_type = ?, achievement_description = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", 
        $data["achievement_type"],
        $data["achievement_description"],
        $data["participation_id"]
    );
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Achievement updated successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to update achievement"]);
    }
}

else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
}
?> 