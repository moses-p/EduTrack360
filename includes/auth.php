<?php
/**
 * Authentication functions for the Rhema app
 */

// Check if a user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if a user has a specific role
function has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    
    if (is_array($role)) {
        return in_array($_SESSION['role'], $role);
    }
    
    return $_SESSION['role'] === $role;
}

// Check if a user is an admin
function is_admin() {
    return has_role('admin');
}

// Check if a user is a teacher
function is_teacher() {
    return has_role('teacher');
}

// Check if a user is a student
function is_student() {
    return has_role('student');
}

// Check if a user is a parent
function is_parent() {
    return has_role('parent');
}

// Require login or redirect
function require_login() {
    if (!is_logged_in()) {
        header("Location: /rhema/login.php");
        exit;
    }
}

// Require a specific role or redirect
function require_role($role) {
    require_login();
    
    if (!has_role($role)) {
        header("Location: /rhema/index.php?error=unauthorized");
        exit;
    }
}

// Get the current user's ID
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Get the current user's role
function get_user_role() {
    return $_SESSION['role'] ?? null;
}
?> 