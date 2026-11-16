<?php
require_once __DIR__ . '/config/conexao.php';

// REQUISITO 5 DA ESPECIFICAÇÃO SENAI:
// "Tela de execução (depende do tema: comprar Gift Card / Key). Só abre logado"
if (!estaLogado()) {
    header('Location: login.php?aviso=login_obrigatorio&redirect=comprar.php');
    exit;
}

$usuario = usuarioAtual($pdo);
$erro = '';
$pedidoRecemCriado = null;

// Carrega todos os produtos disponíveis
$stmtProds = $pdo->query("SELECT * FROM produtos ORDER BY destaque DESC, id ASC");
$listaProdutos = $stmtProds->fetchAll();

$produtoSelecionadoId = isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : (int)($listaProdutos[0]['id'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'finalizar_compra') {
    $produtoId = (int)($_POST['produto_id'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 1);
    $metodoPagamento = trim($_POST['metodo_pagamento'] ?? 'PIX Instantâneo');
    $emailEntrega = trim($_POST['email_entrega'] ?? $usuario['email']);
    $produtoSelecionadoId = $produtoId;

    $stmtP = $pdo->prepare("SELECT * FROM produtos WHERE id = ? LIMIT 1");
    $stmtP->execute([$produtoId]);
    $produtoCompra = $stmtP->fetch();

    if (!$produtoCompra) {
        $erro = 'O produto selecionado não foi encontrado no catálogo.';
    } elseif ($quantidade < 1 || $quantidade > 10) {
        $erro = 'A quantidade por pedido deve ser entre 1 e 10 unidades.';
    } elseif ($produtoCompra['estoque'] < $quantidade) {
        $erro = 'Estoque insuficiente para a quantidade solicitada deste produto.';
    } elseif (!filter_var($emailEntrega, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido para o envio do comprovante e da Key.';
    } else {
        $valorUnitario = (float)$produtoCompra['preco'];
        $valorTotal = round($valorUnitario * $quantidade, 2);
        $cashbackGanho = round($valorTotal * ((int)$produtoCompra['cashback_percent'] / 100), 2);

        if ($metodoPagamento === 'Saldo Ghost Wallet' && (float)$usuario['saldo'] < $valorTotal) {
            $erro = 'Saldo insuficiente na sua Ghost Wallet (' . formatarMoeda((float)$usuario['saldo']) . ') para esta compra de ' . formatarMoeda($valorTotal) . '. Escolha PIX Instantâneo, Cartão ou adicione fundos em Minha Conta.';
        } else {
            $pdo->beginTransaction();
            try {
                $codigoPedido = 'GC-' . date('Y') . '-' . random_int(1000, 9999);
                $keysGeradas = [];
                for ($i = 0; $i < $quantidade; $i++) {
                    $keysGeradas[] = gerarKeyProduto($produtoCompra['plataforma']);
                }
                $keyFinalString = implode(' | ', $keysGeradas);

                $stmtInsPed = $pdo->prepare("
                    INSERT INTO pedidos (codigo_pedido, usuario_id, produto_id, quantidade, valor_unitario, valor_total, cashback_ganho, metodo_pagamento, email_entrega, key_gerada, status_pedido)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aprovado - Key Liberada')
                ");
                $stmtInsPed->execute([
                    $codigoPedido,
                    $usuario['id'],
                    $produtoCompra['id'],
                    $quantidade,
                    $valorUnitario,
                    $valorTotal,
                    $cashbackGanho,
                    $metodoPagamento,
                    $emailEntrega,
                    $keyFinalString
                ]);

                $stmtEst = $pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");
                $stmtEst->execute([$quantidade, $produtoCompra['id']]);

                if ($metodoPagamento === 'Saldo Ghost Wallet') {
                    $stmtSaldo = $pdo->prepare("UPDATE usuarios SET saldo = saldo - ? + ? WHERE id = ?");
                    $stmtSaldo->execute([$valorTotal, $cashbackGanho, $usuario['id']]);
                } else {
                    $stmtSaldo = $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                    $stmtSaldo->execute([$cashbackGanho, $usuario['id']]);
                }

                $pdo->commit();

                $usuario = usuarioAtual($pdo);
                $pedidoRecemCriado = [
                    'codigo_pedido' => $codigoPedido,
                    'produto_titulo' => $produtoCompra['titulo'],
                    'plataforma' => $produtoCompra['plataforma'],
                    'instrucoes_resgate' => $produtoCompra['instrucoes_resgate'],
                    'quantidade' => $quantidade,
                    'valor_total' => $valorTotal,
                    'cashback_ganho' => $cashbackGanho,
                    'metodo_pagamento' => $metodoPagamento,
                    'key_gerada' => $keyFinalString
                ];
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = 'Falha ao processar o pedido: ' . $e->getMessage();
            }
        }
    }
}

$produtoAtual = $listaProdutos[0] ?? null;
foreach ($listaProdutos as $lp) {
    if ((int)$lp['id'] === $produtoSelecionadoId) {
        $produtoAtual = $lp;
        break;
    }
}

$stmtMeusPedidos = $pdo->prepare("
    SELECT p.*, pr.titulo AS produto_titulo, pr.plataforma, pr.categoria, pr.slug, pr.instrucoes_resgate
    FROM pedidos p
    INNER JOIN produtos pr ON pr.id = p.produto_id
    WHERE p.usuario_id = ?
    ORDER BY p.id DESC
");
$stmtMeusPedidos->execute([$usuario['id']]);
$meusPedidos = $stmtMeusPedidos->fetchAll();

$tituloPagina = 'Meu Carrinho & Biblioteca de Keys';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container page">
    <!-- Barra de etapas de checkout estilo e-commerce real (GMG / Nuuvem) -->
    <div class="checkout-header">
        <div>
            <h1><i class="fa-solid fa-cart-shopping accent"></i> Carrinho &amp; Checkout Digital</h1>
            <p>Revise seu item, escolha a forma de pagamento e receba a chave de ativação instantaneamente.</p>
        </div>
        <div class="checkout-steps">
            <div class="step active"><span>1</span> Carrinho</div>
            <div class="step-line"></div>
            <div class="step active"><span>2</span> Pagamento</div>
            <div class="step-line"></div>
            <div class="step <?= $pedidoRecemCriado ? 'active' : '' ?>"><span>3</span> Ativação Imediata</div>
        </div>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger" id="alerta-erro-compra">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($pedidoRecemCriado): ?>
        <div class="order-success-banner" id="painel-key-liberada">
            <div class="order-success-top">
                <div>
                    <span class="order-badge-ok"><i class="fa-solid fa-circle-check"></i> Pagamento confirmado • Entrega liberada</span>
                    <h2>Pedido <?= htmlspecialchars($pedidoRecemCriado['codigo_pedido']) ?> concluído com sucesso!</h2>
                    <p class="muted small">
                        <?= htmlspecialchars($pedidoRecemCriado['produto_titulo']) ?> (<?= (int)$pedidoRecemCriado['quantidade'] ?>x) •
                        Pago via <strong><?= htmlspecialchars($pedidoRecemCriado['metodo_pagamento']) ?></strong> •
                        Cashback creditado na Ghost Wallet: <strong class="accent">+ <?= formatarMoeda((float)$pedidoRecemCriado['cashback_ganho']) ?></strong>
                    </p>
                </div>
                <div class="order-key-reveal">
                    <div class="muted-label mb-1">Chave / PIN Oficial de Ativação</div>
                    <div class="flex gap-1 center-y wrap">
                        <span class="key-box" id="key-recem-gerada"><?= htmlspecialchars($pedidoRecemCriado['key_gerada']) ?></span>
                        <button type="button" class="btn btn-primary btn-sm" onclick="copiarKey('<?= htmlspecialchars($pedidoRecemCriado['key_gerada']) ?>', this)">
                            <i class="fa-regular fa-copy"></i> Copiar código
                        </button>
                    </div>
                </div>
            </div>
            <div class="order-redeem-guide">
                <strong><i class="fa-solid fa-circle-info"></i> Instruções de resgate (<?= htmlspecialchars($pedidoRecemCriado['plataforma']) ?>):</strong>
                <?= htmlspecialchars($pedidoRecemCriado['instrucoes_resgate']) ?>
            </div>
        </div>
    <?php endif; ?>

    <form action="comprar.php" method="POST" id="form-comprar" class="checkout-grid">
        <input type="hidden" name="acao" value="finalizar_compra">

        <!-- COLUNA ESQUERDA: ITEM NO CARRINHO + PAGAMENTO -->
        <div class="checkout-main">
            <!-- Bloco 1: Produto no Carrinho -->
            <div class="card-panel mb-3">
                <div class="panel-title-row">
                    <h2><i class="fa-solid fa-box-open accent"></i> Item selecionado no carrinho</h2>
                    <a href="index.php#catalogo" class="small accent"><i class="fa-solid fa-arrow-left"></i> Trocar na loja</a>
                </div>

                <div class="form-group">
                    <label class="form-label" for="produto_id">Alterar produto rapidamente</label>
                    <select name="produto_id" id="produto_id" class="form-control" onchange="window.location.href='comprar.php?produto_id=' + this.value">
                        <?php foreach ($listaProdutos as $item): ?>
                            <option value="<?= $item['id'] ?>" <?= ((int)$item['id'] === (int)$produtoAtual['id']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($item['plataforma']) ?>] <?= htmlspecialchars($item['titulo']) ?> — <?= formatarMoeda((float)$item['preco']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($produtoAtual):
                    $imgAtual = imagemProduto($produtoAtual);
                    $descAtual = percentualDesconto($produtoAtual);
                ?>
                    <div class="cart-item-card">
                        <div class="cart-item-thumb">
                            <?php if ($imgAtual): ?>
                                <img src="<?= htmlspecialchars($imgAtual) ?>" alt="<?= htmlspecialchars($produtoAtual['titulo']) ?>">
                            <?php else: ?>
                                <i class="<?= htmlspecialchars($produtoAtual['icone']) ?>"></i>
                            <?php endif; ?>
                        </div>

                        <div class="cart-item-info">
                            <div class="cart-item-badges">
                                <span class="cart-pill"><?= htmlspecialchars($produtoAtual['categoria']) ?></span>
                                <span class="cart-pill"><i class="fa-solid fa-desktop"></i> <?= htmlspecialchars($produtoAtual['plataforma']) ?></span>
                                <span class="cart-pill"><i class="fa-solid fa-globe"></i> <?= htmlspecialchars($produtoAtual['regiao']) ?></span>
                            </div>
                            <h3><?= htmlspecialchars($produtoAtual['titulo']) ?></h3>
                            <p class="muted small"><?= htmlspecialchars($produtoAtual['descricao']) ?></p>
                            <div class="cart-item-Specs">
                                <span><i class="fa-solid fa-bolt accent"></i> <?= htmlspecialchars($produtoAtual['entrega']) ?></span>
                                <span><i class="fa-solid fa-coins accent"></i> +<?= (int)$produtoAtual['cashback_percent'] ?>% de cashback</span>
                                <span><i class="fa-solid fa-check accent"></i> <?= (int)$produtoAtual['estoque'] ?> disponíveis</span>
                            </div>
                        </div>

                        <div class="cart-item-pricing">
                            <?php if ($descAtual > 0): ?>
                                <span class="price-old"><?= formatarMoeda((float)$produtoAtual['preco_original']) ?></span>
                            <?php endif; ?>
                            <span class="price-current"><?= formatarMoeda((float)$produtoAtual['preco']) ?></span>
                            <div class="qty-control mt-1">
                                <label for="quantidade" class="small muted">Qtd:</label>
                                <input type="number" name="quantidade" id="quantidade" class="form-control qty-input" value="1" min="1" max="10" required onchange="atualizarTotalCheckout()" oninput="atualizarTotalCheckout()">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bloco 2: Método de Pagamento e Entrega -->
            <div class="card-panel">
                <h2><i class="fa-solid fa-credit-card accent"></i> Forma de pagamento e entrega</h2>

                <div class="payment-options">
                    <label class="payment-card">
                        <input type="radio" name="metodo_pagamento_radio" value="PIX Instantâneo" checked onchange="selecionarPagamento(this.value)">
                        <div class="payment-card-body">
                            <div class="payment-card-title">
                                <i class="fa-brands fa-pix accent"></i>
                                <strong>PIX Instantâneo</strong>
                                <span class="payment-tag">Mais rápido</span>
                            </div>
                            <p class="muted small">Liberação imediata do código de ativação na tela.</p>
                        </div>
                    </label>

                    <label class="payment-card">
                        <input type="radio" name="metodo_pagamento_radio" value="Saldo Ghost Wallet" onchange="selecionarPagamento(this.value)">
                        <div class="payment-card-body">
                            <div class="payment-card-title">
                                <i class="fa-solid fa-wallet accent"></i>
                                <strong>Saldo Ghost Wallet</strong>
                                <span class="payment-tag">Disponível: <?= formatarMoeda((float)$usuario['saldo']) ?></span>
                            </div>
                            <p class="muted small">Debita direto da sua carteira digital Ghost Cycle.</p>
                        </div>
                    </label>

                    <label class="payment-card">
                        <input type="radio" name="metodo_pagamento_radio" value="Cartão de Crédito" onchange="selecionarPagamento(this.value)">
                        <div class="payment-card-body">
                            <div class="payment-card-title">
                                <i class="fa-regular fa-credit-card accent"></i>
                                <strong>Cartão de Crédito</strong>
                                <span class="payment-tag">1x sem juros</span>
                            </div>
                            <p class="muted small">Aprovação automática para cartões nacionais e internacionais.</p>
                        </div>
                    </label>
                </div>

                <!-- Mantém o select #metodo_pagamento sincronizado para total compatibilidade com testes -->
                <div class="form-row mt-2">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="metodo_pagamento">Método confirmado *</label>
                        <select name="metodo_pagamento" id="metodo_pagamento" class="form-control" onchange="sincronizarRadioPagamento(this.value)">
                            <option value="PIX Instantâneo">PIX Instantâneo</option>
                            <option value="Saldo Ghost Wallet">Saldo Ghost Wallet (<?= formatarMoeda((float)$usuario['saldo']) ?>)</option>
                            <option value="Cartão de Crédito">Cartão de Crédito (1x sem juros)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="email_entrega">E-mail de recebimento da cópia da key *</label>
                        <input type="email" name="email_entrega" id="email_entrega" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUNA DIREITA: RESUMO DO PEDIDO (ORDER SUMMARY FIXO) -->
        <aside class="checkout-sidebar">
            <div class="card-panel summary-panel">
                <h2>Resumo do pedido</h2>

                <?php if ($produtoAtual):
                    $economia = max(0, (float)$produtoAtual['preco_original'] - (float)$produtoAtual['preco']);
                ?>
                    <div class="summary-lines">
                        <div class="kv">
                            <span>Preço de tabela</span>
                            <span id="preview-original"><?= formatarMoeda((float)$produtoAtual['preco_original']) ?></span>
                        </div>
                        <div class="kv">
                            <span>Desconto oficial</span>
                            <span class="accent" id="preview-economia">- <?= formatarMoeda($economia) ?></span>
                        </div>
                        <div class="kv">
                            <span>Subtotal (<span id="preview-qtd">1</span> item)</span>
                            <span id="preview-subtotal"><?= formatarMoeda((float)$produtoAtual['preco']) ?></span>
                        </div>
                        <div class="kv">
                            <span>Taxa de entrega digital</span>
                            <span class="accent">Grátis</span>
                        </div>
                        <div class="kv">
                            <span>Cashback Ghost Wallet (<?= (int)$produtoAtual['cashback_percent'] ?>%)</span>
                            <span class="accent" id="preview-cashback">+ <?= formatarMoeda((float)$produtoAtual['preco'] * ((int)$produtoAtual['cashback_percent'] / 100)) ?></span>
                        </div>
                        <div class="kv kv-total">
                            <span>Total a pagar</span>
                            <span class="summary-total-price" id="preview-total"><?= formatarMoeda((float)$produtoAtual['preco']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary btn-block mt-2" id="btn-confirmar-compra">
                    <i class="fa-solid fa-lock"></i> Finalizar compra e revelar key
                </button>

                <div class="trust-badges mt-2">
                    <div><i class="fa-solid fa-shield-halved accent"></i> Chave 100% oficial e verificada</div>
                    <div><i class="fa-solid fa-bolt accent"></i> Entrega automática em segundos</div>
                </div>

                <?php if ($produtoAtual): ?>
                    <div class="info-box mt-2">
                        <strong>Como ativar (<?= htmlspecialchars($produtoAtual['plataforma']) ?>)</strong>
                        <?= htmlspecialchars($produtoAtual['instrucoes_resgate']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </aside>
    </form>

    <!-- HISTÓRICO / BIBLIOTECA DE KEYS DO USUÁRIO -->
    <section class="card-panel mt-3" id="cofre-keys">
        <div class="flex between center-y wrap mb-2">
            <div>
                <h2 style="margin-bottom:2px;"><i class="fa-solid fa-key accent"></i> Minha Biblioteca de Keys &amp; Pedidos</h2>
                <p class="muted small">Todos os seus códigos adquiridos ficam disponíveis permanentemente aqui.</p>
            </div>
            <span class="role-tag"><?= count($meusPedidos) ?> pedido(s)</span>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="tabela-meus-pedidos">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Produto / Plataforma</th>
                        <th>Qtd</th>
                        <th>Total</th>
                        <th>Cashback</th>
                        <th>Pagamento</th>
                        <th>Chave / PIN de Ativação</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($meusPedidos) === 0): ?>
                        <tr><td colspan="8" class="text-center muted">Você ainda não realizou compras.</td></tr>
                    <?php else: ?>
                        <?php foreach ($meusPedidos as $ped): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ped['codigo_pedido']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($ped['produto_titulo']) ?></strong>
                                    <div class="muted small"><?= htmlspecialchars($ped['plataforma']) ?> • <?= htmlspecialchars($ped['categoria']) ?></div>
                                </td>
                                <td><?= (int)$ped['quantidade'] ?>x</td>
                                <td><strong><?= formatarMoeda((float)$ped['valor_total']) ?></strong></td>
                                <td class="accent">+ <?= formatarMoeda((float)$ped['cashback_ganho']) ?></td>
                                <td><?= htmlspecialchars($ped['metodo_pagamento']) ?></td>
                                <td>
                                    <div class="flex gap-1 center-y wrap">
                                        <span class="key-box"><?= htmlspecialchars($ped['key_gerada']) ?></span>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="copiarKey('<?= htmlspecialchars($ped['key_gerada']) ?>', this)" title="Copiar código">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="muted"><?= date('d/m/Y H:i', strtotime($ped['criado_em'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php if ($produtoAtual): ?>
<script>
const precoBase = <?= (float)$produtoAtual['preco'] ?>;
const precoOriginalBase = <?= (float)$produtoAtual['preco_original'] ?>;
const cashbackPct = <?= (int)$produtoAtual['cashback_percent'] ?>;

function formatarBRL(valor) {
    return 'R$ ' + valor.toFixed(2).replace('.', ',');
}

function atualizarTotalCheckout() {
    const qtdInput = document.getElementById('quantidade');
    let qtd = parseInt(qtdInput.value) || 1;
    if (qtd < 1) qtd = 1;
    if (qtd > 10) qtd = 10;

    const total = precoBase * qtd;
    const totalOrig = precoOriginalBase * qtd;
    const economia = Math.max(0, totalOrig - total);
    const cb = total * (cashbackPct / 100);

    document.getElementById('preview-qtd').innerText = qtd;
    document.getElementById('preview-original').innerText = formatarBRL(totalOrig);
    document.getElementById('preview-economia').innerText = '- ' + formatarBRL(economia);
    document.getElementById('preview-subtotal').innerText = formatarBRL(total);
    document.getElementById('preview-total').innerText = formatarBRL(total);
    document.getElementById('preview-cashback').innerText = '+ ' + formatarBRL(cb);
}

function selecionarPagamento(valor) {
    const sel = document.getElementById('metodo_pagamento');
    if (sel) sel.value = valor;
}

function sincronizarRadioPagamento(valor) {
    const radios = document.querySelectorAll('input[name="metodo_pagamento_radio"]');
    radios.forEach(r => { r.checked = (r.value === valor); });
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
