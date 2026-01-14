<?php
require_once 'db_config.php';

echo "=== VERIFICAÇÃO DO MÓDULO DE CONTRATOS ===\n\n";

try {
    // Verificar tabela
    $stmt = $pdo->query("SHOW TABLES LIKE 'contratos'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabela 'contratos' criada\n";
    } else {
        echo "❌ Tabela 'contratos' não encontrada\n";
        exit;
    }
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contratos");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "📊 Total de contratos cadastrados: $total\n\n";
    
    // Listar contratos
    if ($total > 0) {
        echo "=== CONTRATOS CADASTRADOS ===\n";
        $stmt = $pdo->query("
            SELECT ct.numero_contrato, c.nome_convenio, ct.data_inicio, ct.data_fim, ct.ativo
            FROM contratos ct
            JOIN convenios c ON ct.convenio_id = c.id
            ORDER BY ct.id
        ");
        $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($contratos as $ct) {
            $status = $ct['ativo'] ? '✓ Ativo' : '✗ Inativo';
            echo "• {$ct['numero_contrato']} - {$ct['nome_convenio']} ({$status})\n";
            echo "  Vigência: {$ct['data_inicio']} até " . ($ct['data_fim'] ?: 'Indeterminado') . "\n";
        }
    }
    
    // Verificar pasta
    echo "\n=== PASTA DE UPLOADS ===\n";
    $pasta = __DIR__ . '/uploads/contratos';
    if (is_dir($pasta)) {
        echo "✅ Pasta existe: uploads/contratos/\n";
        echo "📁 Permissões: " . substr(sprintf('%o', fileperms($pasta)), -4) . "\n";
    } else {
        echo "❌ Pasta não encontrada\n";
    }
    
    echo "\n=== ACESSO AO MÓDULO ===\n";
    echo "🔗 URL: http://localhost/auditorhosp/contratos.php\n";
    echo "📝 Formulário: http://localhost/auditorhosp/contratos_form.php\n";
    
    echo "\n✅ MÓDULO DE CONTRATOS OPERACIONAL!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
