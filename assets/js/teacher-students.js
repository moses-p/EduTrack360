document.addEventListener('DOMContentLoaded', function() {
    // Initialize student detail view
    initStudentDetailView();
    
    // Initialize export buttons
    initExportButtons();
});

function initStudentDetailView() {
    // Add event listeners to view buttons
    document.querySelectorAll('.view-student').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('data-student-id');
            showStudentDetails(studentId);
        });
    });
}

function showStudentDetails(studentId) {
    const modal = new bootstrap.Modal(document.getElementById('studentDetailModal'));
    const contentContainer = document.getElementById('studentDetailContent');
    
    // Show loading spinner
    contentContainer.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // In a real implementation, fetch student details from the server
    // For now, use mock data
    setTimeout(() => {
        // Mock student data
        const studentData = {
            id: studentId,
            name: 'Student ' + studentId,
            admission_number: 'ADM' + (1000 + parseInt(studentId)),
            gender: Math.random() > 0.5 ? 'Male' : 'Female',
            date_of_birth: '2010-01-' + (Math.floor(Math.random() * 28) + 1).toString().padStart(2, '0'),
            address: '123 Main St, City',
            guardian_name: 'Parent ' + studentId,
            guardian_contact: '123-456-7890',
            guardian_email: 'parent' + studentId + '@example.com',
            medical_info: 'No known medical conditions',
            performance: {
                average_grade: (Math.random() * 40 + 60).toFixed(1),
                attendance_rate: (Math.random() * 20 + 80).toFixed(1) + '%',
                last_term_position: Math.floor(Math.random() * 20) + 1
            }
        };
        
        // Display student details
        contentContainer.innerHTML = `
            <div class="row">
                <div class="col-md-4 text-center mb-3">
                    <div class="avatar-placeholder bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 36px;">
                        ${studentData.name.charAt(0)}
                    </div>
                    <h5 class="mt-2">${studentData.name}</h5>
                    <p class="badge bg-info">${studentData.admission_number}</p>
                </div>
                <div class="col-md-8">
                    <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Gender:</strong> ${studentData.gender}</p>
                            <p><strong>Date of Birth:</strong> ${studentData.date_of_birth}</p>
                            <p><strong>Address:</strong> ${studentData.address}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Guardian:</strong> ${studentData.guardian_name}</p>
                            <p><strong>Contact:</strong> ${studentData.guardian_contact}</p>
                            <p><strong>Email:</strong> ${studentData.guardian_email}</p>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Academic Performance</h6>
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="h3 text-primary">${studentData.performance.average_grade}</div>
                            <div class="small text-muted">Average Grade</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="h3 text-success">${studentData.performance.attendance_rate}</div>
                            <div class="small text-muted">Attendance</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="h3 text-info">${studentData.performance.last_term_position}</div>
                            <div class="small text-muted">Last Position</div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="index.php?page=marks&student_id=${studentData.id}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i> Enter Marks
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-text"></i> Performance Report
                        </a>
                    </div>
                </div>
            </div>
        `;
    }, 500);
}

function initExportButtons() {
    // Print button
    document.getElementById('printStudentListBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        window.print();
    });
    
    // Export to Excel
    document.getElementById('exportExcelBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        alert('Export to Excel functionality would be implemented here');
    });
    
    // Export to PDF
    document.getElementById('exportPdfBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        alert('Export to PDF functionality would be implemented here');
    });
} 