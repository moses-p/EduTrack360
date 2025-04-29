<?php
$page_title = "Error";

$error_message = "The page you requested could not be found or you do not have permission to view it.";

// You could potentially pass specific error messages via session or GET parameters
if (isset($_GET['message'])) {
    $error_message = htmlspecialchars($_GET['message']);
}

$content = <<<HTML
<div class="alert alert-danger" role="alert">
    <h4 class="alert-heading">Error</h4>
    <p>{$error_message}</p>
    <hr>
    <p class="mb-0">Please check the URL or go back to the <a href="index.php">Dashboard</a>.</p>
</div>
HTML;

// This file sets $content, so index.php will display it.
?> 