<?php
require_once '../config/auth.php';
require_once '../controllers/CardapioController.php';
$controller = new CardapioController();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nevasca - Cardápio</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/cardapio.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include_once 'components/header.php'; ?>
    <script src="../assets/js/notifications.js"></script>

    <main class="container">
        <div class="header-cardapio">
            <h1>Gerenciar Cardápio</h1>
            <div class="header-actions">
                <button onclick="gerarCardapioPDF()" class="btn-gerar-cardapio">
                    <i class="fas fa-file-pdf"></i>
                    Gerar Cardápio
                </button>
                <button onclick="abrirModalAdicao()" class="btn-adicionar">Adicionar Item</button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <div class="filtro-tipo">
                <label for="filtroTipo">Filtrar por tipo:</label>
                <select id="filtroTipo" onchange="aplicarFiltros()">
                    <option value="">Todos</option>
                    <option value="Sorvete">Sorvetes</option>
                    <option value="Açaí">Açaís</option>
                    <option value="Picolé">Picolés</option>
                </select>
            </div>
        </div>

        <!-- Grid de Itens -->
        <div class="grid-cardapio">
            <!-- Seção Sorvetes -->
            <div class="secao-cardapio" id="secaoSorvetes">
                <h2>Sorvetes</h2>
                <div class="lista-itens">
                    <!-- Preenchido via JavaScript -->
                </div>
            </div>

            <!-- Seção Açaís -->
            <div class="secao-cardapio" id="secaoAcais">
                <h2>Açaís</h2>
                <div class="lista-itens">
                    <!-- Preenchido via JavaScript -->
                </div>
            </div>

            <!-- Seção Picolés -->
            <div class="secao-cardapio" id="secaoPicoles">
                <h2>Picolés</h2>
                <div class="lista-itens">
                    <!-- Preenchido via JavaScript -->
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Adição/Edição -->
    <div id="modalItem" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitulo">Adicionar Item ao Cardápio</h2>

            <form id="formItem" onsubmit="salvarItem(event)">
                <input type="hidden" id="itemId">

                <div class="form-group">
                    <label for="tipo">Tipo:</label>
                    <select id="tipo" required onchange="atualizarTamanhos()">
                        <option value="">Selecione...</option>
                        <option value="Sorvete">Sorvete</option>
                        <option value="Açaí">Açaí</option>
                        <option value="Picolé">Picolé</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sabor">Sabor:</label>
                    <input type="text" id="sabor" required>
                </div>

                <div class="form-group">
                    <label for="tamanho">Tamanho:</label>
                    <select id="tamanho" required>
                        <option value="">Selecione o tipo primeiro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="preco">Preço:</label>
                    <input type="number" id="preco" step="0.01" min="0.01" required>
                </div>

                <div class="form-group checkbox">
                    <label>
                        <input type="checkbox" id="disponivel" checked>
                        Disponível
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-salvar">Salvar</button>
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-content">
            <h3>Confirmar Exclusão</h3>
            <p>Tem certeza que deseja remover este item do cardápio?</p>
            <div class="form-actions">
                <button onclick="confirmarExclusao()" class="btn-confirmar">Confirmar</button>
                <button onclick="fecharModalConfirmacao()" class="btn-cancelar">Cancelar</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/cardapio.js?v=<?php echo time(); ?>"></script>
</body>

</html>