<?php
// Para exibir erro em texto plano (sem HTML)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: text/plain"); // <-- muda de application/json para texto

// Libera CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// Configuração do banco de dados
$db_config = [
    'host' => 'crossover.proxy.rlwy.net',
    'port' => 28606,
    'user' => 'root',
    'password' => 'ZpLEtEBFLTrrFmtDxdiVKvWxqqgXvmuY',
    'database' => 'railway'
];

// Função para conectar ao banco de dados
function conectarBanco($config) {
    $conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
    if ($conn->connect_error) {
        die(json_encode(['erro' => "Falha na conexão: " . $conn->connect_error]));
    }
    return $conn;
}

// Função para obter o próximo número de senha
function obterProximoNumeroSenha($tipo, $setor_id) {
    $conn = conectarBanco($GLOBALS['db_config']);
    $prefixo = "";

    if ($setor_id == 1) {
        $prefixo = [
            "bolsa" => "B",
            "matricula" => "M",
            "rematricula" => "R",
            "atendimento" => "A"
        ][$tipo] ?? "";
    } elseif ($setor_id == 2) {
        $prefixo = [
            "pagamentos" => "P",
            "2viaboleto" => "V"
        ][$tipo] ?? "";
    } else {
        return null;
    }

    if ($prefixo === "") return null;

    $data_atual = date('Y-m-d');
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(senha, LENGTH(?) + 1) AS UNSIGNED)) 
                            FROM senhas 
                            WHERE setor_id = ? 
                            AND senha LIKE CONCAT(?, '%') 
                            AND DATE(data_hora_geracao) = ?");
    $stmt->bind_param("siss", $prefixo, $setor_id, $prefixo, $data_atual);
    $stmt->execute();
    $stmt->bind_result($ultimo_numero);
    $stmt->fetch();
    $stmt->close();

    $proximo_numero = ($ultimo_numero ?? 0) + 1;
    $conn->close();

    return $prefixo . str_pad($proximo_numero, 3, '0', STR_PAD_LEFT);
}

// Roteamento principal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $setor_id = isset($_POST['setor_id']) ? (int)$_POST['setor_id'] : 0;

    if (!$tipo || !$setor_id) {
        echo json_encode(['erro' => 'Parâmetros ausentes']);
        exit;
    }

    $senha_gerada = obterProximoNumeroSenha($tipo, $setor_id);
    if ($senha_gerada) {
        $conn = conectarBanco($db_config);
        $stmt = $conn->prepare("INSERT INTO senhas (senha, tipo, setor_id, status, data_hora_geracao) VALUES (?, ?, ?, 'gerada', NOW())");
        $stmt->bind_param("ssi", $senha_gerada, $tipo, $setor_id);
        $stmt->execute();

        if ($stmt->error) {
            echo json_encode(['erro' => "Erro ao inserir no banco: " . $stmt->error]);
        } else {
            echo json_encode(['senha' => $senha_gerada]);
        }

        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(['erro' => 'Erro ao gerar a senha.']);
    }

    exit;
}

echo json_encode(['erro' => 'Requisição inválida']);
