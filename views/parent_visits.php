<?php
$page_title = "Parent Visits";
$page_content = '
<div class="container-fluid">
    <div class="row">
        <!-- Visit Summary Card -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Visit Summary</h5>
                    <div class="row text-center">
                        <div class="col">
                            <h3 class="text-primary" id="total-visits">0</h3>
                            <small class="text-muted">Total Visits</small>
                        </div>
                        <div class="col">
                            <h3 class="text-success" id="meetings">0</h3>
                            <small class="text-muted">Meetings</small>
                        </div>
                        <div class="col">
                            <h3 class="text-info" id="pickups">0</h3>
                            <small class="text-muted">Pickups</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Visits Card -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Visits</h5>
                        <button class="btn btn-primary btn-sm" onclick="showVisitModal()">
                            <i class="fas fa-plus"></i> Add Visit
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Student</th>
                                    <th>Parent</th>
                                    <th>Purpose</th>
                                    <th>Meeting With</th>
                                </tr>
                            </thead>
                            <tbody id="visits-list">
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visit Calendar Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Visit Calendar</h5>
                    <div id="visit-calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Visit Modal -->
<div class="modal fade" id="visitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="visit-form">
                    <input type="hidden" id="visit-id">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select class="form-select" id="student-id" required>
                            <option value="">Select Student</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent</label>
                        <select class="form-select" id="parent-id" required>
                            <option value="">Select Parent</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visit Date</label>
                        <input type="date" class="form-control" id="visit-date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visit Time</label>
                        <input type="time" class="form-control" id="visit-time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <select class="form-select" id="visit-purpose" required>
                            <option value="meeting">Meeting</option>
                            <option value="pickup">Pickup</option>
                            <option value="dropoff">Dropoff</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meeting With</label>
                        <input type="text" class="form-control" id="meeting-with">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="visit-notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveVisit()">Save</button>
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
.fc-event {
    cursor: pointer;
}
</style>

<script>
let calendar;

// Load visit summary
function loadVisitSummary() {
    fetch("api/parent_visits.php?summary=true")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("total-visits").textContent = data.data.total;
                document.getElementById("meetings").textContent = data.data.meetings;
                document.getElementById("pickups").textContent = data.data.pickups;
            }
        })
        .catch(error => console.error("Error loading visit summary:", error));
}

// Load recent visits
function loadRecentVisits() {
    fetch("api/parent_visits.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById("visits-list");
                tbody.innerHTML = "";
                
                data.data.forEach(visit => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${formatDate(visit.visit_date)}</td>
                        <td>${formatTime(visit.visit_time)}</td>
                        <td>${visit.student_first_name} ${visit.student_last_name}</td>
                        <td>${visit.parent_first_name} ${visit.parent_last_name}</td>
                        <td><span class="badge bg-${getPurposeColor(visit.purpose)}">${formatPurpose(visit.purpose)}</span></td>
                        <td>${visit.meeting_with || "-"}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => console.error("Error loading recent visits:", error));
}

// Initialize calendar
function initCalendar() {
    const calendarEl = document.getElementById("visit-calendar");
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay"
        },
        events: "api/parent_visits.php?format=calendar",
        eventClick: function(info) {
            showVisitModal(info.event.id);
        }
    });
    calendar.render();
}

// Load students and parents
function loadStudentsAndParents() {
    // Load students
    fetch("api/get_students.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("student-id");
                data.data.forEach(student => {
                    const option = document.createElement("option");
                    option.value = student.id;
                    option.textContent = `${student.first_name} ${student.last_name}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error("Error loading students:", error));
    
    // Load parents
    fetch("api/get_parents.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("parent-id");
                data.data.forEach(parent => {
                    const option = document.createElement("option");
                    option.value = parent.id;
                    option.textContent = `${parent.first_name} ${parent.last_name}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error("Error loading parents:", error));
}

// Show visit modal
function showVisitModal(id = null) {
    const modal = new bootstrap.Modal(document.getElementById("visitModal"));
    document.getElementById("visit-form").reset();
    document.getElementById("visit-id").value = "";
    
    if (id) {
        // Load visit data for editing
        fetch(`api/parent_visits.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const visit = data.data;
                    document.getElementById("visit-id").value = visit.id;
                    document.getElementById("student-id").value = visit.student_id;
                    document.getElementById("parent-id").value = visit.parent_id;
                    document.getElementById("visit-date").value = visit.visit_date;
                    document.getElementById("visit-time").value = visit.visit_time;
                    document.getElementById("visit-purpose").value = visit.purpose;
                    document.getElementById("meeting-with").value = visit.meeting_with || "";
                    document.getElementById("visit-notes").value = visit.notes || "";
                }
            })
            .catch(error => console.error("Error loading visit data:", error));
    }
    
    modal.show();
}

// Save visit
function saveVisit() {
    const id = document.getElementById("visit-id").value;
    const data = {
        student_id: document.getElementById("student-id").value,
        parent_id: document.getElementById("parent-id").value,
        visit_date: document.getElementById("visit-date").value,
        visit_time: document.getElementById("visit-time").value,
        purpose: document.getElementById("visit-purpose").value,
        meeting_with: document.getElementById("meeting-with").value,
        notes: document.getElementById("visit-notes").value
    };
    
    const method = id ? "PUT" : "POST";
    if (id) {
        data.id = id;
    }
    
    fetch("api/parent_visits.php", {
        method: method,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById("visitModal")).hide();
            loadRecentVisits();
            loadVisitSummary();
            calendar.refetchEvents();
        } else {
            alert(data.error);
        }
    })
    .catch(error => console.error("Error saving visit:", error));
}

// Helper functions
function formatDate(date) {
    return new Date(date).toLocaleDateString();
}

function formatTime(time) {
    return new Date(`2000-01-01T${time}`).toLocaleTimeString();
}

function formatPurpose(purpose) {
    return purpose.charAt(0).toUpperCase() + purpose.slice(1);
}

function getPurposeColor(purpose) {
    const colors = {
        meeting: "primary",
        pickup: "success",
        dropoff: "info",
        other: "secondary"
    };
    return colors[purpose] || "secondary";
}

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    loadVisitSummary();
    loadRecentVisits();
    initCalendar();
    loadStudentsAndParents();
});
</script>';
?> 