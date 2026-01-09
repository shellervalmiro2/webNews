<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/utils/FileUpload.php';
require_once __DIR__ . '/config/logMessage.php';
require_once __DIR__ . '/models/ImageModel.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    logMessage($_FILES['image']['name'], 'info');
    try {
        $imagemodel = new ImageModel();
        $uploadDir = __DIR__ . '/uploads/images/';
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        $uploader = new FileUploader($uploadDir, $allowedTypes, 5 * 1024 * 1024);
        
        $fileInfo = $uploader->upload($_FILES['image']);
        
        logMessage("upload successful: " . $fileInfo['filepath'], "info");
        $imageId = $imagemodel->createImage($fileInfo['filepath'], $fileInfo['mime_type']);
                
        echo json_encode([
            'success' => true,
            'url' => $fileInfo['filepath'],
            'filename' => $fileInfo['filename'],
            'id' => $imageId
        ]);
        
    } catch (Exception $e) {
        logMessage($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
logMessage("post or file not receives");

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>