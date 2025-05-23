<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Debug mode: set to true to enable forced login for testing
$debug_mode = true;

// Force login for debugging if needed
if ($debug_mode && (!isset($_SESSION['user_id']) || !isset($_SESSION['role']))) {
    $_SESSION['user_id'] = 1; // Admin user ID
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && basename($_SERVER['SCRIPT_NAME']) != 'login.php') {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Get user role
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Include header
include 'views/includes/header.php';

// Route handling
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

switch ($page) {
    case 'dashboard':
        // Load dashboard based on user role
        if ($user_role == 'admin') {
            include 'views/admin/dashboard.php';
        } elseif ($user_role == 'teacher') {
            include 'views/teacher/dashboard.php';
        } elseif ($user_role == 'ceo') {
            include 'views/ceo/dashboard.php';
        } elseif ($user_role == 'parent') {
            // Load the parent dashboard view
            include 'views/parent/dashboard.php'; 
        } elseif ($user_role == 'staff') {
            // Load the staff dashboard
            include 'views/staff/dashboard.php';
        } else {
            // Default dashboard or error page if role is unknown
            include 'views/error.php';
        }
        break;
    case 'marks':
    case 'marks_entry':
        if ($user_role == 'teacher') {
            include 'views/teacher/marks_entry.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'attendance':
        if ($user_role == 'teacher') {
            include 'views/teacher/attendance.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'students':
        if ($user_role == 'teacher') {
            include 'views/teacher/students.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'reports':
        if ($user_role == 'parent' || $user_role == 'admin' || $user_role == 'teacher') {
            include 'views/reports.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'report_view':
        if ($user_role == 'parent' || $user_role == 'admin' || $user_role == 'teacher') {
            include 'views/report_view.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'users':
        if ($user_role == 'admin') {
            include 'views/admin/users.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'admin':
        if ($user_role == 'admin') {
            include 'views/admin/dashboard.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'classes':
        if ($user_role == 'admin') {
            include 'views/admin/classes.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'subjects':
        if ($user_role == 'admin') {
            include 'views/admin/subjects.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'ocr_analysis':
        if ($user_role == 'admin') {
            include 'views/admin/ocr_accuracy.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'tasks':
        if ($user_role == 'admin') {
            include 'views/admin/tasks.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'system_logs':
        if ($user_role == 'admin') {
            include 'views/admin/system_logs.php';
        } else {
            include 'views/error.php';
        }
        break;
    case 'settings':
        include 'views/settings.php';
        break;
    case 'profile':
        include 'views/profile.php';
        break;
    default:
        include 'views/error.php';
}

// Display the content generated by the included view
if (isset($content)) {
    echo $content;
} else {
    // Optional: Handle cases where $content might not be set by the included file
    echo '<div class="alert alert-warning">Content for this page could not be loaded.</div>';
}

// Include footer
include 'views/includes/footer.php';
?> 