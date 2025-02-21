<?php
require_once '../config/database.php';
require_once '../models/Venda.php';
require_once '../models/Produto.php';

class VendasController
{
    private $database;
    private $venda;
    private $produto;

    public function __construct()
    {
        $this->database = new Database();
        $this->venda = new Venda($this->database->getConnection());
        $this->produto = new Produto($this->database->getConnection());
    }

    public function criarVenda($dados)
    {
        try {
            if (empty($dados['itens'])) {
                throw new Exception('Nenhum item informado');
            }

            if (empty($dados['pagamentos'])) {
                throw new Exception('Nenhuma forma de pagamento informada');
            }

            $this->venda->valor_total = 0;
            $this->venda->pagamentos = [];
            $this->venda->itens = [];

            // Processar itens da venda
            foreach ($dados['itens'] as $item) {
                if (!isset($item['produto_id']) || !isset($item['quantidade']) || !isset($item['preco_unitario'])) {
                    throw new Exception('Dados do item inválidos');
                }

                // Validar dados
                if (!is_numeric($item['quantidade']) || $item['quantidade'] <= 0) {
                    throw new Exception('Quantidade inválida');
                }
                if (!is_numeric($item['preco_unitario']) || $item['preco_unitario'] <= 0) {
                    throw new Exception('Preço unitário inválido');
                }

                $valorItem = $item['quantidade'] * $item['preco_unitario'];
                $this->venda->valor_total += $valorItem;

                $this->venda->itens[] = [
                    'produto_id' => $item['produto_id'],
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario']
                ];
            }

            // Validar pagamentos
            $totalPagamentos = 0;
            foreach ($dados['pagamentos'] as $pagamento) {
                if (!isset($pagamento['forma']) || !isset($pagamento['valor'])) {
                    throw new Exception('Dados do pagamento inválidos');
                }

                if (!is_numeric($pagamento['valor']) || $pagamento['valor'] <= 0) {
                    throw new Exception('Valor do pagamento inválido');
                }

                $totalPagamentos += $pagamento['valor'];
                $this->venda->pagamentos[] = $pagamento;
            }

            // Verificar se o total dos pagamentos corresponde ao total da venda
            if (abs($totalPagamentos - $this->venda->valor_total) > 0.01) {
                throw new Exception('O total dos pagamentos não corresponde ao valor total da venda');
            }

            if ($this->venda->criar()) {
                return [
                    'success' => true,
                    'message' => 'Venda realizada com sucesso',
                    'venda_id' => $this->venda->id
                ];
            }
            return ['success' => false, 'message' => 'Erro ao realizar venda'];
        } catch (Exception $e) {
            error_log('Erro ao criar venda: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listarVendas($dataInicio = null, $dataFim = null)
    {
        try {
            $result = $this->venda->listar($dataInicio, $dataFim);
            $vendas = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $venda = [
                    'id' => $row['id'],
                    'data_venda' => $row['data_venda'],
                    'valor_total' => $row['valor_total'],
                    'pagamentos' => [],
                    'itens' => []
                ];

                // Processar pagamentos
                if ($row['pagamentos']) {
                    $pagamentos = explode(';;', $row['pagamentos']);
                    foreach ($pagamentos as $pagamento) {
                        list($forma, $valor) = explode('|', $pagamento);
                        $venda['pagamentos'][] = [
                            'forma' => $forma,
                            'valor' => $valor
                        ];
                    }
                }

                // Processar itens
                if ($row['itens']) {
                    $itens = explode(';;', $row['itens']);
                    foreach ($itens as $item) {
                        list($nome, $quantidade, $preco_unitario) = explode('|', $item);
                        $venda['itens'][] = [
                            'nome' => $nome,
                            'quantidade' => $quantidade,
                            'preco_unitario' => $preco_unitario
                        ];
                    }
                }

                $vendas[] = $venda;
            }

            return ['success' => true, 'data' => $vendas];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deletarVenda($id)
    {
        try {
            $this->venda->id = $id;
            if ($this->venda->deletar()) {
                return ['success' => true, 'message' => 'Venda deletada com sucesso'];
            }
            return ['success' => false, 'message' => 'Erro ao deletar venda'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function buscarProdutosDisponiveis()
    {
        try {
            $query = "SELECT id, tipo, nome, tamanho, categoria, preco, quantidade 
                     FROM produtos 
                     WHERE quantidade > 0 
                     ORDER BY tipo, nome";

            $stmt = $this->database->getConnection()->prepare($query);
            $stmt->execute();

            $produtos = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produto = [
                    'id' => $row['id'],
                    'tipo' => $row['tipo'],
                    'nome' => $row['nome'],
                    'tamanho' => $row['tamanho'],
                    'categoria' => $row['categoria'],
                    'preco' => floatval($row['preco']),
                    'quantidade' => intval($row['quantidade'])
                ];
                $produtos[] = $produto;
            }

            return ['success' => true, 'data' => $produtos];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function buscarResumoVendasDiarias()
    {
        try {
            $stmt = $this->venda->buscarVendasDiarias();
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            $formasPagamento = [];

            if ($dados) {
                if ($dados['qtd_dinheiro'] > 0) {
                    $formasPagamento[] = [
                        'forma' => 'Dinheiro',
                        'quantidade' => $dados['qtd_dinheiro']
                    ];
                }
                if ($dados['qtd_pix'] > 0) {
                    $formasPagamento[] = [
                        'forma' => 'PIX',
                        'quantidade' => $dados['qtd_pix']
                    ];
                }
                if ($dados['qtd_debito'] > 0) {
                    $formasPagamento[] = [
                        'forma' => 'Débito',
                        'quantidade' => $dados['qtd_debito']
                    ];
                }
                if ($dados['qtd_credito'] > 0) {
                    $formasPagamento[] = [
                        'forma' => 'Crédito',
                        'quantidade' => $dados['qtd_credito']
                    ];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'total_vendas' => $dados['total_vendas'] ?? 0,
                    'valor_total' => $dados['valor_total'] ?? 0,
                    'formas_pagamento' => $formasPagamento
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getFormasPagamento()
    {
        return [
            ['id' => 'dinheiro', 'nome' => 'Dinheiro'],
            ['id' => 'pix', 'nome' => 'PIX'],
            ['id' => 'debito', 'nome' => 'Cartão de Débito'],
            ['id' => 'credito', 'nome' => 'Cartão de Crédito']
        ];
    }
}

// Tratamento das requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new VendasController();

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'criar_venda':
                $dados = [
                    'pagamentos' => json_decode($_POST['pagamentos'], true),
                    'itens' => json_decode($_POST['itens'], true)
                ];
                echo json_encode($controller->criarVenda($dados));
                break;

            case 'listar_vendas':
                echo json_encode($controller->listarVendas(
                    $_POST['data_inicio'] ?? null,
                    $_POST['data_fim'] ?? null
                ));
                break;

            case 'deletar_venda':
                echo json_encode($controller->deletarVenda($_POST['id']));
                break;

            case 'produtos_disponiveis':
                echo json_encode($controller->buscarProdutosDisponiveis());
                break;

            case 'resumo_diario':
                echo json_encode($controller->buscarResumoVendasDiarias());
                break;

            case 'formas_pagamento':
                echo json_encode([
                    'success' => true,
                    'data' => $controller->getFormasPagamento()
                ]);
                break;
        }
    }
}
?>