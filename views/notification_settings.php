<?php
// Set page title
$page_title = "Notification Settings";

// Generate content
$content = <<<HTML
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment Reminder Settings</h5>
            </div>
            <div class="card-body">
                <form id="notificationSettingsForm">
                    <div class="mb-3">
                        <label for="days_before_due" class="form-label">Days Before Due Date for First Reminder</label>
                        <input type="number" class="form-control" id="days_before_due" name="days_before_due" min="1" max="30" required>
                        <div class="form-text">Number of days before the due date to send the first reminder</div>
                    </div>
                    <div class="mb-3">
                        <label for="reminder_frequency" class="form-label">Days Between Reminders</label>
                        <input type="number" class="form-control" id="reminder_frequency" name="reminder_frequency" min="1" max="30" required>
                        <div class="form-text">Number of days to wait between sending reminders</div>
                    </div>
                    <div class="mb-3">
                        <label for="max_reminders" class="form-label">Maximum Number of Reminders</label>
                        <input type="number" class="form-control" id="max_reminders" name="max_reminders" min="1" max="10" required>
                        <div class="form-text">Maximum number of reminders to send for each payment</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Email Templates</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item">
                        <h6 class="mb-1">Payment Reminder</h6>
                        <p class="mb-1">Sent before the due date</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="editTemplate('payment_reminder')">
                            Edit Template
                        </button>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Payment Overdue</h6>
                        <p class="mb-1">Sent after the due date</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="editTemplate('payment_overdue')">
                            Edit Template
                        </button>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Payment Confirmation</h6>
                        <p class="mb-1">Sent when payment is received</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="editTemplate('payment_confirmation')">
                            Edit Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template Edit Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Email Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" id="template_id" name="template_id">
                    <div class="mb-3">
                        <label for="template_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="template_subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="template_body" class="form-label">Body</label>
                        <textarea class="form-control" id="template_body" name="body" rows="10" required></textarea>
                        <div class="form-text">
                            Available variables: {student_name}, {amount}, {due_date}, {payment_date}, {transaction_type}, {description}, {days_overdue}
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load current settings
    loadSettings();
    
    // Form submission handler
    document.getElementById('notificationSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings();
    });
});

function loadSettings() {
    fetch('api/get_notification_settings.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('days_before_due').value = data.days_before_due;
            document.getElementById('reminder_frequency').value = data.reminder_frequency;
            document.getElementById('max_reminders').value = data.max_reminders;
        })
        .catch(error => {
            console.error('Error loading settings:', error);
            showAlert('danger', 'Error loading settings');
        });
}

function saveSettings() {
    const formData = new FormData(document.getElementById('notificationSettingsForm'));
    
    fetch('api/update_notification_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Settings saved successfully');
        } else {
            showAlert('danger', data.error || 'Error saving settings');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error saving settings');
    });
}

function editTemplate(name) {
    fetch(`api/get_email_template.php?name=${name}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('template_id').value = data.id;
            document.getElementById('template_subject').value = data.subject;
            document.getElementById('template_body').value = data.body;
            
            const modal = new bootstrap.Modal(document.getElementById('templateModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading template:', error);
            showAlert('danger', 'Error loading template');
        });
}

function saveTemplate() {
    const formData = new FormData(document.getElementById('templateForm'));
    
    fetch('api/update_email_template.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Template saved successfully');
            bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();
        } else {
            showAlert('danger', data.error || 'Error saving template');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error saving template');
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('form'));
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
HTML;
?> 