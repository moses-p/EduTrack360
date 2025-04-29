document.addEventListener('DOMContentLoaded', () => {
    console.log('Parent dashboard script loaded.');

    // Placeholder: In a real implementation, get student_id from the PHP view or session
    // const studentId = document.getElementById('student-data-container').dataset.studentId; 
    // For now, we don't have it easily accessible on the client-side from the PHP.
    const studentId = null; // We need a way to get this

    loadPerformanceSummary(studentId);
    loadAttendanceSummary(studentId);

});

async function loadPerformanceSummary(studentId) {
    const summaryElement = document.getElementById('performanceSummary');
    const chartCanvas = document.getElementById('performanceChart');
    if (!summaryElement || !chartCanvas) return;

    // Placeholder: Replace with actual API call
    summaryElement.textContent = 'Fetching performance data...';
    if (!studentId) {
         summaryElement.textContent = 'Could not load performance data (Student ID missing).';
         return;
    }

    try {
        // Example API call (needs to be created)
        // const response = await fetch(`/api/get_student_performance_summary.php?student_id=${studentId}`);
        // const data = await response.json();
        
        // Placeholder data
        const data = {
            average: 75,
            recent_subjects: ['Math', 'English', 'Science'],
            recent_marks: [80, 70, 75]
        };
        
        summaryElement.textContent = `Overall Average: ${data.average}%`;

        // Render chart (using Chart.js, assuming it's loaded)
        if (typeof Chart !== 'undefined') {
             const ctx = chartCanvas.getContext('2d');
             new Chart(ctx, {
                type: 'bar', // or 'line'
                data: {
                    labels: data.recent_subjects,
                    datasets: [{
                        label: 'Recent Marks',
                        data: data.recent_marks,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
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
        } else {
            console.error('Chart.js not loaded.');
        }

    } catch (error) {
        console.error('Error loading performance summary:', error);
        summaryElement.textContent = 'Could not load performance data.';
    }
}

async function loadAttendanceSummary(studentId) {
    const summaryElement = document.getElementById('attendanceSummary');
    const daysPresentSpan = document.getElementById('daysPresent');
    const daysAbsentSpan = document.getElementById('daysAbsent');
    const daysLateSpan = document.getElementById('daysLate');
    if (!summaryElement || !daysPresentSpan || !daysAbsentSpan || !daysLateSpan) return;
    
    summaryElement.textContent = 'Fetching attendance data...';
    if (!studentId) {
         summaryElement.textContent = 'Could not load attendance data (Student ID missing).';
         return;
    }
    
    try {
         // Example API call (needs to be created)
        // const response = await fetch(`/api/get_student_attendance_summary.php?student_id=${studentId}&term=current`); // Need term/year context
        // const data = await response.json();

        // Placeholder data
         const data = {
            present: 50,
            absent: 2,
            late: 3
        };

        summaryElement.style.display = 'none'; // Hide loading text
        daysPresentSpan.textContent = data.present;
        daysAbsentSpan.textContent = data.absent;
        daysLateSpan.textContent = data.late;
        
    } catch (error) {
        console.error('Error loading attendance summary:', error);
        summaryElement.textContent = 'Could not load attendance data.';
    }

} 