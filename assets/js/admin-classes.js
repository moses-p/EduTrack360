/**
 * EduTrack360 - Class Management JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize
    loadClasses();
    
    // Event Listeners
    document.getElementById('saveClassBtn').addEventListener('click', saveClass);
    document.getElementById('updateClassBtn').addEventListener('click', updateClass);
    document.getElementById('searchButton').addEventListener('click', function() {
        loadClasses(1, document.getElementById('searchClasses').value);
    });
    
    // Search on Enter key
    document.getElementById('searchClasses').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            loadClasses(1, this.value);
        }
    });
});

/**
 * Load classes with pagination and search
 */
function loadClasses(page = 1, search = '') {
    // Show loading indicator
    document.getElementById('classesList').innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    // API call to get classes
    fetch(`/api/get_classes.php?page=${page}&search=${encodeURIComponent(search)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                displayClasses(data.classes);
                setupPagination(data.pagination);
            } else {
                showAlert('error', data.message || 'Failed to load classes');
            }
        })
        .catch(error => {
            console.error('Error fetching classes:', error);
            document.getElementById('classesList').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading classes. Please try again.</td></tr>';
        });
}

/**
 * Display classes in the table
 */
function displayClasses(classes) {
    const tableBody = document.getElementById('classesList');
    
    if (classes.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No classes found</td></tr>';
        return;
    }
    
    let html = '';
    classes.forEach(classItem => {
        const statusBadge = getStatusBadge(classItem.status);
        
        html += `
        <tr>
            <td>${classItem.id}</td>
            <td>${classItem.name}</td>
            <td>${classItem.year}</td>
            <td>Term ${classItem.term}</td>
            <td>${statusBadge}</td>
            <td>${classItem.student_count || 0}</td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editClass(${classItem.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteClass(${classItem.id}, '${classItem.name}')">
                        <i class="bi bi-trash"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewStudents(${classItem.id}, '${classItem.name}')">
                        <i class="bi bi-people"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    });
    
    tableBody.innerHTML = html;
}

/**
 * Get appropriate badge for class status
 */
function getStatusBadge(status) {
    switch(status) {
        case 'active':
            return '<span class="badge bg-success">Active</span>';
        case 'inactive':
            return '<span class="badge bg-warning text-dark">Inactive</span>';
        case 'completed':
            return '<span class="badge bg-secondary">Completed</span>';
        default:
            return '<span class="badge bg-light text-dark">Unknown</span>';
    }
}

/**
 * Setup pagination controls
 */
function setupPagination(pagination) {
    const paginationEl = document.getElementById('classesPagination');
    
    if (!pagination || pagination.total_pages <= 1) {
        paginationEl.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    html += `
    <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadClasses(${pagination.current_page - 1}, document.getElementById('searchClasses').value); return false;">
            <span aria-hidden="true">&laquo;</span>
        </a>
    </li>`;
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (
            i === 1 || 
            i === pagination.total_pages || 
            (i >= pagination.current_page - 1 && i <= pagination.current_page + 1)
        ) {
            html += `
            <li class="page-item ${pagination.current_page === i ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadClasses(${i}, document.getElementById('searchClasses').value); return false;">${i}</a>
            </li>`;
        } else if (
            i === pagination.current_page - 2 || 
            i === pagination.current_page + 2
        ) {
            html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
        }
    }
    
    // Next button
    html += `
    <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadClasses(${pagination.current_page + 1}, document.getElementById('searchClasses').value); return false;">
            <span aria-hidden="true">&raquo;</span>
        </a>
    </li>`;
    
    paginationEl.innerHTML = html;
}

/**
 * Save a new class
 */
function saveClass() {
    const form = document.getElementById('addClassForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const classData = {
        name: formData.get('name'),
        year: formData.get('year'),
        term: formData.get('term'),
        status: formData.get('status')
    };
    
    // API call to save class
    fetch('/api/add_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(classData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('addClassModal')).hide();
            
            // Clear form
            form.reset();
            
            // Show success message and reload classes
            showAlert('success', 'Class added successfully');
            loadClasses();
        } else {
            showAlert('error', data.message || 'Failed to add class');
        }
    })
    .catch(error => {
        console.error('Error adding class:', error);
        showAlert('error', 'An error occurred. Please try again.');
    });
}

/**
 * Edit class - load data into modal
 */
function editClass(classId) {
    fetch(`/api/get_class.php?id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const classData = data.class;
                
                // Fill in form fields
                document.getElementById('editClassId').value = classData.id;
                document.getElementById('editClassName').value = classData.name;
                document.getElementById('editClassYear').value = classData.year;
                document.getElementById('editClassTerm').value = classData.term;
                document.getElementById('editClassStatus').value = classData.status;
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editClassModal'));
                modal.show();
            } else {
                showAlert('error', data.message || 'Failed to load class data');
            }
        })
        .catch(error => {
            console.error('Error loading class data:', error);
            showAlert('error', 'An error occurred. Please try again.');
        });
}

/**
 * Update an existing class
 */
function updateClass() {
    const form = document.getElementById('editClassForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const classData = {
        id: formData.get('id'),
        name: formData.get('name'),
        year: formData.get('year'),
        term: formData.get('term'),
        status: formData.get('status')
    };
    
    // API call to update class
    fetch('/api/update_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(classData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('editClassModal')).hide();
            
            // Show success message and reload classes
            showAlert('success', 'Class updated successfully');
            loadClasses();
        } else {
            showAlert('error', data.message || 'Failed to update class');
        }
    })
    .catch(error => {
        console.error('Error updating class:', error);
        showAlert('error', 'An error occurred. Please try again.');
    });
}

/**
 * Confirm deletion of a class
 */
function confirmDeleteClass(classId, className) {
    if (confirm(`Are you sure you want to delete class "${className}"?\nThis action cannot be undone.`)) {
        deleteClass(classId);
    }
}

/**
 * Delete a class
 */
function deleteClass(classId) {
    fetch('/api/delete_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: classId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert('success', 'Class deleted successfully');
            loadClasses();
        } else {
            showAlert('error', data.message || 'Failed to delete class');
        }
    })
    .catch(error => {
        console.error('Error deleting class:', error);
        showAlert('error', 'An error occurred. Please try again.');
    });
}

/**
 * View students in a class
 */
function viewStudents(classId, className) {
    // Redirect to students page filtered by class
    window.location.href = `/admin/students.php?class_id=${classId}&class_name=${encodeURIComponent(className)}`;
}

/**
 * Show alert message
 */
function showAlert(type, message) {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertContainer.setAttribute('role', 'alert');
    alertContainer.style.zIndex = '9999';
    
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertContainer);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertContainer);
        bsAlert.close();
    }, 5000);
} 