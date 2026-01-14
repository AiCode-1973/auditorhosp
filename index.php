<?php
require_once 'db_config.php';
include 'includes/header.php';

// Definir modo de agrupamento (padrão: mes)
$agrupar_por = isset($_GET['agrupar_por']) && $_GET['agrupar_por'] === 'convenio' ? 'convenio' : 'mes';

// Filtros de Impressão/Visualização
$filtro_mes = isset($_GET['filtro_mes']) ? $_GET['filtro_mes'] : '';
$filtro_convenio = isset($_GET['filtro_convenio']) ? $_GET['filtro_convenio'] : '';

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

// Consulta SQL adaptada para a estrutura criada
$sql = "
SELECT 
    DATE_FORMAT(f.data_competencia, '%m/%Y') AS Competencia,
    c.nome_convenio AS Convenio,
    SUM(f.valor_total) AS Faturamento,
    SUM(COALESCE(g.valor_glosa, 0)) AS Glosado,
    SUM(COALESCE(r.valor_recursado, 0)) AS Recursado,
    SUM(COALESCE(r.valor_aceito, 0)) AS Aceito,
    SUM(COALESCE(r.valor_recebido, 0)) AS Recebido,
    
    -- % Glosado: (Glosado / Faturamento) * 100
    ROUND(
        (SUM(COALESCE(g.valor_glosa, 0)) / NULLIF(SUM(f.valor_total), 0)) * 100, 
    2) AS Perc_Glosado,

    -- % Recursado: (Recursado / Glosado) * 100
    ROUND(
        (SUM(COALESCE(r.valor_recursado, 0)) / NULLIF(SUM(COALESCE(g.valor_glosa, 0)), 0)) * 100, 
    2) AS Perc_Recursado,

    -- % Aceito: (Aceito / Recursado) * 100
    ROUND(
        (SUM(COALESCE(r.valor_aceito, 0)) / NULLIF(SUM(COALESCE(r.valor_recursado, 0)), 0)) * 100, 
    2) AS Perc_Aceito,

    -- % Recebido: (Recebido / Aceito) * 100
    ROUND(
        (SUM(COALESCE(r.valor_recebido, 0)) / NULLIF(SUM(COALESCE(r.valor_aceito, 0)), 0)) * 100, 
    2) AS Perc_Recebido

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
    echo "<div class='alert alert-danger'>Erro ao executar consulta: " . $e->getMessage() . "</div>";
    $dados = [];
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
?>

<div class="mt-4">
    <div class="w-full">
        <!-- Cabeçalho e Filtros (Oculto na Impressão) -->
        <div class="print:hidden mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
                
                <div class="flex space-x-2">
                    <a href="registrar_auditoria.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                        + Novo Registro
                    </a>
                </div>
            </div>

            <!-- Barra de Ferramentas: Filtros e Opções de Visualização -->
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <!-- Manter o agrupamento atual ao filtrar -->
                    <input type="hidden" name="agrupar_por" value="<?php echo $agrupar_por; ?>">

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

                    <!-- Botões de Ação -->
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition text-sm font-medium">
                            Filtrar
                        </button>
                        <?php if ($filtro_mes || $filtro_convenio): ?>
                            <a href="index.php?agrupar_por=<?php echo $agrupar_por; ?>" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition text-sm font-medium flex items-center">
                                Limpar
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="border-l border-gray-300 mx-2 h-10"></div>

                    <!-- Alternar Visualização -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Agrupar Por</label>
                        <div class="inline-flex rounded-md shadow-sm" role="group">
                            <button type="submit" name="agrupar_por" value="mes" class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-l-lg focus:z-10 focus:ring-2 focus:ring-blue-700 <?php echo $agrupar_por === 'mes' ? 'bg-gray-100 text-blue-700' : 'bg-white text-gray-900 hover:bg-gray-100'; ?>">
                                Mês
                            </button>
                            <button type="submit" name="agrupar_por" value="convenio" class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-r-md focus:z-10 focus:ring-2 focus:ring-blue-700 <?php echo $agrupar_por === 'convenio' ? 'bg-gray-100 text-blue-700' : 'bg-white text-gray-900 hover:bg-gray-100'; ?>">
                                Convênio
                            </button>
                        </div>
                    </div>

                    <!-- Botão Imprimir -->
                    <div class="ml-auto flex">
                        <a href="exportar_excel.php?agrupar_por=<?php echo $agrupar_por; ?>&filtro_mes=<?php echo $filtro_mes; ?>&filtro_convenio=<?php echo $filtro_convenio; ?>" target="_blank" class="bg-green-700 text-white px-4 py-2 rounded-md hover:bg-green-800 transition text-sm font-medium flex items-center gap-2 mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Exportar Excel
                        </a>
                        <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-900 transition text-sm font-medium flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Imprimir Relatório
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cabeçalho Apenas para Impressão -->
        <div class="hidden print:block mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Relatório de Auditoria</h1>
            <p class="text-gray-600">Gerado em: <?php echo date('d/m/Y H:i'); ?></p>
            <?php if ($filtro_mes): ?>
                <p class="text-sm">Filtro Mês: <?php echo date('m/Y', strtotime($filtro_mes)); ?></p>
            <?php endif; ?>
            <?php if ($filtro_convenio): ?>
                <?php 
                    // Buscar nome do convênio para exibir no print
                    $nome_conv_print = '';
                    foreach($convenios_lista as $c) { if($c['id'] == $filtro_convenio) $nome_conv_print = $c['nome_convenio']; }
                ?>
                <p class="text-sm">Filtro Convênio: <?php echo $nome_conv_print; ?></p>
            <?php endif; ?>
        </div>

                <!--<a href="registrar_auditoria.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    + Novo Registro
                </a>-->
            </div>
        </div>

        <?php if (empty($grupos)): ?>
            <div class="bg-white shadow-md rounded-lg p-6 text-center text-gray-500">
                Nenhum dado encontrado.
            </div>
        <?php else: ?>
            <?php foreach ($grupos as $titulo_grupo => $grupo): ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8 print:shadow-none print:overflow-visible print:mb-4 break-inside-avoid">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-100 flex justify-between items-center print:bg-gray-50 print:px-0 print:py-2">
                        <h4 class="text-lg font-bold text-gray-800 print:text-base">
                            <?php echo ($agrupar_por === 'convenio' ? 'Convênio: ' : 'Competência: ') . htmlspecialchars($titulo_grupo); ?>
                        </h4>
                        <div class="text-sm text-gray-600 print:text-xs">
                            <span class="mr-4"><strong>Total Faturado:</strong> R$ <?php echo number_format($grupo['total_faturamento'], 2, ',', '.'); ?></span>
                            <span class="mr-4"><strong>Total Glosado:</strong> R$ <?php echo number_format($grupo['total_glosado'], 2, ',', '.'); ?></span>
                            <span><strong>Total Recebido:</strong> R$ <?php echo number_format($grupo['total_recebido'], 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    <div class="overflow-x-auto print:overflow-visible">
                        <table class="min-w-full divide-y divide-gray-200 print:text-[10px]">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider bg-competencia print:px-2 print:py-1">
                                        <?php echo ($agrupar_por === 'convenio' ? 'Competência' : 'Convênio'); ?>
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-metricas print:px-2 print:py-1">Faturamento</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-metricas print:px-2 print:py-1">Glosado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-metricas print:px-2 print:py-1">Recursado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-metricas print:px-2 print:py-1">Aceito</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-metricas print:px-2 print:py-1">Recebido</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-percentuais print:px-2 print:py-1">% Glosado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-percentuais print:px-2 print:py-1">% Recursado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-percentuais print:px-2 print:py-1">% Aceito</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider bg-percentuais print:px-2 print:py-1">% Recebido</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($grupo['itens'] as $linha): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 print:px-2 print:py-1 print:whitespace-normal">
                                            <?php echo htmlspecialchars(($agrupar_por === 'convenio' ? $linha['Competencia'] : $linha['Convenio'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right print:px-2 print:py-1">R$ <?php echo number_format($linha['Faturamento'], 2, ',', '.'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right print:px-2 print:py-1">R$ <?php echo number_format($linha['Glosado'], 2, ',', '.'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right print:px-2 print:py-1">R$ <?php echo number_format($linha['Recursado'], 2, ',', '.'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right print:px-2 print:py-1">R$ <?php echo number_format($linha['Aceito'], 2, ',', '.'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right print:px-2 print:py-1">R$ <?php echo number_format($linha['Recebido'], 2, ',', '.'); ?></td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right print:px-2 print:py-1"><?php echo $linha['Perc_Glosado'] !== null ? number_format($linha['Perc_Glosado'], 2, ',', '.') . '%' : '-'; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right print:px-2 print:py-1"><?php echo $linha['Perc_Recursado'] !== null ? number_format($linha['Perc_Recursado'], 2, ',', '.') . '%' : '-'; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right print:px-2 print:py-1"><?php echo $linha['Perc_Aceito'] !== null ? number_format($linha['Perc_Aceito'], 2, ',', '.') . '%' : '-'; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right print:px-2 print:py-1"><?php echo $linha['Perc_Recebido'] !== null ? number_format($linha['Perc_Recebido'], 2, ',', '.') . '%' : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
