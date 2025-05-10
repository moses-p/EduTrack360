<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Check if user is logged in and is a parent, teacher, or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['parent', 'teacher', 'admin'])) {
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
                $where[] = "pv.student_id = ?";
                $params[] = $_GET['student_id'];
            }
            
            // Filter by parent
            if (isset($_GET['parent_id'])) {
                $where[] = "pv.parent_id = ?";
                $params[] = $_GET['parent_id'];
            }
            
            // Filter by date range
            if (isset($_GET['start_date'])) {
                $where[] = "pv.visit_date >= ?";
                $params[] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $where[] = "pv.visit_date <= ?";
                $params[] = $_GET['end_date'];
            }
            
            // Filter by purpose
            if (isset($_GET['purpose'])) {
                $where[] = "pv.purpose = ?";
                $params[] = $_GET['purpose'];
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            $stmt = $pdo->prepare("
                SELECT pv.*, 
                       s.first_name as student_first_name,
                       s.last_name as student_last_name,
                       u.first_name as parent_first_name,
                       u.last_name as parent_last_name
                FROM parent_visits pv
                JOIN students s ON pv.student_id = s.id
                JOIN users u ON pv.parent_id = u.id
                $whereClause
                ORDER BY pv.visit_date DESC, pv.check_in DESC
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
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['student_id', 'parent_id', 'visit_date', 'purpose'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate purpose
            $valid_purposes = ['meeting', 'pickup', 'dropoff', 'other'];
            if (!in_array($data['purpose'], $valid_purposes)) {
                throw new Exception('Invalid purpose value');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO parent_visits (
                    student_id, parent_id, visit_date,
                    check_in, check_out, purpose, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['student_id'],
                $data['parent_id'],
                $data['visit_date'],
                $data['check_in'] ?? null,
                $data['check_out'] ?? null,
                $data['purpose'],
                $data['notes'] ?? null
            ]);
            
            $record_id = $pdo->lastInsertId();
            
            // Get the created record
            $stmt = $pdo->prepare("
                SELECT pv.*, 
                       s.first_name as student_first_name,
                       s.last_name as student_last_name,
                       u.first_name as parent_first_name,
                       u.last_name as parent_last_name
                FROM parent_visits pv
                JOIN students s ON pv.student_id = s.id
                JOIN users u ON pv.parent_id = u.id
                WHERE pv.id = ?
            ");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Visit record created successfully',
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
            
            // Validate purpose if provided
            if (isset($data['purpose'])) {
                $valid_purposes = ['meeting', 'pickup', 'dropoff', 'other'];
                if (!in_array($data['purpose'], $valid_purposes)) {
                    throw new Exception('Invalid purpose value');
                }
            }
            
            // Build update query
            $updates = [];
            $params = [];
            
            $fields = ['check_in', 'check_out', 'purpose', 'notes'];
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
                UPDATE parent_visits 
                SET " . implode(", ", $updates) . "
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            // Get the updated record
            $stmt = $pdo->prepare("
                SELECT pv.*, 
                       s.first_name as student_first_name,
                       s.last_name as student_last_name,
                       u.first_name as parent_first_name,
                       u.last_name as parent_last_name
                FROM parent_visits pv
                JOIN students s ON pv.student_id = s.id
                JOIN users u ON pv.parent_id = u.id
                WHERE pv.id = ?
            ");
            $stmt->execute([$data['id']]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Visit record updated successfully',
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
            
            $stmt = $pdo->prepare("DELETE FROM parent_visits WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Visit record deleted successfully'
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