<?php
require_once '../config/auth.php';
require_once '../controllers/VendasController.php';
$controller = new VendasController();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nevasca - Vendas</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/vendas.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include_once 'components/header.php'; ?>
    <script src="../assets/js/notifications.js"></script>

    <main class="container">
        <div class="grid-vendas">
            <!-- Painel Esquerdo - Nova Venda -->
            <div class="painel-venda">
                <h2>Nova Venda</h2>
                <form id="formVenda">
                    <div class="produtos-venda">
                        <div class="selecao-produto">
                            <div class="form-group">
                                <label for="selectProduto">Produto:</label>
                                <select id="selectProduto" class="form-control">
                                    <option value="">Selecione um produto...</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="quantidadeProduto">Quantidade:</label>
                                <input type="number" id="quantidadeProduto" class="form-control" value="1" min="1"
                                    step="0.1">
                            </div>

                            <div class="form-group self-service-config" style="display: none;">
                                <label for="precoSelfService">Preço do Self-Service (kg):</label>
                                <input type="number" id="precoSelfService" class="form-control" step="0.01" min="0"
                                    value="0.00">
                            </div>

                            <button type="button" class="btn-adicionar" onclick="adicionarProdutoSelecionado()">
                                Adicionar à Venda
                            </button>
                        </div>

                        <div class="lista-itens">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Qtd</th>
                                        <th>Preço</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="itensVenda">
                                    <!-- Preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <div class="total-venda">
                            <span>Total:</span>
                            <span id="totalVenda">R$ 0,00</span>
                        </div>
                    </div>

                    <div class="forma-pagamento">
                        <h3>Formas de Pagamento</h3>
                        <div class="pagamentos-container">
                            <!-- Primeiro pagamento (obrigatório) -->
                            <div class="pagamento-item" data-index="0">
                                <h4>Pagamento Principal</h4>
                                <div class="opcoes-pagamento">
                                    <!-- Será preenchido via JavaScript -->
                                </div>
                                <div class="valor-pagamento">
                                    <label for="valorPagamento0">Valor:</label>
                                    <div class="valor-input-container">
                                        <span class="currency-symbol">R$</span>
                                        <input type="number" id="valorPagamento0" class="valor-input" step="0.01"
                                            min="0" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Segundo pagamento (opcional) -->
                            <div class="pagamento-item hidden" data-index="1">
                                <div class="pagamento-header">
                                    <h4>Pagamento Adicional 1</h4>
                                    <button type="button" class="btn-remover-pagamento"
                                        onclick="removerFormaPagamento(1)">×</button>
                                </div>
                                <div class="opcoes-pagamento">
                                    <!-- Preenchido via JavaScript -->
                                </div>
                                <div class="valor-pagamento">
                                    <label for="valorPagamento1">Valor:</label>
                                    <div class="valor-input-container">
                                        <span class="currency-symbol">R$</span>
                                        <input type="number" id="valorPagamento1" class="valor-input" step="0.01"
                                            min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Terceiro pagamento (opcional) -->
                            <div class="pagamento-item hidden" data-index="2">
                                <div class="pagamento-header">
                                    <h4>Pagamento Adicional 2</h4>
                                    <button type="button" class="btn-remover-pagamento"
                                        onclick="removerFormaPagamento(2)">×</button>
                                </div>
                                <div class="opcoes-pagamento">
                                    <!-- Preenchido via JavaScript -->
                                </div>
                                <div class="valor-pagamento">
                                    <label for="valorPagamento2">Valor:</label>
                                    <div class="valor-input-container">
                                        <span class="currency-symbol">R$</span>
                                        <input type="number" id="valorPagamento2" class="valor-input" step="0.01"
                                            min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn-adicionar-pagamento" onclick="adicionarFormaPagamento()">
                            + Adicionar Forma de Pagamento
                        </button>

                        <div class="resumo-pagamentos">
                            <div class="valor-item">
                                <span>Total da Venda:</span>
                                <span id="totalVendaResumo">R$ 0,00</span>
                            </div>
                            <div class="valor-item">
                                <span>Total Pago:</span>
                                <span id="totalPago">R$ 0,00</span>
                            </div>
                            <div class="valor-item">
                                <span>Restante:</span>
                                <span id="valorRestante">R$ 0,00</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-cancelar" onclick="cancelarVenda()">Cancelar Venda</button>
                    <button type="submit" class="btn-finalizar">Finalizar Venda</button>
                </form>
            </div>

            <!-- Painel Direito - Resumo e Histórico -->
            <div class="painel-resumo">
                <!-- Resumo do Dia -->
                <div class="card-resumo">
                    <h3>Resumo do Dia</h3>
                    <div class="resumo-info">
                        <div class="info-item">
                            <span>Total de Vendas:</span>
                            <span id="totalVendas">0</span>
                        </div>
                        <div class="info-item">
                            <span>Valor Total:</span>
                            <span id="valorTotal">R$ 0,00</span>
                        </div>
                        <div class="info-item">
                            <span>Formas de Pagamento:</span>
                            <div id="formasPagamento">
                                <!-- Preenchido via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Histórico de Vendas -->
                <div class="historico-vendas">
                    <h3>Histórico de Vendas</h3>
                    <div class="filtros-historico">
                        <div class="periodo">
                            <label for="dataInicio">De:</label>
                            <input type="date" id="dataInicio">
                            <label for="dataFim">Até:</label>
                            <input type="date" id="dataFim">
                        </div>
                        <button onclick="aplicarFiltros()">Filtrar</button>
                    </div>
                    <div class="lista-vendas">
                        <!-- Preenchido via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Confirmação -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-content">
            <h3>Venda Realizada com Sucesso!</h3>
            <div class="resumo-venda">
                <p>Valor Total: <span id="modalValorTotal"></span></p>
                <p>Forma de Pagamento: <span id="modalFormaPagamento"></span></p>
            </div>
            <button onclick="fecharModal()">OK</button>
        </div>
    </div>

    <script src="../assets/js/vendas.js?v=<?php echo time(); ?>"></script>
</body>

</html>