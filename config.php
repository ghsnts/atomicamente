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
?>
