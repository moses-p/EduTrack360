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

// Validate required parameters
$required_params = ['report_type'];
$missing_params = [];

foreach ($required_params as $param) {
    if (!isset($_GET[$param]) || empty($_GET[$param])) {
        $missing_params[] = $param;
    }
}

if (!empty($missing_params)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: ' . implode(', ', $missing_params)]);
    exit();
}

// Sanitize inputs
$report_type = sanitizeInput($_GET['report_type']);
$class_id = isset($_GET['class_id']) ? sanitizeInput($_GET['class_id']) : null;
$subject_id = isset($_GET['subject_id']) ? sanitizeInput($_GET['subject_id']) : null;
$student_id = isset($_GET['student_id']) ? sanitizeInput($_GET['student_id']) : null;
$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;
$format = isset($_GET['format']) ? sanitizeInput($_GET['format']) : 'pdf';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Generate report based on type
try {
    switch ($report_type) {
        case 'academic':
            generateAcademicReport($conn, $class_id, $subject_id, $student_id, $format);
            break;
        case 'attendance':
            generateAttendanceReport($conn, $class_id, $student_id, $start_date, $end_date, $format);
            break;
        case 'behavior':
            generateBehaviorReport($conn, $class_id, $student_id, $start_date, $end_date, $format);
            break;
        case 'progress':
            generateProgressReport($conn, $class_id, $student_id, $format);
            break;
        default:
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid report type']);
            exit();
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}

// Function to generate academic performance report
function generateAcademicReport($conn, $class_id, $subject_id, $student_id, $format) {
    // Set appropriate headers for the report format
    if ($format === 'pdf') {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="termly_academic_report.pdf"');
    } elseif ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="termly_academic_report.xls"');
    } else {
        header('Content-Type: application/json');
    }
    
    // Get term parameter or default to current term
    $term = isset($_GET['term']) ? intval($_GET['term']) : null;
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // If term is not specified, show error
    if (!$term) {
        throw new Exception("Term must be specified for termly reports");
    }
    
    // Build report data
    $report_data = [];
    
    // If specific student is selected, get only that student's data
    if ($student_id) {
        // Get student details
        $stmt = $conn->prepare("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        // Get student's exam results for the specified term and year
        $params = [$student_id, $term, $year];
        $sql = "
            SELECT 
                er.*, 
                s.name as subject_name, 
                s.code as subject_code,
                c.name as class_name
            FROM 
                exam_results er
            JOIN 
                subjects s ON er.subject_id = s.id
            JOIN 
                classes c ON er.class_id = c.id
            WHERE 
                er.student_id = ? 
                AND er.term = ? 
                AND er.year = ?
        ";
        
        // Add subject filter if provided
        if ($subject_id) {
            $sql .= " AND er.subject_id = ?";
            $params[] = $subject_id;
        }
        
        // Add class filter if provided
        if ($class_id) {
            $sql .= " AND er.class_id = ?";
            $params[] = $class_id;
        }
        
        $sql .= " ORDER BY s.name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate overall performance
        $total_marks = 0;
        $subject_count = count($results);
        foreach ($results as $result) {
            $total_marks += $result['marks'];
        }
        $average_marks = $subject_count > 0 ? $total_marks / $subject_count : 0;
        
        // Get class position
        $position = getStudentPosition($conn, $student_id, $class_id, $term, $year);
        
        // Add to report data
        $report_data['student'] = $student;
        $report_data['results'] = $results;
        $report_data['term'] = $term;
        $report_data['year'] = $year;
        $report_data['total_marks'] = $total_marks;
        $report_data['average_marks'] = $average_marks;
        $report_data['position'] = $position;
        $report_data['grade'] = calculateOverallGrade($average_marks);
        $report_data['remarks'] = generateRemarks($average_marks);
        
        // Generate HTML for the report
        $html = generateAcademicReportHTML($report_data);
        
        if ($format === 'pdf') {
            // For demonstration purposes, just output the HTML
            // In a real implementation, you would use a library like TCPDF or mPDF
            echo $html;
        } elseif ($format === 'excel') {
            // For demonstration purposes, output a simple Excel file
            generateExcelFile($report_data);
        } else {
            // JSON output
            echo json_encode($report_data);
        }
    } else if ($class_id) {
        // Get all students for the specified class
        $stmt = $conn->prepare("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.class_id = ? ORDER BY s.full_name");
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get class name
        $stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            throw new Exception("Class not found");
        }
        
        $report_data['class'] = $class;
        $report_data['term'] = $term;
        $report_data['year'] = $year;
        $report_data['students'] = [];
        
        // Get exam results for each student
        foreach ($students as $student) {
            $student_data = [
                'student' => $student,
                'results' => []
            ];
            
            $params = [$student['id'], $term, $year];
            $sql = "
                SELECT 
                    er.*, 
                    s.name as subject_name, 
                    s.code as subject_code
                FROM 
                    exam_results er
                JOIN 
                    subjects s ON er.subject_id = s.id
                WHERE 
                    er.student_id = ? 
                    AND er.term = ? 
                    AND er.year = ?
            ";
            
            // Add subject filter if provided
            if ($subject_id) {
                $sql .= " AND er.subject_id = ?";
                $params[] = $subject_id;
            }
            
            $sql .= " ORDER BY s.name ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate overall performance
            $total_marks = 0;
            $subject_count = count($results);
            foreach ($results as $result) {
                $total_marks += $result['marks'];
            }
            $average_marks = $subject_count > 0 ? $total_marks / $subject_count : 0;
            
            // Get student position
            $position = getStudentPosition($conn, $student['id'], $class_id, $term, $year);
            
            $student_data['results'] = $results;
            $student_data['total_marks'] = $total_marks;
            $student_data['average_marks'] = $average_marks;
            $student_data['position'] = $position;
            $student_data['grade'] = calculateOverallGrade($average_marks);
            $student_data['remarks'] = generateRemarks($average_marks);
            
            $report_data['students'][] = $student_data;
        }
        
        // Sort students by position
        usort($report_data['students'], function($a, $b) {
            return $a['position'] - $b['position'];
        });
        
        // Generate HTML for the class report
        $html = generateClassReportHTML($report_data);
        
        if ($format === 'pdf') {
            // For demonstration purposes, just output the HTML
            echo $html;
        } elseif ($format === 'excel') {
            // For demonstration purposes, output a simple Excel file
            generateClassExcelFile($report_data);
        } else {
            // JSON output
            echo json_encode($report_data);
        }
    } else {
        throw new Exception("Either student_id or class_id must be specified");
    }
}

// Helper function to get student position
function getStudentPosition($conn, $student_id, $class_id, $term, $year) {
    // Get all students in the class with their average marks
    $stmt = $conn->prepare("
        SELECT 
            er.student_id,
            AVG(er.marks) as average_marks
        FROM 
            exam_results er
        WHERE 
            er.class_id = ? 
            AND er.term = ? 
            AND er.year = ?
        GROUP BY 
            er.student_id
        ORDER BY 
            average_marks DESC
    ");
    $stmt->execute([$class_id, $term, $year]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Find the position of the student
    $position = 1;
    foreach ($results as $index => $result) {
        if ($result['student_id'] == $student_id) {
            // If there are tied scores, they get the same position
            if ($index > 0 && $result['average_marks'] == $results[$index - 1]['average_marks']) {
                $position = getPositionForStudent($results, $index);
            } else {
                $position = $index + 1;
            }
            break;
        }
    }
    
    return $position;
}

// Helper function to get position considering ties
function getPositionForStudent($results, $currentIndex) {
    $currentScore = $results[$currentIndex]['average_marks'];
    
    // Look backward to find the first student with a different score
    for ($i = $currentIndex - 1; $i >= 0; $i--) {
        if ($results[$i]['average_marks'] != $currentScore) {
            // Return position based on the next student after the different score
            return $i + 2;
        }
    }
    
    // If all previous students have the same score, position is 1
    return 1;
}

// Helper function to calculate overall grade
function calculateOverallGrade($average_marks) {
    if ($average_marks >= 80) return 'A';
    if ($average_marks >= 70) return 'B';
    if ($average_marks >= 60) return 'C';
    if ($average_marks >= 50) return 'D';
    return 'F';
}

// Helper function to generate HTML for academic report
function generateAcademicReportHTML($data) {
    $student = $data['student'];
    $results = $data['results'];
    $term = $data['term'];
    $year = $data['year'];
    $total_marks = $data['total_marks'];
    $average_marks = $data['average_marks'];
    $position = $data['position'];
    $grade = $data['grade'];
    $remarks = $data['remarks'];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Termly Academic Report</title>
        <style>
            @page {
                size: A4;
                margin: 1cm;
            }
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 0;
                width: 21cm;
                height: 29.7cm;
                background-color: white;
            }
            .report-container {
                padding: 20px;
            }
            .report-header { text-align: center; margin-bottom: 20px; }
            .school-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .report-title { font-size: 18px; margin-bottom: 5px; }
            .term-info { font-size: 16px; margin-bottom: 20px; }
            .student-info { margin-bottom: 20px; }
            .student-info table { width: 100%; }
            .student-info td { padding: 5px; }
            .results-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .results-table th, .results-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
            .results-table th { background-color: #f2f2f2; }
            .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .summary-table th, .summary-table td { border: 1px solid #ddd; padding: 8px; }
            .summary-table th { text-align: right; width: 30%; background-color: #f2f2f2; }
            .summary-table td { text-align: left; }
            .remarks { margin-bottom: 20px; }
            .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
            .signature { width: 30%; text-align: center; }
            .signature-line { border-top: 1px solid #000; padding-top: 5px; }
            @media print {
                body {
                    width: 21cm;
                    height: 29.7cm;
                }
            }
        </style>
    </head>
    <body>
        <div class="report-container">
            <div class="report-header">
                <div class="school-name">EduTrack360 School</div>
                <div class="report-title">Termly Academic Report Card</div>
                <div class="term-info">Term ' . $term . ' - ' . $year . '</div>
            </div>
            
            <div class="student-info">
                <table>
                    <tr>
                        <td><strong>Student Name:</strong> ' . htmlspecialchars($student['full_name']) . '</td>
                        <td><strong>Admission No:</strong> ' . htmlspecialchars($student['admission_number']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Class:</strong> ' . htmlspecialchars($student['class_name']) . '</td>
                        <td><strong>Position:</strong> ' . htmlspecialchars($position) . '</td>
                    </tr>
                </table>
            </div>
            
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (count($results) > 0) {
        foreach ($results as $result) {
            $subject_grade = calculateGrade($result['marks']);
            $subject_remarks = generateSubjectRemarks($result['marks']);
            
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($result['subject_name']) . '</td>
                    <td>' . htmlspecialchars($result['marks']) . '</td>
                    <td>' . htmlspecialchars($subject_grade) . '</td>
                    <td>' . htmlspecialchars($subject_remarks) . '</td>
                </tr>';
        }
    } else {
        $html .= '
                <tr>
                    <td colspan="4" style="text-align: center;">No exam results found for this term</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="summary">
            <h3>Performance Summary</h3>
            <table class="summary-table">
                <tr>
                    <th>Total Marks:</th>
                    <td>' . htmlspecialchars($total_marks) . '</td>
                </tr>
                <tr>
                    <th>Average Marks:</th>
                    <td>' . htmlspecialchars(number_format($average_marks, 1)) . '</td>
                </tr>
                <tr>
                    <th>Overall Grade:</th>
                    <td>' . htmlspecialchars($grade) . '</td>
                </tr>
                <tr>
                    <th>Position in Class:</th>
                    <td>' . htmlspecialchars($position) . '</td>
                </tr>
            </table>
        </div>
        
        <div class="remarks">
            <h3>Teacher\'s Remarks</h3>
            <p>' . htmlspecialchars($remarks) . '</p>
        </div>
        
        <div class="signatures">
            <div class="signature">
                <div class="signature-line">Class Teacher</div>
            </div>
            <div class="signature">
                <div class="signature-line">Principal</div>
            </div>
            <div class="signature">
                <div class="signature-line">Parent</div>
            </div>
        </div>
    </div>
    </body>
    </html>';
    
    return $html;
}

// Helper function to generate HTML for class report
function generateClassReportHTML($data) {
    $class = $data['class'];
    $term = $data['term'];
    $year = $data['year'];
    $students = $data['students'];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Class Termly Report</title>
        <style>
            @page {
                size: A4;
                margin: 1cm;
            }
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 0;
                width: 21cm;
                height: 29.7cm;
                background-color: white;
            }
            .report-container {
                padding: 20px;
            }
            .report-header { text-align: center; margin-bottom: 20px; }
            .school-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .report-title { font-size: 18px; margin-bottom: 5px; }
            .term-info { font-size: 16px; margin-bottom: 20px; }
            .results-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .results-table th, .results-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
            .results-table th { background-color: #f2f2f2; }
            .page-break { page-break-after: always; }
            .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
            .signature { width: 30%; text-align: center; }
            .signature-line { border-top: 1px solid #000; padding-top: 5px; }
            @media print {
                body {
                    width: 21cm;
                    height: 29.7cm;
                }
            }
        </style>
    </head>
    <body>
        <div class="report-container">
            <div class="report-header">
                <div class="school-name">EduTrack360 School</div>
                <div class="report-title">Class Performance Report</div>
                <div class="term-info">Class: ' . htmlspecialchars($class['name']) . ' - Term ' . $term . ' - ' . $year . '</div>
            </div>
            
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Student Name</th>
                        <th>Admission No</th>
                        <th>Total Marks</th>
                        <th>Average</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (count($students) > 0) {
        foreach ($students as $student_data) {
            $student = $student_data['student'];
            
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($student_data['position']) . '</td>
                    <td>' . htmlspecialchars($student['full_name']) . '</td>
                    <td>' . htmlspecialchars($student['admission_number']) . '</td>
                    <td>' . htmlspecialchars($student_data['total_marks']) . '</td>
                    <td>' . htmlspecialchars(number_format($student_data['average_marks'], 1)) . '</td>
                    <td>' . htmlspecialchars($student_data['grade']) . '</td>
                </tr>';
        }
    } else {
        $html .= '
                <tr>
                    <td colspan="6" style="text-align: center;">No student records found for this class</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="signatures">
            <div class="signature">
                <div class="signature-line">Principal</div>
            </div>
        </div>
    </div>
    </body>
    </html>';
    
    return $html;
}

// Helper function to generate subject-specific remarks
function generateSubjectRemarks($marks) {
    if ($marks >= 80) return "Excellent";
    if ($marks >= 70) return "Very Good";
    if ($marks >= 60) return "Good";
    if ($marks >= 50) return "Satisfactory";
    if ($marks >= 40) return "Fair";
    return "Needs Improvement";
}

// Generate Excel file for individual student
function generateExcelFile($data) {
    $student = $data['student'];
    $results = $data['results'];
    $term = $data['term'];
    $year = $data['year'];
    
    // Create Excel content
    $excel = "EduTrack360 School\nTermly Academic Report\n";
    $excel .= "Term {$term} - {$year}\n\n";
    $excel .= "Student Name: {$student['full_name']}\n";
    $excel .= "Admission No: {$student['admission_number']}\n";
    $excel .= "Class: {$student['class_name']}\n";
    $excel .= "Position: {$data['position']}\n\n";
    
    $excel .= "Subject\tMarks\tGrade\tRemarks\n";
    
    foreach ($results as $result) {
        $subject_grade = calculateGrade($result['marks']);
        $subject_remarks = generateSubjectRemarks($result['marks']);
        
        $excel .= "{$result['subject_name']}\t{$result['marks']}\t{$subject_grade}\t{$subject_remarks}\n";
    }
    
    $excel .= "\nPerformance Summary\n";
    $excel .= "Total Marks: {$data['total_marks']}\n";
    $excel .= "Average Marks: " . number_format($data['average_marks'], 1) . "\n";
    $excel .= "Overall Grade: {$data['grade']}\n";
    $excel .= "Position in Class: {$data['position']}\n\n";
    $excel .= "Teacher's Remarks: {$data['remarks']}\n";
    
    echo $excel;
}

// Generate Excel file for class report
function generateClassExcelFile($data) {
    $class = $data['class'];
    $term = $data['term'];
    $year = $data['year'];
    $students = $data['students'];
    
    // Create Excel content
    $excel = "EduTrack360 School\nClass Performance Report\n";
    $excel .= "Class: {$class['name']} - Term {$term} - {$year}\n\n";
    
    $excel .= "Position\tStudent Name\tAdmission No\tTotal Marks\tAverage\tGrade\n";
    
    foreach ($students as $student_data) {
        $student = $student_data['student'];
        $position = $student_data['position'];
        $total_marks = $student_data['total_marks'];
        $average = number_format($student_data['average_marks'], 1);
        $grade = $student_data['grade'];
        
        $excel .= "{$position}\t{$student['full_name']}\t{$student['admission_number']}\t{$total_marks}\t{$average}\t{$grade}\n";
    }
    
    echo $excel;
}

// Function to generate attendance report
function generateAttendanceReport($conn, $class_id, $student_id, $start_date, $end_date, $format) {
    // Implementation for attendance report
    // This is a placeholder - implement similar to academic report
    echo "Attendance report generated. This feature is under development.";
}

// Function to generate behavior report
function generateBehaviorReport($conn, $class_id, $student_id, $start_date, $end_date, $format) {
    // Implementation for behavior report
    // This is a placeholder - implement similar to academic report
    echo "Behavior report generated. This feature is under development.";
}

// Function to generate progress report
function generateProgressReport($conn, $class_id, $student_id, $format) {
    // Implementation for progress report
    // This is a placeholder - implement similar to academic report
    echo "Progress report generated. This feature is under development.";
}
?> 