document.addEventListener('DOMContentLoaded', function() {
    initReportsPage();
});

function initReportsPage() {
    // Initialize form handlers
    initGenerateReportForm();
    
    // Load existing reports if on the reports list page
    if (document.getElementById('reportsTableBody')) {
        loadRecentReports();
    }
    
    // Setup dependent dropdowns
    initDependentDropdowns();
}

function initGenerateReportForm() {
    const generateReportForm = document.getElementById('generateReportForm');
    if (generateReportForm) {
        generateReportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            generateReport(this);
        });
    }
}

function generateReport(form) {
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
    
    // Get form data
    const formData = new FormData(form);
    const reportFormat = formData.get('format');
    
    // Build query string from form data
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }
    
    if (reportFormat === 'online') {
        // For online viewing, redirect to report viewer page
        window.location.href = 'index.php?page=report_view&' + params.toString();
    } else {
        // For downloading, use the API endpoint
        fetch('api/generate_report.php?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Failed to generate report');
                    });
                }
                
                // Check content type for different formats
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    // It's an error response
                    return response.json().then(data => {
                        throw new Error(data.error || 'Failed to generate report');
                    });
                }
                
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `report_${formData.get('report_type')}_${new Date().toISOString().slice(0,10)}.${reportFormat}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Error generating report:', error);
                alert(error.message || 'Failed to generate report. Please try again.');
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
    }
}

function loadRecentReports() {
    const reportsTableBody = document.getElementById('reportsTableBody');
    if (!reportsTableBody) return;
    
    // Show loading state
    reportsTableBody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading reports...</td></tr>';
    
    // Fetch recent reports
    fetch('api/get_recent_reports.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.reports && data.reports.length > 0) {
                reportsTableBody.innerHTML = '';
                
                data.reports.forEach(report => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${report.name}</td>
                        <td><span class="badge bg-${getReportTypeBadgeColor(report.type)}">${report.type}</span></td>
                        <td>${formatDate(report.generated_date)}</td>
                        <td>${report.generated_by}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="index.php?page=report_view&report_id=${report.id}" class="btn btn-outline-primary">View</a>
                                <a href="api/download_report.php?report_id=${report.id}&format=pdf" target="_blank" class="btn btn-outline-secondary">Print</a>
                            </div>
                        </td>
                    `;
                    reportsTableBody.appendChild(row);
                });
            } else {
                reportsTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No reports found. Generate a new report to get started.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading reports:', error);
            reportsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load reports. Please try again.</td></tr>';
        });
}

function initDependentDropdowns() {
    const classSelect = document.getElementById('classId');
    const subjectSelect = document.getElementById('subjectId');
    const studentSelect = document.getElementById('studentId');
    
    if (classSelect) {
        // Load classes
        fetch('api/get_classes.php')
            .then(response => response.ok ? response.json() : Promise.reject('Failed to load classes'))
            .then(data => {
                if (data.classes && data.classes.length > 0) {
                    classSelect.innerHTML = '<option value="">All Classes</option>';
                    data.classes.forEach(cls => {
                        const option = document.createElement('option');
                        option.value = cls.id;
                        option.textContent = cls.name;
                        classSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading classes:', error));
            
        // Update subjects and students when class changes
        classSelect.addEventListener('change', function() {
            const classId = this.value;
            
            // Update subjects dropdown
            if (subjectSelect) {
                subjectSelect.innerHTML = '<option value="">Loading...</option>';
                subjectSelect.disabled = true;
                
                fetch(`api/get_subjects.php${classId ? '?class_id=' + classId : ''}`)
                    .then(response => response.ok ? response.json() : Promise.reject('Failed to load subjects'))
                    .then(data => {
                        subjectSelect.innerHTML = '<option value="">All Subjects</option>';
                        if (data.subjects && data.subjects.length > 0) {
                            data.subjects.forEach(subject => {
                                const option = document.createElement('option');
                                option.value = subject.id;
                                option.textContent = subject.name;
                                subjectSelect.appendChild(option);
                            });
                        }
                        subjectSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading subjects:', error);
                        subjectSelect.innerHTML = '<option value="">All Subjects</option>';
                        subjectSelect.disabled = false;
                    });
            }
            
            // Update students dropdown
            if (studentSelect) {
                studentSelect.innerHTML = '<option value="">Loading...</option>';
                studentSelect.disabled = true;
                
                fetch(`api/get_students.php${classId ? '?class_id=' + classId : ''}`)
                    .then(response => response.ok ? response.json() : Promise.reject('Failed to load students'))
                    .then(data => {
                        studentSelect.innerHTML = '<option value="">All Students</option>';
                        if (data.students && data.students.length > 0) {
                            data.students.forEach(student => {
                                const option = document.createElement('option');
                                option.value = student.id;
                                option.textContent = student.full_name;
                                studentSelect.appendChild(option);
                            });
                        }
                        studentSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading students:', error);
                        studentSelect.innerHTML = '<option value="">All Students</option>';
                        studentSelect.disabled = false;
                    });
            }
        });
    }
}

// Helper functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function getReportTypeBadgeColor(type) {
    switch (type) {
        case 'academic': return 'primary';
        case 'attendance': return 'success';
        case 'behavior': return 'warning';
        case 'progress': return 'info';
        default: return 'secondary';
    }
} 