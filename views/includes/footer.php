    </div> <!-- End of container -->
    <footer class="footer mt-5 py-3 bg-light">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> EduTrack360. All rights reserved.</p>
            <div class="developer-credit mt-2">
                <span>Developed by </span>
                <img src="assets/img/ag-logo.svg" alt="AG Developer Logo" class="developer-logo" style="height: 30px; vertical-align: middle; margin-left: 5px;">
                <span style="font-weight: bold; margin-left: 3px;">AG Development</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@4.1.1/dist/tesseract.min.js'></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Handle AJAX errors
        function handleAjaxError(error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again later.');
        }

        // Show loading spinner
        function showLoading(element) {
            element.innerHTML = '<div class="spinner"></div>';
        }

        // Hide loading spinner
        function hideLoading(element, content) {
            element.innerHTML = content;
        }
    </script>
    
    <?php 
    // Include page-specific scripts if defined
    if (isset($page_scripts) && is_array($page_scripts)) {
        foreach ($page_scripts as $script_path) {
            echo '<script src="' . htmlspecialchars($script_path) . '"></script>';
        }
    }
    ?>
</body>
</html> 