<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php'; // For PhpSpreadsheet and TCPDF

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

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
            $type = $_GET['type'] ?? null;
            $format = $_GET['format'] ?? 'csv';
            $student_id = $_GET['student_id'] ?? null;
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            
            if (!$type) {
                throw new Exception('Report type is required');
            }
            
            // Validate format
            $valid_formats = ['csv', 'excel', 'pdf'];
            if (!in_array($format, $valid_formats)) {
                throw new Exception('Invalid format');
            }
            
            // Get data based on type
            switch ($type) {
                case 'attendance':
                    $data = getAttendanceData($pdo, $student_id, $start_date, $end_date);
                    $headers = ['Date', 'Status', 'Notes'];
                    $filename = 'attendance_report';
                    break;
                    
                case 'behavior':
                    $data = getBehaviorData($pdo, $student_id, $start_date, $end_date);
                    $headers = ['Date', 'Type', 'Description', 'Points', 'Teacher'];
                    $filename = 'behavior_report';
                    break;
                    
                case 'library':
                    $data = getLibraryData($pdo, $student_id, $start_date, $end_date);
                    $headers = ['Book', 'Author', 'Borrowed Date', 'Due Date', 'Status'];
                    $filename = 'library_report';
                    break;
                    
                case 'transportation':
                    $data = getTransportationData($pdo, $student_id, $start_date, $end_date);
                    $headers = ['Date', 'Route', 'Pickup Status', 'Dropoff Status', 'Notes'];
                    $filename = 'transportation_report';
                    break;
                    
                default:
                    throw new Exception('Invalid report type');
            }
            
            // Export data in requested format
            switch ($format) {
                case 'csv':
                    exportCSV($data, $headers, $filename);
                    break;
                    
                case 'excel':
                    exportExcel($data, $headers, $filename);
                    break;
                    
                case 'pdf':
                    exportPDF($data, $headers, $filename);
                    break;
            }
            
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

// Helper functions to get data
function getAttendanceData($pdo, $student_id, $start_date, $end_date) {
    $params = [];
    $where = [];
    
    if ($student_id) {
        $where[] = "student_id = ?";
        $params[] = $student_id;
    }
    if ($start_date) {
        $where[] = "date >= ?";
        $params[] = $start_date;
    }
    if ($end_date) {
        $where[] = "date <= ?";
        $params[] = $end_date;
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $stmt = $pdo->prepare("
        SELECT date, status, notes
        FROM attendance
        $whereClause
        ORDER BY date DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBehaviorData($pdo, $student_id, $start_date, $end_date) {
    $params = [];
    $where = [];
    
    if ($student_id) {
        $where[] = "b.student_id = ?";
        $params[] = $student_id;
    }
    if ($start_date) {
        $where[] = "b.date >= ?";
        $params[] = $start_date;
    }
    if ($end_date) {
        $where[] = "b.date <= ?";
        $params[] = $end_date;
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $stmt = $pdo->prepare("
        SELECT b.date, b.behavior_type, b.description, b.points,
               CONCAT(t.first_name, ' ', t.last_name) as teacher
        FROM behavior_records b
        JOIN users t ON b.teacher_id = t.id
        $whereClause
        ORDER BY b.date DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLibraryData($pdo, $student_id, $start_date, $end_date) {
    $params = [];
    $where = [];
    
    if ($student_id) {
        $where[] = "b.student_id = ?";
        $params[] = $student_id;
    }
    if ($start_date) {
        $where[] = "b.borrowed_date >= ?";
        $params[] = $start_date;
    }
    if ($end_date) {
        $where[] = "b.borrowed_date <= ?";
        $params[] = $end_date;
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $stmt = $pdo->prepare("
        SELECT bk.title as book, bk.author,
               b.borrowed_date, b.due_date, b.status
        FROM book_borrowings b
        JOIN library_books bk ON b.book_id = bk.id
        $whereClause
        ORDER BY b.borrowed_date DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTransportationData($pdo, $student_id, $start_date, $end_date) {
    $params = [];
    $where = [];
    
    if ($student_id) {
        $where[] = "ta.student_id = ?";
        $params[] = $student_id;
    }
    if ($start_date) {
        $where[] = "ta.date >= ?";
        $params[] = $start_date;
    }
    if ($end_date) {
        $where[] = "ta.date <= ?";
        $params[] = $end_date;
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $stmt = $pdo->prepare("
        SELECT ta.date, r.route_name, ta.pickup_status,
               ta.dropoff_status, ta.notes
        FROM transportation_attendance ta
        JOIN transportation_routes r ON ta.route_id = r.id
        $whereClause
        ORDER BY ta.date DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Export functions
function exportCSV($data, $headers, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }
    
    fclose($output);
}

function exportExcel($data, $headers, $filename) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Write headers
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($col, 1, $header);
        $col++;
    }
    
    // Write data
    $row = 2;
    foreach ($data as $rowData) {
        $col = 1;
        foreach ($rowData as $value) {
            $sheet->setCellValueByColumnAndRow($col, $row, $value);
            $col++;
        }
        $row++;
    }
    
    // Auto-size columns
    foreach (range(1, count($headers)) as $col) {
        $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
    }
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

function exportPDF($data, $headers, $filename) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('EduTrack360');
    $pdf->SetTitle($filename);
    
    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Create the table
    $html = '<table border="1" cellpadding="4">';
    
    // Add headers
    $html .= '<tr>';
    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $html .= '</tr>';
    
    // Add data
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Output the table
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output($filename . '.pdf', 'D');
}
?> 