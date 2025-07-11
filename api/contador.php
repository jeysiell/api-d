<?php
// Conexão com o banco de dados
include 'conexao.php';

// Obter tipo da senha e setor do POST
$tipo = $_POST['tipo'] ?? '';
$setor = $_POST['setor'] ?? '';

// Mapeamento de setores e tipos permitidos
$setores = [
    'secretaria' => [
        'id' => 1,
        'tipos' => ['matricula', 'rematricula', 'atendimento', 'bolsa']
    ],
    'tesouraria' => [
        'id' => 2,
        'tipos' => ['pagamentos', '2viaboleto']
    ]
];

// Validação
if (!isset($setores[$setor])) {
    echo json_encode(['erro' => 'Setor inválido']);
    exit;
}

if (!in_array($tipo, $setores[$setor]['tipos'])) {
    echo json_encode(['erro' => 'Tipo de senha inválido para este setor']);
    exit;
}

// Executar consulta SQL
$sql = "SELECT COUNT(*) AS total FROM senhas WHERE status = 'gerada' AND setor_id = :setor_id AND tipo = :tipo";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':setor_id' => $setores[$setor]['id'],
    ':tipo' => $tipo
]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Resposta JSON
if ($result) {
    echo json_encode(['total' => (int)$result['total']]);
} else {
    echo json_encode(['total' => 0]);
}
?>
