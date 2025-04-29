<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Validate required parameters
$required_params = ['class_id', 'start_date', 'end_date'];
foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required parameter: $param"]);
        exit();
    }
}

// Sanitize inputs
$class_id = sanitizeInput($_GET['class_id']);
$start_date = sanitizeInput($_GET['start_date']);
$end_date = sanitizeInput($_GET['end_date']);

$db = new Database();
$conn = $db->getConnection();

try {
    // Get total students in class
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get attendance summary
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
            s.id as student_id,
            s.full_name as student_name,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absences
        FROM students s
        LEFT JOIN attendance a ON s.id = a.user_id 
            AND a.date BETWEEN ? AND ?
            AND a.user_type = 'student'
        WHERE s.class_id = ?
        GROUP BY s.id, s.full_name
        ORDER BY absences DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date, $class_id]);
    $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate average attendance
    $total_possible_attendance = $total_students * count($attendance_data);
    $total_actual_attendance = array_sum(array_column($attendance_data, 'present_days'));
    $average_attendance = $total_possible_attendance > 0 
        ? round(($total_actual_attendance / $total_possible_attendance) * 100, 2)
        : 0;
    
    // Format most absent students
    $most_absent = array_map(function($student) {
        return [
            'name' => $student['student_name'],
            'absences' => $student['absences']
        ];
    }, $attendance_data);
    
    echo json_encode([
        'total_students' => $total_students,
        'average_attendance' => $average_attendance,
        'most_absent' => $most_absent
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 