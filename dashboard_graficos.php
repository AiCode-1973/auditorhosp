<?php
require_once 'db_config.php';
include 'includes/header.php';

// --- 1. Totais Gerais (Cards) ---
try {
    $sql_totais = "
        SELECT 
            SUM(f.valor_total) as total_faturado,
            (SELECT SUM(valor_glosa) FROM glosas) as total_glosado,
            (SELECT SUM(valor_recursado) FROM recursos) as total_recursado,
            (SELECT SUM(valor_aceito) FROM recursos) as total_aceito
        FROM faturas f
    ";
    $stmt = $pdo->query($sql_totais);
    $totais = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Evitar null
    $totais['total_faturado'] = $totais['total_faturado'] ?? 0;
    $totais['total_glosado'] = $totais['total_glosado'] ?? 0;
    $totais['total_recursado'] = $totais['total_recursado'] ?? 0;
    $totais['total_aceito'] = $totais['total_aceito'] ?? 0;

    // Calcular % Glosa Geral (do Faturado)
    $perc_glosa_geral = ($totais['total_faturado'] > 0) 
        ? ($totais['total_glosado'] / $totais['total_faturado']) * 100 
        : 0;

    // Calcular % Recursado Geral (do Glosado)
    $perc_recursado_geral = ($totais['total_glosado'] > 0) 
        ? ($totais['total_recursado'] / $totais['total_glosado']) * 100 
        : 0;

    // Calcular % Aceito Geral (do Recursado)
    $perc_aceito_geral = ($totais['total_recursado'] > 0) 
        ? ($totais['total_aceito'] / $totais['total_recursado']) * 100 
        : 0;

} catch (PDOException $e) {
    $totais = ['total_faturado' => 0, 'total_glosado' => 0, 'total_recursado' => 0, 'total_aceito' => 0];
    $perc_glosa_geral = 0;
}

// --- 2. Evolução Mensal (Gráfico de Linha/Barra) ---
try {
    $sql_evolucao = "
        SELECT 
            DATE_FORMAT(f.data_competencia, '%Y-%m') as mes_sort,
            DATE_FORMAT(f.data_competencia, '%m/%Y') as mes_label,
            SUM(f.valor_total) as faturado,
            SUM(COALESCE(g.valor_glosa, 0)) as glosado
        FROM faturas f
        LEFT JOIN (
            SELECT fatura_id, SUM(valor_glosa) as valor_glosa 
            FROM glosas 
            GROUP BY fatura_id
        ) g ON f.id = g.fatura_id
        GROUP BY mes_sort, mes_label
        ORDER BY mes_sort ASC
        LIMIT 12
    ";
    $stmt = $pdo->query($sql_evolucao);
    $evolucao_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $evolucao_data = [];
}

// --- 3. Faturamento por Convênio (Gráfico de Rosca) ---
try {
    $sql_convenio = "
        SELECT 
            c.nome_convenio,
            SUM(f.valor_total) as total
        FROM faturas f
        JOIN convenios c ON f.convenio_id = c.id
        GROUP BY c.nome_convenio
        ORDER BY total DESC
    ";
    $stmt = $pdo->query($sql_convenio);
    $convenio_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenio_data = [];
}

// --- 4. Top 5 Glosas por Convênio (Gráfico de Barras) ---
try {
    $sql_top_glosas = "
        SELECT 
            c.nome_convenio,
            SUM(COALESCE(g.valor_glosa, 0)) as total_glosa
        FROM faturas f
        JOIN convenios c ON f.convenio_id = c.id
        LEFT JOIN (
            SELECT fatura_id, SUM(valor_glosa) as valor_glosa 
            FROM glosas 
            GROUP BY fatura_id
        ) g ON f.id = g.fatura_id
        GROUP BY c.nome_convenio
        HAVING total_glosa > 0
        ORDER BY total_glosa DESC
        LIMIT 5
    ";
    $stmt = $pdo->query($sql_top_glosas);
    $top_glosas_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $top_glosas_data = [];
}

// --- 5. Detalhamento por Convênio (Com Competência) ---
try {
    // Buscar meses disponíveis para o filtro
    $sql_meses = "SELECT DISTINCT DATE_FORMAT(data_competencia, '%Y-%m') as mes FROM faturas ORDER BY mes DESC";
    $stmt_meses = $pdo->query($sql_meses);
    $meses_disponiveis = $stmt_meses->fetchAll(PDO::FETCH_COLUMN);

    $sql_detalhe_convenio = "
        SELECT 
            c.id as convenio_id,
            c.nome_convenio,
            DATE_FORMAT(f.data_competencia, '%Y-%m') as mes_competencia,
            SUM(f.valor_total) as total_faturado,
            SUM(COALESCE(g.valor_glosa, 0)) as total_glosado,
            SUM(COALESCE(r.valor_recursado, 0)) as total_recursado,
            SUM(COALESCE(r.valor_aceito, 0)) as total_aceito,
            SUM(COALESCE(r.valor_recebido, 0)) as total_recebido
        FROM faturas f
        JOIN convenios c ON f.convenio_id = c.id
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
        GROUP BY c.id, c.nome_convenio, mes_competencia
        ORDER BY c.nome_convenio, mes_competencia DESC
    ";
    $stmt = $pdo->query($sql_detalhe_convenio);
    $detalhe_convenio_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $detalhe_convenio_data = [];
    $meses_disponiveis = [];
}

?>

<div class="mt-6 mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-800">Dashboard Analítico</h2>
        <button id="btnToggleCards" onclick="toggleCards()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 transition text-sm font-medium shadow-sm">
            Ver em %
        </button>
    </div>

    <!-- Cards de Resumo -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Card Faturamento -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Faturado</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?php echo number_format($totais['total_faturado'], 2, ',', '.'); ?></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Glosado -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Glosado</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <span class="card-value-money">R$ <?php echo number_format($totais['total_glosado'], 2, ',', '.'); ?></span>
                        <span class="card-value-percent hidden"><?php echo number_format($perc_glosa_geral, 2, ',', '.'); ?>%</span>
                    </p>
                    <p class="text-xs text-red-500 mt-1">
                        <span class="card-sub-default"><?php echo number_format($perc_glosa_geral, 2, ',', '.'); ?>% do Faturamento</span>
                        <span class="card-sub-alt hidden">do Faturamento</span>
                    </p>
                </div>
                <div class="p-3 bg-red-100 rounded-full text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Recursado -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Recursado</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <span class="card-value-money">R$ <?php echo number_format($totais['total_recursado'], 2, ',', '.'); ?></span>
                        <span class="card-value-percent hidden"><?php echo number_format($perc_recursado_geral, 2, ',', '.'); ?>%</span>
                    </p>
                    <p class="text-xs text-yellow-600 mt-1 h-4">
                        <span class="card-sub-default"><?php echo number_format($perc_recursado_geral, 2, ',', '.'); ?>% do Glosado</span>
                        <span class="card-sub-alt hidden">do Glosado</span>
                    </p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full text-yellow-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Aceito -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Aceito</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <span class="card-value-money">R$ <?php echo number_format($totais['total_aceito'], 2, ',', '.'); ?></span>
                        <span class="card-value-percent hidden"><?php echo number_format($perc_aceito_geral, 2, ',', '.'); ?>%</span>
                    </p>
                    <p class="text-xs text-green-600 mt-1 h-4">
                        <span class="card-sub-default"><?php echo number_format($perc_aceito_geral, 2, ',', '.'); ?>% do Recursado</span>
                        <span class="card-sub-alt hidden">do Recursado</span>
                    </p>
                </div>
                <div class="p-3 bg-green-100 rounded-full text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção de Detalhamento por Convênio -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-xl font-bold text-gray-800">Análise Individual por Convênio</h3>
            
            <div class="flex gap-2 w-full md:w-auto">
                <!-- Filtro Competência -->
                <select id="selectCompetencia" onchange="updateConvenioCards()" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-auto">
                    <option value="">Todas as Competências</option>
                    <?php foreach ($meses_disponiveis as $mes): ?>
                        <option value="<?php echo $mes; ?>"><?php echo date('m/Y', strtotime($mes . '-01')); ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Filtro Convênio -->
                <select id="selectConvenio" onchange="updateConvenioCards()" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-auto">
                    <option value="">Selecione um Convênio</option>
                    <?php 
                    $convenios_impressos = [];
                    foreach ($detalhe_convenio_data as $conv): 
                        if (!in_array($conv['convenio_id'], $convenios_impressos)):
                            $convenios_impressos[] = $conv['convenio_id'];
                    ?>
                        <option value="<?php echo $conv['convenio_id']; ?>"><?php echo htmlspecialchars($conv['nome_convenio']); ?></option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
            </div>
        </div>

        <div id="convenioCardsContainer" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card Faturamento Convênio -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Faturado</p>
                <p class="text-xl font-bold text-gray-800" id="convFaturado">R$ 0,00</p>
                <p class="text-xs text-gray-500 mt-1" id="convPercFaturado">0% do Total Geral</p>
            </div>

            <!-- Card Glosado Convênio -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Glosado</p>
                <p class="text-xl font-bold text-red-600" id="convGlosado">R$ 0,00</p>
                <p class="text-xs text-gray-500 mt-1" id="convPercGlosado">0% do Faturado</p>
            </div>

            <!-- Card Recursado Convênio -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Recursado</p>
                <p class="text-xl font-bold text-yellow-600" id="convRecursado">R$ 0,00</p>
                <p class="text-xs text-gray-500 mt-1" id="convPercRecursado">0% do Glosado</p>
            </div>

            <!-- Card Aceito Convênio -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Aceito</p>
                <p class="text-xl font-bold text-green-600" id="convAceito">R$ 0,00</p>
                <p class="text-xs text-gray-500 mt-1" id="convPercAceito">0% do Recursado</p>
            </div>
        </div>
        
        <div id="convenioEmptyState" class="text-center py-8 text-gray-500">
            Selecione um convênio acima para ver os detalhes.
        </div>
    </div>

    <!-- Gráficos Linha 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Evolução Mensal -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold text-gray-700 mb-4">Evolução: Faturamento vs Glosa</h3>
            <div class="relative h-64">
                <canvas id="chartEvolucao"></canvas>
            </div>
        </div>

        <!-- Distribuição por Convênio -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-700">Faturamento por Convênio</h3>
                <button id="btnToggleConvenio" onclick="toggleConvenio()" class="text-xs font-semibold bg-blue-100 text-blue-600 px-3 py-1 rounded-full hover:bg-blue-200 transition focus:outline-none">
                    Ver em %
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="chartConvenio"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráficos Linha 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top Glosas -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold text-gray-700 mb-4">Top 5 Convênios com Maior Glosa</h3>
            <div class="relative h-64">
                <canvas id="chartTopGlosas"></canvas>
            </div>
        </div>
        
        <!-- Espaço para futuro gráfico ou info -->
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-center text-gray-400">
            <div class="text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p>Mais métricas em breve...</p>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // --- Dados PHP para JS ---

    const convenioData = <?php echo json_encode($detalhe_convenio_data); ?>;
    const totalFaturadoGeral = <?php echo $totais['total_faturado']; ?>;
    
    // Evolução
    const evolucaoLabels = <?php echo json_encode(array_column($evolucao_data, 'mes_label')); ?>;
    const evolucaoFaturado = <?php echo json_encode(array_column($evolucao_data, 'faturado')); ?>;
    const evolucaoGlosado = <?php echo json_encode(array_column($evolucao_data, 'glosado')); ?>;

    // Convênios
    const convenioLabels = <?php echo json_encode(array_column($convenio_data, 'nome_convenio')); ?>;
    const convenioValores = <?php echo json_encode(array_column($convenio_data, 'total')); ?>;

    // Top Glosas
    const topGlosasLabels = <?php echo json_encode(array_column($top_glosas_data, 'nome_convenio')); ?>;
    const topGlosasValores = <?php echo json_encode(array_column($top_glosas_data, 'total_glosa')); ?>;

    // --- Configuração dos Gráficos ---

    // 1. Gráfico de Evolução (Linha/Barra Mista)
    const ctxEvolucao = document.getElementById('chartEvolucao').getContext('2d');
    new Chart(ctxEvolucao, {
        type: 'bar',
        data: {
            labels: evolucaoLabels,
            datasets: [
                {
                    label: 'Faturado',
                    data: evolucaoFaturado,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)', // Blue-500
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    order: 2
                },
                {
                    label: 'Glosado',
                    data: evolucaoGlosado,
                    type: 'line',
                    borderColor: 'rgb(239, 68, 68)', // Red-500
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR', {notation: 'compact'});
                        }
                    }
                }
            }
        }
    });

    // 2. Gráfico de Convênios (Doughnut)
    const ctxConvenio = document.getElementById('chartConvenio').getContext('2d');
    
    // Calcular total para porcentagem
    const totalConvenio = convenioValores.reduce((a, b) => Number(a) + Number(b), 0);
    let showPercentConvenio = false;

    const chartConvenio = new Chart(ctxConvenio, {
        type: 'doughnut',
        data: {
            labels: convenioLabels,
            datasets: [{
                data: convenioValores,
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            let value = context.raw;
                            if (showPercentConvenio) {
                                let perc = (value / totalConvenio * 100).toFixed(2) + '%';
                                return label + perc;
                            } else {
                                return label + 'R$ ' + parseFloat(value).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        }
    });

    function toggleConvenio() {
        showPercentConvenio = !showPercentConvenio;
        const btn = document.getElementById('btnToggleConvenio');
        btn.textContent = showPercentConvenio ? 'Ver em R$' : 'Ver em %';
        chartConvenio.update();
    }

    function toggleCards() {
        const moneys = document.querySelectorAll('.card-value-money');
        const percents = document.querySelectorAll('.card-value-percent');
        const subDefaults = document.querySelectorAll('.card-sub-default');
        const subAlts = document.querySelectorAll('.card-sub-alt');
        const btn = document.getElementById('btnToggleCards');
        
        const isShowingPercent = btn.textContent.includes('R$');

        if (isShowingPercent) {
            // Voltar para R$
            moneys.forEach(el => el.classList.remove('hidden'));
            percents.forEach(el => el.classList.add('hidden'));
            subDefaults.forEach(el => el.classList.remove('hidden'));
            subAlts.forEach(el => el.classList.add('hidden'));
            btn.textContent = 'Ver em %';
        } else {
            // Mostrar %
            moneys.forEach(el => el.classList.add('hidden'));
            percents.forEach(el => el.classList.remove('hidden'));
            subDefaults.forEach(el => el.classList.add('hidden'));
            subAlts.forEach(el => el.classList.remove('hidden'));
            btn.textContent = 'Ver em R$';
        }
    }

    function updateConvenioCards() {
        const selectConvenio = document.getElementById('selectConvenio');
        const selectCompetencia = document.getElementById('selectCompetencia');
        const container = document.getElementById('convenioCardsContainer');
        const emptyState = document.getElementById('convenioEmptyState');
        
        const convenioId = selectConvenio.value;
        const competencia = selectCompetencia.value;

        if (!convenioId) {
            container.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        // Filtrar dados
        const dadosFiltrados = convenioData.filter(item => {
            const matchConvenio = item.convenio_id == convenioId;
            const matchCompetencia = competencia === "" || item.mes_competencia === competencia;
            return matchConvenio && matchCompetencia;
        });

        if (dadosFiltrados.length > 0) {
            // Somar valores (caso tenha mais de um mês ou mês específico)
            let faturado = 0;
            let glosado = 0;
            let recursado = 0;
            let aceito = 0;

            dadosFiltrados.forEach(d => {
                faturado += parseFloat(d.total_faturado || 0);
                glosado += parseFloat(d.total_glosado || 0);
                recursado += parseFloat(d.total_recursado || 0);
                aceito += parseFloat(d.total_aceito || 0);
            });

            // Calcular percentuais
            // Nota: Para os percentuais relativos ao "Total Geral", precisaríamos do total geral filtrado pela competência também,
            // mas por simplicidade manteremos em relação ao total geral carregado ou recalcularemos se necessário.
            // Vamos manter a lógica local:
            
            const percFaturado = totalFaturadoGeral > 0 ? (faturado / totalFaturadoGeral * 100) : 0; // Esse percentual pode ficar estranho se filtrar mês, mas ok.
            const percGlosado = faturado > 0 ? (glosado / faturado * 100) : 0;
            const percRecursado = glosado > 0 ? (recursado / glosado * 100) : 0;
            const percAceito = recursado > 0 ? (aceito / recursado * 100) : 0;

            document.getElementById('convFaturado').textContent = formatMoney(faturado);
            // Ajuste texto para fazer sentido com filtro
            document.getElementById('convPercFaturado').textContent = competencia ? 'Nesta competência' : formatPercent(percFaturado) + ' do Total Geral';
            
            document.getElementById('convGlosado').textContent = formatMoney(glosado);
            document.getElementById('convPercGlosado').textContent = formatPercent(percGlosado) + ' do Faturado';

            document.getElementById('convRecursado').textContent = formatMoney(recursado);
            document.getElementById('convPercRecursado').textContent = formatPercent(percRecursado) + ' do Glosado';

            document.getElementById('convAceito').textContent = formatMoney(aceito);
            document.getElementById('convPercAceito').textContent = formatPercent(percAceito) + ' do Recursado';

            container.classList.remove('hidden');
            emptyState.classList.add('hidden');
        } else {
            // Caso não tenha dados para o filtro selecionado
            container.classList.add('hidden');
            emptyState.textContent = "Nenhum dado encontrado para este convênio nesta competência.";
            emptyState.classList.remove('hidden');
        }
    }

    function formatMoney(value) {
        return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function formatPercent(value) {
        return value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%';
    }

    // 3. Gráfico Top Glosas (Barra Horizontal)
    const ctxTopGlosas = document.getElementById('chartTopGlosas').getContext('2d');
    new Chart(ctxTopGlosas, {
        type: 'bar',
        data: {
            labels: topGlosasLabels,
            datasets: [{
                label: 'Valor Glosado',
                data: topGlosasValores,
                backgroundColor: 'rgba(239, 68, 68, 0.7)', // Red-500
                borderColor: 'rgb(239, 68, 68)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y', // Barra Horizontal
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR', {notation: 'compact'});
                        }
                    }
                }
            }
        }
    });

</script>

<?php include 'includes/footer.php'; ?>