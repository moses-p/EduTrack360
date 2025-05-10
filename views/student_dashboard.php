<?php
// Set page title
$page_title = "Student Dashboard";

// Generate content
$content = <<<HTML
<div class="row">
    <!-- Student Information Card -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Student Information
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="assets/images/default-avatar.png" alt="Student Photo" class="rounded-circle" style="width: 100px; height: 100px;">
                </div>
                <h4 class="text-center mb-3" id="studentName">Loading...</h4>
                <div class="row">
                    <div class="col-6">
                        <p><strong>Roll Number:</strong> <span id="rollNumber">-</span></p>
                        <p><strong>Class:</strong> <span id="className">-</span></p>
                    </div>
                    <div class="col-6">
                        <p><strong>Section:</strong> <span id="section">-</span></p>
                        <p><strong>Admission Date:</strong> <span id="admissionDate">-</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance Summary Card -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-check me-2"></i>
                    Attendance Summary
                </h5>
                <div class="export-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('attendance', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="exportReport('attendance', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="exportReport('attendance', 'pdf')">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="attendance-stat">
                            <h3 id="presentCount">-</h3>
                            <p class="text-success">Present</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="attendance-stat">
                            <h3 id="absentCount">-</h3>
                            <p class="text-danger">Absent</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="attendance-stat">
                            <h3 id="lateCount">-</h3>
                            <p class="text-warning">Late</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="attendance-stat">
                            <h3 id="attendancePercentage">-</h3>
                            <p>Attendance %</p>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Behavior Summary Card -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-star me-2"></i>
                    Behavior Summary
                </h5>
                <div class="export-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('behavior', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="exportReport('behavior', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="exportReport('behavior', 'pdf')">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="behavior-stat">
                            <h3 id="positiveCount">-</h3>
                            <p class="text-success">Positive</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="behavior-stat">
                            <h3 id="negativeCount">-</h3>
                            <p class="text-danger">Negative</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="behavior-stat">
                            <h3 id="totalPoints">-</h3>
                            <p>Total Points</p>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Recent Behavior Records</h6>
                    <div id="behaviorRecords" class="list-group">
                        <!-- Behavior records will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Library Summary Card -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-book me-2"></i>
                    Library Summary
                </h5>
                <div class="export-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('library', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="exportReport('library', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="exportReport('library', 'pdf')">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="library-stat">
                            <h3 id="borrowedCount">-</h3>
                            <p>Borrowed</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="library-stat">
                            <h3 id="overdueCount">-</h3>
                            <p class="text-danger">Overdue</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="library-stat">
                            <h3 id="returnedCount">-</h3>
                            <p class="text-success">Returned</p>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Current Borrowings</h6>
                    <div id="currentBorrowings" class="list-group">
                        <!-- Current borrowings will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transportation Summary Card -->
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bus me-2"></i>
                    Transportation Summary
                </h5>
                <div class="export-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('transportation', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="exportReport('transportation', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="exportReport('transportation', 'pdf')">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="transportation-info">
                            <p><strong>Route:</strong> <span id="routeName">-</span></p>
                            <p><strong>Vehicle:</strong> <span id="vehicleNumber">-</span></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="transportation-info">
                            <p><strong>Driver:</strong> <span id="driverName">-</span></p>
                            <p><strong>Contact:</strong> <span id="driverContact">-</span></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="transportation-info">
                            <p><strong>Pickup Time:</strong> <span id="pickupTime">-</span></p>
                            <p><strong>Dropoff Time:</strong> <span id="dropoffTime">-</span></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="transportation-info">
                            <p><strong>Stop:</strong> <span id="stopName">-</span></p>
                            <p><strong>Status:</strong> <span id="transportationStatus">-</span></p>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Recent Transportation Attendance</h6>
                    <div id="transportationAttendance" class="list-group">
                        <!-- Transportation attendance will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.attendance-stat, .behavior-stat, .library-stat {
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
}

.attendance-stat h3, .behavior-stat h3, .library-stat h3 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.transportation-info {
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.export-buttons {
    position: absolute;
    top: 10px;
    right: 10px;
}

.export-buttons .btn {
    padding: 2px 8px;
    font-size: 12px;
    margin-left: 5px;
}

.card {
    position: relative;
}
</style>

<script>
// Load student information
async function loadStudentInfo() {
    try {
        const response = await fetch('api/get_student.php');
        const data = await response.json();
        
        if (data.success) {
            const student = data.data;
            document.getElementById('studentName').textContent = `${student.first_name} ${student.last_name}`;
            document.getElementById('rollNumber').textContent = student.roll_number;
            document.getElementById('className').textContent = student.class_name;
            document.getElementById('section').textContent = student.section;
            document.getElementById('admissionDate').textContent = new Date(student.admission_date).toLocaleDateString();
        }
    } catch (error) {
        console.error('Error loading student info:', error);
    }
}

// Load attendance summary
async function loadAttendanceSummary() {
    try {
        const response = await fetch('api/attendance.php?type=summary');
        const data = await response.json();
        
        if (data.success) {
            const summary = data.data;
            document.getElementById('presentCount').textContent = summary.present;
            document.getElementById('absentCount').textContent = summary.absent;
            document.getElementById('lateCount').textContent = summary.late;
            document.getElementById('attendancePercentage').textContent = `${summary.percentage}%`;
            
            // Create attendance chart
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: summary.dates,
                    datasets: [{
                        label: 'Attendance',
                        data: summary.percentages,
                        borderColor: '#17a2b8',
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error loading attendance summary:', error);
    }
}

// Load behavior summary
async function loadBehaviorSummary() {
    try {
        const response = await fetch('api/behavior.php?type=summary');
        const data = await response.json();
        
        if (data.success) {
            const summary = data.data;
            document.getElementById('positiveCount').textContent = summary.positive;
            document.getElementById('negativeCount').textContent = summary.negative;
            document.getElementById('totalPoints').textContent = summary.total_points;
            
            // Load recent behavior records
            const recordsResponse = await fetch('api/behavior.php?type=recent');
            const recordsData = await recordsResponse.json();
            
            if (recordsData.success) {
                const recordsList = document.getElementById('behaviorRecords');
                recordsList.innerHTML = recordsData.data.map(record => `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${record.behavior_type}</strong>
                                <p class="mb-0">${record.description}</p>
                            </div>
                            <small class="text-muted">${new Date(record.date).toLocaleDateString()}</small>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading behavior summary:', error);
    }
}

// Load library summary
async function loadLibrarySummary() {
    try {
        const response = await fetch('api/library.php?type=summary');
        const data = await response.json();
        
        if (data.success) {
            const summary = data.data;
            document.getElementById('borrowedCount').textContent = summary.borrowed;
            document.getElementById('overdueCount').textContent = summary.overdue;
            document.getElementById('returnedCount').textContent = summary.returned;
            
            // Load current borrowings
            const borrowingsResponse = await fetch('api/library.php?type=current');
            const borrowingsData = await borrowingsResponse.json();
            
            if (borrowingsData.success) {
                const borrowingsList = document.getElementById('currentBorrowings');
                borrowingsList.innerHTML = borrowingsData.data.map(book => `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${book.title}</strong>
                                <p class="mb-0">By ${book.author}</p>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">Due: ${new Date(book.due_date).toLocaleDateString()}</small>
                                <br>
                                <span class="badge ${book.status === 'overdue' ? 'bg-danger' : 'bg-warning'}">
                                    ${book.status}
                                </span>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading library summary:', error);
    }
}

// Load transportation summary
async function loadTransportationSummary() {
    try {
        const response = await fetch('api/transportation.php?type=summary');
        const data = await response.json();
        
        if (data.success) {
            const summary = data.data;
            document.getElementById('routeName').textContent = summary.route_name;
            document.getElementById('vehicleNumber').textContent = summary.vehicle_number;
            document.getElementById('driverName').textContent = summary.driver_name;
            document.getElementById('driverContact').textContent = summary.driver_contact;
            document.getElementById('pickupTime').textContent = summary.pickup_time;
            document.getElementById('dropoffTime').textContent = summary.dropoff_time;
            document.getElementById('stopName').textContent = summary.stop_name;
            document.getElementById('transportationStatus').textContent = summary.status;
            
            // Load recent transportation attendance
            const attendanceResponse = await fetch('api/transportation.php?type=attendance&recent=true');
            const attendanceData = await attendanceResponse.json();
            
            if (attendanceData.success) {
                const attendanceList = document.getElementById('transportationAttendance');
                attendanceList.innerHTML = attendanceData.data.map(record => `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${new Date(record.date).toLocaleDateString()}</strong>
                                <p class="mb-0">
                                    Pickup: <span class="badge ${getStatusBadgeClass(record.pickup_status)}">${record.pickup_status}</span>
                                    Dropoff: <span class="badge ${getStatusBadgeClass(record.dropoff_status)}">${record.dropoff_status}</span>
                                </p>
                            </div>
                            <small class="text-muted">${record.notes || ''}</small>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading transportation summary:', error);
    }
}

// Helper function for status badge classes
function getStatusBadgeClass(status) {
    switch (status) {
        case 'present':
            return 'bg-success';
        case 'absent':
            return 'bg-danger';
        case 'late':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
}

// Load all data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadStudentInfo();
    loadAttendanceSummary();
    loadBehaviorSummary();
    loadLibrarySummary();
    loadTransportationSummary();
});

function exportReport(type, format) {
    // Get date range from the dashboard filters if they exist
    const startDate = document.getElementById('start_date')?.value || '';
    const endDate = document.getElementById('end_date')?.value || '';
    
    // Build the export URL
    const url = new URL('api/export_report.php', window.location.origin);
    url.searchParams.append('type', type);
    url.searchParams.append('format', format);
    url.searchParams.append('student_id', <?php echo $_SESSION['student_id']; ?>);
    
    if (startDate) url.searchParams.append('start_date', startDate);
    if (endDate) url.searchParams.append('end_date', endDate);
    
    // Trigger the download
    window.location.href = url.toString();
}
</script>
HTML;
?> 