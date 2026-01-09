<?php
// admin/api.php
session_start();

// Определяем корневую директорию
define('ADMIN_PATH', __DIR__);

// Подключаем конфигурацию и модели
require_once ADMIN_PATH . '/config/database.php';
require_once ADMIN_PATH . '/config/auth.php';
require_once ADMIN_PATH . '/utils/FileUpload.php';

// Автозагрузка моделей
spl_autoload_register(function ($class) {
    if (file_exists(ADMIN_PATH . '/models/' . $class . '.php')) {
        require_once ADMIN_PATH . '/models/' . $class . '.php';
    }
});

// Инициализируем авторизацию
$auth = new Auth();

// Проверяем авторизацию для всех запросов кроме public
if (!isset($_GET['public'])) {
    if (!$auth->isLoggedIn()) {
        sendJsonResponse(false, 'Not authorized', null, 403);
    }
}

// Устанавливаем заголовки
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Обрабатываем OPTIONS запрос (для CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Получаем действие
$action = $_GET['action'] ?? '';

// Инициализируем модели
$imageModel = new ImageModel();

// Обработка различных действий
try {
    switch ($action) {
        case 'upload_image':
            handleImageUpload($imageModel);
            break;
        
        case 'get_images':
            handleGetImages($imageModel);
            break;
        
        case 'save_content':
            handleSaveContent();
            break;
        
        case 'delete_image':
            handleDeleteImage($imageModel);
            break;
        
        case 'get_image':
            handleGetImage($imageModel);
            break;
        
        case 'test':
            // Для тестирования API
            sendJsonResponse(true, 'API is working', [
                'timestamp' => date('Y-m-d H:i:s'),
                'user' => $_SESSION['name'] ?? 'guest',
                'actions' => ['upload_image', 'get_images', 'get_image', 'save_content', 'delete_image', 'test']
            ]);
            break;
        
        default:
            sendJsonResponse(false, 'Invalid action', [
                'available_actions' => ['upload_image', 'get_images', 'get_image', 'save_content', 'delete_image', 'test']
            ]);
            break;
    }
} catch (Exception $e) {
    sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
}

/**
 * Обработка загрузки изображения
 */
function handleImageUpload($imageModel) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Method not allowed', null, 405);
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, 'No file uploaded or upload error');
    }
    
    try {
        // Создаем папку для загрузок если не существует
        $uploadDir = ADMIN_PATH . '/uploads/images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Используем ваш FileUploader
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $uploader = new FileUploader($uploadDir, $allowedTypes, 10 * 1024 * 1024); // 10MB максимум
        
        $fileInfo = $uploader->upload($_FILES['image']);
        
        // Используем ImageModel для сохранения в базу данных
        $imageId = $imageModel->createImage($fileInfo['filename'], $fileInfo['mime_type']);
        
        sendJsonResponse(true, 'Image uploaded successfully', [
            'id' => $imageId,
            'url' => $fileInfo['filepath'],
            'filename' => $fileInfo['filename'],
            'location' => $fileInfo['filepath'],
            'mime_type' => $fileInfo['mime_type'],
            'size' => $fileInfo['size']
        ]);
        
    } catch (Exception $e) {
        error_log('Image upload error: ' . $e->getMessage());
        sendJsonResponse(false, 'Upload error: ' . $e->getMessage());
    }
}

/**
 * Получение списка загруженных изображений (обновленная версия)
 */
function handleGetImages($imageModel) {
    try {
        // Получаем параметры
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $search = $_GET['search'] ?? '';
        
        // Получаем изображения
        if (!empty($search)) {
            $images = $imageModel->searchImages($search, $page, $limit);
            $total = count($imageModel->searchImages($search, 1, 1000)); // Простая оценка
        } else {
            $images = $imageModel->getAllImages($page, $limit);
            $total = $imageModel->getTotalImages();
        }
        
        // Формируем полные URL для изображений
        foreach ($images as &$image) {
            $image['url'] = $image['location'];
            $image['thumbnail_url'] = $image['location'];
            $image['upload_date'] = date('Y-m-d H:i:s', strtotime($image['created_at'] ?? 'now'));
        }
        
        sendJsonResponse(true, 'Images retrieved successfully', [
            'images' => $images,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'has_more' => ($page * $limit) < $total
            ],
            'search' => $search
        ]);
        
    } catch (Exception $e) {
        error_log('Get images error: ' . $e->getMessage());
        sendJsonResponse(false, 'Failed to get images: ' . $e->getMessage());
    }
}
/**
 * Получение информации об одном изображении
 */
function handleGetImage($imageModel) {
    $imageId = $_GET['id'] ?? 0;
    
    if (!$imageId) {
        sendJsonResponse(false, 'Image ID is required');
    }
    
    try {
        $image = $imageModel->getImageById($imageId);
        
        if (!$image) {
            sendJsonResponse(false, 'Image not found', null, 404);
        }
        
        // Добавляем URL к изображению
        $image['url'] = $image['location'];
        $image['thumbnail_url'] = $image['location'];
        
        sendJsonResponse(true, 'Image retrieved successfully', $image);
        
    } catch (Exception $e) {
        error_log('Get image error: ' . $e->getMessage());
        sendJsonResponse(false, 'Failed to get image: ' . $e->getMessage());
    }
}

/**
 * Сохранение контента (например, для автосохранения)
 */
function handleSaveContent() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Method not allowed', null, 405);
    }
    
    // Получаем данные из POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $content = $input['content'] ?? '';
    $documentId = $input['document_id'] ?? 0;
    $title = $input['title'] ?? '';
    $type = $input['type'] ?? 'news'; // news, article, page и т.д.
    
    if (empty($content)) {
        sendJsonResponse(false, 'Content is required');
    }
    
    try {
        // Генерируем уникальный ID для автосохранения
        $autosaveId = uniqid('autosave_');
        
        // Сохраняем во временный файл
        $tempDir = ADMIN_PATH . '/temp/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $tempFile = $tempDir . $autosaveId . '.json';
        $data = [
            'content' => $content,
            'title' => $title,
            'document_id' => $documentId,
            'type' => $type,
            'saved_at' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? 0,
            'user_name' => $_SESSION['name'] ?? 'unknown'
        ];
        
        file_put_contents($tempFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        // Удаляем старые временные файлы (старше 24 часов)
        cleanupOldTempFiles($tempDir);
        
        sendJsonResponse(true, 'Content saved successfully', [
            'autosave_id' => $autosaveId,
            'saved_at' => $data['saved_at']
        ]);
        
    } catch (Exception $e) {
        error_log('Save content error: ' . $e->getMessage());
        sendJsonResponse(false, 'Failed to save content: ' . $e->getMessage());
    }
}

/**
 * Удаление изображения
 */
function handleDeleteImage($imageModel) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Method not allowed', null, 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $imageId = $input['image_id'] ?? 0;
    $filename = $input['filename'] ?? '';
    
    if (!$imageId && !$filename) {
        sendJsonResponse(false, 'Image ID or filename is required');
    }
    
    try {
        // Получаем информацию об изображении
        if ($imageId) {
            $image = $imageModel->getImageById($imageId);
            
            if (!$image) {
                sendJsonResponse(false, 'Image not found', null, 404);
            }
            
            $filename = $image['location'];
        } else {
            // Если передан только filename, ищем ID
            global $pdo;
            $stmt = $pdo->prepare("SELECT id FROM Images WHERE location = ?");
            $stmt->execute([$filename]);
            $image = $stmt->fetch();
            
            if ($image) {
                $imageId = $image['id'];
            }
        }
        
        // Удаляем файл с диска
        $filePath = ADMIN_PATH . '/uploads/images/' . $filename;
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                sendJsonResponse(false, 'Failed to delete file from disk');
            }
        }
        
        // Удаляем запись из базы данных
        if ($imageId) {
            $result = $imageModel->deleteImage($imageId);
            
            if (!$result) {
                sendJsonResponse(false, 'Failed to delete image from database');
            }
        }
        
        sendJsonResponse(true, 'Image deleted successfully', [
            'deleted_file' => $filename,
            'deleted_id' => $imageId
        ]);
        
    } catch (Exception $e) {
        error_log('Delete image error: ' . $e->getMessage());
        sendJsonResponse(false, 'Failed to delete image: ' . $e->getMessage());
    }
}

/**
 * Очистка старых временных файлов
 */
function cleanupOldTempFiles($tempDir) {
    $files = glob($tempDir . '*.json');
    $now = time();
    $maxAge = 24 * 60 * 60; // 24 часа
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileTime = filemtime($file);
            if ($now - $fileTime > $maxAge) {
                unlink($file);
            }
        }
    }
}

/**
 * Вспомогательная функция для отправки JSON ответов
 */
function sendJsonResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Обработка ошибок
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("API Error [$errno]: $errstr in $errfile on line $errline");
    
    if (!headers_sent()) {
        sendJsonResponse(false, 'Internal server error', ['error' => $errstr], 500);
    }
    
    return true;
}

set_error_handler('handleError');

// Обработка исключений
function handleException($exception) {
    error_log("API Exception: " . $exception->getMessage());
    
    if (!headers_sent()) {
        sendJsonResponse(false, 'Internal server error', ['error' => $exception->getMessage()], 500);
    }
}

set_exception_handler('handleException');
?>