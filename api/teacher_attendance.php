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
            $type = $_GET['type'] ?? 'attendance';
            
            if ($type === 'attendance') {
                // Get teacher attendance records
                $params = [];
                $where = [];
                
                // Filter by teacher
                if (isset($_GET['teacher_id'])) {
                    $where[] = "ta.teacher_id = ?";
                    $params[] = $_GET['teacher_id'];
                }
                
                // Filter by date range
                if (isset($_GET['start_date'])) {
                    $where[] = "ta.date >= ?";
                    $params[] = $_GET['start_date'];
                }
                if (isset($_GET['end_date'])) {
                    $where[] = "ta.date <= ?";
                    $params[] = $_GET['end_date'];
                }
                
                // Filter by status
                if (isset($_GET['status'])) {
                    $where[] = "ta.status = ?";
                    $params[] = $_GET['status'];
                }
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $pdo->prepare("
                    SELECT ta.*, 
                           u.first_name as teacher_first_name,
                           u.last_name as teacher_last_name
                    FROM teacher_attendance ta
                    JOIN users u ON ta.teacher_id = u.id
                    $whereClause
                    ORDER BY ta.date DESC, ta.check_in DESC
                ");
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } else if ($type === 'duty') {
                // Get teacher duty records
                $params = [];
                $where = [];
                
                // Filter by teacher
                if (isset($_GET['teacher_id'])) {
                    $where[] = "td.teacher_id = ?";
                    $params[] = $_GET['teacher_id'];
                }
                
                // Filter by date range
                if (isset($_GET['start_date'])) {
                    $where[] = "td.duty_date >= ?";
                    $params[] = $_GET['start_date'];
                }
                if (isset($_GET['end_date'])) {
                    $where[] = "td.duty_date <= ?";
                    $params[] = $_GET['end_date'];
                }
                
                // Filter by duty type
                if (isset($_GET['duty_type'])) {
                    $where[] = "td.duty_type = ?";
                    $params[] = $_GET['duty_type'];
                }
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $pdo->prepare("
                    SELECT td.*, 
                           u.first_name as teacher_first_name,
                           u.last_name as teacher_last_name
                    FROM teacher_duty td
                    JOIN users u ON td.teacher_id = u.id
                    $whereClause
                    ORDER BY td.duty_date DESC
                ");
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
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
            $type = $data['type'] ?? 'attendance';
            
            if ($type === 'attendance') {
                // Add new attendance record
                $required_fields = ['teacher_id', 'date', 'status'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }
                
                // Validate status
                $valid_statuses = ['present', 'absent', 'late', 'excused'];
                if (!in_array($data['status'], $valid_statuses)) {
                    throw new Exception('Invalid status value');
                }
                
                // Check if attendance already exists
                $stmt = $pdo->prepare("
                    SELECT id FROM teacher_attendance
                    WHERE teacher_id = ? AND date = ?
                ");
                $stmt->execute([$data['teacher_id'], $data['date']]);
                if ($stmt->fetch()) {
                    throw new Exception('Attendance record already exists for this date');
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO teacher_attendance (
                        teacher_id, date, check_in, check_out,
                        status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['teacher_id'],
                    $data['date'],
                    $data['check_in'] ?? null,
                    $data['check_out'] ?? null,
                    $data['status'],
                    $data['notes'] ?? null
                ]);
                
                $record_id = $pdo->lastInsertId();
                
                // Get the created record
                $stmt = $pdo->prepare("
                    SELECT ta.*, 
                           u.first_name as teacher_first_name,
                           u.last_name as teacher_last_name
                    FROM teacher_attendance ta
                    JOIN users u ON ta.teacher_id = u.id
                    WHERE ta.id = ?
                ");
                $stmt->execute([$record_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } else if ($type === 'duty') {
                // Add new duty record
                $required_fields = ['teacher_id', 'duty_date', 'duty_type'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }
                
                // Validate duty type
                $valid_types = ['morning', 'afternoon', 'full_day'];
                if (!in_array($data['duty_type'], $valid_types)) {
                    throw new Exception('Invalid duty type');
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO teacher_duty (
                        teacher_id, duty_date, duty_type,
                        location, notes
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['teacher_id'],
                    $data['duty_date'],
                    $data['duty_type'],
                    $data['location'] ?? null,
                    $data['notes'] ?? null
                ]);
                
                $record_id = $pdo->lastInsertId();
                
                // Get the created record
                $stmt = $pdo->prepare("
                    SELECT td.*, 
                           u.first_name as teacher_first_name,
                           u.last_name as teacher_last_name
                    FROM teacher_duty td
                    JOIN users u ON td.teacher_id = u.id
                    WHERE td.id = ?
                ");
                $stmt->execute([$record_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' record created successfully',
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
            $type = $data['type'] ?? 'attendance';
            
            if ($type === 'attendance') {
                // Update attendance record
                if (!isset($data['id'])) {
                    throw new Exception('Record ID is required');
                }
                
                // Validate status if provided
                if (isset($data['status'])) {
                    $valid_statuses = ['present', 'absent', 'late', 'excused'];
                    if (!in_array($data['status'], $valid_statuses)) {
                        throw new Exception('Invalid status value');
                    }
                }
                
                // Build update query
                $updates = [];
                $params = [];
                
                $fields = ['check_in', 'check_out', 'status', 'notes'];
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
                    UPDATE teacher_attendance 
                    SET " . implode(", ", $updates) . "
                    WHERE id = ?
                ");
                $stmt->execute($params);
                
                // Get the updated record
                $stmt = $pdo->prepare("
                    SELECT ta.*, 
                           u.first_name as teacher_first_name,
                           u.last_name as teacher_last_name
                    FROM teacher_attendance ta
                    JOIN users u ON ta.teacher_id = u.id
                    WHERE ta.id = ?
                ");
                $stmt->execute([$data['id']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } else if ($type === 'duty') {
                // Update duty record
                if (!isset($data['id'])) {
                    throw new Exception('Record ID is required');
                }
                
                // Validate duty type if provided
                if (isset($data['duty_type'])) {
                    $valid_types = ['morning', 'afternoon', 'full_day'];
                    if (!in_array($data['duty_type'], $valid_types)) {
                        throw new Exception('Invalid duty type');
                    }
                }
                
                // Build update query
                $updates = [];
                $params = [];
                
                $fields = ['duty_type', 'location', 'notes'];
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
                    UPDATE teacher_duty 
                    SET " . implode(", ", $updates) . "
                    WHERE id = ?
                ");
                $stmt->execute($params);
                
                // Get the updated record
                $stmt = $pdo->prepare("
                    SELECT td.*, 
                           u.first_name as teacher_first_name,
                           u.last_name as teacher_last_name
                    FROM teacher_duty td
                    JOIN users u ON td.teacher_id = u.id
                    WHERE td.id = ?
                ");
                $stmt->execute([$data['id']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' record updated successfully',
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
            $type = $_GET['type'] ?? 'attendance';
            
            if (!$id) {
                throw new Exception('Record ID is required');
            }
            
            if ($type === 'attendance') {
                $stmt = $pdo->prepare("DELETE FROM teacher_attendance WHERE id = ?");
            } else if ($type === 'duty') {
                $stmt = $pdo->prepare("DELETE FROM teacher_duty WHERE id = ?");
            } else {
                throw new Exception('Invalid record type');
            }
            
            $stmt->execute([$id]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' record deleted successfully'
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