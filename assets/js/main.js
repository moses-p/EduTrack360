/**
 * Main JavaScript file for the EduTrack360 system
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar toggle
    initSidebar();
    
    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

/**
 * Initialize sidebar toggle functionality
 */
function initSidebar() {
    // Get the sidebar toggle buttons
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    // If sidebar elements don't exist, exit the function
    if (!sidebar || !content) {
        return;
    }
    
    // Toggle sidebar on mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            content.classList.toggle('active');
        });
    }
    
    // Close sidebar on mobile
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.remove('active');
            content.classList.remove('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const target = event.target;
        
        // Only apply this on mobile view
        if (window.innerWidth <= 768 && sidebar) {
            // Check if sidebar is active and click is outside sidebar
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(target) && 
                target !== sidebarToggle &&
                sidebarToggle && !sidebarToggle.contains(target)) {
                
                sidebar.classList.remove('active');
                content.classList.remove('active');
            }
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        // Reset sidebar and content classes on desktop view
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('active');
            content.classList.remove('active');
        }
    });
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

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

// AJAX Helper
function makeRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url);
        xhr.setRequestHeader('Content-Type', 'application/json');
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(xhr.statusText);
            }
        };
        
        xhr.onerror = function() {
            reject(xhr.statusText);
        };
        
        xhr.send(data ? JSON.stringify(data) : null);
    });
}

// Date Format Helper
function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Show Alert
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Look for container-fluid first, then fall back to container if not found
    const container = document.querySelector('.container-fluid') || document.querySelector('.container');
    
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    } else {
        console.warn('No container found to show alert:', message);
    }
}

// Chart Helper
function createChart(ctx, type, data, options = {}) {
    return new Chart(ctx, {
        type: type,
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            ...options
        }
    });
} 