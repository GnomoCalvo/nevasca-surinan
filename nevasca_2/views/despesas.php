<?php
require_once '../config/auth.php';
require_once '../controllers/DespesasController.php';
$controller = new DespesasController();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nevasca - Despesas</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time();?>">
    <link rel="stylesheet" href="../assets/css/despesas.css?v=<?php echo time();?>">
</head>

<body>
    <?php include_once 'components/header.php'; ?>
    <script src="../assets/js/notifications.js"></script>

    <main class="container">
        <h1>Gerenciamento de Despesas</h1>

        <div class="filtros">
            <div class="periodo">
                <label for="dataInicio">De:</label>
                <input type="date" id="dataInicio">
                <label for="dataFim">Até:</label>
                <input type="date" id="dataFim">
            </div>
            <select id="tipoGasto">
                <option value="todos">Todos os gastos</option>
                <option value="pedidos">Pedidos</option>
                <option value="despesas">Despesas Gerais</option>
            </select>
            <button onclick="aplicarFiltros()">Aplicar Filtros</button>
            <button class="btn-adicionar"
                onclick="abrirModalPedido().catch(error => console.error('Erro:', error))">Novo Pedido</button>
            <button class="btn-adicionar" onclick="abrirModalDespesa()">Nova Despesa</button>
        </div>

        <div class="lista-gastos">
            <!-- Preenchido via JavaScript -->
        </div>
    </main>

    <!-- Modal Pedido -->
    <div id="modalPedido" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Novo Pedido</h2>
            <form id="formPedido">
                <div class="form-group">
                    <label for="dataVencimento">Data de Vencimento:</label>
                    <input type="date" id="dataVencimento" required>
                </div>

                <div class="itens-pedido">
                    <h3>Itens do Pedido</h3>
                    <div id="listaItensPedido">
                        <!-- Os itens serão adicionados aqui -->
                    </div>
                    <button type="button" onclick="adicionarNovoItemPedido()">+ Adicionar Item</button>
                </div>

                <div class="total-pedido">
                    <strong>Total do Pedido: </strong>
                    <span id="totalPedido">R$ 0,00</span>
                </div>

                <button type="submit">Salvar Pedido</button>
            </form>
        </div>
    </div>

    <template id="templateItemPedido">
        <div class="item-pedido">
            <select class="select-produto" required>
                <option value="">Selecione o produto</option>
            </select>
            <input type="text" class="quantidade" placeholder="Quantidade" required pattern="[0-9]*" inputmode="numeric"
                onkeypress="return event.charCode >= 48 && event.charCode <= 57">
            <input type="text" class="preco" placeholder="Preço Unitário" required pattern="[0-9]*\.?[0-9]+"
                inputmode="decimal"
                onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode === 46">
            <span class="subtotal">R$ 0,00</span>
            <button type="button" class="btn-remover" onclick="removerItem(this)">×</button>
        </div>
    </template>

    <!-- Modal Despesa -->
    <div id="modalDespesa" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Nova Despesa</h2>
            <form id="formDespesa">
                <div class="form-group">
                    <label for="nomeDespesa">Nome da Despesa:</label>
                    <input type="text" id="nomeDespesa" required>
                </div>

                <div class="form-group">
                    <label for="valorDespesa">Valor:</label>
                    <input type="number" id="valorDespesa" step="0.01" min="0.01" required>
                </div>

                <button type="submit">Salvar Despesa</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/despesas.js?v=<?php echo time(); ?>"></script>
</body>

</html>