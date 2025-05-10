<?php
// Set page title
$page_title = "Health Records Management";

// Debug: Force role if needed
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Create a sample session for testing
    $_SESSION['user_id'] = 1; // Admin user ID
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
}

// Generate content
$content = <<<HTML
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add Health Record</h5>
            </div>
            <div class="card-body">
                <form id="healthRecordForm" action="api/add_health_record.php" method="POST">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="record_date" class="form-label">Record Date</label>
                        <input type="date" class="form-control" id="record_date" name="record_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="health_status" class="form-label">Health Status</label>
                        <select class="form-select" id="health_status" name="health_status" required>
                            <option value="healthy">Healthy</option>
                            <option value="sick">Sick</option>
                            <option value="injured">Injured</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="action_taken" class="form-label">Action Taken</label>
                        <textarea class="form-control" id="action_taken" name="action_taken" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Record</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Health Records</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportRecords('pdf')">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="exportRecords('excel')">
                        <i class="bi bi-file-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="healthRecordsTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Action Taken</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Records will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date picker
    document.getElementById('record_date').valueAsDate = new Date();
    
    // Load students
    loadStudents();
    
    // Load health records
    loadHealthRecords();
    
    // Form submission handler
    document.getElementById('healthRecordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitHealthRecord();
    });
});

function loadStudents() {
    fetch('api/get_students.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('student_id');
            data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = student.full_name;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading students:', error));
}

function loadHealthRecords() {
    fetch('api/get_health_records.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#healthRecordsTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(record => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${record.student_name}</td>
                    <td>${record.record_date}</td>
                    <td><span class="badge bg-${getStatusColor(record.health_status)}">${record.health_status}</span></td>
                    <td>${record.description || '-'}</td>
                    <td>${record.action_taken || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editRecord(${record.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord(${record.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => console.error('Error loading health records:', error));
}

function submitHealthRecord() {
    const form = document.getElementById('healthRecordForm');
    const formData = new FormData(form);
    
    fetch('api/add_health_record.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Health record added successfully');
            form.reset();
            document.getElementById('record_date').valueAsDate = new Date();
            loadHealthRecords();
        } else {
            showAlert('danger', data.error || 'Error adding health record');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error adding health record');
    });
}

function editRecord(id) {
    // Implement edit functionality
    console.log('Edit record:', id);
}

function deleteRecord(id) {
    if (confirm('Are you sure you want to delete this record?')) {
        fetch(`api/delete_health_record.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Health record deleted successfully');
                loadHealthRecords();
            } else {
                showAlert('danger', data.error || 'Error deleting health record');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Error deleting health record');
        });
    }
}

function exportRecords(format) {
    window.location.href = `api/export_health_records.php?format=${format}`;
}

function getStatusColor(status) {
    switch (status) {
        case 'healthy':
            return 'success';
        case 'sick':
            return 'danger';
        case 'injured':
            return 'warning';
        default:
            return 'secondary';
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('.table-responsive'));
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
HTML;
?> 