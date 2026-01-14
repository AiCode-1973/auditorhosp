<?php
require_once 'db_config.php';

// Função para converter valor brasileiro para decimal
function converterValor($valor) {
    $valor = trim($valor);
    $valor = str_replace('R$', '', $valor);
    $valor = str_replace(' ', '', $valor);
    $valor = str_replace('.', '', $valor);
    $valor = str_replace(',', '.', $valor);
    return floatval($valor);
}

// Dados fornecidos
$dados = [
    ['Julho', 'Prevent Senior', 250628.55, 17168.18, 17658.16, 265055.33, 27.27, 27.27],
    ['Agosto', 'Prevent Senior', 276720.63, 8666.45, 44405.85, 313851.52, 0, 0],
    ['Setembro', 'Prevent Senior', 156370.24, 4520.26, 20001.12, 171851.10, 24.46, 24.46],
    ['Outubro', 'Prevent Senior', 456973.87, 17395.83, 66410.55, 486841.91, 10.16, 10.16],
    ['Novembro', 'Prevent Senior', 230310.32, 10819.08, 16451.51, 235942.75, 590.98, 590.98],
    
    ['Julho', 'Capep', 297943.26, 10600.47, 15729.10, 304744.24, 0, 0],
    ['Agosto', 'Capep', 101849.76, 2542.55, 94.38, 99401.59, 0, 0],
    ['Setembro', 'Capep', 383073.59, 7202.39, 13934.31, 389508.51, 0, 0],
    ['Outubro', 'Capep', 358768.60, 16132.52, 24207.88, 315136.00, 0, 0],
    ['Novembro', 'Capep', 273046.02, 8897.77, 44508.48, 219639.77, 0, 0],
    
    ['Julho', 'Marinha', 90395.78, 17163.20, 15228.14, 88460.72, 0, 0],
    ['Agosto', 'Marinha', 29988.68, 5797.64, 11515.04, 35706.08, 0, 0],
    ['Setembro', 'Marinha', 15220.54, 3425.17, 1819.64, 13615.01, 0, 0],
    ['Outubro', 'Marinha', 46704.35, 5607.39, 6922.91, 48019.87, 0, 0],
    ['Novembro', 'Marinha', 136380.79, 13010.92, 23521.67, 146882.54, 92.02, 92.02],
    
    ['Julho', 'Fusex', 6952.34, 102.85, 3984.60, 10834.09, 0, 0],
    ['Agosto', 'Fusex', 40916.27, 0, 393.30, 41309.57, 481.26, 481.26],
    ['Setembro', 'Fusex', 143937.42, 13428.97, 24905.61, 155414.06, 4699.13, 2660.89],
    ['Outubro', 'Fusex', 37539.21, 423.50, 1894.99, 39010.70, 2034.94, 2034.94],
    ['Novembro', 'Fusex', 96984.20, 13303.43, 12021.34, 95702.11, 548.12, 548.12],
    
    ['Julho', 'Usisaude', 48000.99, 4576.03, 10092.77, 53895.41, 1684.60, 1684.60],
    ['Agosto', 'Usisaude', 147392.28, 17235.33, 46996.14, 176938.17, 234.28, 234.28],
    ['Setembro', 'Usisaude', 92618.46, 11752.71, 45972.51, 126838.26, 941.64, 941.64],
    ['Outubro', 'Usisaude', 30066.11, 6938.51, 20594.11, 46282.92, 77.75, 11863.43],
    ['Novembro', 'Usisaude', 41477.55, 1988.53, 17625.91, 57114.93, 1540.00, 1540.00],
    
    ['Julho', 'GoCare', 41738.85, 1276.50, 0, 40462.35, 197.87, 197.87],
    ['Agosto', 'GoCare', 63603.86, 4060.07, 33421.25, 128339.61, 13424.77, 0],
    ['Setembro', 'GoCare', 184429.00, 6988.41, 11897.16, 189338.41, 34378.07, 5895.00],
    ['Outubro', 'GoCare', 224224.29, 1929.59, 9869.63, 226523.83, 0, 0],
    ['Novembro', 'GoCare', 17657.01, 663.73, 969.63, 17962.91, 5902.05, 4020.47],
    
    ['Julho', 'Aeronautica', 33396.62, 3062.83, 7726.67, 38060.76, 163.16, 163.16],
    ['Agosto', 'Aeronautica', 0, 0, 0, 0, 0, 0],
    ['Setembro', 'Aeronautica', 2032.16, 26.71, 1082.56, 3088.01, 0, 0],
    ['Outubro', 'Aeronautica', 3171.55, 159.37, 682.90, 3695.08, 44.52, 44.52],
    ['Novembro', 'Aeronautica', 32814.60, 4934.20, 7930.88, 35811.28, 0, 0],
    
    ['Julho', 'Cruz Azul', 33564.92, 70.00, 4235.64, 37730.56, 0, 0],
    ['Agosto', 'Cruz Azul', 0, 0, 0, 0, 0, 0],
    ['Setembro', 'Cruz Azul', 112342.37, 32412.15, 6918.07, 86848.29, 0, 0],
    ['Outubro', 'Cruz Azul', 57647.63, 269.80, 1117.39, 58495.22, 0, 0],
    ['Novembro', 'Cruz Azul', 54816.18, 361.35, 1147.21, 55602.04, 0, 0]
];

// Mapeamento de meses
$meses = [
    'Julho' => '07',
    'Agosto' => '08',
    'Setembro' => '09',
    'Outubro' => '10',
    'Novembro' => '11',
    'Dezembro' => '12'
];

try {
    $pdo->beginTransaction();
    
    echo "<h2>Importando Relatório Mensal Consolidado</h2>";
    echo "<p>Ano base: 2024</p><br>";
    
    $importados = 0;
    $erros = 0;
    
    foreach ($dados as $linha) {
        list($mes_nome, $convenio_nome, $valor_inicial, $valor_retirado, $valor_acrescentado, 
             $valor_final, $valor_glosado, $valor_aceito) = $linha;
        
        // Buscar ou criar convênio
        $stmt = $pdo->prepare("SELECT id FROM convenios WHERE nome_convenio = ?");
        $stmt->execute([$convenio_nome]);
        $convenio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$convenio) {
            // Criar convênio se não existir
            $stmt = $pdo->prepare("INSERT INTO convenios (nome_convenio) VALUES (?)");
            $stmt->execute([$convenio_nome]);
            $convenio_id = $pdo->lastInsertId();
            echo "✓ Convênio criado: <strong>$convenio_nome</strong> (ID: $convenio_id)<br>";
        } else {
            $convenio_id = $convenio['id'];
        }
        
        // Montar data de competência
        $mes_num = $meses[$mes_nome];
        $competencia = "2024-{$mes_num}-01";
        
        // Calcular percentuais
        $perc_retirado = $valor_inicial > 0 ? round(($valor_retirado / $valor_inicial) * 100, 2) : 0;
        $perc_acrescentado = $valor_inicial > 0 ? round(($valor_acrescentado / $valor_inicial) * 100, 2) : 0;
        $perc_glosado = $valor_final > 0 ? round(($valor_glosado / $valor_final) * 100, 2) : 0;
        $perc_aceito = $valor_glosado > 0 ? round(($valor_aceito / $valor_glosado) * 100, 2) : 0;
        
        // Inserir ou atualizar registro
        $sql = "INSERT INTO relatorio_mensal_consolidado 
                (competencia, convenio_id, valor_inicial, valor_retirado, valor_acrescentado, 
                 valor_final, valor_glosado, valor_aceito, perc_retirado, perc_acrescentado, 
                 perc_glosado, perc_aceito)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                valor_inicial = VALUES(valor_inicial),
                valor_retirado = VALUES(valor_retirado),
                valor_acrescentado = VALUES(valor_acrescentado),
                valor_final = VALUES(valor_final),
                valor_glosado = VALUES(valor_glosado),
                valor_aceito = VALUES(valor_aceito),
                perc_retirado = VALUES(perc_retirado),
                perc_acrescentado = VALUES(perc_acrescentado),
                perc_glosado = VALUES(perc_glosado),
                perc_aceito = VALUES(perc_aceito)";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $competencia, $convenio_id, $valor_inicial, $valor_retirado, $valor_acrescentado,
            $valor_final, $valor_glosado, $valor_aceito, $perc_retirado, $perc_acrescentado,
            $perc_glosado, $perc_aceito
        ]);
        
        if ($resultado) {
            $importados++;
            echo "✓ Importado: <strong>$mes_nome/$convenio_nome</strong> - R$ " . number_format($valor_final, 2, ',', '.') . "<br>";
        } else {
            $erros++;
            echo "✗ Erro: $mes_nome/$convenio_nome<br>";
        }
    }
    
    $pdo->commit();
    
    echo "<br><hr><br>";
    echo "<div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;'>";
    echo "<h3>✅ Importação Concluída com Sucesso!</h3>";
    echo "<p><strong>Registros importados:</strong> $importados</p>";
    echo "<p><strong>Erros:</strong> $erros</p>";
    echo "</div><br>";
    
    echo "<a href='relatorio_mensal.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ver Relatório Mensal</a> ";
    echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Voltar ao Dashboard</a>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "<h3>❌ Erro na Importação</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
