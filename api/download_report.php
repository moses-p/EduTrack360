<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php'; // For TCPDF and PhpSpreadsheet

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

// Get report ID and format from request
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

// Validate format
if (!in_array($format, ['pdf', 'excel'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid format']);
    exit();
}

try {
    // Get report data from database
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            c.name as class_name,
            s.full_name as student_name,
            u.full_name as generated_by
        FROM reports r
        LEFT JOIN classes c ON r.class_id = c.id
        LEFT JOIN students s ON r.student_id = s.id
        JOIN users u ON r.generated_by = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        throw new Exception('Report not found');
    }
    
    // Decode report data
    $reportData = json_decode($report['report_data'], true);
    
    // Generate file based on format
    if ($format === 'pdf') {
        generatePDF($report, $reportData);
    } else {
        generateExcel($report, $reportData);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Function to generate PDF report
function generatePDF($report, $data) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('EduTrack360');
    $pdf->SetTitle(ucfirst($report['report_type']) . ' Report');
    
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
    $pdf->Cell(0, 10, ucfirst($report['report_type']) . ' Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Add report details
    $pdf->Cell(40, 7, 'Period:', 0);
    $pdf->Cell(0, 7, $report['period'], 0, 1);
    
    $pdf->Cell(40, 7, 'Date Range:', 0);
    $pdf->Cell(0, 7, $report['start_date'] . ' to ' . $report['end_date'], 0, 1);
    
    $pdf->Cell(40, 7, 'Class:', 0);
    $pdf->Cell(0, 7, $report['class_name'], 0, 1);
    
    if ($report['student_name']) {
        $pdf->Cell(40, 7, 'Student:', 0);
        $pdf->Cell(0, 7, $report['student_name'], 0, 1);
    }
    
    $pdf->Ln(10);
    
    // Add report content based on type
    switch ($report['report_type']) {
        case 'academic':
            addAcademicReportContent($pdf, $data);
            break;
        case 'attendance':
            addAttendanceReportContent($pdf, $data);
            break;
        case 'health':
            addHealthReportContent($pdf, $data);
            break;
        case 'discipline':
            addDisciplineReportContent($pdf, $data);
            break;
        case 'co-curricular':
            addCoCurricularReportContent($pdf, $data);
            break;
        case 'financial':
            addFinancialReportContent($pdf, $data);
            break;
        case 'passout':
            addPassoutReportContent($pdf, $data);
            break;
        // Add cases for other report types...
    }
    
    // Output PDF
    $pdf->Output(ucfirst($report['report_type']) . '_Report.pdf', 'D');
}

// Function to generate Excel report
function generateExcel($report, $data) {
    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('EduTrack360')
        ->setLastModifiedBy('EduTrack360')
        ->setTitle(ucfirst($report['report_type']) . ' Report')
        ->setSubject('School Management System Report');
    
    // Add report header
    $sheet->setCellValue('A1', ucfirst($report['report_type']) . ' Report');
    $sheet->mergeCells('A1:D1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Add report details
    $sheet->setCellValue('A3', 'Period:');
    $sheet->setCellValue('B3', $report['period']);
    
    $sheet->setCellValue('A4', 'Date Range:');
    $sheet->setCellValue('B4', $report['start_date'] . ' to ' . $report['end_date']);
    
    $sheet->setCellValue('A5', 'Class:');
    $sheet->setCellValue('B5', $report['class_name']);
    
    if ($report['student_name']) {
        $sheet->setCellValue('A6', 'Student:');
        $sheet->setCellValue('B6', $report['student_name']);
    }
    
    // Add report content based on type
    switch ($report['report_type']) {
        case 'academic':
            addAcademicReportContent($sheet, $data);
            break;
        case 'attendance':
            addAttendanceReportContent($sheet, $data);
            break;
        case 'health':
            addHealthReportContent($sheet, $data);
            break;
        case 'discipline':
            addDisciplineReportContent($sheet, $data);
            break;
        case 'co-curricular':
            addCoCurricularReportContent($sheet, $data);
            break;
        case 'financial':
            addFinancialReportContent($sheet, $data);
            break;
        case 'passout':
            addPassoutReportContent($sheet, $data);
            break;
        // Add cases for other report types...
    }
    
    // Auto-size columns
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Create Excel file
    $writer = new Xlsx($spreadsheet);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . ucfirst($report['report_type']) . '_Report.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Output Excel file
    $writer->save('php://output');
}

// Helper functions for adding report content
function addAcademicReportContent($document, $data) {
    if ($document instanceof TCPDF) {
        // Add table header
        $document->SetFont('helvetica', 'B', 12);
        $document->Cell(60, 7, 'Subject', 1);
        $document->Cell(30, 7, 'Score', 1);
        $document->Cell(30, 7, 'Grade', 1);
        $document->Cell(60, 7, 'Date', 1);
        $document->Ln();
        
        // Add table data
        $document->SetFont('helvetica', '', 12);
        foreach ($data as $record) {
            $document->Cell(60, 7, $record['subject_name'], 1);
            $document->Cell(30, 7, $record['score'], 1);
            $document->Cell(30, 7, $record['grade'], 1);
            $document->Cell(60, 7, $record['exam_date'], 1);
            $document->Ln();
        }
    } else {
        // Add table header
        $document->setCellValue('A8', 'Subject');
        $document->setCellValue('B8', 'Score');
        $document->setCellValue('C8', 'Grade');
        $document->setCellValue('D8', 'Date');
        
        // Style header
        $document->getStyle('A8:D8')->getFont()->setBold(true);
        
        // Add table data
        $row = 9;
        foreach ($data as $record) {
            $document->setCellValue('A' . $row, $record['subject_name']);
            $document->setCellValue('B' . $row, $record['score']);
            $document->setCellValue('C' . $row, $record['grade']);
            $document->setCellValue('D' . $row, $record['exam_date']);
            $row++;
        }
    }
}

function addAttendanceReportContent($document, $data) {
    if ($document instanceof TCPDF) {
        // Add table header
        $document->SetFont('helvetica', 'B', 12);
        $document->Cell(60, 7, 'Date', 1);
        $document->Cell(60, 7, 'Status', 1);
        $document->Cell(60, 7, 'Remarks', 1);
        $document->Ln();
        
        // Add table data
        $document->SetFont('helvetica', '', 12);
        foreach ($data as $record) {
            $document->Cell(60, 7, $record['date'], 1);
            $document->Cell(60, 7, $record['status'], 1);
            $document->Cell(60, 7, $record['remarks'], 1);
            $document->Ln();
        }
    } else {
        // Add table header
        $document->setCellValue('A8', 'Date');
        $document->setCellValue('B8', 'Status');
        $document->setCellValue('C8', 'Remarks');
        
        // Style header
        $document->getStyle('A8:C8')->getFont()->setBold(true);
        
        // Add table data
        $row = 9;
        foreach ($data as $record) {
            $document->setCellValue('A' . $row, $record['date']);
            $document->setCellValue('B' . $row, $record['status']);
            $document->setCellValue('C' . $row, $record['remarks']);
            $row++;
        }
    }
}

function addHealthReportContent($document, $data) {
    if ($document instanceof TCPDF) {
        // Add table header
        $document->SetFont('helvetica', 'B', 12);
        $document->Cell(40, 7, 'Date', 1);
        $document->Cell(40, 7, 'Status', 1);
        $document->Cell(60, 7, 'Description', 1);
        $document->Cell(50, 7, 'Action Taken', 1);
        $document->Ln();
        
        // Add table data
        $document->SetFont('helvetica', '', 12);
        foreach ($data as $record) {
            $document->Cell(40, 7, $record['record_date'], 1);
            $document->Cell(40, 7, $record['health_status'], 1);
            $document->Cell(60, 7, $record['description'], 1);
            $document->Cell(50, 7, $record['action_taken'], 1);
            $document->Ln();
        }
    } else {
        // Add table header
        $document->setCellValue('A8', 'Date');
        $document->setCellValue('B8', 'Status');
        $document->setCellValue('C8', 'Description');
        $document->setCellValue('D8', 'Action Taken');
        
        // Style header
        $document->getStyle('A8:D8')->getFont()->setBold(true);
        
        // Add table data
        $row = 9;
        foreach ($data as $record) {
            $document->setCellValue('A' . $row, $record['record_date']);
            $document->setCellValue('B' . $row, $record['health_status']);
            $document->setCellValue('C' . $row, $record['description']);
            $document->setCellValue('D' . $row, $record['action_taken']);
            $row++;
        }
    }
}

function addDisciplineReportContent($document, $data) {
    if ($document instanceof TCPDF) {
        // Add table header
        $document->SetFont('helvetica', 'B', 12);
        $document->Cell(40, 7, 'Date', 1);
        $document->Cell(40, 7, 'Type', 1);
        $document->Cell(60, 7, 'Description', 1);
        $document->Cell(50, 7, 'Action Taken', 1);
        $document->Ln();
        
        // Add table data
        $document->SetFont('helvetica', '', 12);
        foreach ($data as $record) {
            $document->Cell(40, 7, $record['incident_date'], 1);
            $document->Cell(40, 7, $record['incident_type'], 1);
            $document->Cell(60, 7, $record['description'], 1);
            $document->Cell(50, 7, $record['action_taken'], 1);
            $document->Ln();
        }
    } else {
        // Add table header
        $document->setCellValue('A8', 'Date');
        $document->setCellValue('B8', 'Type');
        $document->setCellValue('C8', 'Description');
        $document->setCellValue('D8', 'Action Taken');
        
        // Style header
        $document->getStyle('A8:D8')->getFont()->setBold(true);
        
        // Add table data
        $row = 9;
        foreach ($data as $record) {
            $document->setCellValue('A' . $row, $record['incident_date']);
            $document->setCellValue('B' . $row, $record['incident_type']);
            $document->setCellValue('C' . $row, $record['description']);
            $document->setCellValue('D' . $row, $record['action_taken']);
            $row++;
        }
    }
}

function addCoCurricularReportContent($document, $data) {
    if ($document instanceof TCPDF) {
        // Add table header
        $document->SetFont('helvetica', 'B', 12);
        $document->Cell(50, 7, 'Activity', 1);
        $document->Cell(30, 7, 'Start Date', 1);
        $document->Cell(30, 7, 'End Date', 1);
        $document->Cell(40, 7, 'Role', 1);
        $document->Cell(40, 7, 'Performance', 1);
        $document->Ln();
        
        // Add table data
        $document->SetFont('helvetica', '', 12);
        foreach ($data as $record) {
            $document->Cell(50, 7, $record['activity_name'], 1);
            $document->Cell(30, 7, $record['start_date'], 1);
            $document->Cell(30, 7, $record['end_date'], 1);
            $document->Cell(40, 7, $record['role'], 1);
            $document->Cell(40, 7, $record['performance_notes'], 1);
            $document->Ln();
        }
    } else {
        // Add table header
        $document->setCellValue('A8', 'Activity');
        $document->setCellValue('B8', 'Start Date');
        $document->setCellValue('C8', 'End Date');
        $document->setCellValue('D8', 'Role');
        $document->setCellValue('E8', 'Performance');
        
        // Style header
        $document->getStyle('A8:E8')->getFont()->setBold(true);
        
        // Add table data
        $row = 9;
        foreach ($data as $record) {
            $document->setCellValue('A' . $row, $record['activity_name']);
            $document->setCellValue('B' . $row, $record['start_date']);
            $document->setCellValue('C' . $row, $record['end_date']);
            $document->setCellValue('D' . $row, $record['role']);
            $document->setCellValue('E' . $row, $record['performance_notes']);
            $row++;
        }
    }
}

function addFinancialReportContent($document, $data) {
    if ($document instanceof TCPDF) {
        // Add table header
        $document->SetFont('helvetica', 'B', 12);
        $document->Cell(40, 7, 'Type', 1);
        $document->Cell(30, 7, 'Amount', 1);
        $document->Cell(30, 7, 'Due Date', 1);
        $document->Cell(30, 7, 'Payment Date', 1);
        $document->Cell(30, 7, 'Status', 1);
        $document->Cell(30, 7, 'Description', 1);
        $document->Ln();
        
        // Add table data
        $document->SetFont('helvetica', '', 12);
        foreach ($data as $record) {
            $document->Cell(40, 7, $record['transaction_type'], 1);
            $document->Cell(30, 7, $record['amount'], 1);
            $document->Cell(30, 7, $record['due_date'], 1);
            $document->Cell(30, 7, $record['payment_date'], 1);
            $document->Cell(30, 7, $record['status'], 1);
            $document->Cell(30, 7, $record['description'], 1);
            $document->Ln();
        }
    } else {
        // Add table header
        $document->setCellValue('A8', 'Type');
        $document->setCellValue('B8', 'Amount');
        $document->setCellValue('C8', 'Due Date');
        $document->setCellValue('D8', 'Payment Date');
        $document->setCellValue('E8', 'Status');
        $document->setCellValue('F8', 'Description');
        
        // Style header
        $document->getStyle('A8:F8')->getFont()->setBold(true);
        
        // Add table data
        $row = 9;
        foreach ($data as $record) {
            $document->setCellValue('A' . $row, $record['transaction_type']);
            $document->setCellValue('B' . $row, $record['amount']);
            $document->setCellValue('C' . $row, $record['due_date']);
            $document->setCellValue('D' . $row, $record['payment_date']);
            $document->setCellValue('E' . $row, $record['status']);
            $document->setCellValue('F' . $row, $record['description']);
            $row++;
        }
    }
}

function addPassoutReportContent($document, $data) {
    if ($document instanceof TCPDF) {
        // Add table header
        $document->SetFont('helvetica', 'B', 12);
        $document->Cell(60, 7, 'Student', 1);
        $document->Cell(40, 7, 'Passout Date', 1);
        $document->Cell(50, 7, 'Reason', 1);
        $document->Cell(40, 7, 'Final Remarks', 1);
        $document->Ln();
        
        // Add table data
        $document->SetFont('helvetica', '', 12);
        foreach ($data as $record) {
            $document->Cell(60, 7, $record['student_name'], 1);
            $document->Cell(40, 7, $record['passout_date'], 1);
            $document->Cell(50, 7, $record['reason'], 1);
            $document->Cell(40, 7, $record['final_remarks'], 1);
            $document->Ln();
        }
    } else {
        // Add table header
        $document->setCellValue('A8', 'Student');
        $document->setCellValue('B8', 'Passout Date');
        $document->setCellValue('C8', 'Reason');
        $document->setCellValue('D8', 'Final Remarks');
        
        // Style header
        $document->getStyle('A8:D8')->getFont()->setBold(true);
        
        // Add table data
        $row = 9;
        foreach ($data as $record) {
            $document->setCellValue('A' . $row, $record['student_name']);
            $document->setCellValue('B' . $row, $record['passout_date']);
            $document->setCellValue('C' . $row, $record['reason']);
            $document->setCellValue('D' . $row, $record['final_remarks']);
            $row++;
        }
    }
}
?> 