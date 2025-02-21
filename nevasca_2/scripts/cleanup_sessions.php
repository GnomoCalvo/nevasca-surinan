<?php
// Define o caminho base do projeto
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/UserSession.php';
require_once BASE_PATH . '/models/RateLimiter.php';

function cleanupSessions() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Limpar sessões expiradas
        $userSession = new UserSession($conn);
        $userSession->cleanExpiredSessions();
        
        // Limpar tentativas de login antigas
        $rateLimiter = new RateLimiter($conn);
        $rateLimiter->cleanOldAttempts();
        
        error_log("Limpeza de sessões e tentativas de login concluída com sucesso");
    } catch (Exception $e) {
        error_log("Erro na limpeza de sessões: " . $e->getMessage());
    }
}

cleanupSessions();