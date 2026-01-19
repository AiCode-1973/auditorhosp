<?php
require_once 'db_config.php';
session_start();

// Verificar se é admin
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] != 'admin') {
    die("Acesso negado. Apenas administradores podem executar este script.");
}

$mensagem = '';
$tipo_msg = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Primeiro, normalizar setores vazios para 'N/D' na tabela pa_ambulatorio
        $sql_update_pa = "UPDATE pa_ambulatorio SET setor = 'N/D' WHERE setor IS NULL OR setor = ''";
        $stmt_update = $pdo->query($sql_update_pa);
        $registros_atualizados_pa = $stmt_update->rowCount();
        
        // 2. Normalizar setores vazios na tabela consolidada
        $sql_update_consolidado = "UPDATE relatorio_mensal_pa_consolidado SET setor = 'N/D' WHERE setor IS NULL OR setor = ''";
        $stmt_update_cons = $pdo->query($sql_update_consolidado);
        $registros_atualizados_cons = $stmt_update_cons->rowCount();
        
        // 3. Identificar e remover duplicados na tabela consolidada (manter apenas o mais recente)
        $sql_duplicados = "
            SELECT 
                competencia, 
                convenio_id, 
                setor, 
                COUNT(*) as total,
                GROUP_CONCAT(id ORDER BY id DESC) as ids
            FROM relatorio_mensal_pa_consolidado
            GROUP BY competencia, convenio_id, setor
            HAVING COUNT(*) > 1
        ";
        
        $stmt_dup = $pdo->query($sql_duplicados);
        $duplicados = $stmt_dup->fetchAll(PDO::FETCH_ASSOC);
        
        $registros_removidos = 0;
        
        foreach ($duplicados as $dup) {
            $ids = explode(',', $dup['ids']);
            // Manter o primeiro (mais recente), remover os demais
            $ids_para_remover = array_slice($ids, 1);
            
            if (count($ids_para_remover) > 0) {
                $placeholders = implode(',', array_fill(0, count($ids_para_remover), '?'));
                $sql_delete = "DELETE FROM relatorio_mensal_pa_consolidado WHERE id IN ($placeholders)";
                $stmt_delete = $pdo->prepare($sql_delete);
                $stmt_delete->execute($ids_para_remover);
                $registros_removidos += count($ids_para_remover);
            }
        }
        
        $pdo->commit();
        
        $mensagem = "Limpeza concluída com sucesso!<br>";
        $mensagem .= "• Atendimentos PA/Ambulatório com setor normalizado: $registros_atualizados_pa<br>";
        $mensagem .= "• Consolidados com setor normalizado: $registros_atualizados_cons<br>";
        $mensagem .= "• Registros duplicados removidos: $registros_removidos<br>";
        $mensagem .= "• Grupos duplicados encontrados: " . count($duplicados);
        $tipo_msg = 'success';
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao limpar duplicados: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}

// Buscar duplicados existentes
try {
    $sql_verificar = "
        SELECT 
            DATE_FORMAT(competencia, '%m/%Y') as competencia_fmt,
            competencia,
            convenio_id,
            setor,
            COUNT(*) as total,
            GROUP_CONCAT(id ORDER BY id) as ids
        FROM relatorio_mensal_pa_consolidado
        GROUP BY competencia, convenio_id, setor
        HAVING COUNT(*) > 1
        ORDER BY competencia DESC
    ";
    
    $stmt_ver = $pdo->query($sql_verificar);
    $duplicados_existentes = $stmt_ver->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar setores vazios
    $sql_vazios_pa = "SELECT COUNT(*) as total FROM pa_ambulatorio WHERE setor IS NULL OR setor = ''";
    $stmt_vazios = $pdo->query($sql_vazios_pa);
    $setores_vazios_pa = $stmt_vazios->fetch(PDO::FETCH_ASSOC)['total'];
    
    $sql_vazios_cons = "SELECT COUNT(*) as total FROM relatorio_mensal_pa_consolidado WHERE setor IS NULL OR setor = ''";
    $stmt_vazios_cons = $pdo->query($sql_vazios_cons);
    $setores_vazios_cons = $stmt_vazios_cons->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    $duplicados_existentes = [];
    $setores_vazios_pa = 0;
    $setores_vazios_cons = 0;
}

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">🧹 Limpeza de Duplicados - PA/Ambulatório</h1>
        <p class="text-gray-600">Remove registros duplicados e normaliza setores vazios.</p>
    </div>

    <?php if ($mensagem): ?>
        <div class="mb-6 p-4 rounded-lg <?php 
            echo $tipo_msg === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-700' : 
                ($tipo_msg === 'error' ? 'bg-red-50 border-l-4 border-red-500 text-red-700' : 
                'bg-blue-50 border-l-4 border-blue-500 text-blue-700');
        ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <!-- Cards de Status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 <?php echo count($duplicados_existentes) > 0 ? 'border-red-500' : 'border-green-500'; ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Duplicados Consolidado</p>
                    <p class="text-3xl font-bold <?php echo count($duplicados_existentes) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo count($duplicados_existentes); ?>
                    </p>
                </div>
                <div class="<?php echo count($duplicados_existentes) > 0 ? 'bg-red-100' : 'bg-green-100'; ?> rounded-full p-3">
                    <svg class="w-8 h-8 <?php echo count($duplicados_existentes) > 0 ? 'text-red-600' : 'text-green-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 <?php echo $setores_vazios_pa > 0 ? 'border-yellow-500' : 'border-green-500'; ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Setores Vazios (PA)</p>
                    <p class="text-3xl font-bold <?php echo $setores_vazios_pa > 0 ? 'text-yellow-600' : 'text-green-600'; ?>">
                        <?php echo $setores_vazios_pa; ?>
                    </p>
                </div>
                <div class="<?php echo $setores_vazios_pa > 0 ? 'bg-yellow-100' : 'bg-green-100'; ?> rounded-full p-3">
                    <svg class="w-8 h-8 <?php echo $setores_vazios_pa > 0 ? 'text-yellow-600' : 'text-green-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 <?php echo $setores_vazios_cons > 0 ? 'border-yellow-500' : 'border-green-500'; ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Setores Vazios (Cons.)</p>
                    <p class="text-3xl font-bold <?php echo $setores_vazios_cons > 0 ? 'text-yellow-600' : 'text-green-600'; ?>">
                        <?php echo $setores_vazios_cons; ?>
                    </p>
                </div>
                <div class="<?php echo $setores_vazios_cons > 0 ? 'bg-yellow-100' : 'bg-green-100'; ?> rounded-full p-3">
                    <svg class="w-8 h-8 <?php echo $setores_vazios_cons > 0 ? 'text-yellow-600' : 'text-green-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($duplicados_existentes) > 0 || $setores_vazios_pa > 0 || $setores_vazios_cons > 0): ?>
        <!-- Alerta e Ação -->
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-500 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-2">⚠️ Problemas Detectados</h3>
                    <p class="text-yellow-700 mb-4">
                        Foram encontrados <?php echo count($duplicados_existentes); ?> grupo(s) duplicado(s) e 
                        <?php echo $setores_vazios_pa + $setores_vazios_cons; ?> registro(s) com setor vazio.
                    </p>
                    <div class="bg-white p-4 rounded border border-yellow-200 mb-4">
                        <h4 class="font-semibold text-gray-800 mb-2">O que será feito:</h4>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>Normalizar setores vazios ou NULL para "N/D" (Não Definido)</li>
                            <li>Agrupar registros duplicados (mantendo o mais recente)</li>
                            <li>Remover duplicatas antigas</li>
                        </ul>
                    </div>
                    <form method="POST">
                        <button type="submit" name="confirmar" value="1" 
                                onclick="return confirm('Tem certeza que deseja executar a limpeza? Esta ação não pode ser desfeita.')"
                                class="bg-yellow-600 text-white px-6 py-3 rounded-md hover:bg-yellow-700 transition font-semibold">
                            🧹 Executar Limpeza
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if (count($duplicados_existentes) > 0): ?>
            <!-- Tabela de Duplicados -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 bg-red-50 border-b border-red-200">
                    <h3 class="text-lg font-semibold text-red-800">Registros Duplicados Encontrados</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Competência</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Convênio ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Setor</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Qtd. Duplicados</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">IDs</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($duplicados_existentes as $dup): ?>
                                <tr class="hover:bg-red-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($dup['competencia_fmt']); ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($dup['convenio_id']); ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                                            if (empty($dup['setor']) || $dup['setor'] == '') {
                                                echo 'bg-red-100 text-red-800';
                                            } elseif ($dup['setor'] == 'PA') {
                                                echo 'bg-blue-100 text-blue-800';
                                            } elseif ($dup['setor'] == 'AMB') {
                                                echo 'bg-purple-100 text-purple-800';
                                            } else {
                                                echo 'bg-green-100 text-green-800';
                                            }
                                        ?>">
                                            <?php echo empty($dup['setor']) ? '(VAZIO)' : htmlspecialchars($dup['setor']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-bold text-red-600">
                                        <?php echo $dup['total']; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 font-mono">
                                        <?php echo htmlspecialchars($dup['ids']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Tudo OK -->
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-green-800">✅ Tudo Certo!</h3>
                    <p class="text-green-700">Não foram encontrados duplicados ou setores vazios.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-6">
        <a href="pa_ambulatorio.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Voltar para PA/Ambulatório
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
