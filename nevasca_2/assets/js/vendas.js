// Variáveis globais
let produtosDisponiveis = [];
let itensVenda = [];
let timeoutBusca;
let formasPagamentoDisponiveis = [];
let pagamentosAtivos = 1;

function debugRadioButtons() {
    const radios = document.querySelectorAll('input[type="radio"]');
    console.log('Total de radio buttons:', radios.length);
    radios.forEach((radio, index) => {
        console.log(`Radio ${index}:`, {
            name: radio.name,
            value: radio.value,
            checked: radio.checked,
            disabled: radio.disabled,
            visible: radio.offsetParent !== null
        });
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', async function () {
    console.log('Iniciando carregamento de produtos...');
    await carregarProdutos();
    console.log('Produtos carregados:', produtosDisponiveis);
    await carregarFormasPagamento();
    console.log('Formas de pagamento carregadas:', formasPagamentoDisponiveis);


    document.querySelectorAll('.valor-input').forEach(input => {
        input.addEventListener('input', atualizarResumoPagamentos);
    });

    document.querySelector('.pagamentos-container').addEventListener('change', function(e) {
        if (e.target.type === 'radio') {
            console.log('Radio changed:', e.target.value);
            atualizarResumoPagamentos();
        }
    });


    await atualizarResumoDiario();

    const hoje = new Date().toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo' }).split('/').reverse().join('-');
    document.getElementById('dataInicio').value = hoje;
    document.getElementById('dataFim').value = hoje;

    // Carregar histórico com a data atual
    await carregarHistoricoVendas();

    // Adicionar listener para o select de produtos
    document.getElementById('selectProduto').addEventListener('change', function () {
        const selfServiceConfig = document.querySelector('.self-service-config');
        const quantidadeLabel = document.querySelector('label[for="quantidadeProduto"]');
        const quantidadeInput = document.getElementById('quantidadeProduto');

        // Encontrar o produto Self-Service nos produtos disponíveis
        const selfService = produtosDisponiveis.find(p => p.tipo === 'Adicionais' && p.nome === 'Self-Service');

        if (this.value === String(selfService?.id)) {
            selfServiceConfig.style.display = 'block';
            quantidadeLabel.textContent = 'Peso (kg):';
            quantidadeInput.step = '0.001'; // Permite decimais para peso
            quantidadeInput.min = '0.001';  // Mínimo de 1g
        } else {
            selfServiceConfig.style.display = 'none';
            quantidadeLabel.textContent = 'Quantidade:';
            quantidadeInput.step = '1';     // Apenas números inteiros para quantidade
            quantidadeInput.min = '1';
        }
    });
});

document.getElementById('formVenda').addEventListener('reset', function(e) {
    console.log('Formulário sendo resetado');
});

document.getElementById('formVenda').addEventListener('submit', async function(e) {
    e.preventDefault();
    toggleLoading(true);

    try {
        if (itensVenda.length === 0) {
            alert('Adicione pelo menos um item à venda');
            toggleLoading(false);
            return;
        }

        // Validar pagamentos
        const pagamentos = [];
        let totalPago = 0;

        for (let i = 0; i < pagamentosAtivos; i++) {
            const formaPagamento = document.querySelector(`input[name="formaPagamento${i}"]:checked`);
            const valorPagamento = parseFloat(document.getElementById(`valorPagamento${i}`).value || 0);

            if (!formaPagamento) {
                alert(`Selecione uma forma de pagamento para o Pagamento ${i + 1}`);
                toggleLoading(false);
                return;
            }

            if (!valorPagamento || valorPagamento <= 0) {
                alert(`Informe um valor válido para o Pagamento ${i + 1}`);
                toggleLoading(false);
                return;
            }

            pagamentos.push({
                forma: formaPagamento.value,
                valor: valorPagamento
            });
            totalPago += valorPagamento;
        }

        // Validar valor total
        const valorTotal = itensVenda.reduce((total, item) => 
            total + (item.quantidade * item.preco_unitario), 0);

        if (Math.abs(totalPago - valorTotal) > 0.01) {
            alert('O total dos pagamentos deve ser igual ao valor total da venda');
            toggleLoading(false);
            return;
        }

        // Preparar os dados da venda
        const formData = new FormData();
        formData.append('action', 'criar_venda');
        formData.append('pagamentos', JSON.stringify(pagamentos));

        // Garantir que os itens têm a estrutura correta
        const itensFormatados = itensVenda.map(item => ({
            produto_id: item.produto_id,
            quantidade: parseFloat(item.quantidade),
            preco_unitario: parseFloat(item.preco_unitario)
        }));

        formData.append('itens', JSON.stringify(itensFormatados));

        console.log('Enviando venda:', {
            pagamentos: pagamentos,
            itens: itensFormatados,
            valor_total: valorTotal
        });

        const response = await fetch('../controllers/VendasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        console.log('Resposta do servidor:', result);

        if (result.success) {
            await mostrarConfirmacao();
            await limparVenda();
            await atualizarResumoDiario();
            await carregarHistoricoVendas();
            await carregarProdutos();
        } else {
            throw new Error(result.message || 'Erro ao criar venda');
        }
    } catch (error) {
        console.error('Erro ao criar venda:', error);
        alert('Erro ao criar venda: ' + error.message);
    } finally {
        toggleLoading(false);
    }
});

// Funções de manipulação da venda
function adicionarItem(produto) {
    const itemExistente = itensVenda.find(item => item.produto_id === produto.id);

    if (itemExistente) {
        if (itemExistente.quantidade >= produto.quantidade_disponivel) {
            alert('Quantidade máxima disponível em estoque atingida');
            return;
        }
        itemExistente.quantidade++;
    } else {
        itensVenda.push({
            produto_id: produto.id,
            nome: produto.nome,
            quantidade: produto.quantidade,
            preco_unitario: produto.preco_unitario
        });
    }

    atualizarTabelaItens();

    // Correção: Remover a referência ao campo de busca que não existe neste contexto
    const resultadosBusca = document.getElementById('resultadosBusca');
    if (resultadosBusca) {
        resultadosBusca.style.display = 'none';
    }
}

function removerItem(index) {
    itensVenda.splice(index, 1);
    atualizarTabelaItens();
}

function atualizarTabelaItens() {
    const tbody = document.getElementById('itensVenda');
    tbody.innerHTML = '';
    let total = 0;

    itensVenda.forEach((item, index) => {
        const subtotal = item.quantidade * item.preco_unitario;
        total += subtotal;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.nome}</td>
            <td>${item.quantidade}</td>
            <td>R$ ${item.preco_unitario.toFixed(2)}</td>
            <td>R$ ${subtotal.toFixed(2)}</td>
            <td>
                <button type="button" class="btn-remover" onclick="removerItem(${index})">×</button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('totalVenda').textContent = `R$ ${total.toFixed(2)}`;
}

function calcularTroco(valorRecebido) {
    const total = parseFloat(document.getElementById('totalVenda')
        .textContent.replace('R$ ', '').replace(',', '.'));

    if (valorRecebido < total) {
        alert('Valor recebido é menor que o total da venda');
        return false;
    }

    const troco = valorRecebido - total;
    document.getElementById('trocoValor').textContent =
        `R$ ${troco.toFixed(2)}`;
    return true;
}

// Funções de carregamento de dados
async function carregarProdutos() {
    const formData = new FormData();
    formData.append('action', 'produtos_disponiveis');

    try {
        const response = await fetch('../controllers/VendasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            produtosDisponiveis = result.data;
            atualizarSelectProdutos();
        } else {
            console.error('Erro ao carregar produtos:', result.message);
        }
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
    }
}

function atualizarSelectProdutos() {
    const select = document.getElementById('selectProduto');
    select.innerHTML = '<option value="">Selecione um produto...</option>';

    // Encontrar o produto Self-Service nos produtos disponíveis
    const selfService = produtosDisponiveis.find(p => p.tipo === 'Adicionais' && p.nome === 'Self-Service');

    if (selfService) {
        // Adiciona opção de Self-Service com o ID correto
        const selfServiceConfig = document.createElement('optgroup');
        selfServiceConfig.label = 'Self-Service';
        selfServiceConfig.innerHTML = `
            <option value="${selfService.id}" data-preco="0">Self-Service (Preço por Kg)</option>
        `;
        select.appendChild(selfServiceConfig);
    }

    // Agrupa produtos por tipo
    const grupos = {
        'Sorvete': [],
        'Açaí': [],
        'Picolé': [],
        'Adicionais': []
    };

    produtosDisponiveis.forEach(produto => {
        if (produto.tipo !== 'Adicionais' || produto.nome !== 'Self-Service') {
            grupos[produto.tipo].push(produto);
        }
    });

    // Adiciona produtos agrupados
    Object.entries(grupos).forEach(([tipo, produtos]) => {
        if (produtos.length > 0) {
            const grupo = document.createElement('optgroup');
            grupo.label = tipo;

            produtos.forEach(produto => {
                let nomeProduto = '';

                // Formatação específica por tipo
                switch (produto.tipo) {
                    case 'Sorvete':
                    case 'Açaí':
                        nomeProduto = produto.nome || `${produto.tipo} ${produto.tamanho}`;
                        break;
                    case 'Picolé':
                        nomeProduto = produto.nome || `${produto.tipo} ${produto.categoria}`;
                        break;
                    case 'Adicionais':
                        nomeProduto = produto.nome;
                        break;
                }

                const option = document.createElement('option');
                option.value = produto.id;
                option.dataset.preco = produto.preco;
                option.dataset.quantidade = produto.quantidade;
                option.textContent = `${nomeProduto} - R$ ${produto.preco.toFixed(2)}`;

                grupo.appendChild(option);
            });

            if (grupo.children.length > 0) {
                select.appendChild(grupo);
            }
        }
    });
}

function adicionarProdutoSelecionado() {
    const select = document.getElementById('selectProduto');
    const quantidadeInput = document.getElementById('quantidadeProduto');
    const selfService = produtosDisponiveis.find(p => p.tipo === 'Adicionais' && p.nome === 'Self-Service');

    if (!select.value) {
        alert('Selecione um produto');
        return;
    }

    if (select.value === String(selfService?.id)) {
        const peso = parseFloat(quantidadeInput.value);
        const precoKg = parseFloat(document.getElementById('precoSelfService').value);

        if (!peso || !precoKg || peso <= 0 || precoKg <= 0) {
            alert('Informe o peso e o preço por Kg do Self-Service');
            return;
        }

        // Criar item com preço correto
        const item = {
            produto_id: selfService.id,
            nome: 'Self-Service',
            quantidade: peso,
            preco_unitario: precoKg // Usar o preço por Kg informado
        };

        itensVenda.push(item);
        atualizarTabelaItens();

        // Limpar campos após adicionar
        select.value = '';
        quantidadeInput.value = '';
        document.getElementById('precoSelfService').value = '';
        document.querySelector('.self-service-config').style.display = 'none';
        quantidadeInput.step = '1';
        quantidadeLabel.textContent = 'Quantidade:';
        quantidadeInput.required = false; // Remove o required após adicionar item
    } else {
        const option = select.selectedOptions[0];
        const produto = {
            id: select.value,
            nome: option.text.split(' - R$')[0],
            preco: parseFloat(option.dataset.preco),
            quantidade_disponivel: parseInt(option.dataset.quantidade)
        };

        const quantidade = parseInt(quantidadeInput.value);

        if (!quantidade || quantidade <= 0) {
            alert('Informe uma quantidade válida');
            return;
        }

        if (quantidade > produto.quantidade_disponivel) {
            alert('Quantidade indisponível em estoque');
            return;
        }

        // Correção: Estrutura correta do objeto ao chamar adicionarItem
        adicionarItem({
            id: produto.id,
            nome: produto.nome,
            quantidade: quantidade,
            preco_unitario: produto.preco
        });
    }

    // Limpa os campos após adicionar
    select.value = '';
    quantidadeInput.value = '';
}

document.getElementById('selectProduto').addEventListener('change', function () {
    const selfServiceConfig = document.querySelector('.self-service-config');
    const quantidadeLabel = document.querySelector('label[for="quantidadeProduto"]');

    if (this.value === 'self-service') {
        selfServiceConfig.style.display = 'block';
        quantidadeLabel.textContent = 'Peso (kg):';
    } else {
        selfServiceConfig.style.display = 'none';
        quantidadeLabel.textContent = 'Quantidade:';
    }
});

async function carregarFormasPagamento() {
    try {
        const formData = new FormData();
        formData.append('action', 'formas_pagamento');

        const response = await fetch('../controllers/VendasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        console.log('Resposta do servidor (formas de pagamento):', result);

        if (result.success) {
            formasPagamentoDisponiveis = result.data;
            console.log('Formas de pagamento disponíveis:', formasPagamentoDisponiveis);
            atualizarOpcoesFormasPagamento();
        } else {
            console.error('Erro ao carregar formas de pagamento:', result.message);
        }
    } catch (error) {
        console.error('Erro ao carregar formas de pagamento:', error);
    }
}

function atualizarOpcoesFormasPagamento() {
    document.querySelectorAll('.opcoes-pagamento').forEach((container, index) => {
        
        const selecaoAtual = container.querySelector('input[type="radio"]:checked')?.value;
        
        // Obter formas já selecionadas
        const formasJaUsadas = new Set(Array.from(document.querySelectorAll('.opcoes-pagamento'))
            .slice(0, index)
            .map(div => div.querySelector('input[type="radio"]:checked')?.value)
            .filter(Boolean));

        // Gerar HTML com classes e estrutura adequada
        container.innerHTML = `
            <div class="radio-group">
                ${formasPagamentoDisponiveis
                    .filter(forma => !formasJaUsadas.has(forma.id))
                    .map(forma => `
                        <label class="radio-option">
                            <input type="radio" 
                                   name="formaPagamento${index}" 
                                   value="${forma.id}" 
                                   ${index === 0 ? 'required' : ''}>
                            <span class="radio-label">${forma.nome}</span>
                        </label>
                    `).join('')}
            </div>
        `;

        // Adicionar listeners após criar os elementos
        container.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.onclick = function() {
                console.log('Radio clicado:', this.value);
                this.checked = true;
                atualizarOpcoesFormasPagamento();
            };
        });

        if (selecaoAtual) {
            const radioParaSelecionar = container.querySelector(`input[value="${selecaoAtual}"]`);
            if (radioParaSelecionar) {
                radioParaSelecionar.checked = true;
            }
        }
    });
    setTimeout(debugRadioButtons, 100);
}

function adicionarFormaPagamento() {
    if (pagamentosAtivos >= 3) {
        alert('Máximo de 3 formas de pagamento atingido');
        return;
    }

    const proximoPagamento = document.querySelector(`.pagamento-item[data-index="${pagamentosAtivos}"]`);
    proximoPagamento.classList.remove('hidden');
    pagamentosAtivos++;

    if (pagamentosAtivos >= 3) {
        document.querySelector('.btn-adicionar-pagamento').style.display = 'none';
    }

    atualizarOpcoesFormasPagamento();
}

function removerFormaPagamento(index) {
    const pagamento = document.querySelector(`.pagamento-item[data-index="${index}"]`);
    pagamento.classList.add('hidden');
    
    // Limpar seleções e valor
    const radioInputs = pagamento.querySelectorAll('input[type="radio"]');
    radioInputs.forEach(input => input.checked = false);
    pagamento.querySelector('.valor-input').value = '';

    pagamentosAtivos--;
    document.querySelector('.btn-adicionar-pagamento').style.display = 'block';
    
    atualizarOpcoesFormasPagamento();
    atualizarResumoPagamentos();
}

function atualizarResumoPagamentos() {
    const totalVenda = parseFloat(document.getElementById('totalVenda').textContent.replace('R$ ', '').replace(',', '.'));
    let totalPago = 0;

    // Somar valores de todos os pagamentos ativos
    document.querySelectorAll('.pagamento-item:not(.hidden) .valor-input').forEach(input => {
        totalPago += parseFloat(input.value || 0);
    });

    document.getElementById('totalVendaResumo').textContent = `R$ ${totalVenda.toFixed(2)}`;
    document.getElementById('totalPago').textContent = `R$ ${totalPago.toFixed(2)}`;
    document.getElementById('valorRestante').textContent = `R$ ${(totalVenda - totalPago).toFixed(2)}`;
}

async function carregarHistoricoVendas() {
    const formData = new FormData();
    formData.append('action', 'listar_vendas');
    formData.append('data_inicio', document.getElementById('dataInicio').value);
    formData.append('data_fim', document.getElementById('dataFim').value);

    try {
        const response = await fetch('../controllers/VendasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            const container = document.querySelector('.lista-vendas');
            container.innerHTML = '';

            result.data.forEach(venda => {
                const div = document.createElement('div');
                div.className = 'venda-item';
                
                // Formatar as formas de pagamento
                const formasPagamento = venda.pagamentos
                    .map(p => `${p.forma}: R$ ${parseFloat(p.valor).toFixed(2)}`)
                    .join(', ');

                div.innerHTML = `
                    <div class="venda-header">
                        <span>Data: ${new Date(venda.data_venda).toLocaleString()}</span>
                        <span>Total: R$ ${parseFloat(venda.valor_total).toFixed(2)}</span>
                        <span>Pagamento: ${formasPagamento}</span>
                        <button type="button" onclick="toggleDetalhesVenda(this)">▼</button>
                        <button type="button" class="btn-cancelar-venda" onclick="cancelarVendaHistorico(${venda.id})">
                            Cancelar Venda
                        </button>
                    </div>
                    <div class="venda-detalhes">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Qtd</th>
                                    <th>Preço</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${venda.itens.map(item => `
                                    <tr>
                                        <td>${item.nome}</td>
                                        <td>${item.quantidade}</td>
                                        <td>R$ ${parseFloat(item.preco_unitario).toFixed(2)}</td>
                                        <td>R$ ${(item.quantidade * item.preco_unitario).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                container.appendChild(div);
            });
        }
    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
    }
}

async function atualizarResumoDiario() {
    const formData = new FormData();
    formData.append('action', 'resumo_diario');

    try {
        const response = await fetch('../controllers/VendasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            document.getElementById('totalVendas').textContent = result.data.total_vendas || '0';
            document.getElementById('valorTotal').textContent = 
                `R$ ${parseFloat(result.data.valor_total || 0).toFixed(2)}`;

            const formasContainer = document.getElementById('formasPagamento');
            if (result.data.formas_pagamento && result.data.formas_pagamento.length > 0) {
                formasContainer.innerHTML = result.data.formas_pagamento
                    .map(forma => `
                        <div class="forma-item">
                            ${forma.forma}: ${forma.quantidade}
                        </div>
                    `).join('');
            } else {
                formasContainer.innerHTML = '<div class="forma-item">Nenhuma venda hoje</div>';
            }
        }
    } catch (error) {
        console.error('Erro ao atualizar resumo:', error);
    }
}

// Funções auxiliares
function toggleDetalhesVenda(button) {
    const detalhes = button.closest('.venda-item').querySelector('.venda-detalhes');
    const estaVisivel = detalhes.style.display === 'block';
    detalhes.style.display = estaVisivel ? 'none' : 'block';
    button.textContent = estaVisivel ? '▼' : '▲';
}

async function cancelarVendaHistorico(vendaId) {
    if (!confirm('Tem certeza que deseja cancelar esta venda? Esta ação não pode ser desfeita.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'deletar_venda');
        formData.append('id', vendaId);

        const response = await fetch('../controllers/VendasController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert('Venda cancelada com sucesso!');
            // Atualizar todas as informações
            await carregarHistoricoVendas();
            await carregarProdutos();
            await atualizarResumoDiario();
        } else {
            throw new Error(result.message || 'Erro ao cancelar venda');
        }
    } catch (error) {
        console.error('Erro ao cancelar venda:', error);
        alert('Erro ao cancelar venda: ' + error.message);
    }
}

function mostrarConfirmacao() {
    const modal = document.getElementById('modalConfirmacao');
    const totalVenda = document.getElementById('totalVenda').textContent;
    const formaPagamento = document.querySelector('input[name="formaPagamento"]:checked')
        .nextElementSibling.textContent;

    if (formaPagamento.value === 'dinheiro') {
        const valorRecebido = prompt('Valor recebido em dinheiro:');
        if (!valorRecebido || !calcularTroco(parseFloat(valorRecebido))) {
            return;
        }
    }

    document.getElementById('modalValorTotal').textContent = totalVenda;
    document.getElementById('modalFormaPagamento').textContent = formaPagamento;
    modal.style.display = 'block';
}

function fecharModal() {
    document.getElementById('modalConfirmacao').style.display = 'none';
}

function limparVenda() {
    itensVenda = [];
    atualizarTabelaItens();
    document.getElementById('formVenda').reset();
    
    // Resetar pagamentos
    pagamentosAtivos = 1;
    document.querySelectorAll('.pagamento-item').forEach((item, index) => {
        if (index === 0) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
    
    document.querySelector('.btn-adicionar-pagamento').style.display = 'block';
    atualizarOpcoesFormasPagamento();
    atualizarResumoPagamentos();
    
    const selfServiceConfig = document.querySelector('.self-service-config');
    if (selfServiceConfig) {
        selfServiceConfig.style.display = 'none';
    }
}

async function mostrarConfirmacao() {
    const modal = document.getElementById('modalConfirmacao');
    const totalVenda = document.getElementById('totalVenda').textContent;
    
    // Criar string com todas as formas de pagamento
    const formasPagamento = Array.from(document.querySelectorAll('.pagamento-item:not(.hidden)'))
        .map(item => {
            const forma = item.querySelector('input[type="radio"]:checked').nextElementSibling.textContent;
            const valor = parseFloat(item.querySelector('.valor-input').value);
            return `${forma}: R$ ${valor.toFixed(2)}`;
        })
        .join('<br>');

    document.getElementById('modalValorTotal').textContent = totalVenda;
    document.getElementById('modalFormaPagamento').innerHTML = formasPagamento;
    modal.style.display = 'block';

    // Retornar uma promise que resolve quando o modal é fechado
    return new Promise(resolve => {
        document.querySelector('#modalConfirmacao button').onclick = () => {
            modal.style.display = 'none';
            resolve();
        };
    });
}

function toggleLoading(show = true) {
    const btnFinalizar = document.querySelector('.btn-finalizar');
    if (show) {
        btnFinalizar.disabled = true;
        btnFinalizar.textContent = 'Processando...';
    } else {
        btnFinalizar.disabled = false;
        btnFinalizar.textContent = 'Finalizar Venda';
    }
}

function cancelarVenda() {
    if (itensVenda.length === 0) return;

    if (confirm('Deseja realmente cancelar esta venda?')) {
        limparVenda();
        document.getElementById('buscaProduto').value = '';
        document.getElementById('resultadosBusca').style.display = 'none';
    }
}

function aplicarFiltros() {
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;

    if (!dataInicio || !dataFim) {
        alert('Selecione um período válido');
        return;
    }

    if (new Date(dataFim) < new Date(dataInicio)) {
        alert('Data final não pode ser menor que a data inicial');
        return;
    }

    carregarHistoricoVendas();
}

// Adicionar validação de quantidade máxima no self-service
function adicionarItemSelfService(peso) {
    // Encontrar o produto Self-Service nos produtos disponíveis
    const selfService = produtosDisponiveis.find(p => p.tipo === 'Adicionais' && p.nome === 'Self-Service');

    if (!selfService) {
        alert('Produto Self-Service não encontrado no estoque');
        return;
    }

    const selfServiceItem = {
        produto_id: selfService.id, // Usar o ID real do produto
        nome: 'Self-Service',
        quantidade: peso,
        preco_unitario: parseFloat(document.getElementById('precoSelfService').value)
    };

    itensVenda.push(selfServiceItem);
    atualizarTabelaItens();
    document.getElementById('buscaProduto').value = '';
    document.getElementById('resultadosBusca').style.display = 'none';
}

function toggleLoading(show = true) {
    const btnFinalizar = document.querySelector('.btn-finalizar');
    if (show) {
        btnFinalizar.disabled = true;
        btnFinalizar.textContent = 'Processando...';
    } else {
        btnFinalizar.disabled = false;
        btnFinalizar.textContent = 'Finalizar Venda';
    }
}