document.addEventListener('DOMContentLoaded', function() {
    // Load school overview
    loadSchoolOverview();
    
    // Load performance trends
    loadPerformanceTrends();
    
    // Load recent activities
    loadRecentActivities();
    
    // Load class performance
    loadClassPerformance();
    
    // Load teacher performance
    loadTeacherPerformance();
});

// Load school overview
function loadSchoolOverview() {
    makeRequest('/api/get_school_overview.php')
        .then(data => {
            document.getElementById('totalStudents').textContent = data.total_students;
            document.getElementById('totalTeachers').textContent = data.total_teachers;
            document.getElementById('attendanceRate').textContent = `${data.attendance_rate}%`;
            document.getElementById('performanceRate').textContent = `${data.performance_rate}%`;
        })
        .catch(error => {
            console.error('Error loading school overview:', error);
            showAlert('Error loading school overview', 'danger');
        });
}

// Load performance trends
function loadPerformanceTrends() {
    const ctx = document.getElementById('performanceTrendsChart').getContext('2d');
    
    makeRequest('/api/get_performance_trends.php')
        .then(data => {
            createChart(ctx, 'line', {
                labels: data.labels,
                datasets: [{
                    label: 'Academic Performance',
                    data: data.academic,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Attendance Rate',
                    data: data.attendance,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }]
            }, {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading performance trends:', error);
            showAlert('Error loading performance trends', 'danger');
        });
}

// Load recent activities
function loadRecentActivities() {
    makeRequest('/api/get_recent_activities.php')
        .then(data => {
            const container = document.getElementById('recentActivities');
            container.innerHTML = '';
            
            data.activities.forEach(activity => {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${activity.title}</h6>
                        <small class="text-muted">${formatDate(activity.date)}</small>
                    </div>
                    <p class="mb-1">${activity.description}</p>
                    <small class="text-${getStatusColor(activity.status)}">${activity.status}</small>
                `;
                container.appendChild(item);
            });
        })
        .catch(error => {
            console.error('Error loading activities:', error);
            showAlert('Error loading recent activities', 'danger');
        });
}

// Load class performance
function loadClassPerformance() {
    const ctx = document.getElementById('classPerformanceChart').getContext('2d');
    
    makeRequest('/api/get_class_performance_summary.php')
        .then(data => {
            createChart(ctx, 'bar', {
                labels: data.labels,
                datasets: [{
                    label: 'Average Score',
                    data: data.scores,
                    backgroundColor: '#17a2b8',
                    borderColor: '#17a2b8',
                    borderWidth: 1
                }]
            }, {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading class performance:', error);
            showAlert('Error loading class performance', 'danger');
        });
}

// Load teacher performance
function loadTeacherPerformance() {
    const ctx = document.getElementById('teacherPerformanceChart').getContext('2d');
    
    makeRequest('/api/get_teacher_performance.php')
        .then(data => {
            createChart(ctx, 'bar', {
                labels: data.labels,
                datasets: [{
                    label: 'Performance Score',
                    data: data.scores,
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                }]
            }, {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading teacher performance:', error);
            showAlert('Error loading teacher performance', 'danger');
        });
} 