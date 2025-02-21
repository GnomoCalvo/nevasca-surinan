<?php
require_once '../config/database.php';
require_once '../models/Produto.php';

class EstoqueController {
    private $database;
    private $produto;

    public function __construct() {
        $this->database = new Database();
        $this->produto = new Produto($this->database->getConnection());
    }

    public function listarProdutos() {
        try {
            $filtroTipo = isset($_POST['filtroTipo']) ? $_POST['filtroTipo'] : '';
            $filtroTamanho = isset($_POST['filtroTamanho']) ? $_POST['filtroTamanho'] : '';
            
            $stmt = $this->produto->listar($filtroTipo, $filtroTamanho);
            $produtos = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produtos[] = array(
                    'id' => $row['id'],
                    'tipo' => $row['tipo'],
                    'nome' => $row['nome'],
                    'tamanho' => $row['tamanho'],
                    'categoria' => $row['categoria'],
                    'preco' => $row['preco'],
                    'quantidade' => $row['quantidade']
                );
            }
            
            return array(
                'success' => true,
                'data' => $produtos
            );
        } catch (Exception $e) {
            error_log('Erro ao listar produtos: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Erro ao listar produtos'
            );
        }
    }

    public function criarProduto($dados) {
        $this->produto->tipo = $dados['tipo'];
        $this->produto->nome = isset($dados['nome']) ? $dados['nome'] : null;
        $this->produto->tamanho = $dados['tamanho'];
        $this->produto->categoria = isset($dados['categoria']) ? $dados['categoria'] : 'NA';
        $this->produto->preco = $dados['preco'];
        $this->produto->quantidade = $dados['quantidade'];

        $produtoExistente = $this->produto->verificarProdutoExistente();

        if ($produtoExistente) {
            return ['success' => false, 'message' => 'Produto já existe no estoque'];
        }

        if ($this->produto->criar()) {
            return ['success' => true, 'message' => 'Produto criado com sucesso'];
        }

        return ['success' => false, 'message' => 'Erro ao criar produto'];
    }

    public function atualizarProduto($id, $dados) {
        $this->produto->id = $id;
        $this->produto->tipo = $dados['tipo'];
        $this->produto->nome = isset($dados['nome']) ? $dados['nome'] : null;
        $this->produto->tamanho = $dados['tamanho'];
        $this->produto->categoria = isset($dados['categoria']) ? $dados['categoria'] : 'NA';
        $this->produto->preco = $dados['preco'];
        $this->produto->quantidade = $dados['quantidade'];

        if ($this->produto->atualizar()) {
            return ['success' => true, 'message' => 'Produto atualizado com sucesso'];
        }

        return ['success' => false, 'message' => 'Erro ao atualizar produto'];
    }

    public function deletarProduto($id) {
        $this->produto->id = $id;

        if ($this->produto->deletar()) {
            return ['success' => true, 'message' => 'Produto deletado com sucesso'];
        }

        return ['success' => false, 'message' => 'Erro ao deletar produto'];
    }

    public function buscarProduto($id) {
        $this->produto->id = $id;
        return $this->produto->buscarPorId();
    }
}

// Limpar qualquer saída anterior
ob_clean();

// Tratamento das requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new EstoqueController();
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'listar':
                    $result = $controller->listarProdutos();
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'criar':
                    echo json_encode($controller->criarProduto($_POST));
                    break;

                case 'atualizar':
                    echo json_encode($controller->atualizarProduto($_POST['id'], $_POST));
                    break;

                case 'deletar':
                    echo json_encode($controller->deletarProduto($_POST['id']));
                    break;

                case 'buscar':
                    echo json_encode($controller->buscarProduto($_POST['id']));
                    break;

                default:
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Ação inválida'
                    ));
            }
        } else {
            throw new Exception('Ação não especificada');
        }
    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
    }
    exit;
}
?>