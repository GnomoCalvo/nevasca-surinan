<?php
require_once '../config/auth.php';
require_once '../controllers/RelatoriosController.php';
$controller = new RelatoriosController();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nevasca - Relatórios</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/relatorios.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include_once 'components/header.php'; ?>
    <script src="../assets/js/notifications.js"></script>

    <main class="container">
        <h1>Relatórios</h1>

        <!-- Filtros de Período -->
        <div class="filtros">
            <div class="periodo">
                <label for="dataInicio">De:</label>
                <input type="date" id="dataInicio">
                <label for="dataFim">Até:</label>
                <input type="date" id="dataFim">
            </div>
            <button onclick="gerarRelatorios()">Gerar Relatórios</button>
        </div>

        <div class="acoes-exportacao">
            <button onclick="exportarXML()">Exportar Financeiro (XML)</button>
            <button onclick="exportarPDF()">Exportar Relatório (PDF)</button>
        </div>

        <!-- Grid de Relatórios -->
        <div class="grid-relatorios">
            <!-- Relatório de Vendas -->
            <div class="card-relatorio">
                <h2>Vendas</h2>
                <div class="resumo-vendas">
                    <div class="info-item">
                        <span>Total de Vendas:</span>
                        <span id="totalVendas">0</span>
                    </div>
                    <div class="info-item">
                        <span>Valor Total:</span>
                        <span id="valorTotalVendas">R$ 0,00</span>
                    </div>
                    <div class="info-item">
                        <span>Formas de Pagamento:</span>
                        <div id="formasPagamento" class="formas-pagamento-grid">
                            <!-- Preenchido via JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="grafico-container">
                    <canvas id="graficoVendas"></canvas>
                </div>
            </div>

            <!-- Relatório de Produtos -->
            <div class="card-relatorio">
                <h2>Produtos Mais Vendidos</h2>
                <div class="resumo-produtos">
                    <div class="info-item">
                        <span>Total de Itens:</span>
                        <span id="totalItens">0</span>
                    </div>
                    <div class="info-item">
                        <span>Valor Total:</span>
                        <span id="valorTotalProdutos">R$ 0,00</span>
                    </div>
                </div>
                <div class="tabela-produtos">
                    <table>
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody id="produtosMaisVendidos">
                            <!-- Preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Relatório de Estoque -->
            <div class="card-relatorio">
                <h2>Alertas de Estoque</h2>
                <div class="resumo-estoque">
                    <div class="info-item">
                        <span>Produtos com Estoque Baixo:</span>
                        <span id="totalAlertas">0</span>
                    </div>
                </div>
                <div class="tabela-estoque">
                    <table>
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Preço</th>
                            </tr>
                        </thead>
                        <tbody id="produtosEstoqueBaixo">
                            <!-- Preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Relatório de Despesas -->
            <div class="card-relatorio">
                <h2>Despesas e Pedidos</h2>
                <div class="resumo-despesas">
                    <div class="info-item">
                        <span>Total de Despesas:</span>
                        <span id="totalDespesas">R$ 0,00</span>
                    </div>
                    <div class="info-item">
                        <span>Pedidos perto da data de vencimento:</span>
                        <span id="totalPedidosVencendo">0</span>
                    </div>
                </div>

                <!-- Seção de Despesas -->
                <h3>Despesas</h3>
                <div class="tabela-despesas">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody id="listaDespesas">
                            <!-- Preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Nova seção de Pedidos -->
                <h3>Pedidos perto da data de vencimento</h3>
                <div class="tabela-pedidos">
                    <table>
                        <thead>
                            <tr>
                                <th>Data Vencimento</th>
                                <th>Itens</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody id="listaPedidos">
                            <!-- Preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Balanço -->
            <div class="card-relatorio balanço">
                <h2>Balanço do Período</h2>
                <div class="resumo-balanco">
                    <div class="info-item receitas">
                        <span>Receitas:</span>
                        <span id="totalReceitas">R$ 0,00</span>
                    </div>
                    <div class="info-item despesas">
                        <span>Despesas:</span>
                        <span id="totalDespesasBalanco">R$ 0,00</span>
                    </div>
                    <div class="info-item lucro">
                        <span>Lucro:</span>
                        <span id="totalLucro">R$ 0,00</span>
                    </div>
                </div>
                <div class="grafico-container">
                    <canvas id="graficoBalanco"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/relatorios.js?v=<?php echo time(); ?>"></script>
</body>

</html>