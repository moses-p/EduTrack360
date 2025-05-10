<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Handle different request methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get behavior records
        try {
            $params = [];
            $where = [];
            
            // Filter by student
            if (isset($_GET['student_id'])) {
                $where[] = "b.student_id = ?";
                $params[] = $_GET['student_id'];
            }
            
            // Filter by teacher
            if (isset($_GET['teacher_id'])) {
                $where[] = "b.teacher_id = ?";
                $params[] = $_GET['teacher_id'];
            }
            
            // Filter by date range
            if (isset($_GET['start_date'])) {
                $where[] = "b.date >= ?";
                $params[] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $where[] = "b.date <= ?";
                $params[] = $_GET['end_date'];
            }
            
            // Filter by behavior type
            if (isset($_GET['behavior_type'])) {
                $where[] = "b.behavior_type = ?";
                $params[] = $_GET['behavior_type'];
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            $stmt = $pdo->prepare("
                SELECT b.*, 
                       s.first_name as student_first_name, 
                       s.last_name as student_last_name,
                       t.first_name as teacher_first_name,
                       t.last_name as teacher_last_name
                FROM behavior_records b
                JOIN students s ON b.student_id = s.id
                JOIN users t ON b.teacher_id = t.id
                $whereClause
                ORDER BY b.date DESC, b.created_at DESC
            ");
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $records]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Add new behavior record
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['student_id', 'behavior_type', 'description', 'date'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate behavior type
            $valid_types = ['positive', 'negative', 'neutral'];
            if (!in_array($data['behavior_type'], $valid_types)) {
                throw new Exception('Invalid behavior type');
            }
            
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO behavior_records (
                    student_id, teacher_id, behavior_type, 
                    description, date, points
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['student_id'],
                $_SESSION['user_id'],
                $data['behavior_type'],
                $data['description'],
                $data['date'],
                $data['points'] ?? 0
            ]);
            
            $record_id = $pdo->lastInsertId();
            
            // Get the created record
            $stmt = $pdo->prepare("
                SELECT b.*, 
                       s.first_name as student_first_name, 
                       s.last_name as student_last_name,
                       t.first_name as teacher_first_name,
                       t.last_name as teacher_last_name
                FROM behavior_records b
                JOIN students s ON b.student_id = s.id
                JOIN users t ON b.teacher_id = t.id
                WHERE b.id = ?
            ");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Behavior record created successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update behavior record
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                throw new Exception('Record ID is required');
            }
            
            // Check if user has permission to update
            $stmt = $pdo->prepare("
                SELECT teacher_id 
                FROM behavior_records 
                WHERE id = ?
            ");
            $stmt->execute([$data['id']]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record || $record['teacher_id'] != $_SESSION['user_id']) {
                throw new Exception('You do not have permission to update this record');
            }
            
            // Validate behavior type if provided
            if (isset($data['behavior_type'])) {
                $valid_types = ['positive', 'negative', 'neutral'];
                if (!in_array($data['behavior_type'], $valid_types)) {
                    throw new Exception('Invalid behavior type');
                }
            }
            
            // Build update query
            $updates = [];
            $params = [];
            
            if (isset($data['behavior_type'])) {
                $updates[] = "behavior_type = ?";
                $params[] = $data['behavior_type'];
            }
            if (isset($data['description'])) {
                $updates[] = "description = ?";
                $params[] = $data['description'];
            }
            if (isset($data['points'])) {
                $updates[] = "points = ?";
                $params[] = $data['points'];
            }
            
            if (empty($updates)) {
                throw new Exception('No fields to update');
            }
            
            $params[] = $data['id'];
            
            $stmt = $pdo->prepare("
                UPDATE behavior_records 
                SET " . implode(", ", $updates) . "
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            // Get the updated record
            $stmt = $pdo->prepare("
                SELECT b.*, 
                       s.first_name as student_first_name, 
                       s.last_name as student_last_name,
                       t.first_name as teacher_first_name,
                       t.last_name as teacher_last_name
                FROM behavior_records b
                JOIN students s ON b.student_id = s.id
                JOIN users t ON b.teacher_id = t.id
                WHERE b.id = ?
            ");
            $stmt->execute([$data['id']]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Behavior record updated successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete behavior record
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Record ID is required');
            }
            
            // Check if user has permission to delete
            $stmt = $pdo->prepare("
                SELECT teacher_id 
                FROM behavior_records 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record || $record['teacher_id'] != $_SESSION['user_id']) {
                throw new Exception('You do not have permission to delete this record');
            }
            
            $stmt = $pdo->prepare("DELETE FROM behavior_records WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Behavior record deleted successfully'
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'summary':
        // Get behavior summary
        $student_id = $_GET['student_id'] ?? $_SESSION['student_id'];
        
        // Get counts for different behavior types
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN behavior_type = 'positive' THEN 1 END) as positive,
                COUNT(CASE WHEN behavior_type = 'negative' THEN 1 END) as negative,
                SUM(points) as total_points
            FROM behavior_records
            WHERE student_id = ?
            AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$student_id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure numeric values
        $summary['positive'] = (int)$summary['positive'];
        $summary['negative'] = (int)$summary['negative'];
        $summary['total_points'] = (int)$summary['total_points'];
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $summary]);
        
        break;
        
    case 'recent':
        // Get recent behavior records
        $student_id = $_GET['student_id'] ?? $_SESSION['student_id'];
        
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   t.first_name as teacher_first_name,
                   t.last_name as teacher_last_name
            FROM behavior_records b
            JOIN users t ON b.teacher_id = t.id
            WHERE b.student_id = ?
            ORDER BY b.date DESC, b.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$student_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $records]);
        
        break;
        
    default:
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?> 