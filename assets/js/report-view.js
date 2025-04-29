/**
 * Report View Script
 * Handles additional functionality for the report view page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add print functionality
    const printButton = document.getElementById('printReport');
    if (printButton) {
        printButton.addEventListener('click', function() {
            window.print();
        });
    }

    // Format date fields
    const dateElements = document.querySelectorAll('.format-date');
    dateElements.forEach(function(element) {
        const rawDate = element.textContent;
        if (rawDate) {
            const formattedDate = new Date(rawDate).toLocaleDateString();
            element.textContent = formattedDate;
        }
    });
});

// Helper function for chart generation if needed
function generatePerformanceChart(canvasId, labels, data) {
    if (!document.getElementById(canvasId)) return;
    
    const ctx = document.getElementById(canvasId).getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Marks',
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Subject Performance'
                }
            }
        }
    });
} 