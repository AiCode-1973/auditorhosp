<?php
require_once 'db_config.php';

// Definir nome do arquivo
$filename = "relatorio_internacoes_" . date('Y-m-d_H-i') . ".xls";

// Definir headers para download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Filtros
$filtro_paciente = isset($_GET['filtro_paciente']) ? $_GET['filtro_paciente'] : '';
$filtro_status = isset($_GET['filtro_status']) ? $_GET['filtro_status'] : '';
$filtro_competencia = isset($_GET['filtro_competencia']) ? $_GET['filtro_competencia'] : '';

// Construção da query
$where_clauses = [];
$params = [];

if ($filtro_paciente) {
    $where_clauses[] = "i.paciente LIKE ?";
    $params[] = "%$filtro_paciente%";
}

if ($filtro_status) {
    $where_clauses[] = "i.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_competencia) {
    $where_clauses[] = "i.competencia = ?";
    $params[] = $filtro_competencia . '-01';
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

try {
    $sql = "
        SELECT 
            i.*, 
            c.nome_convenio 
        FROM internacoes i
        JOIN convenios c ON i.convenio_id = c.id
        $where_sql
        ORDER BY i.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $internacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao gerar relatório: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Atendimento</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Competência</th>
                <th>Recebimento</th>
                <th>Paciente</th>
                <th>Convênio</th>
                <th>Guia</th>
                <th>Data Entrada</th>
                <th>Data Saída</th>
                <th>Valor Inicial</th>
                <th>Valor Retirado</th>
                <th>Valor Acrescentado</th>
                <th>Valor Final</th>
                <th>Valor Glosado</th>
                <th>Valor Aceito</th>
                <th>Valor Faturado</th>
                <th>Conta Corrigida</th>
                <th>Falta NF</th>
                <th>Status</th>
                <th>Observações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($internacoes as $int): ?>
                <tr>
                    <td><?php echo $int['competencia'] ? date('m/Y', strtotime($int['competencia'])) : '-'; ?></td>
                    <td><?php echo $int['data_recebimento'] ? date('d/m/Y', strtotime($int['data_recebimento'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($int['paciente']); ?></td>
                    <td><?php echo htmlspecialchars($int['nome_convenio']); ?></td>
                    <td><?php echo htmlspecialchars($int['guia_paciente']); ?></td>
                    <td><?php echo $int['data_entrada'] ? date('d/m/Y', strtotime($int['data_entrada'])) : '-'; ?></td>
                    <td><?php echo $int['data_saida'] ? date('d/m/Y', strtotime($int['data_saida'])) : '-'; ?></td>
                    <td class="text-right"><?php echo number_format($int['valor_inicial'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($int['valor_retirado'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($int['valor_acrescentado'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($int['valor_total'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($int['valor_glosado'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($int['valor_aceito'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($int['valor_faturado'], 2, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $int['conta_corrigida'] ? 'Sim' : 'Não'; ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($int['falta_nf']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($int['status']); ?></td>
                    <td><?php echo htmlspecialchars($int['observacoes']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>