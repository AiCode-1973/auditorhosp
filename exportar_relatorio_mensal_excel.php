<?php
require_once 'db_config.php';

// Definir nome do arquivo
$filename = "relatorio_mensal_consolidado_" . date('Y-m-d_H-i') . ".xls";

// Definir headers para download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Filtros
$filtro_mes = isset($_GET['filtro_mes']) ? $_GET['filtro_mes'] : '';
$filtro_convenio = isset($_GET['filtro_convenio']) ? $_GET['filtro_convenio'] : '';

// Construção da cláusula WHERE
$where_clauses = [];
$params = [];

if ($filtro_mes) {
    $where_clauses[] = "DATE_FORMAT(r.competencia, '%Y-%m') = ?";
    $params[] = $filtro_mes;
}

if ($filtro_convenio) {
    $where_clauses[] = "r.convenio_id = ?";
    $params[] = $filtro_convenio;
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Consulta SQL
$sql = "
    SELECT 
        DATE_FORMAT(r.competencia, '%m/%Y') AS Competencia,
        c.nome_convenio AS Convenio,
        r.valor_inicial,
        r.valor_retirado,
        r.valor_acrescentado,
        r.valor_final,
        r.valor_glosado,
        r.valor_aceito,
        r.perc_retirado,
        r.perc_acrescentado,
        r.perc_glosado,
        r.perc_aceito
    FROM relatorio_mensal_consolidado r
    JOIN convenios c ON r.convenio_id = c.id
    $where_sql
    ORDER BY r.competencia DESC, c.nome_convenio ASC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao gerar relatório.";
    exit;
}

// Início do HTML para Excel
?>
<meta charset="UTF-8">
<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 5px; text-align: right; }
    th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
    .text-left { text-align: left; }
    .bg-header { background-color: #d9edf7; }
    .bg-percent { background-color: #e3f2fd; }
</style>

<table>
    <tr>
        <th colspan="12" style="font-size: 16px; background-color: #fff; border: none;">Relatório Mensal Consolidado</th>
    </tr>
    <tr>
        <td colspan="12" style="text-align: left; border: none;">Gerado em: <?php echo date('d/m/Y H:i'); ?></td>
    </tr>
    <tr><td colspan="12" style="border: none;"></td></tr>

    <!-- Cabeçalho da Tabela -->
    <tr class="bg-header">
        <th style="background-color: #d9edf7;">Competência</th>
        <th style="background-color: #d9edf7;">Convênio</th>
        <th style="background-color: #d9edf7;">Valor Inicial</th>
        <th style="background-color: #d9edf7;">Valor Retirado</th>
        <th style="background-color: #d9edf7;">Valor Acrescentado</th>
        <th style="background-color: #d9edf7;">Valor Final</th>
        <th style="background-color: #d9edf7;">Valor Glosado</th>
        <th style="background-color: #d9edf7;">Valor Aceito</th>
        <th style="background-color: #e3f2fd;">% Retirado</th>
        <th style="background-color: #e3f2fd;">% Acrescentado</th>
        <th style="background-color: #e3f2fd;">% Glosado</th>
        <th style="background-color: #e3f2fd;">% Aceito</th>
    </tr>

    <!-- Itens -->
    <?php 
    $total_inicial = 0;
    $total_retirado = 0;
    $total_acrescentado = 0;
    $total_final = 0;
    $total_glosado = 0;
    $total_aceito = 0;
    
    foreach ($dados as $linha): 
        $total_inicial += $linha['valor_inicial'];
        $total_retirado += $linha['valor_retirado'];
        $total_acrescentado += $linha['valor_acrescentado'];
        $total_final += $linha['valor_final'];
        $total_glosado += $linha['valor_glosado'];
        $total_aceito += $linha['valor_aceito'];
    ?>
        <tr>
            <td class="text-left"><?php echo htmlspecialchars($linha['Competencia']); ?></td>
            <td class="text-left"><?php echo htmlspecialchars($linha['Convenio']); ?></td>
            <td><?php echo number_format($linha['valor_inicial'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($linha['valor_retirado'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($linha['valor_acrescentado'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($linha['valor_final'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($linha['valor_glosado'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($linha['valor_aceito'], 2, ',', '.'); ?></td>
            <td class="bg-percent"><?php echo number_format($linha['perc_retirado'], 2, ',', '.'); ?>%</td>
            <td class="bg-percent"><?php echo number_format($linha['perc_acrescentado'], 2, ',', '.'); ?>%</td>
            <td class="bg-percent"><?php echo number_format($linha['perc_glosado'], 2, ',', '.'); ?>%</td>
            <td class="bg-percent"><?php echo number_format($linha['perc_aceito'], 2, ',', '.'); ?>%</td>
        </tr>
    <?php endforeach; ?>

    <!-- Totais -->
    <tr style="font-weight: bold; background-color: #f9f9f9;">
        <td class="text-left" colspan="2">TOTAIS</td>
        <td><?php echo number_format($total_inicial, 2, ',', '.'); ?></td>
        <td><?php echo number_format($total_retirado, 2, ',', '.'); ?></td>
        <td><?php echo number_format($total_acrescentado, 2, ',', '.'); ?></td>
        <td><?php echo number_format($total_final, 2, ',', '.'); ?></td>
        <td><?php echo number_format($total_glosado, 2, ',', '.'); ?></td>
        <td><?php echo number_format($total_aceito, 2, ',', '.'); ?></td>
        <td colspan="4"></td>
    </tr>
</table>
