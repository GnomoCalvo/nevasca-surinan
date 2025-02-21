<?php
require_once '../config/database.php';
require_once '../models/Cardapio.php';

class CardapioController
{
    private $database;
    private $cardapio;

    public function __construct()
    {
        $this->database = new Database();
        $this->cardapio = new Cardapio($this->database->getConnection());
    }

    public function criarItem($dados)
    {
        try {
            $this->cardapio->tipo = $dados['tipo'];
            $this->cardapio->sabor = $dados['sabor'];
            $this->cardapio->tamanho = $dados['tamanho'];
            $this->cardapio->preco = $dados['preco'];
            $this->cardapio->disponivel = isset($dados['disponivel']) ? 1 : 0;

            if ($this->cardapio->criar()) {
                return [
                    'success' => true,
                    'message' => 'Item adicionado ao cardápio com sucesso'
                ];
            }
            return [
                'success' => false,
                'message' => 'Erro ao adicionar item ao cardápio'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function verificarItemDuplicado($dados)
    {
        $tipo = $dados['tipo'] ?? '';
        $sabor = $dados['sabor'] ?? '';
        $tamanho = $dados['tamanho'] ?? '';
        $id = $dados['id'] ?? null;

        $duplicado = $this->cardapio->verificarDuplicado($tipo, $sabor, $tamanho, $id);

        return [
            'success' => true,
            'duplicado' => $duplicado
        ];
    }

    public function listarItens($tipo = null)
    {
        try {
            $result = $this->cardapio->listar($tipo);
            $itens = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $itens[] = [
                    'id' => $row['id'],
                    'tipo' => $row['tipo'],
                    'sabor' => $row['sabor'],
                    'tamanho' => $row['tamanho'],
                    'preco' => $row['preco'],
                    'disponivel' => $row['disponivel']
                ];
            }

            return [
                'success' => true,
                'data' => $itens
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function atualizarItem($id, $dados)
    {
        try {
            $this->cardapio->id = $id;
            $this->cardapio->tipo = $dados['tipo'];
            $this->cardapio->sabor = $dados['sabor'];
            $this->cardapio->tamanho = $dados['tamanho'];
            $this->cardapio->preco = $dados['preco'];
            $this->cardapio->disponivel = isset($dados['disponivel']) ? 1 : 0;

            if ($this->cardapio->atualizar()) {
                return [
                    'success' => true,
                    'message' => 'Item atualizado com sucesso'
                ];
            }
            return [
                'success' => false,
                'message' => 'Erro ao atualizar item'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deletarItem($id)
    {
        try {
            $this->cardapio->id = $id;
            if ($this->cardapio->deletar()) {
                return [
                    'success' => true,
                    'message' => 'Item removido com sucesso'
                ];
            }
            return [
                'success' => false,
                'message' => 'Erro ao remover item'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function buscarItem($id)
    {
        try {
            $item = $this->cardapio->buscarPorId($id);
            if ($item) {
                return [
                    'success' => true,
                    'data' => $item
                ];
            }
            return [
                'success' => false,
                'message' => 'Item não encontrado'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getTipos()
    {
        return [
            ['id' => 'sorvete', 'nome' => 'Sorvete'],
            ['id' => 'acai', 'nome' => 'Açaí'],
            ['id' => 'picole', 'nome' => 'Picolé']
        ];
    }

    public function getTamanhos($tipo)
    {
        return $this->cardapio->getTamanhosPorTipo($tipo);
    }
}

// Tratamento das requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new CardapioController();

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'criar':
                echo json_encode($controller->criarItem($_POST));
                break;

            case 'listar':
                echo json_encode($controller->listarItens(
                    $_POST['tipo'] ?? null
                ));
                break;

            case 'atualizar':
                echo json_encode($controller->atualizarItem(
                    $_POST['id'],
                    $_POST
                ));
                break;

            case 'deletar':
                echo json_encode($controller->deletarItem($_POST['id']));
                break;

            case 'buscar':
                echo json_encode($controller->buscarItem($_POST['id']));
                break;

            case 'tipos':
                echo json_encode([
                    'success' => true,
                    'data' => $controller->getTipos()
                ]);
                break;

            case 'tamanhos':
                echo json_encode([
                    'success' => true,
                    'data' => $controller->getTamanhos($_POST['tipo'])
                ]);
                break;

            case 'verificar_duplicado':
                echo json_encode($controller->verificarItemDuplicado($_POST));
                break;
        }
    }
}
?>