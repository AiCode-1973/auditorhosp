<?php
require_once 'db_config.php';

echo "<h2>Limpeza Imediata - Registros Vazios</h2><hr>";

try {
    // Deletar registros com setor vazio
    $sql = "DELETE FROM relatorio_mensal_pa_consolidado WHERE setor IS NULL OR setor = ''";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $deletados = $stmt->rowCount();
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong style='color: #155724;'>✓ Limpeza concluída com sucesso!</strong><br>";
    echo "Registros deletados: <strong>$deletados</strong>";
    echo "</div>";
    
    // Verificar se ainda existem registros vazios
    $sql_check = "SELECT COUNT(*) as total FROM relatorio_mensal_pa_consolidado WHERE setor IS NULL OR setor = ''";
    $stmt_check = $pdo->query($sql_check);
    $ainda_vazios = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($ainda_vazios == 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
        echo "<strong style='color: #155724;'>✓ Nenhum registro vazio restante!</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
        echo "<strong style='color: #721c24;'>⚠ Ainda existem $ainda_vazios registros vazios!</strong>";
        echo "</div>";
    }
    
    // Mostrar registros restantes
    echo "<h3>Registros Restantes na Tabela Consolidada:</h3>";
    $sql_list = "SELECT id, DATE_FORMAT(competencia, '%m/%Y') as comp, convenio_id, setor, qtd_atendimentos 
                 FROM relatorio_mensal_pa_consolidado 
                 ORDER BY competencia DESC, convenio_id";
    $stmt_list = $pdo->query($sql_list);
    $registros = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Competência</th><th>Convênio</th><th>Setor</th><th>Qtd</th></tr>";
    foreach ($registros as $r) {
        echo "<tr>";
        echo "<td>" . $r['id'] . "</td>";
        echo "<td>" . $r['comp'] . "</td>";
        echo "<td>" . $r['convenio_id'] . "</td>";
        echo "<td>" . htmlspecialchars($r['setor']) . "</td>";
        echo "<td>" . $r['qtd_atendimentos'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<strong style='color: #721c24;'>Erro: " . $e->getMessage() . "</strong>";
    echo "</div>";
}

echo "<br><a href='consolidar_pa_ambulatorio.php' style='display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir para Consolidação</a>";
echo " <a href='relatorio_mensal_pa_ambulatorio.php' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Ir para Relatório</a>";
?>
