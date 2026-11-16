<?php
// ============================================================
// GHOST CYCLE - CONEXÃO E AUTO-INICIALIZAÇÃO DO BANCO DE DADOS
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ghost_cycle');

function obterConexao(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Caso o banco ghost_cycle ainda não exista na máquina, cria e popula automaticamente a partir de banco.sql
        try {
            $pdoRoot = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $sqlFile = __DIR__ . '/../database/banco.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdoRoot->exec($sql);

                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                $hashPadrao = password_hash('123456', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?");
                $stmt->execute([$hashPadrao]);
                return $pdo;
            }
        } catch (PDOException $inner) {
            die("Erro crítico ao conectar/inicializar o banco de dados Ghost Cycle: " . htmlspecialchars($inner->getMessage()));
        }
        die("Erro de conexão ao banco de dados: " . htmlspecialchars($e->getMessage()));
    }
}

$pdo = obterConexao();

// Funções auxiliares de sessão e autenticação
function estaLogado(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

function ehAdmin(): bool {
    return estaLogado() && isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';
}

function usuarioAtual(PDO $pdo): ?array {
    if (!estaLogado()) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        session_destroy();
        return null;
    }
    // Atualiza dados na sessão em tempo real (caso admin ou perfil tenha editado)
    $_SESSION['usuario_nome'] = $user['nome'];
    $_SESSION['usuario_email'] = $user['email'];
    $_SESSION['usuario_tipo'] = $user['tipo'];
    $_SESSION['usuario_saldo'] = $user['saldo'];
    $_SESSION['usuario_nick'] = $user['nick_gamer'];
    return $user;
}

// Retorna o caminho relativo da imagem do produto (assets/img/produtos/<id>.jpg|png|webp ou <slug>.*) ou null
function imagemProduto(array $produto): ?string {
    foreach ([(string)$produto['id'], $produto['slug']] as $nome) {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $rel = 'assets/img/produtos/' . $nome . '.' . $ext;
            if (is_file(__DIR__ . '/../' . $rel)) {
                return $rel;
            }
        }
    }
    return null;
}

function percentualDesconto(array $produto): int {
    $orig = (float)$produto['preco_original'];
    return $orig > 0 ? (int)round((1 - ((float)$produto['preco'] / $orig)) * 100) : 0;
}

function formatarMoeda(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function gerarKeyProduto(string $plataforma): string {
    $prefixo = 'GCYC';
    $plat = strtoupper($plataforma);
    $mapa = [
        'STEAM' => 'STM', 'RIOT' => 'RIOT', 'PLAYSTATION' => 'PSN', 'XBOX' => 'XBX',
        'GARENA' => 'FFBR', 'ROBLOX' => 'RBLX', 'NINTENDO' => 'NSW', 'GOOGLE' => 'GPLY',
        'APPLE' => 'APPL', 'BATTLE' => 'BNET', 'EPIC' => 'EPIC', 'ROCKSTAR' => 'RSTR',
        'DISCORD' => 'DSCD', 'EA APP' => 'EAPP',
    ];
    foreach ($mapa as $trecho => $pref) {
        if (strpos($plat, $trecho) !== false) {
            $prefixo = $pref;
            break;
        }
    }

    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $blocos = [$prefixo];
    for ($b = 0; $b < 4; $b++) {
        $bloco = '';
        for ($i = 0; $i < 4; $i++) {
            $bloco .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $blocos[] = $bloco;
    }
    return implode('-', $blocos);
}

