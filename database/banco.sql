-- ============================================================
-- BANCO DE DADOS: ghost_cycle
-- SISTEMA: Ghost Cycle - Loja de Gift Cards e Keys de Jogos
-- PAPEL: Desenvolvedor Full Stack
-- (arquivo gerado - catálogo com 44 produtos e filtros estilo Nuuvem)
-- ============================================================

DROP DATABASE IF EXISTS ghost_cycle;
CREATE DATABASE ghost_cycle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ghost_cycle;

-- ------------------------------------------------------------
-- 1. USUÁRIOS (caminhos de login: comum e admin)
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    telefone VARCHAR(20) DEFAULT NULL,
    nick_gamer VARCHAR(80) DEFAULT NULL,
    senha VARCHAR(255) NOT NULL,
    palavra_seguranca VARCHAR(100) NOT NULL DEFAULT 'ghost',
    tipo ENUM('comum', 'admin') NOT NULL DEFAULT 'comum',
    saldo DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    avatar VARCHAR(100) DEFAULT 'cyber-ghost',
    status ENUM('ativo', 'suspenso') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 2. PRODUTOS (Gift Cards, Keys de Jogos, Moedas & Top-Up, Assinaturas)
-- genero  = gênero do jogo (Keys/Top-Up) ou tipo do produto (Gift Card/Assinatura)
-- vendas  = base para a ordenação "Mais vendidos"
-- A imagem do produto é lida de assets/img/produtos/<slug>.(jpg|png|webp)
-- ------------------------------------------------------------
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    categoria ENUM('Gift Card', 'Key de Jogo', 'Moedas & Top-Up', 'Assinatura') NOT NULL,
    plataforma VARCHAR(60) NOT NULL,
    genero VARCHAR(60) NOT NULL DEFAULT 'Outros',
    regiao VARCHAR(50) NOT NULL DEFAULT 'Brasil (Global)',
    entrega VARCHAR(60) NOT NULL DEFAULT 'Envio Imediato (24/7)',
    preco_original DECIMAL(10, 2) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    cashback_percent INT NOT NULL DEFAULT 3,
    estoque INT NOT NULL DEFAULT 50,
    destaque TINYINT(1) NOT NULL DEFAULT 0,
    badge VARCHAR(50) DEFAULT 'ENTREGA AUTOMÁTICA',
    icone VARCHAR(60) DEFAULT 'fa-gamepad',
    vendas INT NOT NULL DEFAULT 0,
    descricao TEXT NOT NULL,
    instrucoes_resgate TEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_plataforma (plataforma),
    INDEX idx_genero (genero)
);

-- ------------------------------------------------------------
-- 3. PEDIDOS (compra e resgate de Keys/PINs)
-- ------------------------------------------------------------
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_pedido VARCHAR(30) NOT NULL UNIQUE,
    usuario_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    valor_unitario DECIMAL(10, 2) NOT NULL,
    valor_total DECIMAL(10, 2) NOT NULL,
    cashback_ganho DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    metodo_pagamento ENUM('PIX Instantâneo', 'Saldo Ghost Wallet', 'Cartão de Crédito') NOT NULL,
    email_entrega VARCHAR(150) NOT NULL,
    key_gerada VARCHAR(255) NOT NULL,
    status_pedido ENUM('Aprovado - Key Liberada', 'Cancelado', 'Reembolsado') NOT NULL DEFAULT 'Aprovado - Key Liberada',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedido_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_pedido_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 4. LOGS DE REDEFINIÇÃO DE SENHA (auditoria)
-- ------------------------------------------------------------
CREATE TABLE logs_redefinicao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    ip_solicitante VARCHAR(45) NOT NULL,
    realizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ============================================================
-- CARGA INICIAL (a senha de todas as contas de teste é 123456;
-- o hash correto é aplicado por config/conexao.php / script de setup)
-- ============================================================
INSERT INTO usuarios (nome, email, cpf, telefone, nick_gamer, senha, palavra_seguranca, tipo, saldo, avatar, status) VALUES
('Administrador Ghost Master', 'admin@ghostcycle.com', '000.000.000-01', '(11) 99999-0001', 'GhostAdmin_01', 'TROCAR', 'masterkey', 'admin', 9999.00, 'admin-crown', 'ativo'),
('Lucas Phantom (Cliente QA)', 'cliente@ghostcycle.com', '111.222.333-44', '(11) 98888-7777', 'PhantomBR', 'TROCAR', 'eldenring', 'comum', 350.00, 'cyber-ghost', 'ativo'),
('Mariana Cyber (Cliente QA)', 'mariana@ghostcycle.com', '222.333.444-55', '(11) 97777-6666', 'MariValkyrie', 'TROCAR', 'valorant', 'comum', 180.00, 'neon-ninja', 'ativo'),
('Rafael Hunter (Cliente QA)', 'rafael@ghostcycle.com', '333.444.555-66', '(11) 96666-5555', 'RafaHunter', 'TROCAR', 'steam', 'comum', 95.50, 'stealth-wolf', 'ativo'),
('Conta Suspensa Teste', 'suspenso@ghostcycle.com', '444.555.666-77', '(11) 95555-4444', 'BannedUser', 'TROCAR', 'banido', 'comum', 0.00, 'cyber-ghost', 'suspenso');

INSERT INTO produtos (titulo, slug, categoria, plataforma, genero, regiao, entrega, preco_original, preco, cashback_percent, estoque, destaque, badge, icone, vendas, descricao, instrucoes_resgate) VALUES
('Gift Card Steam R$ 100,00 - Carteira Digital','gift-card-steam-r-100-00-carteira-digital','Gift Card','Steam','Loja de Games','Brasil (BRL)','PIN Digital Imediato',105.00,94.90,5,120,1,'MAIS VENDIDO','fa-brands fa-steam',980,'Recarregue sua Carteira Steam e compre jogos, DLCs e itens da comunidade com envio automático.','Abra a Steam > clique no seu nome > Detalhes da conta > Adicionar fundos > Resgatar um código de Carteira Steam e digite o PIN.'),
('Valorant Points - 2.150 VP (Riot Games Brasil)','valorant-points-2-150-vp-riot-games-brasil','Moedas & Top-Up','Riot Games','FPS / Tiro','Servidor Brasil','Código PIN Riot',89.90,79.90,4,85,1,'MAIS VENDIDO','fa-solid fa-crosshairs',870,'Desbloqueie o Passe de Batalha, skins e pacotes da loja rotativa do Valorant em segundos.','Na loja do jogo clique no ícone de moeda > Resgatar código e insira o PIN recebido.'),
('Elden Ring: Shadow of the Erdtree Edition - Key Steam','elden-ring-shadow-of-the-erdtree-edition-key-steam','Key de Jogo','Steam','RPG','Global / LATAM','Chave CD-Key Original',299.90,219.90,6,30,1,'KEY OFICIAL','fa-solid fa-dragon',640,'Jogo base premiado + a expansão Shadow of the Erdtree. Ativação direta na biblioteca Steam.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Gift Card PlayStation Store R$ 150,00 (PSN Brasil)','gift-card-playstation-store-r-150-00-psn-brasil','Gift Card','PlayStation','Loja de Games','PSN Brasil','Código 12 Dígitos',150.00,139.90,5,64,1,'ENVIO EM SEGUNDOS','fa-brands fa-playstation',720,'Saldo para a conta brasileira da PSN: jogos de PS4/PS5, moedas e assinaturas.','No PS4/PS5 abra a PlayStation Store > Resgatar códigos e digite o código de 12 dígitos.'),
('Xbox Game Pass Ultimate - 3 Meses (Código Digital)','xbox-game-pass-ultimate-3-meses-codigo-digital','Assinatura','Xbox','Assinatura de Games','Brasil (Global)','Key 25 Caracteres',179.90,144.90,5,42,0,'OFERTA GAMER','fa-brands fa-xbox',510,'Centenas de jogos no console, PC e nuvem, com lançamentos no Day One e EA Play incluso.','Acesse redeem.microsoft.com, faça login com sua conta Microsoft e digite os 25 caracteres.'),
('1.720 Diamantes Free Fire + Bônus Recarga','1-720-diamantes-free-fire-bonus-recarga','Moedas & Top-Up','Garena','FPS / Tiro','Servidor BR','PIN Recarga Jogo',69.90,54.90,4,200,0,'TOP RECARGA','fa-solid fa-fire-flame-curved',790,'PIN oficial Garena para 1.720 Diamantes no Free Fire: passes, incubadoras e eventos.','Acesse o site oficial Recarga Jogo > escolha Free Fire > informe seu ID e o PIN Garena recebido.'),
('Grand Theft Auto V: Edição Premium - Key Rockstar','grand-theft-auto-v-edicao-premium-key-rockstar','Key de Jogo','Rockstar','Ação','Global / Brasil','CD-Key Imediata',119.90,59.90,7,55,1,'OFERTA','fa-solid fa-car-burst',930,'Los Santos completa: campanha, GTA Online e o pacote Criminal Enterprise Starter.','Abra o Rockstar Games Launcher > Configurações > Resgatar código e cole a chave recebida.'),
('Robux 1.700 + Item Virtual Exclusivo - Gift Card Roblox','robux-1-700-item-virtual-exclusivo-gift-card-roblox','Gift Card','Roblox','Jogo Online','Global','PIN Oficial Roblox',109.90,92.50,5,90,0,'ITEM GRÁTIS','fa-solid fa-cubes',560,'Créditos Roblox para Robux ou Premium, com item cosmético exclusivo no resgate.','Acesse roblox.com/redeem, faça login e digite o PIN do cartão.'),
('Black Myth: Wukong Deluxe Edition - Key Steam PC','black-myth-wukong-deluxe-edition-key-steam-pc','Key de Jogo','Steam','Ação','Brasil / LATAM','CD-Key Imediata',279.90,199.90,6,25,0,'SUPER DESCONTO','fa-solid fa-wand-sparkles',420,'RPG de ação mitológico aclamado. Edição Deluxe com armas, armaduras e trilha sonora.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Gift Card Steam R$ 50,00 - Carteira Digital','gift-card-steam-r-50-00-carteira-digital','Gift Card','Steam','Loja de Games','Brasil (BRL)','PIN Digital Imediato',52.50,47.90,5,150,0,'ENTREGA IMEDIATA','fa-brands fa-steam',610,'Crédito de R$ 50 na Carteira Steam para jogos, DLCs e itens.','Abra a Steam > clique no seu nome > Detalhes da conta > Adicionar fundos > Resgatar um código de Carteira Steam e digite o PIN.'),
('Gift Card Steam R$ 200,00 - Carteira Digital','gift-card-steam-r-200-00-carteira-digital','Gift Card','Steam','Loja de Games','Brasil (BRL)','PIN Digital Imediato',210.00,189.90,5,70,0,'OFERTA','fa-brands fa-steam',380,'Crédito de R$ 200 na Carteira Steam para quem quer garantir vários lançamentos.','Abra a Steam > clique no seu nome > Detalhes da conta > Adicionar fundos > Resgatar um código de Carteira Steam e digite o PIN.'),
('Gift Card PlayStation Store R$ 100,00 (PSN Brasil)','gift-card-playstation-store-r-100-00-psn-brasil','Gift Card','PlayStation','Loja de Games','PSN Brasil','Código 12 Dígitos',100.00,93.90,5,90,0,'ENTREGA IMEDIATA','fa-brands fa-playstation',540,'R$ 100 de saldo na PlayStation Store brasileira para jogos, DLCs e moedas.','No PS4/PS5 abra a PlayStation Store > Resgatar códigos e digite o código de 12 dígitos.'),
('Gift Card Xbox R$ 100,00 (Brasil)','gift-card-xbox-r-100-00-brasil','Gift Card','Xbox','Loja de Games','Brasil (BRL)','Key 25 Caracteres',100.00,92.90,5,80,0,'ENTREGA IMEDIATA','fa-brands fa-xbox',450,'Saldo Microsoft para comprar jogos, add-ons e apps na Microsoft Store e no Xbox.','Acesse redeem.microsoft.com ou abra a Microsoft Store > perfil > Resgatar código e digite os 25 caracteres.'),
('Gift Card Nintendo eShop R$ 100,00','gift-card-nintendo-eshop-r-100-00','Gift Card','Nintendo','Loja de Games','Brasil (BRL)','Código 16 Dígitos',100.00,94.50,5,60,0,'ENTREGA IMEDIATA','fa-solid fa-gamepad',330,'Crédito para o Nintendo eShop: jogos digitais, DLCs e upgrades do Switch.','No Nintendo eShop selecione sua conta > Inserir código e digite o código de 16 dígitos.'),
('Gift Card Google Play R$ 100,00','gift-card-google-play-r-100-00','Gift Card','Google Play','Apps & Mobile','Brasil (BRL)','Código Digital',100.00,94.90,4,110,0,'ENTREGA IMEDIATA','fa-brands fa-google-play',500,'Saldo para apps, jogos mobile, filmes e assinaturas na Google Play.','Abra a Google Play Store > perfil > Pagamentos e assinaturas > Resgatar código de presente.'),
('Gift Card Apple (App Store e iTunes) R$ 100,00','gift-card-apple-app-store-e-itunes-r-100-00','Gift Card','Apple','Apps & Mobile','Brasil (BRL)','Código Digital',100.00,95.90,4,95,0,'ENTREGA IMEDIATA','fa-brands fa-apple',410,'Crédito para App Store, Apple Music, iCloud+ e muito mais no seu iPhone ou iPad.','Abra a App Store > seu perfil > Resgatar cartão-presente ou código e digite o código.'),
('Gift Card Battle.net R$ 100,00','gift-card-battle-net-r-100-00','Gift Card','Battle.net','Loja de Games','Brasil (BRL)','Código Digital',100.00,93.50,5,75,0,'ENTREGA IMEDIATA','fa-solid fa-shield-halved',270,'Saldo Blizzard para Diablo, Overwatch, World of Warcraft e Call of Duty.','Acesse battle.net/account/redeem, faça login e digite o código do cartão.'),
('Cyberpunk 2077: Ultimate Edition - Key Steam','cyberpunk-2077-ultimate-edition-key-steam','Key de Jogo','Steam','RPG','Global / LATAM','CD-Key Imediata',249.90,119.90,6,48,1,'OFERTA','fa-solid fa-robot',760,'Night City com o jogo base e a expansão Phantom Liberty em uma única chave.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('The Witcher 3: Wild Hunt - Complete Edition - Key Steam','the-witcher-3-wild-hunt-complete-edition-key-steam','Key de Jogo','Steam','RPG','Global / LATAM','CD-Key Imediata',129.90,39.90,6,90,1,'OFERTA','fa-solid fa-paw',850,'Um dos maiores RPGs de todos os tempos com todas as expansões incluídas.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Red Dead Redemption 2 - Key Rockstar','red-dead-redemption-2-key-rockstar','Key de Jogo','Rockstar','Aventura','Global / LATAM','CD-Key Imediata',249.90,129.90,6,40,1,'OFERTA','fa-solid fa-hat-cowboy',700,'Épico faroeste de mundo aberto da Rockstar, com o modo Red Dead Online.','Abra o Rockstar Games Launcher > Configurações > Resgatar código e cole a chave recebida.'),
('Resident Evil 4 Remake - Key Steam','resident-evil-4-remake-key-steam','Key de Jogo','Steam','Terror','Global / LATAM','CD-Key Imediata',249.00,149.90,6,35,0,'OFERTA','fa-solid fa-skull',480,'A reimaginação do clássico de terror com gráficos modernos e combate refinado.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('EA SPORTS FC 26 - Standard Edition - Key Steam','ea-sports-fc-26-standard-edition-key-steam','Key de Jogo','Steam','Esporte','Global / LATAM','CD-Key Imediata',299.90,199.90,5,60,1,'LANÇAMENTO','fa-solid fa-futbol',690,'O novo capítulo do futebol da EA com Ultimate Team e modos de carreira renovados.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Hollow Knight: Silksong - Key Steam','hollow-knight-silksong-key-steam','Key de Jogo','Steam','Indie','Global / LATAM','CD-Key Imediata',59.99,54.90,5,85,1,'LANÇAMENTO','fa-solid fa-bug',610,'A aguardada sequência de Hollow Knight: explore Pharloom com Hornet.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Hades II - Key Steam','hades-ii-key-steam','Key de Jogo','Steam','Indie','Global / LATAM','CD-Key Imediata',89.99,79.90,5,65,0,'FAVORITO','fa-solid fa-moon',440,'Roguelike de ação da Supergiant com Melinoë, a princesa imortal do Submundo.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Forza Horizon 5 - Key Steam','forza-horizon-5-key-steam','Key de Jogo','Steam','Corrida','Global / LATAM','CD-Key Imediata',249.90,99.90,6,52,0,'OFERTA','fa-solid fa-car-side',530,'Corrida em mundo aberto pelo México com centenas de carros.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Baldur''s Gate 3 - Key Steam','baldur-s-gate-3-key-steam','Key de Jogo','Steam','RPG','Global / LATAM','CD-Key Imediata',199.90,169.90,6,44,1,'JOGO DO ANO','fa-solid fa-dice-d20',720,'RPG premiado com escolhas que mudam a história, solo ou em cooperativo.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Battlefield 6 - Key Steam','battlefield-6-key-steam','Key de Jogo','Steam','FPS / Tiro','Global / LATAM','CD-Key Imediata',299.90,249.90,5,50,1,'LANÇAMENTO','fa-solid fa-person-rifle',650,'Guerra total com destruição em larga escala e batalhas de até 128 jogadores.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Stardew Valley - Key Steam','stardew-valley-key-steam','Key de Jogo','Steam','Simulação','Global / LATAM','CD-Key Imediata',24.99,21.90,4,120,0,'CLÁSSICO','fa-solid fa-seedling',570,'Herde a fazenda do seu avô e construa a vida dos sonhos no campo.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Hogwarts Legacy - Key Steam','hogwarts-legacy-key-steam','Key de Jogo','Steam','Aventura','Global / LATAM','CD-Key Imediata',249.90,79.90,6,58,0,'OFERTA','fa-solid fa-hat-wizard',590,'RPG de ação no mundo bruxo do século XIX, com Hogwarts para explorar.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Sekiro: Shadows Die Twice - GOTY Edition - Key Steam','sekiro-shadows-die-twice-goty-edition-key-steam','Key de Jogo','Steam','Ação','Global / LATAM','CD-Key Imediata',229.90,149.90,6,33,0,'OFERTA','fa-solid fa-user-ninja',400,'Ação de samurai da FromSoftware. Edição Jogo do Ano com conteúdo extra.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Sid Meier''s Civilization VI - Key Steam','sid-meier-s-civilization-vi-key-steam','Key de Jogo','Steam','Estratégia','Global / LATAM','CD-Key Imediata',199.90,49.90,5,70,0,'OFERTA','fa-solid fa-chess-rook',350,'Construa um império que resista ao teste do tempo em estratégia por turnos.','No cliente Steam, clique em "Jogos" > "Ativar um produto na Steam..." e cole a chave recebida.'),
('Alan Wake 2 - Key Epic Games','alan-wake-2-key-epic-games','Key de Jogo','Epic Games','Terror','Global / LATAM','CD-Key Imediata',169.90,99.90,6,28,0,'OFERTA','fa-solid fa-lightbulb',310,'Survival horror psicológico da Remedy: alterne entre Saga Anderson e Alan Wake.','Acesse store.epicgames.com > sua conta > Resgatar código e informe a chave recebida.'),
('Valorant Points - 1.000 VP (Riot Games Brasil)','valorant-points-1-000-vp-riot-games-brasil','Moedas & Top-Up','Riot Games','FPS / Tiro','Servidor Brasil','Código PIN Riot',44.90,39.90,4,140,0,'ENTREGA IMEDIATA','fa-solid fa-crosshairs',640,'Pacote de 1.000 VP para skins e itens do Valorant.','Na loja do jogo clique no ícone de moeda > Resgatar código e insira o PIN recebido.'),
('League of Legends - 3.500 Riot Points (RP)','league-of-legends-3-500-riot-points-rp','Moedas & Top-Up','Riot Games','MOBA','Servidor Brasil','Código PIN Riot',109.90,99.90,4,110,1,'MAIS VENDIDO','fa-solid fa-chess-knight',760,'Riot Points para skins, campeões e passe de batalha no League of Legends.','Na loja do jogo clique no ícone de moeda > Resgatar código e insira o PIN recebido.'),
('Fortnite - 5.000 V-Bucks','fortnite-5-000-v-bucks','Moedas & Top-Up','Epic Games','Battle Royale','Global','Código Digital',199.90,179.90,4,75,1,'MAIS VENDIDO','fa-solid fa-umbrella',830,'V-Bucks para o Passe de Batalha, skins e emotes do Fortnite.','Acesse epicgames.com/fortnite/vbucks-card > faça login > informe o código do cartão V-Bucks.'),
('Free Fire - 520 Diamantes','free-fire-520-diamantes','Moedas & Top-Up','Garena','FPS / Tiro','Servidor BR','PIN Recarga Jogo',24.90,21.90,4,260,0,'TOP RECARGA','fa-solid fa-fire-flame-curved',720,'Recarga rápida de 520 Diamantes para o Free Fire.','Acesse o site oficial Recarga Jogo > escolha Free Fire > informe seu ID e o PIN Garena recebido.'),
('Call of Duty Mobile - 2.400 CP','call-of-duty-mobile-2-400-cp','Moedas & Top-Up','Mobile','FPS / Tiro','Global','PIN Recarga Jogo',99.90,89.90,4,95,0,'ENTREGA IMEDIATA','fa-solid fa-person-military-rifle',410,'COD Points para o Passe de Batalha e caixas do Call of Duty Mobile.','Acesse o centro de recargas oficial do jogo, informe seu ID de jogador e o código recebido.'),
('Genshin Impact - 6.480 Cristais Gênesis','genshin-impact-6-480-cristais-genesis','Moedas & Top-Up','Mobile','RPG','Global','Código Digital',599.90,549.90,4,40,0,'PACOTE GRANDE','fa-solid fa-gem',380,'Cristais Gênesis para convocar personagens e armas em Genshin Impact.','Acesse o centro de recargas oficial do jogo, informe seu ID de jogador e o código recebido.'),
('PUBG Mobile - 1.800 UC','pubg-mobile-1-800-uc','Moedas & Top-Up','Mobile','FPS / Tiro','Global','PIN Recarga Jogo',119.90,107.90,4,85,0,'ENTREGA IMEDIATA','fa-solid fa-parachute-box',360,'Unknown Cash para Royale Pass e skins exclusivas no PUBG Mobile.','Acesse o centro de recargas oficial do jogo, informe seu ID de jogador e o código recebido.'),
('EA SPORTS FC 26 - 2.800 FC Points','ea-sports-fc-26-2-800-fc-points','Moedas & Top-Up','Multiplataforma','Esporte','Global','Código Digital',119.90,109.90,4,100,0,'ULTIMATE TEAM','fa-solid fa-futbol',470,'FC Points para pacotes de jogadores no Ultimate Team do EA SPORTS FC 26.','Abra a loja do jogo na sua plataforma > Resgatar código e informe o PIN recebido.'),
('PlayStation Plus Essential - 3 Meses (PSN Brasil)','playstation-plus-essential-3-meses-psn-brasil','Assinatura','PlayStation','Assinatura de Games','PSN Brasil','Código Digital',149.90,129.90,5,55,1,'MAIS VENDIDO','fa-brands fa-playstation',620,'Multiplayer online, jogos mensais e descontos exclusivos na PS Store.','Na PlayStation Store > Resgatar códigos, informe o código da assinatura (conta PSN Brasil).'),
('PlayStation Plus Extra - 12 Meses (PSN Brasil)','playstation-plus-extra-12-meses-psn-brasil','Assinatura','PlayStation','Assinatura de Games','PSN Brasil','Código Digital',549.90,489.90,5,30,0,'MELHOR CUSTO','fa-brands fa-playstation',330,'Catálogo com centenas de jogos de PS4 e PS5 para baixar e jogar.','Na PlayStation Store > Resgatar códigos, informe o código da assinatura (conta PSN Brasil).'),
('Nintendo Switch Online - 12 Meses (Individual)','nintendo-switch-online-12-meses-individual','Assinatura','Nintendo','Assinatura de Games','Brasil (BRL)','Código 16 Dígitos',99.90,89.90,5,45,0,'OFERTA GAMER','fa-solid fa-gamepad',290,'Online no Switch, saves na nuvem e catálogo de jogos clássicos.','No Nintendo eShop > Nintendo Switch Online > Inserir código e digite o código recebido.'),
('Xbox Game Pass para PC - 3 Meses','xbox-game-pass-para-pc-3-meses','Assinatura','Xbox','Assinatura de Games','Brasil (Global)','Key 25 Caracteres',119.90,99.90,5,50,0,'OFERTA GAMER','fa-brands fa-xbox',340,'Biblioteca de jogos de PC com lançamentos Day One da Xbox Game Studios.','Acesse redeem.microsoft.com, faça login com sua conta Microsoft e digite os 25 caracteres.'),
('EA Play - 12 Meses','ea-play-12-meses','Assinatura','EA App','Assinatura de Games','Global','Key Digital',199.90,169.90,5,38,0,'FIFA & MAIS','fa-solid fa-futbol',210,'Acesso ao catálogo EA, testes antecipados e recompensas mensais.','Abra o EA App > seu perfil > Resgatar código e informe a chave recebida.'),
('Discord Nitro - 1 Mês','discord-nitro-1-mes','Assinatura','Discord','Comunidade','Global','Código Digital',49.90,44.90,4,120,0,'ENTREGA IMEDIATA','fa-brands fa-discord',480,'Emojis, uploads maiores, streaming em HD e boosts de servidor.','Acesse discord.com/billing/promotions, faça login e informe o código recebido.');

INSERT INTO pedidos (codigo_pedido, usuario_id, produto_id, quantidade, valor_unitario, valor_total, cashback_ganho, metodo_pagamento, email_entrega, key_gerada, status_pedido) VALUES
('GC-2026-8841', 2, 1, 1, 94.90, 94.90, 4.75, 'PIX Instantâneo', 'cliente@ghostcycle.com', 'STM-GC99-774K-X91M-BR26', 'Aprovado - Key Liberada'),
('GC-2026-9102', 2, 2, 1, 79.90, 79.90, 3.20, 'Saldo Ghost Wallet', 'cliente@ghostcycle.com', 'RIOT-VPBR-8821-KLP9-2026', 'Aprovado - Key Liberada');
