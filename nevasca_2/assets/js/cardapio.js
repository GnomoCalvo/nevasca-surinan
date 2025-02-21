// Variáveis globais
let itemParaExcluir = null;
let salvandoItem = false;

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    carregarCardapio();
    inicializarEventListeners();
    
    // Fechar modal ao clicar no X
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            fecharModal();
            fecharModalConfirmacao();
        });
    });

    document.getElementById('preco').addEventListener('change', function() {
        validarPreco(this);
    });
});

// Funções de manipulação do cardápio
async function carregarCardapio() {
    const filtroTipo = document.getElementById('filtroTipo').value;
    
    const formData = new FormData();
    formData.append('action', 'listar');
    if (filtroTipo) {
        formData.append('tipo', filtroTipo);
    }
    
    try {
        const response = await fetch('../controllers/CardapioController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            // Agrupar itens por tipo
            const itensPorTipo = {
                'Sorvete': [],
                'Açaí': [],
                'Picolé': []
            };
            
            result.data.forEach(item => {
                if (itensPorTipo[item.tipo]) {
                    itensPorTipo[item.tipo].push(item);
                }
            });
            
            // Atualizar cada seção
            atualizarSecao('secaoSorvetes', itensPorTipo['Sorvete']);
            atualizarSecao('secaoAcais', itensPorTipo['Açaí']);
            atualizarSecao('secaoPicoles', itensPorTipo['Picolé']);
            
            // Mostrar/ocultar seções baseado no filtro
            if (filtroTipo) {
                document.querySelectorAll('.secao-cardapio').forEach(secao => {
                    secao.style.display = 'none';
                });
                switch (filtroTipo) {
                    case 'Sorvete':
                        document.getElementById('secaoSorvetes').style.display = 'block';
                        break;
                    case 'Açaí':
                        document.getElementById('secaoAcais').style.display = 'block';
                        break;
                    case 'Picolé':
                        document.getElementById('secaoPicoles').style.display = 'block';
                        break;
                }
            } else {
                document.querySelectorAll('.secao-cardapio').forEach(secao => {
                    secao.style.display = 'block';
                });
            }

            // Reinicializar os event listeners após atualizar o conteúdo
            inicializarEventListeners();
        }
    } catch (error) {
        console.error('Erro ao carregar cardápio:', error);
    }
}

function atualizarSecao(secaoId, itens) {
    const container = document.querySelector(`#${secaoId} .lista-itens`);
    container.innerHTML = itens.map(item => `
        <div class="item-cardapio ${item.disponivel ? '' : 'indisponivel'}">
            <div class="sabor">${item.sabor}</div>
            <div class="tamanho">${item.tamanho !== 'NA' ? item.tamanho : ''}</div>
            <div class="preco">${formatarPreco(item.preco)}</div>
            <div class="acoes">
                <button class="btn-editar" data-id="${item.id}">✎</button>
                <button class="btn-deletar" data-id="${item.id}">✕</button>
            </div>
        </div>
    `).join('');
}

function formatarPreco(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

function validarPreco(input) {
    const valor = parseFloat(input.value);
    if (valor <= 0) {
        alert('O preço deve ser maior que zero');
        input.value = '';
        return false;
    }
    return true;
}

function validarFormulario() {
    const tipo = document.getElementById('tipo').value;
    const sabor = document.getElementById('sabor').value;
    const tamanho = document.getElementById('tamanho').value;
    const preco = document.getElementById('preco').value;
    
    if (!tipo || !sabor || !tamanho || !preco) {
        alert('Todos os campos são obrigatórios');
        return false;
    }
    
    return validarPreco(document.getElementById('preco'));
}

async function atualizarTamanhos() {
    const tipo = document.getElementById('tipo').value;
    const selectTamanho = document.getElementById('tamanho');
    
    if (!tipo) {
        selectTamanho.innerHTML = '<option value="">Selecione o tipo primeiro</option>';
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'tamanhos');
    formData.append('tipo', tipo);
    
    try {
        const response = await fetch('../controllers/CardapioController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            selectTamanho.innerHTML = result.data.map(tamanho => 
                `<option value="${tamanho}">${tamanho}</option>`
            ).join('');
        }
    } catch (error) {
        console.error('Erro ao carregar tamanhos:', error);
    }
}

function abrirModal() {
    const modal = document.getElementById('modalItem');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function fecharModal() {
    const modal = document.getElementById('modalItem');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('formItem').reset();
    salvandoItem = false; // Reset da flag ao fechar
}

// Funções de manipulação do modal
function abrirModalAdicao() {
    document.getElementById('modalTitulo').textContent = 'Adicionar Item ao Cardápio';
    document.getElementById('itemId').value = '';
    document.getElementById('formItem').reset();
    document.getElementById('tipo').value = '';
    document.getElementById('tamanho').value = '';
    abrirModal();
}

async function editarItem(id) {
    const formData = new FormData();
    formData.append('action', 'buscar');
    formData.append('id', id);
    
    try {
        const response = await fetch('../controllers/CardapioController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            const item = result.data;
            
            document.getElementById('modalTitulo').textContent = 'Editar Item do Cardápio';
            document.getElementById('itemId').value = item.id;
            document.getElementById('tipo').value = item.tipo;
            await atualizarTamanhos();
            document.getElementById('sabor').value = item.sabor;
            document.getElementById('tamanho').value = item.tamanho;
            document.getElementById('preco').value = item.preco;
            document.getElementById('disponivel').checked = item.disponivel == 1;
            
            const modal = document.getElementById('modalItem');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    } catch (error) {
        console.error('Erro ao carregar item:', error);
    }
}

function deletarItem(id) {
    itemParaExcluir = id;
    const modal = document.getElementById('modalConfirmacao');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

async function confirmarExclusao() {
    if (!itemParaExcluir) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'deletar');
        formData.append('id', itemParaExcluir);
        
        const response = await fetch('../controllers/CardapioController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            fecharModalConfirmacao();
            await carregarCardapio();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Erro ao deletar item:', error);
        alert('Erro ao deletar item');
    } finally {
        fecharModalConfirmacao();
    }
}

async function verificarItemDuplicado(tipo, sabor, tamanho, id = null) {
    const formData = new FormData();
    formData.append('action', 'verificar_duplicado');
    formData.append('tipo', tipo);
    formData.append('sabor', sabor);
    formData.append('tamanho', tamanho);
    if (id) formData.append('id', id);
    
    try {
        const response = await fetch('../controllers/CardapioController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        return result.duplicado;
    } catch (error) {
        console.error('Erro ao verificar duplicidade:', error);
        return false;
    }
}

async function salvarItem(event) {
    event.preventDefault();
    
    if (salvandoItem) return; // Evita múltiplos salvamentos
    salvandoItem = true;
    
    if (!validarFormulario()) {
        salvandoItem = false;
        return;
    }

    const id = document.getElementById('itemId').value;
    const tipo = document.getElementById('tipo').value;
    const sabor = document.getElementById('sabor').value;
    const tamanho = document.getElementById('tamanho').value;
    
    try {
        const duplicado = await verificarItemDuplicado(tipo, sabor, tamanho, id);
        if (duplicado) {
            alert('Já existe um item com este tipo, sabor e tamanho no cardápio.');
            salvandoItem = false;
            return;
        }

        const formData = new FormData();
        formData.append('action', id ? 'atualizar' : 'criar');
        if (id) formData.append('id', id);
        
        formData.append('tipo', tipo);
        formData.append('sabor', sabor);
        formData.append('tamanho', tamanho);
        formData.append('preco', document.getElementById('preco').value);
        formData.append('disponivel', document.getElementById('disponivel').checked ? 1 : 0);
        
        const response = await fetch('../controllers/CardapioController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            fecharModal();
            await carregarCardapio();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Erro ao salvar item:', error);
        alert('Erro ao salvar item');
    } finally {
        salvandoItem = false;
    }
}

document.getElementById('formItem').addEventListener('submit', function(e) {
    e.preventDefault();
    salvarItem(e);
});

async function verificarSaborDuplicado(tipo, sabor, tamanho, itemId = null) {
    const formData = new FormData();
    formData.append('action', 'verificar_duplicado');
    formData.append('tipo', tipo);
    formData.append('sabor', sabor);
    formData.append('tamanho', tamanho);
    if (itemId) formData.append('item_id', itemId);
    
    try {
        const response = await fetch('../controllers/CardapioController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        return result.duplicado;
    } catch (error) {
        console.error('Erro ao verificar duplicidade:', error);
        return false;
    }
}

function fecharModal() {
    document.getElementById('modalItem').style.display = 'none';
    document.getElementById('formItem').reset();
}

function abrirModalConfirmacao() {
    const modal = document.getElementById('modalConfirmacao');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function fecharModalConfirmacao() {
    const modal = document.getElementById('modalConfirmacao');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
    itemParaExcluir = null;
}

function aplicarFiltros() {
    carregarCardapio();
}

function inicializarEventListeners() {
    // Listeners dos botões de ação
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            editarItem(id);
        });
    });

    document.querySelectorAll('.btn-deletar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            deletarItem(id);
        });
    });

    // Listener do botão de adicionar
    const btnAdicionar = document.querySelector('.btn-adicionar');
    if (btnAdicionar) {
        btnAdicionar.onclick = () => abrirModalAdicao();
    }

    // Listener do formulário
    const formItem = document.getElementById('formItem');
    if (formItem) {
        formItem.onsubmit = (e) => {
            e.preventDefault();
            salvarItem(e);
        };
    }

    // Listeners dos botões de fechar modal
    document.querySelectorAll('.close').forEach(btn => {
        btn.onclick = () => {
            fecharModal();
            fecharModalConfirmacao();
        };
    });
}

async function gerarCardapioPDF() {
    try {
        // Buscar todos os itens do cardápio
        const formData = new FormData();
        formData.append('action', 'listar');
        
        const response = await fetch('../controllers/CardapioController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (!result.success) throw new Error('Erro ao buscar itens do cardápio');

        // Debug: verificar os dados recebidos
        console.log('Dados recebidos:', result.data);

        // Agrupar itens por tipo e filtrar apenas disponíveis
        const itensPorTipo = result.data
            .filter(item => item.disponivel === 1 || item.disponivel === '1') // Aceitar tanto número quanto string
            .reduce((acc, item) => {
                // Debug: verificar cada item sendo processado
                console.log('Processando item:', item);
                
                const tipo = item.tipo;
                const tamanho = item.tamanho || 'NA';
                
                if (!acc[tipo]) {
                    acc[tipo] = {};
                }
                if (!acc[tipo][tamanho]) {
                    acc[tipo][tamanho] = [];
                }
                acc[tipo][tamanho].push(item);
                return acc;
            }, {});

        // Debug: verificar o resultado do agrupamento
        console.log('Itens agrupados:', itensPorTipo);

        // Criar nova janela para o PDF
        const win = window.open('', '_blank');
        
        // Gerar o HTML do cardápio
        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Cardápio Nevasca</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
                    
                    body {
                        font-family: 'Poppins', sans-serif;
                        margin: 0;
                        padding: 20px;
                        background: #fff;
                    }
                    
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        padding: 20px;
                        background: linear-gradient(135deg, #f3e5ff 0%, #fff 100%);
                        border-radius: 15px;
                    }
                    
                    .logo {
                        max-width: 200px;
                        margin-bottom: 15px;
                    }
                    
                    .titulo {
                        color: #8a2be2;
                        font-size: 2.5em;
                        margin: 10px 0;
                    }
                    
                    .tipo-section {
                        margin-bottom: 30px;
                        background: #f8f9fa;
                        padding: 20px;
                        border-radius: 15px;
                    }
                    
                    .tipo-titulo {
                        color: #8a2be2;
                        font-size: 1.8em;
                        margin-bottom: 20px;
                        padding-bottom: 10px;
                        border-bottom: 3px solid #ffd700;
                    }
                    
                    .tamanho-group {
                        margin-bottom: 20px;
                    }
                    
                    .tamanho-titulo {
                        color: #6a1cb2;
                        font-size: 1.2em;
                        margin-bottom: 10px;
                    }
                    
                    .items-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                        gap: 15px;
                    }
                    
                    .item {
                        background: white;
                        padding: 15px;
                        border-radius: 10px;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    }
                    
                    .item-sabor {
                        color: #333;
                        font-weight: 600;
                        margin-bottom: 5px;
                    }
                    
                    .item-preco {
                        color: #8a2be2;
                        font-weight: 700;
                        font-size: 1.1em;
                    }
                    
                    @media print {
                        body {
                            padding: 0;
                        }
                        
                        .header {
                            background: none;
                        }
                        
                        .tipo-section {
                            break-inside: avoid;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="../assets/img/logo.png" alt="Logo Nevasca" class="logo">
                    <h1 class="titulo">Cardápio Nevasca</h1>
                </div>
                
                ${Object.entries(itensPorTipo).length > 0 ? 
                    Object.entries(itensPorTipo).map(([tipo, tamanhos]) => `
                        <div class="tipo-section">
                            <h2 class="tipo-titulo">${tipo}</h2>
                            ${Object.entries(tamanhos).map(([tamanho, items]) => `
                                <div class="tamanho-group">
                                    ${tamanho !== 'NA' ? `<h3 class="tamanho-titulo">${tamanho}</h3>` : ''}
                                    <div class="items-grid">
                                        ${items.map(item => `
                                            <div class="item">
                                                <div class="item-sabor">${item.sabor}</div>
                                                <div class="item-preco">R$ ${parseFloat(item.preco).toFixed(2)}</div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `).join('')
                    : '<p>Nenhum item disponível no cardápio.</p>'
                }
            </body>
            </html>
        `;

        // Debug: verificar o HTML gerado
        console.log('HTML gerado:', html);

        // Escrever o HTML na nova janela
        win.document.write(html);
        
        // Fechar o documento e imprimir
        win.document.close();
        win.print();
        
    } catch (error) {
        console.error('Erro ao gerar cardápio:', error);
        alert('Erro ao gerar cardápio. Por favor, tente novamente.');
    }
}