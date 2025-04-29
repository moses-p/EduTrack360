document.addEventListener('DOMContentLoaded', function() {
    initAttendancePage();
});

function initAttendancePage() {
    // Initialize attendance status listeners
    initStatusListeners();
    
    // Initialize date range controls
    initDateRangeControls();
    
    // Initialize attendance form submission
    initAttendanceForm();
    
    // Initialize chart
    initAttendanceChart();
    
    // Initialize export and print buttons
    document.getElementById('exportAttendanceBtn')?.addEventListener('click', exportAttendance);
    document.getElementById('printAttendanceBtn')?.addEventListener('click', printAttendance);
    document.getElementById('todayAttendanceBtn')?.addEventListener('click', () => selectDate(new Date()));
}

function initStatusListeners() {
    // Add event listeners to all attendance status selects
    document.querySelectorAll('.attendance-status').forEach(select => {
        select.addEventListener('change', function() {
            const reasonField = this.closest('tr').querySelector('.reason-field');
            if (this.value === 'absent') {
                reasonField.disabled = false;
                reasonField.required = true;
            } else {
                reasonField.disabled = true;
                reasonField.required = false;
                reasonField.value = '';
            }
        });
    });
}

function initDateRangeControls() {
    const today = new Date();
    const oneMonthAgo = new Date();
    oneMonthAgo.setMonth(today.getMonth() - 1);
    
    const startDateInput = document.getElementById('dateRangeStart');
    const endDateInput = document.getElementById('dateRangeEnd');
    
    if (startDateInput && endDateInput) {
        // Set default date range to the last month
        startDateInput.value = formatDate(oneMonthAgo);
        endDateInput.value = formatDate(today);
        
        // Add event listeners
        startDateInput.addEventListener('change', loadAttendanceSummary);
        endDateInput.addEventListener('change', loadAttendanceSummary);
        
        // Initial load
        loadAttendanceSummary();
    }
}

function initAttendanceForm() {
    const form = document.getElementById('attendanceForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveAttendance(this);
        });
    }
}

function saveAttendance(form) {
    // In production this would send data to the server
    // For now we'll just show a success message and reset the form
    
    // Simulate an API call with a timeout
    showLoader();
    
    setTimeout(() => {
        hideLoader();
        showMessage('Attendance saved successfully!', 'success');
        
        // Update the summary
        loadAttendanceSummary();
    }, 1000);
}

function loadAttendanceSummary() {
    const startDate = document.getElementById('dateRangeStart')?.value;
    const endDate = document.getElementById('dateRangeEnd')?.value;
    
    if (!startDate || !endDate) return;
    
    // Show loader
    showLoader();
    
    // For demo purposes, we'll use mock data
    setTimeout(() => {
        // Mock data - in production, fetch from server
        const data = {
            present: 85,
            absent: 12,
            late: 8,
            total_days: 20,
            frequently_absent: [
                { name: 'John Doe', absences: 5 },
                { name: 'Jane Smith', absences: 3 },
                { name: 'Bob Johnson', absences: 2 }
            ]
        };
        
        // Update UI
        document.getElementById('presentCount').textContent = data.present;
        document.getElementById('absentCount').textContent = data.absent;
        document.getElementById('lateCount').textContent = data.late;
        
        // Update frequently absent list
        const absentListContainer = document.getElementById('frequentlyAbsentList');
        if (absentListContainer) {
            let html = '';
            
            if (data.frequently_absent.length > 0) {
                html = '<ul class="list-group list-group-flush">';
                data.frequently_absent.forEach(student => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        ${student.name}
                        <span class="badge bg-danger rounded-pill">${student.absences}</span>
                    </li>`;
                });
                html += '</ul>';
            } else {
                html = '<p class="text-center text-muted">No absences recorded</p>';
            }
            
            absentListContainer.innerHTML = html;
        }
        
        // Update chart
        updateAttendanceChart(data);
        
        // Hide loader
        hideLoader();
    }, 800);
}

function initAttendanceChart() {
    const ctx = document.getElementById('attendanceChart')?.getContext('2d');
    if (!ctx) return;
    
    // Initialize chart with empty data
    window.attendanceChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Present', 'Absent', 'Late'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',    // green
                    'rgba(220, 53, 69, 0.7)',    // red
                    'rgba(255, 193, 7, 0.7)'     // yellow
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function updateAttendanceChart(data) {
    if (!window.attendanceChart) return;
    
    window.attendanceChart.data.datasets[0].data = [
        data.present,
        data.absent,
        data.late
    ];
    
    window.attendanceChart.update();
}

function exportAttendance() {
    // In production, this would generate a CSV file
    alert('Export functionality would generate a CSV file of attendance records');
}

function printAttendance() {
    window.print();
}

function selectDate(date) {
    // This would load attendance for a specific date
    alert(`Loading attendance for ${formatDate(date)}`);
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function showLoader() {
    // In a real implementation, add a loader element
    console.log('Loading...');
}

function hideLoader() {
    console.log('Loading complete');
}

function showMessage(message, type = 'info') {
    // For now, just show an alert
    alert(message);
    
    // In a real implementation, use a toast or notification system
    console.log(`${type.toUpperCase()}: ${message}`);
} 