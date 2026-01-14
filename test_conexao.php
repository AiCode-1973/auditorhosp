<?php
require_once 'db_config.php';

echo "=== TESTE DE CONEXÃO COM BANCO DE DADOS ===\n\n";

try {
    // Testar conexão
    echo "✓ Conexão estabelecida com sucesso!\n";
    echo "  Host: 186.209.113.107\n";
    echo "  Banco: dema5738_auditorhosp\n\n";
    
    // Listar tabelas
    echo "=== TABELAS EXISTENTES ===\n";
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach($tables as $table) {
        echo "✓ $table\n";
        
        // Contar registros
        $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "  → $count registros\n";
    }
    
    echo "\nTotal: " . count($tables) . " tabelas\n\n";
    
    // Testar algumas consultas básicas
    echo "=== ESTATÍSTICAS RÁPIDAS ===\n";
    
    // Convênios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM convenios");
    $total_convenios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✓ Convênios cadastrados: $total_convenios\n";
    
    // Internações
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM internacoes");
    $total_internacoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✓ Atendimentos registrados: $total_internacoes\n";
    
    // Relatório Mensal
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM relatorio_mensal_consolidado");
    $total_relatorios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✓ Relatórios mensais: $total_relatorios\n";
    
    // Usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✓ Usuários cadastrados: $total_usuarios\n";
    
    // Logs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs_atendimento");
    $total_logs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✓ Logs registrados: $total_logs\n";
    
    echo "\n✅ BANCO DE DADOS OPERACIONAL!\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO NA CONEXÃO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
