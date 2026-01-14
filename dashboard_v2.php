<?php
require_once 'db_config.php';
include 'includes/header.php';

// Filtro de Mês
$filtro_mes = isset($_GET['mes']) ? $_GET['mes'] : '';

// Buscar meses disponíveis
try {
    $sql_meses_disponiveis = "SELECT DISTINCT DATE_FORMAT(data_competencia, '%Y-%m') as mes FROM faturas ORDER BY mes DESC";
    $stmt = $pdo->query($sql_meses_disponiveis);
    $meses_disponiveis_filtro = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $meses_disponiveis_filtro = [];
}

// Condição WHERE para filtro (apenas para cards)
$where_filtro_cards = "";
if ($filtro_mes) {
    $where_filtro_cards = " WHERE DATE_FORMAT(f.data_competencia, '%Y-%m') = " . $pdo->quote($filtro_mes);
}

// --- 1. Totais Gerais (Cards) ---
try {
    // Subconsultas com filtro de mês
    if ($filtro_mes) {
        $mes_quoted = $pdo->quote($filtro_mes);
        $subquery_glosas = "(SELECT SUM(g.valor_glosa) FROM glosas g JOIN faturas f2 ON g.fatura_id = f2.id WHERE DATE_FORMAT(f2.data_competencia, '%Y-%m') = $mes_quoted)";
        $subquery_recursos_recursado = "(SELECT SUM(r.valor_recursado) FROM recursos r JOIN faturas f3 ON r.fatura_id = f3.id WHERE DATE_FORMAT(f3.data_competencia, '%Y-%m') = $mes_quoted)";
        $subquery_recursos_aceito = "(SELECT SUM(r.valor_aceito) FROM recursos r JOIN faturas f4 ON r.fatura_id = f4.id WHERE DATE_FORMAT(f4.data_competencia, '%Y-%m') = $mes_quoted)";
    } else {
        $subquery_glosas = "(SELECT SUM(valor_glosa) FROM glosas)";
        $subquery_recursos_recursado = "(SELECT SUM(valor_recursado) FROM recursos)";
        $subquery_recursos_aceito = "(SELECT SUM(valor_aceito) FROM recursos)";
    }
    
    $sql_totais = "
        SELECT 
            SUM(f.valor_total) as total_faturado,
            $subquery_glosas as total_glosado,
            $subquery_recursos_recursado as total_recursado,
            $subquery_recursos_aceito as total_aceito
        FROM faturas f
        $where_filtro_cards
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

<style>
    /* Neon Theme Overrides */
    body {
        background-color: #0b1121 !important; /* Extremely dark blue/slate */
        color: #e2e8f0 !important;
        font-family: 'Inter', sans-serif;
    }

    /* Navbar Override if possible, or we just live with the blue one but hopefully it blends okay. 
       Actually, let's force the nav to be dark too corresponding to the user request. */
    nav.bg-blue-600 {
        background-color: #0f172a !important; /* Darker slate */
        border-bottom: 1px solid #1e293b;
    }
    nav a.text-white {
        color: #e2e8f0 !important;
    }
    nav a:hover {
        color: #00a6fb !important; /* Neon Blue */
        background-color: rgba(0, 166, 251, 0.1) !important;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #0f172a; 
    }
    ::-webkit-scrollbar-thumb {
        background: #1e293b; 
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #00a6fb; 
    }

    /* Futuristic Utilities */
    .neon-text {
        color: #00a6fb;
        text-shadow: 0 0 10px rgba(0, 166, 251, 0.5);
    }
    .neon-border {
        border: 1px solid rgba(0, 166, 251, 0.3);
        box-shadow: 0 0 15px rgba(0, 166, 251, 0.1);
    }
    .glass-panel {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-enter {
        animation: fadeIn 0.6s ease-out forwards;
    }
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }

    /* Card Glow Effects */
    .glow-blue {
        border: 1px solid rgba(59, 130, 246, 0.3);
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.1);
    }
    .glow-blue:hover {
        border-color: #3b82f6;
        box-shadow: 0 0 25px rgba(59, 130, 246, 0.4);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    .glow-red {
        border: 1px solid rgba(239, 68, 68, 0.3);
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.1);
    }
    .glow-red:hover {
        border-color: #ef4444;
        box-shadow: 0 0 25px rgba(239, 68, 68, 0.4);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    .glow-yellow {
        border: 1px solid rgba(234, 179, 8, 0.3);
        box-shadow: 0 0 15px rgba(234, 179, 8, 0.1);
    }
    .glow-yellow:hover {
        border-color: #eab308;
        box-shadow: 0 0 25px rgba(234, 179, 8, 0.4);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    .glow-green {
        border: 1px solid rgba(34, 197, 94, 0.3);
        box-shadow: 0 0 15px rgba(34, 197, 94, 0.1);
    }
    .glow-green:hover {
        border-color: #22c55e;
        box-shadow: 0 0 25px rgba(34, 197, 94, 0.4);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }
    
    /* Select Overrides */
    select {
        background-color: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }
</style>

<div class="mt-6 mb-10 container mx-auto px-4">
    <div class="mb-8 animate-enter">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 tracking-tight">
                    Dashboard <span class="text-slate-500 font-light">Analítico</span>
                </h2>
                <p class="text-slate-400 text-sm mt-1">Visão geral dos indicadores</p>
            </div>
            <button id="btnToggleCards" onclick="toggleCards()" class="glass-panel text-cyan-400 px-6 py-2 rounded-full hover:bg-cyan-900/30 transition text-sm font-bold border border-cyan-500/30 shadow-[0_0_15px_rgba(0,166,251,0.2)]">
                Ver em %
            </button>
        </div>
        
        <!-- Filtro de Mês -->
        <form method="GET" action="" class="flex items-center gap-3">
            <label class="text-slate-300 text-sm font-medium">Filtrar por Mês:</label>
            <select name="mes" onchange="this.form.submit()" class="bg-slate-800 border border-slate-600 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 px-4 py-2">
                <option value="">Todos os Meses</option>
                <?php foreach ($meses_disponiveis_filtro as $mes): ?>
                    <option value="<?php echo $mes; ?>" <?php echo ($filtro_mes == $mes) ? 'selected' : ''; ?>>
                        <?php echo date('m/Y', strtotime($mes . '-01')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($filtro_mes): ?>
                <a href="?" class="text-slate-400 hover:text-cyan-400 text-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpar Filtro
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Cards de Resumo -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <!-- Card Faturamento -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-blue animate-enter delay-100">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-cyan-300 uppercase tracking-wider mb-2">Total Faturado</p>
            <p class="text-3xl font-bold text-white relative z-10">R$ <?php echo number_format($totais['total_faturado'], 2, ',', '.'); ?></p>
            <div class="mt-4 flex items-center text-xs text-slate-400">
                <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 animate-pulse"></span> Atualizado
            </div>
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-blue-500 to-cyan-400 w-full"></div>
        </div>

        <!-- Card Glosado -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-red animate-enter delay-200">
             <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
            </div>
            <p class="text-sm font-medium text-red-300 uppercase tracking-wider mb-2">Total Glosado</p>
            <p class="text-3xl font-bold text-white relative z-10">
                <span class="card-value-money">R$ <?php echo number_format($totais['total_glosado'], 2, ',', '.'); ?></span>
                <span class="card-value-percent hidden"><?php echo number_format($perc_glosa_geral, 2, ',', '.'); ?>%</span>
            </p>
            <p class="text-xs text-red-400 mt-2 relative z-10">
                <span class="card-sub-default"><?php echo number_format($perc_glosa_geral, 2, ',', '.'); ?>% do Faturamento</span>
                <span class="card-sub-alt hidden">do Faturamento</span>
            </p>
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-red-600 to-red-400 w-full"></div>
        </div>

        <!-- Card Recursado -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-yellow animate-enter delay-300">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <p class="text-sm font-medium text-yellow-300 uppercase tracking-wider mb-2">Total Recursado</p>
            <p class="text-3xl font-bold text-white relative z-10">
                <span class="card-value-money">R$ <?php echo number_format($totais['total_recursado'], 2, ',', '.'); ?></span>
                <span class="card-value-percent hidden"><?php echo number_format($perc_recursado_geral, 2, ',', '.'); ?>%</span>
            </p>
            <p class="text-xs text-yellow-400 mt-2 relative z-10">
                <span class="card-sub-default"><?php echo number_format($perc_recursado_geral, 2, ',', '.'); ?>% do Glosado</span>
                <span class="card-sub-alt hidden">do Glosado</span>
            </p>
             <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-yellow-500 to-amber-300 w-full"></div>
        </div>

        <!-- Card Aceito -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-green animate-enter" style="animation-delay: 0.4s">
             <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-green-300 uppercase tracking-wider mb-2">Total Aceito</p>
            <p class="text-3xl font-bold text-white relative z-10">
                <span class="card-value-money">R$ <?php echo number_format($totais['total_aceito'], 2, ',', '.'); ?></span>
                <span class="card-value-percent hidden"><?php echo number_format($perc_aceito_geral, 2, ',', '.'); ?>%</span>
            </p>
            <p class="text-xs text-green-400 mt-2 relative z-10">
                <span class="card-sub-default"><?php echo number_format($perc_aceito_geral, 2, ',', '.'); ?>% do Recursado</span>
                <span class="card-sub-alt hidden">do Recursado</span>
            </p>
             <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-green-500 to-emerald-400 w-full"></div>
        </div>
    </div>

    <!-- Seção de Detalhamento por Convênio -->
    <div class="glass-panel rounded-xl p-6 mb-10 neon-border animate-enter delay-200">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <span class="w-1 h-6 bg-cyan-400 mr-3 rounded-full"></span>
                Análise Individual por Convênio
            </h3>
            
            <div class="flex gap-3 w-full md:w-auto">
                <!-- Filtro Competência -->
                <select id="selectCompetencia" onchange="updateConvenioCards()" class="bg-slate-800 border border-slate-600 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block w-full p-2.5">
                    <option value="">Todas as Competências</option>
                    <?php foreach ($meses_disponiveis as $mes): ?>
                        <option value="<?php echo $mes; ?>"><?php echo date('m/Y', strtotime($mes . '-01')); ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Filtro Convênio -->
                <select id="selectConvenio" onchange="updateConvenioCards()" class="bg-slate-800 border border-slate-600 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block w-full p-2.5">
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
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-cyan-400 uppercase">Faturado</p>
                <p class="text-xl font-bold text-white" id="convFaturado">R$ 0,00</p>
                <p class="text-xs text-slate-400 mt-1" id="convPercFaturado">0% do Total Geral</p>
            </div>

            <!-- Card Glosado Convênio -->
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-red-400 uppercase">Glosado</p>
                <p class="text-xl font-bold text-red-500" id="convGlosado">R$ 0,00</p>
                <p class="text-xs text-slate-400 mt-1" id="convPercGlosado">0% do Faturado</p>
            </div>

            <!-- Card Recursado Convênio -->
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-yellow-400 uppercase">Recursado</p>
                <p class="text-xl font-bold text-yellow-500" id="convRecursado">R$ 0,00</p>
                <p class="text-xs text-slate-400 mt-1" id="convPercRecursado">0% do Glosado</p>
            </div>

            <!-- Card Aceito Convênio -->
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-green-400 uppercase">Aceito</p>
                <p class="text-xl font-bold text-green-500" id="convAceito">R$ 0,00</p>
                <p class="text-xs text-slate-400 mt-1" id="convPercAceito">0% do Recursado</p>
            </div>
        </div>
        
        <div id="convenioEmptyState" class="text-center py-12 text-slate-500">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Selecione um convênio acima para visualizar os dados detalhados.
        </div>
    </div>

    <!-- Gráficos Linha 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 animate-enter delay-300">
        <!-- Evolução Mensal -->
        <div class="glass-panel p-6 rounded-xl hover-glow">
            <h3 class="text-lg font-bold text-white mb-6 border-b border-slate-700 pb-2">Evolução Mensal</h3>
            <div class="relative h-72">
                <canvas id="chartEvolucao"></canvas>
            </div>
        </div>

        <!-- Distribuição por Convênio -->
        <div class="glass-panel p-6 rounded-xl hover-glow">
            <div class="flex justify-between items-center mb-6 border-b border-slate-700 pb-2">
                <h3 class="text-lg font-bold text-white">Faturamento por Convênio</h3>
                <button id="btnToggleConvenio" onclick="toggleConvenio()" class="text-xs font-bold uppercase tracking-wide text-cyan-400 border border-cyan-800 px-3 py-1 rounded hover:bg-cyan-900/50 transition focus:outline-none">
                    Ver em %
                </button>
            </div>
            <div class="relative h-72">
                <canvas id="chartConvenio"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráficos Linha 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-enter" style="animation-delay: 0.5s">
        <!-- Top Glosas -->
        <div class="glass-panel p-6 rounded-xl hover-glow">
            <h3 class="text-lg font-bold text-white mb-6 border-b border-slate-700 pb-2">Top 5 Glosas</h3>
            <div class="relative h-72">
                <canvas id="chartTopGlosas"></canvas>
            </div>
        </div>
        
        <!-- Informativo Futuro -->
        <div class="glass-panel p-6 rounded-xl flex items-center justify-center text-slate-500 border border-dashed border-slate-700 opacity-70">
            <div class="text-center">
                 <div class="w-16 h-16 mx-auto bg-slate-800 rounded-full flex items-center justify-center mb-4 neon-border">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                 </div>
                <p>Novas métricas com IA em desenvolvimento...</p>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Configuração Global do Chart.js para Tema Escuro
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = '#334155';
    Chart.defaults.font.family = "'Inter', sans-serif";

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

    // 1. Gráfico de Evolução
    const ctxEvolucao = document.getElementById('chartEvolucao').getContext('2d');
    
    // Gradient para o Faturado
    const gradientFaturado = ctxEvolucao.createLinearGradient(0, 0, 0, 400);
    gradientFaturado.addColorStop(0, 'rgba(0, 166, 251, 0.5)'); // Neon Blue
    gradientFaturado.addColorStop(1, 'rgba(0, 166, 251, 0.0)');

    new Chart(ctxEvolucao, {
        type: 'line', // Usando linha para ambos para visual moderno, ou bar+line
        data: {
            labels: evolucaoLabels,
            datasets: [
                {
                    label: 'Faturado',
                    data: evolucaoFaturado,
                    type: 'bar', // Barra moderna
                    backgroundColor: gradientFaturado,
                    borderColor: '#00a6fb',
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: 0.6,
                    order: 2
                },
                {
                    label: 'Glosado',
                    data: evolucaoGlosado,
                    type: 'line',
                    borderColor: '#ef4444', // Red-500
                    backgroundColor: 'rgba(239, 68, 68, 0.2)',
                    borderWidth: 2,
                    pointBackgroundColor: '#ef4444',
                    tension: 0.4,
                    fill: true,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#e2e8f0',
                    bodyColor: '#e2e8f0',
                    borderColor: '#334155',
                    borderWidth: 1,
                    callbacks: {
                         label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                },
                legend: {
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(51, 65, 85, 0.3)'
                    },
                    ticks: {
                        callback: function(value) {
                             return new Intl.NumberFormat('pt-BR', { notation: "compact", compactDisplay: "short" }).format(value);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // 2. Gráfico de Convênios
    const ctxConvenio = document.getElementById('chartConvenio').getContext('2d');
    const totalConvenio = convenioValores.reduce((a, b) => Number(a) + Number(b), 0);
    let showPercentConvenio = false;

    const chartConvenio = new Chart(ctxConvenio, {
        type: 'doughnut',
        data: {
            labels: convenioLabels,
            datasets: [{
                data: convenioValores,
                backgroundColor: [
                    '#00a6fb', // Neon Blue
                    '#06b6d4', // Cyan
                    '#3b82f6', // Blue
                    '#6366f1', // Indigo
                    '#8b5cf6', // Violet
                    '#d946ef', // Fuchsia
                    '#f43f5e'  // Rose
                ],
                borderColor: '#0f172a',
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        color: '#94a3b8'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    borderColor: '#334155',
                    borderWidth: 1,
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
                                return label + new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
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

    // 3. Gráfico Top Glosas
    const ctxTopGlosas = document.getElementById('chartTopGlosas').getContext('2d');
    
    // Gradient Red
    const gradientGlosas = ctxTopGlosas.createLinearGradient(400, 0, 0, 0);
    gradientGlosas.addColorStop(0, '#ef4444');
    gradientGlosas.addColorStop(1, '#991b1b');

    new Chart(ctxTopGlosas, {
        type: 'bar',
        data: {
            labels: topGlosasLabels,
            datasets: [{
                label: 'Valor Glosado',
                data: topGlosasValores,
                backgroundColor: gradientGlosas,
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            indexAxis: 'y', 
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    borderColor: '#334155',
                    borderWidth: 1,
                     callbacks: {
                        label: function(context) {
                            return ' Glosado: ' + new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.raw);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(51, 65, 85, 0.3)' },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('pt-BR', { notation: "compact" }).format(value);
                        }
                    }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });

    // --- Interatividade dos Cards ---

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

            // Recalcular percentuais locais
            const percFaturado = totalFaturadoGeral > 0 ? (faturado / totalFaturadoGeral * 100) : 0;
            const percGlosado = faturado > 0 ? (glosado / faturado * 100) : 0;
            const percRecursado = glosado > 0 ? (recursado / glosado * 100) : 0;
            const percAceito = recursado > 0 ? (aceito / recursado * 100) : 0;

            document.getElementById('convFaturado').textContent = formatMoney(faturado);
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
            container.classList.add('hidden');
            emptyState.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Nenhum dado encontrado para este filtro.';
            emptyState.classList.remove('hidden');
        }
    }

    function formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    }

    function formatPercent(value) {
        return value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%';
    }

</script>

<?php include 'includes/footer.php'; ?>
