<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get export format
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

// Validate format
if (!in_array($format, ['pdf', 'excel'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid format']);
    exit();
}

try {
    // Get filter parameters
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $health_status = isset($_GET['health_status']) ? $_GET['health_status'] : null;
    
    // Build query
    $query = "
        SELECT 
            hr.*,
            s.full_name as student_name,
            u.full_name as recorded_by_name
        FROM health_records hr
        JOIN students s ON hr.student_id = s.id
        JOIN users u ON hr.recorded_by = u.id
        WHERE 1=1
    ";
    $params = [];
    
    // Add filters
    if ($student_id) {
        $query .= " AND hr.student_id = ?";
        $params[] = $student_id;
    }
    
    if ($start_date) {
        $query .= " AND hr.record_date >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date) {
        $query .= " AND hr.record_date <= ?";
        $params[] = $end_date;
    }
    
    if ($health_status) {
        $query .= " AND hr.health_status = ?";
        $params[] = $health_status;
    }
    
    // Add sorting
    $query .= " ORDER BY hr.record_date DESC, hr.created_at DESC";
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate file based on format
    if ($format === 'pdf') {
        generatePDF($records);
    } else {
        generateExcel($records);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

function generatePDF($records) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('EduTrack360');
    $pdf->SetTitle('Health Records Report');
    
    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'EduTrack360', 'School Management System');
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Add report header
    $pdf->Cell(0, 10, 'Health Records Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Add table header
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 7, 'Student', 1);
    $pdf->Cell(30, 7, 'Date', 1);
    $pdf->Cell(30, 7, 'Status', 1);
    $pdf->Cell(50, 7, 'Description', 1);
    $pdf->Cell(40, 7, 'Action Taken', 1);
    $pdf->Ln();
    
    // Add table data
    $pdf->SetFont('helvetica', '', 12);
    foreach ($records as $record) {
        $pdf->Cell(40, 7, $record['student_name'], 1);
        $pdf->Cell(30, 7, date('Y-m-d', strtotime($record['record_date'])), 1);
        $pdf->Cell(30, 7, ucfirst($record['health_status']), 1);
        $pdf->Cell(50, 7, $record['description'] ?: '-', 1);
        $pdf->Cell(40, 7, $record['action_taken'] ?: '-', 1);
        $pdf->Ln();
    }
    
    // Output PDF
    $pdf->Output('Health_Records_Report.pdf', 'D');
}

function generateExcel($records) {
    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('EduTrack360')
        ->setLastModifiedBy('EduTrack360')
        ->setTitle('Health Records Report')
        ->setSubject('School Management System Report');
    
    // Add report header
    $sheet->setCellValue('A1', 'Health Records Report');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Add table header
    $sheet->setCellValue('A3', 'Student');
    $sheet->setCellValue('B3', 'Date');
    $sheet->setCellValue('C3', 'Status');
    $sheet->setCellValue('D3', 'Description');
    $sheet->setCellValue('E3', 'Action Taken');
    
    // Style header
    $sheet->getStyle('A3:E3')->getFont()->setBold(true);
    
    // Add table data
    $row = 4;
    foreach ($records as $record) {
        $sheet->setCellValue('A' . $row, $record['student_name']);
        $sheet->setCellValue('B' . $row, date('Y-m-d', strtotime($record['record_date'])));
        $sheet->setCellValue('C' . $row, ucfirst($record['health_status']));
        $sheet->setCellValue('D' . $row, $record['description'] ?: '-');
        $sheet->setCellValue('E' . $row, $record['action_taken'] ?: '-');
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Create Excel file
    $writer = new Xlsx($spreadsheet);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Health_Records_Report.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Output Excel file
    $writer->save('php://output');
}
?> 