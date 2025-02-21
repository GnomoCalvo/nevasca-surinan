<?php
require_once '../config/database.php';
require_once '../models/Usuario.php';

class ConfiguracoesController {
    private $database;
    private $usuario;

    public function __construct() {
        $this->database = new Database();
        $this->usuario = new Usuario($this->database->getConnection());
    }

    public function atualizarCredenciais($dados) {
        try {
            // Validações
            if (!$this->usuario->validateUsername($dados['username'])) {
                return [
                    'success' => false,
                    'message' => 'Usuário inválido. Use no mínimo 5 caracteres, apenas letras, números e underline.'
                ];
            }

            if (!$this->usuario->validatePassword($dados['password'])) {
                return [
                    'success' => false,
                    'message' => 'Senha inválida. Use no mínimo 8 caracteres, com pelo menos uma letra e um número.'
                ];
            }

            if ($dados['password'] !== $dados['confirm_password']) {
                return [
                    'success' => false,
                    'message' => 'As senhas não conferem.'
                ];
            }

            // Atualiza as credenciais
            $this->usuario->id = $_SESSION['user_id'];
            $this->usuario->username = $dados['username'];
            $this->usuario->password = $dados['password'];
            $this->usuario->current_password = $dados['current_password'];

            if ($this->usuario->updateCredentials()) {
                // Atualiza a sessão com o novo username
                $_SESSION['username'] = $dados['username'];
                
                return [
                    'success' => true,
                    'message' => 'Credenciais atualizadas com sucesso!'
                ];
            }

            return [
                'success' => false,
                'message' => 'Senha atual incorreta.'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar credenciais: ' . $e->getMessage()
            ];
        }
    }
}

// Tratamento das requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não autenticado'
        ]);
        exit;
    }

    $controller = new ConfiguracoesController();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'atualizar_credenciais':
                echo json_encode($controller->atualizarCredenciais($_POST));
                break;
        }
    }
}
?>