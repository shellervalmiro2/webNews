<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../log/error.log');

define('ADMIN_PATH', __DIR__ . '/../admin');

spl_autoload_register(function ($class){
    if(file_exists(ADMIN_PATH . '/models/' . $class . '.php')){
        require_once ADMIN_PATH . '/models/' . $class . '.php';
    } elseif(file_exists(ADMIN_PATH . '/controllers/' . $class . '.php' )){
        require_once ADMIN_PATH . '/controllers/' . $class . '.php';
    }
});

// Функция для очистки UTF-8
function cleanUtf8($data) {
    if (is_array($data)) {
        return array_map('cleanUtf8', $data);
    } elseif (is_string($data)) {
        return iconv('UTF-8', 'UTF-8//IGNORE', $data);
    }
    return $data;
}

$resource_name = $_GET["r"] ?? "all";
$query = $_GET['q'] ?? null;
$resourcesToFetch = [];
$finalReponse = [];

try {
    $resourcesToFetch = [
        ["name" => "musicContent", "result" => (new MusicModel())->getAllMusic()],
        ["name" => "services", "result" => (new ServiceModel())->getAllServices()],
        ["name" => "news", "result" => (new NewsModel())->getAllNews()],
        ["name" => "events", "result" => (new EventModel())->getAllEvents()],
        ["name" => "videos", "result" => (new VideoModel())->getAllVideos()],
        ["name" => "stream", "result" => (new LiveStreamModel())->getAllStreams()],
        ["name" => "partners", "result" => (new PartnersModel())->getAllPartners()],
        ["name" => "interviews", "result" => (new InterviewModel())->getAllInterviews()],
        ["name" => "documents", "result" => (new DocumentModel())->getAllDocuments()]

    ];
} catch(PDOException $e) {
    error_log($e->getMessage());
    //logMessage($e->getMessage());
    $finalReponse = ['error' => 'Database error'];
}

if($query){
    $controller = new AdminController();
    switch($query){
        case 'userlogout':
            $finalReponse = $controller->userLogout();
            break;
        case 'userlogin':
            $finalReponse = $controller->userLogin();
            break;
        case 'userRegister':
            $finalReponse = $controller->userRegistration();
            break;
        case 'mediaActivity':
            $finalReponse = $controller->mediaActivity();
            break;
        case 'uid':
            $finalReponse = $controller->getCurrentUser();
            break;
        case 'googleAuthUrl':
            $finalReponse = $controller->getGoogleAuthUrl();
            break;
        case 'savetokenFB':
	    	$finalResponse = $controller->saveFirebaseToken();
            break;
        case 'streamAccess':
            $finalReponse = $controller->grantStreamAccess();
            break;
        case 'viewerReg':
            $finalReponse = $controller->registerViewer();
            break;
        case 'createOrder':
            $controller = new OrderController();
            $finalReponse = $controller->createOrder();
            break;
        case 'updatePaymentStatus':
            $controller = new OrderController();
            $finalReponse = $controller->updatePaymentStatus();
            break;
        case 'orders':
            $controller = new OrderController();
            $finalReponse = $controller->getAllOrders();
            break;
        case 'createServiceOrder':
            $controller = new ServiceOrderController();
            $controller->createOrder();
            break;
        case 'siteSettings':
            $controller = new SiteSettingController();
            if(isset($_GET['key'])){
                $key = $_GET['key'];
            	$finalReponse = $controller->getSettingValue($key);
            } else if(isset($_GET['group'])){
                $group = $_GET['group'];
                $finalReponse = $controller->getSettingsByGroup($group);
            }
            break;
        case 'updateProfile':
            $finalReponse = $controller->updateUserProfile();
            break;

        case 'getUserProfile':
            $controller = new UserController();
            if(!isset($_GET["sid"]) || empty($_GET['sid'])){
                $finalReponse = ["response" => "fail",
                                  "name" => "guest",
                                  "email" => "",
                                  "avatar_url" => $_GET];
                break;
            }
            //session_id($_GET['sid']);
            //session_start();
            $finalReponse = $controller->getUserProfile();
            break;
        case 'updateUserAvatar':
            $controller = new UserController();
            $finalReponse = $controller->upadateAvatar();
            break;
        case 'saveDocument':
            $controller = new DocumentController();
            $finalReponse = $controller->saveDocument();
            break;
        case 'getDocument':
            $controller = new DocumentController();
            $finalReponse = $controller->getDocument();
            break;
        case 'deleteDocument':
            $controller = new DocumentController();
            $finalReponse = $controller->deleteDocument();
            break;
        case 'uploadDocumentImage':
            $controller = new DocumentController();
            $finalReponse = $controller->uploadImage();
            break;
        case 'deleteimage':
            if(!isset($_GET['id']) || !isset($_POST['url'])){
                $finalReponse = ['success' => false, 'message' => 'missing id or image path'];
            } else {
                if(file_exists(__DIR__ . '/..' . $_POST['url'])){
                    $model = new ImageModel();
                    if(unlink(__DIR__ . '/..' . $_POST['url']) && $model->deleteImage($id)){
                        $finalReponse = ['success' => true, 'message' => 'image deleted successfully'];
                    } else {
                        $finalReponse = ['success' => false, 'message' => 'failed to delete image'];
                    }
                } else {
                    $finalReponse = ['success' => false, 'message' => 'file not found'];
                }
            }
            break;
        default:
            break;
    }
}

if (empty($finalReponse)) {
    switch($resource_name) {
        case "all":
            foreach($resourcesToFetch as $resource) {
                $finalReponse[$resource["name"]] = $resource["result"];
            }
            break;
        default:
            foreach($resourcesToFetch as $resource) {
                if($resource["name"] == $resource_name) {
                    $data = $resource["result"];
                    $result = null;
                    /*if($_GET['id']){
                        $result = array_filter($data, function($itm){
                            return $itm['id'] == (int)$_GET['id'];
                        });
                    }
                    if($result){
                        $finalReponse = [
                            reset($result);
                        ];
                    } else {
                        $finalReponse = $data;
                    }*/
                    $finalReponse = $data;
                    break;
                }
            }
            break;
    }
}

header("Content-Type: application/json; charset=utf-8");
$cleanedData = cleanUtf8($finalReponse);
echo json_encode($cleanedData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
?>
