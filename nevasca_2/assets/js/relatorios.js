// Variáveis para os gráficos
let graficoVendas = null;
let graficoBalanco = null;

// Event Listeners
document.addEventListener('DOMContentLoaded', function () {
    const hoje = new Date();
    const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);

    document.getElementById('dataInicio').value = inicioMes.toISOString().split('T')[0];
    document.getElementById('dataFim').value = hoje.toISOString().split('T')[0];

    gerarRelatorios();
});

// Função principal para gerar todos os relatórios
async function gerarRelatorios() {
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;

    await Promise.all([
        gerarRelatorioVendas(dataInicio, dataFim),
        gerarRelatorioProdutos(dataInicio, dataFim),
        gerarRelatorioEstoque(),
        gerarRelatorioDespesas(dataInicio, dataFim),
        gerarBalanco(dataInicio, dataFim)
    ]);
}

// Funções específicas para cada relatório
async function gerarRelatorioVendas(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'relatorio_vendas');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    try {
        const response = await fetch('../controllers/RelatoriosController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            const data = result.data;

            // Atualizar informações resumidas
            document.getElementById('totalVendas').textContent = data.total_vendas;
            document.getElementById('valorTotalVendas').textContent =
                `R$ ${parseFloat(data.total_geral).toFixed(2)}`;

            // Atualizar formas de pagamento
            const formasContainer = document.getElementById('formasPagamento');
            formasContainer.innerHTML = Object.entries(data.formas_pagamento)
                .map(([forma, info]) => `
                    <div class="forma-item">
                        <span class="forma-nome">${forma.charAt(0).toUpperCase() + forma.slice(1)}</span>
                        <span class="forma-valor">R$ ${parseFloat(info.valor).toFixed(2)}</span>
                        <span class="forma-quantidade">${info.quantidade} venda${info.quantidade !== 1 ? 's' : ''}</span>
                    </div>
                `).join('');

            // Atualizar gráfico
            atualizarGraficoVendas(data.vendas);
        }
    } catch (error) {
        console.error('Erro ao gerar relatório de vendas:', error);
    }
}

async function gerarRelatorioProdutos(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'relatorio_produtos');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    try {
        const response = await fetch('../controllers/RelatoriosController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            const data = result.data;

            // Atualizar informações resumidas
            document.getElementById('totalItens').textContent = data.total_quantidade;
            document.getElementById('valorTotalProdutos').textContent =
                `R$ ${parseFloat(data.total_valor).toFixed(2)}`;

            // Atualizar tabela de produtos
            const tbody = document.getElementById('produtosMaisVendidos');
            tbody.innerHTML = data.produtos.map(produto => `
                <tr>
                    <td>${produto.nome} ${produto.tamanho !== 'NA' ? `- ${produto.tamanho}` : ''}</td>
                    <td>${produto.quantidade_vendida}</td>
                    <td>R$ ${parseFloat(produto.valor_total).toFixed(2)}</td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Erro ao gerar relatório de produtos:', error);
    }
}

async function gerarRelatorioEstoque() {
    const formData = new FormData();
    formData.append('action', 'relatorio_estoque');

    try {
        const response = await fetch('../controllers/RelatoriosController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            const data = result.data;

            // Atualizar total de alertas
            document.getElementById('totalAlertas').textContent = data.total_alertas;

            // Atualizar tabela de estoque
            const tbody = document.getElementById('produtosEstoqueBaixo');
            tbody.innerHTML = data.produtos.map(produto => `
                <tr>
                    <td>${produto.nome} ${produto.tamanho !== 'NA' ? `- ${produto.tamanho}` : ''}</td>
                    <td>${produto.quantidade}</td>
                    <td>R$ ${parseFloat(produto.preco).toFixed(2)}</td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Erro ao gerar relatório de estoque:', error);
    }
}

async function gerarRelatorioDespesas(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'relatorio_despesas');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    try {
        const response = await fetch('../controllers/RelatoriosController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            const data = result.data;

            // Atualizar informações resumidas
            document.getElementById('totalDespesas').textContent =
                `R$ ${parseFloat(data.total_despesas).toFixed(2)}`;
            document.getElementById('totalPedidosVencendo').textContent =
                data.pedidos_vencendo.length;

            // Atualizar tabela de despesas
            const tbodyDespesas = document.getElementById('listaDespesas');
            tbodyDespesas.innerHTML = data.despesas.map(despesa => `
                <tr>
                    <td>${new Date(despesa.data).toLocaleDateString()}</td>
                    <td>${despesa.nome}</td>
                    <td>R$ ${parseFloat(despesa.valor).toFixed(2)}</td>
                </tr>
            `).join('');

            // Atualizar tabela de pedidos
            const tbodyPedidos = document.getElementById('listaPedidos');
            tbodyPedidos.innerHTML = data.pedidos_vencendo.map(pedido => {
                const itensHtml = pedido.itens.map(item => `
                    <span class="pedido-item">
                        ${item.nome} - ${item.quantidade} x R$ ${parseFloat(item.preco).toFixed(2)}
                    </span>
                `).join('');

                return `
                    <tr>
                        <td>
                            ${new Date(pedido.data_vencimento).toLocaleDateString()}
                            <br>
                            <span class="status-${pedido.status}">${pedido.status}</span>
                        </td>
                        <td>
                            <div class="pedido-itens">
                                ${itensHtml}
                            </div>
                        </td>
                        <td>R$ ${parseFloat(pedido.valor_total).toFixed(2)}</td>
                    </tr>
                `;
            }).join('');
        }
    } catch (error) {
        console.error('Erro ao gerar relatório de despesas:', error);
    }
}

async function gerarBalanco(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'balanco');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    try {
        const response = await fetch('../controllers/RelatoriosController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            const data = result.data;

            // Atualizar informações do balanço
            document.getElementById('totalReceitas').textContent =
                `R$ ${parseFloat(data.receitas).toFixed(2)}`;
            document.getElementById('totalDespesasBalanco').textContent =
                `R$ ${parseFloat(data.despesas).toFixed(2)}`;
            document.getElementById('totalLucro').textContent =
                `R$ ${parseFloat(data.lucro).toFixed(2)}`;

            // Atualizar gráfico do balanço
            atualizarGraficoBalanco(data);
        }
    } catch (error) {
        console.error('Erro ao gerar balanço:', error);
    }
}

// Funções auxiliares para os gráficos
function atualizarGraficoVendas(vendas) {
    const ctx = document.getElementById('graficoVendas').getContext('2d');

    if (graficoVendas) {
        graficoVendas.destroy();
    }

    graficoVendas = new Chart(ctx, {
        type: 'line',
        data: {
            labels: vendas.map(venda => new Date(venda.data).toLocaleDateString()),
            datasets: [{
                label: 'Valor Total de Vendas',
                data: vendas.map(venda => venda.valor_total),
                borderColor: '#8a2be2',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => `R$ ${value}`
                    }
                }
            }
        }
    });
}

function atualizarGraficoBalanco(data) {
    const ctx = document.getElementById('graficoBalanco').getContext('2d');

    if (graficoBalanco) {
        graficoBalanco.destroy();
    }

    graficoBalanco = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Receitas', 'Despesas'],
            datasets: [{
                data: [data.receitas, data.despesas],
                backgroundColor: ['#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
}

async function exportarPDF() {
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;

    // Cria uma nova janela para o relatório
    const win = window.open('', '_blank');
    win.document.write(`
        <html>
        <head>
            <title>Relatório ${dataInicio} a ${dataFim}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .section {
                    margin-bottom: 30px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f5f5f5;
                }
                .total {
                    font-weight: bold;
                }
                .formas-pagamento-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 10px;
                    margin-top: 10px;
                }
                .forma-item {
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                @media print {
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Relatório Detalhado</h1>
                <p>Período: ${new Date(dataInicio).toLocaleDateString()} a ${new Date(dataFim).toLocaleDateString()}</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()">Imprimir Relatório</button>
            </div>
    `);

    try {
        // Buscar dados do balanço
        const balancoData = await fetchBalanco(dataInicio, dataFim);
        if (balancoData) {
            win.document.write(`
                <div class="section">
                    <h2>Resumo Financeiro</h2>
                    <table>
                        <tr>
                            <th>Receitas</th>
                            <td>R$ ${parseFloat(balancoData.receitas).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <th>Despesas</th>
                            <td>R$ ${parseFloat(balancoData.despesas).toFixed(2)}</td>
                        </tr>
                        <tr class="total">
                            <th>Lucro</th>
                            <td>R$ ${parseFloat(balancoData.lucro).toFixed(2)}</td>
                        </tr>
                    </table>
                </div>
            `);
        }

        // Buscar dados de vendas
        const vendasData = await fetchVendas(dataInicio, dataFim);
        if (vendasData) {
            win.document.write(`
                <div class="section">
                    <h2>Vendas do Período</h2>
                    <h3>Formas de Pagamento</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Forma</th>
                                <th>Quantidade</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Object.entries(vendasData.formas_pagamento)
                                .map(([forma, info]) => `
                                    <tr>
                                        <td>${forma.charAt(0).toUpperCase() + forma.slice(1)}</td>
                                        <td>${info.quantidade}</td>
                                        <td>R$ ${parseFloat(info.valor).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                        </tbody>
                    </table>

                    <h3>Vendas Diárias</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Quantidade</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${vendasData.vendas.map(venda => `
                                <tr>
                                    <td>${new Date(venda.data).toLocaleDateString()}</td>
                                    <td>${venda.total_vendas}</td>
                                    <td>R$ ${parseFloat(venda.valor_total).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `);
        }

        // Buscar dados de despesas e pedidos
        const despesasData = await fetchDespesas(dataInicio, dataFim);
        if (despesasData) {
            win.document.write(`
                <div class="section">
                    <h2>Despesas</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${despesasData.despesas.map(despesa => `
                                <tr>
                                    <td>${new Date(despesa.data).toLocaleDateString()}</td>
                                    <td>${despesa.nome}</td>
                                    <td>R$ ${parseFloat(despesa.valor).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>

                    <h2>Pedidos perto de vencer</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Data Vencimento</th>
                                <th>Status</th>
                                <th>Itens</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${despesasData.pedidos_vencendo.map(pedido => `
                                <tr>
                                    <td>${new Date(pedido.data_vencimento).toLocaleDateString()}</td>
                                    <td><span class="status-${pedido.status}">${pedido.status}</span></td>
                                    <td>${pedido.itens.map(item =>
                `${item.nome} - ${item.quantidade} x R$ ${parseFloat(item.preco).toFixed(2)}`
            ).join('<br>')}</td>
                                    <td>R$ ${parseFloat(pedido.valor_total).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `);
        }

        win.document.write('</body></html>');
        win.document.close();

    } catch (error) {
        console.error('Erro ao gerar relatório:', error);
        win.close();
    }
}

async function fetchBalanco(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'balanco');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    const response = await fetch('../controllers/RelatoriosController.php', {
        method: 'POST',
        body: formData
    });
    const result = await response.json();
    return result.success ? result.data : null;
}

async function fetchVendas(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'relatorio_vendas');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    const response = await fetch('../controllers/RelatoriosController.php', {
        method: 'POST',
        body: formData
    });
    const result = await response.json();
    return result.success ? result.data : null;
}

async function fetchDespesas(dataInicio, dataFim) {
    const formData = new FormData();
    formData.append('action', 'relatorio_despesas');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    const response = await fetch('../controllers/RelatoriosController.php', {
        method: 'POST',
        body: formData
    });
    const result = await response.json();
    return result.success ? result.data : null;
}

async function exportarXML() {
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;

    const formData = new FormData();
    formData.append('action', 'exportar_xml');
    formData.append('data_inicio', dataInicio);
    formData.append('data_fim', dataFim);

    try {
        const response = await fetch('../controllers/RelatoriosController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            // Criar blob e fazer download
            const blob = new Blob([result.data.xml], { type: 'application/xml' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `relatorio_financeiro_${dataInicio}_${dataFim}.xml`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    } catch (error) {
        console.error('Erro ao exportar XML:', error);
    }
}

function atualizarFormasPagamento(data) {
    const formasContainer = document.getElementById('formasPagamento');
    if (!data.formas_pagamento) return;

    formasContainer.innerHTML = Object.entries(data.formas_pagamento)
        .map(([forma, info]) => {
            // Verifica se há vendas nesta forma de pagamento
            if (info.valor > 0 || info.quantidade > 0) {
                return `
                    <div class="forma-item">
                        <span class="forma-nome">${forma.charAt(0).toUpperCase() + forma.slice(1)}</span>
                        <span class="forma-valor">R$ ${parseFloat(info.valor).toFixed(2)}</span>
                        <span class="forma-quantidade">${info.quantidade} venda${info.quantidade !== 1 ? 's' : ''}</span>
                    </div>
                `;
            }
            return '';
        })
        .filter(item => item !== '')
        .join('');
}