<?php
error_reporting(E_ALL); 
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../log/admin_errors.log');
define('ADMIN_PATH', __DIR__);
require_once ADMIN_PATH . '/config/database.php';
require_once ADMIN_PATH . '/config/auth.php';
require_once ADMIN_PATH . '/config/logMessage.php';
require_once ADMIN_PATH . '/config/config.php';

// Initialize authentication
$auth = new Auth();

// Check if user is logged in for all pages except login
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'login.php') {
    $auth->requireLogin();
    $auth->checkSessionTimeout(); // Optional: Check session timeout
}

// Автозагрузка контроллеров
spl_autoload_register(function ($class) {
    if (file_exists(ADMIN_PATH . '/controllers/' . $class . '.php')) {
        require_once ADMIN_PATH . '/controllers/' . $class . '.php';
    } elseif (file_exists(ADMIN_PATH . '/models/' . $class . '.php')) {
        require_once ADMIN_PATH . '/models/' . $class . '.php';
    } elseif (file_exists(ADMIN_PATH . '/utils/' . $class . '.php')) {
        require_once ADMIN_PATH . '/utils/' . $class . '.php';
    }
});

// Маршрутизация
$action = $_GET['action'] ?? 'dashboard';
$method = $_GET['method'] ?? 'index';
$id = $_GET['id'] ?? null;

try{
    switch ($action) {
        case 'dashboard':
            $controller = new AdminController();
            $controller->dashboard();
            break;
            
        case 'news':
            $controller = new NewsController();
            if ($id && $method == 'edit') {
                $controller->edit($id);
            } elseif ($method == 'create') {
                $controller->create();
            } elseif ($id && $method == 'delete') {
                $controller->delete($id);
            } else {
                $controller->index();
            }
            break;
            
        case 'music':
            $controller = new MusicController();
            if ($id && $method == 'edit') {
                $controller->edit($id);
            } elseif ($method == 'create') {
                $controller->create();
            } elseif ($id && $method == 'delete') {
                $controller->delete($id);
            } else {
                $controller->index();
            }
            break;
            
        case 'videos':
            $controller = new VideoController();
            if ($id && $method == 'edit') {
                $controller->edit($id);
            } elseif ($method == 'create') {
                $controller->create();
            } elseif ($id && $method == 'delete') {
                $controller->delete($id);
            } else {
                $controller->index();
            }
            break;
            
        case 'services':
            $controller = new ServiceController();
            if ($id && $method == 'edit') {
                $controller->edit($id);
            } elseif ($method == 'create') {
                $controller->create();
            } elseif ($id && $method == 'delete') {
                $controller->delete($id);
            } else {
                $controller->index();
            }
            break;
            
        case 'events':
            $controller = new EventController();
            if ($id && $method == 'edit') {
                $controller->edit($id);
            } elseif ($method == 'create') {
                $controller->create();
            } elseif ($id && $method == 'delete') {
                $controller->delete($id);
            } else {
                $controller->index();
            }
            break;
            
        case 'livestream':
            $controller = new LiveStreamController();
            if ($id && $method == 'edit') {
                $controller->edit($id);
            } elseif ($method == 'create') {
                $controller->create();
            } elseif ($id && $method == 'delete') {
                $controller->delete($id);
            } else {
                $controller->index();
            }
            break;
        case 'partners':
            $controller = new PartnersController();
            if ($id && $method == 'edit') {
                $controller->edit($id);
            } elseif ($method == 'create') {
                $controller->create();
            } elseif ($id && $method == 'delete') {
                $controller->delete($id);
            } else {
                $controller->index();
            }
            break;
        case 'userlogin':
            header("Content-Type: application/json; charset=utf-8");
            $controller = new AdminController();
            $controller->userLogin();
            break;
        case 'userRegister':
            header('Content-Type: application/json; charset=utf-8');
            $controller = new AdminController();
            $controller->userRegistration();
            break;
        case 'createOrder':
            $controller = new OrderController();
            $controller->createOrder();
            break;
            
       case 'interview':
            $controller = new InterviewController();
            if ($id && $method == 'edit') {
                $controller->edit($id);
            } elseif ($method == 'create') {
                $controller->create();
            } elseif ($id && $method == 'delete') {
                $controller->delete($id);
            } else {
                $controller->index();
            }
            break;

        case 'orders':
            $controller = new OrderController();
            if ($method == 'index') {
                $controller->index();
            }
            break;
        case 'service_orders':
            $controller = new ServiceOrderController();
            $controller->index();
            break;
        case 'settings':
            $controller = new SiteSettingController();
            if($method == 'index'){
                $controller->index();
            } else if ($method == 'edit' && $id){
                $controller->edit($id);
            } else if($method == 'create'){
                $controller->create();
            } else if($method == 'delete' && $id){
                $controller->delete($id);
            }
            break;
        default:
            // Если действие не распознано, показываем дашборд
            $controller = new AdminController();
            $controller->dashboard();
            break;
    }
} catch (Exception $e){
    die($e->getMessage());
}

?>