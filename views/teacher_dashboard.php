<?php
$page_title = "Teacher Dashboard";
$page_content = '
<div class="container-fluid">
    <div class="row">
        <!-- Teacher Info Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Welcome, <span id="teacher-name">Loading...</span></h5>
                            <p class="text-muted mb-0" id="teacher-subject">Loading subject...</p>
                        </div>
                        <div class="text-end">
                            <p class="mb-0"><strong>Total Classes:</strong> <span id="total-classes">-</span></p>
                            <p class="mb-0"><strong>Total Students:</strong> <span id="total-students">-</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Summary Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Attendance Summary</h5>
                        <button class="btn btn-primary btn-sm" onclick="showAttendanceModal()">
                            <i class="fas fa-plus"></i> Mark Attendance
                        </button>
                    </div>
                    <div class="row text-center mb-3">
                        <div class="col">
                            <h3 class="text-success" id="present-count">-</h3>
                            <small class="text-muted">Present</small>
                        </div>
                        <div class="col">
                            <h3 class="text-danger" id="absent-count">-</h3>
                            <small class="text-muted">Absent</small>
                        </div>
                        <div class="col">
                            <h3 class="text-warning" id="late-count">-</h3>
                            <small class="text-muted">Late</small>
                        </div>
                    </div>
                    <canvas id="attendance-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Duty Schedule Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Duty Schedule</h5>
                        <button class="btn btn-primary btn-sm" onclick="showDutyModal()">
                            <i class="fas fa-plus"></i> Add Duty
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="duty-list">
                                <tr>
                                    <td colspan="5" class="text-center">Loading duties...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Attendance</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-list">
                                <tr>
                                    <td colspan="5" class="text-center">Loading attendance...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Messages</h5>
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
                                    <td colspan="4" class="text-center">Loading messages...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="attendance-form">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="attendance-date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="attendance-status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="excused">Excused</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Check-in Time</label>
                        <input type="time" class="form-control" id="check-in-time">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Check-out Time</label>
                        <input type="time" class="form-control" id="check-out-time">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="attendance-notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAttendance()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Duty Modal -->
<div class="modal fade" id="dutyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Duty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="duty-form">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="duty-date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="duty-type" required>
                            <option value="morning">Morning Duty</option>
                            <option value="afternoon">Afternoon Duty</option>
                            <option value="evening">Evening Duty</option>
                            <option value="special">Special Duty</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" id="duty-location" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="duty-notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveDuty()">Save</button>
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
let attendanceChart;

// Load teacher information
function loadTeacherInfo() {
    fetch("api/get_teacher_info.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("teacher-name").textContent = data.data.name;
                document.getElementById("teacher-subject").textContent = data.data.subject;
                document.getElementById("total-classes").textContent = data.data.total_classes;
                document.getElementById("total-students").textContent = data.data.total_students;
            }
        })
        .catch(error => console.error("Error loading teacher info:", error));
}

// Load attendance summary
function loadAttendanceSummary() {
    fetch("api/get_teacher_attendance.php?summary=true")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("present-count").textContent = data.data.present;
                document.getElementById("absent-count").textContent = data.data.absent;
                document.getElementById("late-count").textContent = data.data.late;
                
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

// Load duty schedule
function loadDutySchedule() {
    fetch("api/get_teacher_duties.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById("duty-list");
                tbody.innerHTML = "";
                
                data.data.forEach(duty => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${formatDate(duty.date)}</td>
                        <td>${duty.type}</td>
                        <td>${duty.location}</td>
                        <td><span class="badge bg-${getDutyStatusColor(duty.status)}">${duty.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editDuty(${duty.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteDuty(${duty.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading duty schedule:", error));
}

// Load recent attendance
function loadRecentAttendance() {
    fetch("api/get_teacher_attendance.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById("attendance-list");
                tbody.innerHTML = "";
                
                data.data.forEach(record => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${formatDate(record.date)}</td>
                        <td><span class="badge bg-${getAttendanceStatusColor(record.status)}">${record.status}</span></td>
                        <td>${record.check_in_time || "-"}</td>
                        <td>${record.check_out_time || "-"}</td>
                        <td>${record.notes || "-"}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading recent attendance:", error));
}

// Load messages
function loadMessages() {
    fetch("api/get_teacher_messages.php")
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

// Show attendance modal
function showAttendanceModal() {
    const modal = new bootstrap.Modal(document.getElementById("attendanceModal"));
    document.getElementById("attendance-form").reset();
    document.getElementById("attendance-date").valueAsDate = new Date();
    modal.show();
}

// Show duty modal
function showDutyModal() {
    const modal = new bootstrap.Modal(document.getElementById("dutyModal"));
    document.getElementById("duty-form").reset();
    document.getElementById("duty-date").valueAsDate = new Date();
    modal.show();
}

// Show message modal
function showMessageModal() {
    const modal = new bootstrap.Modal(document.getElementById("messageModal"));
    document.getElementById("message-form").reset();
    
    // Load recipients
    fetch("api/get_message_recipients.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("recipient");
                select.innerHTML = '<option value="">Select Recipient</option>';
                
                data.data.forEach(recipient => {
                    const option = document.createElement("option");
                    option.value = recipient.id;
                    option.textContent = recipient.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error("Error loading recipients:", error));
    
    modal.show();
}

// Save attendance
function saveAttendance() {
    const data = {
        date: document.getElementById("attendance-date").value,
        status: document.getElementById("attendance-status").value,
        check_in_time: document.getElementById("check-in-time").value,
        check_out_time: document.getElementById("check-out-time").value,
        notes: document.getElementById("attendance-notes").value
    };
    
    fetch("api/save_teacher_attendance.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById("attendanceModal")).hide();
            loadAttendanceSummary();
            loadRecentAttendance();
        } else {
            alert(data.error);
        }
    })
    .catch(error => console.error("Error saving attendance:", error));
}

// Save duty
function saveDuty() {
    const data = {
        date: document.getElementById("duty-date").value,
        type: document.getElementById("duty-type").value,
        location: document.getElementById("duty-location").value,
        notes: document.getElementById("duty-notes").value
    };
    
    fetch("api/save_teacher_duty.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById("dutyModal")).hide();
            loadDutySchedule();
        } else {
            alert(data.error);
        }
    })
    .catch(error => console.error("Error saving duty:", error));
}

// Send message
function sendMessage() {
    const data = {
        recipient_id: document.getElementById("recipient").value,
        subject: document.getElementById("message-subject").value,
        content: document.getElementById("message-content").value
    };
    
    fetch("api/send_teacher_message.php", {
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
            loadMessages();
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

function getAttendanceStatusColor(status) {
    const colors = {
        present: "success",
        absent: "danger",
        late: "warning",
        excused: "info"
    };
    return colors[status] || "secondary";
}

function getDutyStatusColor(status) {
    const colors = {
        completed: "success",
        pending: "warning",
        cancelled: "danger"
    };
    return colors[status] || "secondary";
}

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    loadTeacherInfo();
    loadAttendanceSummary();
    loadDutySchedule();
    loadRecentAttendance();
    loadMessages();
});
</script>';
?> 