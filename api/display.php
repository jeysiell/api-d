<?php
include 'conexao.php';

// Caminho dos arquivos
$senhaAtualFile = '../json/senha_atual.json';
$senhasAnterioresFile = '../json/senhas_anteriores.json';

// Recuperar a senha atual e as senhas anteriores
$senha_atual = file_exists($senhaAtualFile) ? json_decode(file_get_contents($senhaAtualFile), true) : ['senha' => 'Nenhuma senha chamada', 'setor' => 'Nenhum setor'];
$senhas_anteriores = file_exists($senhasAnterioresFile) ? json_decode(file_get_contents($senhasAnterioresFile), true) : [];

// Se a requisição for do tipo POST, salva a nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['senha']) && isset($_POST['setor'])) {
        $nova_senha = $_POST['senha'];
        
        // Receber o setor da senha e transformar a primeira letra em maiúscula
        $setor = ucfirst(strtolower($_POST['setor'])); // Converter o setor para minúsculo e depois capitalizar a primeira letra

        // Somente mover a senha atual para o histórico se houver uma nova senha
        if ($senha_atual['senha'] !== 'Nenhuma senha chamada') {
            // Adicionar a senha atual ao início do array de senhas anteriores
            array_unshift($senhas_anteriores, $senha_atual);

            // Manter apenas as 3 últimas senhas
            $senhas_anteriores = array_slice($senhas_anteriores, 0, 3);

            // Salvar as senhas anteriores no arquivo
            file_put_contents($senhasAnterioresFile, json_encode($senhas_anteriores));
        }

        // Salvar a nova senha como a senha atual
        file_put_contents($senhaAtualFile, json_encode(['senha' => $nova_senha, 'setor' => $setor]));
    } else {
        echo json_encode(['erro' => 'Senha ou setor não fornecido']);
        exit;
    }
}

// Definir o cabeçalho como JSON e retornar os dados
header('Content-Type: application/json');
echo json_encode(['atual' => json_decode(file_get_contents($senhaAtualFile), true), 'anteriores' => $senhas_anteriores]);
?>
