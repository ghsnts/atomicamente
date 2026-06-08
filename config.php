<?php
// config.php - Conexão Segura via PDO com Variáveis de Ambiente
// ================================================================

// Inicia output buffering para melhor controle de erros
ob_start();

// ============================================================
// HABILITAR DEBUG EM DESENVOLVIMENTO
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// CARREGAR VARIÁVEIS DE AMBIENTE
// ============================================================

$env_vars = [];
$env_file = __DIR__ . '/.env';

// Tenta carregar .env se existir
if (file_exists($env_file)) {
    $envContent = file_get_contents($env_file);
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Ignora comentários e linhas vazias
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                // Remove aspas se existirem
                $value = trim($value, '"\'');
                $env_vars[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

// ============================================================
// CONFIGURAÇÃO DE BANCO DE DADOS
// ============================================================
$db_host = $env_vars['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$db_name = $env_vars['DB_NAME'] ?? getenv('DB_NAME') ?: 'atomicamente_db';
$db_user = $env_vars['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$db_pass = $env_vars['DB_PASS'] ?? getenv('DB_PASS') ?: '';
$db_charset = $env_vars['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';
$app_env = $env_vars['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';

// Construir DSN
$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";

// Opções PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Tentar conectar
$pdo = null;
$connection_error = null;

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    $connection_error = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
    ];
    
    // Em desenvolvimento, mostra diagnóstico visual
    if ($app_env === 'development') {
        ob_end_clean(); // Limpa qualquer output anterior
        displayDevelopmentError($connection_error, $db_host, $db_name, $db_user, $env_file);
        exit;
    } else {
        // Em produção, mostra mensagem genérica
        ob_end_clean();
        die("❌ Desculpe, houve um erro na conexão com o servidor. Por favor, tente novamente mais tarde.");
    }
}

// ============================================================
// FUNÇÃO DE DIAGNÓSTICO VISUAL
// ============================================================
function displayDevelopmentError($error, $host, $db, $user, $env_file) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>❌ Erro de Conexão - Atomicamente</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
        }
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .content {
            padding: 30px;
        }
        .error-box {
            background: #fee;
            border-left: 4px solid #f00;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .error-box strong {
            color: #f00;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            color: #667eea;
            font-size: 1.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .config-table {
            width: 100%;
            border-collapse: collapse;
            background: #f9f9f9;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .config-table tr {
            border-bottom: 1px solid #e0e0e0;
        }
        .config-table tr:last-child {
            border-bottom: none;
        }
        .config-table td {
            padding: 12px 15px;
        }
        .config-table td:first-child {
            font-weight: bold;
            color: #333;
            width: 150px;
        }
        .config-table td:last-child {
            font-family: 'Courier New', monospace;
            color: #666;
        }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .checklist {
            background: #f0f8ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .checklist ul {
            list-style: none;
            padding-left: 0;
        }
        .checklist li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checklist li:before {
            content: '❌';
            font-weight: bold;
        }
        .checklist li.done:before {
            content: '✅';
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            margin: 10px 0;
            line-height: 1.5;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            border-radius: 0 0 12px 12px;
            font-size: 0.85rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>❌ Erro de Conexão com Banco de Dados</h1>
            <p>A aplicação não conseguiu conectar ao MySQL/MariaDB</p>
        </div>
        
        <div class="content">
            <!-- ERRO -->
            <div class="section">
                <h2>🔴 Erro Reportado</h2>
                <div class="error-box">
                    <strong>Mensagem:</strong> <?php echo htmlspecialchars($error['message']); ?><br>
                    <strong>Código:</strong> <?php echo htmlspecialchars($error['code']); ?>
                </div>
            </div>
            
            <!-- CONFIGURAÇÃO CARREGADA -->
            <div class="section">
                <h2>⚙️ Configuração Carregada</h2>
                <table class="config-table">
                    <tr>
                        <td>Host:</td>
                        <td><code><?php echo htmlspecialchars($host); ?></code></td>
                    </tr>
                    <tr>
                        <td>Banco:</td>
                        <td><code><?php echo htmlspecialchars($db); ?></code></td>
                    </tr>
                    <tr>
                        <td>Usuário:</td>
                        <td><code><?php echo htmlspecialchars($user); ?></code></td>
                    </tr>
                    <tr>
                        <td>Arquivo .env:</td>
                        <td class="<?php echo file_exists($env_file) ? 'status-ok' : 'status-error'; ?>">
                            <?php echo file_exists($env_file) ? '✅ Existe' : '❌ NÃO ENCONTRADO'; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- CHECKLIST -->
            <div class="section">
                <h2>✓ Checklist de Diagnóstico</h2>
                <div class="checklist">
                    <ul>
                        <li <?php echo file_exists($env_file) ? 'class="done"' : ''; ?>>
                            Arquivo <code>.env</code> existe
                        </li>
                        <li>
                            MySQL/MariaDB está rodando
                        </li>
                        <li>
                            Banco <code><?php echo htmlspecialchars($db); ?></code> foi criado
                        </li>
                        <li>
                            Usuário <code><?php echo htmlspecialchars($user); ?></code> existe
                        </li>
                        <li>
                            Credenciais estão corretas
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- SOLUÇÃO -->
            <div class="section">
                <h2>🔧 Solução Passo-a-Passo</h2>
                
                <h3 style="color: #333; margin: 15px 0 10px; font-size: 1rem;">1️⃣ Verificar Arquivo .env</h3>
                <p style="color: #666; margin-bottom: 10px;">Crie ou edite o arquivo <code>.env</code>:</p>
                <div class="code-block">DB_HOST=localhost
DB_NAME=atomicamente_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
APP_ENV=development
FORCE_HTTPS=false
ADMIN_EMAILS=seu_email@local.com</div>

                <h3 style="color: #333; margin: 15px 0 10px; font-size: 1rem;">2️⃣ Verificar MySQL</h3>
                <p style="color: #666; margin-bottom: 10px;">Certifique-se que o MySQL está rodando e acesse phpMyAdmin:</p>
                <div class="code-block">http://localhost/phpmyadmin/</div>

                <h3 style="color: #333; margin: 15px 0 10px; font-size: 1rem;">3️⃣ Criar/Verificar Banco</h3>
                <p style="color: #666; margin-bottom: 10px;">Execute no phpMyAdmin ou terminal:</p>
                <div class="code-block">CREATE DATABASE IF NOT EXISTS atomicamente_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</div>
            </div>
            
            <button onclick="location.reload();" style="
                padding: 12px 24px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                font-size: 1rem;
                width: 100%;
                margin-top: 20px;
            ">🔄 Tentar Novamente</button>
        </div>
        
        <div class="footer">
            <p>💡 <strong>Dica:</strong> Verifique o arquivo de log do Apache para mais detalhes.</p>
            <p>📍 <strong>Arquivo:</strong> <code><?php echo htmlspecialchars($env_file); ?></code></p>
        </div>
    </div>
</body>
</html>
    <?php
}

// ============================================================
// WHITELIST DE ADMINISTRADORAS (SEGURO)
// ============================================================
$adminEmailsEnv = $env_vars['ADMIN_EMAILS'] ?? getenv('ADMIN_EMAILS') ?: '';
if ($adminEmailsEnv) {
    $GLOBALS['ADMIN_EMAILS'] = array_map('trim', explode(',', $adminEmailsEnv));
} else {
    $GLOBALS['ADMIN_EMAILS'] = [];
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
if (($env_vars['FORCE_HTTPS'] ?? getenv('FORCE_HTTPS')) === 'true' && empty($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Define headers de segurança
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}

// Limpa output buffering
ob_end_clean();
?>
