<?php
// Conexão com o banco de dados (usada por todas as páginas)
$host    = 'localhost';
$banco   = 'gerenciador_logistico';
$usuario = 'root';
$senha   = ''; // padrão do XAMPP: sem senha

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);        // mostra erros do SQL
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);   // resultados com nomes de coluna
} catch (PDOException $e) {
    die('Não foi possível conectar ao banco de dados: ' . $e->getMessage());
}