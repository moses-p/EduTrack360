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
        // Get routes, stops, or attendance
        try {
            $type = $_GET['type'] ?? 'routes';
            
            if ($type === 'routes') {
                // Get routes
                $params = [];
                $where = [];
                
                // Filter by route name
                if (isset($_GET['route_name'])) {
                    $where[] = "route_name LIKE ?";
                    $params[] = "%{$_GET['route_name']}%";
                }
                
                // Filter by driver
                if (isset($_GET['driver_name'])) {
                    $where[] = "driver_name LIKE ?";
                    $params[] = "%{$_GET['driver_name']}%";
                }
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $pdo->prepare("
                    SELECT r.*, 
                           COUNT(DISTINCT st.student_id) as total_students
                    FROM transportation_routes r
                    LEFT JOIN student_transportation st ON r.id = st.route_id
                    $whereClause
                    GROUP BY r.id
                    ORDER BY r.route_name
                ");
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } else if ($type === 'stops') {
                // Get stops for a route
                if (!isset($_GET['route_id'])) {
                    throw new Exception('Route ID is required');
                }
                
                $stmt = $pdo->prepare("
                    SELECT * FROM route_stops
                    WHERE route_id = ?
                    ORDER BY stop_order
                ");
                $stmt->execute([$_GET['route_id']]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } else if ($type === 'attendance') {
                // Get transportation attendance
                $params = [];
                $where = [];
                
                // Filter by student
                if (isset($_GET['student_id'])) {
                    $where[] = "ta.student_id = ?";
                    $params[] = $_GET['student_id'];
                }
                
                // Filter by route
                if (isset($_GET['route_id'])) {
                    $where[] = "ta.route_id = ?";
                    $params[] = $_GET['route_id'];
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
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $pdo->prepare("
                    SELECT ta.*, 
                           s.first_name as student_first_name,
                           s.last_name as student_last_name,
                           r.route_name
                    FROM transportation_attendance ta
                    JOIN students s ON ta.student_id = s.id
                    JOIN transportation_routes r ON ta.route_id = r.id
                    $whereClause
                    ORDER BY ta.date DESC, s.first_name, s.last_name
                ");
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else if ($type === 'summary') {
                // Get transportation summary
                $student_id = $_GET['student_id'] ?? $_SESSION['student_id'];
                
                // Get student's transportation details
                $stmt = $pdo->prepare("
                    SELECT r.*, 
                           s.stop_name,
                           s.stop_time as pickup_time,
                           DATE_ADD(s.stop_time, INTERVAL 8 HOUR) as dropoff_time
                    FROM student_transportation st
                    JOIN transportation_routes r ON st.route_id = r.id
                    JOIN route_stops s ON st.stop_id = s.id
                    WHERE st.student_id = ?
                    AND st.pickup_type IN ('morning', 'both')
                ");
                $stmt->execute([$student_id]);
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($summary) {
                    // Get recent attendance
                    $stmt = $pdo->prepare("
                        SELECT 
                            COUNT(CASE WHEN pickup_status = 'present' THEN 1 END) as pickup_present,
                            COUNT(CASE WHEN pickup_status = 'absent' THEN 1 END) as pickup_absent,
                            COUNT(CASE WHEN pickup_status = 'late' THEN 1 END) as pickup_late,
                            COUNT(CASE WHEN dropoff_status = 'present' THEN 1 END) as dropoff_present,
                            COUNT(CASE WHEN dropoff_status = 'absent' THEN 1 END) as dropoff_absent,
                            COUNT(CASE WHEN dropoff_status = 'late' THEN 1 END) as dropoff_late
                        FROM transportation_attendance
                        WHERE student_id = ?
                        AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    ");
                    $stmt->execute([$student_id]);
                    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $summary = array_merge($summary, $attendance);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $summary]);
                
            } else if ($type === 'attendance' && isset($_GET['recent'])) {
                // Get recent transportation attendance
                $student_id = $_GET['student_id'] ?? $_SESSION['student_id'];
                
                $stmt = $pdo->prepare("
                    SELECT ta.*, 
                           r.route_name
                    FROM transportation_attendance ta
                    JOIN transportation_routes r ON ta.route_id = r.id
                    WHERE ta.student_id = ?
                    ORDER BY ta.date DESC
                    LIMIT 5
                ");
                $stmt->execute([$student_id]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $records]);
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
        // Add new route, stop, or attendance
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $type = $data['type'] ?? 'route';
            
            if ($type === 'route') {
                // Add new route
                $required_fields = ['route_name', 'vehicle_number', 'driver_name', 'capacity'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO transportation_routes (
                        route_name, vehicle_number, driver_name,
                        driver_contact, capacity
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['route_name'],
                    $data['vehicle_number'],
                    $data['driver_name'],
                    $data['driver_contact'] ?? null,
                    $data['capacity']
                ]);
                
                $route_id = $pdo->lastInsertId();
                
                // Get the created route
                $stmt = $pdo->prepare("
                    SELECT r.*, 
                           COUNT(DISTINCT st.student_id) as total_students
                    FROM transportation_routes r
                    LEFT JOIN student_transportation st ON r.id = st.route_id
                    WHERE r.id = ?
                    GROUP BY r.id
                ");
                $stmt->execute([$route_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } else if ($type === 'stop') {
                // Add new stop
                $required_fields = ['route_id', 'stop_name', 'stop_time', 'stop_order'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO route_stops (
                        route_id, stop_name, stop_time, stop_order
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['route_id'],
                    $data['stop_name'],
                    $data['stop_time'],
                    $data['stop_order']
                ]);
                
                $stop_id = $pdo->lastInsertId();
                
                // Get the created stop
                $stmt = $pdo->prepare("SELECT * FROM route_stops WHERE id = ?");
                $stmt->execute([$stop_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } else if ($type === 'attendance') {
                // Add transportation attendance
                $required_fields = ['student_id', 'route_id', 'date', 'pickup_status', 'dropoff_status'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }
                
                // Validate status values
                $valid_statuses = ['present', 'absent', 'late'];
                if (!in_array($data['pickup_status'], $valid_statuses) || 
                    !in_array($data['dropoff_status'], $valid_statuses)) {
                    throw new Exception('Invalid status value');
                }
                
                // Check if attendance already exists
                $stmt = $pdo->prepare("
                    SELECT id FROM transportation_attendance
                    WHERE student_id = ? AND route_id = ? AND date = ?
                ");
                $stmt->execute([
                    $data['student_id'],
                    $data['route_id'],
                    $data['date']
                ]);
                if ($stmt->fetch()) {
                    throw new Exception('Attendance record already exists for this date');
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO transportation_attendance (
                        student_id, route_id, date,
                        pickup_status, dropoff_status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['student_id'],
                    $data['route_id'],
                    $data['date'],
                    $data['pickup_status'],
                    $data['dropoff_status'],
                    $data['notes'] ?? null
                ]);
                
                $attendance_id = $pdo->lastInsertId();
                
                // Get the created attendance
                $stmt = $pdo->prepare("
                    SELECT ta.*, 
                           s.first_name as student_first_name,
                           s.last_name as student_last_name,
                           r.route_name
                    FROM transportation_attendance ta
                    JOIN students s ON ta.student_id = s.id
                    JOIN transportation_routes r ON ta.route_id = r.id
                    WHERE ta.id = ?
                ");
                $stmt->execute([$attendance_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' created successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update route, stop, or attendance
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $type = $data['type'] ?? 'route';
            
            if ($type === 'route') {
                // Update route
                if (!isset($data['id'])) {
                    throw new Exception('Route ID is required');
                }
                
                // Build update query
                $updates = [];
                $params = [];
                
                $fields = ['route_name', 'vehicle_number', 'driver_name', 'driver_contact', 'capacity'];
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
                    UPDATE transportation_routes 
                    SET " . implode(", ", $updates) . "
                    WHERE id = ?
                ");
                $stmt->execute($params);
                
                // Get the updated route
                $stmt = $pdo->prepare("
                    SELECT r.*, 
                           COUNT(DISTINCT st.student_id) as total_students
                    FROM transportation_routes r
                    LEFT JOIN student_transportation st ON r.id = st.route_id
                    WHERE r.id = ?
                    GROUP BY r.id
                ");
                $stmt->execute([$data['id']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } else if ($type === 'stop') {
                // Update stop
                if (!isset($data['id'])) {
                    throw new Exception('Stop ID is required');
                }
                
                // Build update query
                $updates = [];
                $params = [];
                
                $fields = ['stop_name', 'stop_time', 'stop_order'];
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
                    UPDATE route_stops 
                    SET " . implode(", ", $updates) . "
                    WHERE id = ?
                ");
                $stmt->execute($params);
                
                // Get the updated stop
                $stmt = $pdo->prepare("SELECT * FROM route_stops WHERE id = ?");
                $stmt->execute([$data['id']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } else if ($type === 'attendance') {
                // Update attendance
                if (!isset($data['id'])) {
                    throw new Exception('Attendance ID is required');
                }
                
                // Validate status values if provided
                $valid_statuses = ['present', 'absent', 'late'];
                if (isset($data['pickup_status']) && !in_array($data['pickup_status'], $valid_statuses)) {
                    throw new Exception('Invalid pickup status');
                }
                if (isset($data['dropoff_status']) && !in_array($data['dropoff_status'], $valid_statuses)) {
                    throw new Exception('Invalid dropoff status');
                }
                
                // Build update query
                $updates = [];
                $params = [];
                
                $fields = ['pickup_status', 'dropoff_status', 'notes'];
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
                    UPDATE transportation_attendance 
                    SET " . implode(", ", $updates) . "
                    WHERE id = ?
                ");
                $stmt->execute($params);
                
                // Get the updated attendance
                $stmt = $pdo->prepare("
                    SELECT ta.*, 
                           s.first_name as student_first_name,
                           s.last_name as student_last_name,
                           r.route_name
                    FROM transportation_attendance ta
                    JOIN students s ON ta.student_id = s.id
                    JOIN transportation_routes r ON ta.route_id = r.id
                    WHERE ta.id = ?
                ");
                $stmt->execute([$data['id']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' updated successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete route or stop
        try {
            $id = $_GET['id'] ?? null;
            $type = $_GET['type'] ?? 'route';
            
            if (!$id) {
                throw new Exception('ID is required');
            }
            
            if ($type === 'route') {
                // Check if route has students
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM student_transportation 
                    WHERE route_id = ?
                ");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Cannot delete route with assigned students');
                }
                
                $stmt = $pdo->prepare("DELETE FROM transportation_routes WHERE id = ?");
                $stmt->execute([$id]);
                
            } else if ($type === 'stop') {
                // Check if stop has students
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM student_transportation 
                    WHERE stop_id = ?
                ");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Cannot delete stop with assigned students');
                }
                
                $stmt = $pdo->prepare("DELETE FROM route_stops WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' deleted successfully'
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