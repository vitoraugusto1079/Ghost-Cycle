<?php
require_once __DIR__ . '/../config/conexao.php';
$usuarioLogado = usuarioAtual($pdo);
$paginaAtual = basename($_SERVER['PHP_SELF']);

$qtdPedidosUsuario = 0;
if ($usuarioLogado) {
    $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?");
    $stmtCnt->execute([$usuarioLogado['id']]);
    $qtdPedidosUsuario = (int)$stmtCnt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($tituloPagina) ? htmlspecialchars($tituloPagina) . ' | Ghost Cycle' : 'Ghost Cycle — Gift Cards, Moedas & Keys de Jogos' ?></title>
    <link rel="icon" type="image/png" href="assets/img/logo-icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container navbar">
        <a href="index.php" class="brand-logo" title="Ghost Cycle - Home">
            <img src="assets/img/logo-icon.png" alt="Ghost Cycle" class="logo-emblem">
            <span class="brand-name">GHOST <span>CYCLE</span></span>
        </a>

        <ul class="nav-links">
            <li><a href="index.php" class="<?= $paginaAtual === 'index.php' ? 'active' : '' ?>">Loja</a></li>
            <?php if ($usuarioLogado): ?>
                <li><a href="perfil.php" class="<?= $paginaAtual === 'perfil.php' ? 'active' : '' ?>">Minha Conta</a></li>
                <?php if (ehAdmin()): ?>
                    <li><a href="admin.php" class="admin-link <?= $paginaAtual === 'admin.php' ? 'active' : '' ?>">Administração</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <?php if ($usuarioLogado): ?>
                <a href="perfil.php#carteira" class="wallet-pill" title="Saldo disponível na Ghost Wallet">
                    <i class="fa-solid fa-wallet"></i>
                    <strong><?= formatarMoeda((float)$usuarioLogado['saldo']) ?></strong>
                </a>

                <a href="comprar.php" class="cart-icon-btn <?= $paginaAtual === 'comprar.php' ? 'active' : '' ?>" id="btn-nav-carrinho" title="Carrinho de Compras e Cofre de Keys">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <?php if ($qtdPedidosUsuario > 0): ?>
                        <span class="cart-badge"><?= $qtdPedidosUsuario ?></span>
                    <?php endif; ?>
                </a>

                <a href="perfil.php" class="user-chip" title="Acessar Minha Conta">
                    <span class="user-avatar-mini"><?= strtoupper(substr($usuarioLogado['nick_gamer'] ?: $usuarioLogado['nome'], 0, 1)) ?></span>
                    <span><?= htmlspecialchars($usuarioLogado['nick_gamer'] ?: explode(' ', $usuarioLogado['nome'])[0]) ?></span>
                    <span class="role-tag <?= $usuarioLogado['tipo'] ?>"><?= $usuarioLogado['tipo'] ?></span>
                </a>

                <a href="logout.php" class="btn btn-outline btn-sm" id="btn-logout" title="Encerrar sessão">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm" id="btn-nav-login">Entrar</a>
                <a href="cadastro.php" class="btn btn-primary btn-sm" id="btn-nav-cadastro">Cadastre-se</a>
            <?php endif; ?>
        </div>
    </div>
</header>
