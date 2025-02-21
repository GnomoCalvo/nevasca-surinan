<?php
require_once '../config/database.php';
require_once '../models/Usuario.php';
require_once '../models/UserSession.php';
require_once '../models/RateLimiter.php';

class LoginController
{
    private $database;
    private $usuario;
    private $userSession;
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; // 15 minutos
    private $rateLimiter;

    public function __construct()
    {
        $this->database = new Database();
        $this->usuario = new Usuario($this->database->getConnection());
        $this->userSession = new UserSession($this->database->getConnection());
        $this->rateLimiter = new RateLimiter($this->database->getConnection());
    }

    public function login($username, $password)
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Verificar bloqueio de IP
            if ($this->rateLimiter->isBlocked($ip)) {
                $this->logMessage('warning', "IP bloqueado por excesso de tentativas: " . $ip);
                return [
                    'success' => false,
                    'message' => 'Muitas tentativas de login. Tente novamente em 15 minutos.',
                    'blocked' => true
                ];
            }

            $this->usuario->username = $username;
            $this->usuario->password = $password;

            $result = $this->usuario->login();

            if ($result && is_array($result)) {
                $this->rateLimiter->resetAttempts($ip);
                
                // Rotação do ID da sessão
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['username'] = $result['username'];
                $_SESSION['last_activity'] = time();

                $token = $this->userSession->createSession($result['id']);

                if ($token && $this->setCookie('auth_token', $token)) {
                    $this->logMessage('info', "Login bem-sucedido para usuário: " . $username);
                } else {
                    $this->logMessage('error', "Falha ao criar/definir token de autenticação");
                }

                return ['success' => true, 'message' => 'Login realizado com sucesso'];
            }

            // Incrementar tentativas em caso de falha
            $attemptsLeft = $this->maxLoginAttempts - $this->rateLimiter->incrementAttempts($ip);
            $this->logMessage('warning', "Tentativa de login falhou para usuário: " . $username);
            
            return [
                'success' => false,
                'message' => 'Usuário ou senha inválidos',
                'attempts_left' => $attemptsLeft
            ];
        } catch (Exception $e) {
            $this->logMessage('error', "Erro no login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao processar login'];
        }
    }

    private function setCookie($name, $value) {
        $result = setcookie($name, $value, [
            'expires' => time() + 28800,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        return $result && isset($_COOKIE[$name]);
    }

    private function logMessage($level, $message) {
        $logEntry = date('Y-m-d H:i:s') . " [$level] $message" . PHP_EOL;
        error_log($logEntry, 3, '../logs/auth.log');
    }

    public function isLoggedIn()
    {
        if (isset($_SESSION['user_id'])) {
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
                $this->logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
            return true;
        }

        $token = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
        if ($token) {
            $session = $this->userSession->validateSession($token);
            if ($session) {
                $this->userSession->updateActivity($token);
                $_SESSION['user_id'] = $session['user_id'];
                $_SESSION['username'] = $session['username'];
                $_SESSION['last_activity'] = time();
                return true;
            }
        }

        return false;
    }

    public function logout()
    {
        try {
            // Remover token de autenticação do banco
            $token = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
            if ($token) {
                $this->userSession->deleteSession($token);
            }

            // Limpar todas as variáveis de sessão
            $_SESSION = array();

            // Destruir o cookie da sessão
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time()-3600, '/');
            }

            // Destruir o cookie de autenticação
            setcookie('auth_token', '', time()-3600, '/', '', true, true);

            // Destruir a sessão
            session_destroy();

            return true;
        } catch (Exception $e) {
            error_log("Erro no logout: " . $e->getMessage());
            return false;
        }
    }
}
?>