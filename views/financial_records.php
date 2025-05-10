<?php
// Set page title
$page_title = "Financial Records Management";

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
                <h5 class="card-title mb-0">Add Financial Record</h5>
            </div>
            <div class="card-body">
                <form id="financialRecordForm" action="api/add_financial_record.php" method="POST">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transaction_type" class="form-label">Transaction Type</label>
                        <select class="form-select" id="transaction_type" name="transaction_type" required>
                            <option value="tuition">Tuition Fee</option>
                            <option value="transport">Transport Fee</option>
                            <option value="library">Library Fee</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date">
                    </div>
                    <div class="mb-3">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select class="form-select" id="payment_status" name="payment_status" required>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Record</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Financial Records</h5>
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
                    <table class="table table-striped" id="financialRecordsTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th>Description</th>
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
    // Initialize date pickers
    document.getElementById('due_date').valueAsDate = new Date();
    document.getElementById('payment_date').valueAsDate = new Date();
    
    // Load students
    loadStudents();
    
    // Load financial records
    loadFinancialRecords();
    
    // Form submission handler
    document.getElementById('financialRecordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitFinancialRecord();
    });
    
    // Payment status change handler
    document.getElementById('payment_status').addEventListener('change', function(e) {
        const paymentDate = document.getElementById('payment_date');
        if (e.target.value === 'unpaid') {
            paymentDate.value = '';
            paymentDate.disabled = true;
        } else {
            paymentDate.disabled = false;
            if (!paymentDate.value) {
                paymentDate.valueAsDate = new Date();
            }
        }
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

function loadFinancialRecords() {
    fetch('api/get_financial_records.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#financialRecordsTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(record => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${record.student_name}</td>
                    <td>${record.transaction_type}</td>
                    <td>${record.amount}</td>
                    <td>${record.due_date}</td>
                    <td>${record.payment_date || '-'}</td>
                    <td><span class="badge bg-${getStatusColor(record.payment_status)}">${record.payment_status}</span></td>
                    <td>${record.description || '-'}</td>
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
            
            // Check for payment alerts
            checkPaymentAlerts(data);
        })
        .catch(error => console.error('Error loading financial records:', error));
}

function submitFinancialRecord() {
    const form = document.getElementById('financialRecordForm');
    const formData = new FormData(form);
    
    fetch('api/add_financial_record.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Financial record added successfully');
            form.reset();
            document.getElementById('due_date').valueAsDate = new Date();
            document.getElementById('payment_date').valueAsDate = new Date();
            loadFinancialRecords();
        } else {
            showAlert('danger', data.error || 'Error adding financial record');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error adding financial record');
    });
}

function editRecord(id) {
    // Implement edit functionality
    console.log('Edit record:', id);
}

function deleteRecord(id) {
    if (confirm('Are you sure you want to delete this record?')) {
        fetch(`api/delete_financial_record.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Financial record deleted successfully');
                loadFinancialRecords();
            } else {
                showAlert('danger', data.error || 'Error deleting financial record');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Error deleting financial record');
        });
    }
}

function exportRecords(format) {
    window.location.href = `api/export_financial_records.php?format=${format}`;
}

function getStatusColor(status) {
    switch (status) {
        case 'paid':
            return 'success';
        case 'partial':
            return 'warning';
        case 'unpaid':
            return 'danger';
        default:
            return 'secondary';
    }
}

function checkPaymentAlerts(records) {
    // Group records by student
    const studentPayments = {};
    records.forEach(record => {
        if (!studentPayments[record.student_id]) {
            studentPayments[record.student_id] = {
                name: record.student_name,
                total: 0,
                paid: 0,
                records: []
            };
        }
        studentPayments[record.student_id].total += parseFloat(record.amount);
        if (record.payment_status === 'paid') {
            studentPayments[record.student_id].paid += parseFloat(record.amount);
        }
        studentPayments[record.student_id].records.push(record);
    });
    
    // Check payment status for each student
    Object.values(studentPayments).forEach(student => {
        const paymentPercentage = (student.paid / student.total) * 100;
        let alertType, message;
        
        if (paymentPercentage === 0) {
            alertType = 'danger';
            message = `${student.name} has not paid any fees (${student.records.length} pending records)`;
        } else if (paymentPercentage < 50) {
            alertType = 'warning';
            message = `${student.name} has paid less than 50% of fees (${paymentPercentage.toFixed(1)}% paid)`;
        } else if (paymentPercentage < 100) {
            alertType = 'info';
            message = `${student.name} has paid ${paymentPercentage.toFixed(1)}% of fees`;
        } else {
            alertType = 'success';
            message = `${student.name} has paid all fees`;
        }
        
        showAlert(alertType, message);
    });
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