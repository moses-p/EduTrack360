<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#007bff">
    <meta name="description" content="EduTrack360 Performance Management System">
    <title><?php echo $page_title ?? 'EduTrack360'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet"><!-- Custom styles to fix layout issues -->
    
    <!-- PWA Support -->
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="EduTrack360">
</head>
<body class="bg-light">
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark text-white">
            <div class="sidebar-header d-flex align-items-center justify-content-between p-3">
                <h3 class="mb-0">EduTrack360</h3>
                <button type="button" id="sidebarCollapse" class="btn btn-dark d-md-none">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <ul class="list-unstyled components">
                <?php if (isset($sidebar_items)): ?>
                    <?php foreach ($sidebar_items as $item): ?>
                        <li>
                            <a href="<?php echo $item['url']; ?>" class="<?php echo $item['active'] ? 'active' : ''; ?>">
                                <i class="bi <?php echo $item['icon']; ?>"></i>
                                <span><?php echo $item['text']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button type="button" id="sidebarToggle" class="btn btn-dark d-md-none">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-link text-dark dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i>
                                <span class="d-none d-md-inline"><?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="index.php?page=profile"><i class="bi bi-person"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="index.php?page=settings"><i class="bi bi-gear"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid p-3 p-md-4">
                <?php if (isset($page_title)): ?>
                    <h2 class="mb-4 animate__animated animate__fadeIn"><?php echo $page_title; ?></h2>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="animate__animated animate__fadeIn">
                    <?php echo $content ?? ''; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="position-fixed top-50 start-50 translate-middle d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js is already included in footer.php -->
    <script src="assets/js/main.js"></script>
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 