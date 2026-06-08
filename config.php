<?php
// config.php - Conexão Segura via PDO com Variáveis de Ambiente
// ================================================================

// Carrega variáveis de ambiente do arquivo .env se existir
if (file_exists(__DIR__ . '/.env')) {
    $envFile = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envFile);
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignora comentários e linhas vazias
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove aspas se existirem
            $value = trim($value, '"\'');
            putenv("$key=$value");
        }
    }
}

// ============================================================
// CONFIGURAÇÃO DE BANCO DE DADOS
// ============================================================
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'atomicamente_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Em desenvolvimento, mostra o erro
    if (getenv('APP_ENV') === 'development') {
        die("Erro crítico de ligação: " . $e->getMessage());
    } else {
        // Em produção, mostra mensagem genérica por segurança
        die("Desculpe, houve um erro na conexão com o servidor. Por favor, tente novamente mais tarde.");
    }
}

// ============================================================
// WHITELIST DE ADMINISTRADORAS (SEGURO)
// ============================================================
$adminEmailsEnv = getenv('ADMIN_EMAILS');
if ($adminEmailsEnv) {
    // Se estiver em variável de ambiente, usa essa
    $GLOBALS['ADMIN_EMAILS'] = array_map('trim', explode(',', $adminEmailsEnv));
} else {
    // Fallback para desenvolvimento (REMOVA EM PRODUÇÃO)
    $GLOBALS['ADMIN_EMAILS'] = [
        // Adicione aqui os emails de administradores
        // Exemplo:
        // 'seu_email@exemplo.com',
        // 'outro_admin@exemplo.com',
    ];
}

// ============================================================
// FUNÇÃO AUXILIAR PARA VERIFICAR SE USUÁRIO É ADMIN
// ============================================================
function verificarSeEhAdmin() {
    if (!isset($_SESSION['user_email'])) {
        return false;
    }
    return in_array($_SESSION['user_email'], $GLOBALS['ADMIN_EMAILS']);
}

// ============================================================
// SEGURANÇA ADICIONAL
// ============================================================

// Força HTTPS em produção
if (getenv('FORCE_HTTPS') === 'true' && empty($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Define headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
?>
