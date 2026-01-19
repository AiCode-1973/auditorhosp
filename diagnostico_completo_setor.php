<?php
require_once 'db_config.php';

echo "<h2>Diagnóstico Completo - Setores</h2><hr>";

// 1. Verificar registros auditados do convênio 6
echo "<h3>1. Registros Auditados - Convênio 6:</h3>";
$sql1 = "SELECT setor, COUNT(*) as qtd 
         FROM pa_ambulatorio 
         WHERE status = 'Auditado' AND convenio_id = 6 
         GROUP BY setor";
$stmt1 = $pdo->query($sql1);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Setor</th><th>Quantidade</th></tr>";
foreach($stmt1->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $setor_display = $r['setor'] === null ? '[NULL]' : ($r['setor'] === '' ? '[VAZIO]' : htmlspecialchars($r['setor']));
    echo "<tr><td>" . $setor_display . "</td><td>" . $r['qtd'] . "</td></tr>";
}
echo "</table><br>";

// 2. Verificar TODOS os registros auditados por setor
echo "<h3>2. Todos Registros Auditados por Setor:</h3>";
$sql2 = "SELECT setor, COUNT(*) as qtd 
         FROM pa_ambulatorio 
         WHERE status = 'Auditado' 
         GROUP BY setor 
         ORDER BY setor";
$stmt2 = $pdo->query($sql2);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Setor</th><th>Quantidade</th></tr>";
foreach($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $setor_display = $r['setor'] === null ? '[NULL]' : ($r['setor'] === '' ? '[VAZIO]' : htmlspecialchars($r['setor']));
    $cor = ($setor_display == '[NULL]' || $setor_display == '[VAZIO]') ? 'background: #ffcccc;' : '';
    echo "<tr style='$cor'><td>" . $setor_display . "</td><td>" . $r['qtd'] . "</td></tr>";
}
echo "</table><br>";

// 3. Verificar registros na tabela consolidada
echo "<h3>3. Registros na Tabela Consolidada:</h3>";
$sql3 = "SELECT 
    DATE_FORMAT(competencia, '%m/%Y') as comp,
    convenio_id,
    setor,
    qtd_atendimentos,
    id
FROM relatorio_mensal_pa_consolidado 
ORDER BY competencia DESC, convenio_id, setor";
$stmt3 = $pdo->query($sql3);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Competência</th><th>Convênio</th><th>Setor</th><th>Qtd</th></tr>";
foreach($stmt3->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $setor_display = $r['setor'] === null ? '[NULL]' : ($r['setor'] === '' ? '[VAZIO]' : htmlspecialchars($r['setor']));
    $cor = ($setor_display == '[NULL]' || $setor_display == '[VAZIO]') ? 'background: #ffcccc;' : '';
    echo "<tr style='$cor'><td>" . $r['id'] . "</td><td>" . $r['comp'] . "</td><td>" . $r['convenio_id'] . "</td><td>" . $setor_display . "</td><td>" . $r['qtd_atendimentos'] . "</td></tr>";
}
echo "</table><br>";

// 4. Simular o GROUP BY da consolidação
echo "<h3>4. Simulação da Consolidação (GROUP BY):</h3>";
$sql4 = "SELECT 
    DATE_FORMAT(p.competencia, '%Y-%m-01') as competencia,
    DATE_FORMAT(p.competencia, '%m/%Y') as comp_fmt,
    p.convenio_id,
    p.setor,
    COUNT(*) as qtd,
    CONCAT(DATE_FORMAT(p.competencia, '%Y-%m-01'), '-', p.convenio_id, '-', COALESCE(p.setor, '')) as chave_unica
FROM pa_ambulatorio p
WHERE p.competencia IS NOT NULL AND p.status = 'Auditado'
GROUP BY DATE_FORMAT(p.competencia, '%Y-%m'), p.convenio_id, p.setor
ORDER BY competencia DESC, p.convenio_id, p.setor";
$stmt4 = $pdo->query($sql4);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Competência</th><th>Conv ID</th><th>Setor</th><th>Qtd</th><th>Chave Única</th></tr>";
foreach($stmt4->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $setor_display = $r['setor'] === null ? '[NULL]' : ($r['setor'] === '' ? '[VAZIO]' : htmlspecialchars($r['setor']));
    $cor = ($setor_display == '[NULL]' || $setor_display == '[VAZIO]') ? 'background: #ffcccc;' : '';
    echo "<tr style='$cor'><td>" . $r['comp_fmt'] . "</td><td>" . $r['convenio_id'] . "</td><td>" . $setor_display . "</td><td>" . $r['qtd'] . "</td><td>" . htmlspecialchars($r['chave_unica']) . "</td></tr>";
}
echo "</table><br>";

// 5. Testar a query de consolidação exata
echo "<h3>5. Query de Consolidação Exata (com filtro de setor):</h3>";
$sql5 = "SELECT 
    DATE_FORMAT(p.competencia, '%Y-%m-01') as competencia_consolidada,
    p.convenio_id,
    p.setor,
    COUNT(*) as qtd_atendimentos
FROM pa_ambulatorio p
WHERE p.competencia IS NOT NULL 
  AND p.status = 'Auditado'
  AND p.setor IS NOT NULL 
  AND TRIM(p.setor) != ''
GROUP BY DATE_FORMAT(p.competencia, '%Y-%m'), p.convenio_id, p.setor
ORDER BY competencia_consolidada DESC, p.convenio_id, p.setor";
$stmt5 = $pdo->query($sql5);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Competência</th><th>Conv ID</th><th>Setor</th><th>Qtd</th></tr>";
foreach($stmt5->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "<tr><td>" . $r['competencia_consolidada'] . "</td><td>" . $r['convenio_id'] . "</td><td>" . htmlspecialchars($r['setor']) . "</td><td>" . $r['qtd_atendimentos'] . "</td></tr>";
}
echo "</table>";
?>
