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
    $transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : null;
    $payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : null;
    
    // Build query
    $query = "
        SELECT 
            fr.*,
            s.full_name as student_name,
            u.full_name as recorded_by_name,
            (
                SELECT SUM(amount)
                FROM financial_records
                WHERE student_id = fr.student_id
            ) as total_fees,
            (
                SELECT SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END)
                FROM financial_records
                WHERE student_id = fr.student_id
            ) as paid_amount
        FROM financial_records fr
        JOIN students s ON fr.student_id = s.id
        JOIN users u ON fr.recorded_by = u.id
        WHERE 1=1
    ";
    $params = [];
    
    // Add filters
    if ($student_id) {
        $query .= " AND fr.student_id = ?";
        $params[] = $student_id;
    }
    
    if ($start_date) {
        $query .= " AND fr.due_date >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date) {
        $query .= " AND fr.due_date <= ?";
        $params[] = $end_date;
    }
    
    if ($transaction_type) {
        $query .= " AND fr.transaction_type = ?";
        $params[] = $transaction_type;
    }
    
    if ($payment_status) {
        $query .= " AND fr.payment_status = ?";
        $params[] = $payment_status;
    }
    
    // Add sorting
    $query .= " ORDER BY fr.due_date DESC, fr.created_at DESC";
    
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
    $pdf->SetTitle('Financial Records Report');
    
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
    $pdf->Cell(0, 10, 'Financial Records Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Add payment summary
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Payment Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    // Group records by student
    $studentPayments = [];
    foreach ($records as $record) {
        if (!isset($studentPayments[$record['student_id']])) {
            $studentPayments[$record['student_id']] = [
                'name' => $record['student_name'],
                'total' => $record['total_fees'],
                'paid' => $record['paid_amount'],
                'percentage' => ($record['paid_amount'] / $record['total_fees']) * 100
            ];
        }
    }
    
    // Add student payment summaries
    foreach ($studentPayments as $student) {
        $pdf->Cell(60, 7, $student['name'], 0);
        $pdf->Cell(30, 7, 'Total: $' . number_format($student['total'], 2), 0);
        $pdf->Cell(30, 7, 'Paid: $' . number_format($student['paid'], 2), 0);
        $pdf->Cell(30, 7, number_format($student['percentage'], 1) . '%', 0);
        $pdf->Ln();
    }
    
    $pdf->Ln(10);
    
    // Add table header
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 7, 'Student', 1);
    $pdf->Cell(30, 7, 'Type', 1);
    $pdf->Cell(25, 7, 'Amount', 1);
    $pdf->Cell(25, 7, 'Due Date', 1);
    $pdf->Cell(25, 7, 'Status', 1);
    $pdf->Cell(45, 7, 'Description', 1);
    $pdf->Ln();
    
    // Add table data
    $pdf->SetFont('helvetica', '', 12);
    foreach ($records as $record) {
        $pdf->Cell(40, 7, $record['student_name'], 1);
        $pdf->Cell(30, 7, ucfirst($record['transaction_type']), 1);
        $pdf->Cell(25, 7, '$' . number_format($record['amount'], 2), 1);
        $pdf->Cell(25, 7, date('Y-m-d', strtotime($record['due_date'])), 1);
        $pdf->Cell(25, 7, ucfirst($record['payment_status']), 1);
        $pdf->Cell(45, 7, $record['description'] ?: '-', 1);
        $pdf->Ln();
    }
    
    // Output PDF
    $pdf->Output('Financial_Records_Report.pdf', 'D');
}

function generateExcel($records) {
    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('EduTrack360')
        ->setLastModifiedBy('EduTrack360')
        ->setTitle('Financial Records Report')
        ->setSubject('School Management System Report');
    
    // Add report header
    $sheet->setCellValue('A1', 'Financial Records Report');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Add payment summary header
    $sheet->setCellValue('A3', 'Payment Summary');
    $sheet->mergeCells('A3:F3');
    $sheet->getStyle('A3')->getFont()->setBold(true);
    
    // Group records by student
    $studentPayments = [];
    foreach ($records as $record) {
        if (!isset($studentPayments[$record['student_id']])) {
            $studentPayments[$record['student_id']] = [
                'name' => $record['student_name'],
                'total' => $record['total_fees'],
                'paid' => $record['paid_amount'],
                'percentage' => ($record['paid_amount'] / $record['total_fees']) * 100
            ];
        }
    }
    
    // Add student payment summaries
    $row = 4;
    foreach ($studentPayments as $student) {
        $sheet->setCellValue('A' . $row, $student['name']);
        $sheet->setCellValue('B' . $row, 'Total: $' . number_format($student['total'], 2));
        $sheet->setCellValue('C' . $row, 'Paid: $' . number_format($student['paid'], 2));
        $sheet->setCellValue('D' . $row, number_format($student['percentage'], 1) . '%');
        $row++;
    }
    
    // Add table header
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Student');
    $sheet->setCellValue('B' . $row, 'Type');
    $sheet->setCellValue('C' . $row, 'Amount');
    $sheet->setCellValue('D' . $row, 'Due Date');
    $sheet->setCellValue('E' . $row, 'Status');
    $sheet->setCellValue('F' . $row, 'Description');
    
    // Style header
    $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
    
    // Add table data
    $row++;
    foreach ($records as $record) {
        $sheet->setCellValue('A' . $row, $record['student_name']);
        $sheet->setCellValue('B' . $row, ucfirst($record['transaction_type']));
        $sheet->setCellValue('C' . $row, '$' . number_format($record['amount'], 2));
        $sheet->setCellValue('D' . $row, date('Y-m-d', strtotime($record['due_date'])));
        $sheet->setCellValue('E' . $row, ucfirst($record['payment_status']));
        $sheet->setCellValue('F' . $row, $record['description'] ?: '-');
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Create Excel file
    $writer = new Xlsx($spreadsheet);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Financial_Records_Report.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Output Excel file
    $writer->save('php://output');
}
?> 