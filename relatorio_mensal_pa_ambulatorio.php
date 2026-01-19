<?php
require_once 'db_config.php';
include 'includes/header.php';

// Filtros
$filtro_mes = isset($_GET['filtro_mes']) ? $_GET['filtro_mes'] : '';
$filtro_convenio = isset($_GET['filtro_convenio']) ? $_GET['filtro_convenio'] : '';
$filtro_setor = isset($_GET['filtro_setor']) ? $_GET['filtro_setor'] : '';

// Buscar convênios para o filtro
try {
    $stmt_conv = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
    $convenios_lista = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenios_lista = [];
}

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

if ($filtro_setor) {
    $where_clauses[] = "r.setor = ?";
    $params[] = $filtro_setor;
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Consulta SQL - Consolidado por Competência, Convênio e Setor
$sql = "
    SELECT 
        r.id,
        DATE_FORMAT(r.competencia, '%m/%Y') AS Competencia,
        r.competencia AS competencia_raw,
        c.nome_convenio AS Convenio,
        r.setor AS Setor,
        r.valor_inicial,
        r.valor_retirado,
        r.valor_acrescentado,
        r.valor_final as valor_total,
        r.valor_glosado,
        r.valor_aceito,
        r.valor_faturado,
        r.qtd_atendimentos as total_atendimentos
    FROM relatorio_mensal_pa_consolidado r
    JOIN convenios c ON r.convenio_id = c.id
    WHERE r.setor IS NOT NULL AND r.setor != ''
    " . ($where_sql ? " AND " . str_replace('WHERE ', '', $where_sql) : "") . "
    ORDER BY r.competencia DESC, c.nome_convenio ASC, r.setor ASC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totais gerais
    $total_inicial = 0;
    $total_retirado = 0;
    $total_acrescentado = 0;
    $total_final = 0;
    $total_glosado = 0;
    $total_aceito = 0;
    $total_faturado = 0;
    $total_atendimentos = 0;
    
    foreach ($dados as $linha) {
        $total_inicial += $linha['valor_inicial'];
        $total_retirado += $linha['valor_retirado'];
        $total_acrescentado += $linha['valor_acrescentado'];
        $total_final += $linha['valor_total'];
        $total_glosado += $linha['valor_glosado'];
        $total_aceito += $linha['valor_aceito'];
        $total_faturado += $linha['valor_faturado'];
        $total_atendimentos += $linha['total_atendimentos'];
    }
    
    // Calcular percentuais médios
    $perc_retirado = $total_inicial > 0 ? round(($total_retirado / $total_inicial) * 100, 2) : 0;
    $perc_acrescentado = $total_inicial > 0 ? round(($total_acrescentado / $total_inicial) * 100, 2) : 0;
    $perc_glosado = $total_final > 0 ? round(($total_glosado / $total_final) * 100, 2) : 0;
    $perc_aceito = $total_glosado > 0 ? round(($total_aceito / $total_glosado) * 100, 2) : 0;
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro ao executar consulta: " . $e->getMessage() . "</div>";
    $dados = [];
    $total_inicial = $total_retirado = $total_acrescentado = $total_final = $total_glosado = $total_aceito = $total_faturado = $total_atendimentos = 0;
    $perc_retirado = $perc_acrescentado = $perc_glosado = $perc_aceito = 0;
}
?>

<div class="mt-4">
    <div class="w-full">
        <div class="print:hidden mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Relatório Mensal PA/Ambulatório</h2>
            </div>


            <!-- Filtros -->
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <!-- Filtro Mês -->
                    <div>
                        <label for="filtro_mes" class="block text-sm font-medium text-gray-700 mb-1">Mês Competência</label>
                        <input type="month" name="filtro_mes" id="filtro_mes" value="<?php echo $filtro_mes; ?>" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Filtro Convênio -->
                    <div>
                        <label for="filtro_convenio" class="block text-sm font-medium text-gray-700 mb-1">Convênio</label>
                        <select name="filtro_convenio" id="filtro_convenio" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[200px]">
                            <option value="">Todos</option>
                            <?php foreach ($convenios_lista as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $filtro_convenio == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['nome_convenio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro Setor -->
                    <div>
                        <label for="filtro_setor" class="block text-sm font-medium text-gray-700 mb-1">Setor</label>
                        <select name="filtro_setor" id="filtro_setor" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[120px]">
                            <option value="">Todos</option>
                            <option value="PA" <?php echo $filtro_setor == 'PA' ? 'selected' : ''; ?>>PA</option>
                            <option value="AMB" <?php echo $filtro_setor == 'AMB' ? 'selected' : ''; ?>>AMB</option>
                            <option value="NC" <?php echo $filtro_setor == 'NC' ? 'selected' : ''; ?>>NC</option>
                        </select>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition text-sm font-medium">
                            Filtrar
                        </button>
                        <?php if ($filtro_mes || $filtro_convenio || $filtro_setor): ?>
                            <a href="relatorio_mensal_pa_ambulatorio.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition text-sm font-medium flex items-center">
                                Limpar
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="border-l border-gray-300 mx-2 h-10"></div>

                    <!-- Botão Imprimir -->
                    <div class="ml-auto flex">
                        <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-900 transition text-sm font-medium flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Imprimir
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cabeçalho Apenas para Impressão -->
        <div class="hidden print:block mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Relatório Mensal PA/Ambulatório</h1>
            <p class="text-gray-600">Gerado em: <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <?php if (empty($dados)): ?>
            <div class="bg-white shadow-md rounded-lg p-6 text-center text-gray-500">
                Nenhum dado encontrado.
            </div>
        <?php else: ?>
            <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8 print:shadow-none">
                <div class="print:overflow-visible">
                    <table class="w-full divide-y divide-gray-200 text-xs print:text-[10px]">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-2 py-2 text-left text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">Competência</th>
                                <th class="px-2 py-2 text-left text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">Convênio</th>
                                <th class="px-2 py-2 text-left text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">Setor</th>
                                <th class="px-2 py-2 text-center text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">Qtd.</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">V. Inicial</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">V. Retir.</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">V. Acres.</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">V. Final</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">V. Glos.</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">V. Aceit.</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider print:px-1 print:py-1">V. Fatur.</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider bg-blue-50 print:px-1 print:py-1">% Ret.</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider bg-green-50 print:px-1 print:py-1">% Acres.</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider bg-blue-50 print:px-1 print:py-1">% Glosa</th>
                                <th class="px-2 py-2 text-right text-[10px] font-medium text-gray-700 uppercase tracking-wider bg-green-50 print:px-1 print:py-1">% Aceit.</th>
                                <th class="px-2 py-2 text-center text-[10px] font-medium text-gray-700 uppercase tracking-wider print:hidden">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dados as $linha): 
                                $perc_ret_linha = $linha['valor_inicial'] > 0 ? round(($linha['valor_retirado'] / $linha['valor_inicial']) * 100, 2) : 0;
                                $perc_acres_linha = $linha['valor_inicial'] > 0 ? round(($linha['valor_acrescentado'] / $linha['valor_inicial']) * 100, 2) : 0;
                                $perc_glosa_linha = $linha['valor_total'] > 0 ? round(($linha['valor_glosado'] / $linha['valor_total']) * 100, 2) : 0;
                                $perc_aceito_linha = $linha['valor_glosado'] > 0 ? round(($linha['valor_aceito'] / $linha['valor_glosado']) * 100, 2) : 0;
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-gray-900 print:px-1 print:py-1">
                                        <?php echo htmlspecialchars($linha['Competencia']); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] font-medium text-gray-900 print:px-1 print:py-1">
                                        <?php echo htmlspecialchars($linha['Convenio']); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-gray-900 print:px-1 print:py-1">
                                        <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                                            if ($linha['Setor'] == 'PA') echo 'bg-blue-100 text-blue-800';
                                            elseif ($linha['Setor'] == 'AMB') echo 'bg-purple-100 text-purple-800';
                                            else echo 'bg-green-100 text-green-800';
                                        ?>">
                                            <?php echo htmlspecialchars($linha['Setor']); ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-gray-900 text-center font-semibold print:px-2 print:py-1">
                                        <?php echo number_format($linha['total_atendimentos'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-gray-900 text-right print:px-2 print:py-1">
                                        R$ <?php echo number_format($linha['valor_inicial'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-red-600 text-right print:px-2 print:py-1">
                                        R$ <?php echo number_format($linha['valor_retirado'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-green-600 text-right print:px-2 print:py-1">
                                        R$ <?php echo number_format($linha['valor_acrescentado'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] font-bold text-gray-900 text-right print:px-2 print:py-1">
                                        R$ <?php echo number_format($linha['valor_total'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-gray-900 text-right print:px-2 print:py-1">
                                        R$ <?php echo number_format($linha['valor_glosado'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-gray-900 text-right print:px-2 print:py-1">
                                        R$ <?php echo number_format($linha['valor_aceito'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-gray-900 text-right print:px-2 print:py-1">
                                        R$ <?php echo number_format($linha['valor_faturado'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-blue-700 text-right bg-blue-50 print:px-2 print:py-1">
                                        <?php echo number_format($perc_ret_linha, 2, ',', '.'); ?>%
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-green-700 text-right bg-green-50 print:px-2 print:py-1">
                                        <?php echo number_format($perc_acres_linha, 2, ',', '.'); ?>%
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-blue-700 text-right bg-blue-50 print:px-2 print:py-1">
                                        <?php echo number_format($perc_glosa_linha, 2, ',', '.'); ?>%
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-[11px] text-green-700 text-right bg-green-50 print:px-2 print:py-1">
                                        <?php echo number_format($perc_aceito_linha, 2, ',', '.'); ?>%
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-center text-[11px] font-medium print:hidden">
                                        <a href="relatorio_mensal_pa_form.php?id=<?php echo $linha['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <a href="javascript:void(0);" onclick="if(confirm('Tem certeza que deseja excluir este registro?')) window.location.href='excluir_relatorio_pa.php?id=<?php echo $linha['id']; ?>';" class="text-red-600 hover:text-red-900" title="Excluir">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold border-t-2 border-gray-300">
                            <tr>
                                <td colspan="3" class="px-2 py-2 text-right text-[10px] text-gray-700 uppercase print:px-2 print:py-1">Totais:</td>
                                <td class="px-2 py-2 whitespace-nowrap text-center text-[11px] text-gray-900 print:px-2 print:py-1"><?php echo number_format($total_atendimentos, 0, ',', '.'); ?></td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-gray-900 print:px-2 print:py-1">R$ <?php echo number_format($total_inicial, 2, ',', '.'); ?></td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-red-600 print:px-2 print:py-1">R$ <?php echo number_format($total_retirado, 2, ',', '.'); ?></td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-green-600 print:px-2 print:py-1">R$ <?php echo number_format($total_acrescentado, 2, ',', '.'); ?></td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-gray-900 print:px-2 print:py-1">R$ <?php echo number_format($total_final, 2, ',', '.'); ?></td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-gray-900 print:px-2 print:py-1">R$ <?php echo number_format($total_glosado, 2, ',', '.'); ?></td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-gray-900 print:px-2 print:py-1">R$ <?php echo number_format($total_aceito, 2, ',', '.'); ?></td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-gray-900 print:px-2 print:py-1">R$ <?php echo number_format($total_faturado, 2, ',', '.'); ?></td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-blue-700 bg-blue-50 print:px-2 print:py-1"><?php echo number_format($perc_retirado, 2, ',', '.'); ?>%</td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-green-700 bg-green-50 print:px-2 print:py-1"><?php echo number_format($perc_acrescentado, 2, ',', '.'); ?>%</td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-blue-700 bg-blue-50 print:px-2 print:py-1"><?php echo number_format($perc_glosado, 2, ',', '.'); ?>%</td>
                                <td class="px-2 py-2 whitespace-nowrap text-right text-[11px] text-green-700 bg-green-50 print:px-2 print:py-1"><?php echo number_format($perc_aceito, 2, ',', '.'); ?>%</td>
                                <td class="px-2 py-2 print:hidden"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
