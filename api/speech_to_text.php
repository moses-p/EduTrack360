<?php
require_once '../config/database.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Check if audio file was uploaded
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No audio file uploaded');
    }
    
    // Initialize Google Cloud Speech-to-Text client
    $speech = new GoogleCloudSpeech([
        'api_key' => GOOGLE_CLOUD_API_KEY
    ]);
    
    // Get student's preferred language
    $stmt = $pdo->prepare("
        SELECT preferred_language
        FROM students
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Configure recognition
    $config = [
        'encoding' => 'LINEAR16',
        'sampleRateHertz' => 16000,
        'languageCode' => $student['preferred_language'] ?? 'en-US',
        'enableAutomaticPunctuation' => true,
        'model' => 'default'
    ];
    
    // Perform speech recognition
    $audio = file_get_contents($_FILES['audio']['tmp_name']);
    $response = $speech->recognize($audio, $config);
    
    // Get the transcribed text
    $text = '';
    foreach ($response->results as $result) {
        $text .= $result->alternatives[0]->transcript;
    }
    
    // Return the transcribed text
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'text' => $text
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 