<?php
require_once 'db_config.php';

echo "<h2>Teste e Correção Definitiva</h2><hr>";

// 1. Mostrar EXATAMENTE o que existe no banco (com detalhes de bytes)
echo "<h3>1. Registros Consolidados (análise de bytes):</h3>";
$sql = "SELECT id, DATE_FORMAT(competencia, '%m/%Y') as comp, convenio_id, setor, 
        LENGTH(setor) as tamanho, 
        HEX(setor) as hex_value,
        qtd_atendimentos
        FROM relatorio_mensal_pa_consolidado 
        WHERE competencia = '2026-01-01' AND convenio_id IN (6, 9)
        ORDER BY convenio_id, id";
$stmt = $pdo->query($sql);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Comp</th><th>Conv</th><th>Setor</th><th>Tamanho</th><th>HEX</th><th>Qtd</th></tr>";
foreach ($registros as $r) {
    $setor_display = $r['setor'] === null ? '[NULL]' : ($r['setor'] === '' ? '[VAZIO]' : htmlspecialchars($r['setor']));
    $cor = ($r['tamanho'] == 0 || $r['setor'] === null) ? 'background: #ffcccc;' : '';
    echo "<tr style='$cor'>";
    echo "<td>" . $r['id'] . "</td>";
    echo "<td>" . $r['comp'] . "</td>";
    echo "<td>" . $r['convenio_id'] . "</td>";
    echo "<td>" . $setor_display . "</td>";
    echo "<td>" . $r['tamanho'] . "</td>";
    echo "<td>" . $r['hex_value'] . "</td>";
    echo "<td>" . $r['qtd_atendimentos'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// 2. DELETAR todos os registros problemáticos (vazio ou NULL)
echo "<h3>2. Executando Limpeza Forçada:</h3>";
try {
    $pdo->beginTransaction();
    
    // Deletar por condições múltiplas
    $sql_delete = "DELETE FROM relatorio_mensal_pa_consolidado 
                   WHERE setor IS NULL 
                      OR setor = '' 
                      OR LENGTH(setor) = 0
                      OR TRIM(setor) = ''";
    $stmt_del = $pdo->prepare($sql_delete);
    $stmt_del->execute();
    $deletados = $stmt_del->rowCount();
    
    $pdo->commit();
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong style='color: #155724;'>✓ Deletados: $deletados registros</strong>";
    echo "</div>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<strong style='color: #721c24;'>Erro: " . $e->getMessage() . "</strong>";
    echo "</div>";
}

// 3. Verificar o que restou
echo "<h3>3. Registros Após Limpeza:</h3>";
$sql_after = "SELECT id, DATE_FORMAT(competencia, '%m/%Y') as comp, convenio_id, setor, qtd_atendimentos
              FROM relatorio_mensal_pa_consolidado 
              ORDER BY competencia DESC, convenio_id";
$stmt_after = $pdo->query($sql_after);
$registros_after = $stmt_after->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Competência</th><th>Conv</th><th>Setor</th><th>Qtd</th></tr>";
foreach ($registros_after as $r) {
    echo "<tr>";
    echo "<td>" . $r['id'] . "</td>";
    echo "<td>" . $r['comp'] . "</td>";
    echo "<td>" . $r['convenio_id'] . "</td>";
    echo "<td>" . htmlspecialchars($r['setor']) . "</td>";
    echo "<td>" . $r['qtd_atendimentos'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// 4. Testar a consolidação simulada
echo "<h3>4. Simulação de Consolidação (o que seria inserido):</h3>";
$sql_sim = "SELECT 
    DATE_FORMAT(p.competencia, '%Y-%m-01') as competencia,
    DATE_FORMAT(p.competencia, '%m/%Y') as comp_fmt,
    p.convenio_id,
    p.setor,
    COUNT(*) as qtd
FROM pa_ambulatorio p
WHERE p.competencia IS NOT NULL 
  AND p.status = 'Auditado'
  AND p.setor IS NOT NULL 
  AND TRIM(p.setor) != ''
GROUP BY DATE_FORMAT(p.competencia, '%Y-%m'), p.convenio_id, p.setor
ORDER BY competencia DESC, p.convenio_id";
$stmt_sim = $pdo->query($sql_sim);
$simulacao = $stmt_sim->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #d4edda;'><th>Competência</th><th>Conv</th><th>Setor</th><th>Qtd</th></tr>";
foreach ($simulacao as $s) {
    echo "<tr>";
    echo "<td>" . $s['comp_fmt'] . "</td>";
    echo "<td>" . $s['convenio_id'] . "</td>";
    echo "<td>" . htmlspecialchars($s['setor']) . "</td>";
    echo "<td>" . $s['qtd'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>Agora você pode executar a consolidação normalmente!</strong><br>";
echo "<a href='consolidar_pa_ambulatorio.php' style='display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Ir para Consolidação</a>";
echo "</div>";
?>
