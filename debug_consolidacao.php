<?php
require_once 'db_config.php';

echo "<h2>Diagnóstico de Consolidação PA/Ambulatório</h2>";
echo "<hr>";

// 1. Verificar registros auditados em pa_ambulatorio
echo "<h3>1. Registros Auditados em pa_ambulatorio:</h3>";
$sql1 = "SELECT 
    COUNT(*) as total, 
    COUNT(CASE WHEN setor IS NULL OR setor = '' THEN 1 END) as vazios,
    COUNT(CASE WHEN setor = 'N/D' THEN 1 END) as nd,
    COUNT(CASE WHEN TRIM(setor) = '' THEN 1 END) as espacos
FROM pa_ambulatorio 
WHERE status = 'Auditado'";

$stmt1 = $pdo->query($sql1);
$result1 = $stmt1->fetch(PDO::FETCH_ASSOC);

echo "Total Auditados: " . $result1['total'] . "<br>";
echo "Setores Vazios/NULL: " . $result1['vazios'] . "<br>";
echo "Setores N/D: " . $result1['nd'] . "<br>";
echo "Setores só com espaços: " . $result1['espacos'] . "<br>";

// 2. Verificar o que seria consolidado (simulação)
echo "<h3>2. Simulação de Consolidação (o que seria gerado):</h3>";
$sql2 = "
    SELECT 
        DATE_FORMAT(p.competencia, '%Y-%m-01') as competencia,
        DATE_FORMAT(p.competencia, '%m/%Y') as competencia_fmt,
        p.convenio_id,
        c.nome_convenio,
        p.setor as setor_original,
        COALESCE(NULLIF(p.setor, ''), 'N/D') as setor_normalizado,
        COUNT(*) as qtd
    FROM pa_ambulatorio p
    JOIN convenios c ON p.convenio_id = c.id
    WHERE p.competencia IS NOT NULL AND p.status = 'Auditado'
    GROUP BY DATE_FORMAT(p.competencia, '%Y-%m'), p.convenio_id, COALESCE(NULLIF(p.setor, ''), 'N/D')
    ORDER BY competencia DESC, c.nome_convenio, setor_normalizado
";

$stmt2 = $pdo->query($sql2);
$grupos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>
    <th>Competência</th>
    <th>Convênio</th>
    <th>Setor Original</th>
    <th>Setor Normalizado</th>
    <th>Qtd</th>
</tr>";

foreach ($grupos as $g) {
    $setor_orig = $g['setor_original'] === null ? '[NULL]' : ($g['setor_original'] === '' ? '[VAZIO]' : htmlspecialchars($g['setor_original']));
    echo "<tr>";
    echo "<td>" . htmlspecialchars($g['competencia_fmt']) . "</td>";
    echo "<td>" . htmlspecialchars($g['nome_convenio']) . "</td>";
    echo "<td style='background: " . ($setor_orig == '[NULL]' || $setor_orig == '[VAZIO]' ? '#ffcccc' : '#fff') . ";'>" . $setor_orig . "</td>";
    echo "<td>" . htmlspecialchars($g['setor_normalizado']) . "</td>";
    echo "<td>" . $g['qtd'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Verificar registros já existentes na tabela consolidada
echo "<h3>3. Registros Existentes em relatorio_mensal_pa_consolidado:</h3>";
$sql3 = "
    SELECT 
        DATE_FORMAT(competencia, '%m/%Y') as competencia_fmt,
        competencia,
        convenio_id,
        setor,
        id
    FROM relatorio_mensal_pa_consolidado
    ORDER BY competencia DESC, convenio_id, setor
";

$stmt3 = $pdo->query($sql3);
$existentes = $stmt3->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>
    <th>ID</th>
    <th>Competência</th>
    <th>Convênio ID</th>
    <th>Setor</th>
</tr>";

foreach ($existentes as $e) {
    $setor = $e['setor'] === null ? '[NULL]' : ($e['setor'] === '' ? '[VAZIO]' : htmlspecialchars($e['setor']));
    echo "<tr>";
    echo "<td>" . $e['id'] . "</td>";
    echo "<td>" . htmlspecialchars($e['competencia_fmt']) . "</td>";
    echo "<td>" . $e['convenio_id'] . "</td>";
    echo "<td style='background: " . ($setor == '[NULL]' || $setor == '[VAZIO]' ? '#ffcccc' : '#fff') . ";'>" . $setor . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Identificar possíveis conflitos
echo "<h3>4. Análise de Conflitos Potenciais:</h3>";
$sql4 = "
    SELECT 
        competencia,
        convenio_id,
        setor,
        COUNT(*) as duplicatas
    FROM relatorio_mensal_pa_consolidado
    GROUP BY competencia, convenio_id, setor
    HAVING COUNT(*) > 1
";

$stmt4 = $pdo->query($sql4);
$conflitos = $stmt4->fetchAll(PDO::FETCH_ASSOC);

if (count($conflitos) > 0) {
    echo "<strong style='color: red;'>ATENÇÃO: Encontrados " . count($conflitos) . " grupos duplicados!</strong><br>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #ffcccc;'><th>Competência</th><th>Convênio ID</th><th>Setor</th><th>Duplicatas</th></tr>";
    foreach ($conflitos as $c) {
        echo "<tr>";
        echo "<td>" . $c['competencia'] . "</td>";
        echo "<td>" . $c['convenio_id'] . "</td>";
        echo "<td>" . htmlspecialchars($c['setor']) . "</td>";
        echo "<td>" . $c['duplicatas'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<strong style='color: green;'>✓ Nenhuma duplicata encontrada na tabela consolidada</strong><br>";
}

// 5. Verificar se há setores vazios na consolidada
$sql5 = "SELECT COUNT(*) as total FROM relatorio_mensal_pa_consolidado WHERE setor IS NULL OR setor = ''";
$stmt5 = $pdo->query($sql5);
$vazios_cons = $stmt5->fetch(PDO::FETCH_ASSOC)['total'];

if ($vazios_cons > 0) {
    echo "<br><strong style='color: orange;'>AVISO: Existem $vazios_cons registros com setor vazio/NULL na tabela consolidada!</strong><br>";
} else {
    echo "<br><strong style='color: green;'>✓ Nenhum registro com setor vazio na tabela consolidada</strong><br>";
}
?>
