// Initialize report charts and visualizations
function initializeReportCharts(reportType, reportData) {
    // Initialize summary chart
    initializeSummaryChart(reportType, reportData);
    
    // Initialize report-specific charts
    switch (reportType) {
        case 'academic':
            initializeAcademicCharts(reportData);
            break;
        case 'attendance':
            initializeAttendanceCharts(reportData);
            break;
        case 'health':
            initializeHealthCharts(reportData);
            break;
        case 'discipline':
            initializeDisciplineCharts(reportData);
            break;
        case 'co_curricular':
            initializeCoCurricularCharts(reportData);
            break;
        case 'financial':
            initializeFinancialCharts(reportData);
            break;
        case 'passout':
            initializePassoutCharts(reportData);
            break;
    }
}

// Initialize summary chart based on report type
function initializeSummaryChart(reportType, data) {
    const ctx = document.getElementById('summaryChart').getContext('2d');
    let chartData;
    
    switch (reportType) {
        case 'academic':
            chartData = {
                type: 'doughnut',
                data: {
                    labels: ['Excellent', 'Good', 'Average', 'Below Average'],
                    datasets: [{
                        data: calculateGradeDistribution(data),
                        backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
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
            };
            break;
            
        case 'attendance':
            chartData = {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        data: calculateAttendanceDistribution(data),
                        backgroundColor: ['#28a745', '#dc3545', '#ffc107']
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
            };
            break;
            
        case 'health':
            chartData = {
                type: 'doughnut',
                data: {
                    labels: ['Healthy', 'Sick', 'Injured', 'Other'],
                    datasets: [{
                        data: calculateHealthDistribution(data),
                        backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#6c757d']
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
            };
            break;
            
        // Add cases for other report types...
    }
    
    if (chartData) {
        new Chart(ctx, chartData);
    }
}

// Academic report charts
function initializeAcademicCharts(data) {
    // Performance overview chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: data.map(d => d.exam_date),
            datasets: [{
                label: 'Score',
                data: data.map(d => d.score),
                borderColor: '#17a2b8',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // Subject distribution chart
    const subjectCtx = document.getElementById('subjectChart').getContext('2d');
    new Chart(subjectCtx, {
        type: 'bar',
        data: {
            labels: [...new Set(data.map(d => d.subject_name))],
            datasets: [{
                label: 'Average Score',
                data: calculateSubjectAverages(data),
                backgroundColor: '#17a2b8'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

// Attendance report charts
function initializeAttendanceCharts(data) {
    // Attendance overview chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'bar',
        data: {
            labels: ['Present', 'Absent', 'Late'],
            datasets: [{
                data: calculateAttendanceDistribution(data),
                backgroundColor: ['#28a745', '#dc3545', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Attendance trends chart
    const trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [...new Set(data.map(d => d.date))],
            datasets: [{
                label: 'Attendance Rate',
                data: calculateAttendanceTrend(data),
                borderColor: '#28a745',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: value => value + '%'
                    }
                }
            }
        }
    });
}

// Health report charts
function initializeHealthCharts(data) {
    // Health status distribution chart
    const statusCtx = document.getElementById('healthStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Healthy', 'Sick', 'Injured', 'Other'],
            datasets: [{
                data: calculateHealthDistribution(data),
                backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#6c757d']
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
    
    // Health incidents timeline chart
    const timelineCtx = document.getElementById('healthTimelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [...new Set(data.map(d => d.record_date))],
            datasets: [{
                label: 'Health Incidents',
                data: calculateHealthIncidents(data),
                borderColor: '#dc3545',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Discipline report charts
function initializeDisciplineCharts(data) {
    // Incident type distribution chart
    const typeCtx = document.getElementById('disciplineTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'pie',
        data: {
            labels: ['Minor', 'Major', 'Critical'],
            datasets: [{
                data: calculateDisciplineDistribution(data),
                backgroundColor: ['#ffc107', '#dc3545', '#6c757d']
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
    
    // Incident timeline chart
    const timelineCtx = document.getElementById('disciplineTimelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [...new Set(data.map(d => d.incident_date))],
            datasets: [{
                label: 'Incidents',
                data: calculateDisciplineIncidents(data),
                borderColor: '#dc3545',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Co-curricular report charts
function initializeCoCurricularCharts(data) {
    // Activity participation chart
    const participationCtx = document.getElementById('activityParticipationChart').getContext('2d');
    new Chart(participationCtx, {
        type: 'bar',
        data: {
            labels: [...new Set(data.map(d => d.activity_name))],
            datasets: [{
                label: 'Participation Hours',
                data: calculateActivityParticipation(data),
                backgroundColor: '#17a2b8'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Activity timeline chart
    const timelineCtx = document.getElementById('activityTimelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [...new Set(data.map(d => d.start_date))],
            datasets: [{
                label: 'Activities',
                data: calculateActivityTimeline(data),
                borderColor: '#17a2b8',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Financial report charts
function initializeFinancialCharts(data) {
    // Payment status distribution chart
    const statusCtx = document.getElementById('paymentStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Pending', 'Overdue'],
            datasets: [{
                data: calculatePaymentDistribution(data),
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
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
    
    // Payment timeline chart
    const timelineCtx = document.getElementById('paymentTimelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [...new Set(data.map(d => d.payment_date))],
            datasets: [{
                label: 'Amount',
                data: calculatePaymentTimeline(data),
                borderColor: '#28a745',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Passout report charts
function initializePassoutCharts(data) {
    // Passout distribution chart
    const distributionCtx = document.getElementById('passoutDistributionChart').getContext('2d');
    new Chart(distributionCtx, {
        type: 'pie',
        data: {
            labels: ['Graduation', 'Transfer', 'Other'],
            datasets: [{
                data: calculatePassoutDistribution(data),
                backgroundColor: ['#28a745', '#17a2b8', '#6c757d']
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
    
    // Passout timeline chart
    const timelineCtx = document.getElementById('passoutTimelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [...new Set(data.map(d => d.passout_date))],
            datasets: [{
                label: 'Passouts',
                data: calculatePassoutTimeline(data),
                borderColor: '#17a2b8',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Helper functions for data calculations
function calculateGradeDistribution(data) {
    const distribution = {
        'Excellent': 0,
        'Good': 0,
        'Average': 0,
        'Below Average': 0
    };
    
    data.forEach(record => {
        const score = parseFloat(record.score);
        if (score >= 80) distribution['Excellent']++;
        else if (score >= 60) distribution['Good']++;
        else if (score >= 40) distribution['Average']++;
        else distribution['Below Average']++;
    });
    
    return Object.values(distribution);
}

function calculateAttendanceDistribution(data) {
    const distribution = {
        'Present': 0,
        'Absent': 0,
        'Late': 0
    };
    
    data.forEach(record => {
        distribution[record.status]++;
    });
    
    return Object.values(distribution);
}

function calculateHealthDistribution(data) {
    const distribution = {
        'Healthy': 0,
        'Sick': 0,
        'Injured': 0,
        'Other': 0
    };
    
    data.forEach(record => {
        distribution[record.health_status]++;
    });
    
    return Object.values(distribution);
}

function calculateSubjectAverages(data) {
    const subjectScores = {};
    const subjectCounts = {};
    
    data.forEach(record => {
        const subject = record.subject_name;
        const score = parseFloat(record.score);
        
        if (!subjectScores[subject]) {
            subjectScores[subject] = 0;
            subjectCounts[subject] = 0;
        }
        
        subjectScores[subject] += score;
        subjectCounts[subject]++;
    });
    
    return Object.keys(subjectScores).map(subject => 
        subjectScores[subject] / subjectCounts[subject]
    );
}

function calculateAttendanceTrend(data) {
    const dates = [...new Set(data.map(d => d.date))].sort();
    const trend = [];
    
    dates.forEach(date => {
        const dayRecords = data.filter(d => d.date === date);
        const presentCount = dayRecords.filter(d => d.status === 'Present').length;
        const attendanceRate = (presentCount / dayRecords.length) * 100;
        trend.push(attendanceRate);
    });
    
    return trend;
}

function calculateHealthIncidents(data) {
    const dates = [...new Set(data.map(d => d.record_date))].sort();
    const incidents = [];
    
    dates.forEach(date => {
        const dayRecords = data.filter(d => d.record_date === date);
        const incidentCount = dayRecords.filter(d => 
            d.health_status !== 'Healthy'
        ).length;
        incidents.push(incidentCount);
    });
    
    return incidents;
}

function calculateDisciplineDistribution(data) {
    const distribution = {
        'Minor': 0,
        'Major': 0,
        'Critical': 0
    };
    
    data.forEach(record => {
        distribution[record.incident_type]++;
    });
    
    return Object.values(distribution);
}

function calculateDisciplineIncidents(data) {
    const dates = [...new Set(data.map(d => d.incident_date))].sort();
    const incidents = [];
    
    dates.forEach(date => {
        const dayRecords = data.filter(d => d.incident_date === date);
        incidents.push(dayRecords.length);
    });
    
    return incidents;
}

function calculateActivityParticipation(data) {
    const activities = {};
    
    data.forEach(record => {
        if (!activities[record.activity_name]) {
            activities[record.activity_name] = 0;
        }
        activities[record.activity_name]++;
    });
    
    return Object.values(activities);
}

function calculateActivityTimeline(data) {
    const dates = [...new Set(data.map(d => d.start_date))].sort();
    const activities = [];
    
    dates.forEach(date => {
        const dayRecords = data.filter(d => d.start_date === date);
        activities.push(dayRecords.length);
    });
    
    return activities;
}

function calculatePaymentDistribution(data) {
    const distribution = {
        'Paid': 0,
        'Pending': 0,
        'Overdue': 0
    };
    
    data.forEach(record => {
        distribution[record.status]++;
    });
    
    return Object.values(distribution);
}

function calculatePaymentTimeline(data) {
    const dates = [...new Set(data.map(d => d.payment_date))].sort();
    const amounts = [];
    
    dates.forEach(date => {
        const dayRecords = data.filter(d => d.payment_date === date);
        const totalAmount = dayRecords.reduce((sum, record) => sum + parseFloat(record.amount), 0);
        amounts.push(totalAmount);
    });
    
    return amounts;
}

function calculatePassoutDistribution(data) {
    const distribution = {
        'Graduation': 0,
        'Transfer': 0,
        'Other': 0
    };
    
    data.forEach(record => {
        distribution[record.reason]++;
    });
    
    return Object.values(distribution);
}

function calculatePassoutTimeline(data) {
    const dates = [...new Set(data.map(d => d.passout_date))].sort();
    const passouts = [];
    
    dates.forEach(date => {
        const dayRecords = data.filter(d => d.passout_date === date);
        passouts.push(dayRecords.length);
    });
    
    return passouts;
} 