<?php
// Set page title
$page_title = "View Report";

// Debug: Force role if needed
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Create a sample session for testing
    $_SESSION['user_id'] = 1; // Admin user ID
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
}

// Get report ID from URL
$report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;

if ($report_id <= 0) {
    // No valid report ID provided
    $content = <<<HTML
    <div class="alert alert-danger">
        <h4 class="alert-heading">Error</h4>
        <p>No valid report ID was provided.</p>
        <hr>
        <p class="mb-0">Please go back to the <a href="index.php?page=reports">Reports</a> page.</p>
    </div>
    HTML;
} else {
    // Set sidebar items (adjust based on user role)
    $sidebar_items = [
        ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
        ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => true],
        ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
    ];
    
    // Add page-specific scripts
    $page_scripts = ['assets/js/report-view.js'];
    
    // Generate report view content
    $content = <<<HTML
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Academic Report <span id="reportTitle"></span></h5>
            <div>
                <a href="api/download_report.php?report_id={$report_id}&format=pdf" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="bi bi-file-text"></i> View Printable Report
                </a>
                <a href="index.php?page=reports" class="btn btn-outline-secondary btn-sm ms-2">
                    <i class="bi bi-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>
        <div class="card-body" id="reportContent">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading report...</p>
            </div>
        </div>
    </div>
    
    <!-- Report Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load report data
        fetch('api/get_report.php?report_id={$report_id}')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load report');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayReport(data.report);
                } else {
                    showError(data.message || 'Failed to load report');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred while loading the report');
            });
        
        function displayReport(report) {
            // Set report title
            document.getElementById('reportTitle').textContent = report.name;
            
            // Build report content HTML
            const reportContent = document.getElementById('reportContent');
            
            let html = `
                <div class="report-header mb-4">
                    <h4 class="text-center">\${report.name}</h4>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <p><strong>Student:</strong> \${report.student_name}</p>
                            <p><strong>Class:</strong> \${report.class_name}</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p><strong>Term:</strong> \${report.term}</p>
                            <p><strong>Year:</strong> \${report.year}</p>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Marks</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            // If we have subject results
            if (report.subjects && report.subjects.length > 0) {
                report.subjects.forEach(subject => {
                    html += `
                        <tr>
                            <td>\${subject.name}</td>
                            <td>\${subject.marks}</td>
                            <td>\${subject.grade}</td>
                            <td>\${subject.remarks}</td>
                        </tr>
                    `;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="4" class="text-center">No subject data available</td>
                    </tr>
                `;
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
                
                <div class="report-summary p-3 border rounded bg-light mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Total Marks:</strong> \${report.total_marks}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Average:</strong> \${report.average_marks}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Position:</strong> \${report.position}</p>
                        </div>
                    </div>
                </div>
                
                <div class="report-remarks mb-4">
                    <h5>Teacher's Remarks</h5>
                    <p class="p-3 border rounded">\${report.remarks}</p>
                </div>
                
                <div class="report-signature mt-5 pt-4">
                    <div class="row">
                        <div class="col-md-6">
                            <p>____________________________<br>Class Teacher's Signature</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p>____________________________<br>Head Teacher's Signature</p>
                        </div>
                    </div>
                </div>
            `;
            
            reportContent.innerHTML = html;
        }
        
        function showError(message) {
            const reportContent = document.getElementById('reportContent');
            reportContent.innerHTML = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">Error</h5>
                    <p>\${message}</p>
                    <hr>
                    <p class="mb-0">Please go back to the <a href="index.php?page=reports">Reports</a> page.</p>
                </div>
            `;
        }
    });
    </script>
    HTML;
}
?> 