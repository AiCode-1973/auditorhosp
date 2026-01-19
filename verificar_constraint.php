<?php
require_once 'db_config.php';

echo "<h2>Verificar e Corrigir Constraint Única</h2><hr>";

// 1. Verificar estrutura da tabela
echo "<h3>1. Estrutura Atual da Tabela:</h3>";
$sql_show = "SHOW CREATE TABLE relatorio_mensal_pa_consolidado";
$stmt_show = $pdo->query($sql_show);
$create_table = $stmt_show->fetch(PDO::FETCH_ASSOC);

echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; overflow-x: auto;'>";
echo htmlspecialchars($create_table['Create Table']);
echo "</pre>";

// 2. Verificar índices
echo "<h3>2. Índices da Tabela:</h3>";
$sql_indexes = "SHOW INDEXES FROM relatorio_mensal_pa_consolidado";
$stmt_indexes = $pdo->query($sql_indexes);
$indexes = $stmt_indexes->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Key Name</th><th>Column</th><th>Unique</th><th>Seq</th></tr>";
foreach ($indexes as $idx) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($idx['Key_name']) . "</td>";
    echo "<td>" . htmlspecialchars($idx['Column_name']) . "</td>";
    echo "<td>" . ($idx['Non_unique'] == 0 ? 'YES' : 'NO') . "</td>";
    echo "<td>" . $idx['Seq_in_index'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// 3. Verificar se há registros duplicados ANTES de recriar a constraint
echo "<h3>3. Verificar Duplicados (por setor exato):</h3>";
$sql_dups = "SELECT 
    competencia, 
    convenio_id, 
    setor,
    LENGTH(setor) as len,
    HEX(setor) as hex,
    COUNT(*) as qtd,
    GROUP_CONCAT(id) as ids
FROM relatorio_mensal_pa_consolidado
GROUP BY competencia, convenio_id, setor
HAVING COUNT(*) > 1";
$stmt_dups = $pdo->query($sql_dups);
$dups = $stmt_dups->fetchAll(PDO::FETCH_ASSOC);

if (count($dups) > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>⚠ Encontrados " . count($dups) . " grupos duplicados!</strong>";
    echo "</div>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Competência</th><th>Conv</th><th>Setor</th><th>Len</th><th>HEX</th><th>Qtd</th><th>IDs</th></tr>";
    foreach ($dups as $d) {
        echo "<tr>";
        echo "<td>" . $d['competencia'] . "</td>";
        echo "<td>" . $d['convenio_id'] . "</td>";
        echo "<td>[" . htmlspecialchars($d['setor']) . "]</td>";
        echo "<td>" . $d['len'] . "</td>";
        echo "<td>" . $d['hex'] . "</td>";
        echo "<td>" . $d['qtd'] . "</td>";
        echo "<td>" . $d['ids'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "✓ Nenhum duplicado encontrado";
    echo "</div>";
}

// 4. Opção para recriar a constraint
if (isset($_POST['recriar_constraint'])) {
    try {
        $pdo->beginTransaction();
        
        // Dropar constraint antiga
        $sql_drop = "ALTER TABLE relatorio_mensal_pa_consolidado DROP INDEX unique_comp_conv_setor";
        try {
            $pdo->exec($sql_drop);
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✓ Constraint antiga removida";
            echo "</div>";
        } catch (PDOException $e) {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "Aviso: " . $e->getMessage();
            echo "</div>";
        }
        
        // Criar nova constraint
        $sql_create = "ALTER TABLE relatorio_mensal_pa_consolidado 
                       ADD UNIQUE KEY unique_comp_conv_setor (competencia, convenio_id, setor)";
        $pdo->exec($sql_create);
        
        $pdo->commit();
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✓ Constraint recriada com sucesso!</strong>";
        echo "</div>";
        
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<strong>Erro:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<form method='POST' style='margin: 20px 0;'>";
echo "<button type='submit' name='recriar_constraint' value='1' style='background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;' onclick='return confirm(\"Tem certeza que deseja recriar a constraint única?\");'>";
echo "🔧 Recriar Constraint Única";
echo "</button>";
echo "</form>";

echo "<br><a href='diagnostico_e_corrige_automatico.php' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Voltar ao Diagnóstico</a>";
?>
