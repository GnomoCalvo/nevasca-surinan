// Variáveis globais
let produtos = [];
const modal = document.getElementById('modalProduto');
const span = document.getElementsByClassName('close')[0];

// Funções de manipulação do modal
function abrirModalAdicionar() {
    document.getElementById('modalTitulo').textContent = 'Adicionar Produto';
    document.getElementById('formProduto').reset();
    document.getElementById('produtoId').value = '';
    atualizarCampos();
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function abrirModalEditar(id) {
    document.getElementById('modalTitulo').textContent = 'Editar Produto';
    const produto = produtos.find(p => p.id === id);

    if (produto) {
        document.getElementById('produtoId').value = produto.id;
        document.getElementById('tipo').value = produto.tipo;
        document.getElementById('nome').value = produto.nome || '';
        document.getElementById('tamanho').value = produto.tamanho;
        document.getElementById('categoria').value = produto.categoria || 'NA';
        document.getElementById('preco').value = produto.preco;
        document.getElementById('quantidade').value = produto.quantidade;

        atualizarCampos();
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function fecharModal() {
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('formProduto').reset();
}

document.addEventListener('DOMContentLoaded', function() {
    // Botão X para fechar
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            fecharModal();
        });
    });

    // Clique fora do modal
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            fecharModal();
        }
    });

    // Adiciona evento de toque para dispositivos móveis
    window.addEventListener('touchend', function(event) {
        if (event.target === modal) {
            fecharModal();
        }
    });
});

window.onclick = function (event) {
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Função para atualizar campos baseado no tipo
function atualizarCampos() {
    const tipo = document.getElementById('tipo').value;
    const campoNome = document.getElementById('campoNome');
    const campoCategoria = document.getElementById('campoCategoria');

    if (tipo === 'Adicionais') {
        campoNome.style.display = 'block';
        campoCategoria.style.display = 'none';
        document.getElementById('nome').required = true;
    } else if (tipo === 'Picolé') {
        campoNome.style.display = 'none';
        campoCategoria.style.display = 'block';
        document.getElementById('nome').required = false;
    } else {
        campoNome.style.display = 'none';
        campoCategoria.style.display = 'none';
        document.getElementById('nome').required = false;
    }

    // Atualizar tamanhos disponíveis
    atualizarTamanhos();
}

function atualizarTamanhos() {
    const tipo = document.getElementById('tipo').value;
    const tamanhoSelect = document.getElementById('tamanho');
    tamanhoSelect.innerHTML = '';

    if (tipo === 'Sorvete' || tipo === 'Açaí') {
        ['2L', '5L', '10L'].forEach(tam => {
            const option = new Option(tam, tam);
            tamanhoSelect.add(option);
        });
    } else {
        tamanhoSelect.add(new Option('NA', 'NA'));
    }
}

// Funções de CRUD
async function carregarProdutos() {
    try {
        mostrarCarregando();
        const formData = new FormData();
        formData.append('action', 'listar');

        const response = await fetch('../controllers/EstoqueController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        console.log('Resposta do servidor:', result);

        const tbody = document.getElementById('tabelaProdutos');
        if (!tbody) {
            throw new Error('Elemento tbody não encontrado');
        }

        tbody.innerHTML = ''; // Limpa a tabela atual

        if (result.success) {
            produtos = result.data; // Armazena os produtos globalmente
            if (Array.isArray(result.data) && result.data.length > 0) {
                result.data.forEach(produto => {
                    const tr = document.createElement('tr');

                    // Primeiro, crie o conteúdo básico da linha
                    tr.innerHTML = `
                        <td>${produto.tipo || ''}</td>
                        <td>${produto.nome || ''}</td>
                        <td>${produto.tamanho || ''}</td>
                        <td>${produto.categoria || 'N/A'}</td>
                        <td>R$ ${parseFloat(produto.preco || 0).toFixed(2)}</td>
                        <td>${produto.quantidade || 0}</td>
                        <td></td>
                    `;

                    // Depois, crie os botões separadamente
                    const tdAcoes = tr.querySelector('td:last-child');

                    // Botão Editar
                    const btnEditar = document.createElement('button');
                    btnEditar.className = 'btn-editar';
                    btnEditar.textContent = 'Editar';
                    btnEditar.type = 'button'; // Importante para evitar submit acidental
                    btnEditar.addEventListener('click', function () {
                        abrirModalEditar(produto.id);
                    });

                    // Botão Excluir
                    const btnExcluir = document.createElement('button');
                    btnExcluir.className = 'btn-excluir';
                    btnExcluir.textContent = 'Excluir';
                    btnExcluir.type = 'button'; // Importante para evitar submit acidental
                    btnExcluir.addEventListener('click', function () {
                        deletarProduto(produto.id);
                    });

                    // Adiciona os botões à célula de ações
                    tdAcoes.appendChild(btnEditar);
                    tdAcoes.appendChild(btnExcluir);

                    tbody.appendChild(tr);
                });
            } else {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td colspan="7" class="no-data">
                        Nenhum produto cadastrado no estoque
                    </td>
                `;
                tbody.appendChild(tr);
            }
        } else {
            throw new Error(result.message || 'Erro ao carregar produtos');
        }
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
        const tbody = document.getElementById('tabelaProdutos');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="error-message">
                        Erro ao carregar produtos. Por favor, tente novamente.
                    </td>
                </tr>
            `;
        }
    } finally {
        mostrarCarregando(false);
    }
}

// Garante que todas as funções necessárias estejam no escopo global
window.abrirModalEditar = abrirModalEditar;
window.deletarProduto = deletarProduto;
window.abrirModalAdicionar = abrirModalAdicionar;
window.aplicarFiltros = aplicarFiltros;

document.addEventListener('DOMContentLoaded', function () {
    // Carregar produtos inicialmente
    carregarProdutos();

    // Adicionar event listener para o formulário
    const form = document.getElementById('formProduto');
    if (form) {
        form.addEventListener('submit', salvarProduto);
    }

    // Event listeners para campos numéricos
    const precoInput = document.getElementById('preco');
    if (precoInput) {
        precoInput.addEventListener('input', function (e) {
            if (this.value < 0) this.value = 0;
        });
    }

    const quantidadeInput = document.getElementById('quantidade');
    if (quantidadeInput) {
        quantidadeInput.addEventListener('input', function (e) {
            if (this.value < 0) this.value = 0;
            this.value = Math.floor(this.value);
        });
    }

    // Event listener para o tipo
    const tipoSelect = document.getElementById('tipo');
    if (tipoSelect) {
        tipoSelect.addEventListener('change', atualizarCampos);
    }
});

function mostrarCarregando(show = true) {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = show ? 'flex' : 'none';
    }
}

async function salvarProduto(event) {
    event.preventDefault();

    if (!validarFormulario()) {
        return;
    }

    const formData = new FormData();
    const id = document.getElementById('produtoId').value;
    const action = id ? 'atualizar' : 'criar';

    if (!confirm(`Deseja realmente ${action === 'criar' ? 'adicionar' : 'atualizar'} este produto?`)) {
        return;
    }

    formData.append('action', id ? 'atualizar' : 'criar');
    if (id) formData.append('id', id);
    formData.append('tipo', document.getElementById('tipo').value);
    formData.append('nome', document.getElementById('nome').value);
    formData.append('tamanho', document.getElementById('tamanho').value);
    formData.append('categoria', document.getElementById('categoria').value);
    formData.append('preco', document.getElementById('preco').value);
    formData.append('quantidade', document.getElementById('quantidade').value);

    try {
        const response = await fetch('../controllers/EstoqueController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            modal.style.display = 'none';
            carregarProdutos();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Erro ao salvar produto:', error);
        alert('Erro ao salvar produto');
    }
}

function validarFormulario() {
    const tipo = document.getElementById('tipo').value;
    const nome = document.getElementById('nome');
    const categoria = document.getElementById('categoria');
    const preco = document.getElementById('preco');
    const quantidade = document.getElementById('quantidade');

    if (tipo === 'Adicionais' && !nome.value.trim()) {
        alert('O campo Nome é obrigatório para Adicionais');
        return false;
    }

    if (tipo === 'Picolé' && categoria.value === 'NA') {
        alert('Selecione uma categoria válida para Picolé');
        return false;
    }

    if (parseFloat(preco.value) < 0) {
        alert('O preço não pode ser negativo');
        return false;
    }

    if (parseInt(quantidade.value) < 0) {
        alert('A quantidade não pode ser negativa');
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('preco').addEventListener('input', function (e) {
        if (this.value < 0) this.value = 0;
    });

    document.getElementById('quantidade').addEventListener('input', function (e) {
        if (this.value < 0) this.value = 0;
        this.value = Math.floor(this.value); // Apenas números inteiros
    });

    // Atualizar campos quando o tipo mudar
    document.getElementById('tipo').addEventListener('change', atualizarCampos);
});

async function deletarProduto(id) {
    if (!confirm('Tem certeza que deseja deletar este produto?')) return;

    const formData = new FormData();
    formData.append('action', 'deletar');
    formData.append('id', id);

    try {
        const response = await fetch('../controllers/EstoqueController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            carregarProdutos();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Erro ao deletar produto:', error);
        alert('Erro ao deletar produto');
    }
}

async function aplicarFiltros() {
    try {
        mostrarCarregando();
        const formData = new FormData();
        formData.append('action', 'listar');

        // Adicionar os valores dos filtros
        const filtroTipo = document.getElementById('filtroTipo').value;
        const filtroTamanho = document.getElementById('filtroTamanho').value;

        formData.append('filtroTipo', filtroTipo);
        formData.append('filtroTamanho', filtroTamanho);

        const response = await fetch('../controllers/EstoqueController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        console.log('Resposta do servidor:', result);

        atualizarTabela(result);
    } catch (error) {
        console.error('Erro ao aplicar filtros:', error);
        alert('Erro ao aplicar filtros');
    } finally {
        mostrarCarregando(false);
    }
}

function atualizarTabela(result) {
    const tbody = document.getElementById('tabelaProdutos');
    if (!tbody) {
        throw new Error('Elemento tbody não encontrado');
    }

    tbody.innerHTML = '';

    if (result.success) {
        produtos = result.data;
        if (Array.isArray(result.data) && result.data.length > 0) {
            result.data.forEach(produto => {
                const tr = document.createElement('tr');

                // Primeiro, crie o conteúdo básico da linha
                tr.innerHTML = `
                    <td>${produto.tipo || ''}</td>
                    <td>${produto.nome || ''}</td>
                    <td>${produto.tamanho || ''}</td>
                    <td>${produto.categoria || 'N/A'}</td>
                    <td>R$ ${parseFloat(produto.preco || 0).toFixed(2)}</td>
                    <td>${produto.quantidade || 0}</td>
                    <td></td>
                `;

                // Depois, crie os botões separadamente
                const tdAcoes = tr.querySelector('td:last-child');

                // Botão Editar
                const btnEditar = document.createElement('button');
                btnEditar.className = 'btn-editar';
                btnEditar.textContent = 'Editar';
                btnEditar.type = 'button';
                btnEditar.addEventListener('click', function () {
                    abrirModalEditar(produto.id);
                });

                // Botão Excluir
                const btnExcluir = document.createElement('button');
                btnExcluir.className = 'btn-excluir';
                btnExcluir.textContent = 'Excluir';
                btnExcluir.type = 'button';
                btnExcluir.addEventListener('click', function () {
                    deletarProduto(produto.id);
                });

                // Adiciona os botões à célula de ações
                tdAcoes.appendChild(btnEditar);
                tdAcoes.appendChild(btnExcluir);

                tbody.appendChild(tr);
            });
        } else {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td colspan="7" class="no-data">
                    Nenhum produto encontrado com os filtros selecionados
                </td>
            `;
            tbody.appendChild(tr);
        }
    } else {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td colspan="7" class="error-message">
                Erro ao carregar produtos. Por favor, tente novamente.
            </td>
        `;
        tbody.appendChild(tr);
    }
}

// Event Listeners
document.getElementById('formProduto').addEventListener('submit', salvarProduto);

// Carregar produtos ao iniciar a página
document.addEventListener('DOMContentLoaded', carregarProdutos);