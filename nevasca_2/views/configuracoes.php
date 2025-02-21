<?php
require_once '../config/auth.php';
require_once '../controllers/ConfiguracoesController.php';
$controller = new ConfiguracoesController();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nevasca - Configurações</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/configuracoes.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include_once 'components/header.php'; ?>
    <script src="../assets/js/notifications.js"></script>

    <main class="container">
        <div class="config-container">
            <h1>Configurações</h1>

            <div class="card-config">
                <h2>Atualizar Credenciais</h2>

                <form id="formCredenciais" onsubmit="atualizarCredenciais(event)">
                    <div class="form-group">
                        <label for="username">Novo Nome de Usuário:</label>
                        <input type="text" id="username" name="username" value="<?php echo $_SESSION['username']; ?>"
                            required minlength="5" pattern="[A-Za-z0-9_]+"
                            title="Use apenas letras, números e underline">
                    </div>

                    <div class="form-group">
                        <label for="current_password">Senha Atual:</label>
                        <div class="password-input">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('current_password')">

                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Nova Senha:</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required minlength="8"
                                pattern="(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:,<.>])[A-Za-z\d!@#$%^&*()\-_=+{};:,<.>]{8,}"
                                title="Mínimo 8 caracteres, com pelo menos uma letra, um número e um caractere especial">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">

                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nova Senha:</label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">

                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-salvar">Atualizar Credenciais</button>
                    </div>

                    <div id="mensagem" class="mensagem"></div>
                </form>
            </div>

            <div class="card-info">
                <h3>Requisitos de Segurança</h3>
                <ul>
                    <li>Nome de usuário: mínimo 5 caracteres, apenas letras, números e underline</li>
                    <li>Senha: mínimo 8 caracteres</li>
                    <li>Senha deve conter pelo menos uma letra, um número e um caractere especial</li>
                </ul>
            </div>
        </div>
    </main>

    <script src="../assets/js/configuracoes.js?v=<?php echo time(); ?>"></script>
</body>

</html>