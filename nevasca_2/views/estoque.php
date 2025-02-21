<?php
require_once '../config/auth.php';
require_once '../controllers/EstoqueController.php';
$controller = new EstoqueController();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nevasca - Estoque</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time();?>">
    <link rel="stylesheet" href="../assets/css/estoque.css?v=<?php echo time();?>">
</head>
<body>
    <?php include_once 'components/header.php'; ?>
    <script src="../assets/js/notifications.js"></script>

    <main class="container">
        <h1>Gerenciamento de Estoque</h1>

        <div class="filtros">
            <select id="filtroTipo">
                <option value="">Todos os tipos</option>
                <option value="Sorvete">Sorvete</option>
                <option value="Açaí">Açaí</option>
                <option value="Picolé">Picolé</option>
                <option value="Adicionais">Adicionais</option>
            </select>

            <select id="filtroTamanho">
                <option value="">Todos os tamanhos</option>
                <option value="2L">2L</option>
                <option value="5L">5L</option>
                <option value="10L">10L</option>
                <option value="NA">NA</option>
            </select>

            <button onclick="aplicarFiltros()">Aplicar Filtros</button>
            <button class="btn-adicionar" onclick="abrirModalAdicionar()">Adicionar Produto</button>
        </div>

        <div class="tabela-container">
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nome</th>
                        <th>Tamanho</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Quantidade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabelaProdutos">
                    <!-- Preenchido via JavaScript -->
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Adicionar/Editar Produto -->
    <div id="modalProduto" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitulo">Adicionar Produto</h2>
            <form id="formProduto">
                <input type="hidden" id="produtoId">
                
                <div class="form-group">
                    <label for="tipo">Tipo:</label>
                    <select id="tipo" required onchange="atualizarCampos()">
                        <option value="Sorvete">Sorvete</option>
                        <option value="Açaí">Açaí</option>
                        <option value="Picolé">Picolé</option>
                        <option value="Adicionais">Adicionais</option>
                    </select>
                </div>

                <div class="form-group" id="campoNome" style="display: none;">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome">
                </div>

                <div class="form-group">
                    <label for="tamanho">Tamanho:</label>
                    <select id="tamanho" required>
                        <option value="2L">2L</option>
                        <option value="5L">5L</option>
                        <option value="10L">10L</option>
                        <option value="NA">NA</option>
                    </select>
                </div>

                <div class="form-group" id="campoCategoria" style="display: none;">
                    <label for="categoria">Categoria:</label>
                    <select id="categoria">
                        <option value="Comum">Comum</option>
                        <option value="Premium">Premium</option>
                        <option value="Recheado">Recheado</option>
                        <option value="Paleta">Paleta</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="preco">Preço:</label>
                    <input type="number" id="preco" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" required>
                </div>

                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/estoque.js?v=<?php echo time(); ?>"></script>
</body>
</html>