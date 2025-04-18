<?php
$host = '192.168.1.55';
$dbname = 'web2';
$user = 'postgres';
$pass = 'dsds';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
