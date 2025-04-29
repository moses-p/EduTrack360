document.addEventListener('DOMContentLoaded', function() {
    // Initialize the dashboard
    initStaffDashboard();
});

function initStaffDashboard() {
    // Fetch dashboard data
    fetchTaskStats();
    fetchRecentTasks();
    fetchAnnouncements();
}

function fetchTaskStats() {
    // Mock data - in production, this would fetch from the server
    setTimeout(() => {
        const pendingTasks = 3;
        const completedTasks = 7;
        const totalTasks = pendingTasks + completedTasks;
        const completionRate = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
        
        // Update UI
        document.getElementById('pendingTasksCount').textContent = pendingTasks;
        document.getElementById('completedTasksCount').textContent = completedTasks;
        document.getElementById('taskCompletionRate').textContent = completionRate + '%';
        
        const progressBar = document.getElementById('taskProgressBar');
        if (progressBar) {
            progressBar.style.width = completionRate + '%';
            progressBar.setAttribute('aria-valuenow', completionRate);
        }
    }, 500);
}

function fetchRecentTasks() {
    // Mock data - in production, this would be an AJAX call
    setTimeout(() => {
        const tasks = [
            { id: 1, title: 'Review monthly budget', dueDate: '2025-05-05', priority: 'high', status: 'pending' },
            { id: 2, title: 'Update staff handbook', dueDate: '2025-05-10', priority: 'medium', status: 'in-progress' },
            { id: 3, title: 'Organize staff meeting', dueDate: '2025-05-15', priority: 'medium', status: 'pending' }
        ];
        
        const tbody = document.querySelector('#recentTasksTable tbody');
        if (tbody) {
            tbody.innerHTML = '';
            
            if (tasks.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No tasks found</td></tr>';
                return;
            }
            
            tasks.forEach(task => {
                const priorityBadge = getPriorityBadge(task.priority);
                const statusBadge = getStatusBadge(task.status);
                
                const row = `
                    <tr>
                        <td>${task.title}</td>
                        <td>${task.dueDate}</td>
                        <td>${priorityBadge}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-primary view-task" data-task-id="${task.id}">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-success complete-task" data-task-id="${task.id}">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </td>
                    </tr>
                `;
                
                tbody.innerHTML += row;
            });
            
            // Add event listeners to buttons
            document.querySelectorAll('.view-task').forEach(btn => {
                btn.addEventListener('click', function() {
                    const taskId = this.getAttribute('data-task-id');
                    viewTask(taskId);
                });
            });
            
            document.querySelectorAll('.complete-task').forEach(btn => {
                btn.addEventListener('click', function() {
                    const taskId = this.getAttribute('data-task-id');
                    completeTask(taskId);
                });
            });
        }
    }, 700);
}

function fetchAnnouncements() {
    // Mock data - in production, this would be an AJAX call
    setTimeout(() => {
        const announcements = [
            { id: 1, title: 'Staff Meeting', content: 'There will be a staff meeting on Friday at 2:00 PM.', date: '2025-04-28' },
            { id: 2, title: 'New Leave Policy', content: 'Please review the updated leave policy in the staff handbook.', date: '2025-04-25' }
        ];
        
        const container = document.getElementById('announcementsContainer');
        if (container) {
            // Keep the welcome announcement
            const welcomeAlert = container.querySelector('.alert-info');
            container.innerHTML = '';
            container.appendChild(welcomeAlert);
            
            // Update announcement count
            document.getElementById('announcementsCount').textContent = announcements.length;
            
            announcements.forEach(announcement => {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-secondary mt-2';
                alertDiv.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <h5>${announcement.title}</h5>
                        <small>${announcement.date}</small>
                    </div>
                    <p>${announcement.content}</p>
                `;
                
                container.appendChild(alertDiv);
            });
        }
    }, 600);
}

function getPriorityBadge(priority) {
    switch (priority) {
        case 'high':
            return '<span class="badge bg-danger">High</span>';
        case 'medium':
            return '<span class="badge bg-warning text-dark">Medium</span>';
        case 'low':
            return '<span class="badge bg-info text-dark">Low</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

function getStatusBadge(status) {
    switch (status) {
        case 'pending':
            return '<span class="badge bg-secondary">Pending</span>';
        case 'in-progress':
            return '<span class="badge bg-primary">In Progress</span>';
        case 'completed':
            return '<span class="badge bg-success">Completed</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

function viewTask(taskId) {
    // In production, this would open a task detail view
    console.log('Viewing task:', taskId);
    alert('Viewing task: ' + taskId);
}

function completeTask(taskId) {
    // In production, this would update the task status
    console.log('Completing task:', taskId);
    alert('Task ' + taskId + ' marked as complete!');
    
    // Refresh data after action
    fetchTaskStats();
    fetchRecentTasks();
} 