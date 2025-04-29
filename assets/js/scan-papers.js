document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('videoFeed');
    const canvas = document.getElementById('canvasOverlay');
    const context = canvas.getContext('2d');
    const startButton = document.getElementById('startButton');
    const scanButton = document.getElementById('scanButton');
    const scannerContainer = document.getElementById('scannerContainer');
    const ocrResult = document.getElementById('ocrResult');
    const progressBar = document.getElementById('progressBar');
    const marksForm = document.getElementById('marksForm');
    const confirmedText = document.getElementById('confirmedText');
    const marksInput = document.getElementById('marksInput');
    const saveMarksForm = document.getElementById('saveMarksForm');
    const studentSelect = document.getElementById('studentSelect');
    const subjectSelect = document.getElementById('subjectSelect');
    const enhanceImageToggle = document.getElementById('enhanceImageToggle') || createEnhanceToggle();
    const confidenceIndicator = document.getElementById('confidenceIndicator') || createConfidenceIndicator();

    let stream = null;
    let worker = null; // Tesseract worker
    let lastCapturedImage = null; // Store the last captured image for reprocessing
    let markConfidence = 0; // Track confidence in extracted mark
    let imageProcessor = new ImageProcessor(); // Image preprocessing utility

    // Create enhance toggle if it doesn't exist
    function createEnhanceToggle() {
        const toggle = document.createElement('div');
        toggle.className = 'form-check form-switch mb-3';
        toggle.innerHTML = `
            <input class="form-check-input" type="checkbox" id="enhanceImageToggle" checked>
            <label class="form-check-label" for="enhanceImageToggle">Enhance Image (Recommended)</label>
        `;
        scannerContainer.appendChild(toggle);
        return toggle.querySelector('input');
    }

    // Create confidence indicator if it doesn't exist
    function createConfidenceIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'confidenceIndicator';
        indicator.className = 'mt-2 mb-3';
        indicator.innerHTML = `
            <label class="form-label d-flex justify-content-between">
                <span>Detection Confidence</span>
                <span id="confidenceValue">0%</span>
            </label>
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%" 
                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        `;
        if (marksForm) {
            marksForm.insertBefore(indicator, marksForm.firstChild);
        }
        return indicator;
    }

    // Image Processor Class for enhancing images before OCR
    function ImageProcessor() {
        // Enhance image for better OCR
        this.enhance = function(canvas) {
            const ctx = canvas.getContext('2d');
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;
            
            // Convert to grayscale
            for (let i = 0; i < data.length; i += 4) {
                const avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
                data[i] = avg; // R
                data[i + 1] = avg; // G
                data[i + 2] = avg; // B
            }
            
            // Increase contrast
            const contrast = 1.5; // Contrast factor (1 is normal)
            const factor = (259 * (contrast + 255)) / (255 * (259 - contrast));
            
            for (let i = 0; i < data.length; i += 4) {
                data[i] = factor * (data[i] - 128) + 128; // R
                data[i + 1] = factor * (data[i + 1] - 128) + 128; // G
                data[i + 2] = factor * (data[i + 2] - 128) + 128; // B
            }
            
            // Apply threshold to make text clearer
            this.adaptiveThreshold(data, canvas.width, canvas.height);
            
            ctx.putImageData(imageData, 0, 0);
            
            // Apply perspective correction if skewed
            this.correctPerspective(canvas);
            
            return canvas;
        };
        
        // Adaptive thresholding for better text detection
        this.adaptiveThreshold = function(data, width, height) {
            const blockSize = 11; // Size of the neighborhood
            const C = 2; // Constant subtracted from mean
            
            // Simple adaptive threshold implementation
            for (let y = 0; y < height; y++) {
                for (let x = 0; x < width; x++) {
                    const idx = (y * width + x) * 4;
                    
                    // Calculate local mean
                    let sum = 0;
                    let count = 0;
                    
                    for (let j = Math.max(0, y - blockSize/2); j < Math.min(height, y + blockSize/2); j++) {
                        for (let i = Math.max(0, x - blockSize/2); i < Math.min(width, x + blockSize/2); i++) {
                            sum += data[(j * width + i) * 4];
                            count++;
                        }
                    }
                    
                    const mean = sum / count;
                    
                    // Apply threshold
                    if (data[idx] < mean - C) {
                        data[idx] = 0; // Black
                        data[idx + 1] = 0;
                        data[idx + 2] = 0;
                    } else {
                        data[idx] = 255; // White
                        data[idx + 1] = 255;
                        data[idx + 2] = 255;
                    }
                }
            }
        };
        
        // Detect and correct perspective (simplified version)
        this.correctPerspective = function(canvas) {
            // This is a placeholder for perspective correction
            // In a real implementation, you would:
            // 1. Detect document edges
            // 2. Calculate the perspective transform
            // 3. Apply the transform to correct the image
            
            // For simplicity, we're just returning the canvas as is
            return canvas;
        };
    }

    // Initialize Tesseract worker with improved configuration
    async function initializeTesseract() {
        try {
            if (typeof Tesseract === 'undefined') {
                console.error("Tesseract.js not loaded. Make sure it's included in the footer.");
                ocrResult.textContent = "Error: Tesseract library not found.";
                return;
            }
            
            worker = await Tesseract.createWorker({
                logger: m => updateProgress(m),
                langPath: 'https://tessdata.projectnaptha.com/4.0.0',
                gzip: true,
                corePath: 'https://cdn.jsdelivr.net/npm/tesseract.js-core@4.0.4/tesseract-core.wasm.js'
            });
            
            // Load language and configure for optimal OCR of numbers
            await worker.loadLanguage('eng');
            await worker.initialize('eng');
            
            // Set Tesseract parameters for better number recognition
            await worker.setParameters({
                tessedit_char_whitelist: '0123456789/:.', // Focus on numbers and separators
                tessedit_pageseg_mode: '7', // Treat image as a single text line
                tessedit_ocr_engine_mode: '2' // Use LSTM neural network
            });
            
            console.log('Tesseract worker initialized with optimized settings.');
        } catch (error) {
            console.error('Error initializing Tesseract:', error);
            ocrResult.textContent = "Error initializing OCR engine.";
        }
    }

    function updateProgress(m) {
        console.log(m);
        if (m.status === 'recognizing text') {
            const progress = Math.round(m.progress * 100);
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
        }
    }

    // Update confidence display
    function updateConfidence(confidence) {
        markConfidence = confidence;
        const confidenceBar = document.querySelector('#confidenceIndicator .progress-bar');
        const confidenceValue = document.getElementById('confidenceValue');
        
        if (confidenceBar && confidenceValue) {
            const confidencePercent = Math.round(confidence * 100);
            confidenceBar.style.width = confidencePercent + '%';
            confidenceBar.setAttribute('aria-valuenow', confidencePercent);
            confidenceValue.textContent = confidencePercent + '%';
            
            // Set color based on confidence
            if (confidencePercent < 40) {
                confidenceBar.className = 'progress-bar bg-danger';
            } else if (confidencePercent < 70) {
                confidenceBar.className = 'progress-bar bg-warning';
            } else {
                confidenceBar.className = 'progress-bar bg-success';
            }
        }
    }

    // Start Camera Function
    startButton.addEventListener('click', async () => {
        if (stream) { // Stop existing stream if any
            stream.getTracks().forEach(track => track.stop());
        }
        try {
            // Request higher resolution if possible
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment', // Prefer rear camera
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                } 
            });
            video.srcObject = stream;
            scannerContainer.style.display = 'block';
            scanButton.disabled = false;
            startButton.textContent = 'Switch Camera'; // Or Stop Camera
            ocrResult.textContent = 'Camera started. Position paper and click Scan.';
            resetProgress();
            marksForm.style.display = 'none';
            if (!worker) {
                initializeTesseract(); // Initialize worker if not already done
            }
        } catch (err) {
            console.error("Error accessing camera: ", err);
            ocrResult.textContent = "Error accessing camera. Please ensure permissions are granted.";
            scannerContainer.style.display = 'none';
            scanButton.disabled = true;
        }
    });

    // Scan Marks Function with improved processing
    scanButton.addEventListener('click', async () => {
        if (!stream || !worker) {
            ocrResult.textContent = 'Camera or OCR not ready.';
            return;
        }
        
        ocrResult.textContent = 'Scanning...';
        scanButton.disabled = true; // Disable scan button during processing
        resetProgress();
        marksForm.style.display = 'none';
        updateConfidence(0);

        // Set canvas dimensions to video dimensions
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Draw video frame onto canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Store original image for potential reprocessing
        lastCapturedImage = context.getImageData(0, 0, canvas.width, canvas.height);
        
        // Enhance image if toggle is checked
        if (enhanceImageToggle.checked) {
            try {
                imageProcessor.enhance(canvas);
            } catch (error) {
                console.error('Error enhancing image:', error);
            }
        }

        try {
            // Recognize text from canvas with improved options
            const { data } = await worker.recognize(canvas, {
                rectangle: detectROI(canvas) // Try to detect region of interest
            });
            
            console.log('OCR Results:', data);
            ocrResult.textContent = data.text || 'No text recognized.';
            
            // Extract marks using advanced logic
            const extractionResult = extractMarksAdvanced(data.text, data.words);
            
            if (extractionResult.mark !== null) {
                confirmedText.textContent = data.text; // Show full text for context
                marksInput.value = extractionResult.mark; // Pre-fill the likely mark
                marksForm.style.display = 'block'; // Show confirmation form
                updateConfidence(extractionResult.confidence);
                loadStudentsAndSubjects(); // Load dropdowns
            } else {
                tryAlternativeProcessing();
            }

        } catch (error) {
            console.error('OCR Error:', error);
            ocrResult.textContent = 'Error during OCR processing.';
            marksForm.style.display = 'none';
        } finally {
            scanButton.disabled = false; // Re-enable scan button
        }
    });

    // Try alternative processing methods if initial scan fails
    async function tryAlternativeProcessing() {
        if (!lastCapturedImage) {
            marksForm.style.display = 'none';
            ocrResult.textContent += "\nCould not reliably extract marks. Please scan again.";
            return;
        }
        
        ocrResult.textContent += "\nTrying alternative processing...";
        
        // Restore original image
        context.putImageData(lastCapturedImage, 0, 0);
        
        // Apply different preprocessing technique
        try {
            // Apply inverted threshold (sometimes works better for certain papers)
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const pixelData = imageData.data;
            
            // Invert and threshold
            for (let i = 0; i < pixelData.length; i += 4) {
                const avg = (pixelData[i] + pixelData[i + 1] + pixelData[i + 2]) / 3;
                // Invert and threshold
                const val = avg > 128 ? 0 : 255;
                pixelData[i] = val;
                pixelData[i + 1] = val;
                pixelData[i + 2] = val;
            }
            
            context.putImageData(imageData, 0, 0);
            
            // Try OCR again with different parameters
            await worker.setParameters({
                tessedit_char_whitelist: '0123456789', // More restrictive - numbers only
                tessedit_pageseg_mode: '6' // Assume single uniform block of text
            });
            
            const { data: ocrData } = await worker.recognize(canvas);
            
            console.log('Alternative OCR Results:', ocrData);
            ocrResult.textContent += "\n\nAlternative scan results: " + ocrData.text;
            
            // Extract just numbers for simplicity
            const numbers = ocrData.text.match(/\d+/g);
            if (numbers && numbers.length > 0) {
                // Use the most likely mark based on context and validation
                const validMarks = numbers.map(num => parseInt(num))
                    .filter(num => num >= 0 && num <= 100);
                    
                if (validMarks.length > 0) {
                    const mark = findMostLikelyMark(validMarks);
                    confirmedText.textContent = ocrData.text; // Show full text for context
                    marksInput.value = mark; // Pre-fill the likely mark
                    marksForm.style.display = 'block'; // Show confirmation form
                    updateConfidence(0.4); // Lower confidence for alternative method
                    loadStudentsAndSubjects(); // Load dropdowns
                    return;
                }
            }
            
            // If we still couldn't find a valid mark
            marksForm.style.display = 'none';
            ocrResult.textContent += "\nCould not reliably extract marks. Please enter marks manually or scan again.";
            
            // Reset parameters
            await worker.setParameters({
                tessedit_char_whitelist: '0123456789/:.', 
                tessedit_pageseg_mode: '7'
            });
            
        } catch (error) {
            console.error('Alternative processing error:', error);
            marksForm.style.display = 'none';
            ocrResult.textContent += "\nAlternative processing failed. Please scan again.";
        }
    }

    // Advanced mark extraction with multiple pattern recognition strategies
    function extractMarksAdvanced(text, words) {
        // Initialize result object
        const result = {
            mark: null,
            confidence: 0
        };
        
        if (!text) return result;
        
        // 1. Look for marks with explicit labels (highest confidence)
        const labeledMarkPatterns = [
            { regex: /marks?\s*[:=]\s*(\d{1,3})/i, confidence: 0.9 },
            { regex: /score\s*[:=]\s*(\d{1,3})/i, confidence: 0.9 },
            { regex: /total\s*[:=]\s*(\d{1,3})/i, confidence: 0.9 },
            { regex: /points?\s*[:=]\s*(\d{1,3})/i, confidence: 0.85 },
            { regex: /grade\s*[:=]\s*(\d{1,3})/i, confidence: 0.8 }
        ];
        
        for (const pattern of labeledMarkPatterns) {
            const match = text.match(pattern.regex);
            if (match && match[1]) {
                const mark = parseInt(match[1]);
                if (isValidMark(mark)) {
                    result.mark = mark;
                    result.confidence = pattern.confidence;
                    return result;
                }
            }
        }
        
        // 2. Look for fraction patterns (e.g., 85/100)
        const fractionPatterns = [
            { regex: /(\d{1,3})\s*\/\s*100/i, confidence: 0.85 },
            { regex: /(\d{1,3})\s*\/\s*\d{2,3}/i, confidence: 0.7 } // e.g. 85/100, 42/50
        ];
        
        for (const pattern of fractionPatterns) {
            const match = text.match(pattern.regex);
            if (match && match[1]) {
                const mark = parseInt(match[1]);
                if (isValidMark(mark)) {
                    result.mark = mark;
                    result.confidence = pattern.confidence;
                    return result;
                }
            }
        }
        
        // 3. Look at the spatial arrangement of numbers and words
        if (words && words.length > 0) {
            // Look for numbers that appear near mark-related words
            const markKeywords = ['mark', 'score', 'total', 'grade', 'point'];
            
            for (let i = 0; i < words.length; i++) {
                const word = words[i];
                
                // Check if this word is a number
                if (/^\d{1,3}$/.test(word.text) && isValidMark(parseInt(word.text))) {
                    const mark = parseInt(word.text);
                    
                    // Look at words before and after this number
                    const prevWord = i > 0 ? words[i-1].text.toLowerCase() : '';
                    const nextWord = i < words.length-1 ? words[i+1].text.toLowerCase() : '';
                    
                    // Check if adjacent words are mark-related keywords
                    if (markKeywords.some(keyword => prevWord.includes(keyword))) {
                        result.mark = mark;
                        result.confidence = 0.8;
                        return result;
                    } else if (markKeywords.some(keyword => nextWord.includes(keyword))) {
                        result.mark = mark;
                        result.confidence = 0.7;
                        return result;
                    }
                }
            }
            
            // Fall back to looking for numbers in reasonable marks range (40-100)
            // with higher confidence for numbers in common mark ranges
            for (const word of words) {
                if (/^\d{1,3}$/.test(word.text)) {
                    const mark = parseInt(word.text);
                    if (mark >= 75 && mark <= 100) {
                        result.mark = mark;
                        result.confidence = 0.65;
                        return result;
                    } else if (mark >= 40 && mark <= 100) {
                        result.mark = mark;
                        result.confidence = 0.5;
                        return result;
                    }
                }
            }
        }
        
        // 4. Last resort: just find any numbers that could be marks
        const numbers = text.match(/\d{1,3}/g);
        if (numbers && numbers.length > 0) {
            // Filter to only include valid marks
            const validMarks = numbers.map(num => parseInt(num))
                .filter(isValidMark);
                
            if (validMarks.length > 0) {
                // Use heuristics to find the most likely mark
                result.mark = findMostLikelyMark(validMarks);
                result.confidence = 0.4; // Low confidence when we're just guessing
                return result;
            }
        }
        
        return result; // Return with mark: null if nothing found
    }

    // Find the most likely mark from a list of potential marks
    function findMostLikelyMark(marks) {
        if (!marks || marks.length === 0) return null;
        if (marks.length === 1) return marks[0];
        
        // Prioritize marks in typical ranges
        const highMarks = marks.filter(m => m >= 60 && m <= 100);
        if (highMarks.length === 1) return highMarks[0];
        
        const midMarks = marks.filter(m => m >= 30 && m <= 100);
        if (midMarks.length === 1) return midMarks[0];
        
        // If we have multiple candidates, prefer marks that are divisible by 5 or 10
        // (common marking schemes)
        const divisibleByTen = marks.filter(m => m % 10 === 0);
        if (divisibleByTen.length === 1) return divisibleByTen[0];
        
        const divisibleByFive = marks.filter(m => m % 5 === 0);
        if (divisibleByFive.length === 1) return divisibleByFive[0];
        
        // If still multiple, prefer the mark closest to average passing grade (70)
        return marks.reduce((prev, curr) => 
            Math.abs(curr - 70) < Math.abs(prev - 70) ? curr : prev
        );
    }

    // Validate if a number can be a valid mark
    function isValidMark(num) {
        return !isNaN(num) && num >= 0 && num <= 100;
    }

    // Try to detect the region of interest (where marks are typically found)
    function detectROI(canvas) {
        // This is a placeholder for more sophisticated ROI detection
        // In a real implementation, you could:
        // 1. Use edge detection to find the boundaries of the mark area
        // 2. Look for consistent patterns across multiple documents
        // 3. Allow users to define ROI templates for different exam papers
        
        // For now, we'll just return an estimated region
        // where marks often appear (slightly to the right and top of center)
        const width = canvas.width;
        const height = canvas.height;
        
        return {
            left: Math.floor(width * 0.5),
            top: Math.floor(height * 0.3),
            width: Math.floor(width * 0.4),
            height: Math.floor(height * 0.4)
        };
    }

    // Function to reset progress bar
    function resetProgress() {
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressBar.setAttribute('aria-valuenow', 0);
    }
    
    // --- Functions to load Students/Subjects ---
    async function loadStudentsAndSubjects() {
        studentSelect.innerHTML = '<option value="">Loading students...</option>';
        subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
        
        try {
            // Fetch students (for teacher's class)
            const studentResponse = await fetch('api/get_students.php');
            if (!studentResponse.ok) {
                throw new Error(`HTTP error! status: ${studentResponse.status}`);
            }
            const studentResult = await studentResponse.json();
            const students = studentResult.success ? studentResult.data : [];
            
            // Populate student dropdown
            studentSelect.innerHTML = '<option value="">Select Student...</option>';
            if (students && students.length > 0) {
                students.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    // Assuming student object has full_name and admission_number
                    option.textContent = `${student.full_name} (${student.admission_number || 'ID: ' + student.id})`; 
                    studentSelect.appendChild(option);
                });
            } else {
                 studentSelect.innerHTML = '<option value="">No students found for your class</option>';
            }

            // Fetch subjects
            const subjectResponse = await fetch('api/get_subjects.php');
            if (!subjectResponse.ok) {
                throw new Error(`HTTP error! status: ${subjectResponse.status}`);
            }
            const subjectResult = await subjectResponse.json();
            const subjects = subjectResult.success ? subjectResult.data : [];

            // Populate subject dropdown
            subjectSelect.innerHTML = '<option value="">Select Subject...</option>';
             if (subjects && subjects.length > 0) {
                subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.id;
                     // Assuming subject object has name and code
                    option.textContent = `${subject.name} (${subject.code || 'ID: ' + subject.id})`;
                    subjectSelect.appendChild(option);
                });
            } else {
                 subjectSelect.innerHTML = '<option value="">No subjects found</option>';
            }

        } catch (error) {
            console.error('Error loading dropdown data:', error);
            studentSelect.innerHTML = '<option value="">Error loading students</option>';
            subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
            showAlert('Could not load student or subject list. Please check API endpoints and database connection.', 'danger');
        }
    }

    // --- Handle Save Marks Form Submission ---
    saveMarksForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitButton = saveMarksForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        const formData = new FormData(saveMarksForm);
        const formValues = Object.fromEntries(formData.entries());
        console.log("Submitting marks:", formValues);

        try {
            const response = await fetch('api/save_scanned_marks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formValues)
            });
            
            const result = await response.json();

            if (response.ok && result.success) {
               showAlert('Marks saved successfully!', 'success');
               marksForm.style.display = 'none'; 
               ocrResult.textContent = 'Awaiting scan...'; // Reset OCR result
               resetProgress();
               // Log mark submission for accuracy analysis
               logMarkSubmission(formValues, markConfidence);
            } else {
               showAlert('Error saving marks: ' + (result.message || 'Unknown server error.'), 'danger');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            showAlert('An error occurred while saving marks. Check console for details.', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Save Marks';
        }
    });

    // Log mark submissions for future analysis (could be expanded to server-side)
    function logMarkSubmission(formValues, confidence) {
        console.log('Mark submission logged:', {
            studentId: formValues.student_id,
            subjectId: formValues.subject_id,
            mark: formValues.marks,
            confidence: confidence,
            timestamp: new Date().toISOString()
        });
        
        // This could be expanded to send analytics to the server
        // to improve OCR accuracy over time
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

    // Initial setup
    resetProgress();
    // Create UI improvements
    const scanControls = document.createElement('div');
    scanControls.className = 'mt-3 mb-3';
    scanControls.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>Scanning Tips:</span>
            <button type="button" class="btn btn-sm btn-outline-info" 
                data-bs-toggle="collapse" data-bs-target="#scanningTips">
                Show Tips
            </button>
        </div>
        <div class="collapse mb-3" id="scanningTips">
            <div class="card card-body small">
                <ul class="mb-0">
                    <li>Ensure good lighting on the paper</li>
                    <li>Hold camera steady and parallel to the page</li>
                    <li>Position the mark clearly in the frame</li>
                    <li>Keep the paper flat without wrinkles</li>
                    <li>Avoid shadows on the paper</li>
                </ul>
            </div>
        </div>
    `;
    
    if (scannerContainer) {
        scannerContainer.insertBefore(scanControls, scannerContainer.firstChild);
    }
}); 