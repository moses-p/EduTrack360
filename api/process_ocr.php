<?php
/**
 * Advanced OCR Processing API
 * 
 * This file provides server-side processing of scanned exam papers
 * with advanced image preprocessing and machine learning capabilities.
 */

// Basic configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/functions.php';

// Security Check: Only teachers and admins can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit();
}

// Check for proper request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    http_response_code(405);
    exit();
}

// Function to handle image preprocessing
function preprocessImage($imagePath) {
    // Requires PHP GD or Imagick extension
    if (!extension_loaded('gd') && !extension_loaded('imagick')) {
        error_log("GD or Imagick extension required for image preprocessing");
        return false;
    }
    
    try {
        // Use Imagick if available (better image processing)
        if (extension_loaded('imagick')) {
            $image = new Imagick($imagePath);
            
            // Enhance image for OCR
            $image->normalizeImage();
            $image->enhanceImage();
            $image->contrastImage(1.5);
            
            // Convert to grayscale
            $image->transformImageColorspace(Imagick::COLORSPACE_GRAY);
            
            // Apply adaptive threshold for better text detection
            $image->adaptiveThresholdImage(15, 15, 0);
            
            // Save processed image
            $processedPath = $imagePath . '_processed.png';
            $image->writeImage($processedPath);
            $image->clear();
            
            return $processedPath;
        } 
        // Fallback to GD
        else if (extension_loaded('gd')) {
            $imageInfo = getimagesize($imagePath);
            $mimeType = $imageInfo['mime'];
            
            // Create GD image based on file type
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($imagePath);
                    break;
                default:
                    return false;
            }
            
            // Create grayscale image
            imagefilter($image, IMG_FILTER_GRAYSCALE);
            
            // Increase contrast
            imagefilter($image, IMG_FILTER_CONTRAST, 30);
            
            // Apply brightness adjustment if needed
            imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);
            
            // Save processed image
            $processedPath = $imagePath . '_processed.png';
            imagepng($image, $processedPath);
            imagedestroy($image);
            
            return $processedPath;
        }
    } catch (Exception $e) {
        error_log("Image preprocessing error: " . $e->getMessage());
        return false;
    }
    
    return false;
}

// Function to perform OCR using Tesseract (requires installation)
function performOCR($imagePath) {
    // Check if Tesseract is installed
    exec('which tesseract', $output, $returnCode);
    if ($returnCode !== 0) {
        error_log("Tesseract not installed on server");
        return ['success' => false, 'message' => 'OCR engine not available on server'];
    }
    
    try {
        // Create temp file for OCR output
        $outputFile = tempnam(sys_get_temp_dir(), 'ocr');
        
        // Run Tesseract with optimized settings for exam marks
        $command = "tesseract $imagePath $outputFile -l eng --oem 1 --psm 6 -c tessedit_char_whitelist='0123456789/:.ABCDEFGHIJKLMNOPQRSTUVWXYZ'";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            error_log("Tesseract OCR failed with code: $returnCode");
            return ['success' => false, 'message' => 'OCR processing failed'];
        }
        
        // Read OCR result
        $ocrText = file_get_contents($outputFile . '.txt');
        
        // Clean up temp file
        @unlink($outputFile);
        @unlink($outputFile . '.txt');
        
        return [
            'success' => true,
            'text' => $ocrText,
            'possible_marks' => extractPossibleMarks($ocrText)
        ];
    } catch (Exception $e) {
        error_log("OCR error: " . $e->getMessage());
        return ['success' => false, 'message' => 'OCR processing error: ' . $e->getMessage()];
    }
}

// Function to extract possible marks from OCR text
function extractPossibleMarks($text) {
    $results = [];
    
    // Pattern matching for marks
    $patterns = [
        // Explicit mark labels
        '/marks?\s*[:=]\s*(\d{1,3})/i' => 0.9,
        '/score\s*[:=]\s*(\d{1,3})/i' => 0.9,
        '/total\s*[:=]\s*(\d{1,3})/i' => 0.9,
        // Fractions
        '/(\d{1,3})\s*\/\s*100/i' => 0.85,
        '/(\d{1,3})\s*\/\s*\d{2,3}/i' => 0.7,
        // Simple numbers that could be marks
        '/\b([1-9]\d|100)\b/' => 0.5,
        '/\b(\d{1,2})\b/' => 0.3
    ];
    
    foreach ($patterns as $pattern => $confidence) {
        if (preg_match_all($pattern, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $mark = (int)$match;
                if ($mark >= 0 && $mark <= 100) {
                    $results[] = [
                        'mark' => $mark,
                        'confidence' => $confidence,
                        'pattern' => $pattern
                    ];
                }
            }
        }
    }
    
    // Sort by confidence (highest first)
    usort($results, function($a, $b) {
        return $b['confidence'] <=> $a['confidence'];
    });
    
    return $results;
}

// Function to log OCR attempts for analysis and improvement
function logOCRAttempt($userId, $imagePath, $ocrResult, $finalMark = null) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO ocr_attempts (
                user_id, image_path, ocr_text, possible_marks, selected_mark, 
                confidence, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $possibleMarks = isset($ocrResult['possible_marks']) ? json_encode($ocrResult['possible_marks']) : null;
        $ocrText = isset($ocrResult['text']) ? $ocrResult['text'] : null;
        $confidence = $finalMark && isset($ocrResult['possible_marks'][0]['confidence']) ? 
                    $ocrResult['possible_marks'][0]['confidence'] : 0;
        
        $stmt->execute([
            $_SESSION['user_id'],
            $imagePath,
            $ocrText,
            $possibleMarks,
            $finalMark,
            $confidence
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging OCR attempt: " . $e->getMessage());
        return false;
    }
}

// Main processing logic
try {
    // Validate required fields
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
        http_response_code(400);
        exit();
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($_FILES['image']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG and PNG are allowed']);
        http_response_code(400);
        exit();
    }
    
    // Create upload directory if needed
    $uploadDir = '../uploads/scanned_papers/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('ocr_') . '.' . $fileExtension;
    $filePath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        http_response_code(500);
        exit();
    }
    
    // Preprocess image for better OCR
    $processedImage = preprocessImage($filePath);
    $imageToProcess = $processedImage ?: $filePath;
    
    // Perform OCR
    $ocrResult = performOCR($imageToProcess);
    
    // Log OCR attempt for training/analysis
    $bestMark = isset($ocrResult['possible_marks'][0]['mark']) ? 
                $ocrResult['possible_marks'][0]['mark'] : null;
    logOCRAttempt($_SESSION['user_id'], $filePath, $ocrResult, $bestMark);
    
    // Return result
    echo json_encode([
        'success' => true,
        'ocr_result' => $ocrResult,
        'image_path' => $filePath
    ]);
    
} catch (Exception $e) {
    error_log("Process OCR error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Processing error: ' . $e->getMessage()]);
    http_response_code(500);
    exit();
}
?> 