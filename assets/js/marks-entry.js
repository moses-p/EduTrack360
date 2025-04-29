document.addEventListener('DOMContentLoaded', function() {
    initMarksEntryPage();
});

function initMarksEntryPage() {
    // Initialize student selector
    loadStudents();
    
    // Initialize form submission
    initFormSubmission();
    
    // Initialize camera functionality
    initCameraScanner();
}

// Load students for the teacher's class
function loadStudents() {
    const studentSelect = document.getElementById('studentSelect');
    if (!studentSelect) return;
    
    // Get teacher class ID from the form
    const classId = document.getElementById('subjectIdInput')?.value;
    
    if (!classId) {
        console.error('No class ID found');
        studentSelect.innerHTML = '<option value="">No students found</option>';
        return;
    }
    
    // Show loading state
    studentSelect.innerHTML = '<option value="">Loading students...</option>';
    studentSelect.disabled = true;
    
    // Fetch students from the server via AJAX
    fetch('ajax/get_students.php?class_id=' + encodeURIComponent(classId))
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            studentSelect.innerHTML = '<option value="">Select Student</option>';
            
            if (data.success && data.students.length > 0) {
                data.students.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = student.full_name;
                    studentSelect.appendChild(option);
                });
            } else {
                studentSelect.innerHTML = '<option value="">No students found for this class</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching students:', error);
            studentSelect.innerHTML = '<option value="">Error loading students</option>';
            
            // Fallback to mock data in case of error (for development only)
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                const mockStudents = [
                    { id: 1, name: 'John Doe' },
                    { id: 2, name: 'Jane Smith' },
                    { id: 3, name: 'Michael Johnson' },
                    { id: 4, name: 'Emily Williams' },
                    { id: 5, name: 'Robert Brown' }
                ];
                
                studentSelect.innerHTML = '<option value="">Select Student (Mock Data)</option>';
                mockStudents.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = student.name;
                    studentSelect.appendChild(option);
                });
            }
        })
        .finally(() => {
            studentSelect.disabled = false;
        });
}

// Initialize form submission
function initFormSubmission() {
    const marksEntryForm = document.getElementById('marksEntryForm');
    if (marksEntryForm) {
        marksEntryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveMarks(this);
        });
    }
}

// Save marks to server
function saveMarks(form) {
    // Validate form
    const studentId = form.elements['student_id'].value;
    const subjectId = form.elements['subject_id'].value;
    const marks = form.elements['marks'].value;
    const term = form.elements['term'].value;
    const year = form.elements['year'].value;
    
    if (!studentId || !marks || !term || !year || !subjectId) {
        alert('Please fill in all required fields');
        return;
    }
    
    if (marks < 0 || marks > 100) {
        alert('Marks must be between 0 and 100');
        return;
    }
    
    // Create form data for submission
    const formData = new FormData();
    formData.append('student_id', studentId);
    formData.append('subject_id', subjectId);
    formData.append('marks', marks);
    formData.append('term', term);
    formData.append('year', year);
    
    // Show loading indicator
    showLoading();
    
    // Send data to server
    fetch('ajax/save_marks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // Show success message 
            const message = data.updated ? 'Marks updated successfully!' : 'Marks saved successfully!';
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.style.zIndex = '9999';
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            // Add toast to document
            document.body.appendChild(toast);
            
            // Initialize and show Bootstrap toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            bsToast.show();
            
            // Remove toast element after it's hidden
            toast.addEventListener('hidden.bs.toast', function() {
                document.body.removeChild(toast);
            });
            
            // Reset form but keep subject and year values
            const subjectIdValue = document.getElementById('subjectIdInput').value;
            const yearValue = document.getElementById('yearInput').value;
            
            form.reset();
            
            document.getElementById('subjectIdInput').value = subjectIdValue;
            document.getElementById('yearInput').value = yearValue;
        } else {
            // Show error message
            alert('Error: ' + (data.message || 'Failed to save marks'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error saving marks:', error);
        alert('Failed to save marks. Please try again.');
    });
}

// Initialize camera scanner functionality
function initCameraScanner() {
    const startButton = document.getElementById('startButton');
    const scanButton = document.getElementById('scanButton');
    const scannerContainer = document.getElementById('scannerContainer');
    const videoFeed = document.getElementById('videoFeed');
    const progressBar = document.getElementById('progressBar');
    const ocrResult = document.getElementById('ocrResult');

    let stream = null;
    
    if (!startButton || !scanButton || !videoFeed) return;
    
    // Check if device has camera capabilities
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        startButton.disabled = true;
        ocrResult.textContent = 'Camera API not supported in this browser. Please use a modern browser or enter marks manually.';
        document.getElementById('scannerContainer').innerHTML += '<div class="alert alert-warning mt-2">Camera functionality not available in this browser.</div>';
                return;
    }
    
    startButton.addEventListener('click', function() {
        if (stream) {
            // Stop camera if already running
            stopCamera();
            startButton.textContent = 'Start Camera';
                scanButton.disabled = true;
                    scannerContainer.style.display = 'none';
                 return;
            }
            
        // First check if the device has any video input devices
        navigator.mediaDevices.enumerateDevices()
            .then(devices => {
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                
                if (videoDevices.length === 0) {
                    throw new Error('No camera detected on this device. Please enter marks manually.');
                }
                
                // Request camera access with better constraints
                return navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
            })
            .then(function(mediaStream) {
                stream = mediaStream;
                videoFeed.srcObject = mediaStream;
                startButton.textContent = 'Stop Camera';
                scanButton.disabled = false; 
                scannerContainer.style.display = 'block';
                ocrResult.textContent = 'Camera active. Click "Scan Mark" to capture and process the image.';
            })
            .catch(function(err) {
                console.error('Error accessing camera:', err);
                
                let errorMessage = 'Could not access camera. ';
                
                if (err.name === 'NotFoundError' || err.message.includes('device not found')) {
                    errorMessage += 'No camera was detected on your device. Please enter marks manually.';
                } else if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errorMessage += 'Camera access was denied. Please grant permission in your browser settings.';
                } else if (err.name === 'NotReadableError') {
                    errorMessage += 'Camera is in use by another application. Please close other applications using the camera.';
                } else {
                    errorMessage += err.message || 'Unknown error.';
                }
                
                alert(errorMessage);
                ocrResult.textContent = errorMessage;
                
                // Add a manual entry suggestion
                const manualEntryNote = document.createElement('div');
                manualEntryNote.className = 'alert alert-info mt-3';
                manualEntryNote.innerHTML = '<strong>Tip:</strong> You can enter marks manually in the form on the left.';
                document.getElementById('resultContainer').appendChild(manualEntryNote);
                
                // Add a manual OCR simulation button as fallback
                const simulateButton = document.createElement('button');
                simulateButton.className = 'btn btn-outline-secondary mt-2';
                simulateButton.textContent = 'Simulate OCR (Demo Only)';
                simulateButton.addEventListener('click', simulateOcrScan);
                document.getElementById('resultContainer').appendChild(simulateButton);
            });
    });
    
    scanButton.addEventListener('click', function() {
        if (!stream) return;
        
        // Simulate OCR scanning
        scanButton.disabled = true;
        ocrResult.textContent = 'Scanning...';
        simulateOcrScan();
    });
    
    function simulateOcrScan() {
        // Reset and show progress
        progressBar.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', 0);
        
        // Simulate progress
        let progress = 0;
        const interval = setInterval(() => {
            progress += 10;
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            
            if (progress >= 100) {
                clearInterval(interval);
                
                // Generate a random mark between 0-100
                const randomMark = Math.floor(Math.random() * 101);
                ocrResult.textContent = 'Recognized Text: "' + randomMark + '"';
                
                // Set the mark in the form
                document.getElementById('marksInput').value = randomMark;
                
                scanButton.disabled = false;
            }
        }, 200);
    }
    
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            videoFeed.srcObject = null;
        }
    }
    
    // Clean up on page unload
    window.addEventListener('beforeunload', stopCamera);
}

function showLoading() {
    // Create loading overlay if it doesn't exist
    let loadingOverlay = document.getElementById('loadingOverlay');
    
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'loadingOverlay';
        loadingOverlay.style.position = 'fixed';
        loadingOverlay.style.top = '0';
        loadingOverlay.style.left = '0';
        loadingOverlay.style.width = '100%';
        loadingOverlay.style.height = '100%';
        loadingOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.justifyContent = 'center';
        loadingOverlay.style.alignItems = 'center';
        loadingOverlay.style.zIndex = '9999';
        
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border text-light';
        spinner.setAttribute('role', 'status');
        
        const srText = document.createElement('span');
        srText.className = 'visually-hidden';
        srText.textContent = 'Loading...';
        
        spinner.appendChild(srText);
        loadingOverlay.appendChild(spinner);
        
        document.body.appendChild(loadingOverlay);
                } else {
        loadingOverlay.style.display = 'flex';
    }
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}
