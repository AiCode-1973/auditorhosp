<?php
require_once 'db_config.php';
session_start();

echo "<h2>Corrigir Registros PA/Ambulatório com Setor Vazio</h2><hr>";

// 1. Verificar quantos registros têm setor vazio
$sql_count = "SELECT 
    COUNT(*) as total,
    status,
    convenio_id,
    COUNT(*) as qtd
FROM pa_ambulatorio 
WHERE setor IS NULL OR setor = '' OR LENGTH(setor) = 0 OR TRIM(setor) = ''
GROUP BY status, convenio_id
ORDER BY convenio_id";

$stmt_count = $pdo->query($sql_count);
$grupos = $stmt_count->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Registros com Setor Vazio:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Status</th><th>Convênio</th><th>Quantidade</th></tr>";
$total_geral = 0;
foreach ($grupos as $g) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($g['status']) . "</td>";
    echo "<td>" . $g['convenio_id'] . "</td>";
    echo "<td>" . $g['qtd'] . "</td>";
    echo "</tr>";
    $total_geral += $g['qtd'];
}
echo "<tr style='background: #ffcccc; font-weight: bold;'>";
echo "<td colspan='2'>TOTAL</td><td>$total_geral</td>";
echo "</tr>";
echo "</table><br>";

if ($total_geral > 0) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<strong>⚠ Atenção:</strong> Encontrados <strong>$total_geral</strong> registros com setor vazio.<br>";
    echo "Estes registros NÃO PODEM ser consolidados até que o campo setor seja preenchido.";
    echo "</div>";
    
    // Mostrar alguns exemplos
    echo "<h3>Exemplos de Registros com Setor Vazio:</h3>";
    $sql_exemplos = "SELECT id, DATE_FORMAT(competencia, '%m/%Y') as comp, convenio_id, status, setor, valor_total
                     FROM pa_ambulatorio 
                     WHERE setor IS NULL OR setor = '' OR LENGTH(setor) = 0 OR TRIM(setor) = ''
                     LIMIT 20";
    $stmt_ex = $pdo->query($sql_exemplos);
    $exemplos = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Competência</th><th>Convênio</th><th>Status</th><th>Setor</th><th>Valor</th></tr>";
    foreach ($exemplos as $ex) {
        echo "<tr>";
        echo "<td><a href='pa_ambulatorio_form.php?id=" . $ex['id'] . "' target='_blank'>" . $ex['id'] . "</a></td>";
        echo "<td>" . $ex['comp'] . "</td>";
        echo "<td>" . $ex['convenio_id'] . "</td>";
        echo "<td>" . $ex['status'] . "</td>";
        echo "<td style='background: #ffcccc;'>[VAZIO]</td>";
        echo "<td>R$ " . number_format($ex['valor_total'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Opção para marcar como pendente
    if (isset($_POST['marcar_pendente'])) {
        try {
            $sql_update = "UPDATE pa_ambulatorio 
                          SET status = 'Pendente' 
                          WHERE status = 'Auditado' 
                            AND (setor IS NULL OR setor = '' OR LENGTH(setor) = 0 OR TRIM(setor) = '')";
            $stmt_upd = $pdo->prepare($sql_update);
            $stmt_upd->execute();
            $atualizados = $stmt_upd->rowCount();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<strong style='color: #155724;'>✓ Sucesso!</strong><br>";
            echo "$atualizados registros foram marcados como 'Pendente'.<br>";
            echo "Estes registros NÃO serão mais consolidados até que o setor seja preenchido.";
            echo "</div>";
            
            echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
            
        } catch (PDOException $e) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
            echo "<strong style='color: #721c24;'>Erro: " . $e->getMessage() . "</strong>";
            echo "</div>";
        }
    }
    
    echo "<form method='POST' style='margin: 20px 0;'>";
    echo "<div style='background: #e7f3ff; border: 1px solid #004085; padding: 20px; border-radius: 5px;'>";
    echo "<h4>Solução Recomendada:</h4>";
    echo "<p>Marcar estes registros como <strong>'Pendente'</strong> para que não sejam consolidados.</p>";
    echo "<p>Depois você pode editar cada registro individualmente para preencher o setor correto (PA, AMB, PA/NC ou AMB/NC).</p>";
    echo "<button type='submit' name='marcar_pendente' value='1' class='btn-primary' style='background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;' onclick='return confirm(\"Tem certeza? Isto irá marcar $total_geral registros como Pendente.\");'>";
    echo "✓ Marcar $total_geral Registros como Pendente";
    echo "</button>";
    echo "</div>";
    echo "</form>";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<strong style='color: #155724;'>✓ Tudo certo!</strong><br>";
    echo "Não há registros com setor vazio em pa_ambulatorio.";
    echo "</div>";
}

echo "<br><a href='pa_ambulatorio.php' style='display: inline-block; background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Voltar para PA/Ambulatório</a>";
echo " <a href='consolidar_pa_ambulatorio.php' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Ir para Consolidação</a>";
?>
