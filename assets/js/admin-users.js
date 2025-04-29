document.addEventListener('DOMContentLoaded', () => {
    console.log('Admin users script loaded.');

    const createStudentModal = document.getElementById('createStudentModal');
    const createStudentForm = document.getElementById('createStudentForm');
    const classSelect = document.getElementById('studentClass');
    const parentSelect = document.getElementById('studentParent');
    const userListBody = document.getElementById('userList');
    const createStudentMsg = document.getElementById('createStudentMsg');
    
    // Setup search functionality
    const searchButton = document.getElementById('searchButton');
    const searchInput = document.getElementById('userSearchInput');
    const roleFilter = document.getElementById('roleFilter');
    
    if (searchButton && searchInput) {
        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
                e.preventDefault();
            }
        });
    }

    // --- Load initial data for the page (users table, modal dropdowns) ---
    loadUsers(); // Placeholder
    loadClassesForModal();
    loadParentsForModal();

    // --- Event Listener for Modal Opening (to potentially refresh dropdowns) ---
    if (createStudentModal) {
        createStudentModal.addEventListener('show.bs.modal', event => {
            // Optional: Reload classes/parents if they might change frequently
            // loadClassesForModal();
            // loadParentsForModal();
            if (createStudentMsg) {
                createStudentMsg.innerHTML = ''; // Clear previous messages
            }
            // createStudentForm.reset(); // Optionally reset form on open
        });
    }

    // --- Event Listener for Create Student Form Submission ---
    if (createStudentForm) {
        createStudentForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (createStudentMsg) {
                createStudentMsg.innerHTML = ''; // Clear previous messages
            }
            
            const submitButton = createStudentForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Creating...';
            }

            const formData = new FormData(createStudentForm);
            const data = Object.fromEntries(formData.entries());
            // Ensure parent_id is null if empty string was submitted
            if (data.parent_id === '') {
                data.parent_id = null;
            }
            
            console.log("Submitting new student:", data);

            try {
                const response = await fetch('api/create_student.php', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();

                if (response.ok && result.success) {
                    if (createStudentMsg) {
                        createStudentMsg.innerHTML = '<div class="alert alert-success">Student created successfully!</div>';
                    }
                    createStudentForm.reset();
                    loadUsers(); // Refresh user list
                    
                    // Show success toast
                    showAlert('Student created successfully!', 'success');
                    
                    // Optionally close modal after a delay
                    setTimeout(() => {
                        try {
                            const modal = bootstrap.Modal.getInstance(createStudentModal);
                            if (modal) modal.hide();
                        } catch (e) {
                            console.error('Error closing modal:', e);
                        }
                    }, 1500);
                } else {
                    if (createStudentMsg) {
                        createStudentMsg.innerHTML = `<div class="alert alert-danger">Error: ${(result.message || 'Unknown server error.')}</div>`;
                    }
                    showAlert(`Error: ${result.message || 'Unknown server error.'}`, 'danger');
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                if (createStudentMsg) {
                    createStudentMsg.innerHTML = '<div class="alert alert-danger">An error occurred. Check console.</div>';
                }
                showAlert('An error occurred while creating student', 'danger');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Create Student';
                }
            }
        });
    }
    
    // Setup search functionality
    function performSearch() {
        const searchTerm = searchInput?.value || '';
        const roleSelection = roleFilter?.value || '';
        console.log(`Searching for: "${searchTerm}", Role: "${roleSelection}"`);
        
        // TODO: Implement API search endpoint
        // For now, just reload users and filter client-side
        loadUsers(searchTerm, roleSelection);
    }

    // Get references to link student-parent elements
    const linkStudentParentForm = document.getElementById('linkStudentParentForm');
    const linkStudentParentMsg = document.getElementById('linkStudentParentMsg');
    const linkStudentSelect = document.getElementById('linkStudentSelect');
    const linkParentSelect = document.getElementById('linkParentSelect');
    
    // For linking students to parents
    if (linkStudentParentForm) {
        // Load students and parents when the modal opens
        const linkModal = document.getElementById('linkStudentParentModal');
        if (linkModal) {
            linkModal.addEventListener('show.bs.modal', () => {
                loadStudentsForLinking();
                loadParentsForLinking();
            });
        }
        
        // Handle form submission
        linkStudentParentForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            
            if (linkStudentParentMsg) {
                linkStudentParentMsg.innerHTML = ''; // Clear previous messages
            }
            
            const submitButton = linkStudentParentForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Linking...';
            }
            
            const formData = new FormData(linkStudentParentForm);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/link_student_parent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    // Show success message
                    if (linkStudentParentMsg) {
                        linkStudentParentMsg.innerHTML = `
                            <div class="alert alert-success">
                                ${result.message}
                            </div>
                        `;
                    }
                    
                    // Reset form
                    linkStudentParentForm.reset();
                    
                    // Optionally close modal after a delay
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('linkStudentParentModal'));
                        if (modal) modal.hide();
                    }, 2000);
                } else {
                    // Show error message
                    if (linkStudentParentMsg) {
                        linkStudentParentMsg.innerHTML = `
                            <div class="alert alert-danger">
                                ${result.message || 'Failed to link student to parent.'}
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Error linking student to parent:', error);
                if (linkStudentParentMsg) {
                    linkStudentParentMsg.innerHTML = `
                        <div class="alert alert-danger">
                            An error occurred: ${error.message}
                        </div>
                    `;
                }
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Link Student to Parent';
                }
            }
        });
    }
});

// --- Function to load user list ---
async function loadUsers(searchTerm = '', roleFilter = '') {
    const userListBody = document.getElementById('userList');
    if (!userListBody) {
        console.error('User list table body not found');
        return;
    }
    
    userListBody.innerHTML = '<tr><td colspan="6">Loading users...</td></tr>';
    
    try {
        // In a real implementation, you would pass the search params to the API
        const response = await fetch('api/get_users.php');
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        const result = await response.json();
        
        if (result.success && Array.isArray(result.data)) {
            // Filter the results client-side if search terms are provided
            let filteredData = result.data;
            if (searchTerm || roleFilter) {
                filteredData = result.data.filter(user => {
                    const matchesSearch = !searchTerm || 
                        user.full_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        user.username?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        user.email?.toLowerCase().includes(searchTerm.toLowerCase());
                    
                    const matchesRole = !roleFilter || user.role === roleFilter;
                    
                    return matchesSearch && matchesRole;
                });
            }
            
            if (filteredData.length === 0) {
                userListBody.innerHTML = '<tr><td colspan="6">No users found matching your criteria.</td></tr>';
                return;
            }
            
            userListBody.innerHTML = '';
            filteredData.forEach(user => {
                const row = document.createElement('tr');
                row.setAttribute('data-user-id', user.id);
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.full_name || 'N/A'}</td>
                    <td>${user.username || 'N/A'}</td>
                    <td><span class="badge bg-${getRoleBadgeColor(user.role)}">${user.role || 'N/A'}</span></td>
                    <td>${user.email || 'N/A'}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary" onclick="editUser(${user.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDeleteUser(${user.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                userListBody.appendChild(row);
            });
        } else {
            userListBody.innerHTML = '<tr><td colspan="6">Error: Unexpected data format.</td></tr>';
            console.error('Invalid response format:', result);
        }
    } catch (error) {
        console.error('Error loading users:', error);
        userListBody.innerHTML = `<tr><td colspan="6">Error loading users: ${error.message}</td></tr>`;
    }
}

// Helper function to determine badge color based on role
function getRoleBadgeColor(role) {
    switch (role?.toLowerCase()) {
        case 'admin': return 'danger';
        case 'teacher': return 'primary';
        case 'parent': return 'success';
        case 'student': return 'info';
        case 'staff': return 'warning';
        default: return 'secondary';
    }
}

// User action functions
async function editUser(userId) {
    console.log(`Editing user ${userId}`);
    
    try {
        // Show loading indicator
        showLoading('Loading User Data', 'Please wait while we fetch the user information.');
        
        // Fetch user data
        const response = await fetch(`api/get_user.php?id=${userId}`);
        
        // Hide loading indicator
        hideLoading();
        
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            // Open edit modal with user data
            openEditUserModal(result.data);
        } else {
            console.error('Error fetching user data:', result.message || 'Unknown error');
            showAlert('Error loading user data', 'danger');
        }
    } catch (error) {
        console.error('Error loading user data:', error);
        showAlert(`Error: ${error.message}`, 'danger');
        hideLoading();
    }
}

async function confirmDeleteUser(userId) {
    console.log(`Deleting user ${userId}`);
    
    // Get the user's name from the DOM
    let userName = "this user";
    try {
        const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
        if (userRow) {
            const nameCell = userRow.querySelector('td:nth-child(2)');
            if (nameCell) userName = nameCell.textContent.trim();
        }
    } catch (e) {
        console.error('Error getting user name:', e);
    }
    
    if (confirm(`Are you sure you want to delete ${userName}? This action cannot be undone.`)) {
        try {
            // Show loading indicator
            showAlert('Deleting user...', 'info');
            
            const response = await fetch('api/delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showAlert('User deleted successfully', 'success');
                // Refresh user list
                loadUsers();
            } else {
                console.error('Error deleting user:', result.message || 'Unknown error');
                showAlert(`Error: ${result.message || 'Unknown error'}`, 'danger');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            showAlert(`Error: ${error.message}`, 'danger');
        }
    }
}

// Function to open edit user modal
function openEditUserModal(userData) {
    // TODO: Implement a proper edit modal with form
    alert('Edit functionality coming soon!\nUser data: ' + JSON.stringify(userData, null, 2));
}

// --- Function to load Classes into Modal ---
async function loadClassesForModal() {
    const classSelect = document.getElementById('studentClass');
    if (!classSelect) {
        console.error('Class select element not found');
        return;
    }
    
    classSelect.disabled = true;
    classSelect.innerHTML = '<option value="">Loading classes...</option>';

    try {
        const response = await fetch('api/get_classes.php');
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        const result = await response.json();

        classSelect.innerHTML = '<option value="">Select Class...</option>'; // Reset
        
        if (result.success && Array.isArray(result.data)) {
            if (result.data.length > 0) {
                result.data.forEach(cls => {
                    const option = document.createElement('option');
                    option.value = cls.id;
                    // Assuming class object has name and year
                    option.textContent = `${cls.name} (${cls.year || 'Year N/A'})`; 
                    classSelect.appendChild(option);
                });
            } else {
                classSelect.innerHTML = '<option value="">No classes found</option>';
            }
        } else {
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
            console.error('Invalid response format:', result);
        }
    } catch (error) {
        console.error('Error loading classes:', error);
        classSelect.innerHTML = '<option value="">Error loading classes</option>';
    } finally {
        classSelect.disabled = false;
    }
}

// --- Function to load Parent Users into Modal ---
async function loadParentsForModal() {
    const parentSelect = document.getElementById('studentParent');
    if (!parentSelect) {
        console.error('Parent select element not found');
        return;
    }
    
    parentSelect.disabled = true;
    parentSelect.innerHTML = '<option value="">Loading parents...</option>';

    try {
        // Assumes an API exists to get users specifically with the 'parent' role
        const response = await fetch('api/get_users.php?role=parent');
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        const result = await response.json();
        
        parentSelect.innerHTML = '<option value="">Select Parent (Optional)...</option>'; // Reset
        
        if (result.success && Array.isArray(result.data)) {
            if (result.data.length > 0) {
                result.data.forEach(parent => {
                    const option = document.createElement('option');
                    option.value = parent.id; // Assuming parent user ID is needed for students.parent_id
                    option.textContent = `${parent.full_name} (${parent.username || 'Username N/A'})`; 
                    parentSelect.appendChild(option);
                });
            } else {
                parentSelect.innerHTML = '<option value="">No parent accounts found</option>';
            }
        } else {
            parentSelect.innerHTML = '<option value="">Error loading parents</option>';
            console.error('Invalid response format:', result);
        }
    } catch (error) {
        console.error('Error loading parents:', error);
        parentSelect.innerHTML = '<option value="">Error loading parents</option>';
    } finally {
        parentSelect.disabled = false;
    }
}

// --- Function to load Students for the linking modal ---
async function loadStudentsForLinking() {
    if (!linkStudentSelect) {
        console.error('Student select element for linking not found');
        return;
    }
    
    linkStudentSelect.disabled = true;
    linkStudentSelect.innerHTML = '<option value="">Loading students...</option>';
    
    try {
        const response = await fetch('api/get_students.php');
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        const result = await response.json();
        
        linkStudentSelect.innerHTML = '<option value="">Select Student...</option>'; // Reset
        
        if (result.success && Array.isArray(result.data)) {
            if (result.data.length > 0) {
                result.data.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = `${student.full_name} (${student.admission_number})`;
                    // Indicate if student already has a parent
                    if (student.parent_id) {
                        option.textContent += ' - Already linked';
                        option.classList.add('text-danger');
                    }
                    linkStudentSelect.appendChild(option);
                });
            } else {
                linkStudentSelect.innerHTML = '<option value="">No students found</option>';
            }
        } else {
            linkStudentSelect.innerHTML = '<option value="">Error loading students</option>';
            console.error('Invalid response format:', result);
        }
    } catch (error) {
        console.error('Error loading students:', error);
        linkStudentSelect.innerHTML = '<option value="">Error loading students</option>';
    } finally {
        linkStudentSelect.disabled = false;
    }
}

// --- Function to load Parents for the linking modal ---
async function loadParentsForLinking() {
    if (!linkParentSelect) {
        console.error('Parent select element for linking not found');
        return;
    }
    
    linkParentSelect.disabled = true;
    linkParentSelect.innerHTML = '<option value="">Loading parents...</option>';
    
    try {
        const response = await fetch('api/get_users.php?role=parent');
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        const result = await response.json();
        
        linkParentSelect.innerHTML = '<option value="">Select Parent...</option>'; // Reset
        
        if (result.success && Array.isArray(result.data)) {
            if (result.data.length > 0) {
                result.data.forEach(parent => {
                    const option = document.createElement('option');
                    option.value = parent.id;
                    option.textContent = `${parent.full_name} (${parent.username || 'Username N/A'})`;
                    linkParentSelect.appendChild(option);
                });
            } else {
                linkParentSelect.innerHTML = '<option value="">No parent accounts found</option>';
            }
        } else {
            linkParentSelect.innerHTML = '<option value="">Error loading parents</option>';
            console.error('Invalid response format:', result);
        }
    } catch (error) {
        console.error('Error loading parents:', error);
        linkParentSelect.innerHTML = '<option value="">Error loading parents</option>';
    } finally {
        linkParentSelect.disabled = false;
    }
} 