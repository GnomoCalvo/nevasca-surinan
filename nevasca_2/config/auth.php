<?php
// Configurações de sessão PHP (mantidas para compatibilidade)
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Configurações de cookie da sessão
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

// Configurar o domínio do cookie da sessão
$domain = $_SERVER['HTTP_HOST'];
if (strpos($domain, 'preview.services') !== false) {
    session_set_cookie_params([
        'lifetime' => 28800,
        'path' => '/',
        'domain' => $domain,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Iniciar sessão PHP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log para debug
error_log("Verificando autenticação");
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));
error_log("Auth Token Cookie: " . (isset($_COOKIE['auth_token']) ? 'presente' : 'ausente'));

require_once 'database.php';
require_once '../models/UserSession.php';
require_once '../controllers/LoginController.php';

$controller = new LoginController();

// Verificar autenticação
if (!$controller->isLoggedIn()) {
    error_log("Usuário não autenticado - redirecionando para login");
    
    // Limpar qualquer sessão ou cookie residual
    session_unset();
    session_destroy();
    if (isset($_COOKIE['auth_token'])) {
        setcookie('auth_token', '', time() - 3600, '/');
    }
    
    header("Location: login.php");
    exit();
} else {
    error_log("Usuário autenticado - ID: " . ($_SESSION['user_id'] ?? 'não definido'));
}
?>