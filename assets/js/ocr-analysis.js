// OCR Analysis Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Constants
    const PAGE_SIZE = 10;
    let currentPage = 1;
    let totalRecords = 0;
    let currentFilters = {
        dateRange: 30,
        teacher: 'all',
        confidence: 'all',
        feedback: 'all'
    };

    // DOM Elements
    const refreshDataBtn = document.getElementById('refreshData');
    const exportDataBtn = document.getElementById('exportData');
    const applyFiltersBtn = document.getElementById('applyFilters');
    const dateRangeFilter = document.getElementById('dateRange');
    const teacherFilter = document.getElementById('teacherFilter');
    const confidenceFilter = document.getElementById('confidenceFilter');
    const feedbackFilter = document.getElementById('feedbackFilter');
    const ocrAttemptsList = document.getElementById('ocrAttemptsList');
    const ocrPagination = document.getElementById('ocrPagination');
    
    // Summary elements
    const totalScansEl = document.getElementById('totalScans');
    const accuracyRateEl = document.getElementById('accuracyRate');
    const avgConfidenceEl = document.getElementById('avgConfidence');
    const needsReviewEl = document.getElementById('needsReview');
    
    // Modal elements
    const ocrDetailModal = new bootstrap.Modal(document.getElementById('ocrDetailModal'));
    const ocrChartModal = new bootstrap.Modal(document.getElementById('ocrChartModal'));
    const saveOcrFeedbackBtn = document.getElementById('saveOcrFeedback');
    
    // Event listeners
    refreshDataBtn.addEventListener('click', loadData);
    exportDataBtn.addEventListener('click', exportToCsv);
    applyFiltersBtn.addEventListener('click', applyFilters);
    
    document.getElementById('dateRange').addEventListener('change', function() {
        currentFilters.dateRange = this.value;
    });
    
    document.getElementById('teacherFilter').addEventListener('change', function() {
        currentFilters.teacher = this.value;
    });
    
    document.getElementById('confidenceFilter').addEventListener('change', function() {
        currentFilters.confidence = this.value;
    });
    
    document.getElementById('feedbackFilter').addEventListener('change', function() {
        currentFilters.feedback = this.value;
    });
    
    saveOcrFeedbackBtn.addEventListener('click', saveOcrFeedback);
    
    // Initialize
    loadTeachers();
    loadData();
    initializeCharts();

    // Functions
    function loadTeachers() {
        fetch('/api/get_teachers.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const select = document.getElementById('teacherFilter');
                    
                    // Keep the default "All" option
                    const defaultOption = select.options[0];
                    select.innerHTML = '';
                    select.appendChild(defaultOption);
                    
                    // Add teachers from API
                    data.data.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.id;
                        option.textContent = `${teacher.first_name} ${teacher.last_name}`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading teachers:', error);
                showAlert('Error loading teachers. Please try again later.', 'danger');
            });
    }

    function loadData() {
        showLoading();
        
        // Prepare query parameters
        const params = new URLSearchParams({
            page: currentPage,
            limit: PAGE_SIZE,
            dateRange: currentFilters.dateRange,
            teacher: currentFilters.teacher,
            confidence: currentFilters.confidence,
            feedback: currentFilters.feedback
        });
        
        fetch(`/api/get_ocr_attempts.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.status === 'success') {
                    renderOcrAttempts(data.data);
                    totalRecords = data.total;
                    renderPagination();
                    updateSummary(data.summary);
                } else {
                    ocrAttemptsList.innerHTML = `<tr><td colspan="9">No data found</td></tr>`;
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error loading OCR attempts:', error);
                ocrAttemptsList.innerHTML = `<tr><td colspan="9">Error loading data. Please try again.</td></tr>`;
                showAlert('Error loading OCR data. Please try again later.', 'danger');
            });
    }

    function renderOcrAttempts(attempts) {
        if (!attempts || attempts.length === 0) {
            ocrAttemptsList.innerHTML = `<tr><td colspan="9">No OCR attempts found</td></tr>`;
            return;
        }
        
        let html = '';
        attempts.forEach(attempt => {
            // Format date
            const date = new Date(attempt.created_at);
            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            // Format confidence with color coding
            let confidenceClass = 'text-danger';
            if (attempt.confidence >= 70) {
                confidenceClass = 'text-success';
            } else if (attempt.confidence >= 40) {
                confidenceClass = 'text-warning';
            }
            
            // Format feedback status
            let feedbackBadge = `<span class="badge bg-secondary">No Feedback</span>`;
            if (attempt.feedback === 'correct') {
                feedbackBadge = `<span class="badge bg-success">Correct</span>`;
            } else if (attempt.feedback === 'incorrect') {
                feedbackBadge = `<span class="badge bg-danger">Incorrect</span>`;
            } else if (attempt.feedback === 'adjusted') {
                feedbackBadge = `<span class="badge bg-warning">Adjusted</span>`;
            }
            
            // Truncate text with ellipsis if too long
            const truncatedText = attempt.ocr_text.length > 50 
                ? attempt.ocr_text.substring(0, 50) + '...' 
                : attempt.ocr_text;
            
            html += `
                <tr>
                    <td>${attempt.id}</td>
                    <td>${attempt.teacher_name}</td>
                    <td>${formattedDate}</td>
                    <td title="${escapeHtml(attempt.ocr_text)}">${escapeHtml(truncatedText)}</td>
                    <td>${attempt.selected_mark}</td>
                    <td><span class="${confidenceClass}">${Math.round(attempt.confidence)}%</span></td>
                    <td>${attempt.feedback_mark || attempt.selected_mark}</td>
                    <td>${feedbackBadge}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary view-ocr" data-id="${attempt.id}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        ocrAttemptsList.innerHTML = html;
        
        // Add event listeners for view buttons
        document.querySelectorAll('.view-ocr').forEach(button => {
            button.addEventListener('click', () => {
                viewOcrAttempt(button.getAttribute('data-id'));
            });
        });
    }

    function renderPagination() {
        const totalPages = Math.ceil(totalRecords / PAGE_SIZE);
        
        if (totalPages <= 1) {
            ocrPagination.innerHTML = '';
            return;
        }
        
        let html = `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;
        
        // Handle large number of pages
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        html += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        
        ocrPagination.innerHTML = html;
        
        // Add event listeners for pagination links
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', event => {
                event.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                
                if (!isNaN(page) && page !== currentPage && page > 0 && page <= totalPages) {
                    currentPage = page;
                    loadData();
                }
            });
        });
    }

    function updateSummary(summary) {
        if (!summary) return;
        
        totalScansEl.textContent = summary.total_scans;
        accuracyRateEl.textContent = Math.round(summary.accuracy_rate) + '%';
        avgConfidenceEl.textContent = Math.round(summary.avg_confidence) + '%';
        needsReviewEl.textContent = summary.needs_review;

        // Update color coding for accuracy rate
        if (summary.accuracy_rate >= 70) {
            accuracyRateEl.className = 'text-success';
        } else if (summary.accuracy_rate >= 50) {
            accuracyRateEl.className = 'text-warning';
        } else {
            accuracyRateEl.className = 'text-danger';
        }
        
        // Update the charts with new data if available
        updateCharts(summary.chart_data);
    }

    function viewOcrAttempt(id) {
        fetch(`/api/get_ocr_attempt.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const attempt = data.data;
                    
                    // Set modal data
                    document.getElementById('originalImage').src = attempt.image_path;
                    document.getElementById('processedImage').src = attempt.processed_image_path || attempt.image_path;
                    document.getElementById('extractedText').value = attempt.ocr_text;
                    document.getElementById('selectedMark').value = attempt.selected_mark;
                    
                    // Update detected marks list
                    const detectedMarksEl = document.getElementById('detectedMarks');
                    let possibleMarks = [];
                    
                    try {
                        possibleMarks = JSON.parse(attempt.possible_marks);
                    } catch (e) {
                        console.error('Error parsing possible marks:', e);
                    }
                    
                    if (possibleMarks.length > 0) {
                        let marksHtml = '';
                        possibleMarks.forEach(mark => {
                            const confidenceClass = mark.confidence >= 70 ? 'text-success' : 
                                                  (mark.confidence >= 40 ? 'text-warning' : 'text-danger');
                            
                            marksHtml += `
                                <div class="mark-item mb-1">
                                    <span class="mark-value">${mark.value}</span>
                                    <span class="mark-confidence ${confidenceClass}">
                                        (${Math.round(mark.confidence)}%)
                                    </span>
                                </div>
                            `;
                        });
                        detectedMarksEl.innerHTML = marksHtml;
                    } else {
                        detectedMarksEl.innerHTML = '<p class="text-muted">No marks detected</p>';
                    }
                    
                    // Set feedback type
                    if (attempt.feedback) {
                        document.getElementById('feedbackType').value = attempt.feedback;
                    } else {
                        document.getElementById('feedbackType').value = 'correct';
                    }
                    
                    // Set up the save button with the current ID
                    saveOcrFeedbackBtn.setAttribute('data-id', id);
                    
                    // Show the modal
                    ocrDetailModal.show();
                } else {
                    showAlert('Error loading OCR attempt details.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error fetching OCR attempt:', error);
                showAlert('Error loading OCR attempt details.', 'danger');
            });
    }

    function saveOcrFeedback() {
        const id = saveOcrFeedbackBtn.getAttribute('data-id');
        const feedbackType = document.getElementById('feedbackType').value;
        const feedbackMark = document.getElementById('selectedMark').value;
        const notes = document.getElementById('adminNotes').value;
        
        const data = {
            id: id,
            feedback: feedbackType,
            feedback_mark: feedbackMark,
            notes: notes
        };
        
        fetch('/api/update_ocr_feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert('Feedback saved successfully!', 'success');
                ocrDetailModal.hide();
                loadData(); // Reload the data to reflect changes
            } else {
                showAlert(data.message || 'Error saving feedback.', 'danger');
            }
        })
        .catch(error => {
            console.error('Error saving feedback:', error);
            showAlert('Error saving feedback.', 'danger');
        });
    }

    function applyFilters() {
        // Update filter values
        currentFilters.dateRange = dateRangeFilter.value;
        currentFilters.teacher = teacherFilter.value;
        currentFilters.confidence = confidenceFilter.value;
        currentFilters.feedback = feedbackFilter.value;
        
        // Reset to first page and load data
        currentPage = 1;
        loadData();
    }

    function exportToCsv() {
        // Get all records for export (ignoring pagination)
        const params = new URLSearchParams({
            export: true,
            dateRange: currentFilters.dateRange,
            teacher: currentFilters.teacher,
            confidence: currentFilters.confidence,
            feedback: currentFilters.feedback
        });
        
        fetch(`/api/get_ocr_attempts.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    // Convert to CSV
                    const csvContent = convertToCSV(data.data);
                    
                    // Create a download link
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.setAttribute('href', url);
                    link.setAttribute('download', `ocr_analysis_${formatDate(new Date())}.csv`);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    showAlert('No data available to export.', 'warning');
                }
            })
            .catch(error => {
                console.error('Error exporting data:', error);
                showAlert('Error exporting data.', 'danger');
            });
    }

    function convertToCSV(data) {
        const headers = [
            'ID', 'Teacher', 'Date', 'Extracted Text', 
            'Detected Mark', 'Confidence (%)', 'Final Mark', 'Feedback'
        ];
        
        let csvRows = [headers.join(',')];
        
        data.forEach(item => {
            // Format CSV values, escape commas and quotes
            const date = new Date(item.created_at);
            const formattedDate = formatDate(date);
            
            const row = [
                item.id,
                `"${item.teacher_name.replace(/"/g, '""')}"`,
                formattedDate,
                `"${item.ocr_text.replace(/"/g, '""')}"`,
                item.selected_mark,
                Math.round(item.confidence),
                item.feedback_mark || item.selected_mark,
                item.feedback || 'No Feedback'
            ];
            
            csvRows.push(row.join(','));
        });
        
        return csvRows.join('\n');
    }

    // Charts
    let accuracyChart, confidenceChart, usageChart;
    
    function initializeCharts() {
        const ctx1 = document.getElementById('accuracyChart').getContext('2d');
        const ctx2 = document.getElementById('confidenceChart').getContext('2d');
        const ctx3 = document.getElementById('usageChart').getContext('2d');
        
        // Accuracy Chart
        accuracyChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Accuracy Rate (%)',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
        
        // Confidence Chart
        confidenceChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Average Confidence (%)',
                    data: [],
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
        
        // Usage Chart
        usageChart = new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'OCR Scans',
                    data: [],
                    backgroundColor: 'rgba(153, 102, 255, 0.6)',
                    borderColor: 'rgb(153, 102, 255)',
                    borderWidth: 1
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
        
        // Add click handler for chart button
        document.getElementById('chartTabs').addEventListener('shown.bs.tab', function(event) {
            const tabId = event.target.getAttribute('id');
            if (tabId === 'accuracy-tab') {
                accuracyChart.update();
            } else if (tabId === 'confidence-tab') {
                confidenceChart.update();
            } else if (tabId === 'usage-tab') {
                usageChart.update();
            }
        });
        
        document.querySelector('button[data-bs-toggle="modal"][data-bs-target="#ocrChartModal"]')?.addEventListener('click', function() {
            accuracyChart.update();
        });
    }

    function updateCharts(chartData) {
        if (!chartData) return;
        
        // Update Accuracy Chart
        if (chartData.accuracy && chartData.accuracy.labels && chartData.accuracy.data) {
            accuracyChart.data.labels = chartData.accuracy.labels;
            accuracyChart.data.datasets[0].data = chartData.accuracy.data;
            accuracyChart.update();
        }
        
        // Update Confidence Chart
        if (chartData.confidence && chartData.confidence.labels && chartData.confidence.data) {
            confidenceChart.data.labels = chartData.confidence.labels;
            confidenceChart.data.datasets[0].data = chartData.confidence.data;
            confidenceChart.update();
        }
        
        // Update Usage Chart
        if (chartData.usage && chartData.usage.labels && chartData.usage.data) {
            usageChart.data.labels = chartData.usage.labels;
            usageChart.data.datasets[0].data = chartData.usage.data;
            usageChart.update();
        }
    }

    // Utility functions
    function showLoading() {
        ocrAttemptsList.innerHTML = `<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`;
    }

    function hideLoading() {
        // This function doesn't need to do anything as renderOcrAttempts will replace the loading indicator
    }

    function showAlert(message, type = 'success') {
        const alertPlaceholder = document.getElementById('alertPlaceholder') || createAlertPlaceholder();
        
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        alertPlaceholder.appendChild(wrapper);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            const alert = wrapper.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }

    function createAlertPlaceholder() {
        const alertPlaceholder = document.createElement('div');
        alertPlaceholder.id = 'alertPlaceholder';
        alertPlaceholder.className = 'position-fixed top-0 end-0 p-3';
        alertPlaceholder.style.zIndex = '9999';
        document.body.appendChild(alertPlaceholder);
        return alertPlaceholder;
    }

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}); 