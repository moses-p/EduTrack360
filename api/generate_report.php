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

// Get request data
$reportType = $_POST['report_type'] ?? '';
$period = $_POST['period'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$classId = $_POST['class_id'] ?? '';
$studentId = $_POST['student_id'] ?? '';
$format = $_POST['format'] ?? 'online';

// Validate required fields
if (!$reportType || !$startDate || !$endDate || !$classId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Generate report based on type
    switch ($reportType) {
        case 'academic':
            $report = generateAcademicReport($conn, $startDate, $endDate, $classId, $studentId);
            break;
        case 'attendance':
            $report = generateAttendanceReport($conn, $startDate, $endDate, $classId, $studentId);
            break;
        case 'health':
            $report = generateHealthReport($conn, $startDate, $endDate, $classId, $studentId);
            break;
        case 'discipline':
            $report = generateDisciplineReport($conn, $startDate, $endDate, $classId, $studentId);
            break;
        case 'co_curricular':
            $report = generateCoCurricularReport($conn, $startDate, $endDate, $classId, $studentId);
            break;
        case 'financial':
            $report = generateFinancialReport($conn, $startDate, $endDate, $classId, $studentId);
            break;
        case 'passout':
            $report = generatePassoutReport($conn, $startDate, $endDate, $classId, $studentId);
            break;
        default:
            throw new Exception('Invalid report type');
    }
    
    // Save report to database
    $stmt = $conn->prepare("
        INSERT INTO reports (
            report_type, period, start_date, end_date, class_id, student_id,
            generated_by, generated_at, report_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    
    $stmt->execute([
        $reportType,
        $period,
        $startDate,
        $endDate,
        $classId,
        $studentId ?: null,
        $_SESSION['user_id'],
        json_encode($report)
    ]);
    
    $reportId = $conn->lastInsertId();
    
    // Commit transaction
    $conn->commit();
    
    // Return response based on format
    if ($format === 'online') {
        echo json_encode([
            'success' => true,
            'report_id' => $reportId,
            'message' => 'Report generated successfully'
        ]);
    } else {
        // Generate file based on format
        $file = generateReportFile($report, $format);
        
        // Set appropriate headers
        header('Content-Type: ' . ($format === 'pdf' ? 'application/pdf' : 'application/vnd.ms-excel'));
        header('Content-Disposition: attachment; filename="' . $reportType . '_report.' . $format . '"');
        
        echo $file;
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Helper functions for generating different types of reports
function generateAcademicReport($conn, $startDate, $endDate, $classId, $studentId) {
    // Implementation for academic report
    $query = "
                SELECT 
            s.id as student_id,
            s.full_name as student_name,
            sub.name as subject_name,
            m.score,
            m.grade,
            m.exam_date
        FROM students s
        JOIN marks m ON s.id = m.student_id
        JOIN subjects sub ON m.subject_id = sub.id
        WHERE s.class_id = :class_id
        AND m.exam_date BETWEEN :start_date AND :end_date
    ";
    
    if ($studentId) {
        $query .= " AND s.id = :student_id";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':class_id', $classId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    
    if ($studentId) {
        $stmt->bindParam(':student_id', $studentId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateAttendanceReport($conn, $startDate, $endDate, $classId, $studentId) {
    // Implementation for attendance report
    $query = "
        SELECT 
            s.id as student_id,
            s.full_name as student_name,
            a.date,
            a.status,
            a.remarks
        FROM students s
        JOIN attendance a ON s.id = a.student_id
        WHERE s.class_id = :class_id
        AND a.date BETWEEN :start_date AND :end_date
    ";
    
    if ($studentId) {
        $query .= " AND s.id = :student_id";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':class_id', $classId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    
    if ($studentId) {
        $stmt->bindParam(':student_id', $studentId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateHealthReport($conn, $startDate, $endDate, $classId, $studentId) {
    // Implementation for health report
    $query = "
        SELECT 
            s.id as student_id,
            s.full_name as student_name,
            h.record_date,
            h.health_status,
            h.description,
            h.action_taken
        FROM students s
        JOIN health_records h ON s.id = h.student_id
        WHERE s.class_id = :class_id
        AND h.record_date BETWEEN :start_date AND :end_date
    ";
    
    if ($studentId) {
        $query .= " AND s.id = :student_id";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':class_id', $classId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    
    if ($studentId) {
        $stmt->bindParam(':student_id', $studentId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateDisciplineReport($conn, $startDate, $endDate, $classId, $studentId) {
    // Implementation for discipline report
    $query = "
        SELECT 
            s.id as student_id,
            s.full_name as student_name,
            d.incident_date,
            d.incident_type,
            d.description,
            d.action_taken
        FROM students s
        JOIN discipline_records d ON s.id = d.student_id
        WHERE s.class_id = :class_id
        AND d.incident_date BETWEEN :start_date AND :end_date
    ";
    
    if ($studentId) {
        $query .= " AND s.id = :student_id";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':class_id', $classId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    
    if ($studentId) {
        $stmt->bindParam(':student_id', $studentId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateCoCurricularReport($conn, $startDate, $endDate, $classId, $studentId) {
    // Implementation for co-curricular activities report
    $query = "
        SELECT 
            s.id as student_id,
            s.full_name as student_name,
            c.name as activity_name,
            c.start_date,
            c.end_date,
            ap.role,
            ap.performance_notes
        FROM students s
        JOIN activity_participation ap ON s.id = ap.student_id
        JOIN co_curricular_activities c ON ap.activity_id = c.id
        WHERE s.class_id = :class_id
        AND c.start_date BETWEEN :start_date AND :end_date
    ";
    
    if ($studentId) {
        $query .= " AND s.id = :student_id";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':class_id', $classId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    
    if ($studentId) {
        $stmt->bindParam(':student_id', $studentId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateFinancialReport($conn, $startDate, $endDate, $classId, $studentId) {
    // Implementation for financial report
    $query = "
        SELECT 
            s.id as student_id,
            s.full_name as student_name,
            f.transaction_type,
            f.amount,
            f.due_date,
            f.payment_date,
            f.status,
            f.description
        FROM students s
        JOIN financial_records f ON s.id = f.student_id
        WHERE s.class_id = :class_id
        AND f.due_date BETWEEN :start_date AND :end_date
    ";
    
    if ($studentId) {
        $query .= " AND s.id = :student_id";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':class_id', $classId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    
    if ($studentId) {
        $stmt->bindParam(':student_id', $studentId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generatePassoutReport($conn, $startDate, $endDate, $classId, $studentId) {
    // Implementation for passout report
    $query = "
        SELECT 
            s.id as student_id,
            s.full_name as student_name,
            p.passout_date,
            p.reason,
            p.final_remarks
        FROM students s
        JOIN passout_records p ON s.id = p.student_id
        WHERE s.class_id = :class_id
        AND p.passout_date BETWEEN :start_date AND :end_date
    ";
    
    if ($studentId) {
        $query .= " AND s.id = :student_id";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':class_id', $classId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    
    if ($studentId) {
        $stmt->bindParam(':student_id', $studentId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateReportFile($data, $format) {
    // Implementation for generating PDF or Excel file
    // This is a placeholder - you'll need to implement actual file generation
    // using a library like TCPDF for PDF or PhpSpreadsheet for Excel
    return "Report data in " . $format . " format";
}
?> 