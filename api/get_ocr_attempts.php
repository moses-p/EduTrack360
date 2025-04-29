<?php
// API to get OCR attempts with filtering options
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Ensure user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get filter parameters
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Calculate offset
$offset = ($page - 1) * $limit;

// Base SQL
$sql = "SELECT o.id, o.teacher_id, 
               CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
               o.image_path, o.extracted_text, o.marks_data, 
               o.status, o.admin_feedback, o.created_at, o.reviewed_at
        FROM ocr_attempts o
        JOIN users u ON o.teacher_id = u.id
        WHERE 1=1";

// Add filters
$params = [];

// Teacher filter - admin can see all, teachers can only see their own
if (is_admin()) {
    if ($teacher_id) {
        $sql .= " AND o.teacher_id = :teacher_id";
        $params[':teacher_id'] = $teacher_id;
    }
} else {
    // Regular teacher can only see their own attempts
    $sql .= " AND o.teacher_id = :current_user_id";
    $params[':current_user_id'] = $_SESSION['user_id'];
}

// Status filter
if ($status) {
    $sql .= " AND o.status = :status";
    $params[':status'] = $status;
}

// Date range filter
if ($start_date) {
    $sql .= " AND DATE(o.created_at) >= :start_date";
    $params[':start_date'] = $start_date;
}

if ($end_date) {
    $sql .= " AND DATE(o.created_at) <= :end_date";
    $params[':end_date'] = $end_date;
}

// Count total records for pagination
$count_sql = str_replace("SELECT o.id, o.teacher_id, 
               CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
               o.image_path, o.extracted_text, o.marks_data, 
               o.status, o.admin_feedback, o.created_at, o.reviewed_at", 
               "SELECT COUNT(*) as total", $sql);

$count_stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total = $count_stmt->fetchColumn();

// Order and limit
$sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Execute main query
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    if ($key == ':limit' || $key == ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare pagination info
$total_pages = ceil($total / $limit);

echo json_encode([
    'status' => 'success',
    'data' => [
        'attempts' => $attempts,
        'pagination' => [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]
    ]
]); 