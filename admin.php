<?php
require_once __DIR__ . '/config/conexao.php';

// REQUISITO 7 DA ESPECIFICAÇÃO SENAI:
// "Área do administrador. Lista usuários, edita e exclui. O usuário comum não entra aqui."
if (!estaLogado()) {
    header('Location: login.php?aviso=login_obrigatorio&redirect=admin.php');
    exit;
}

if (!ehAdmin()) {
    // Bloqueia imediatamente o usuário comum e redireciona com alerta de acesso negado
    header('Location: index.php?msg=acesso_negado');
    exit;
}

$adminAtual = usuarioAtual($pdo);
$erro = '';
$sucesso = '';

// Processa Edição ou Exclusão de Usuários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'editar_usuario') {
        $idEdit = (int)($_POST['usuario_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cpf = trim($_POST['cpf'] ?? '');
        $nick = trim($_POST['nick_gamer'] ?? '');
        $tipo = $_POST['tipo'] ?? 'comum';
        $saldo = (float)($_POST['saldo'] ?? 0);
        $status = $_POST['status'] ?? 'ativo';

        if ($idEdit <= 0 || $nome === '' || $email === '' || $cpf === '') {
            $erro = 'Preencha todos os campos obrigatórios para atualizar o usuário.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'O e-mail informado para o usuário é inválido.';
        } else {
            // Verifica duplicidade de e-mail ou CPF com outros registros
            $stmtDup = $pdo->prepare("SELECT id FROM usuarios WHERE (email = ? OR cpf = ?) AND id != ? LIMIT 1");
            $stmtDup->execute([$email, $cpf, $idEdit]);
            if ($stmtDup->fetch()) {
                $erro = 'E-mail ou CPF já pertence a outro usuário cadastrado no banco.';
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE usuarios
                    SET nome = ?, email = ?, cpf = ?, nick_gamer = ?, tipo = ?, saldo = ?, status = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$nome, $email, $cpf, $nick, $tipo, $saldo, $status, $idEdit]);
                $sucesso = "Usuário #{$idEdit} ({$nome}) atualizado com sucesso no banco de dados!";
            }
        }
    } elseif ($acao === 'excluir_usuario') {
        $idDel = (int)($_POST['usuario_id'] ?? 0);

        if ($idDel <= 0) {
            $erro = 'ID de usuário inválido para exclusão.';
        } elseif ($idDel === (int)$adminAtual['id']) {
            $erro = 'Operação bloqueada: Você não pode excluir sua própria conta de Administrador ativa.';
        } else {
            $stmtNome = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ? LIMIT 1");
            $stmtNome->execute([$idDel]);
            $alvo = $stmtNome->fetch();

            if (!$alvo) {
                $erro = 'Usuário não encontrado no banco de dados.';
            } else {
                $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmtDel->execute([$idDel]);
                $sucesso = "Usuário #{$idDel} ({$alvo['nome']} - {$alvo['email']}) foi excluído permanentemente!";
            }
        }
    }
}

// Verifica se há um usuário carregado para edição via GET (?editar=ID)
$usuarioParaEditar = null;
if (isset($_GET['editar'])) {
    $idGet = (int)$_GET['editar'];
    $stmtEd = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
    $stmtEd->execute([$idGet]);
    $usuarioParaEditar = $stmtEd->fetch();
}

// Filtro de pesquisa de usuários
$buscaUsuario = trim($_GET['busca_usuario'] ?? '');
if ($buscaUsuario !== '') {
    $stmtUsers = $pdo->prepare("SELECT * FROM usuarios WHERE nome LIKE ? OR email LIKE ? OR cpf LIKE ? OR nick_gamer LIKE ? ORDER BY id ASC");
    $termo = '%' . $buscaUsuario . '%';
    $stmtUsers->execute([$termo, $termo, $termo, $termo]);
    $listaUsuarios = $stmtUsers->fetchAll();
} else {
    $stmtUsers = $pdo->query("SELECT * FROM usuarios ORDER BY id ASC");
    $listaUsuarios = $stmtUsers->fetchAll();
}

// KPIs administrativos
$totalUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalPedidos = (int)$pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$faturamentoTotal = (float)$pdo->query("SELECT COALESCE(SUM(valor_total), 0) FROM pedidos")->fetchColumn();

$tituloPagina = 'Área do Administrador — Gestão de Usuários';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container page">
    <div class="page-header">
        <h1>Administração de usuários</h1>
        <p>Liste, filtre, edite e exclua contas. Área restrita a administradores.</p>
    </div>

    <?php if ($sucesso !== ''): ?>
        <div class="alert alert-success" id="alerta-sucesso-admin"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger" id="alerta-erro-admin"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Usuários cadastrados</div>
            <div class="kpi-value" id="kpi-total-usuarios"><?= $totalUsuarios ?> conta(s)</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Keys emitidas</div>
            <div class="kpi-value"><?= $totalPedidos ?> pedido(s)</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Volume movimentado</div>
            <div class="kpi-value accent"><?= formatarMoeda($faturamentoTotal) ?></div>
        </div>
    </div>

    <?php if ($usuarioParaEditar): ?>
        <section class="card-panel edit-panel" id="painel-edicao-admin">
            <div class="flex between center-y wrap mb-2">
                <h2 style="margin-bottom:0;">Editando usuário #<?= (int)$usuarioParaEditar['id'] ?> — <?= htmlspecialchars($usuarioParaEditar['nome']) ?></h2>
                <a href="admin.php" class="btn btn-outline btn-sm">Cancelar</a>
            </div>

            <form action="admin.php" method="POST" id="form-admin-editar">
                <input type="hidden" name="acao" value="editar_usuario">
                <input type="hidden" name="usuario_id" value="<?= (int)$usuarioParaEditar['id'] ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_nome">Nome completo *</label>
                        <input type="text" class="form-control" id="edit_nome" name="nome" value="<?= htmlspecialchars($usuarioParaEditar['nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_nick">Nickname gamer</label>
                        <input type="text" class="form-control" id="edit_nick" name="nick_gamer" value="<?= htmlspecialchars($usuarioParaEditar['nick_gamer'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_email">E-mail *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" value="<?= htmlspecialchars($usuarioParaEditar['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_cpf">CPF *</label>
                        <input type="text" class="form-control" id="edit_cpf" name="cpf" value="<?= htmlspecialchars($usuarioParaEditar['cpf']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_tipo">Permissão</label>
                        <select class="form-control" id="edit_tipo" name="tipo">
                            <option value="comum" <?= $usuarioParaEditar['tipo'] === 'comum' ? 'selected' : '' ?>>Usuário comum</option>
                            <option value="admin" <?= $usuarioParaEditar['tipo'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_saldo">Saldo Ghost Wallet (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="edit_saldo" name="saldo" value="<?= htmlspecialchars($usuarioParaEditar['saldo']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_status">Status da conta</label>
                    <select class="form-control" id="edit_status" name="status">
                        <option value="ativo" <?= $usuarioParaEditar['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="suspenso" <?= $usuarioParaEditar['status'] === 'suspenso' ? 'selected' : '' ?>>Suspenso (login bloqueado)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" id="btn-salvar-edicao-admin">Salvar alterações</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="card-panel">
        <div class="flex between center-y wrap mb-2">
            <h2 style="margin-bottom:0;">Usuários</h2>

            <form action="admin.php" method="GET" class="search-box">
                <input type="text" name="busca_usuario" id="busca_usuario" placeholder="Nome, e-mail ou CPF" value="<?= htmlspecialchars($buscaUsuario) ?>">
                <button type="submit" class="btn btn-primary btn-sm" id="btn-filtrar-usuarios">Filtrar</button>
                <?php if ($buscaUsuario !== ''): ?>
                    <a href="admin.php" class="btn btn-outline btn-sm">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="tabela-usuarios-admin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>CPF</th>
                        <th>Tipo</th>
                        <th>Saldo</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listaUsuarios as $u): ?>
                        <tr data-user-id="<?= (int)$u['id'] ?>">
                            <td>#<?= (int)$u['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($u['nome']) ?>
                                <div class="muted">@<?= htmlspecialchars($u['nick_gamer'] ?? '-') ?></div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['cpf']) ?></td>
                            <td><span class="role-tag <?= $u['tipo'] ?>"><?= htmlspecialchars($u['tipo']) ?></span></td>
                            <td><?= formatarMoeda((float)$u['saldo']) ?></td>
                            <td>
                                <?php if ($u['status'] === 'ativo'): ?>
                                    <span class="status-ok">Ativo</span>
                                <?php else: ?>
                                    <span class="status-off">Suspenso</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right" style="white-space:nowrap;">
                                <a href="admin.php?editar=<?= (int)$u['id'] ?>" class="btn btn-outline btn-sm btn-editar-usuario" title="Editar usuário">Editar</a>

                                <?php if ((int)$u['id'] !== (int)$adminAtual['id']): ?>
                                    <form action="admin.php" method="POST" class="inline-form" onsubmit="return confirm('Tem certeza que deseja excluir o usuário <?= htmlspecialchars(addslashes($u['nome'])) ?>?');">
                                        <input type="hidden" name="acao" value="excluir_usuario">
                                        <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm btn-excluir-usuario" title="Excluir usuário">Excluir</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted small">(você)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
