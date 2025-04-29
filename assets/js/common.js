/**
 * Common utility functions for the EduTrack360 System
 */

// Helper function for making API requests
async function makeRequest(url, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error making request:', error);
        throw error;
    }
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

// Helper function to create a loading modal
function createLoadingModal(message = 'Loading...', description = 'Please wait while we process your request.') {
    // Check if modal already exists
    let modal = document.getElementById('loadingModal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'loadingModal';
        modal.setAttribute('data-bs-backdrop', 'static');
        modal.setAttribute('data-bs-keyboard', 'false');
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'loadingModalLabel');
        modal.setAttribute('aria-hidden', 'true');
        
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 id="loadingModalLabel">${message}</h5>
                        <p class="text-muted">${description}</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    } else {
        // Update the message if it already exists
        document.getElementById('loadingModalLabel').textContent = message;
    }
    
    return modal;
}

// Helper function to show loading
function showLoading(message = 'Loading...', description = 'Please wait while we process your request.') {
    const modal = createLoadingModal(message, description);
    return new bootstrap.Modal(modal).show();
}

// Helper function to hide loading
function hideLoading() {
    try {
        const loadingModalEl = document.getElementById('loadingModal');
        if (loadingModalEl) {
            const loadingModal = bootstrap.Modal.getInstance(loadingModalEl);
            if (loadingModal) loadingModal.hide();
        }
    } catch (e) {
        console.error('Error hiding loading modal:', e);
    }
}

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    if (isNaN(date)) return dateString;
    
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Helper function for updating progress bars
function updateProgressBar(elementId, value, max = 100) {
    const progressBar = document.getElementById(elementId);
    if (progressBar) {
        const percentage = (value / max) * 100;
        progressBar.style.width = `${percentage}%`;
        progressBar.setAttribute('aria-valuenow', value);
        
        // Update color based on value
        if (percentage < 30) {
            progressBar.className = 'progress-bar bg-success';
        } else if (percentage < 70) {
            progressBar.className = 'progress-bar bg-warning';
        } else {
            progressBar.className = 'progress-bar bg-danger';
        }
    }
}

// Helper function for creating charts
function createChart(canvasId, type, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (ctx) {
        return new Chart(ctx, {
            type: type,
            data: data,
            options: options
        });
    }
    return null;
}

// Helper function for form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Helper function for loading content
async function loadContent(url, containerId) {
    try {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
        
        const response = await makeRequest(url);
        container.innerHTML = response.html || response;
    } catch (error) {
        console.error('Error loading content:', error);
        showAlert('Failed to load content', 'danger');
    }
}