// Variáveis globais
let listaProdutos = [];
let itensPedido = [];
const modalPedido = document.getElementById('modalPedido');
const modalDespesa = document.getElementById('modalDespesa');
const spans = document.getElementsByClassName('close');

// Event Listeners para fechar modais
Array.from(spans).forEach(span => {
    span.onclick = function () {
        modalPedido.style.display = 'none';
        modalDespesa.style.display = 'none';
    }
});

window.onclick = function (event) {
    if (event.target == modalPedido || event.target == modalDespesa) {
        modalPedido.style.display = 'none';
        modalDespesa.style.display = 'none';
    }
}

// Funções de manipulação dos modais
async function abrirModalPedido() {
    try {
        await carregarProdutos(); // Aguardar o carregamento dos produtos
        document.getElementById('formPedido').reset();
        document.getElementById('listaItensPedido').innerHTML = '';
        itensPedido = [];
        adicionarNovoItemPedido();
        atualizarTotalPedido();
        modalPedido.style.display = 'block';
    } catch (error) {
        console.error('Erro ao abrir modal de pedido:', error);
        alert('Erro ao carregar produtos disponíveis');
    }
}

function adicionarNovoItemPedido() {
    const template = document.getElementById('templateItemPedido');
    if (!template) {
        console.error('Template não encontrado');
        return;
    }

    const clone = template.content.cloneNode(true);
    const select = clone.querySelector('.select-produto');

    // Preencher o select com os produtos da lista
    listaProdutos.forEach(produto => {
        const option = document.createElement('option');
        option.value = JSON.stringify({
            nome: produto.nome,
            tamanho: produto.tamanho
        });
        option.textContent = `${produto.nome} - ${produto.tamanho}`;
        select.appendChild(option);
    });

    // Adicionar event listeners
    clone.querySelector('.quantidade').addEventListener('input', atualizarSubtotal);
    clone.querySelector('.preco').addEventListener('input', atualizarSubtotal);

    document.getElementById('listaItensPedido').appendChild(clone);
}

function abrirModalDespesa() {
    document.getElementById('formDespesa').reset();
    modalDespesa.style.display = 'block';
}

// Funções de manipulação de itens do pedido
function adicionarItem(produto) {
    const itemExistente = itensVenda.find(item =>
        item.produto_id === produto.id);

    const quantidadeAtual = itemExistente ? itemExistente.quantidade : 0;
    const quantidadeTotal = quantidadeAtual + 1;

    if (quantidadeTotal > produto.quantidade) {
        alert(`Quantidade máxima disponível: ${produto.quantidade}`);
        return;
    }

    if (itemExistente) {
        itemExistente.quantidade = quantidadeTotal;
    } else {
        itensVenda.push({
            produto_id: produto.id,
            nome: produto.nome,
            quantidade: 1,
            preco_unitario: produto.preco,
            estoque_disponivel: produto.quantidade
        });
    }

    atualizarTabelaItens();
}

function removerItem(button) {
    button.closest('.item-pedido').remove();
    atualizarTotalPedido();
}

function atualizarSubtotal(event) {
    const itemPedido = event.target.closest('.item-pedido');
    const quantidade = parseFloat(itemPedido.querySelector('.quantidade').value) || 0;
    const preco = parseFloat(itemPedido.querySelector('.preco').value) || 0;
    const subtotal = quantidade * preco;

    itemPedido.querySelector('.subtotal').textContent = formatarMoeda(subtotal);
    atualizarTotalPedido();
}

function validarValores(form) {
    const valores = form.querySelectorAll('input[type="number"]');
    let valido = true;

    valores.forEach(input => {
        const valor = parseFloat(input.value);
        if (valor <= 0) {
            alert('Os valores precisam ser maiores que zero');
            valido = false;
        }
    });

    return valido;
}

function atualizarTotalPedido() {
    const subtotais = document.querySelectorAll('.subtotal');
    let total = 0;

    subtotais.forEach(subtotal => {
        const valor = parseFloat(subtotal.textContent.replace('R$', '').replace('.', '').replace(',', '.').trim()) || 0;
        total += valor;
    });

    document.getElementById('totalPedido').textContent = formatarMoeda(total);
}

function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

// Funções de CRUD
async function carregarProdutos() {
    const formData = new FormData();
    formData.append('action', 'lista_produtos');

    try {
        const response = await fetch('../controllers/DespesasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            listaProdutos = result.data;
        } else {
            throw new Error(result.message || 'Erro ao carregar produtos');
        }
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
        throw error;
    }
}

function validarDatas() {
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;
    const dataVencimento = document.getElementById('dataVencimento')?.value;

    if (new Date(dataFim) < new Date(dataInicio)) {
        alert('Data final não pode ser menor que a data inicial');
        return false;
    }

    if (dataVencimento && new Date(dataVencimento) < new Date()) {
        alert('Data de vencimento não pode ser menor que a data atual');
        return false;
    }

    return true;
}

async function carregarGastos() {
    mostrarCarregando();
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;
    const tipoGasto = document.getElementById('tipoGasto').value;

    try {
        let gastos = [];

        if (tipoGasto === 'todos' || tipoGasto === 'pedidos') {
            const pedidosResponse = await fetch('../controllers/DespesasController.php', {
                method: 'POST',
                body: new URLSearchParams({
                    'action': 'listar_pedidos',
                    'data_inicio': dataInicio,
                    'data_fim': dataFim
                })
            });
            const pedidosResult = await pedidosResponse.json();
            if (pedidosResult.success) {
                gastos = gastos.concat(pedidosResult.data.map(pedido => ({
                    ...pedido,
                    tipo: 'pedido'
                })));
            }
        }

        if (tipoGasto === 'todos' || tipoGasto === 'despesas') {
            const despesasResponse = await fetch('../controllers/DespesasController.php', {
                method: 'POST',
                body: new URLSearchParams({
                    'action': 'listar_despesas',
                    'data_inicio': dataInicio,
                    'data_fim': dataFim
                })
            });
            const despesasResult = await despesasResponse.json();
            if (despesasResult.success) {
                gastos = gastos.concat(despesasResult.data.map(despesa => ({
                    ...despesa,
                    tipo: 'despesa'
                })));
            }
        }

        // Ordenar por data
        gastos.sort((a, b) => new Date(b.data_pedido || b.data_despesa) - new Date(a.data_pedido || a.data_despesa));

        atualizarListaGastos(gastos);
    } catch (error) {
        console.error('Erro ao carregar gastos:', error);
        alert('Erro ao carregar gastos');
    } finally {
        mostrarCarregando(false);
    }
}

const style = document.createElement('style');
style.textContent = `
.loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
`;
document.head.appendChild(style);

async function carregarPedidos(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'listar_pedidos');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    const response = await fetch('../controllers/DespesasController.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();
    return result.success ? result.data.map(pedido => ({
        ...pedido,
        tipo: 'pedido',
        data: pedido.data_pedido
    })) : [];
}

async function carregarDespesas(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'listar_despesas');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    const response = await fetch('../controllers/DespesasController.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();
    return result.success ? result.data.map(despesa => ({
        ...despesa,
        tipo: 'despesa',
        data: despesa.data_despesa
    })) : [];
}

function atualizarListaGastos(gastos) {
    const container = document.querySelector('.lista-gastos');
    container.innerHTML = '';

    gastos.forEach(gasto => {
        const elemento = document.createElement('div');
        elemento.className = 'gasto-item';

        const isPedido = gasto.tipo === 'pedido' || gasto.data_pedido;
        const data = isPedido ? gasto.data_pedido : gasto.data_despesa;
        const dataFormatada = new Date(data.split(' ')[0] + 'T00:00:00').toLocaleDateString('pt-BR');

        const vencimentoClass = isPedido &&
            new Date(gasto.data_vencimento) <= new Date(Date.now() + 5 * 24 * 60 * 60 * 1000)
            ? 'vencimento-proximo'
            : '';

        const statusButton = isPedido ? `
            <button class="btn-status status-${gasto.status || 'pendente'}" 
                    onclick="atualizarStatusPedido(${gasto.id}, '${gasto.status || 'pendente'}')">
                ${gasto.status || 'pendente'}
            </button>
        ` : '';

        elemento.innerHTML = `
            <div class="gasto-header">
                <span class="gasto-tipo">${isPedido ? 'Pedido' : 'Despesa Geral'}</span>
                <span class="gasto-data">${dataFormatada}</span>
                <span class="gasto-valor">R$ ${parseFloat(isPedido ? gasto.valor_total : gasto.valor).toFixed(2)}</span>
                ${isPedido ? `
                    <span class="gasto-data ${vencimentoClass}">
                        Vencimento: ${new Date(gasto.data_vencimento + 'T00:00:00').toLocaleDateString('pt-BR')}
                    </span>
                    ${statusButton}
                ` : ''}
                <button class="btn-expandir" onclick="toggleDetalhes(this)">▼</button>
                <button class="btn-deletar" onclick="deletarGasto('${isPedido ? 'pedido' : 'despesa'}', ${gasto.id})">×</button>
            </div>
            <div class="gasto-detalhes" style="display: none;">
                ${isPedido ? `
                    <h4>Itens do Pedido:</h4>
                    ${gasto.itens.map(item => `
                        <div class="item-lista">
                            <span>${item.nome} - ${item.tamanho}</span>
                            <span>${item.quantidade} x R$ ${parseFloat(item.preco_unitario).toFixed(2)}</span>
                        </div>
                    `).join('')}
                ` : `
                    <div class="item-lista">
                        <span>Descrição: ${gasto.nome}</span>
                    </div>
                `}
            </div>
        `;

        container.appendChild(elemento);
    });
}

function toggleDetalhes(button) {
    const detalhes = button.closest('.gasto-item').querySelector('.gasto-detalhes');
    const estaVisivel = detalhes.style.display === 'block';
    detalhes.style.display = estaVisivel ? 'none' : 'block';
    button.textContent = estaVisivel ? '▼' : '▲';
}

async function deletarGasto(tipo, id) {
    const tipoGasto = tipo === 'pedido' ? 'pedido' : 'despesa';
    const mensagem = `Tem certeza que deseja deletar este ${tipoGasto}?\nEsta ação não pode ser desfeita.`;

    if (!confirm(mensagem)) return;

    try {
        const formData = new FormData();
        formData.append('action', `deletar_${tipoGasto}`);
        formData.append('id', id);

        const response = await fetch('../controllers/DespesasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            carregarGastos();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error(`Erro ao deletar ${tipoGasto}:`, error);
        alert(`Erro ao deletar ${tipoGasto}: ${error.message}`);
    }
}

// Event Listeners para formulários
document.getElementById('formPedido').addEventListener('submit', async function (e) {
    e.preventDefault();
    if (!validarValores(this) || !validarDatas()) return;

    const dataVencimento = document.getElementById('dataVencimento').value;
    const hoje = new Date();
    const vencimento = new Date(dataVencimento);

    if (vencimento < hoje) {
        alert('A data de vencimento não pode ser anterior à data atual');
        return;
    }

    const itens = [];
    document.querySelectorAll('.item-pedido').forEach(item => {
        const produtoSelect = item.querySelector('.select-produto');
        if (produtoSelect.value) {
            const produto = JSON.parse(produtoSelect.value);
            itens.push({
                nome: produto.nome,
                tamanho: produto.tamanho,
                quantidade: parseInt(item.querySelector('.quantidade').value),
                preco_unitario: parseFloat(item.querySelector('.preco').value)
            });
        }
    });

    if (itens.length === 0) {
        alert('Adicione pelo menos um item ao pedido');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'criar_pedido');
    formData.append('data_vencimento', dataVencimento);
    formData.append('itens', JSON.stringify(itens));

    try {
        const response = await fetch('../controllers/DespesasController.php', {
            method: 'POST',
            body: formData
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Resposta do servidor não é JSON válido');
        }

        const result = await response.json();
        if (result.success) {
            alert(result.message);
            modalPedido.style.display = 'none';
            carregarGastos();
        } else {
            throw new Error(result.message || 'Erro ao salvar pedido');
        }
    } catch (error) {
        console.error('Erro ao salvar pedido:', error);
        alert('Erro ao salvar pedido: ' + error.message);
    }
});

document.getElementById('formDespesa').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData();
    formData.append('action', 'criar_despesa');
    formData.append('nome', document.getElementById('nomeDespesa').value);
    formData.append('valor', document.getElementById('valorDespesa').value);

    try {
        const response = await fetch('../controllers/DespesasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            alert(result.message);
            modalDespesa.style.display = 'none';
            carregarGastos();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Erro ao salvar despesa:', error);
        alert('Erro ao salvar despesa');
    }
});

function aplicarFiltros() {
    if (validarDatas()) {
        carregarGastos();
    }
}

// Carregar dados iniciais
document.addEventListener('DOMContentLoaded', function () {
    const hoje = new Date();
    const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);

    document.getElementById('dataInicio').value = inicioMes.toISOString().split('T')[0];
    document.getElementById('dataFim').value = hoje.toISOString().split('T')[0];

    carregarGastos();
});

async function exportarRelatorio() {
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;
    const tipoGasto = document.getElementById('tipoGasto').value;

    const formData = new FormData();
    formData.append('action', 'exportar_relatorio');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);
    formData.append('tipo', tipoGasto);

    try {
        const response = await fetch('../controllers/DespesasController.php', {
            method: 'POST',
            body: formData
        });

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `relatorio_${tipoGasto}_${dataInicio}_${dataFim}.csv`;
        document.body.appendChild(a);
        a.click();
        a.remove();
    } catch (error) {
        console.error('Erro ao exportar relatório:', error);
        alert('Erro ao exportar relatório');
    }
}

function tratarErro(erro, operacao) {
    console.error(`Erro ao ${operacao}:`, erro);

    let mensagem = `Erro ao ${operacao}. `;
    if (erro.message) {
        mensagem += erro.message;
    } else {
        mensagem += 'Por favor, tente novamente.';
    }

    alert(mensagem);
}

function mostrarCarregando(mostrar = true) {
    const loading = document.getElementById('loading') ||
        (() => {
            const div = document.createElement('div');
            div.id = 'loading';
            div.className = 'loading';
            div.textContent = 'Carregando...';
            document.body.appendChild(div);
            return div;
        })();

    loading.style.display = mostrar ? 'flex' : 'none';
}

async function atualizarStatusPedido(id, statusAtual) {
    const novoStatus = statusAtual === 'pendente' ? 'pago' : 'pendente';
    
    if (!confirm(`Deseja marcar este pedido como ${novoStatus}?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'atualizar_status_pedido');
    formData.append('id', id);
    formData.append('status', novoStatus);

    try {
        const response = await fetch('../controllers/DespesasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            alert(result.message);
            carregarGastos(); // Recarrega a lista de gastos
            
            // Atualizar notificações se disponível
            if (window.atualizarNotificacoesAposPagamento) {
                window.atualizarNotificacoesAposPagamento();
            }
        } else {
            throw new Error(result.message || 'Erro ao atualizar status');
        }
    } catch (error) {
        console.error('Erro ao atualizar status:', error);
        alert('Erro ao atualizar status do pedido');
    }
}