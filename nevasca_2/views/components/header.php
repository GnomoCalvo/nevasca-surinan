<?php
require_once '../controllers/DespesasController.php';
$despesasController = new DespesasController();
$pedidosVencendo = $despesasController->buscarPedidosVencendo();
$totalNotificacoes = count($pedidosVencendo['data'] ?? []);
?>

<link rel="stylesheet" href="../assets/css/header.css?v=<?php echo time(); ?>">

<header class="main-header">
    <div class="logo">
        <img src="../assets/img/logo.png" alt="Nevasca">
    </div>
    
    <nav class="main-nav">
        <a href="vendas.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'vendas.php' ? 'active' : ''; ?>">
            Vendas
        </a>
        <a href="estoque.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'estoque.php' ? 'active' : ''; ?>">
            Estoque
        </a>
        <a href="despesas.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'despesas.php' ? 'active' : ''; ?>">
            Despesas
        </a>
        <a href="cardapio.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'cardapio.php' ? 'active' : ''; ?>">
            Cardápio
        </a>
        <a href="relatorios.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>">
            Relatórios
        </a>
        <a href="configuracoes.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : ''; ?>">
            Configurações
        </a>
    </nav>

    <div style="display: flex; align-items: center;">
        <div class="notifications">
            <div class="notification-icon" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if ($totalNotificacoes > 0): ?>
                    <span class="notification-badge"><?php echo $totalNotificacoes; ?></span>
                <?php endif; ?>
            </div>
            
            <div id="notificationDropdown" class="notification-dropdown">
                <?php if ($totalNotificacoes > 0): ?>
                    <h3>Pedidos Vencendo</h3>
                    <?php foreach ($pedidosVencendo['data'] as $pedido): ?>
                        <div class="notification-item">
                            <span class="notification-title">Pedido #<?php echo $pedido['id']; ?></span>
                            <span class="notification-date">Vence em: <?php echo date('d/m/Y', strtotime($pedido['data_vencimento'])); ?></span>
                            <span class="notification-value">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notification-empty">
                        Nenhuma notificação
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="../controllers/logout.php" class="logout-btn">
            <span>Sair</span>
        </a>
    </div>
</header>