<?php
$page_title = "OCR Accuracy Analysis";

// Security Check: Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /login.php?error=unauthorized");
    exit();
}

// Add script dependencies
$scripts = [
    'assets/js/common.js',
    'assets/js/ocr-analysis.js'
];

$content = <<<'HTML'
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">OCR Accuracy Analysis</h4>
        <div>
            <button type="button" class="btn btn-primary" id="refreshData">
                <i class="bi bi-arrow-clockwise"></i> Refresh Data
            </button>
            <button type="button" class="btn btn-success" id="exportData">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-primary" id="totalScans">0</h3>
                    <p class="text-muted">Total Scans</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-success" id="accuracyRate">0%</h3>
                    <p class="text-muted">Accuracy Rate</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-info" id="avgConfidence">0%</h3>
                    <p class="text-muted">Avg. Confidence</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-warning" id="needsReview">0</h3>
                    <p class="text-muted">Needs Review</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="dateRange" class="form-label">Date Range</label>
                        <select class="form-select" id="dateRange">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                            <option value="all">All Time</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="teacherFilter" class="form-label">Teacher</label>
                        <select class="form-select" id="teacherFilter">
                            <option value="all" selected>All Teachers</option>
                            <!-- Will be populated by JS -->
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="confidenceFilter" class="form-label">Confidence</label>
                        <select class="form-select" id="confidenceFilter">
                            <option value="all" selected>All Levels</option>
                            <option value="high">High (>70%)</option>
                            <option value="medium">Medium (40-70%)</option>
                            <option value="low">Low (<40%)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="feedbackFilter" class="form-label">Feedback</label>
                        <select class="form-select" id="feedbackFilter">
                            <option value="all" selected>All</option>
                            <option value="correct">Correct</option>
                            <option value="incorrect">Incorrect</option>
                            <option value="adjusted">Adjusted</option>
                            <option value="pending">No Feedback</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary" id="applyFilters">
                    <i class="bi bi-filter"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- OCR Attempts Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">OCR Analysis</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teacher</th>
                            <th>Date</th>
                            <th>Extracted Text</th>
                            <th>Detected Mark</th>
                            <th>Confidence</th>
                            <th>Final Mark</th>
                            <th>Feedback</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ocrAttemptsList">
                        <tr><td colspan="9">Loading data...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="OCR attempts pagination" class="mt-3">
                <ul class="pagination justify-content-center" id="ocrPagination">
                    <!-- Pagination will be populated by JS -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- OCR Attempt Detail Modal -->
<div class="modal fade" id="ocrDetailModal" tabindex="-1" aria-labelledby="ocrDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ocrDetailModalLabel">OCR Attempt Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Original Image</label>
                            <div class="border p-2 text-center">
                                <img id="originalImage" class="img-fluid" src="" alt="Original scanned image">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Processed Image</label>
                            <div class="border p-2 text-center">
                                <img id="processedImage" class="img-fluid" src="" alt="Processed image">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="extractedText" class="form-label">Extracted Text</label>
                            <textarea id="extractedText" class="form-control" rows="4" readonly></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Detected Marks</label>
                            <div id="detectedMarks" class="border p-2" style="max-height: 100px; overflow-y: auto;">
                                <!-- Will be populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="selectedMark" class="form-label">Selected Mark</label>
                    <input type="number" class="form-control" id="selectedMark" min="0" max="100">
                </div>
                
                <div class="mb-3">
                    <label for="feedbackType" class="form-label">Feedback</label>
                    <select class="form-select" id="feedbackType">
                        <option value="correct">Correct</option>
                        <option value="incorrect">Incorrect</option>
                        <option value="adjusted">Manually Adjusted</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="adminNotes" class="form-label">Notes</label>
                    <textarea id="adminNotes" class="form-control" rows="2" placeholder="Optional notes for improving OCR"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveOcrFeedback">Save Feedback</button>
            </div>
        </div>
    </div>
</div>

<!-- Chart Modal -->
<div class="modal fade" id="ocrChartModal" tabindex="-1" aria-labelledby="ocrChartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ocrChartModalLabel">OCR Accuracy Trends</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="chartTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="accuracy-tab" data-bs-toggle="tab" data-bs-target="#accuracy" type="button" role="tab">Accuracy</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="confidence-tab" data-bs-toggle="tab" data-bs-target="#confidence" type="button" role="tab">Confidence</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="usage-tab" data-bs-toggle="tab" data-bs-target="#usage" type="button" role="tab">Usage</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="chartTabContent">
                    <div class="tab-pane fade show active" id="accuracy" role="tabpanel">
                        <canvas id="accuracyChart" height="300"></canvas>
                    </div>
                    <div class="tab-pane fade" id="confidence" role="tabpanel">
                        <canvas id="confidenceChart" height="300"></canvas>
                    </div>
                    <div class="tab-pane fade" id="usage" role="tabpanel">
                        <canvas id="usageChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
HTML;

// Add script handling from the scripts array
$page_scripts = $scripts;
?> 