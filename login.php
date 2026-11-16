<?php
require_once __DIR__ . '/config/conexao.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';
$emailPreenchido = $_GET['email'] ?? '';
$redirectUrl = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $redirectPost = trim($_POST['redirect'] ?? '');
    $emailPreenchido = $email;

    if ($email === '' || $senha === '') {
        $erro = 'Por favor, preencha o e-mail e a senha para autenticar.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        $senhaValida = false;
        if ($usuario) {
            if (password_verify($senha, $usuario['senha']) || $senha === $usuario['senha']) {
                $senhaValida = true;
            }
        }

        if (!$usuario || !$senhaValida) {
            $erro = 'E-mail ou senha incorretos. Verifique suas credenciais.';
        } elseif ($usuario['status'] === 'suspenso') {
            $erro = 'Esta conta encontra-se suspensa pelo Administrador. Entre em contato com o suporte.';
        } else {
            // Autenticação bem-sucedida
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            $_SESSION['usuario_saldo'] = $usuario['saldo'];
            $_SESSION['usuario_nick'] = $usuario['nick_gamer'];

            // Redirecionamento automático conforme o tipo da conta (Requisito 3: Usuário Comum x Administrador)
            if ($redirectPost !== '' && strpos($redirectPost, 'http') === false) {
                header('Location: ' . $redirectPost);
                exit;
            }

            if ($usuario['tipo'] === 'admin') {
                header('Location: admin.php?login=sucesso');
                exit;
            } else {
                header('Location: index.php?login=sucesso');
                exit;
            }
        }
    }
}

$tituloPagina = 'Entrar na Conta';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container page">
    <div class="auth-wrapper">
        <div class="card-panel">
            <div class="page-header">
                <h1>Entrar</h1>
                <p>Acesse sua conta para comprar e gerenciar suas chaves digitais.</p>
            </div>

            <?php if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso'): ?>
                <div class="alert alert-success" id="alerta-cadastro-sucesso">Conta criada com sucesso! Faça login abaixo para continuar.</div>
            <?php endif; ?>

            <?php if (isset($_GET['senha']) && $_GET['senha'] === 'redefinida'): ?>
                <div class="alert alert-success" id="alerta-senha-redefinida">Sua senha foi redefinida com sucesso! Acesse com a nova senha.</div>
            <?php endif; ?>

            <?php if (isset($_GET['aviso']) && $_GET['aviso'] === 'login_obrigatorio'): ?>
                <div class="alert alert-warning" id="alerta-login-obrigatorio"><strong>Atenção:</strong> faça login para acessar o carrinho e concluir sua compra.</div>
            <?php endif; ?>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger" id="alerta-erro-login"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" id="form-login" novalidate>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectUrl) ?>">

                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="seuemail@exemplo.com" value="<?= htmlspecialchars($emailPreenchido) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required>
                    <div class="form-hint"><a href="redefinir_senha.php" class="accent">Esqueceu a senha?</a></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="btn-submit-login">Entrar</button>
            </form>

            <p class="text-center muted small mt-2">Ainda não tem conta? <a href="cadastro.php" class="accent">Criar conta</a></p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
