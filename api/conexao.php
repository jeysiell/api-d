<?php
$host = 'crossover.proxy.rlwy.net';
$port = 28606;
$usuario = 'root';
$senha = 'ZpLEtEBFLTrrFmtDxdiVKvWxqqgXvmuY';
$banco = 'railway';

// Criação da conexão
$conexao = new mysqli($host, $usuario, $senha, $banco, $port);

// Verificação da conexão
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Opcional: definir charset para UTF-8
$conexao->set_charset("utf8");

echo "Conectado com sucesso!";
?>
