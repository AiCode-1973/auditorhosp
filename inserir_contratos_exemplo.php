<?php
require_once 'db_config.php';

echo "<h2>Inserindo Contratos de Exemplo</h2><br>";

try {
    $pdo->beginTransaction();
    
    // Buscar alguns convênios
    $stmt = $pdo->query("SELECT id, nome_convenio FROM convenios LIMIT 5");
    $convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($convenios) == 0) {
        echo "⚠️ Nenhum convênio encontrado. Cadastre convênios primeiro.<br>";
        exit;
    }
    
    $contratos_exemplo = [
        [
            'numero_contrato' => 'CT-2024-001',
            'data_inicio' => '2024-01-01',
            'data_fim' => '2024-12-31',
            'valor_contrato' => 500000.00,
            'observacoes' => 'Contrato anual vigente'
        ],
        [
            'numero_contrato' => 'CT-2025-001',
            'data_inicio' => '2025-01-01',
            'data_fim' => '2025-12-31',
            'valor_contrato' => 650000.00,
            'observacoes' => 'Novo contrato com reajuste'
        ],
        [
            'numero_contrato' => 'CT-2023-003',
            'data_inicio' => '2023-06-01',
            'data_fim' => '2024-05-31',
            'valor_contrato' => 450000.00,
            'observacoes' => 'Contrato vencido'
        ]
    ];
    
    $inseridos = 0;
    
    foreach ($convenios as $index => $convenio) {
        if (!isset($contratos_exemplo[$index])) break;
        
        $dados = $contratos_exemplo[$index];
        
        $sql = "INSERT INTO contratos (convenio_id, numero_contrato, data_inicio, data_fim, valor_contrato, observacoes, ativo, usuario_criacao) 
                VALUES (?, ?, ?, ?, ?, ?, 1, 1)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $convenio['id'],
            $dados['numero_contrato'],
            $dados['data_inicio'],
            $dados['data_fim'],
            $dados['valor_contrato'],
            $dados['observacoes']
        ]);
        
        echo "✓ Contrato <strong>{$dados['numero_contrato']}</strong> criado para <strong>{$convenio['nome_convenio']}</strong><br>";
        $inseridos++;
    }
    
    $pdo->commit();
    
    echo "<br><strong>✅ Total de $inseridos contratos criados!</strong><br>";
    echo "<br><a href='contratos.php'>→ Ver Contratos</a>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Erro: " . $e->getMessage();
}
?>
