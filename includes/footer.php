<footer class="site-footer">
    <div class="container">
        <div class="footer-columns">
            <!-- Coluna 1: Sobre a Marca -->
            <div class="footer-col footer-about">
                <a href="index.php" class="brand-logo mb-1">
                    <img src="assets/img/logo-icon.png" alt="Ghost Cycle" class="logo-emblem">
                    <span class="brand-name">GHOST <span>CYCLE</span></span>
                </a>
                <p class="muted small">
                    Sua loja oficial de Gift Cards, CD-Keys de jogos e recargas para PC, PlayStation, Xbox, Nintendo e dispositivos móveis com entrega digital imediata 24/7.
                </p>
                <div class="footer-socials mt-2">
                    <a href="index.php" aria-label="Discord" title="Discord"><i class="fa-brands fa-discord"></i></a>
                    <a href="index.php" aria-label="Instagram" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="index.php" aria-label="X / Twitter" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="index.php" aria-label="YouTube" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>

            <!-- Coluna 2: Categorias do Catálogo -->
            <div class="footer-col">
                <h3 class="footer-title">Categorias</h3>
                <ul class="footer-links">
                    <li><a href="index.php?categoria[]=<?= urlencode('Gift Card') ?>#catalogo">Gift Cards Oficiais</a></li>
                    <li><a href="index.php?categoria[]=<?= urlencode('Key de Jogo') ?>#catalogo">Keys de Jogos (PC &amp; Console)</a></li>
                    <li><a href="index.php?categoria[]=<?= urlencode('Moedas & Top-Up') ?>#catalogo">Moedas &amp; Top-Up</a></li>
                    <li><a href="index.php?categoria[]=<?= urlencode('Assinatura') ?>#catalogo">Assinaturas Gamer</a></li>
                    <li><a href="index.php?promo=1&amp;ordem=desconto#catalogo">Ofertas em Destaque</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Plataformas Populares -->
            <div class="footer-col">
                <h3 class="footer-title">Plataformas</h3>
                <ul class="footer-links">
                    <li><a href="index.php?plataforma[]=Steam#catalogo">Steam</a></li>
                    <li><a href="index.php?plataforma[]=PlayStation#catalogo">PlayStation Store (PSN)</a></li>
                    <li><a href="index.php?plataforma[]=Xbox#catalogo">Xbox &amp; Game Pass</a></li>
                    <li><a href="index.php?plataforma[]=Nintendo#catalogo">Nintendo eShop</a></li>
                    <li><a href="index.php?plataforma[]=Riot+Games#catalogo">Riot Games &amp; Mobile</a></li>
                </ul>
            </div>

            <!-- Coluna 4: Minha Conta & Pagamentos -->
            <div class="footer-col">
                <h3 class="footer-title">Minha Conta &amp; Segurança</h3>
                <ul class="footer-links mb-2">
                    <?php if (!empty($usuarioLogado)): ?>
                        <li><a href="perfil.php">Minha Conta e Ghost Wallet</a></li>
                        <li><a href="comprar.php">Meu Carrinho e Biblioteca de Keys</a></li>
                        <li><a href="logout.php">Encerrar Sessão</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Entrar na Conta</a></li>
                        <li><a href="cadastro.php">Criar Conta Gratuita</a></li>
                        <li><a href="redefinir_senha.php">Recuperar Acesso</a></li>
                    <?php endif; ?>
                </ul>

                <div class="muted-label mb-1">Formas de Pagamento</div>
                <div class="footer-payments">
                    <span class="pay-badge"><i class="fa-brands fa-pix accent"></i> PIX</span>
                    <span class="pay-badge"><i class="fa-solid fa-wallet accent"></i> Ghost Wallet</span>
                    <span class="pay-badge"><i class="fa-brands fa-cc-visa"></i> Visa</span>
                    <span class="pay-badge"><i class="fa-brands fa-cc-mastercard"></i> Mastercard</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© <?= date('Y') ?> <strong>Ghost Cycle Digital Store</strong>. Todos os direitos reservados. As marcas mencionadas pertencem aos seus respectivos proprietários.</span>
            <div class="footer-seals">
                <span><i class="fa-solid fa-lock accent"></i> Ambiente 100% Seguro (SSL)</span>
                <span><i class="fa-solid fa-bolt accent"></i> Envio Automático 24/7</span>
            </div>
        </div>
    </div>
</footer>

<script>
function copiarKey(codigo, btnElement) {
    navigator.clipboard.writeText(codigo).then(() => {
        const originalHTML = btnElement.innerHTML;
        btnElement.innerHTML = 'Copiado!';
        setTimeout(() => {
            btnElement.innerHTML = originalHTML;
        }, 2000);
    });
}
</script>
</body>
</html>
