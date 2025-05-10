<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Check if user is logged in and is a teacher or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Handle different request methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            $params = [];
            $where = [];
            
            // Filter by student
            if (isset($_GET['student_id'])) {
                $where[] = "up.student_id = ?";
                $params[] = $_GET['student_id'];
            }
            
            // Filter by year
            if (isset($_GET['year'])) {
                $where[] = "up.year = ?";
                $params[] = $_GET['year'];
            }
            
            // Filter by subject
            if (isset($_GET['subject'])) {
                $where[] = "up.subject = ?";
                $params[] = $_GET['subject'];
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            $stmt = $pdo->prepare("
                SELECT up.*, 
                       s.first_name as student_first_name,
                       s.last_name as student_last_name
                FROM uneb_performance up
                JOIN students s ON up.student_id = s.id
                $whereClause
                ORDER BY up.year DESC, up.subject ASC
            ");
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If summary is requested
            if (isset($_GET['summary']) && $_GET['summary'] === 'true') {
                $summary = [];
                
                // Get overall performance by year
                $stmt = $pdo->prepare("
                    SELECT year,
                           COUNT(*) as total_subjects,
                           AVG(grade) as average_grade,
                           MIN(grade) as lowest_grade,
                           MAX(grade) as highest_grade
                    FROM uneb_performance
                    $whereClause
                    GROUP BY year
                    ORDER BY year DESC
                ");
                $stmt->execute($params);
                $summary['by_year'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get performance by subject
                $stmt = $pdo->prepare("
                    SELECT subject,
                           COUNT(*) as total_students,
                           AVG(grade) as average_grade,
                           MIN(grade) as lowest_grade,
                           MAX(grade) as highest_grade
                    FROM uneb_performance
                    $whereClause
                    GROUP BY subject
                    ORDER BY subject ASC
                ");
                $stmt->execute($params);
                $summary['by_subject'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $summary]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $records]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['student_id', 'year', 'subject', 'grade'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate grade
            if (!is_numeric($data['grade']) || $data['grade'] < 1 || $data['grade'] > 9) {
                throw new Exception('Invalid grade value. Grade must be between 1 and 9');
            }
            
            // Check if record already exists
            $stmt = $pdo->prepare("
                SELECT id FROM uneb_performance
                WHERE student_id = ? AND year = ? AND subject = ?
            ");
            $stmt->execute([$data['student_id'], $data['year'], $data['subject']]);
            if ($stmt->fetch()) {
                throw new Exception('Performance record already exists for this student, year, and subject');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO uneb_performance (
                    student_id, year, subject,
                    grade, remarks, notes
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['student_id'],
                $data['year'],
                $data['subject'],
                $data['grade'],
                $data['remarks'] ?? null,
                $data['notes'] ?? null
            ]);
            
            $record_id = $pdo->lastInsertId();
            
            // Get the created record
            $stmt = $pdo->prepare("
                SELECT up.*, 
                       s.first_name as student_first_name,
                       s.last_name as student_last_name
                FROM uneb_performance up
                JOIN students s ON up.student_id = s.id
                WHERE up.id = ?
            ");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Performance record created successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                throw new Exception('Record ID is required');
            }
            
            // Validate grade if provided
            if (isset($data['grade'])) {
                if (!is_numeric($data['grade']) || $data['grade'] < 1 || $data['grade'] > 9) {
                    throw new Exception('Invalid grade value. Grade must be between 1 and 9');
                }
            }
            
            // Build update query
            $updates = [];
            $params = [];
            
            $fields = ['grade', 'remarks', 'notes'];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                throw new Exception('No fields to update');
            }
            
            $params[] = $data['id'];
            
            $stmt = $pdo->prepare("
                UPDATE uneb_performance 
                SET " . implode(", ", $updates) . "
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            // Get the updated record
            $stmt = $pdo->prepare("
                SELECT up.*, 
                       s.first_name as student_first_name,
                       s.last_name as student_last_name
                FROM uneb_performance up
                JOIN students s ON up.student_id = s.id
                WHERE up.id = ?
            ");
            $stmt->execute([$data['id']]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Performance record updated successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('Record ID is required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM uneb_performance WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Performance record deleted successfully'
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    default:
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?> 