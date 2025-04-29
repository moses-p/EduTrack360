<?php
$page_title = "Settings";

// Default content
$content = <<<HTML
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Settings</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="list">General</a>
                <a href="#appearance" class="list-group-item list-group-item-action" data-bs-toggle="list">Appearance</a>
                <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">Notifications</a>
                <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">Security</a>
                <a href="#backup" class="list-group-item list-group-item-action" data-bs-toggle="list">Backup & Restore</a>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="general">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <form id="generalSettingsForm">
                            <div class="mb-3">
                                <label for="siteName" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="siteName" name="site_name" value="EduTrack360">
                            </div>
                            <div class="mb-3">
                                <label for="siteDescription" class="form-label">Site Description</label>
                                <textarea class="form-control" id="siteDescription" name="site_description" rows="2">Performance Management System for Educational Institutions</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="timeZone" class="form-label">Time Zone</label>
                                <select class="form-select" id="timeZone" name="time_zone">
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">Eastern Time (ET)</option>
                                    <option value="America/Chicago">Central Time (CT)</option>
                                    <option value="America/Denver">Mountain Time (MT)</option>
                                    <option value="America/Los_Angeles">Pacific Time (PT)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="dateFormat" class="form-label">Date Format</label>
                                <select class="form-select" id="dateFormat" name="date_format">
                                    <option value="Y-m-d">YYYY-MM-DD</option>
                                    <option value="m/d/Y">MM/DD/YYYY</option>
                                    <option value="d/m/Y">DD/MM/YYYY</option>
                                    <option value="M j, Y">Month Day, Year</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="appearance">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Appearance Settings</h5>
                    </div>
                    <div class="card-body">
                        <form id="appearanceSettingsForm">
                            <div class="mb-3">
                                <label for="theme" class="form-label">Theme</label>
                                <select class="form-select" id="theme" name="theme">
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="system">Use System Setting</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="primaryColor" class="form-label">Primary Color</label>
                                <input type="color" class="form-control form-control-color" id="primaryColor" name="primary_color" value="#0d6efd">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="notifications">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Notification Settings</h5>
                    </div>
                    <div class="card-body">
                        <form id="notificationSettingsForm">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" name="email_notifications" checked>
                                <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="taskNotifications" name="task_notifications" checked>
                                <label class="form-check-label" for="taskNotifications">Task Assignments</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="systemNotifications" name="system_notifications" checked>
                                <label class="form-check-label" for="systemNotifications">System Updates</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="security">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Security Settings</h5>
                    </div>
                    <div class="card-body">
                        <form id="securitySettingsForm">
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" name="current_password">
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newPassword" name="new_password">
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password">
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="backup">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Backup & Restore</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6>Create Backup</h6>
                            <p>Create a backup of your system data:</p>
                            <button id="createBackupBtn" class="btn btn-primary">Create Backup</button>
                        </div>
                        <div class="mb-4">
                            <h6>Restore from Backup</h6>
                            <p>Upload a backup file to restore your system data:</p>
                            <div class="mb-3">
                                <input class="form-control" type="file" id="backupFile">
                            </div>
                            <button id="restoreBackupBtn" class="btn btn-warning">Restore from Backup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

// Add page-specific scripts
$page_scripts = [];
?> 