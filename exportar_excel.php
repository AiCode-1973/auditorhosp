<?php
require_once 'db_config.php';

// Definir nome do arquivo
$filename = "relatorio_auditoria_" . date('Y-m-d_H-i') . ".xls";

// Definir headers para download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Definir modo de agrupamento (padrão: mes)
$agrupar_por = isset($_GET['agrupar_por']) && $_GET['agrupar_por'] === 'convenio' ? 'convenio' : 'mes';

// Filtros
$filtro_mes = isset($_GET['filtro_mes']) ? $_GET['filtro_mes'] : '';
$filtro_convenio = isset($_GET['filtro_convenio']) ? $_GET['filtro_convenio'] : '';

// Construção da cláusula WHERE
$where_clauses = [];
$params = [];

if ($filtro_mes) {
    $where_clauses[] = "DATE_FORMAT(f.data_competencia, '%Y-%m') = ?";
    $params[] = $filtro_mes;
}

if ($filtro_convenio) {
    $where_clauses[] = "f.convenio_id = ?";
    $params[] = $filtro_convenio;
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Consulta SQL (Mesma do index.php)
$sql = "
SELECT 
    DATE_FORMAT(f.data_competencia, '%m/%Y') AS Competencia,
    c.nome_convenio AS Convenio,
    SUM(f.valor_total) AS Faturamento,
    SUM(COALESCE(g.valor_glosa, 0)) AS Glosado,
    SUM(COALESCE(r.valor_recursado, 0)) AS Recursado,
    SUM(COALESCE(r.valor_aceito, 0)) AS Aceito,
    SUM(COALESCE(r.valor_recebido, 0)) AS Recebido,
    
    ROUND((SUM(COALESCE(g.valor_glosa, 0)) / NULLIF(SUM(f.valor_total), 0)) * 100, 2) AS Perc_Glosado,
    ROUND((SUM(COALESCE(r.valor_recursado, 0)) / NULLIF(SUM(COALESCE(g.valor_glosa, 0)), 0)) * 100, 2) AS Perc_Recursado,
    ROUND((SUM(COALESCE(r.valor_aceito, 0)) / NULLIF(SUM(COALESCE(r.valor_recursado, 0)), 0)) * 100, 2) AS Perc_Aceito,
    ROUND((SUM(COALESCE(r.valor_recebido, 0)) / NULLIF(SUM(COALESCE(r.valor_aceito, 0)), 0)) * 100, 2) AS Perc_Recebido

FROM 
    faturas f
    LEFT JOIN convenios c ON f.convenio_id = c.id
    LEFT JOIN (
        SELECT fatura_id, SUM(valor_glosa) as valor_glosa 
        FROM glosas 
        GROUP BY fatura_id
    ) g ON f.id = g.fatura_id
    LEFT JOIN (
        SELECT fatura_id, SUM(valor_recursado) as valor_recursado, SUM(valor_aceito) as valor_aceito, SUM(valor_recebido) as valor_recebido
        FROM recursos 
        GROUP BY fatura_id
    ) r ON f.id = r.fatura_id
$where_sql
GROUP BY 
    DATE_FORMAT(f.data_competencia, '%m/%Y'),
    c.nome_convenio
ORDER BY 
    " . ($agrupar_por === 'convenio' ? "c.nome_convenio, f.data_competencia DESC" : "f.data_competencia DESC, c.nome_convenio");

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao gerar relatório.";
    exit;
}

// Agrupar dados
$grupos = [];
foreach ($dados as $linha) {
    $chave = ($agrupar_por === 'convenio') ? $linha['Convenio'] : $linha['Competencia'];
    
    if (!isset($grupos[$chave])) {
        $grupos[$chave] = [
            'itens' => [],
            'total_faturamento' => 0,
            'total_glosado' => 0,
            'total_recursado' => 0,
            'total_aceito' => 0,
            'total_recebido' => 0
        ];
    }
    $grupos[$chave]['itens'][] = $linha;
    $grupos[$chave]['total_faturamento'] += $linha['Faturamento'];
    $grupos[$chave]['total_glosado'] += $linha['Glosado'];
    $grupos[$chave]['total_recursado'] += $linha['Recursado'];
    $grupos[$chave]['total_aceito'] += $linha['Aceito'];
    $grupos[$chave]['total_recebido'] += $linha['Recebido'];
}

// Início do HTML para Excel
?>
<meta charset="UTF-8">
<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 5px; text-align: right; }
    th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
    .text-left { text-align: left; }
    .bg-group { background-color: #e0e0e0; font-weight: bold; }
    .bg-header { background-color: #d9edf7; }
</style>

<table>
    <tr>
        <th colspan="10" style="font-size: 16px; background-color: #fff; border: none;">Relatório de Auditoria</th>
    </tr>
    <tr>
        <td colspan="10" style="text-align: left; border: none;">Gerado em: <?php echo date('d/m/Y H:i'); ?></td>
    </tr>
    <tr><td colspan="10" style="border: none;"></td></tr>

    <?php foreach ($grupos as $titulo_grupo => $grupo): ?>
        <!-- Cabeçalho do Grupo -->
        <tr class="bg-group">
            <td colspan="10" class="text-left" style="background-color: #e0e0e0;">
                <?php echo ($agrupar_por === 'convenio' ? 'Convênio: ' : 'Competência: ') . htmlspecialchars($titulo_grupo); ?>
            </td>
        </tr>
        
        <!-- Cabeçalho da Tabela -->
        <tr class="bg-header">
            <th style="background-color: #d9edf7;"><?php echo ($agrupar_por === 'convenio' ? 'Competência' : 'Convênio'); ?></th>
            <th style="background-color: #d9edf7;">Faturamento</th>
            <th style="background-color: #d9edf7;">Glosado</th>
            <th style="background-color: #d9edf7;">Recursado</th>
            <th style="background-color: #d9edf7;">Aceito</th>
            <th style="background-color: #d9edf7;">Recebido</th>
            <th style="background-color: #d9edf7;">% Glosado</th>
            <th style="background-color: #d9edf7;">% Recursado</th>
            <th style="background-color: #d9edf7;">% Aceito</th>
            <th style="background-color: #d9edf7;">% Recebido</th>
        </tr>

        <!-- Itens -->
        <?php foreach ($grupo['itens'] as $linha): ?>
            <tr>
                <td class="text-left"><?php echo htmlspecialchars(($agrupar_por === 'convenio' ? $linha['Competencia'] : $linha['Convenio'])); ?></td>
                <td><?php echo number_format($linha['Faturamento'], 2, ',', '.'); ?></td>
                <td><?php echo number_format($linha['Glosado'], 2, ',', '.'); ?></td>
                <td><?php echo number_format($linha['Recursado'], 2, ',', '.'); ?></td>
                <td><?php echo number_format($linha['Aceito'], 2, ',', '.'); ?></td>
                <td><?php echo number_format($linha['Recebido'], 2, ',', '.'); ?></td>
                <td><?php echo $linha['Perc_Glosado'] !== null ? number_format($linha['Perc_Glosado'], 2, ',', '.') . '%' : '-'; ?></td>
                <td><?php echo $linha['Perc_Recursado'] !== null ? number_format($linha['Perc_Recursado'], 2, ',', '.') . '%' : '-'; ?></td>
                <td><?php echo $linha['Perc_Aceito'] !== null ? number_format($linha['Perc_Aceito'], 2, ',', '.') . '%' : '-'; ?></td>
                <td><?php echo $linha['Perc_Recebido'] !== null ? number_format($linha['Perc_Recebido'], 2, ',', '.') . '%' : '-'; ?></td>
            </tr>
        <?php endforeach; ?>

        <!-- Totais do Grupo -->
        <tr style="font-weight: bold; background-color: #f9f9f9;">
            <td class="text-left">TOTAL</td>
            <td><?php echo number_format($grupo['total_faturamento'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($grupo['total_glosado'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($grupo['total_recursado'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($grupo['total_aceito'], 2, ',', '.'); ?></td>
            <td><?php echo number_format($grupo['total_recebido'], 2, ',', '.'); ?></td>
            <td colspan="4"></td>
        </tr>
        <tr><td colspan="10" style="border: none;"></td></tr>
    <?php endforeach; ?>
</table>
