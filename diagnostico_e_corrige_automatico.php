<?php
require_once 'db_config.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><meta charset='UTF-8'><title>Diagnóstico e Correção Automática</title></head><body>";
echo "<h1>🔍 Diagnóstico Completo e Correção Automática</h1><hr>";

// ============================================
// ETAPA 1: DIAGNÓSTICO INICIAL
// ============================================
echo "<h2>ETAPA 1: Diagnóstico Inicial</h2>";

// Verificar registros auditados com setor vazio
$sql1 = "SELECT COUNT(*) as total FROM pa_ambulatorio 
         WHERE status = 'Auditado' AND (setor IS NULL OR setor = '' OR LENGTH(setor) = 0)";
$stmt1 = $pdo->query($sql1);
$vazios_pa = $stmt1->fetch(PDO::FETCH_ASSOC)['total'];

echo "<div style='padding: 10px; margin: 10px 0; background: " . ($vazios_pa > 0 ? "#fff3cd" : "#d4edda") . "; border-radius: 5px;'>";
echo "<strong>Registros Auditados com Setor Vazio em pa_ambulatorio:</strong> $vazios_pa";
echo "</div>";

// Verificar registros consolidados com setor vazio
$sql2 = "SELECT COUNT(*) as total FROM relatorio_mensal_pa_consolidado 
         WHERE setor IS NULL OR setor = '' OR LENGTH(setor) = 0";
$stmt2 = $pdo->query($sql2);
$vazios_cons = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];

echo "<div style='padding: 10px; margin: 10px 0; background: " . ($vazios_cons > 0 ? "#fff3cd" : "#d4edda") . "; border-radius: 5px;'>";
echo "<strong>Registros Consolidados com Setor Vazio:</strong> $vazios_cons";
echo "</div>";

// ============================================
// ETAPA 2: CORREÇÃO AUTOMÁTICA
// ============================================
echo "<h2>ETAPA 2: Correção Automática</h2>";

try {
    $pdo->beginTransaction();
    
    // 2.1 - Marcar registros com setor vazio como Pendente
    $sql_mark = "UPDATE pa_ambulatorio 
                 SET status = 'Pendente' 
                 WHERE status = 'Auditado' 
                   AND (setor IS NULL OR setor = '' OR LENGTH(setor) = 0)";
    $stmt_mark = $pdo->prepare($sql_mark);
    $stmt_mark->execute();
    $marcados = $stmt_mark->rowCount();
    
    echo "<div style='padding: 10px; margin: 10px 0; background: #d4edda; border-radius: 5px;'>";
    echo "✓ <strong>$marcados</strong> registros marcados como Pendente (não serão consolidados)";
    echo "</div>";
    
    // 2.2 - Deletar registros consolidados com setor vazio
    $sql_del = "DELETE FROM relatorio_mensal_pa_consolidado 
                WHERE setor IS NULL OR setor = '' OR LENGTH(setor) = 0";
    $stmt_del = $pdo->prepare($sql_del);
    $stmt_del->execute();
    $deletados = $stmt_del->rowCount();
    
    echo "<div style='padding: 10px; margin: 10px 0; background: #d4edda; border-radius: 5px;'>";
    echo "✓ <strong>$deletados</strong> registros consolidados deletados";
    echo "</div>";
    
    $pdo->commit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div style='padding: 10px; margin: 10px 0; background: #f8d7da; border-radius: 5px;'>";
    echo "✗ Erro na correção: " . $e->getMessage();
    echo "</div>";
    die();
}

// ============================================
// ETAPA 3: TESTAR CONSOLIDAÇÃO
// ============================================
echo "<h2>ETAPA 3: Testar Consolidação (Simulação)</h2>";

try {
    // Simular a query de consolidação
    $sql_test = "
        SELECT 
            DATE_FORMAT(p.competencia, '%Y-%m-01') as competencia_consolidada,
            p.convenio_id,
            c.nome_convenio,
            p.setor,
            LENGTH(p.setor) as setor_length,
            HEX(p.setor) as setor_hex,
            COUNT(*) as qtd_atendimentos,
            SUM(p.valor_inicial) as valor_inicial,
            SUM(p.valor_total) as valor_final
        FROM pa_ambulatorio p
        JOIN convenios c ON p.convenio_id = c.id
        WHERE p.competencia IS NOT NULL 
          AND p.status = 'Auditado'
          AND p.setor IS NOT NULL 
          AND LENGTH(p.setor) > 0
        GROUP BY DATE_FORMAT(p.competencia, '%Y-%m'), p.convenio_id, p.setor
        ORDER BY competencia_consolidada DESC, c.nome_convenio, p.setor
    ";
    
    $stmt_test = $pdo->query($sql_test);
    $grupos_teste = $stmt_test->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='padding: 10px; margin: 10px 0; background: #d1ecf1; border-radius: 5px;'>";
    echo "✓ Query executada com sucesso. <strong>" . count($grupos_teste) . "</strong> grupos para consolidar.";
    echo "</div>";
    
    if (count($grupos_teste) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Competência</th><th>Convênio</th><th>Setor</th><th>Length</th><th>HEX</th><th>Qtd</th><th>V.Inicial</th><th>V.Final</th></tr>";
        foreach ($grupos_teste as $g) {
            $setor_check = ($g['setor_length'] == 0 || strlen(trim($g['setor'])) == 0) ? 'background: #ffcccc;' : '';
            echo "<tr style='$setor_check'>";
            echo "<td>" . date('m/Y', strtotime($g['competencia_consolidada'])) . "</td>";
            echo "<td>" . htmlspecialchars($g['nome_convenio']) . "</td>";
            echo "<td>[" . htmlspecialchars($g['setor']) . "]</td>";
            echo "<td>" . $g['setor_length'] . "</td>";
            echo "<td>" . $g['setor_hex'] . "</td>";
            echo "<td>" . $g['qtd_atendimentos'] . "</td>";
            echo "<td>R$ " . number_format($g['valor_inicial'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($g['valor_final'], 2, ',', '.') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<div style='padding: 10px; margin: 10px 0; background: #f8d7da; border-radius: 5px;'>";
    echo "✗ Erro no teste: " . $e->getMessage();
    echo "</div>";
}

// ============================================
// ETAPA 4: EXECUTAR CONSOLIDAÇÃO REAL
// ============================================
echo "<h2>ETAPA 4: Executar Consolidação Real</h2>";

try {
    $pdo->beginTransaction();
    
    $registros_inseridos = 0;
    $registros_atualizados = 0;
    $erros = [];
    $debug_log = []; // Log detalhado para debug
    
    foreach ($grupos_teste as $grupo) {
        $setor = trim($grupo['setor']);
        
        $debug_log[] = "Processando: Conv {$grupo['convenio_id']} ({$grupo['nome_convenio']}), Setor=[$setor], Length={$grupo['setor_length']}, HEX={$grupo['setor_hex']}";
        
        // VALIDACAO CRITICA: Pular se setor vazio (verificacao multipla)
        if (empty($setor) || strlen($setor) == 0 || $grupo['setor_length'] == 0) {
            $erros[] = "PULADO - Conv {$grupo['convenio_id']} ({$grupo['nome_convenio']}): setor vazio (length={$grupo['setor_length']}, hex={$grupo['setor_hex']})";
            $debug_log[] = "  -> PULADO (setor vazio)";
            continue;
        }
        
        // Calcular percentuais
        $perc_retirado = 0;
        $perc_acrescentado = 0;
        $perc_glosado = 0;
        $perc_aceito = 0;
        
        // Verificar se já existe
        $sql_check = "SELECT id FROM relatorio_mensal_pa_consolidado 
                     WHERE competencia = ? AND convenio_id = ? AND setor = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$grupo['competencia_consolidada'], $grupo['convenio_id'], $setor]);
        $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        $debug_log[] = "  -> Verificando existente: comp={$grupo['competencia_consolidada']}, conv={$grupo['convenio_id']}, setor=[$setor]";
        $debug_log[] = "  -> Resultado: " . ($existe ? "EXISTE (ID={$existe['id']})" : "NAO EXISTE");
        
        if ($existe) {
            // Atualizar
            $sql_upd = "UPDATE relatorio_mensal_pa_consolidado SET
                qtd_atendimentos = ?,
                valor_inicial = ?,
                valor_final = ?
                WHERE id = ?";
            $stmt_upd = $pdo->prepare($sql_upd);
            $stmt_upd->execute([
                $grupo['qtd_atendimentos'],
                $grupo['valor_inicial'],
                $grupo['valor_final'],
                $existe['id']
            ]);
            $registros_atualizados++;
            $debug_log[] = "  -> ATUALIZADO com sucesso";
        } else {
            // Inserir
            $debug_log[] = "  -> Tentando INSERIR: comp={$grupo['competencia_consolidada']}, conv={$grupo['convenio_id']}, setor=[$setor]";
            
            $sql_ins = "INSERT INTO relatorio_mensal_pa_consolidado 
                (competencia, convenio_id, setor, qtd_atendimentos, valor_inicial, valor_final,
                 valor_retirado, valor_acrescentado, valor_glosado, valor_aceito, valor_faturado,
                 perc_retirado, perc_acrescentado, perc_glosado, perc_aceito)
                VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, 0)";
            $stmt_ins = $pdo->prepare($sql_ins);
            $stmt_ins->execute([
                $grupo['competencia_consolidada'],
                $grupo['convenio_id'],
                $setor,
                $grupo['qtd_atendimentos'],
                $grupo['valor_inicial'],
                $grupo['valor_final']
            ]);
            $registros_inseridos++;
            $debug_log[] = "  -> INSERIDO com sucesso (novo ID: " . $pdo->lastInsertId() . ")";
        }
    }
    
    $pdo->commit();
    
    echo "<div style='padding: 15px; margin: 10px 0; background: #d4edda; border-radius: 5px;'>";
    echo "<h3 style='margin-top: 0; color: #155724;'>✓ Consolidação Concluída!</h3>";
    echo "<strong>Inseridos:</strong> $registros_inseridos<br>";
    echo "<strong>Atualizados:</strong> $registros_atualizados<br>";
    echo "<strong>Total processado:</strong> " . ($registros_inseridos + $registros_atualizados);
    if (count($erros) > 0) {
        echo "<br><br><strong style='color: #856404;'>Avisos (" . count($erros) . " registros pulados):</strong><br>";
        echo "<ul style='margin: 5px 0; padding-left: 20px;'>";
        foreach ($erros as $erro) {
            echo "<li style='color: #856404;'>" . htmlspecialchars($erro) . "</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div style='padding: 10px; margin: 10px 0; background: #f8d7da; border-radius: 5px;'>";
    echo "<strong>✗ ERRO na consolidação:</strong><br>";
    echo $e->getMessage();
    
    // Mostrar log de debug
    echo "<h4>Log de Debug:</h4>";
    echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; border-radius: 5px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;'>";
    foreach ($debug_log as $log) {
        echo htmlspecialchars($log) . "<br>";
    }
    echo "</div>";
    echo "</div>";
}

// ============================================
// ETAPA 5: VERIFICAÇÃO FINAL
// ============================================
echo "<h2>ETAPA 5: Verificação Final</h2>";

// Verificar se ainda existem vazios
$sql_check1 = "SELECT COUNT(*) as total FROM pa_ambulatorio 
               WHERE status = 'Auditado' AND (setor IS NULL OR setor = '' OR LENGTH(setor) = 0)";
$stmt_check1 = $pdo->query($sql_check1);
$ainda_vazios_pa = $stmt_check1->fetch(PDO::FETCH_ASSOC)['total'];

$sql_check2 = "SELECT COUNT(*) as total FROM relatorio_mensal_pa_consolidado 
               WHERE setor IS NULL OR setor = '' OR LENGTH(setor) = 0";
$stmt_check2 = $pdo->query($sql_check2);
$ainda_vazios_cons = $stmt_check2->fetch(PDO::FETCH_ASSOC)['total'];

if ($ainda_vazios_pa == 0 && $ainda_vazios_cons == 0) {
    echo "<div style='padding: 15px; margin: 10px 0; background: #d4edda; border: 2px solid #28a745; border-radius: 5px;'>";
    echo "<h3 style='margin-top: 0; color: #155724;'>✓✓✓ TUDO CORRETO! ✓✓✓</h3>";
    echo "Não há mais registros com setor vazio.<br>";
    echo "O sistema está pronto para uso normal.";
    echo "</div>";
} else {
    echo "<div style='padding: 10px; margin: 10px 0; background: #fff3cd; border-radius: 5px;'>";
    echo "<strong>⚠ Ainda existem problemas:</strong><br>";
    echo "PA Auditados vazios: $ainda_vazios_pa<br>";
    echo "Consolidados vazios: $ainda_vazios_cons";
    echo "</div>";
}

// Mostrar registros consolidados finais
echo "<h3>Registros Consolidados (Estado Final):</h3>";
$sql_final = "SELECT 
    r.id,
    DATE_FORMAT(r.competencia, '%m/%Y') as comp,
    c.nome_convenio,
    r.setor,
    r.qtd_atendimentos
FROM relatorio_mensal_pa_consolidado r
JOIN convenios c ON r.convenio_id = c.id
ORDER BY r.competencia DESC, c.nome_convenio, r.setor";
$stmt_final = $pdo->query($sql_final);
$final = $stmt_final->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Competência</th><th>Convênio</th><th>Setor</th><th>Qtd</th></tr>";
foreach ($final as $f) {
    echo "<tr>";
    echo "<td>" . $f['id'] . "</td>";
    echo "<td>" . $f['comp'] . "</td>";
    echo "<td>" . htmlspecialchars($f['nome_convenio']) . "</td>";
    echo "<td>" . htmlspecialchars($f['setor']) . "</td>";
    echo "<td>" . $f['qtd_atendimentos'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

echo "<div style='margin: 20px 0; padding: 15px; background: #e7f3ff; border-radius: 5px;'>";
echo "<h3>Próximos Passos:</h3>";
echo "<a href='relatorio_mensal_pa_ambulatorio.php' style='display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;'>Ver Relatório Mensal</a>";
echo "<a href='pa_ambulatorio.php' style='display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;'>Ver PA/Ambulatório</a>";
echo "</div>";

echo "</body></html>";
?>
