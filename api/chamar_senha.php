<?php
// Configurações do banco de dados
include 'conexao.php';


// Obter tipo da senha e setor do POST
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
$setor = isset($_POST['setor']) ? $_POST['setor'] : '';

// Normaliza o setor para minúsculas
$setor = strtolower($setor);

if (!$tipo || !$setor) {
    echo json_encode(['erro' => 'Tipo de senha ou setor não fornecido.']);
    exit;
}

// Mapeamento de setores para IDs
$setor_id = null;
if ($setor === 'secretaria') {
    $setor_id = 1; // 1 para Secretaria
} elseif ($setor === 'tesouraria') {
    $setor_id = 2; // 2 para Tesouraria
} else {
    echo json_encode(['erro' => 'Setor inválido.']);
    exit;
}

// Depuração: verificar valores do tipo e setor
error_log("Tipo: $tipo, Setor: $setor");

// Consulta para buscar a senha
$sql_senha = "SELECT * FROM senhas WHERE tipo = ? AND setor_id = ? AND status = 'gerada' ORDER BY id ASC LIMIT 1";
$stmt = $conn->prepare($sql_senha);

if (!$stmt) {
    echo json_encode(['erro' => 'Erro na preparação da consulta: ' . $conn->error]);
    exit;
}

$stmt->bind_param("si", $tipo, $setor_id); // "si" para aceitar um inteiro
$stmt->execute();
$result_senha = $stmt->get_result();

// Verifique se há alguma senha encontrada
if ($result_senha->num_rows > 0) {
    $senha = $result_senha->fetch_assoc();
    $senhaId = $senha['id'];

    // Depuração: Exibir a senha e ID retornados
    error_log("Senha encontrada: {$senha['senha']}, ID: $senhaId");

    // Atualiza o status da senha para 'chamada'
    $sql_update = "UPDATE senhas SET status = 'chamada', data_hora_chamada = NOW() WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);

    if (!$stmt_update) {
        echo json_encode(['erro' => 'Erro na preparação da atualização: ' . $conn->error]);
        exit;
    }

    $stmt_update->bind_param("i", $senhaId);

    if ($stmt_update->execute()) {
        // Caminhos dos arquivos JSON
        $senhaAtualFile = '../json/senha_atual.json';
        $senhasAnterioresFile = '../json/senhas_anteriores.json';

        // Recuperar a senha atual e as senhas anteriores
        $senha_atual = file_exists($senhaAtualFile) ? json_decode(file_get_contents($senhaAtualFile), true) : ['senha' => 'Nenhuma senha chamada', 'setor' => 'Nenhum setor'];
        $senhas_anteriores = file_exists($senhasAnterioresFile) ? json_decode(file_get_contents($senhasAnterioresFile), true) : [];

        // Somente mover a senha atual para o histórico se houver uma nova senha
        if ($senha_atual['senha'] !== 'Nenhuma senha chamada') {
            // Adicionar a senha atual ao início do array de senhas anteriores
            array_unshift($senhas_anteriores, $senha_atual);

            // Manter apenas as 3 últimas senhas
            $senhas_anteriores = array_slice($senhas_anteriores, 0, 3);

            // Salvar as senhas anteriores no arquivo
            file_put_contents($senhasAnterioresFile, json_encode($senhas_anteriores, JSON_PRETTY_PRINT));
        }

        // Salvar a nova senha como a senha atual
        file_put_contents($senhaAtualFile, json_encode(['senha' => $senha['senha'], 'setor' => ucfirst($setor)], JSON_PRETTY_PRINT));

        // Retorna a senha e informações adicionais como resposta
        echo json_encode([
            'senha' => $senha['senha'],
            'tipo' => $tipo,
            'setor' => ucfirst($setor)
        ]);
    } else {
        echo json_encode(['erro' => 'Erro ao atualizar status da senha: ' . $conn->error]);
    }

    $stmt_update->close();
} else {
    // Nenhuma senha encontrada
    echo json_encode(['erro' => 'Nenhuma senha disponível para este tipo e setor.']);
}

$stmt->close();
$conn->close();
?>
