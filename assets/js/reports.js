document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const reportPeriod = document.getElementById('reportPeriod');
    
    if (startDate && endDate && reportPeriod) {
        // Set default dates
        const today = new Date();
        startDate.value = today.toISOString().split('T')[0];
        endDate.value = today.toISOString().split('T')[0];
        
        // Handle period change
        reportPeriod.addEventListener('change', function() {
            const today = new Date();
            let start = new Date();
            let end = new Date();
            
            switch(this.value) {
                case 'daily':
                    // Same day
                    break;
                case 'weekly':
                    // Start of week to end of week
                    start.setDate(today.getDate() - today.getDay());
                    end.setDate(start.getDate() + 6);
                    break;
                case 'monthly':
                    // Start of month to end of month
                    start.setDate(1);
                    end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
                    break;
                case 'yearly':
                    // Start of year to end of year
                    start = new Date(today.getFullYear(), 0, 1);
                    end = new Date(today.getFullYear(), 11, 31);
                    break;
                case 'custom':
                    // Don't set dates for custom range
                    return;
            }
            
            startDate.value = start.toISOString().split('T')[0];
            endDate.value = end.toISOString().split('T')[0];
        });
    }
    
    // Handle report type change
    const reportType = document.getElementById('reportType');
    if (reportType) {
        reportType.addEventListener('change', function() {
            // Show/hide relevant form fields based on report type
            const classField = document.getElementById('classId');
            const studentField = document.getElementById('studentId');
            
            // Load classes based on report type
            if (classField) {
                loadClasses(this.value);
            }
            
            // Load students if class is selected
            if (studentField && classField.value) {
                loadStudents(classField.value);
            }
        });
    }
    
    // Handle class change
    const classSelect = document.getElementById('classId');
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const studentSelect = document.getElementById('studentId');
            if (studentSelect && this.value) {
                loadStudents(this.value);
            }
        });
    }
    
    // Handle form submission
    const reportForm = document.getElementById('generateReportForm');
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            generateReport(this);
        });
    }
    
    // Load recent reports
    loadRecentReports();
});

// Function to load classes
function loadClasses(reportType) {
    const classSelect = document.getElementById('classId');
    if (!classSelect) return;
    
    // Show loading state
    classSelect.innerHTML = '<option value="">Loading classes...</option>';
    
    // Fetch classes from API
    fetch('api/classes.php?report_type=' + reportType)
        .then(response => response.json())
        .then(data => {
            classSelect.innerHTML = '<option value="">Select Class</option>';
            data.forEach(classItem => {
                const option = document.createElement('option');
                option.value = classItem.id;
                option.textContent = classItem.name;
                classSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
        });
}

// Function to load students
function loadStudents(classId) {
    const studentSelect = document.getElementById('studentId');
    if (!studentSelect) return;
    
    // Show loading state
    studentSelect.innerHTML = '<option value="">Loading students...</option>';
    
    // Fetch students from API
    fetch('api/students.php?class_id=' + classId)
        .then(response => response.json())
        .then(data => {
            studentSelect.innerHTML = '<option value="">All Students (Class Report)</option>';
            data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = student.name;
                studentSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading students:', error);
            studentSelect.innerHTML = '<option value="">Error loading students</option>';
        });
}

// Function to generate report
function generateReport(form) {
    const formData = new FormData(form);
    const reportType = formData.get('report_type');
    const format = formData.get('format');
    
    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
    
    // Generate report via API
    fetch('api/generate_report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (format === 'online') {
            return response.json();
    } else {
            return response.blob();
        }
    })
    .then(data => {
        if (format === 'online') {
            // Redirect to report view
            window.location.href = 'index.php?page=report_view&id=' + data.report_id;
        } else {
            // Download file
            const url = window.URL.createObjectURL(data);
                const a = document.createElement('a');
                a.href = url;
            a.download = `${reportType}_report.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            a.remove();
        }
            })
            .catch(error => {
                console.error('Error generating report:', error);
        alert('Error generating report. Please try again.');
            })
            .finally(() => {
                // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
            });
}

// Function to load recent reports
function loadRecentReports() {
    const recentReportsTable = document.getElementById('recentReportsTable');
    if (!recentReportsTable) return;
    
    // Show loading state
    recentReportsTable.innerHTML = '<tr><td colspan="3" class="text-center">Loading recent reports...</td></tr>';
    
    // Fetch recent reports from API
    fetch('api/recent_reports.php')
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                recentReportsTable.innerHTML = '<tr><td colspan="3" class="text-center">No recent reports</td></tr>';
                return;
            }
            
            recentReportsTable.innerHTML = '';
            data.forEach(report => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${report.name}</td>
                    <td>${report.date}</td>
                    <td>
                        <a href="index.php?page=report_view&id=${report.id}" class="btn btn-sm btn-primary">
                            <i class="bi bi-eye"></i> View
                        </a>
                        </td>
                    `;
                recentReportsTable.appendChild(row);
                });
        })
        .catch(error => {
            console.error('Error loading recent reports:', error);
            recentReportsTable.innerHTML = '<tr><td colspan="3" class="text-center">Error loading reports</td></tr>';
        });
}

// Handle quick report clicks
document.querySelectorAll('[data-report]').forEach(element => {
    element.addEventListener('click', function(e) {
        e.preventDefault();
        const reportType = this.dataset.report;
        generateQuickReport(reportType);
    });
});

// Function to generate quick report
function generateQuickReport(reportType) {
    const today = new Date();
    let startDate, endDate;
    
    switch(reportType) {
        case 'daily_attendance':
            startDate = endDate = today.toISOString().split('T')[0];
            break;
        case 'weekly_health':
            startDate = new Date(today.setDate(today.getDate() - today.getDay())).toISOString().split('T')[0];
            endDate = new Date(today.setDate(today.getDate() + 6)).toISOString().split('T')[0];
            break;
        case 'monthly_finance':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
    }
    
    // Redirect to report generation with pre-filled parameters
    window.location.href = `index.php?page=reports&action=generate&type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
} 