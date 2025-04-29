<?php
$page_title = "Profile";

// Default content
$content = <<<HTML
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="assets/images/default-avatar.png" class="rounded-circle img-fluid mb-3" style="width: 120px;">
                <h5 class="mb-1">{$_SESSION['full_name']}</h5>
                <p class="text-muted mb-3">{$_SESSION['role']}</p>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateProfilePicModal">
                    <i class="bi bi-camera"></i> Change Photo
                </button>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Contact Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Email</label>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-envelope me-2"></i>
                        <span id="userEmail">user@example.com</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Phone</label>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-telephone me-2"></i>
                        <span id="userPhone">+1 (555) 123-4567</span>
                    </div>
                </div>
                <div>
                    <label class="form-label small text-muted">Location</label>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-geo-alt me-2"></i>
                        <span id="userLocation">City, Country</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Edit Profile</h5>
            </div>
            <div class="card-body">
                <form id="profileForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" value="">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" value="">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" value="">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Password</h5>
            </div>
            <div class="card-body">
                <form id="passwordForm">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword">
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword">
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword">
                    </div>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Profile Picture Modal -->
<div class="modal fade" id="updateProfilePicModal" tabindex="-1" aria-labelledby="updateProfilePicModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateProfilePicModalLabel">Change Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="profilePicForm">
                    <div class="mb-3">
                        <label for="profilePic" class="form-label">Select a new profile picture</label>
                        <input class="form-control" type="file" id="profilePic" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-center">
                            <img id="profilePicPreview" src="assets/images/default-avatar.png" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProfilePicBtn">Upload Picture</button>
            </div>
        </div>
    </div>
</div>
HTML;

// Add page-specific scripts
 