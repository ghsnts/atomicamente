<?php
// config.php - Conexão Base Segura via PDO
$host = 'localhost';
$db   = 'atomicamente_db';
$user = 'root';
$pass = 'vertrigo'; // No Vertrigo a senha padrão do root costuma ser vazia ou 'vertrigo'
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Erro crítico de ligação: " . $e->getMessage());
}

// WHITELIST DE ADMINISTRADORAS (Apenas estes emails têm acesso ao Painel Admin)
$GLOBALS['ADMIN_EMAILS'] = [
    'gustavo4.santos@alunos.ifsuldeminas.edu.br',
    'mfernanda.cardoso20@gmail.com',
    'lialvarenga8888@gmail.com',
    'anajuliag903@gmail.com',
    'larissa3.carvalho@alunos.ifsuldeminas.edu.br',
    'ritacborges070209@gmail.com',
    'ana4.rosa@alunos.ifsuldeminas.edu.br',
    'maria2.cardoso@alunos.ifsuldeminas.edu.br' // Adiciona aqui o teu email para testares!
];

// Função auxiliar para verificar se o usuário atual é admin
function verificarSeEhAdmin() {
    if (!isset($_SESSION['user_email'])) {
        return false;
    }
    return in_array($_SESSION['user_email'], $GLOBALS['ADMIN_EMAILS']);
}
?>
