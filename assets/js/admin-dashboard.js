document.addEventListener('DOMContentLoaded', function () {
    // Initialize chart
    initializeCharts();
    
    // Load system overview
    loadSystemOverview();

    // Load recent activities (formerly system logs)
    loadRecentActivities();

    // Load system status
    loadSystemStatus();
    
    // Load OCR performance data
    loadOcrPerformance();

    // Set up interval updates
    setInterval(loadSystemStatus, 60000); // Update every minute
    
    // Set up tab buttons for chart timeline
    document.getElementById('viewWeek').addEventListener('click', function() {
        updateChartTimeline('week');
        setActiveTimelineButton(this);
    });
    
    document.getElementById('viewMonth').addEventListener('click', function() {
        updateChartTimeline('month');
        setActiveTimelineButton(this);
    });
    
    document.getElementById('viewYear').addEventListener('click', function() {
        updateChartTimeline('year');
        setActiveTimelineButton(this);
    });
});

// Initialize main chart
let mainChart;
function initializeCharts() {
    const ctx = document.getElementById('mainChart').getContext('2d');
    
    // Create the main chart
    mainChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
            datasets: [
                {
                    label: 'User Activities',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                },
                {
                    label: 'OCR Scans',
                    data: [28, 48, 40, 19, 86, 27, 90],
                    borderColor: 'rgb(255, 159, 64)',
                    tension: 0.1,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Update chart timeline
function updateChartTimeline(period) {
    // This would typically fetch new data from the server based on period
    // For now, we'll just update with placeholder data
    let labels, data1, data2;
    
    if (period === 'week') {
        labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        data1 = [65, 59, 80, 81, 56, 55, 40];
        data2 = [28, 48, 40, 19, 86, 27, 90];
    } else if (period === 'month') {
        labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        data1 = [275, 234, 189, 192];
        data2 = [110, 132, 142, 126];
    } else if (period === 'year') {
        labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        data1 = [1200, 1100, 1350, 900, 800, 950, 1400, 1600, 1200, 900, 850, 1000];
        data2 = [400, 450, 480, 420, 390, 500, 600, 700, 650, 500, 450, 550];
    }
    
    mainChart.data.labels = labels;
    mainChart.data.datasets[0].data = data1;
    mainChart.data.datasets[1].data = data2;
    mainChart.update();
}

// Helper to set active timeline button
function setActiveTimelineButton(activeButton) {
    const buttons = document.querySelectorAll('.btn-group button');
    buttons.forEach(button => {
        button.classList.remove('active');
    });
    activeButton.classList.add('active');
}

// Load system overview
function loadSystemOverview() {
    // Add timestamp to prevent caching
    const timestamp = new Date().getTime();
    fetch(`api/get_system_overview.php?t=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            if (result.success && result.data) {
                document.getElementById('totalUsers').textContent = result.data.total_users;
                document.getElementById('activeClasses').textContent = result.data.active_classes;
                document.getElementById('totalSubjects').textContent = result.data.total_subjects;
                document.getElementById('pendingTasks').textContent = result.data.pending_tasks;
            } else {
                console.error('Invalid data format in system overview response');
                showAlert('Error loading system overview', 'danger');
                
                // Fallback to placeholder data
                document.getElementById('totalUsers').textContent = '157';
                document.getElementById('activeClasses').textContent = '12';
                document.getElementById('totalSubjects').textContent = '24';
                document.getElementById('pendingTasks').textContent = '5';
            }
        })
        .catch(error => {
            console.error('Error loading system overview:', error);
            showAlert('Error loading system overview', 'danger');
            
            // Fallback to placeholder data
            document.getElementById('totalUsers').textContent = '157';
            document.getElementById('activeClasses').textContent = '12';
            document.getElementById('totalSubjects').textContent = '24';
            document.getElementById('pendingTasks').textContent = '5';
        });
}

// Load OCR performance data
function loadOcrPerformance() {
    // Add a timestamp parameter to prevent caching
    const timestamp = new Date().getTime();
    fetch(`api/get_ocr_stats.php?t=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            if (result.success !== false && result.data) {
                // Update OCR accuracy progress bar
                updateProgressBar('ocrAccuracy', result.data.accuracy_rate);
                
                // Update scans requiring review
                const reviewRate = result.data.needs_review_rate || 
                                  (result.data.needs_review / result.data.total_scans * 100) || 0;
                updateProgressBar('ocrReview', reviewRate);
                
                // Update total scans
                document.getElementById('ocrScans').textContent = result.data.total_scans || 0;
            } else {
                console.error('Invalid data format in OCR stats response');
                
                // Fallback to placeholder data
                updateProgressBar('ocrAccuracy', 78);
                updateProgressBar('ocrReview', 22);
                document.getElementById('ocrScans').textContent = '124';
            }
        })
        .catch(error => {
            console.error('Error loading OCR performance data:', error);
            
            // Fallback to placeholder data
            updateProgressBar('ocrAccuracy', 78);
            updateProgressBar('ocrReview', 22);
            document.getElementById('ocrScans').textContent = '124';
        });
}

// Load system logs (now Recent Activities)
function loadRecentActivities() {
    // Add timestamp to prevent caching
    const timestamp = new Date().getTime();
    fetch(`api/get_system_logs.php?t=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            const container = document.getElementById('systemLogs');
            if (!container) {
                console.error('System logs container element not found');
                return;
            }
            
            container.innerHTML = '';

            if (result.success && result.data && result.data.length > 0) {
                result.data.forEach(log => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action';
                    
                    // Get activity icon based on log type
                    let icon = 'bi-info-circle';
                    if (log.level === 'warning') icon = 'bi-exclamation-triangle';
                    if (log.level === 'error') icon = 'bi-x-circle';
                    
                    item.innerHTML = `
                        <div class="d-flex w-100 justify-content-between">
                            <div>
                                <i class="bi ${icon} text-${getLogLevelColor(log.level)} me-2"></i>
                                <span>${log.message}</span>
                            </div>
                            <small class="text-muted">${formatTimeAgo(log.created_at)}</small>
                        </div>
                        <small class="text-muted">
                            ${log.user_name ? 'By ' + log.user_name : ''}
                            ${log.ip_address ? 'from ' + log.ip_address : ''}
                        </small>
                    `;
                    container.appendChild(item);
                });
            } else {
                // If no logs or error, show a message
                container.innerHTML = `
                    <div class="list-group-item text-center text-muted py-3">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        No recent activities to display
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading system logs:', error);
            
            const container = document.getElementById('systemLogs');
            if (container) {
                container.innerHTML = `
                    <div class="list-group-item text-center text-danger py-3">
                        <i class="bi bi-exclamation-circle fs-4 d-block mb-2"></i>
                        Error loading activities. Please try again later.
                    </div>
                `;
            }
        });
}

// Load system status
function loadSystemStatus() {
    // Add timestamp to prevent caching
    const timestamp = new Date().getTime();
    fetch(`api/get_system_status.php?t=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            if (result.success && result.data) {
                updateProgressBar('dbStatus', result.data.database_status);
                updateStatusText('dbStatusText', result.data.database_status);
                
                updateProgressBar('storageUsage', result.data.storage_usage);
                updateStatusText('storageStatusText', result.data.storage_usage);
                
                updateProgressBar('activeSessions', result.data.active_sessions);
                updateStatusText('sessionStatusText', result.data.active_sessions);
                
                updateProgressBar('systemLoad', result.data.system_load);
                updateStatusText('loadStatusText', result.data.system_load);
            } else {
                console.error('Invalid data format in system status response');
                
                // Use fallback values
                updateProgressBar('dbStatus', 30);
                updateStatusText('dbStatusText', 30);
                
                updateProgressBar('storageUsage', 45);
                updateStatusText('storageStatusText', 45);
                
                updateProgressBar('activeSessions', 20);
                updateStatusText('sessionStatusText', 20);
                
                updateProgressBar('systemLoad', 35);
                updateStatusText('loadStatusText', 35);
            }
        })
        .catch(error => {
            console.error('Error loading system status:', error);
            
            // Use fallback values on error
            updateProgressBar('dbStatus', 30);
            updateStatusText('dbStatusText', 30);
            
            updateProgressBar('storageUsage', 45);
            updateStatusText('storageStatusText', 45);
            
            updateProgressBar('activeSessions', 20);
            updateStatusText('sessionStatusText', 20);
            
            updateProgressBar('systemLoad', 35);
            updateStatusText('loadStatusText', 35);
        });
}

// Helper function to update progress bar
function updateProgressBar(id, value) {
    const progressBar = document.getElementById(id);
    if (!progressBar) {
        console.error(`Progress bar element with ID "${id}" not found`);
        return;
    }
    
    // Ensure value is a number
    value = parseInt(value, 10) || 0;
    
    progressBar.style.width = `${value}%`;
    
    // Set color based on value
    if (value >= 80) {
        progressBar.className = 'progress-bar bg-danger';
    } else if (value >= 60) {
        progressBar.className = 'progress-bar bg-warning';
    } else {
        progressBar.className = 'progress-bar bg-success';
    }
    
    // For OCR-specific progress bars, we use different logic
    if (id === 'ocrAccuracy') {
        if (value >= 70) {
            progressBar.className = 'progress-bar bg-success';
        } else if (value >= 50) {
            progressBar.className = 'progress-bar bg-warning';
        } else {
            progressBar.className = 'progress-bar bg-danger';
        }
        progressBar.textContent = `${value}%`;
    } else if (id === 'ocrReview') {
        if (value <= 20) {
            progressBar.className = 'progress-bar bg-success';
        } else if (value <= 40) {
            progressBar.className = 'progress-bar bg-warning';
        } else {
            progressBar.className = 'progress-bar bg-danger';
        }
        progressBar.textContent = `${value}%`;
    }
}

// Helper function to update status text
function updateStatusText(id, value) {
    const statusText = document.getElementById(id);
    if (!statusText) {
        console.error(`Status text element with ID "${id}" not found`);
        return;
    }
    
    // Ensure value is a number
    value = parseInt(value, 10) || 0;
    
    let status, statusClass;
    if (value >= 80) {
        status = 'Critical';
        statusClass = 'text-danger';
    } else if (value >= 60) {
        status = 'Warning';
        statusClass = 'text-warning';
    } else {
        status = 'Normal';
        statusClass = 'text-success';
    }
    
    statusText.textContent = status;
    statusText.className = `small ${statusClass}`;
}

// Helper function to get log level color
function getLogLevelColor(level) {
    switch (level?.toLowerCase()) {
        case 'error':
            return 'danger';
        case 'warning':
            return 'warning';
        case 'info':
            return 'info';
        default:
            return 'secondary';
    }
}

// Helper function to format time ago
function formatTimeAgo(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    if (isNaN(date)) return dateString;
    
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffSec < 60) return 'Just now';
    if (diffMin < 60) return `${diffMin} min ago`;
    if (diffHour < 24) return `${diffHour} hour${diffHour !== 1 ? 's' : ''} ago`;
    if (diffDay < 7) return `${diffDay} day${diffDay !== 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
}

// Helper function to show alerts
function showAlert(message, type = 'info') {
    const alertsContainer = document.getElementById('alerts-container') || document.createElement('div');
    
    if (!document.getElementById('alerts-container')) {
        alertsContainer.id = 'alerts-container';
        alertsContainer.className = 'position-fixed top-0 end-0 p-3';
        document.body.appendChild(alertsContainer);
    }
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertsContainer.appendChild(alert);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 5000);
} 