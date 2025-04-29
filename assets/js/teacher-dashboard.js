document.addEventListener('DOMContentLoaded', function() {
    // Load today's attendance
    loadTodaysAttendance();
    
    // Load class performance
    loadClassPerformance();
    
    // Load recent activities
    loadRecentActivities();
});

// Load today's attendance
function loadTodaysAttendance() {
    const today = new Date().toISOString().split('T')[0];
    
    makeRequest(`/api/get_attendance_summary.php?class_id=${classId}&start_date=${today}&end_date=${today}`)
        .then(data => {
            document.getElementById('presentCount').textContent = data.present_count || 0;
            document.getElementById('absentCount').textContent = data.absent_count || 0;
            document.getElementById('lateCount').textContent = data.late_count || 0;
        })
        .catch(error => {
            console.error('Error loading attendance:', error);
            showAlert('Error loading attendance data', 'danger');
        });
}

// Load class performance
function loadClassPerformance() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    makeRequest(`/api/get_class_performance.php?class_id=${classId}`)
        .then(data => {
            createChart(ctx, 'line', {
                labels: data.labels,
                datasets: [{
                    label: 'Average Score',
                    data: data.scores,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
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
            console.error('Error loading performance data:', error);
            showAlert('Error loading performance data', 'danger');
        });
}

// Load recent activities
function loadRecentActivities() {
    makeRequest(`/api/get_recent_activities.php?user_id=${userId}`)
        .then(data => {
            const tbody = document.getElementById('activitiesTable');
            tbody.innerHTML = '';
            
            data.activities.forEach(activity => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${formatDate(activity.date)}</td>
                    <td>${activity.description}</td>
                    <td>
                        <span class="badge bg-${getStatusColor(activity.status)}">
                            ${activity.status}
                        </span>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading activities:', error);
            showAlert('Error loading recent activities', 'danger');
        });
}

// Helper function to get status color
function getStatusColor(status) {
    switch (status.toLowerCase()) {
        case 'completed':
            return 'success';
        case 'pending':
            return 'warning';
        case 'failed':
            return 'danger';
        default:
            return 'secondary';
    }
} 