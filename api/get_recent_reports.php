<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Debug: Check for empty tables and insert sample data if needed
    $checkStudents = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
    if ($checkStudents == 0) {
        // Create a sample student if none exists
        $conn->exec("INSERT INTO students (admission_number, full_name, date_of_birth, gender, class_id) 
                     VALUES ('ST001', 'John Doe', '2010-01-01', 'male', 1)");
    }
    
    $checkReports = $conn->query("SELECT COUNT(*) FROM reports")->fetchColumn();
    if ($checkReports == 0) {
        // Create a sample report if none exists
        $studentId = $conn->query("SELECT id FROM students LIMIT 1")->fetchColumn();
        if ($studentId) {
            $conn->exec("INSERT INTO reports 
                        (student_id, class_id, term, year, total_marks, average_marks, position, remarks)
                        VALUES 
                        ($studentId, 1, 1, 2024, 450, 75.5, 1, 'Good performance overall.')");
        }
    }
    
    // Get user role and ID
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    // Build query based on user role
    $params = [];
    $sql = "
        SELECT 
            r.id,
            CONCAT('Report for ', s.full_name, ' - ', CASE r.term 
                WHEN 1 THEN 'Term 1' 
                WHEN 2 THEN 'Term 2' 
                WHEN 3 THEN 'Term 3' 
                ELSE 'Unknown Term' 
            END, ' ', r.year) AS name,
            'academic' AS type,
            r.created_at AS generated_date,
            COALESCE(u.full_name, 'System') AS generated_by
        FROM 
            reports r
        JOIN 
            students s ON r.student_id = s.id
        LEFT JOIN 
            users u ON u.id = (SELECT user_id FROM teachers WHERE class_id = r.class_id LIMIT 1)
    ";
    
    // Filter based on user role
    if ($user_role === 'parent') {
        // Parent can only see reports for their children
        $sql .= " WHERE s.parent_id = ? ";
        $params[] = $user_id;
    } elseif ($user_role === 'teacher') {
        // Teachers can see reports for students in their classes
        $sql .= " WHERE EXISTS (
            SELECT 1 FROM teachers t 
            WHERE t.class_id = r.class_id 
            AND t.user_id = ?
        ) ";
        $params[] = $user_id;
    }
    // Admin can see all reports
    
    // Order by most recent first
    $sql .= " ORDER BY r.created_at DESC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with reports
    echo json_encode([
        'success' => true,
        'reports' => $reports
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_recent_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get_recent_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?> 