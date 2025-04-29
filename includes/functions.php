<?php
function calculateGrade($marks) {
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    return 'F';
}

function generateReport($student_id, $class_id, $term, $year) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all exam results for the student in the specified term
    $stmt = $conn->prepare("
        SELECT er.*, s.name as subject_name 
        FROM exam_results er 
        JOIN subjects s ON er.subject_id = s.id 
        WHERE er.student_id = ? AND er.class_id = ? AND er.term = ? AND er.year = ?
    ");
    $stmt->execute([$student_id, $class_id, $term, $year]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total and average marks
    $total_marks = 0;
    $subject_count = count($results);
    
    foreach ($results as $result) {
        $total_marks += $result['marks'];
    }
    
    $average_marks = $subject_count > 0 ? $total_marks / $subject_count : 0;
    
    // Get class position
    $stmt = $conn->prepare("
        SELECT student_id, AVG(marks) as avg_marks 
        FROM exam_results 
        WHERE class_id = ? AND term = ? AND year = ? 
        GROUP BY student_id 
        ORDER BY avg_marks DESC
    ");
    $stmt->execute([$class_id, $term, $year]);
    $class_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $position = 1;
    foreach ($class_results as $result) {
        if ($result['student_id'] == $student_id) {
            break;
        }
        $position++;
    }
    
    // Generate remarks based on performance
    $remarks = generateRemarks($average_marks);
    
    // Save report
    $stmt = $conn->prepare("
        INSERT INTO reports (student_id, class_id, term, year, total_marks, average_marks, position, remarks) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$student_id, $class_id, $term, $year, $total_marks, $average_marks, $position, $remarks]);
    
    return [
        'total_marks' => $total_marks,
        'average_marks' => $average_marks,
        'position' => $position,
        'remarks' => $remarks,
        'results' => $results
    ];
}

function generateRemarks($average_marks) {
    if ($average_marks >= 80) {
        return "Excellent performance! Keep up the good work.";
    } elseif ($average_marks >= 70) {
        return "Very good performance. Continue working hard.";
    } elseif ($average_marks >= 60) {
        return "Good performance. There's room for improvement.";
    } elseif ($average_marks >= 50) {
        return "Average performance. More effort needed.";
    } else {
        return "Below average performance. Please work harder.";
    }
}

function processScannedPaper($file_path, $teacher_id, $subject_id, $class_id, $exam_type, $term, $year) {
    // This function would integrate with Tesseract.js for OCR
    // For now, we'll just save the file information
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO scanned_papers (teacher_id, subject_id, class_id, exam_type, term, year, file_path) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$teacher_id, $subject_id, $class_id, $exam_type, $term, $year, $file_path]);
    
    return $conn->lastInsertId();
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isAuthorized($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $user_role = $_SESSION['role'];
    
    // Admin has access to everything
    if ($user_role == 'admin') {
        return true;
    }
    
    // Check if user's role matches required role
    return $user_role == $required_role;
}
?> 