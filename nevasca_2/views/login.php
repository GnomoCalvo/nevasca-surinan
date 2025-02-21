<?php
// Garante que a sessão está limpa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug da sessão
error_log('=== Início do processamento da página de login ===');
error_log('Session ID inicial: ' . session_id());
error_log('Session data inicial: ' . print_r($_SESSION, true));

// Regenera o ID da sessão para prevenir session fixation
session_regenerate_id(true);

error_log('Session ID após regeneração: ' . session_id());

// Gera um novo token CSRF apenas se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log('Novo CSRF Token gerado');
} else {
    error_log('CSRF Token existente mantido');
}

$csrf_token = $_SESSION['csrf_token'];

// Debug final
error_log('CSRF Token final: ' . $csrf_token);
error_log('Session data final: ' . print_r($_SESSION, true));
error_log('Cookie PHPSESSID: ' . (isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : 'não definido'));
error_log('=== Fim do processamento da página de login ===');

// Verificar configurações de cookie
$cookie_params = session_get_cookie_params();
error_log('Configurações do cookie de sessão: ' . print_r($cookie_params, true));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nevasca</title>
    <link rel="stylesheet" href="../assets/css/login.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo-container">
                <img src="../assets/img/logo.png" alt="Nevasca">
            </div>
            <h1>Bem-vindo</h1>
            
            <form id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="input-group">
                    <label for="username">Usuário</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required minlength="5">
                </div>
                
                <div class="input-group">
                    <label for="password">Senha</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <button type="submit">Entrar</button>
                <div id="errorMessage" class="error-message"></div>
            </form>
        </div>
    </div>
    <script>
        // Debug do token CSRF no carregamento da página
        console.log('CSRF Token no carregamento:', '<?php echo $csrf_token; ?>');
    </script>
    <script src="../assets/js/login.js?v=<?php echo time(); ?>"></script>
</body>
</html>