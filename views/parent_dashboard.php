<?php
$page_title = "Parent Dashboard";
$page_content = '
<div class="container-fluid">
    <div class="row">
        <!-- Student Selection Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Select Student</h5>
                        <select class="form-select w-auto" id="student-select" onchange="loadStudentData()">
                            <option value="">Loading students...</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Performance Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Academic Performance</h5>
                    <div class="row text-center mb-3">
                        <div class="col">
                            <h3 class="text-primary" id="average-grade">-</h3>
                            <small class="text-muted">Average Grade</small>
                        </div>
                        <div class="col">
                            <h3 class="text-success" id="attendance-rate">-</h3>
                            <small class="text-muted">Attendance Rate</small>
                        </div>
                        <div class="col">
                            <h3 class="text-info" id="behavior-points">-</h3>
                            <small class="text-muted">Behavior Points</small>
                        </div>
                    </div>
                    <canvas id="performance-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- UNEB Performance Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">UNEB Performance</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Subject</th>
                                    <th>Grade</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody id="uneb-list">
                                <tr>
                                    <td colspan="4" class="text-center">Select a student to view UNEB results</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Summary Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Attendance Summary</h5>
                    <div class="row text-center mb-3">
                        <div class="col">
                            <h3 class="text-success" id="present-days">-</h3>
                            <small class="text-muted">Present</small>
                        </div>
                        <div class="col">
                            <h3 class="text-danger" id="absent-days">-</h3>
                            <small class="text-muted">Absent</small>
                        </div>
                        <div class="col">
                            <h3 class="text-warning" id="late-days">-</h3>
                            <small class="text-muted">Late</small>
                        </div>
                    </div>
                    <canvas id="attendance-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Library Activity Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Library Activity</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Borrowed Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="library-list">
                                <tr>
                                    <td colspan="4" class="text-center">Select a student to view library activity</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transportation Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Transportation</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Route:</strong> <span id="route-name">-</span></p>
                            <p class="mb-1"><strong>Vehicle:</strong> <span id="vehicle-info">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Pickup Time:</strong> <span id="pickup-time">-</span></p>
                            <p class="mb-1"><strong>Dropoff Time:</strong> <span id="dropoff-time">-</span></p>
                        </div>
                    </div>
                    <canvas id="transportation-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Behavior Summary Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Behavior Summary</h5>
                    <div class="row text-center mb-3">
                        <div class="col">
                            <h3 class="text-success" id="positive-behaviors">-</h3>
                            <small class="text-muted">Positive</small>
                        </div>
                        <div class="col">
                            <h3 class="text-danger" id="negative-behaviors">-</h3>
                            <small class="text-muted">Negative</small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody id="behavior-list">
                                <tr>
                                    <td colspan="4" class="text-center">Select a student to view behavior records</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Communication Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Communication</h5>
                        <button class="btn btn-primary btn-sm" onclick="showMessageModal()">
                            <i class="fas fa-envelope"></i> New Message
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="messages-list">
                                <tr>
                                    <td colspan="4" class="text-center">Select a student to view messages</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fee Payment Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Fee Payment</h5>
                    <div class="row text-center mb-3">
                        <div class="col">
                            <h3 class="text-primary" id="total-fees">-</h3>
                            <small class="text-muted">Total Fees</small>
                        </div>
                        <div class="col">
                            <h3 class="text-success" id="paid-fees">-</h3>
                            <small class="text-muted">Paid</small>
                        </div>
                        <div class="col">
                            <h3 class="text-danger" id="pending-fees">-</h3>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="fees-list">
                                <tr>
                                    <td colspan="4" class="text-center">Select a student to view fee details</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Records Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Health Records</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Blood Group:</strong> <span id="blood-group">-</span></p>
                            <p class="mb-1"><strong>Allergies:</strong> <span id="allergies">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Last Checkup:</strong> <span id="last-checkup">-</span></p>
                            <p class="mb-1"><strong>Next Checkup:</strong> <span id="next-checkup">-</span></p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Doctor</th>
                                </tr>
                            </thead>
                            <tbody id="health-list">
                                <tr>
                                    <td colspan="4" class="text-center">Select a student to view health records</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Co-curricular Activities Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Co-curricular Activities</h5>
                    <div class="row text-center mb-3">
                        <div class="col">
                            <h3 class="text-primary" id="total-activities">-</h3>
                            <small class="text-muted">Total Activities</small>
                        </div>
                        <div class="col">
                            <h3 class="text-success" id="achievements">-</h3>
                            <small class="text-muted">Achievements</small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Category</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="activities-list">
                                <tr>
                                    <td colspan="4" class="text-center">Select a student to view activities</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="message-form">
                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <select class="form-select" id="recipient" required>
                            <option value="">Select Recipient</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" id="message-subject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" id="message-content" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.card-title {
    color: #2c3e50;
}
.table th {
    font-weight: 600;
    color: #2c3e50;
}
</style>

<script>
let performanceChart, attendanceChart, transportationChart;

// Load students for the parent
function loadStudents() {
    fetch("api/get_students.php?parent_id=<?php echo $_SESSION["user_id"]; ?>")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("student-select");
                select.innerHTML = "";
                
                data.data.forEach(student => {
                    const option = document.createElement("option");
                    option.value = student.id;
                    option.textContent = `${student.first_name} ${student.last_name}`;
                    select.appendChild(option);
                });
                
                if (data.data.length > 0) {
                    loadStudentData();
                }
            }
        })
        .catch(error => console.error("Error loading students:", error));
}

// Load all student data
function loadStudentData() {
    const studentId = document.getElementById("student-select").value;
    if (!studentId) return;
    
    loadAcademicPerformance(studentId);
    loadUNEBPerformance(studentId);
    loadAttendanceSummary(studentId);
    loadLibraryActivity(studentId);
    loadTransportationInfo(studentId);
    loadBehaviorSummary(studentId);
    loadMessages(studentId);
    loadFeePayments(studentId);
    loadHealthRecords(studentId);
    loadActivities(studentId);
}

// Load academic performance
function loadAcademicPerformance(studentId) {
    fetch(`api/get_student_performance.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("average-grade").textContent = data.data.average_grade;
                document.getElementById("attendance-rate").textContent = `${data.data.attendance_rate}%`;
                document.getElementById("behavior-points").textContent = data.data.behavior_points;
                
                // Update performance chart
                if (performanceChart) {
                    performanceChart.destroy();
                }
                
                const ctx = document.getElementById("performance-chart").getContext("2d");
                performanceChart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: data.data.terms,
                        datasets: [{
                            label: "Average Grade",
                            data: data.data.grades,
                            borderColor: "#3498db",
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error("Error loading academic performance:", error));
}

// Load UNEB performance
function loadUNEBPerformance(studentId) {
    fetch(`api/uneb_performance.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById("uneb-list");
                tbody.innerHTML = "";
                
                data.data.forEach(record => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${record.year}</td>
                        <td>${record.subject}</td>
                        <td>${record.grade}</td>
                        <td>${record.points}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading UNEB performance:", error));
}

// Load attendance summary
function loadAttendanceSummary(studentId) {
    fetch(`api/get_attendance_summary.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("present-days").textContent = data.data.present;
                document.getElementById("absent-days").textContent = data.data.absent;
                document.getElementById("late-days").textContent = data.data.late;
                
                // Update attendance chart
                if (attendanceChart) {
                    attendanceChart.destroy();
                }
                
                const ctx = document.getElementById("attendance-chart").getContext("2d");
                attendanceChart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: ["Present", "Absent", "Late"],
                        datasets: [{
                            data: [data.data.present, data.data.absent, data.data.late],
                            backgroundColor: ["#2ecc71", "#e74c3c", "#f1c40f"]
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error("Error loading attendance summary:", error));
}

// Load library activity
function loadLibraryActivity(studentId) {
    fetch(`api/get_library_summary.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById("library-list");
                tbody.innerHTML = "";
                
                data.data.forEach(record => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${record.book_title}</td>
                        <td>${formatDate(record.borrowed_date)}</td>
                        <td>${formatDate(record.due_date)}</td>
                        <td><span class="badge bg-${getStatusColor(record.status)}">${record.status}</span></td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading library activity:", error));
}

// Load transportation info
function loadTransportationInfo(studentId) {
    fetch(`api/get_transportation_summary.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("route-name").textContent = data.data.route_name;
                document.getElementById("vehicle-info").textContent = data.data.vehicle_info;
                document.getElementById("pickup-time").textContent = data.data.pickup_time;
                document.getElementById("dropoff-time").textContent = data.data.dropoff_time;
                
                // Update transportation chart
                if (transportationChart) {
                    transportationChart.destroy();
                }
                
                const ctx = document.getElementById("transportation-chart").getContext("2d");
                transportationChart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: data.data.dates,
                        datasets: [{
                            label: "Pickup",
                            data: data.data.pickup_rates,
                            borderColor: "#2ecc71",
                            tension: 0.1
                        }, {
                            label: "Dropoff",
                            data: data.data.dropoff_rates,
                            borderColor: "#3498db",
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error("Error loading transportation info:", error));
}

// Load behavior summary
function loadBehaviorSummary(studentId) {
    fetch(`api/get_behavior_summary.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("positive-behaviors").textContent = data.data.positive;
                document.getElementById("negative-behaviors").textContent = data.data.negative;
                
                const tbody = document.getElementById("behavior-list");
                tbody.innerHTML = "";
                
                data.data.records.forEach(record => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${formatDate(record.date)}</td>
                        <td><span class="badge bg-${record.type === "positive" ? "success" : "danger"}">${record.type}</span></td>
                        <td>${record.description}</td>
                        <td>${record.points}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading behavior summary:", error));
}

// Load messages
function loadMessages(studentId) {
    fetch(`api/get_messages.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById("messages-list");
                tbody.innerHTML = "";
                
                data.data.forEach(message => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${formatDate(message.date)}</td>
                        <td>${message.sender_name}</td>
                        <td>${message.subject}</td>
                        <td><span class="badge bg-${message.read ? "success" : "warning"}">${message.read ? "Read" : "Unread"}</span></td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading messages:", error));
}

// Load fee payments
function loadFeePayments(studentId) {
    fetch(`api/get_fee_payments.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("total-fees").textContent = data.data.total;
                document.getElementById("paid-fees").textContent = data.data.paid;
                document.getElementById("pending-fees").textContent = data.data.pending;
                
                const tbody = document.getElementById("fees-list");
                tbody.innerHTML = "";
                
                data.data.payments.forEach(payment => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${formatDate(payment.date)}</td>
                        <td>${payment.description}</td>
                        <td>${payment.amount}</td>
                        <td><span class="badge bg-${getPaymentStatusColor(payment.status)}">${payment.status}</span></td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading fee payments:", error));
}

// Load health records
function loadHealthRecords(studentId) {
    fetch(`api/get_health_records.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("blood-group").textContent = data.data.blood_group;
                document.getElementById("allergies").textContent = data.data.allergies || "None";
                document.getElementById("last-checkup").textContent = formatDate(data.data.last_checkup);
                document.getElementById("next-checkup").textContent = formatDate(data.data.next_checkup);
                
                const tbody = document.getElementById("health-list");
                tbody.innerHTML = "";
                
                data.data.records.forEach(record => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${formatDate(record.date)}</td>
                        <td>${record.type}</td>
                        <td>${record.description}</td>
                        <td>${record.doctor_name}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading health records:", error));
}

// Load co-curricular activities
function loadActivities(studentId) {
    fetch(`api/get_activities.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("total-activities").textContent = data.data.total;
                document.getElementById("achievements").textContent = data.data.achievements;
                
                const tbody = document.getElementById("activities-list");
                tbody.innerHTML = "";
                
                data.data.activities.forEach(activity => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${activity.name}</td>
                        <td>${activity.category}</td>
                        <td>${activity.schedule}</td>
                        <td><span class="badge bg-${getActivityStatusColor(activity.status)}">${activity.status}</span></td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading activities:", error));
}

// Show message modal
function showMessageModal() {
    const modal = new bootstrap.Modal(document.getElementById("messageModal"));
    document.getElementById("message-form").reset();
    
    // Load teachers for the selected student
    const studentId = document.getElementById("student-select").value;
    fetch(`api/get_teachers.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("recipient");
                select.innerHTML = '<option value="">Select Recipient</option>';
                
                data.data.forEach(teacher => {
                    const option = document.createElement("option");
                    option.value = teacher.id;
                    option.textContent = `${teacher.first_name} ${teacher.last_name} (${teacher.subject})`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error("Error loading teachers:", error));
    
    modal.show();
}

// Send message
function sendMessage() {
    const studentId = document.getElementById("student-select").value;
    const data = {
        student_id: studentId,
        recipient_id: document.getElementById("recipient").value,
        subject: document.getElementById("message-subject").value,
        content: document.getElementById("message-content").value
    };
    
    fetch("api/send_message.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById("messageModal")).hide();
            loadMessages(studentId);
        } else {
            alert(data.error);
        }
    })
    .catch(error => console.error("Error sending message:", error));
}

// Helper functions
function formatDate(date) {
    return new Date(date).toLocaleDateString();
}

function getStatusColor(status) {
    const colors = {
        borrowed: "warning",
        returned: "success",
        overdue: "danger"
    };
    return colors[status] || "secondary";
}

function getPaymentStatusColor(status) {
    const colors = {
        paid: "success",
        pending: "warning",
        overdue: "danger"
    };
    return colors[status] || "secondary";
}

function getActivityStatusColor(status) {
    const colors = {
        active: "success",
        completed: "info",
        upcoming: "warning"
    };
    return colors[status] || "secondary";
}

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    loadStudents();
});
</script>';
?> 