<?php
$page_title = "Marks Entry";

// Set sidebar items
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => 'index.php?page=marks', 'text' => 'Marks Entry', 'icon' => 'bi-pencil-square', 'active' => true],
    ['url' => 'index.php?page=attendance', 'text' => 'Attendance', 'icon' => 'bi-calendar-check', 'active' => false],
    ['url' => 'index.php?page=students', 'text' => 'Students', 'icon' => 'bi-people', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../login.php?error=unauthorized");
    exit();
}

// Database connection (Assuming Database class is autoloaded or required)
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get teacher's subjects and classes (using simplified logic for one class/subject)
$teacher_class_id = null;
$teacher_subject_id = null;

try {
    // First check if this user_id exists in the teachers table
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacher) {
        // Now get their assigned subject and class
        $stmt = $conn->prepare("SELECT subject_id, class_id FROM teachers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $teacher_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher_info) {
            $teacher_class_id = $teacher_info['class_id'];
            $teacher_subject_id = $teacher_info['subject_id'];
            
            // Get subject name for display
            $stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
            $stmt->execute([$teacher_subject_id]);
            $subject = $stmt->fetch(PDO::FETCH_ASSOC);
            $subject_name = $subject ? $subject['name'] : 'Unknown Subject';
        } else {
            // Handle case where teacher info not found
            throw new Exception("Teacher information not found in database.");
        }
    } else {
        throw new Exception("Your user account is not linked to a teacher record.");
    }
} catch (PDOException $e) {
     die("Error fetching teacher info: " . $e->getMessage());
} catch (Exception $e) {
     die($e->getMessage());
}

// Pass necessary PHP variables to JavaScript
$js_vars = json_encode([
    'teacher_class_id' => $teacher_class_id,
    'teacher_subject_id' => $teacher_subject_id
]);

$content = <<<HTML
<div class="container-fluid">
    <div class="row">
        <!-- Manual / Combined Entry Column -->
        <div class="col-lg-7 col-md-12">
            <div class="card">
                 <div class="card-header">
                    <h5 class="card-title mb-0">Enter or Scan Student Marks</h5>
                </div>
                <div class="card-body">
                    <form id="marksEntryForm">
                        <!-- Hidden field for Subject ID pre-filled -->
                        <input type="hidden" id="subjectIdInput" name="subject_id" value="{$teacher_subject_id}"> 
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="studentSelect" class="form-label">Student</label>
                                <select class="form-select" id="studentSelect" name="student_id" required>
                                    <!-- Options loaded dynamically -->
                                    <option value="">Loading Students...</option>
                                </select>
                            </div>
                             <div class="col-md-6">
                                <label for="subjectDisplay" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subjectDisplay" value="{$subject_name}" readonly disabled>
                                <!-- Subject name will be loaded via JS -->
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="termInput" class="form-label">Term</label>
                                <select id="termInput" name="term" class="form-select" required>
                                    <option value="1">Term 1</option>
                                    <option value="2">Term 2</option>
                                    <option value="3">Term 3</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="yearInput" class="form-label">Year</label>
                                <input type="number" id="yearInput" name="year" class="form-control" value="{$current_year}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="marksInput" class="form-label">Marks</label>
                                <input type="number" class="form-control" id="marksInput" name="marks" min="0" max="100" required>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                             <button type="submit" class="btn btn-primary">Save Marks</button>
                             <span>Or use camera -></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Camera Scan Column -->
        <div class="col-lg-5 col-md-12">
             <div class="card">
                 <div class="card-header">
                    <h5 class="card-title mb-0">Camera Scanner</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Use the camera to quickly populate the marks field. Select student/term/year first if needed.</p>
                    <div class="mb-3">
                        <button id="startButton" class="btn btn-sm btn-outline-primary">Start Camera</button>
                        <button id="scanButton" class="btn btn-sm btn-success" disabled>Scan Mark</button>
                    </div>
                    
                    <div id="scannerContainer" class="mb-3" style="position: relative; max-width: 100%; margin: auto; display: none;">
                        <video id="videoFeed" width="100%" height="auto" autoplay playsinline style="border: 1px solid #eee;"></video>
                        <canvas id="canvasOverlay" style="position: absolute; top: 0; left: 0; display: none;"></canvas>
                    </div>
                    
                    <div id="resultContainer">
                        <h6>Scanning Progress:</h6>
                        <div class="progress mb-2" style="height: 10px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <h6>Last Scan Attempt:</h6>
                        <pre id="ocrResult" style="background: #f8f9fa; padding: 8px; border: 1px solid #eee; min-height: 60px; font-size: 0.8em; white-space: pre-wrap; word-wrap: break-word;">Awaiting scan...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div>
HTML;

// Set current year
$current_year = date('Y');

// Replace placeholders in HTML
$content = str_replace('{$teacher_subject_id}', htmlspecialchars($teacher_subject_id ?? ''), $content);
$content = str_replace('{$subject_name}', htmlspecialchars($subject_name ?? 'Not Assigned'), $content);
$content = str_replace('{$current_year}', $current_year, $content);

// Define the specific JS file for this page
$page_scripts = ['assets/js/marks-entry.js']; // Fixed path to the JS file

?>

<?php /* Entire inline script block removed
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Entry - EduTrack360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tesseract.js@2.1.0/dist/tesseract.min.js">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php echo $content; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@2.1.0/dist/tesseract.min.js"></script>
    <script>
        // Load students for the current class
        function loadStudents() { ... }

        // Handle paper scanning
        document.getElementById('scanForm').addEventListener('submit', function(e) { ... });

        // Handle manual marks entry
        document.getElementById('manualEntryForm').addEventListener('submit', function(e) { ... });

        // Load students when page loads
        loadStudents();
    </script>
</body>
</html>
*/ ?> 