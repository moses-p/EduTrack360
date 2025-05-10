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
        try {
            $type = $_GET['type'] ?? 'records';
            
            if ($type === 'summary') {
                // Get attendance summary
                $student_id = $_GET['student_id'] ?? $_SESSION['student_id'];
                
                // Get counts for different statuses
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                        COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                        COUNT(*) as total
                    FROM attendance
                    WHERE student_id = ?
                    AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ");
                $stmt->execute([$student_id]);
                $counts = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculate percentage
                $percentage = $counts['total'] > 0 
                    ? round(($counts['present'] / $counts['total']) * 100) 
                    : 0;
                
                // Get daily attendance percentages for the chart
                $stmt = $pdo->prepare("
                    SELECT 
                        date,
                        ROUND((COUNT(CASE WHEN status = 'present' THEN 1 END) * 100.0 / COUNT(*))) as percentage
                    FROM attendance
                    WHERE student_id = ?
                    AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY date
                    ORDER BY date
                ");
                $stmt->execute([$student_id]);
                $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $dates = array_column($daily_data, 'date');
                $percentages = array_column($daily_data, 'percentage');
                
                $summary = [
                    'present' => (int)$counts['present'],
                    'absent' => (int)$counts['absent'],
                    'late' => (int)$counts['late'],
                    'percentage' => $percentage,
                    'dates' => $dates,
                    'percentages' => $percentages
                ];
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $summary]);
                
            } else {
                // Get attendance records
                $params = [];
                $where = [];
                
                // Filter by student
                if (isset($_GET['student_id'])) {
                    $where[] = "student_id = ?";
                    $params[] = $_GET['student_id'];
                }
                
                // Filter by date range
                if (isset($_GET['start_date'])) {
                    $where[] = "date >= ?";
                    $params[] = $_GET['start_date'];
                }
                if (isset($_GET['end_date'])) {
                    $where[] = "date <= ?";
                    $params[] = $_GET['end_date'];
                }
                
                // Filter by status
                if (isset($_GET['status'])) {
                    $where[] = "status = ?";
                    $params[] = $_GET['status'];
                }
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $pdo->prepare("
                    SELECT * FROM attendance
                    $whereClause
                    ORDER BY date DESC
                ");
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
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
        // Add new attendance record
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['student_id', 'date', 'status'];
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
            
            // Check if attendance already exists for this date
            $stmt = $pdo->prepare("
                SELECT id FROM attendance
                WHERE student_id = ? AND date = ?
            ");
            $stmt->execute([$data['student_id'], $data['date']]);
            if ($stmt->fetch()) {
                throw new Exception('Attendance record already exists for this date');
            }
            
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO attendance (
                    student_id, date, status, notes
                ) VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['student_id'],
                $data['date'],
                $data['status'],
                $data['notes'] ?? null
            ]);
            
            $record_id = $pdo->lastInsertId();
            
            // Get the created record
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE id = ?");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Attendance record created successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update attendance record
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
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
            
            if (isset($data['status'])) {
                $updates[] = "status = ?";
                $params[] = $data['status'];
            }
            if (isset($data['notes'])) {
                $updates[] = "notes = ?";
                $params[] = $data['notes'];
            }
            
            if (empty($updates)) {
                throw new Exception('No fields to update');
            }
            
            $params[] = $data['id'];
            
            $stmt = $pdo->prepare("
                UPDATE attendance 
                SET " . implode(", ", $updates) . "
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            // Get the updated record
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE id = ?");
            $stmt->execute([$data['id']]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Attendance record updated successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete attendance record
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Record ID is required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Attendance record deleted successfully'
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