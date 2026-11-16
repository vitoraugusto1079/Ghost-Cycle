<?php
require_once __DIR__ . '/config/conexao.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $nick_gamer = trim($_POST['nick_gamer'] ?? '');
    $palavra_seguranca = trim($_POST['palavra_seguranca'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if ($nome === '' || $email === '' || $cpf === '' || $palavra_seguranca === '' || $senha === '') {
        $erro = 'Preencha todos os campos obrigatórios para concluir seu cadastro.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um endereço de e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve possuir no mínimo 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'A confirmação de senha não confere com a senha digitada.';
    } else {
        // Verifica duplicidade de e-mail ou CPF
        $stmtCheck = $pdo->prepare("SELECT id, email, cpf FROM usuarios WHERE email = ? OR cpf = ? LIMIT 1");
        $stmtCheck->execute([$email, $cpf]);
        $existente = $stmtCheck->fetch();

        if ($existente) {
            if (strcasecmp($existente['email'], $email) === 0) {
                $erro = 'Este endereço de e-mail já está cadastrado na Ghost Cycle.';
            } else {
                $erro = 'Este CPF já possui uma conta vinculada no sistema.';
            }
        } else {
            $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
            $nickFinal = $nick_gamer !== '' ? $nick_gamer : explode(' ', $nome)[0] . '_GC';

            $stmtIns = $pdo->prepare("
                INSERT INTO usuarios (nome, email, cpf, telefone, nick_gamer, senha, palavra_seguranca, tipo, saldo, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'comum', 0.00, 'ativo')
            ");
            $stmtIns->execute([
                $nome,
                $email,
                $cpf,
                $telefone,
                $nickFinal,
                $hashSenha,
                $palavra_seguranca
            ]);

            header('Location: login.php?cadastro=sucesso&email=' . urlencode($email));
            exit;
        }
    }
}

$tituloPagina = 'Criar Conta Gamer';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container page">
    <div class="auth-wrapper wide">
        <div class="card-panel">
            <div class="page-header">
                <h1>Criar conta</h1>
                <p>Preencha seus dados para adquirir Gift Cards, Moedas e Keys com entrega imediata.</p>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger" id="alerta-erro-cadastro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form action="cadastro.php" method="POST" id="form-cadastro" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nome">Nome completo *</label>
                        <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex.: Gabriel Oliveira" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="nick_gamer">Nickname gamer</label>
                        <input type="text" class="form-control" id="nick_gamer" name="nick_gamer" placeholder="Ex.: GhostSniperBR" value="<?= htmlspecialchars($_POST['nick_gamer'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">E-mail *</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="seuemail@exemplo.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cpf">CPF *</label>
                        <input type="text" class="form-control" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone / WhatsApp</label>
                        <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(11) 99999-9999" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="palavra_seguranca">Palavra de segurança *</label>
                        <input type="text" class="form-control" id="palavra_seguranca" name="palavra_seguranca" placeholder="Usada para redefinir a senha" value="<?= htmlspecialchars($_POST['palavra_seguranca'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="senha">Senha (mínimo 6 caracteres) *</label>
                        <input type="password" class="form-control" id="senha" name="senha" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirmar_senha">Confirmar senha *</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="btn-submit-cadastro">Finalizar cadastro</button>
            </form>

            <p class="text-center muted small mt-2">Já tem conta? <a href="login.php" class="accent">Entrar</a></p>
        </div>
    </div>
</main>

<script>
// Máscara automática para CPF
document.getElementById('cpf')?.addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    e.target.value = v;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

