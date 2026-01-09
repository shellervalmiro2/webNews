<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once __DIR__ . '/config/config.php';

$redirectPage = $_SESSION['name'] == ADMIN_NAME ? 'login.php' : '/account/login.html';
$auth = new Auth();
$auth->logout();

header('Location: ' . $redirectPage);
exit;
?>