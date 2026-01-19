<?php
require_once 'db_config.php';
session_start();

$mensagem = '';
$tipo_msg = 'info';

// LIMPEZA PRÉVIA: Sempre deletar registros com setor vazio ANTES de qualquer processamento
try {
    // 1. Marcar registros de pa_ambulatorio com setor vazio como NÃO auditados
    $sql_desauditar = "UPDATE pa_ambulatorio 
                       SET status = 'Pendente' 
                       WHERE status = 'Auditado' 
                         AND (setor IS NULL OR setor = '' OR LENGTH(setor) = 0 OR TRIM(setor) = '')";
    $pdo->exec($sql_desauditar);
    
    // 2. Deletar registros consolidados com setor vazio
    $sql_limpeza = "DELETE FROM relatorio_mensal_pa_consolidado 
                    WHERE setor IS NULL 
                       OR setor = '' 
                       OR LENGTH(setor) = 0 
                       OR TRIM(setor) = ''";
    $pdo->exec($sql_limpeza);
} catch (PDOException $e) {
    // Ignorar erros de limpeza prévia
}

// Processar consolidação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // PASSO 1: DELETAR registros com setor vazio da tabela consolidada (limpeza de dados antigos)
        $sql_deletar_vazios = "DELETE FROM relatorio_mensal_pa_consolidado 
                               WHERE setor IS NULL 
                                  OR setor = '' 
                                  OR LENGTH(setor) = 0 
                                  OR TRIM(setor) = ''";
        $registros_deletados = $pdo->exec($sql_deletar_vazios);
        
        // PASSO 2: Remover duplicados que possam existir
        $sql_encontrar_dups = "
            SELECT competencia, convenio_id, setor, MIN(id) as id_manter
            FROM relatorio_mensal_pa_consolidado
            WHERE setor IS NOT NULL AND TRIM(setor) != ''
            GROUP BY competencia, convenio_id, setor
            HAVING COUNT(*) > 1
        ";
        $stmt_dups = $pdo->query($sql_encontrar_dups);
        $duplicados = $stmt_dups->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($duplicados as $dup) {
            // Manter apenas o registro mais antigo (MIN id), remover os demais
            $sql_remover = "DELETE FROM relatorio_mensal_pa_consolidado 
                           WHERE competencia = ? AND convenio_id = ? AND setor = ? AND id != ?";
            $stmt_rem = $pdo->prepare($sql_remover);
            $stmt_rem->execute([
                $dup['competencia'], 
                $dup['convenio_id'], 
                $dup['setor'], 
                $dup['id_manter']
            ]);
        }
        
        // PASSO 3: Buscar apenas registros com setor preenchido para consolidação
        $sql = "
            SELECT 
                DATE_FORMAT(p.competencia, '%Y-%m-01') as competencia_consolidada,
                p.convenio_id,
                c.nome_convenio,
                p.setor,
                SUM(p.valor_inicial) as valor_inicial,
                SUM(p.valor_retirado) as valor_retirado,
                SUM(p.valor_acrescentado) as valor_acrescentado,
                SUM(p.valor_total) as valor_final,
                SUM(p.valor_glosado) as valor_glosado,
                SUM(p.valor_aceito) as valor_aceito,
                SUM(p.valor_faturado) as valor_faturado,
                COUNT(*) as qtd_atendimentos
            FROM pa_ambulatorio p
            JOIN convenios c ON p.convenio_id = c.id
            WHERE p.competencia IS NOT NULL 
              AND p.status = 'Auditado'
              AND p.setor IS NOT NULL 
              AND LENGTH(p.setor) > 0
            GROUP BY DATE_FORMAT(p.competencia, '%Y-%m'), p.convenio_id, p.setor
            ORDER BY competencia_consolidada DESC, c.nome_convenio, p.setor
        ";
        
        $stmt = $pdo->query($sql);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $registros_processados = 0;
        $registros_atualizados = 0;
        $registros_inseridos = 0;
        
        foreach ($grupos as $grupo) {
            // Usar setor diretamente (já filtrado na query, sem vazios)
            $setor = trim($grupo['setor']);
            
            // PROTEÇÃO EXTRA: Pular registros com setor vazio (validação rigorosa)
            if (empty($setor) || strlen($setor) == 0) {
                continue; // Pular este grupo
            }
            
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
            
            // Verificar se já existe registro (buscar APENAS por setor exato)
            $sql_check = "SELECT id FROM relatorio_mensal_pa_consolidado 
                         WHERE competencia = ? AND convenio_id = ? AND setor = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$grupo['competencia_consolidada'], $grupo['convenio_id'], $setor]);
            $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($existe) {
                // Atualizar registro existente
                $sql_update = "UPDATE relatorio_mensal_pa_consolidado SET
                    valor_inicial = ?,
                    valor_retirado = ?,
                    valor_acrescentado = ?,
                    valor_final = ?,
                    valor_glosado = ?,
                    valor_aceito = ?,
                    valor_faturado = ?,
                    perc_retirado = ?,
                    perc_acrescentado = ?,
                    perc_glosado = ?,
                    perc_aceito = ?,
                    qtd_atendimentos = ?
                    WHERE id = ?";
                
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([
                    $grupo['valor_inicial'],
                    $grupo['valor_retirado'],
                    $grupo['valor_acrescentado'],
                    $grupo['valor_final'],
                    $grupo['valor_glosado'],
                    $grupo['valor_aceito'],
                    $grupo['valor_faturado'],
                    $perc_retirado,
                    $perc_acrescentado,
                    $perc_glosado,
                    $perc_aceito,
                    $grupo['qtd_atendimentos'],
                    $existe['id']
                ]);
                
                $registros_atualizados++;
            } else {
                // Inserir novo registro
                $sql_insert = "INSERT INTO relatorio_mensal_pa_consolidado 
                    (competencia, convenio_id, setor, valor_inicial, valor_retirado, valor_acrescentado, 
                     valor_final, valor_glosado, valor_aceito, valor_faturado, perc_retirado, perc_acrescentado, 
                     perc_glosado, perc_aceito, qtd_atendimentos)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([
                    $grupo['competencia_consolidada'],
                    $grupo['convenio_id'],
                    $setor,
                    $grupo['valor_inicial'],
                    $grupo['valor_retirado'],
                    $grupo['valor_acrescentado'],
                    $grupo['valor_final'],
                    $grupo['valor_glosado'],
                    $grupo['valor_aceito'],
                    $grupo['valor_faturado'],
                    $perc_retirado,
                    $perc_acrescentado,
                    $perc_glosado,
                    $perc_aceito,
                    $grupo['qtd_atendimentos']
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
            
            // Note: atendimento_id is NULL as it's a global action
            $sql_log = "INSERT INTO logs_atendimento (usuario_id, usuario_nome, atendimento_id, acao, detalhes, ip_address) 
                        VALUES (?, ?, NULL, 'CONSOLIDACAO_PA', ?, ?)";
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
            DATE_FORMAT(p.competencia, '%m/%Y') as competencia_formatada,
            c.nome_convenio,
            COALESCE(NULLIF(p.setor, ''), 'N/D') as setor,
            COUNT(*) as qtd_atendimentos,
            SUM(p.valor_inicial) as valor_inicial,
            SUM(p.valor_total) as valor_final
        FROM pa_ambulatorio p
        JOIN convenios c ON p.convenio_id = c.id
        WHERE p.competencia IS NOT NULL
        GROUP BY DATE_FORMAT(p.competencia, '%Y-%m'), p.convenio_id, COALESCE(NULLIF(p.setor, ''), 'N/D')
        ORDER BY p.competencia DESC, c.nome_convenio, setor
        LIMIT 10
    ";
    
    $stmt_previa = $pdo->query($sql_previa);
    $previa = $stmt_previa->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $sql_count = "
        SELECT COUNT(DISTINCT CONCAT(DATE_FORMAT(p.competencia, '%Y-%m'), '-', p.convenio_id, '-', COALESCE(NULLIF(p.setor, ''), 'N/D'))) as total
        FROM pa_ambulatorio p
        WHERE p.competencia IS NOT NULL
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
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Consolidar Dados PA/Ambulatório → Relatório Mensal</h2>
        <p class="text-gray-600">Esta ferramenta consolida os dados de atendimentos agrupando por competência, convênio e setor.</p>
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
            <div class="text-xs text-gray-500 mt-1">Competência + Convênio + Setor</div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">Status</div>
            <div class="text-lg font-semibold text-green-600">Pronto para consolidar</div>
            <div class="text-xs text-gray-500 mt-1">Os dados serão atualizados</div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">Ação</div>
            <form method="POST" action="" onsubmit="return confirm('Confirma a consolidação dos dados de PA/Ambulatório?');">
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
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Setor</th>
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
                                <td class="px-6 py-3 text-sm text-center text-gray-900">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $item['setor'] == 'PA' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                        <?php echo htmlspecialchars($item['setor']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-center text-gray-900"><?php echo $item['qtd_atendimentos']; ?></td>
                                <td class="px-6 py-3 text-sm text-right text-gray-900">R$ <?php echo number_format($item['valor_inicial'], 2, ',', '.'); ?></td>
                                <td class="px-6 py-3 text-sm text-right text-gray-900">R$ <?php echo number_format($item['valor_final'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum dado disponível para consolidação.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 flex justify-between">
        <a href="pa_ambulatorio.php" class="text-blue-600 hover:underline">← Voltar para Atendimentos</a>
        <a href="relatorio_mensal_pa_ambulatorio.php" class="text-blue-600 hover:underline">Ver Relatório Mensal →</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
