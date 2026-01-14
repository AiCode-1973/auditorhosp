<?php
require_once 'db_config.php';
session_start();

$mensagem = '';
$tipo_msg = 'info';

// Processar consolidação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Buscar todas as internações agrupadas por competência e convênio
        $sql = "
            SELECT 
                DATE_FORMAT(i.competencia, '%Y-%m-01') as competencia_consolidada,
                i.convenio_id,
                c.nome_convenio,
                SUM(i.valor_inicial) as valor_inicial,
                SUM(i.valor_retirado) as valor_retirado,
                SUM(i.valor_acrescentado) as valor_acrescentado,
                SUM(i.valor_total) as valor_final,
                SUM(i.valor_glosado) as valor_glosado,
                SUM(i.valor_aceito) as valor_aceito,
                COUNT(*) as qtd_atendimentos
            FROM internacoes i
            JOIN convenios c ON i.convenio_id = c.id
            WHERE i.competencia IS NOT NULL AND i.status = 'Auditado'
            GROUP BY DATE_FORMAT(i.competencia, '%Y-%m'), i.convenio_id
            ORDER BY competencia_consolidada DESC, c.nome_convenio
        ";
        
        $stmt = $pdo->query($sql);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $registros_processados = 0;
        $registros_atualizados = 0;
        $registros_inseridos = 0;
        
        foreach ($grupos as $grupo) {
            // Calcular percentuais
            $perc_retirado = $grupo['valor_inicial'] > 0 
                ? round(($grupo['valor_retirado'] / $grupo['valor_inicial']) * 100, 2) 
                : 0;
            
            $perc_acrescentado = $grupo['valor_inicial'] > 0 
                ? round(($grupo['valor_acrescentado'] / $grupo['valor_inicial']) * 100, 2) 
                : 0;
            
            $perc_glosado = $grupo['valor_final'] > 0 
                ? round(($grupo['valor_glosado'] / $grupo['valor_final']) * 100, 2) 
                : 0;
            
            $perc_aceito = $grupo['valor_glosado'] > 0 
                ? round(($grupo['valor_aceito'] / $grupo['valor_glosado']) * 100, 2) 
                : 0;
            
            // Verificar se já existe registro
            $sql_check = "SELECT id FROM relatorio_mensal_consolidado 
                         WHERE competencia = ? AND convenio_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$grupo['competencia_consolidada'], $grupo['convenio_id']]);
            $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($existe) {
                // Atualizar registro existente
                $sql_update = "UPDATE relatorio_mensal_consolidado SET
                    valor_inicial = ?,
                    valor_retirado = ?,
                    valor_acrescentado = ?,
                    valor_final = ?,
                    valor_glosado = ?,
                    valor_aceito = ?,
                    perc_retirado = ?,
                    perc_acrescentado = ?,
                    perc_glosado = ?,
                    perc_aceito = ?
                    WHERE id = ?";
                
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([
                    $grupo['valor_inicial'],
                    $grupo['valor_retirado'],
                    $grupo['valor_acrescentado'],
                    $grupo['valor_final'],
                    $grupo['valor_glosado'],
                    $grupo['valor_aceito'],
                    $perc_retirado,
                    $perc_acrescentado,
                    $perc_glosado,
                    $perc_aceito,
                    $existe['id']
                ]);
                
                $registros_atualizados++;
            } else {
                // Inserir novo registro
                $sql_insert = "INSERT INTO relatorio_mensal_consolidado 
                    (competencia, convenio_id, valor_inicial, valor_retirado, valor_acrescentado, 
                     valor_final, valor_glosado, valor_aceito, perc_retirado, perc_acrescentado, 
                     perc_glosado, perc_aceito)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([
                    $grupo['competencia_consolidada'],
                    $grupo['convenio_id'],
                    $grupo['valor_inicial'],
                    $grupo['valor_retirado'],
                    $grupo['valor_acrescentado'],
                    $grupo['valor_final'],
                    $grupo['valor_glosado'],
                    $grupo['valor_aceito'],
                    $perc_retirado,
                    $perc_acrescentado,
                    $perc_glosado,
                    $perc_aceito
                ]);
                
                $registros_inseridos++;
            }
            
            $registros_processados++;
        }
        
        $mensagem = "Consolidação concluída com sucesso!<br>";
        $mensagem .= "Total processado: $registros_processados registros<br>";
        $mensagem .= "Novos inseridos: $registros_inseridos<br>";
        $mensagem .= "Atualizados: $registros_atualizados";
        $tipo_msg = 'success';
        
        // Registrar log de consolidação
        if (isset($_SESSION['usuario_id'])) {
            $usuario_id = $_SESSION['usuario_id'];
            $usuario_nome = $_SESSION['usuario_nome'];
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
            
            $sql_log = "INSERT INTO logs_atendimento (usuario_id, usuario_nome, atendimento_id, acao, detalhes, ip_address) 
                        VALUES (?, ?, NULL, 'CONSOLIDACAO', ?, ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([
                $usuario_id,
                $usuario_nome,
                $mensagem,
                $ip_address
            ]);
        }
        
    } catch (PDOException $e) {
        $mensagem = "Erro ao consolidar dados: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}

// Buscar prévia dos dados
try {
    $sql_previa = "
        SELECT 
            DATE_FORMAT(i.competencia, '%m/%Y') as competencia_formatada,
            c.nome_convenio,
            COUNT(*) as qtd_atendimentos,
            SUM(i.valor_inicial) as valor_inicial,
            SUM(i.valor_total) as valor_final
        FROM internacoes i
        JOIN convenios c ON i.convenio_id = c.id
        WHERE i.competencia IS NOT NULL
        GROUP BY DATE_FORMAT(i.competencia, '%Y-%m'), i.convenio_id
        ORDER BY i.competencia DESC, c.nome_convenio
        LIMIT 10
    ";
    
    $stmt_previa = $pdo->query($sql_previa);
    $previa = $stmt_previa->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $sql_count = "
        SELECT COUNT(DISTINCT CONCAT(DATE_FORMAT(i.competencia, '%Y-%m'), '-', i.convenio_id)) as total
        FROM internacoes i
        WHERE i.competencia IS NOT NULL
    ";
    $stmt_count = $pdo->query($sql_count);
    $total_grupos = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    $previa = [];
    $total_grupos = 0;
}

include 'includes/header.php';
?>

<div class="container mx-auto mt-8 max-w-6xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Consolidar Atendimentos → Relatório Mensal</h2>
        <p class="text-gray-600">Esta ferramenta consolida os dados de atendimentos agrupando por competência e convênio para o relatório mensal.</p>
    </div>

    <?php if ($mensagem): ?>
        <div class="<?php echo $tipo_msg == 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> border px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold"><?php echo $tipo_msg == 'success' ? 'Sucesso!' : 'Erro!'; ?></strong>
            <div class="mt-1"><?php echo $mensagem; ?></div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">Grupos a Consolidar</div>
            <div class="text-3xl font-bold text-blue-600"><?php echo $total_grupos; ?></div>
            <div class="text-xs text-gray-500 mt-1">Competência + Convênio</div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">Status</div>
            <div class="text-lg font-semibold text-green-600">Pronto para consolidar</div>
            <div class="text-xs text-gray-500 mt-1">Os dados serão atualizados</div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">Ação</div>
            <form method="POST" action="" onsubmit="return confirm('Confirma a consolidação dos dados?');">
                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition">
                    ⚡ Consolidar Agora
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <div class="bg-gray-50 px-6 py-3 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Prévia dos Dados (últimos 10 grupos)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Competência</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Convênio</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qtd Atend.</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Inicial</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Final</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($previa) > 0): ?>
                        <?php foreach ($previa as $item): ?>
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900"><?php echo $item['competencia_formatada']; ?></td>
                                <td class="px-6 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($item['nome_convenio']); ?></td>
                                <td class="px-6 py-3 text-sm text-center text-gray-900"><?php echo $item['qtd_atendimentos']; ?></td>
                                <td class="px-6 py-3 text-sm text-right text-gray-900">R$ <?php echo number_format($item['valor_inicial'], 2, ',', '.'); ?></td>
                                <td class="px-6 py-3 text-sm text-right text-gray-900">R$ <?php echo number_format($item['valor_final'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum dado disponível para consolidação.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-semibold text-blue-800 mb-2">ℹ️ Como funciona:</h4>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Os atendimentos são agrupados por <strong>competência e convênio</strong></li>
            <li>• Os valores são <strong>somados</strong> (valor inicial, retirado, acrescentado, final, glosado, aceito)</li>
            <li>• Os percentuais são <strong>calculados automaticamente</strong></li>
            <li>• Se o registro já existe, ele é <strong>atualizado</strong></li>
            <li>• Se não existe, um <strong>novo registro</strong> é criado</li>
            <li>• Um log da consolidação é registrado automaticamente</li>
        </ul>
    </div>

    <div class="mt-6 flex justify-between">
        <a href="internacoes.php" class="text-blue-600 hover:underline">← Voltar para Atendimentos</a>
        <a href="relatorio_mensal.php" class="text-blue-600 hover:underline">Ver Relatório Mensal →</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
