<?php
$tituloPagina = 'Loja — Gift Cards, Keys, Moedas e Assinaturas';
require_once __DIR__ . '/includes/header.php';

const PRODUTOS_POR_PAGINA = 12;

$CATEGORIAS = [
    'Gift Card'       => ['Gift Cards',       'fa-credit-card'],
    'Key de Jogo'     => ['Keys de Jogos',    'fa-key'],
    'Moedas & Top-Up' => ['Moedas & Top-Up',  'fa-coins'],
    'Assinatura'      => ['Assinaturas',      'fa-gamepad'],
];

$ORDENS = [
    'relevancia'  => ['Relevância',        'destaque DESC, vendas DESC, id ASC'],
    'vendidos'    => ['Mais vendidos',     'vendas DESC, id ASC'],
    'desconto'    => ['Maiores descontos', '(1 - preco / preco_original) DESC, vendas DESC'],
    'menor_preco' => ['Menor preço',       'preco ASC, id ASC'],
    'maior_preco' => ['Maior preço',       'preco DESC, id ASC'],
    'nome'        => ['Nome (A-Z)',        'titulo ASC'],
    'recentes'    => ['Lançamentos',       'id DESC'],
];

function paramLista(string $nome): array {
    $v = $_GET[$nome] ?? [];
    if (!is_array($v)) {
        $v = [$v];
    }
    $v = array_map(fn($x) => trim((string)$x), $v);
    return array_values(array_filter($v, fn($x) => $x !== ''));
}

function paramPreco(string $nome): ?float {
    $v = trim((string)($_GET[$nome] ?? ''));
    if ($v === '') {
        return null;
    }
    return max(0.0, (float)str_replace(',', '.', $v));
}

// Monta uma URL da loja alterando apenas os parâmetros informados (null remove o parâmetro)
function urlFiltro(array $mod): string {
    $q = $_GET;
    unset($q['pagina']);
    foreach ($mod as $k => $v) {
        if ($v === null || $v === []) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    $qs = http_build_query($q);
    return 'index.php' . ($qs !== '' ? '?' . $qs : '') . '#catalogo';
}

function urlSemValor(string $chave, string $valor): string {
    return urlFiltro([$chave => array_values(array_diff(paramLista($chave), [$valor]))]);
}

function marcado(array $lista, string $valor): string {
    return in_array($valor, $lista, true) ? 'checked' : '';
}

// ---------- Leitura dos filtros ----------
$fCategorias  = array_values(array_intersect(paramLista('categoria'), array_keys($CATEGORIAS)));
$fPlataformas = paramLista('plataforma');
$fGeneros     = paramLista('genero');
$busca        = trim((string)($_GET['busca'] ?? ''));
$precoMin     = paramPreco('preco_min');
$precoMax     = paramPreco('preco_max');
$soPromo      = !empty($_GET['promo']);
$soEstoque    = !empty($_GET['estoque']);
$ordem        = isset($ORDENS[$_GET['ordem'] ?? '']) ? $_GET['ordem'] : 'relevancia';
$paginaAtualN = max(1, (int)($_GET['pagina'] ?? 1));

// ---------- Consulta principal ----------
$where = [];
$params = [];

if ($fCategorias) {
    $where[] = 'categoria IN (' . implode(',', array_fill(0, count($fCategorias), '?')) . ')';
    array_push($params, ...$fCategorias);
}
if ($fPlataformas) {
    $where[] = 'plataforma IN (' . implode(',', array_fill(0, count($fPlataformas), '?')) . ')';
    array_push($params, ...$fPlataformas);
}
if ($fGeneros) {
    $where[] = 'genero IN (' . implode(',', array_fill(0, count($fGeneros), '?')) . ')';
    array_push($params, ...$fGeneros);
}
if ($busca !== '') {
    $where[] = '(titulo LIKE ? OR plataforma LIKE ? OR genero LIKE ? OR descricao LIKE ?)';
    $termo = '%' . $busca . '%';
    array_push($params, $termo, $termo, $termo, $termo);
}
if ($precoMin !== null) {
    $where[] = 'preco >= ?';
    $params[] = $precoMin;
}
if ($precoMax !== null) {
    $where[] = 'preco <= ?';
    $params[] = $precoMax;
}
if ($soPromo) {
    $where[] = 'preco <= preco_original * 0.90';
}
if ($soEstoque) {
    $where[] = 'estoque > 0';
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM produtos $sqlWhere");
$stmtTotal->execute($params);
$totalResultados = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalResultados / PRODUTOS_POR_PAGINA));
$paginaAtualN = min($paginaAtualN, $totalPaginas);
$offset = ($paginaAtualN - 1) * PRODUTOS_POR_PAGINA;

$stmt = $pdo->prepare(
    "SELECT * FROM produtos $sqlWhere ORDER BY {$ORDENS[$ordem][1]} LIMIT " . PRODUTOS_POR_PAGINA . " OFFSET " . (int)$offset
);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// ---------- Contadores da barra lateral ----------
$contCategoria = $pdo->query("SELECT categoria, COUNT(*) n FROM produtos GROUP BY categoria")->fetchAll(PDO::FETCH_KEY_PAIR);

// Plataformas e gêneros respeitam a categoria escolhida (como na Nuuvem)
$whereFacet = '';
$paramsFacet = [];
if ($fCategorias) {
    $whereFacet = 'WHERE categoria IN (' . implode(',', array_fill(0, count($fCategorias), '?')) . ')';
    $paramsFacet = $fCategorias;
}
$stmtPlat = $pdo->prepare("SELECT plataforma, COUNT(*) n FROM produtos $whereFacet GROUP BY plataforma ORDER BY n DESC, plataforma ASC");
$stmtPlat->execute($paramsFacet);
$listaPlataformas = $stmtPlat->fetchAll(PDO::FETCH_KEY_PAIR);

$stmtGen = $pdo->prepare("SELECT genero, COUNT(*) n FROM produtos $whereFacet GROUP BY genero ORDER BY n DESC, genero ASC");
$stmtGen->execute($paramsFacet);
$listaGeneros = $stmtGen->fetchAll(PDO::FETCH_KEY_PAIR);

$totalCatalogo = (int)array_sum($contCategoria);
$filtrosAtivos = $fCategorias || $fPlataformas || $fGeneros || $busca !== '' || $precoMin !== null || $precoMax !== null || $soPromo || $soEstoque;

// Últimas keys do usuário logado (painel da home)
$ultimosPedidos = [];
if ($usuarioLogado) {
    $stmtPed = $pdo->prepare("
        SELECT p.*, pr.titulo AS produto_titulo
        FROM pedidos p
        INNER JOIN produtos pr ON pr.id = p.produto_id
        WHERE p.usuario_id = ?
        ORDER BY p.id DESC
        LIMIT 2
    ");
    $stmtPed->execute([$usuarioLogado['id']]);
    $ultimosPedidos = $stmtPed->fetchAll();
}

// Jogos em destaque para a esteira panorâmica (marquee lento) no topo da Loja
$stmtDest = $pdo->query("SELECT id, titulo, slug, icone, preco, preco_original FROM produtos WHERE categoria = 'Key de Jogo' ORDER BY destaque DESC, vendas DESC LIMIT 14");
$jogosEsteira = $stmtDest->fetchAll();
?>

<main class="container">
    <section>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'acesso_negado'): ?>
            <div class="alert alert-danger" id="alerta-acesso-negado" style="margin-top:24px;">
                <strong>Acesso restrito:</strong> a Área do Administrador é exclusiva para contas com privilégio de Administrador.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logout'): ?>
            <div class="alert alert-info" id="alerta-logout" style="margin-top:24px;">
                Você encerrou sua sessão com segurança. Volte sempre à <strong>Ghost Cycle</strong>!
            </div>
        <?php endif; ?>

        <!-- VITRINE EM ESTEIRA PANORÂMICA (ACIMA DO CARD DA LOJA OFICIAL) -->
        <div class="showcase-banner" id="esteira-destaques">
            <div class="hero-marquee" aria-hidden="true">
                <div class="hero-marquee-track">
                    <?php for ($loop = 0; $loop < 2; $loop++): ?>
                        <?php foreach ($jogosEsteira as $jg):
                            $imgDest = imagemProduto($jg);
                        ?>
                            <div class="hero-marquee-tile">
                                <?php if ($imgDest): ?>
                                    <img src="<?= htmlspecialchars($imgDest) ?>" alt="<?= htmlspecialchars($jg['titulo']) ?>">
                                <?php else: ?>
                                    <div class="tile-fallback">
                                        <i class="<?= htmlspecialchars($jg['icone']) ?>"></i>
                                        <span><?= htmlspecialchars($jg['titulo']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="showcase-overlay">
                <span class="showcase-kicker">GHOST CYCLE • KEYS OFICIAIS &amp; GIFT CARDS</span>
                <h2 class="showcase-headline">DOMINE CADA UNIVERSO. <span>SUA PRÓXIMA LENDA COMEÇA NO PRIMEIRO CÓDIGO.</span></h2>
                <p class="showcase-sub">De mundos sombrios a arenas competitivas: ativação instantânea para PC, PlayStation, Xbox e Nintendo.</p>
            </div>
        </div>

        <?php if ($usuarioLogado): ?>
            <!-- VISÃO LOGADO -->
            <div class="status-banner-auth" id="painel-home-logado">
                <div class="hero-grid">
                    <div>
                        <span class="hero-badge">Membro Ghost VIP</span>
                        <h1 class="hero-title">Olá, <?= htmlspecialchars($usuarioLogado['nick_gamer'] ?: $usuarioLogado['nome']) ?>!</h1>
                        <p class="hero-subtitle">Escolha seu Gift Card, Key ou Moeda abaixo e receba o código imediatamente após a compra.</p>
                        <div class="hero-actions">
                            <a href="comprar.php" class="btn btn-primary" id="btn-hero-comprar"><i class="fa-solid fa-cart-shopping"></i> Meu Carrinho &amp; Pedidos</a>
                            <a href="perfil.php" class="btn btn-outline" id="btn-hero-perfil">Minha Conta</a>
                            <?php if (ehAdmin()): ?>
                                <a href="admin.php" class="btn btn-outline" id="btn-hero-admin">Gerenciar usuários</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="hero-stats-box">
                        <div class="hero-wallet">
                            <div>
                                <div class="muted-label">Saldo Ghost Wallet</div>
                                <div class="wallet-value"><?= formatarMoeda((float)$usuarioLogado['saldo']) ?></div>
                            </div>
                            <div class="text-right">
                                <div class="muted-label">Conta</div>
                                <span class="role-tag <?= $usuarioLogado['tipo'] ?>"><?= $usuarioLogado['tipo'] ?></span>
                            </div>
                        </div>
                        <div class="muted-label mb-1">Suas últimas keys</div>
                        <?php if ($ultimosPedidos): ?>
                            <?php foreach ($ultimosPedidos as $up): ?>
                                <div class="mini-order">
                                    <span><?= htmlspecialchars($up['produto_titulo']) ?></span>
                                    <code><?= htmlspecialchars($up['key_gerada']) ?></code>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="muted-text">Você ainda não resgatou nenhuma key.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- VISÃO NÃO LOGADO (VISITANTE) -->
            <div class="status-banner-auth" id="painel-home-visitante">
                <div class="hero-grid">
                    <div>
                        <span class="hero-badge">Loja Oficial • Entrega Digital 24/7</span>
                        <h1 class="hero-title">Gift Cards, Keys e Moedas com entrega imediata</h1>
                        <p class="hero-subtitle">Crie sua conta grátis, pague via PIX, Cartão ou Ghost Wallet e acumule <strong>cashback imediato</strong> em todas as compras.</p>
                        <div class="hero-actions">
                            <a href="cadastro.php" class="btn btn-primary" id="btn-visitante-cadastro">Criar conta grátis</a>
                            <a href="login.php" class="btn btn-outline" id="btn-visitante-login">Entrar na conta</a>
                        </div>
                    </div>

                    <div class="hero-stats-box">
                        <div class="muted-label mb-1">Vantagens Ghost Cycle</div>
                        <ul class="hero-list">
                            <li><strong>Envio imediato:</strong> receba seu código PIN ou CD-Key na tela logo após a confirmação.</li>
                            <li><strong>Cashback garantido:</strong> parte do valor volta direto para sua carteira digital.</li>
                            <li><strong>Cofre de Keys:</strong> todo o seu histórico de códigos salvo em um só lugar.</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- CATEGORIAS -->
    <nav class="category-bar" aria-label="Categorias">
        <a href="index.php#catalogo" class="category-tab <?= !$fCategorias ? 'active' : '' ?>">Tudo <span class="tab-count"><?= $totalCatalogo ?></span></a>
        <?php foreach ($CATEGORIAS as $valor => [$rotulo, $icone]): ?>
            <a href="index.php?categoria[]=<?= urlencode($valor) ?>#catalogo" class="category-tab <?= ($fCategorias === [$valor]) ? 'active' : '' ?>"><?= htmlspecialchars($rotulo) ?> <span class="tab-count"><?= (int)($contCategoria[$valor] ?? 0) ?></span></a>
        <?php endforeach; ?>
        <a href="<?= htmlspecialchars(urlFiltro(['promo' => '1', 'ordem' => 'desconto'])) ?>" class="category-tab category-tab-promo <?= $soPromo ? 'active' : '' ?>">Ofertas</a>
    </nav>

    <div class="catalog-layout" id="catalogo">
        <!-- FILTROS -->
        <aside class="filter-sidebar">
            <form action="index.php" method="GET" id="form-filtros">
                <input type="hidden" name="ordem" value="<?= htmlspecialchars($ordem) ?>">

                <div class="filter-group search-group">
                    <div class="search-box">
                        <input type="text" name="busca" id="input-busca" placeholder="Buscar produto..." value="<?= htmlspecialchars($busca) ?>">
                        <button type="submit" class="btn btn-primary btn-sm" id="btn-buscar" aria-label="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </div>

                <details class="filter-group" open>
                    <summary>Categoria</summary>
                    <?php foreach ($CATEGORIAS as $valor => [$rotulo, $icone]): ?>
                        <label class="filter-option">
                            <input type="checkbox" name="categoria[]" value="<?= htmlspecialchars($valor) ?>" <?= marcado($fCategorias, $valor) ?> onchange="this.form.submit()">
                            <span class="filter-label"><?= htmlspecialchars($rotulo) ?></span>
                            <span class="filter-count"><?= (int)($contCategoria[$valor] ?? 0) ?></span>
                        </label>
                    <?php endforeach; ?>
                </details>

                <details class="filter-group" open>
                    <summary>Plataforma</summary>
                    <div class="filter-scroll">
                        <?php foreach ($listaPlataformas as $nome => $qtd): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="plataforma[]" value="<?= htmlspecialchars($nome) ?>" <?= marcado($fPlataformas, $nome) ?> onchange="this.form.submit()">
                                <span class="filter-label"><?= htmlspecialchars($nome) ?></span>
                                <span class="filter-count"><?= (int)$qtd ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>

                <details class="filter-group" open>
                    <summary>Gênero / Tipo</summary>
                    <div class="filter-scroll">
                        <?php foreach ($listaGeneros as $nome => $qtd): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="genero[]" value="<?= htmlspecialchars($nome) ?>" <?= marcado($fGeneros, $nome) ?> onchange="this.form.submit()">
                                <span class="filter-label"><?= htmlspecialchars($nome) ?></span>
                                <span class="filter-count"><?= (int)$qtd ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>

                <details class="filter-group" open>
                    <summary>Preço (R$)</summary>
                    <div class="price-range">
                        <input type="number" class="form-control" name="preco_min" id="preco_min" min="0" step="1" placeholder="Mín." value="<?= $precoMin !== null ? htmlspecialchars((string)$precoMin) : '' ?>">
                        <span>–</span>
                        <input type="number" class="form-control" name="preco_max" id="preco_max" min="0" step="1" placeholder="Máx." value="<?= $precoMax !== null ? htmlspecialchars((string)$precoMax) : '' ?>">
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm btn-block" id="btn-aplicar-preco">Aplicar preço</button>
                </details>

                <details class="filter-group" open>
                    <summary>Disponibilidade</summary>
                    <label class="filter-option">
                        <input type="checkbox" name="promo" value="1" <?= $soPromo ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="filter-label">Desconto de 10% ou mais</span>
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="estoque" value="1" <?= $soEstoque ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="filter-label">Somente com estoque</span>
                    </label>
                </details>

                <?php if ($filtrosAtivos): ?>
                    <a href="index.php#catalogo" class="btn btn-outline btn-block" id="btn-limpar-filtros">Limpar filtros</a>
                <?php endif; ?>
            </form>
        </aside>

        <!-- RESULTADOS -->
        <section class="catalog-results">
            <div class="results-toolbar">
                <div class="results-count" id="contador-resultados">
                    <strong><?= $totalResultados ?></strong> <?= $totalResultados === 1 ? 'produto encontrado' : 'produtos encontrados' ?>
                </div>
                <form action="index.php" method="GET" class="sort-form">
                    <?php foreach ($_GET as $k => $v): if (in_array($k, ['ordem', 'pagina'], true)) continue; ?>
                        <?php foreach ((array)$v as $item): ?>
                            <input type="hidden" name="<?= htmlspecialchars($k) ?><?= is_array($v) ? '[]' : '' ?>" value="<?= htmlspecialchars((string)$item) ?>">
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <label for="select-ordem">Ordenar por</label>
                    <select name="ordem" id="select-ordem" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($ORDENS as $chave => [$rotulo]): ?>
                            <option value="<?= $chave ?>" <?= $ordem === $chave ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if ($filtrosAtivos): ?>
                <div class="active-chips" id="filtros-ativos">
                    <?php foreach ($fCategorias as $c): ?>
                        <a href="<?= htmlspecialchars(urlSemValor('categoria', $c)) ?>" class="chip"><?= htmlspecialchars($CATEGORIAS[$c][0]) ?> <i class="fa-solid fa-xmark"></i></a>
                    <?php endforeach; ?>
                    <?php foreach ($fPlataformas as $c): ?>
                        <a href="<?= htmlspecialchars(urlSemValor('plataforma', $c)) ?>" class="chip"><?= htmlspecialchars($c) ?> <i class="fa-solid fa-xmark"></i></a>
                    <?php endforeach; ?>
                    <?php foreach ($fGeneros as $c): ?>
                        <a href="<?= htmlspecialchars(urlSemValor('genero', $c)) ?>" class="chip"><?= htmlspecialchars($c) ?> <i class="fa-solid fa-xmark"></i></a>
                    <?php endforeach; ?>
                    <?php if ($busca !== ''): ?>
                        <a href="<?= htmlspecialchars(urlFiltro(['busca' => null])) ?>" class="chip">"<?= htmlspecialchars($busca) ?>" <i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                    <?php if ($precoMin !== null || $precoMax !== null): ?>
                        <a href="<?= htmlspecialchars(urlFiltro(['preco_min' => null, 'preco_max' => null])) ?>" class="chip">
                            R$ <?= $precoMin !== null ? number_format($precoMin, 0, ',', '.') : '0' ?> – <?= $precoMax !== null ? number_format($precoMax, 0, ',', '.') : '∞' ?> <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($soPromo): ?>
                        <a href="<?= htmlspecialchars(urlFiltro(['promo' => null])) ?>" class="chip">Desconto ≥ 10% <i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                    <?php if ($soEstoque): ?>
                        <a href="<?= htmlspecialchars(urlFiltro(['estoque' => null])) ?>" class="chip">Com estoque <i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="products-grid" id="catalogo-produtos">
                <?php if (!$produtos): ?>
                    <div class="card-panel empty-state">
                        <i class="fa-solid fa-ghost"></i>
                        <h3>Nenhum produto encontrado</h3>
                        <p>Tente remover algum filtro ou buscar por outro termo.</p>
                        <a href="index.php#catalogo" class="btn btn-outline">Ver catálogo completo</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($produtos as $prod):
                        $img = imagemProduto($prod);
                        $desc = percentualDesconto($prod);
                        $semEstoque = (int)$prod['estoque'] <= 0;
                    ?>
                        <article class="product-card" data-produto-id="<?= (int)$prod['id'] ?>">
                            <div class="product-visual">
                                <?php if ($img): ?>
                                    <img class="product-cover" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($prod['titulo']) ?>" loading="lazy">
                                <?php else: ?>
                                    <i class="<?= htmlspecialchars($prod['icone']) ?> product-fallback-icon"></i>
                                <?php endif; ?>
                                <?php if ($desc > 0): ?>
                                    <span class="discount-badge">-<?= $desc ?>%</span>
                                <?php endif; ?>
                            </div>

                            <div class="product-body">
                                <div class="product-category"><?= htmlspecialchars($prod['plataforma']) ?> • <?= htmlspecialchars($prod['genero']) ?></div>
                                <h2 class="product-title"><?= htmlspecialchars($prod['titulo']) ?></h2>
                                <div class="product-meta"><?= htmlspecialchars($prod['categoria']) ?> • <?= htmlspecialchars($prod['regiao']) ?></div>

                                <div class="product-price-row">
                                    <?php if ($desc > 0): ?>
                                        <span class="price-old"><?= formatarMoeda((float)$prod['preco_original']) ?></span>
                                    <?php endif; ?>
                                    <span class="price-current"><?= formatarMoeda((float)$prod['preco']) ?></span>
                                    <div class="pix-discount-label">+<?= (int)$prod['cashback_percent'] ?>% cashback</div>
                                    <div class="stock-label <?= $semEstoque ? 'out' : '' ?>"><?= $semEstoque ? 'Esgotado' : (int)$prod['estoque'] . ' em estoque' ?></div>
                                </div>

                                <?php if ($semEstoque): ?>
                                    <button type="button" class="btn btn-outline btn-block" disabled>Indisponível</button>
                                <?php elseif ($usuarioLogado): ?>
                                    <a href="comprar.php?produto_id=<?= (int)$prod['id'] ?>" class="btn btn-primary btn-block btn-comprar-logado">Comprar agora</a>
                                <?php else: ?>
                                    <a href="login.php?aviso=login_obrigatorio&redirect=<?= urlencode('comprar.php?produto_id=' . (int)$prod['id']) ?>" class="btn btn-outline btn-block btn-comprar-visitante">Faça login para comprar</a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <nav class="pagination" aria-label="Paginação" id="paginacao">
                    <?php if ($paginaAtualN > 1): ?>
                        <a href="<?= htmlspecialchars(urlFiltro(['pagina' => $paginaAtualN - 1])) ?>"><i class="fa-solid fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a href="<?= htmlspecialchars(urlFiltro(['pagina' => $i])) ?>" class="<?= $i === $paginaAtualN ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($paginaAtualN < $totalPaginas): ?>
                        <a href="<?= htmlspecialchars(urlFiltro(['pagina' => $paginaAtualN + 1])) ?>"><i class="fa-solid fa-chevron-right"></i></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

