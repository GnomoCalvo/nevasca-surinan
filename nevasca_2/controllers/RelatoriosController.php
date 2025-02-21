<?php
require_once '../config/database.php';
require_once '../models/Relatorio.php';
require_once '../models/Produto.php';

class RelatoriosController
{
    private $database;
    private $relatorio;

    public function __construct()
    {
        $this->database = new Database();
        $this->relatorio = new Relatorio($this->database->getConnection());
    }

    public function gerarRelatorioVendas($dataInicio, $dataFim)
    {
        try {
            $result = $this->relatorio->vendasPorPeriodo($dataInicio, $dataFim);
            $vendas = [];
            $totalGeral = 0;
            $totalVendas = 0;
            $formasPagamento = [
                'dinheiro' => ['quantidade' => 0, 'valor' => 0],
                'pix' => ['quantidade' => 0, 'valor' => 0],
                'debito' => ['quantidade' => 0, 'valor' => 0],
                'credito' => ['quantidade' => 0, 'valor' => 0]
            ];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $totalGeral += $row['valor_total'];
                $totalVendas += $row['total_vendas'];

                // Atualizar quantidades e valores por forma de pagamento
                $formasPagamento['dinheiro']['quantidade'] += $row['qtd_dinheiro'];
                $formasPagamento['dinheiro']['valor'] += $row['total_dinheiro'];

                $formasPagamento['pix']['quantidade'] += $row['qtd_pix'];
                $formasPagamento['pix']['valor'] += $row['total_pix'];

                $formasPagamento['debito']['quantidade'] += $row['qtd_debito'];
                $formasPagamento['debito']['valor'] += $row['total_debito'];

                $formasPagamento['credito']['quantidade'] += $row['qtd_credito'];
                $formasPagamento['credito']['valor'] += $row['total_credito'];

                $vendas[] = [
                    'data' => $row['data'],
                    'total_vendas' => $row['total_vendas'],
                    'valor_total' => $row['valor_total']
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'vendas' => $vendas,
                    'total_geral' => $totalGeral,
                    'total_vendas' => $totalVendas,
                    'formas_pagamento' => $formasPagamento
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function gerarRelatorioProdutos($dataInicio, $dataFim)
    {
        try {
            $result = $this->relatorio->produtosMaisVendidos($dataInicio, $dataFim);
            $produtos = [];
            $totalQuantidade = 0;
            $totalValor = 0;

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $totalQuantidade += $row['quantidade_vendida'];
                $totalValor += $row['valor_total'];

                $produtos[] = [
                    'tipo' => $row['tipo'],
                    'nome' => $row['nome'] ?: $row['tipo'],
                    'tamanho' => $row['tamanho'],
                    'quantidade_vendida' => $row['quantidade_vendida'],
                    'valor_total' => $row['valor_total']
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'produtos' => $produtos,
                    'total_quantidade' => $totalQuantidade,
                    'total_valor' => $totalValor
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function gerarRelatorioEstoque()
    {
        try {
            $result = $this->relatorio->estoqueAtual();
            $produtos = [];
            $alertas = 0;

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $produtos[] = [
                    'tipo' => $row['tipo'],
                    'nome' => $row['nome'] ?: $row['tipo'],
                    'tamanho' => $row['tamanho'],
                    'categoria' => $row['categoria'],
                    'quantidade' => $row['quantidade'],
                    'preco' => $row['preco']
                ];
                $alertas++;
            }

            return [
                'success' => true,
                'data' => [
                    'produtos' => $produtos,
                    'total_alertas' => $alertas
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function gerarRelatorioDespesas($dataInicio, $dataFim)
    {
        try {
            $result = $this->relatorio->despesasPorPeriodo($dataInicio, $dataFim);
            $despesas = [];
            $totalDespesas = 0;

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $totalDespesas += $row['valor'];
                $despesas[] = [
                    'data' => $row['data'],
                    'nome' => $row['nome'],
                    'valor' => $row['valor']
                ];
            }

            // Buscar pedidos vencendo
            $pedidosVencendo = $this->relatorio->pedidosVencendo();
            $alertasPedidos = [];

            while ($row = $pedidosVencendo->fetch(PDO::FETCH_ASSOC)) {
                $itens = [];
                if ($row['itens']) {
                    $itensPedido = explode(';;', $row['itens']);
                    foreach ($itensPedido as $item) {
                        list($nome, $quantidade, $preco) = explode('|', $item);
                        $itens[] = [
                            'nome' => $nome,
                            'quantidade' => $quantidade,
                            'preco' => $preco
                        ];
                    }
                }

                $alertasPedidos[] = [
                    'id' => $row['id'],
                    'data_vencimento' => $row['data_vencimento'],
                    'valor_total' => $row['valor_total'],
                    'status' => $row['status'], // Adicionando o status aqui
                    'itens' => $itens
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'despesas' => $despesas,
                    'total_despesas' => $totalDespesas,
                    'pedidos_vencendo' => $alertasPedidos
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function exportarXML($dataInicio, $dataFim)
    {
        try {
            require_once '../models/RelatorioXML.php';
            $xmlGenerator = new RelatorioXML($this->relatorio);
            $xml = $xmlGenerator->gerarXMLFinanceiro($dataInicio, $dataFim);

            return [
                'success' => true,
                'data' => [
                    'xml' => $xml
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function gerarBalanco($dataInicio, $dataFim)
    {
        try {
            $balanco = $this->relatorio->balancoPeriodo($dataInicio, $dataFim);
            return [
                'success' => true,
                'data' => $balanco
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Tratamento das requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new RelatoriosController();

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'relatorio_vendas':
                echo json_encode($controller->gerarRelatorioVendas(
                    $_POST['data_inicio'],
                    $_POST['data_fim']
                ));
                break;

            case 'relatorio_produtos':
                echo json_encode($controller->gerarRelatorioProdutos(
                    $_POST['data_inicio'],
                    $_POST['data_fim']
                ));
                break;

            case 'relatorio_estoque':
                echo json_encode($controller->gerarRelatorioEstoque());
                break;

            case 'relatorio_despesas':
                echo json_encode($controller->gerarRelatorioDespesas(
                    $_POST['data_inicio'],
                    $_POST['data_fim']
                ));
                break;

            case 'balanco':
                echo json_encode($controller->gerarBalanco(
                    $_POST['data_inicio'],
                    $_POST['data_fim']
                ));
                break;

            case 'exportar_xml':
                echo json_encode($controller->exportarXML(
                    $_POST['data_inicio'],
                    $_POST['data_fim']
                ));
                break;
        }
    }
}
?>