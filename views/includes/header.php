<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTrack360 - Performance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">EduTrack360</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">Dashboard</a>
                    </li>
                    <?php if ($_SESSION['role'] == 'teacher'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'marks') ? 'active' : ''; ?>" href="index.php?page=marks">Marks Entry</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] == 'parent'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="index.php?page=reports">Reports</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>" href="index.php?page=admin">Admin</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mt-4"> 