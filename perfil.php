<?php
require_once __DIR__ . '/config/conexao.php';

if (!estaLogado()) {
    header('Location: login.php?aviso=login_obrigatorio&redirect=perfil.php');
    exit;
}

$usuario = usuarioAtual($pdo);
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'editar_perfil';

    if ($acao === 'editar_perfil') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $nick_gamer = trim($_POST['nick_gamer'] ?? '');
        $palavra_seguranca = trim($_POST['palavra_seguranca'] ?? '');
        $avatar = trim($_POST['avatar'] ?? 'cyber-ghost');
        $nova_senha = $_POST['nova_senha'] ?? '';

        if ($nome === '' || $email === '' || $palavra_seguranca === '') {
            $erro = 'Nome completo, e-mail e palavra de segurança são obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Formato de e-mail inválido.';
        } else {
            $stmtEmail = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1");
            $stmtEmail->execute([$email, $usuario['id']]);
            if ($stmtEmail->fetch()) {
                $erro = 'Este e-mail já está em uso por outro usuário cadastrado.';
            } else {
                if ($nova_senha !== '') {
                    if (strlen($nova_senha) < 6) {
                        $erro = 'Para alterar sua senha no perfil, informe pelo menos 6 caracteres.';
                    } else {
                        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                        $stmtUpd = $pdo->prepare("
                            UPDATE usuarios
                            SET nome = ?, email = ?, telefone = ?, nick_gamer = ?, palavra_seguranca = ?, avatar = ?, senha = ?
                            WHERE id = ?
                        ");
                        $stmtUpd->execute([$nome, $email, $telefone, $nick_gamer, $palavra_seguranca, $avatar, $hash, $usuario['id']]);
                        $sucesso = 'Suas informações de perfil e sua nova senha foram atualizadas com sucesso!';
                    }
                } else {
                    $stmtUpd = $pdo->prepare("
                        UPDATE usuarios
                        SET nome = ?, email = ?, telefone = ?, nick_gamer = ?, palavra_seguranca = ?, avatar = ?
                        WHERE id = ?
                    ");
                    $stmtUpd->execute([$nome, $email, $telefone, $nick_gamer, $palavra_seguranca, $avatar, $usuario['id']]);
                    $sucesso = 'Dados do seu perfil atualizados com sucesso!';
                }
                $usuario = usuarioAtual($pdo);
            }
        }
    } elseif ($acao === 'recarregar_carteira') {
        $valorRecarga = (float)($_POST['valor_recarga'] ?? 0);
        if ($valorRecarga <= 0 || $valorRecarga > 5000) {
            $erro = 'Selecione um valor de recarga válido entre R$ 10,00 e R$ 5.000,00.';
        } else {
            $stmtSaldo = $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
            $stmtSaldo->execute([$valorRecarga, $usuario['id']]);
            $sucesso = 'Recarga via PIX de ' . formatarMoeda($valorRecarga) . ' adicionada imediatamente à sua Ghost Wallet!';
            $usuario = usuarioAtual($pdo);
        }
    }
}

// Estatísticas da conta do usuário
$stmtStats = $pdo->prepare("SELECT COUNT(*) AS total_compras, COALESCE(SUM(valor_total), 0) AS total_gasto, COALESCE(SUM(cashback_ganho), 0) AS total_cashback FROM pedidos WHERE usuario_id = ?");
$stmtStats->execute([$usuario['id']]);
$stats = $stmtStats->fetch();

// Últimos 3 pedidos para a visão rápida da conta
$stmtRecentes = $pdo->prepare("
    SELECT p.*, pr.titulo AS produto_titulo, pr.plataforma
    FROM pedidos p
    INNER JOIN produtos pr ON pr.id = p.produto_id
    WHERE p.usuario_id = ?
    ORDER BY p.id DESC
    LIMIT 3
");
$stmtRecentes->execute([$usuario['id']]);
$pedidosRecentes = $stmtRecentes->fetchAll();

$iconeAvatarMapa = [
    'cyber-ghost' => 'fa-ghost',
    'neon-ninja'  => 'fa-user-ninja',
    'stealth-wolf'=> 'fa-shield-cat',
    'admin-crown' => 'fa-crown',
];
$iconeAvatarAtual = $iconeAvatarMapa[$usuario['avatar'] ?? 'cyber-ghost'] ?? 'fa-ghost';

$tituloPagina = 'Minha Conta — Perfil & Carteira';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container page">
    <!-- Cabeçalho da Conta (Estilo Steam / Green Man Gaming / Nuuvem) -->
    <section class="account-banner">
        <div class="account-identity">
            <div class="account-avatar">
                <i class="fa-solid <?= htmlspecialchars($iconeAvatarAtual) ?>"></i>
            </div>
            <div>
                <div class="flex gap-1 center-y wrap">
                    <h1><?= htmlspecialchars($usuario['nome']) ?></h1>
                    <span class="role-tag <?= $usuario['tipo'] ?>"><?= $usuario['tipo'] === 'admin' ? 'Administrador' : 'Membro Verificado' ?></span>
                </div>
                <p class="muted small">
                    <strong>@<?= htmlspecialchars($usuario['nick_gamer'] ?: 'GhostPlayer') ?></strong> •
                    <?= htmlspecialchars($usuario['email']) ?> •
                    Cliente desde <?= date('m/Y', strtotime($usuario['criado_em'])) ?>
                </p>
            </div>
        </div>

        <div class="account-metrics">
            <div class="account-metric">
                <span class="muted-label">Saldo Ghost Wallet</span>
                <strong class="metric-val accent" id="perfil-saldo-atual"><?= formatarMoeda((float)$usuario['saldo']) ?></strong>
            </div>
            <div class="account-metric">
                <span class="muted-label">Pedidos Concluídos</span>
                <strong class="metric-val"><?= (int)$stats['total_compras'] ?></strong>
            </div>
            <div class="account-metric">
                <span class="muted-label">Cashback Acumulado</span>
                <strong class="metric-val accent"><?= formatarMoeda((float)$stats['total_cashback']) ?></strong>
            </div>
        </div>
    </section>

    <?php if ($sucesso !== ''): ?>
        <div class="alert alert-success" id="alerta-sucesso-perfil">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger" id="alerta-erro-perfil">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <!-- Layout Profissional de Minha Conta: Menu Lateral + Painéis de Conteúdo -->
    <div class="account-layout">
        <!-- Coluna Lateral: Navegação da Conta + Resumo de Segurança + Carteira -->
        <aside class="account-sidebar">
            <nav class="account-nav card-panel mb-2">
                <div class="muted-label mb-1">Painel da Conta</div>
                <a href="#dados-pessoais" class="account-nav-link active"><i class="fa-regular fa-id-card"></i> Dados Pessoais &amp; Segurança</a>
                <a href="#carteira" class="account-nav-link"><i class="fa-solid fa-wallet"></i> Carteira Ghost Wallet</a>
                <a href="comprar.php#cofre-keys" class="account-nav-link"><i class="fa-solid fa-key"></i> Biblioteca de Keys (<?= (int)$stats['total_compras'] ?>)</a>
                <a href="comprar.php" class="account-nav-link"><i class="fa-solid fa-cart-shopping"></i> Ir para o Carrinho</a>
                <?php if (ehAdmin()): ?>
                    <a href="admin.php" class="account-nav-link"><i class="fa-solid fa-user-shield"></i> Painel do Administrador</a>
                <?php endif; ?>
            </nav>

            <!-- Bloco da Carteira Digital (Ghost Wallet) -->
            <div class="card-panel mb-2" id="carteira">
                <div class="flex between center-y mb-1">
                    <h2 style="margin-bottom:0;"><i class="fa-solid fa-wallet accent"></i> Ghost Wallet</h2>
                    <span class="small muted">PIX Imediato</span>
                </div>
                <p class="muted small mb-2">Adicione créditos à sua carteira para comprar Keys e Gift Cards com 1 clique.</p>

                <form action="perfil.php" method="POST">
                    <input type="hidden" name="acao" value="recarregar_carteira">
                    <label class="form-label" for="select-recarga">Valor da recarga</label>
                    <select name="valor_recarga" class="form-control mb-2" id="select-recarga">
                        <option value="25.00">+ R$ 25,00</option>
                        <option value="50.00">+ R$ 50,00</option>
                        <option value="100.00" selected>+ R$ 100,00</option>
                        <option value="250.00">+ R$ 250,00</option>
                        <option value="500.00">+ R$ 500,00</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-block" id="btn-recarregar-saldo">
                        <i class="fa-brands fa-pix"></i> Adicionar fundos via PIX
                    </button>
                </form>
            </div>

            <!-- Resumo de Credenciais Vinculadas -->
            <div class="card-panel">
                <h2><i class="fa-solid fa-shield-halved accent"></i> Dados Registrados</h2>
                <div class="kv"><span>ID de Cliente</span><strong>#<?= (int)$usuario['id'] ?></strong></div>
                <div class="kv"><span>CPF Titular</span><span><?= htmlspecialchars($usuario['cpf']) ?></span></div>
                <div class="kv"><span>Status</span><span class="status-ok">Verificado</span></div>
                <div class="kv"><span>Palavra-chave</span><code><?= htmlspecialchars($usuario['palavra_seguranca']) ?></code></div>
            </div>
        </aside>

        <!-- Coluna Principal: Edição de Dados Cadastrais, Segurança e Últimos Pedidos -->
        <div class="account-content">
            <div class="card-panel mb-3" id="dados-pessoais">
                <div class="panel-title-row">
                    <div>
                        <h2 style="margin-bottom:2px;">Dados pessoais e configurações de segurança</h2>
                        <p class="muted small">Mantenha seus dados e e-mail sempre atualizados para o recebimento das suas chaves digitais.</p>
                    </div>
                </div>

                <form action="perfil.php" method="POST" id="form-editar-perfil" novalidate>
                    <input type="hidden" name="acao" value="editar_perfil">

                    <div class="form-section-title">1. Identidade e Contato</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="nome">Nome completo *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nick_gamer">Gamertag / Apelido público</label>
                            <input type="text" class="form-control" id="nick_gamer" name="nick_gamer" value="<?= htmlspecialchars($usuario['nick_gamer'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="email">E-mail principal (entrega de keys) *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="telefone">Telefone / Celular</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" placeholder="(11) 99999-9999">
                        </div>
                    </div>

                    <div class="form-section-title mt-1">2. Preferências e Recuperação de Conta</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="palavra_seguranca">Palavra de segurança (para redefinir senha) *</label>
                            <input type="text" class="form-control" id="palavra_seguranca" name="palavra_seguranca" value="<?= htmlspecialchars($usuario['palavra_seguranca']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="avatar">Insígnia do perfil</label>
                            <select class="form-control" id="avatar" name="avatar">
                                <option value="cyber-ghost" <?= ($usuario['avatar'] === 'cyber-ghost') ? 'selected' : '' ?>>Cyber Ghost</option>
                                <option value="neon-ninja" <?= ($usuario['avatar'] === 'neon-ninja') ? 'selected' : '' ?>>Neon Ninja</option>
                                <option value="stealth-wolf" <?= ($usuario['avatar'] === 'stealth-wolf') ? 'selected' : '' ?>>Stealth Wolf</option>
                                <option value="admin-crown" <?= ($usuario['avatar'] === 'admin-crown') ? 'selected' : '' ?>>Sovereign Crown</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section-title mt-1">3. Alteração de Senha (Opcional)</div>
                    <div class="form-group">
                        <label class="form-label" for="nova_senha">Nova senha de acesso</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Preencha apenas se desejar alterar sua senha atual (mín. 6 caracteres)">
                    </div>

                    <div class="flex gap-1 between center-y wrap mt-2">
                        <span class="muted small"><i class="fa-solid fa-lock"></i> Suas informações são protegidas e criptografadas.</span>
                        <button type="submit" class="btn btn-primary" id="btn-salvar-perfil">
                            <i class="fa-solid fa-floppy-disk"></i> Salvar alterações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bloco de Compras Recentes na Minha Conta -->
            <div class="card-panel">
                <div class="flex between center-y wrap mb-2">
                    <h2 style="margin-bottom:0;"><i class="fa-solid fa-clock-rotate-left accent"></i> Últimas aquisições da conta</h2>
                    <a href="comprar.php#cofre-keys" class="btn btn-outline btn-sm">Ver histórico completo</a>
                </div>

                <?php if (!$pedidosRecentes): ?>
                    <p class="muted small">Nenhuma compra registrada até o momento. Explore a <a href="index.php" class="accent">Loja</a> para adquirir seu primeiro Gift Card ou Key.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Produto</th>
                                    <th>Total</th>
                                    <th>Chave / PIN</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidosRecentes as $pr): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($pr['codigo_pedido']) ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($pr['produto_titulo']) ?></strong>
                                            <div class="muted small"><?= htmlspecialchars($pr['plataforma']) ?></div>
                                        </td>
                                        <td><?= formatarMoeda((float)$pr['valor_total']) ?></td>
                                        <td><span class="key-box"><?= htmlspecialchars($pr['key_gerada']) ?></span></td>
                                        <td class="muted"><?= date('d/m/Y', strtotime($pr['criado_em'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
