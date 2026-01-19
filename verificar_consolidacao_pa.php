<?php
require_once 'db_config.php';
include 'includes/header.php';

// Filtro de competência
$filtro_competencia = isset($_GET['competencia']) ? $_GET['competencia'] : '';

// Construir WHERE para filtro
$where_filter = "";
$params = [];
if ($filtro_competencia) {
    $where_filter = "WHERE DATE_FORMAT(p.competencia, '%Y-%m') = ?";
    $params[] = $filtro_competencia;
}

// 1. BUSCAR DADOS DOS ATENDIMENTOS INDIVIDUAIS (pa_ambulatorio)
try {
    $sql_individual = "
        SELECT 
            DATE_FORMAT(p.competencia, '%Y-%m-01') as competencia,
            DATE_FORMAT(p.competencia, '%m/%Y') as competencia_fmt,
            p.convenio_id,
            c.nome_convenio,
            p.setor,
            COUNT(*) as qtd_atendimentos,
            SUM(p.valor_inicial) as soma_inicial,
            SUM(p.valor_retirado) as soma_retirado,
            SUM(p.valor_acrescentado) as soma_acrescentado,
            SUM(p.valor_total) as soma_final,
            SUM(p.valor_glosado) as soma_glosado,
            SUM(p.valor_aceito) as soma_aceito,
            SUM(p.valor_faturado) as soma_faturado
        FROM pa_ambulatorio p
        JOIN convenios c ON p.convenio_id = c.id
        $where_filter
        GROUP BY DATE_FORMAT(p.competencia, '%Y-%m'), p.convenio_id, p.setor
        ORDER BY p.competencia DESC, c.nome_convenio, p.setor
    ";
    
    $stmt_individual = $pdo->prepare($sql_individual);
    $stmt_individual->execute($params);
    $dados_individual = $stmt_individual->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dados_individual = [];
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>Erro ao buscar atendimentos individuais: " . $e->getMessage() . "</div>";
}

// 2. BUSCAR DADOS CONSOLIDADOS (relatorio_mensal_pa_consolidado)
try {
    $sql_consolidado = "
        SELECT 
            DATE_FORMAT(r.competencia, '%Y-%m-01') as competencia,
            DATE_FORMAT(r.competencia, '%m/%Y') as competencia_fmt,
            r.convenio_id,
            c.nome_convenio,
            r.setor,
            r.qtd_atendimentos,
            r.valor_inicial,
            r.valor_retirado,
            r.valor_acrescentado,
            r.valor_final,
            r.valor_glosado,
            r.valor_aceito,
            r.valor_faturado
        FROM relatorio_mensal_pa_consolidado r
        JOIN convenios c ON r.convenio_id = c.id
        $where_filter
        ORDER BY r.competencia DESC, c.nome_convenio, r.setor
    ";
    
    $stmt_consolidado = $pdo->prepare($sql_consolidado);
    $stmt_consolidado->execute($params);
    $dados_consolidado = $stmt_consolidado->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dados_consolidado = [];
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>Erro ao buscar consolidados: " . $e->getMessage() . "</div>";
}

// 3. COMPARAR DADOS (criar array indexado para facilitar comparação)
$consolidado_map = [];
foreach ($dados_consolidado as $item) {
    $chave = $item['competencia'] . '|' . $item['convenio_id'] . '|' . $item['setor'];
    $consolidado_map[$chave] = $item;
}

$diferencas = [];
$total_registros = 0;
$registros_ok = 0;
$registros_erro = 0;

foreach ($dados_individual as $individual) {
    $total_registros++;
    $chave = $individual['competencia'] . '|' . $individual['convenio_id'] . '|' . $individual['setor'];
    
    if (isset($consolidado_map[$chave])) {
        $consolidado = $consolidado_map[$chave];
        
        // Comparar valores (com tolerância de 0.01 por conta de arredondamentos)
        $divergencias = [];
        
        if (abs($individual['qtd_atendimentos'] - $consolidado['qtd_atendimentos']) > 0) {
            $divergencias[] = "Qtd. Atendimentos";
        }
        if (abs($individual['soma_inicial'] - $consolidado['valor_inicial']) > 0.01) {
            $divergencias[] = "Valor Inicial";
        }
        if (abs($individual['soma_retirado'] - $consolidado['valor_retirado']) > 0.01) {
            $divergencias[] = "Valor Retirado";
        }
        if (abs($individual['soma_acrescentado'] - $consolidado['valor_acrescentado']) > 0.01) {
            $divergencias[] = "Valor Acrescentado";
        }
        if (abs($individual['soma_final'] - $consolidado['valor_final']) > 0.01) {
            $divergencias[] = "Valor Final";
        }
        if (abs($individual['soma_glosado'] - $consolidado['valor_glosado']) > 0.01) {
            $divergencias[] = "Valor Glosado";
        }
        if (abs($individual['soma_aceito'] - $consolidado['valor_aceito']) > 0.01) {
            $divergencias[] = "Valor Aceito";
        }
        if (abs($individual['soma_faturado'] - $consolidado['valor_faturado']) > 0.01) {
            $divergencias[] = "Valor Faturado";
        }
        
        if (count($divergencias) > 0) {
            $registros_erro++;
            $diferencas[] = [
                'competencia' => $individual['competencia_fmt'],
                'convenio' => $individual['nome_convenio'],
                'setor' => $individual['setor'],
                'divergencias' => $divergencias,
                'individual' => $individual,
                'consolidado' => $consolidado
            ];
        } else {
            $registros_ok++;
        }
    } else {
        $registros_erro++;
        $diferencas[] = [
            'competencia' => $individual['competencia_fmt'],
            'convenio' => $individual['nome_convenio'],
            'setor' => $individual['setor'],
            'divergencias' => ['CONSOLIDADO NÃO EXISTE'],
            'individual' => $individual,
            'consolidado' => null
        ];
    }
}

// 4. VERIFICAR SE HÁ CONSOLIDADOS SEM ATENDIMENTOS INDIVIDUAIS
foreach ($dados_consolidado as $consolidado) {
    $chave = $consolidado['competencia'] . '|' . $consolidado['convenio_id'] . '|' . $consolidado['setor'];
    
    $existe_individual = false;
    foreach ($dados_individual as $individual) {
        $chave_ind = $individual['competencia'] . '|' . $individual['convenio_id'] . '|' . $individual['setor'];
        if ($chave === $chave_ind) {
            $existe_individual = true;
            break;
        }
    }
    
    if (!$existe_individual) {
        $registros_erro++;
        $diferencas[] = [
            'competencia' => $consolidado['competencia_fmt'],
            'convenio' => $consolidado['nome_convenio'],
            'setor' => $consolidado['setor'],
            'divergencias' => ['CONSOLIDADO ÓRFÃO (sem atendimentos individuais)'],
            'individual' => null,
            'consolidado' => $consolidado
        ];
    }
}

// Buscar meses disponíveis para filtro
try {
    $stmt_meses = $pdo->query("SELECT DISTINCT DATE_FORMAT(competencia, '%Y-%m') as mes FROM pa_ambulatorio ORDER BY mes DESC");
    $meses_disponiveis = $stmt_meses->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $meses_disponiveis = [];
}
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">🔍 Verificação de Integridade - Consolidação PA/Ambulatório</h1>
        <p class="text-gray-600">Este relatório compara os dados dos atendimentos individuais com os valores consolidados para identificar divergências.</p>
    </div>

    <!-- Filtro -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label for="competencia" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Competência</label>
                <select name="competencia" id="competencia" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[200px]">
                    <option value="">Todas as Competências</option>
                    <?php foreach ($meses_disponiveis as $mes): ?>
                        <option value="<?php echo $mes; ?>" <?php echo $filtro_competencia == $mes ? 'selected' : ''; ?>>
                            <?php echo date('m/Y', strtotime($mes . '-01')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition text-sm font-medium">
                Verificar
            </button>
            <?php if ($filtro_competencia): ?>
                <a href="verificar_consolidacao_pa.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition text-sm font-medium">
                    Limpar Filtro
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Cards de Resumo -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total de Registros</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $total_registros; ?></p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Registros OK</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $registros_ok; ?></p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Divergências</p>
                    <p class="text-3xl font-bold text-red-600"><?php echo $registros_erro; ?></p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultado da Verificação -->
    <?php if ($registros_erro == 0 && $total_registros > 0): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-green-800">✅ Integridade Verificada!</h3>
                    <p class="text-green-700">Todos os valores consolidados estão corretos e batem com os atendimentos individuais.</p>
                </div>
            </div>
        </div>
    <?php elseif ($registros_erro > 0): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-red-800">⚠️ Divergências Encontradas!</h3>
                    <p class="text-red-700">Foram encontradas <?php echo $registros_erro; ?> divergência(s) entre os dados individuais e consolidados.</p>
                </div>
            </div>
        </div>

        <!-- Tabela de Divergências -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-red-50 border-b border-red-200">
                <h3 class="text-lg font-semibold text-red-800">Detalhes das Divergências</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Competência</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Convênio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Setor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Campos com Divergência</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($diferencas as $diff): ?>
                            <tr class="hover:bg-red-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($diff['competencia']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($diff['convenio']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                                        if ($diff['setor'] == 'PA') echo 'bg-blue-100 text-blue-800';
                                        elseif ($diff['setor'] == 'AMB') echo 'bg-purple-100 text-purple-800';
                                        else echo 'bg-green-100 text-green-800';
                                    ?>">
                                        <?php echo htmlspecialchars($diff['setor']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-red-600">
                                    <?php echo implode(', ', $diff['divergencias']); ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button onclick="mostrarDetalhes(<?php echo htmlspecialchars(json_encode($diff)); ?>)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Ver Detalhes
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 border border-gray-200 p-6 rounded-lg text-center">
            <p class="text-gray-600">Nenhum registro encontrado para verificar. Execute a consolidação primeiro.</p>
        </div>
    <?php endif; ?>

    <!-- Ações Recomendadas -->
    <?php if ($registros_erro > 0): ?>
        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">💡 Ações Recomendadas</h3>
            <ul class="list-disc list-inside text-yellow-700 space-y-1">
                <li>Execute novamente a consolidação para corrigir as divergências</li>
                <li>Verifique se os atendimentos estão com status "Auditado"</li>
                <li>Confira se não há atendimentos duplicados</li>
            </ul>
            <div class="mt-4">
                <a href="consolidar_pa_ambulatorio.php" class="inline-flex items-center bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 transition text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reconsolidar Dados
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para Detalhes -->
<div id="modalDetalhes" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Detalhes da Divergência</h3>
            <button onclick="fecharModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="conteudoModal" class="p-6">
            <!-- Conteúdo será preenchido via JavaScript -->
        </div>
    </div>
</div>

<script>
function mostrarDetalhes(diff) {
    const modal = document.getElementById('modalDetalhes');
    const conteudo = document.getElementById('conteudoModal');
    
    let html = `
        <div class="mb-4">
            <h4 class="font-semibold text-gray-900 mb-2">Informações Gerais</h4>
            <div class="bg-gray-50 p-3 rounded">
                <p><strong>Competência:</strong> ${diff.competencia}</p>
                <p><strong>Convênio:</strong> ${diff.convenio}</p>
                <p><strong>Setor:</strong> ${diff.setor}</p>
            </div>
        </div>
    `;
    
    if (diff.individual && diff.consolidado) {
        html += `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-700">Campo</th>
                            <th class="px-4 py-2 text-right font-medium text-blue-700">Soma Individual</th>
                            <th class="px-4 py-2 text-right font-medium text-green-700">Consolidado</th>
                            <th class="px-4 py-2 text-right font-medium text-red-700">Diferença</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-4 py-2">Qtd. Atendimentos</td>
                            <td class="px-4 py-2 text-right">${diff.individual.qtd_atendimentos}</td>
                            <td class="px-4 py-2 text-right">${diff.consolidado.qtd_atendimentos}</td>
                            <td class="px-4 py-2 text-right font-bold ${diff.individual.qtd_atendimentos != diff.consolidado.qtd_atendimentos ? 'text-red-600' : 'text-green-600'}">
                                ${diff.individual.qtd_atendimentos - diff.consolidado.qtd_atendimentos}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">Valor Inicial</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.individual.soma_inicial).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.consolidado.valor_inicial).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right font-bold ${Math.abs(diff.individual.soma_inicial - diff.consolidado.valor_inicial) > 0.01 ? 'text-red-600' : 'text-green-600'}">
                                R$ ${(diff.individual.soma_inicial - diff.consolidado.valor_inicial).toFixed(2)}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">Valor Retirado</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.individual.soma_retirado).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.consolidado.valor_retirado).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right font-bold ${Math.abs(diff.individual.soma_retirado - diff.consolidado.valor_retirado) > 0.01 ? 'text-red-600' : 'text-green-600'}">
                                R$ ${(diff.individual.soma_retirado - diff.consolidado.valor_retirado).toFixed(2)}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">Valor Acrescentado</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.individual.soma_acrescentado).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.consolidado.valor_acrescentado).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right font-bold ${Math.abs(diff.individual.soma_acrescentado - diff.consolidado.valor_acrescentado) > 0.01 ? 'text-red-600' : 'text-green-600'}">
                                R$ ${(diff.individual.soma_acrescentado - diff.consolidado.valor_acrescentado).toFixed(2)}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">Valor Final</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.individual.soma_final).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.consolidado.valor_final).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right font-bold ${Math.abs(diff.individual.soma_final - diff.consolidado.valor_final) > 0.01 ? 'text-red-600' : 'text-green-600'}">
                                R$ ${(diff.individual.soma_final - diff.consolidado.valor_final).toFixed(2)}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">Valor Glosado</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.individual.soma_glosado).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.consolidado.valor_glosado).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right font-bold ${Math.abs(diff.individual.soma_glosado - diff.consolidado.valor_glosado) > 0.01 ? 'text-red-600' : 'text-green-600'}">
                                R$ ${(diff.individual.soma_glosado - diff.consolidado.valor_glosado).toFixed(2)}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">Valor Aceito</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.individual.soma_aceito).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.consolidado.valor_aceito).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right font-bold ${Math.abs(diff.individual.soma_aceito - diff.consolidado.valor_aceito) > 0.01 ? 'text-red-600' : 'text-green-600'}">
                                R$ ${(diff.individual.soma_aceito - diff.consolidado.valor_aceito).toFixed(2)}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">Valor Faturado</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.individual.soma_faturado).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right">R$ ${parseFloat(diff.consolidado.valor_faturado).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right font-bold ${Math.abs(diff.individual.soma_faturado - diff.consolidado.valor_faturado) > 0.01 ? 'text-red-600' : 'text-green-600'}">
                                R$ ${(diff.individual.soma_faturado - diff.consolidado.valor_faturado).toFixed(2)}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
    } else if (!diff.consolidado) {
        html += `<div class="bg-red-50 p-4 rounded">
            <p class="text-red-700"><strong>Problema:</strong> Consolidado não existe para este grupo de atendimentos.</p>
            <p class="text-gray-700 mt-2">Execute a consolidação para criar este registro.</p>
        </div>`;
    } else if (!diff.individual) {
        html += `<div class="bg-yellow-50 p-4 rounded">
            <p class="text-yellow-700"><strong>Problema:</strong> Registro consolidado órfão (sem atendimentos individuais correspondentes).</p>
            <p class="text-gray-700 mt-2">Pode haver sido criado manualmente ou os atendimentos foram excluídos.</p>
        </div>`;
    }
    
    conteudo.innerHTML = html;
    modal.classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modalDetalhes').classList.add('hidden');
}

// Fechar modal ao clicar fora
document.getElementById('modalDetalhes').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
