<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'LoginController.php';
require_once '../config/database.php';
require_once '../models/UserSession.php';

$controller = new LoginController();
$controller->logout();

// Garantir que todos os cookies sejam removidos
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time()-3600, '/');
    }
}

// Destruir a sessão completamente
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
session_destroy();

// Redirecionar para a página de login
header("Location: ../views/login.php");
exit();
?>