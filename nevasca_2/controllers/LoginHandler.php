<?php
// Inicie a sessão antes de qualquer output
session_start();

// Debug detalhado
error_log('=== LoginHandler Iniciado ===');
error_log('Session ID: ' . session_id());
error_log('POST recebido: ' . print_r($_POST, true));
error_log('SESSION antes: ' . print_r($_SESSION, true));

// Configurações de cabeçalho
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Função para retornar erro em formato JSON
function returnError($message) {
    error_log('Retornando erro: ' . $message);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'debug' => [
            'session_id' => session_id(),
            'session_token' => $_SESSION['csrf_token'] ?? 'não definido',
            'received_token' => $_POST['csrf_token'] ?? 'não recebido'
        ]
    ]);
    exit;
}

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Método não permitido');
}

try {
    // Verificar arquivos necessários
    $requiredFiles = [
        '../config/database.php' => 'Database configuration',
        'LoginController.php' => 'Login controller'
    ];

    foreach ($requiredFiles as $file => $description) {
        if (!file_exists($file)) {
            error_log("Arquivo não encontrado: $file");
            returnError("$description file not found");
        }
    }

    require_once '../config/database.php';
    require_once 'LoginController.php';

    // Verificar dados necessários
    if (!isset($_POST['action'])) {
        returnError('Ação não especificada');
    }

    // Debug CSRF
    error_log('Token na sessão: ' . ($_SESSION['csrf_token'] ?? 'não definido'));
    error_log('Token recebido: ' . ($_POST['csrf_token'] ?? 'não recebido'));

    // Verificação do CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        returnError('Token CSRF ausente');
    }

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log('Token na sessão: ' . $_SESSION['csrf_token']);
        error_log('Token recebido: ' . $_POST['csrf_token']);
        returnError('Token CSRF inválido');
    }

    $controller = new LoginController();

    switch ($_POST['action']) {
        case 'login':
            if (!isset($_POST['username']) || !isset($_POST['password'])) {
                returnError('Dados de login incompletos');
            }

            $result = $controller->login($_POST['username'], $_POST['password']);
            
            if ($result['success']) {
                // Regenerar ID da sessão após login bem-sucedido
                session_regenerate_id(true);
                // Gerar novo token CSRF
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            
            echo json_encode($result);
            break;

        default:
            returnError('Ação inválida');
    }

} catch (Exception $e) {
    error_log('Erro no LoginHandler: ' . $e->getMessage());
    returnError('Erro no processamento: ' . $e->getMessage());
}

error_log('=== LoginHandler Finalizado ===');
error_log('SESSION depois: ' . print_r($_SESSION, true));