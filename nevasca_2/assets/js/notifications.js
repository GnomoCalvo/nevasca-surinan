let notificationsVisible = false;

function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    notificationsVisible = !notificationsVisible;
    dropdown.style.display = notificationsVisible ? 'block' : 'none';
}

// Fechar notificações ao clicar fora
document.addEventListener('click', function(event) {
    const notifications = document.querySelector('.notifications');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (!notifications.contains(event.target) && notificationsVisible) {
        dropdown.style.display = 'none';
        notificationsVisible = false;
    }
});

// Verificar novas notificações a cada 5 minutos
async function verificarNotificacoes() {
    try {
        const response = await fetch('../controllers/DespesasController.php', {
            method: 'POST',
            body: new URLSearchParams({
                'action': 'pedidos_vencendo'
            })
        });
        
        const result = await response.json();
        if (result.success) {
            // Filtrar apenas pedidos pendentes (garantia adicional no frontend)
            const pedidosPendentes = result.data.filter(pedido => 
                !pedido.status || pedido.status === 'pendente'
            );
            
            atualizarBadgeNotificacoes(pedidosPendentes.length);
            if (notificationsVisible) {
                atualizarDropdownNotificacoes(pedidosPendentes);
            }
        }
    } catch (error) {
        console.error('Erro ao verificar notificações:', error);
    }
}

function atualizarBadgeNotificacoes(total) {
    const badge = document.querySelector('.notification-badge');
    if (total > 0) {
        if (!badge) {
            const newBadge = document.createElement('span');
            newBadge.className = 'notification-badge';
            newBadge.textContent = total;
            document.querySelector('.notification-icon').appendChild(newBadge);
        } else {
            badge.textContent = total;
        }
    } else if (badge) {
        badge.remove();
    }
}

function atualizarDropdownNotificacoes(pedidos) {
    const dropdown = document.getElementById('notificationDropdown');
    if (pedidos.length > 0) {
        dropdown.innerHTML = `
            <h3>Pedidos a Vencer</h3>
            ${pedidos.map(pedido => `
                <div class="notification-item">
                    <span class="notification-title">Pedido #${pedido.id}</span>
                    <span class="notification-date">Vence em: ${new Date(pedido.data_vencimento).toLocaleDateString()}</span>
                    <span class="notification-value">R$ ${parseFloat(pedido.valor_total).toFixed(2)}</span>
                </div>
            `).join('')}
        `;
    } else {
        dropdown.innerHTML = `
            <div class="notification-empty">
                Nenhuma notificação
            </div>
        `;
    }
}

function atualizarNotificacoesAposPagamento() {
    verificarNotificacoes();
}

window.atualizarNotificacoesAposPagamento = atualizarNotificacoesAposPagamento;

// Iniciar verificação de notificações
setInterval(verificarNotificacoes, 300000); // 5 minutos
verificarNotificacoes(); // Verificar imediatamente ao carregar