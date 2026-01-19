<?php
require_once 'db_config.php';

echo "<h2>Diagnóstico - Registros com Setor Vazio</h2>";

// 1. Verificar registros com setor vazio ou NULL na tabela pa_ambulatorio
$sql1 = "SELECT 
    id, 
    guia_paciente, 
    convenio_id, 
    setor, 
    DATE_FORMAT(competencia, '%m/%Y') as competencia,
    status,
    data_cadastro
FROM pa_ambulatorio 
WHERE setor IS NULL OR setor = '' OR TRIM(setor) = ''
ORDER BY id DESC
LIMIT 20";

$stmt1 = $pdo->query($sql1);
$vazios_pa = $stmt1->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>PA/Ambulatório - Registros com Setor Vazio (" . count($vazios_pa) . " encontrados):</h3>";

if (count($vazios_pa) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Guia</th><th>Convênio ID</th><th>Setor (vazio)</th><th>Competência</th><th>Status</th><th>Data Cadastro</th></tr>";
    foreach ($vazios_pa as $r) {
        echo "<tr>";
        echo "<td>" . $r['id'] . "</td>";
        echo "<td>" . htmlspecialchars($r['guia_paciente']) . "</td>";
        echo "<td>" . $r['convenio_id'] . "</td>";
        echo "<td style='background: #ffcccc;'>" . ($r['setor'] === '' ? '(VAZIO)' : 'NULL') . "</td>";
        echo "<td>" . $r['competencia'] . "</td>";
        echo "<td>" . $r['status'] . "</td>";
        echo "<td>" . $r['data_cadastro'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h4>SOLUÇÃO:</h4>";
    echo "<p>Execute o SQL abaixo para corrigir todos os registros com setor vazio:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo "UPDATE pa_ambulatorio \n";
    echo "SET setor = 'N/D' \n";
    echo "WHERE setor IS NULL OR setor = '' OR TRIM(setor) = '';";
    echo "</pre>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✓ Nenhum registro com setor vazio encontrado!</p>";
}

echo "<hr>";

// 2. Verificar registros consolidados com setor vazio
$sql2 = "SELECT 
    id, 
    convenio_id, 
    setor,
    DATE_FORMAT(competencia, '%m/%Y') as competencia,
    qtd_atendimentos,
    valor_inicial
FROM relatorio_mensal_pa_consolidado 
WHERE setor IS NULL OR setor = '' OR TRIM(setor) = ''
ORDER BY id DESC";

$stmt2 = $pdo->query($sql2);
$vazios_cons = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Consolidado - Registros com Setor Vazio (" . count($vazios_cons) . " encontrados):</h3>";

if (count($vazios_cons) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Convênio ID</th><th>Setor (vazio)</th><th>Competência</th><th>Qtd Atend.</th><th>Valor Inicial</th></tr>";
    foreach ($vazios_cons as $r) {
        echo "<tr>";
        echo "<td>" . $r['id'] . "</td>";
        echo "<td>" . $r['convenio_id'] . "</td>";
        echo "<td style='background: #ffcccc;'>" . ($r['setor'] === '' ? '(VAZIO)' : 'NULL') . "</td>";
        echo "<td>" . $r['competencia'] . "</td>";
        echo "<td>" . $r['qtd_atendimentos'] . "</td>";
        echo "<td>R$ " . number_format($r['valor_inicial'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h4>SOLUÇÃO:</h4>";
    echo "<p>Execute o SQL abaixo para corrigir todos os registros consolidados com setor vazio:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo "UPDATE relatorio_mensal_pa_consolidado \n";
    echo "SET setor = 'N/D' \n";
    echo "WHERE setor IS NULL OR setor = '' OR TRIM(setor) = '';";
    echo "</pre>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✓ Nenhum consolidado com setor vazio encontrado!</p>";
}

echo "<hr>";

// 3. Estatísticas gerais
$sql3 = "SELECT 
    setor,
    COUNT(*) as total,
    SUM(valor_inicial) as soma_valor
FROM pa_ambulatorio
GROUP BY setor
ORDER BY total DESC";

$stmt3 = $pdo->query($sql3);
$stats = $stmt3->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Estatísticas por Setor:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Setor</th><th>Quantidade</th><th>Valor Total</th></tr>";
foreach ($stats as $s) {
    $setor_display = $s['setor'] === '' ? '(VAZIO)' : ($s['setor'] === null ? '(NULL)' : $s['setor']);
    $style = ($s['setor'] === '' || $s['setor'] === null) ? "style='background: #ffcccc;'" : "";
    echo "<tr $style>";
    echo "<td>" . $setor_display . "</td>";
    echo "<td>" . $s['total'] . "</td>";
    echo "<td>R$ " . number_format($s['soma_valor'], 2, ',', '.') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Resumo:</h3>";
echo "<ul>";
echo "<li><strong>Total de registros com setor vazio em PA/Ambulatório:</strong> " . count($vazios_pa) . "</li>";
echo "<li><strong>Total de registros com setor vazio no Consolidado:</strong> " . count($vazios_cons) . "</li>";
echo "</ul>";

if (count($vazios_pa) > 0 || count($vazios_cons) > 0) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-top: 20px;'>";
    echo "<h4 style='margin-top: 0;'>⚠️ Ação Recomendada:</h4>";
    echo "<p>Use a interface web para corrigir automaticamente:</p>";
    echo "<p><a href='limpar_duplicados_pa.php' style='background: #ffc107; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🧹 Acessar Limpeza Automática</a></p>";
    echo "</div>";
}
?>
