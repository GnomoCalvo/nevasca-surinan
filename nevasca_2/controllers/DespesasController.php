<?php
require_once '../config/database.php';
require_once '../models/Pedido.php';
require_once '../models/Despesa.php';
require_once '../models/Produto.php';

class DespesasController
{
    private $database;
    private $pedido;
    private $despesa;

    public function __construct()
    {
        $this->database = new Database();
        $this->pedido = new Pedido($this->database->getConnection());
        $this->despesa = new Despesa($this->database->getConnection());
    }

    public function criarPedido($dados)
    {
        try {
            $this->pedido->data_vencimento = $dados['data_vencimento'];
            $this->pedido->valor_total = 0;
            $this->pedido->itens = [];

            // Processar itens do pedido
            foreach ($dados['itens'] as $item) {
                $valorItem = $item['quantidade'] * $item['preco_unitario'];
                $this->pedido->valor_total += $valorItem;

                $this->pedido->itens[] = [
                    'nome' => $item['nome'],
                    'tamanho' => $item['tamanho'],
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario']
                ];
            }

            if ($this->pedido->criar()) {
                return ['success' => true, 'message' => 'Pedido criado com sucesso'];
            }
            return ['success' => false, 'message' => 'Erro ao criar pedido'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listarPedidos($dataInicio = null, $dataFim = null)
    {
        try {
            $result = $this->pedido->listar($dataInicio, $dataFim);
            $pedidos = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $pedido = [
                    'id' => $row['id'],
                    'data_pedido' => $row['data_pedido'],
                    'data_vencimento' => $row['data_vencimento'],
                    'valor_total' => $row['valor_total'],
                    'status' => $row['status'] ?? 'pendente', // Garantir que o status seja incluído
                    'itens' => []
                ];

                if ($row['itens']) {
                    $itens = explode(';;', $row['itens']);
                    foreach ($itens as $item) {
                        list($nome, $tamanho, $quantidade, $preco_unitario) = explode('|', $item);
                        $pedido['itens'][] = [
                            'nome' => $nome,
                            'tamanho' => $tamanho,
                            'quantidade' => $quantidade,
                            'preco_unitario' => $preco_unitario
                        ];
                    }
                }

                $pedidos[] = $pedido;
            }

            return ['success' => true, 'data' => $pedidos];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deletarPedido($id)
    {
        try {
            $this->pedido->id = $id;
            if ($this->pedido->deletar()) {
                return ['success' => true, 'message' => 'Pedido deletado com sucesso'];
            }
            return ['success' => false, 'message' => 'Erro ao deletar pedido'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function atualizarStatusPedido($id, $status)
    {
        try {
            if ($this->pedido->atualizarStatus($id, $status)) {
                return [
                    'success' => true,
                    'message' => 'Status do pedido atualizado com sucesso'
                ];
            }
            return ['success' => false, 'message' => 'Erro ao atualizar status do pedido'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function criarDespesa($dados)
    {
        try {
            $this->despesa->nome = $dados['nome'];
            $this->despesa->valor = $dados['valor'];

            if ($this->despesa->criar()) {
                return ['success' => true, 'message' => 'Despesa criada com sucesso'];
            }
            return ['success' => false, 'message' => 'Erro ao criar despesa'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listarDespesas($dataInicio = null, $dataFim = null)
    {
        try {
            $result = $this->despesa->listar($dataInicio, $dataFim);
            $despesas = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $despesas[] = [
                    'id' => $row['id'],
                    'nome' => $row['nome'],
                    'valor' => $row['valor'],
                    'data_despesa' => $row['data_despesa']
                ];
            }

            return ['success' => true, 'data' => $despesas];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deletarDespesa($id)
    {
        try {
            $this->despesa->id = $id;
            if ($this->despesa->deletar()) {
                return ['success' => true, 'message' => 'Despesa deletada com sucesso'];
            }
            return ['success' => false, 'message' => 'Erro ao deletar despesa'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function buscarPedidosVencendo()
    {
        try {
            $result = $this->pedido->buscarPedidosVencendo();
            $pedidos = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $pedidos[] = [
                    'id' => $row['id'],
                    'data_vencimento' => $row['data_vencimento'],
                    'valor_total' => $row['valor_total']
                ];
            }

            return ['success' => true, 'data' => $pedidos];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getListaProdutosPedido()
    {
        return [
            ['nome' => 'Sorvete 2L', 'tamanho' => '2L'],
            ['nome' => 'Sorvete 5L', 'tamanho' => '5L'],
            ['nome' => 'Sorvete 10L', 'tamanho' => '10L'],
            ['nome' => 'Açaí 2L', 'tamanho' => '2L'],
            ['nome' => 'Açaí 5L', 'tamanho' => '5L'],
            ['nome' => 'Açaí 10L', 'tamanho' => '10L'],
            ['nome' => 'Cobertura Pequena', 'tamanho' => 'Unidade'],
            ['nome' => 'Cobertura Grande', 'tamanho' => 'Unidade'],
            ['nome' => 'Picolé Comum', 'tamanho' => 'Unidade'],
            ['nome' => 'Picolé Premium', 'tamanho' => 'Unidade'],
            ['nome' => 'Picolé Recheado', 'tamanho' => 'Unidade'],
            ['nome' => 'Cascão', 'tamanho' => 'Unidade'],
            ['nome' => 'Cone', 'tamanho' => 'Unidade'],
            ['nome' => 'Paleta', 'tamanho' => 'Unidade'],
            ['nome' => 'Açaízinho', 'tamanho' => 'Unidade'],
            ['nome' => 'Leite em Pó Alibra', 'tamanho' => '25kg'],
            ['nome' => 'Leite em Pó Alibra', 'tamanho' => '1kg'],
            ['nome' => 'Leite em Pó Leitino', 'tamanho' => '1kg'],
            ['nome' => 'Paçoquinha', 'tamanho' => '100un'],
            ['nome' => 'Granola', 'tamanho' => '1kg'],
            ['nome' => 'Coloret', 'tamanho' => '500g'],
            ['nome' => 'Coloret', 'tamanho' => '1kg'],
            ['nome' => 'Coloret', 'tamanho' => '5kg'],
            ['nome' => 'Mix de Cereal', 'tamanho' => '400g'],
            ['nome' => 'Ovomaltine', 'tamanho' => '750g'],
            ['nome' => 'Gotas de Chocolate', 'tamanho' => '1kg'],
            ['nome' => 'Gotas de Chocolate', 'tamanho' => '2.5kg'],
            ['nome' => 'Colher', 'tamanho' => '50un'],
            ['nome' => 'Colher', 'tamanho' => '500un'],
            ['nome' => 'Bala de Goma', 'tamanho' => '1kg'],
            ['nome' => 'Amendoim Granulado', 'tamanho' => '1kg'],
            ['nome' => 'Leite Condensado', 'tamanho' => '395g']
        ];
    }

    public function exportarRelatorio($dataInicio, $dataFim, $tipo)
    {
        try {
            $dados = [];

            if ($tipo !== 'despesas') {
                $pedidos = $this->listarPedidos($dataInicio, $dataFim)['data'];
                $dados = array_merge($dados, $pedidos);
            }

            if ($tipo !== 'pedidos') {
                $despesas = $this->listarDespesas($dataInicio, $dataFim)['data'];
                $dados = array_merge($dados, $despesas);
            }

            // Ordenar por data
            usort($dados, function ($a, $b) {
                return strtotime($b['data']) - strtotime($a['data']);
            });

            // Gerar CSV
            $output = fopen('php://output', 'w');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=relatorio.csv');

            // Cabeçalho
            fputcsv($output, ['Data', 'Tipo', 'Descrição', 'Valor']);

            // Dados
            foreach ($dados as $item) {
                fputcsv($output, [
                    $item['data'],
                    isset($item['data_pedido']) ? 'Pedido' : 'Despesa',
                    isset($item['data_pedido']) ? 'Pedido #' . $item['id'] : $item['nome'],
                    number_format($item['valor_total'] ?? $item['valor'], 2, ',', '.')
                ]);
            }

            fclose($output);
            return true;
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Tratamento das requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $controller = new DespesasController();

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'criar_pedido':
                $dados = [
                    'data_vencimento' => $_POST['data_vencimento'],
                    'itens' => json_decode($_POST['itens'], true)
                ];
                echo json_encode($controller->criarPedido($dados));
                break;

            case 'listar_pedidos':
                echo json_encode($controller->listarPedidos(
                    $_POST['data_inicio'] ?? null,
                    $_POST['data_fim'] ?? null
                ));
                break;

            case 'deletar_pedido':
                echo json_encode($controller->deletarPedido($_POST['id']));
                break;

            case 'criar_despesa':
                echo json_encode($controller->criarDespesa($_POST));
                break;

            case 'listar_despesas':
                echo json_encode($controller->listarDespesas(
                    $_POST['data_inicio'] ?? null,
                    $_POST['data_fim'] ?? null
                ));
                break;

            case 'deletar_despesa':
                echo json_encode($controller->deletarDespesa($_POST['id']));
                break;

            case 'pedidos_vencendo':
                echo json_encode($controller->buscarPedidosVencendo());
                break;

            case 'lista_produtos':
                echo json_encode(['success' => true, 'data' => $controller->getListaProdutosPedido()]);
                break;

            case 'atualizar_status_pedido':
                echo json_encode($controller->atualizarStatusPedido(
                    $_POST['id'],
                    $_POST['status']
                ));
                break;
        }
    }
}
?>