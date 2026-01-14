<?php
require_once 'db_config.php';
include 'includes/header.php';

// Filtro de Mês
$filtro_mes = isset($_GET['mes']) ? $_GET['mes'] : '';

// Buscar meses disponíveis
try {
    $sql_meses_disponiveis = "SELECT DISTINCT DATE_FORMAT(competencia, '%Y-%m') as mes FROM relatorio_mensal_consolidado ORDER BY mes DESC";
    $stmt = $pdo->query($sql_meses_disponiveis);
    $meses_disponiveis_filtro = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $meses_disponiveis_filtro = [];
}

// Condição WHERE para filtro
$where_filtro = "";
if ($filtro_mes) {
    $where_filtro = " WHERE DATE_FORMAT(competencia, '%Y-%m') = " . $pdo->quote($filtro_mes);
}

// --- 1. Totais Gerais (Cards) ---
try {
    $sql_totais = "
        SELECT 
            SUM(valor_inicial) as total_inicial,
            SUM(valor_retirado) as total_retirado,
            SUM(valor_acrescentado) as total_acrescentado,
            SUM(valor_final) as total_final,
            SUM(valor_glosado) as total_glosado,
            SUM(valor_aceito) as total_aceito
        FROM relatorio_mensal_consolidado
        $where_filtro
    ";
    $stmt = $pdo->query($sql_totais);
    $totais = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Evitar null
    $totais['total_inicial'] = $totais['total_inicial'] ?? 0;
    $totais['total_retirado'] = $totais['total_retirado'] ?? 0;
    $totais['total_acrescentado'] = $totais['total_acrescentado'] ?? 0;
    $totais['total_final'] = $totais['total_final'] ?? 0;
    $totais['total_glosado'] = $totais['total_glosado'] ?? 0;
    $totais['total_aceito'] = $totais['total_aceito'] ?? 0;

    // Calcular percentuais
    $perc_retirado_geral = ($totais['total_inicial'] > 0) 
        ? ($totais['total_retirado'] / $totais['total_inicial']) * 100 
        : 0;
    $perc_acrescentado_geral = ($totais['total_inicial'] > 0) 
        ? ($totais['total_acrescentado'] / $totais['total_inicial']) * 100 
        : 0;
    $perc_glosado_geral = ($totais['total_final'] > 0) 
        ? ($totais['total_glosado'] / $totais['total_final']) * 100 
        : 0;
    $perc_aceito_geral = ($totais['total_glosado'] > 0) 
        ? ($totais['total_aceito'] / $totais['total_glosado']) * 100 
        : 0;

} catch (PDOException $e) {
    $totais = ['total_inicial' => 0, 'total_retirado' => 0, 'total_acrescentado' => 0, 'total_final' => 0, 'total_glosado' => 0, 'total_aceito' => 0];
    $perc_retirado_geral = $perc_acrescentado_geral = $perc_glosado_geral = $perc_aceito_geral = 0;
}

// --- 2. Evolução Mensal ---
try {
    $sql_evolucao = "
        SELECT 
            DATE_FORMAT(competencia, '%Y-%m') as mes_sort,
            DATE_FORMAT(competencia, '%m/%Y') as mes_label,
            SUM(valor_retirado) as valor_retirado,
            SUM(valor_acrescentado) as valor_acrescentado
        FROM relatorio_mensal_consolidado
        GROUP BY mes_sort, mes_label
        ORDER BY mes_sort ASC
        LIMIT 12
    ";
    $stmt = $pdo->query($sql_evolucao);
    $evolucao_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $evolucao_data = [];
}

// --- 3. Distribuição por Convênio ---
try {
    $sql_convenio = "
        SELECT 
            c.nome_convenio,
            SUM(r.valor_final) as total
        FROM relatorio_mensal_consolidado r
        JOIN convenios c ON r.convenio_id = c.id
        GROUP BY c.nome_convenio
        ORDER BY total DESC
    ";
    $stmt = $pdo->query($sql_convenio);
    $convenio_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenio_data = [];
}

// --- 4. Top 5 Convênios com Maior Glosa ---
try {
    $sql_top_glosas = "
        SELECT 
            c.nome_convenio,
            SUM(r.valor_glosado) as total_glosa
        FROM relatorio_mensal_consolidado r
        JOIN convenios c ON r.convenio_id = c.id
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

// --- 5. Detalhamento por Convênio ---
try {
    $sql_meses = "SELECT DISTINCT DATE_FORMAT(competencia, '%Y-%m') as mes FROM relatorio_mensal_consolidado ORDER BY mes DESC";
    $stmt_meses = $pdo->query($sql_meses);
    $meses_disponiveis = $stmt_meses->fetchAll(PDO::FETCH_COLUMN);

    $sql_detalhe_convenio = "
        SELECT 
            c.id as convenio_id,
            c.nome_convenio,
            DATE_FORMAT(r.competencia, '%Y-%m') as mes_competencia,
            SUM(r.valor_inicial) as total_inicial,
            SUM(r.valor_retirado) as total_retirado,
            SUM(r.valor_acrescentado) as total_acrescentado,
            SUM(r.valor_final) as total_final,
            SUM(r.valor_glosado) as total_glosado,
            SUM(r.valor_aceito) as total_aceito
        FROM relatorio_mensal_consolidado r
        JOIN convenios c ON r.convenio_id = c.id
        GROUP BY c.id, c.nome_convenio, mes_competencia
        ORDER BY c.nome_convenio, mes_competencia DESC
    ";
    $stmt = $pdo->query($sql_detalhe_convenio);
    $detalhe_convenio_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $detalhe_convenio_data = [];
    $meses_disponiveis = [];
}

// --- 6. Comparativo Inicial vs Final por Convênio ---
try {
    $sql_comparativo = "
        SELECT 
            c.nome_convenio,
            SUM(r.valor_inicial) as total_inicial,
            SUM(r.valor_final) as total_final
        FROM relatorio_mensal_consolidado r
        JOIN convenios c ON r.convenio_id = c.id
        GROUP BY c.nome_convenio
        ORDER BY c.nome_convenio
        LIMIT 8
    ";
    $stmt = $pdo->query($sql_comparativo);
    $comparativo_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $comparativo_data = [];
}
?>

<style>
    body {
        background-color: #0b1121 !important;
        color: #e2e8f0 !important;
        font-family: 'Inter', sans-serif;
    }

    nav.bg-blue-600 {
        background-color: #0f172a !important;
        border-bottom: 1px solid #1e293b;
    }
    nav a.text-white {
        color: #e2e8f0 !important;
    }
    nav a:hover {
        color: #00a6fb !important;
        background-color: rgba(0, 166, 251, 0.1) !important;
    }

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
                    Dashboard <span class="text-slate-500 font-light">Relatório Mensal</span>
                </h2>
                <p class="text-slate-400 text-sm mt-1">Análise consolidada por competência</p>
            </div>
            <div class="flex gap-3">
                <a href="relatorio_mensal.php" class="glass-panel text-slate-400 px-6 py-2 rounded-full hover:bg-slate-800/50 transition text-sm font-medium border border-slate-700">
                    Ver Tabela
                </a>
                <button id="btnToggleCards" onclick="toggleCards()" class="glass-panel text-cyan-400 px-6 py-2 rounded-full hover:bg-cyan-900/30 transition text-sm font-bold border border-cyan-500/30 shadow-[0_0_15px_rgba(0,166,251,0.2)]">
                    Ver em %
                </button>
            </div>
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
        <!-- Card Valor Inicial -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-blue animate-enter delay-100">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-cyan-300 uppercase tracking-wider mb-2">Valor Inicial</p>
            <p class="text-3xl font-bold text-white relative z-10">R$ <?php echo number_format($totais['total_inicial'], 2, ',', '.'); ?></p>
            <div class="mt-4 flex items-center text-xs text-slate-400">
                <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 animate-pulse"></span> Total Geral
            </div>
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-blue-500 to-cyan-400 w-full"></div>
        </div>

        <!-- Card Valor Retirado -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-red animate-enter delay-200">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-red-300 uppercase tracking-wider mb-2">Valor Retirado</p>
            <p class="text-3xl font-bold text-white relative z-10">
                <span class="card-value-money">R$ <?php echo number_format($totais['total_retirado'], 2, ',', '.'); ?></span>
                <span class="card-value-percent hidden"><?php echo number_format($perc_retirado_geral, 2, ',', '.'); ?>%</span>
            </p>
            <p class="text-xs text-red-400 mt-2 relative z-10">
                <span class="card-sub-default"><?php echo number_format($perc_retirado_geral, 2, ',', '.'); ?>% do Valor Inicial</span>
                <span class="card-sub-alt hidden">do Valor Inicial</span>
            </p>
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-red-600 to-red-400 w-full"></div>
        </div>

        <!-- Card Valor Acrescentado -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-green animate-enter delay-300">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-green-300 uppercase tracking-wider mb-2">Valor Acrescentado</p>
            <p class="text-3xl font-bold text-white relative z-10">
                <span class="card-value-money">R$ <?php echo number_format($totais['total_acrescentado'], 2, ',', '.'); ?></span>
                <span class="card-value-percent hidden"><?php echo number_format($perc_acrescentado_geral, 2, ',', '.'); ?>%</span>
            </p>
            <p class="text-xs text-green-400 mt-2 relative z-10">
                <span class="card-sub-default"><?php echo number_format($perc_acrescentado_geral, 2, ',', '.'); ?>% do Valor Inicial</span>
                <span class="card-sub-alt hidden">do Valor Inicial</span>
            </p>
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-green-500 to-emerald-400 w-full"></div>
        </div>

        <!-- Card Valor Final -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-yellow animate-enter" style="animation-delay: 0.4s">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-yellow-300 uppercase tracking-wider mb-2">Valor Final</p>
            <p class="text-3xl font-bold text-white relative z-10">R$ <?php echo number_format($totais['total_final'], 2, ',', '.'); ?></p>
            <p class="text-xs text-yellow-400 mt-2 relative z-10">Inicial - Retirado + Acrescentado</p>
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-yellow-500 to-amber-300 w-full"></div>
        </div>
    </div>

    <!-- Cards Glosa/Aceito -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        <!-- Card Glosado -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-red animate-enter" style="animation-delay: 0.5s">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-red-300 uppercase tracking-wider mb-2">Total Glosado</p>
            <p class="text-3xl font-bold text-white relative z-10">
                <span class="card-value-money">R$ <?php echo number_format($totais['total_glosado'], 2, ',', '.'); ?></span>
                <span class="card-value-percent hidden"><?php echo number_format($perc_glosado_geral, 2, ',', '.'); ?>%</span>
            </p>
            <p class="text-xs text-red-400 mt-2 relative z-10">
                <span class="card-sub-default"><?php echo number_format($perc_glosado_geral, 2, ',', '.'); ?>% do Valor Final</span>
                <span class="card-sub-alt hidden">do Valor Final</span>
            </p>
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-red-600 to-red-400 w-full"></div>
        </div>

        <!-- Card Aceito -->
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group glow-green animate-enter" style="animation-delay: 0.6s">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <p class="text-sm font-medium text-green-300 uppercase tracking-wider mb-2">Total Aceito</p>
            <p class="text-3xl font-bold text-white relative z-10">
                <span class="card-value-money">R$ <?php echo number_format($totais['total_aceito'], 2, ',', '.'); ?></span>
                <span class="card-value-percent hidden"><?php echo number_format($perc_aceito_geral, 2, ',', '.'); ?>%</span>
            </p>
            <p class="text-xs text-green-400 mt-2 relative z-10">
                <span class="card-sub-default"><?php echo number_format($perc_aceito_geral, 2, ',', '.'); ?>% do Glosado</span>
                <span class="card-sub-alt hidden">do Glosado</span>
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
                <select id="selectCompetencia" onchange="updateConvenioCards()" class="bg-slate-800 border border-slate-600 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block w-full p-2.5">
                    <option value="">Todas as Competências</option>
                    <?php foreach ($meses_disponiveis as $mes): ?>
                        <option value="<?php echo $mes; ?>"><?php echo date('m/Y', strtotime($mes . '-01')); ?></option>
                    <?php endforeach; ?>
                </select>

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

        <div id="convenioCardsContainer" class="hidden grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-cyan-400 uppercase">Inicial</p>
                <p class="text-xl font-bold text-white" id="convInicial">R$ 0,00</p>
                <p class="text-xs text-slate-400 mt-1">Base de cálculo</p>
            </div>
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-red-400 uppercase">Retirado</p>
                <p class="text-xl font-bold text-red-500" id="convRetirado">R$ 0,00</p>
                <p class="text-xs text-red-400 mt-1" id="convPercRetirado">0,00% do Inicial</p>
            </div>
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-green-400 uppercase">Acrescentado</p>
                <p class="text-xl font-bold text-green-500" id="convAcrescentado">R$ 0,00</p>
                <p class="text-xs text-green-400 mt-1" id="convPercAcrescentado">0,00% do Inicial</p>
            </div>
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-yellow-400 uppercase">Final</p>
                <p class="text-xl font-bold text-yellow-500" id="convFinal">R$ 0,00</p>
                <p class="text-xs text-slate-400 mt-1">Inicial - Retirado + Acrescentado</p>
            </div>
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-red-400 uppercase">Glosado</p>
                <p class="text-xl font-bold text-red-500" id="convGlosado">R$ 0,00</p>
                <p class="text-xs text-red-400 mt-1" id="convPercGlosado">0,00% do Final</p>
            </div>
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <p class="text-xs font-medium text-green-400 uppercase">Aceito</p>
                <p class="text-xl font-bold text-green-500" id="convAceito">R$ 0,00</p>
                <p class="text-xs text-green-400 mt-1" id="convPercAceito">0,00% do Glosado</p>
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
        <div class="glass-panel p-6 rounded-xl">
            <h3 class="text-lg font-bold text-white mb-6 border-b border-slate-700 pb-2">Evolução Mensal (Retirado vs Acrescentado)</h3>
            <div class="relative h-72">
                <canvas id="chartEvolucao"></canvas>
            </div>
        </div>

        <!-- Distribuição por Convênio -->
        <div class="glass-panel p-6 rounded-xl">
            <div class="flex justify-between items-center mb-6 border-b border-slate-700 pb-2">
                <h3 class="text-lg font-bold text-white">Valor Final por Convênio</h3>
            </div>
            <div class="relative h-72">
                <canvas id="chartConvenio"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráficos Linha 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-enter" style="animation-delay: 0.5s">
        <!-- Top Glosas -->
        <div class="glass-panel p-6 rounded-xl">
            <h3 class="text-lg font-bold text-white mb-6 border-b border-slate-700 pb-2">Top 5 Convênios - Maior Glosa</h3>
            <div class="relative h-72">
                <canvas id="chartTopGlosas"></canvas>
            </div>
        </div>
        
        <!-- Comparativo Inicial vs Final -->
        <div class="glass-panel p-6 rounded-xl">
            <h3 class="text-lg font-bold text-white mb-6 border-b border-slate-700 pb-2">Comparativo: Inicial vs Final</h3>
            <div class="relative h-72">
                <canvas id="chartComparativo"></canvas>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = '#334155';
    Chart.defaults.font.family = "'Inter', sans-serif";

    const convenioData = <?php echo json_encode($detalhe_convenio_data); ?>;
    const totalInicialGeral = <?php echo $totais['total_inicial']; ?>;
    
    const evolucaoLabels = <?php echo json_encode(array_column($evolucao_data, 'mes_label')); ?>;
    const evolucaoRetirado = <?php echo json_encode(array_column($evolucao_data, 'valor_retirado')); ?>;
    const evolucaoAcrescentado = <?php echo json_encode(array_column($evolucao_data, 'valor_acrescentado')); ?>;

    const convenioLabels = <?php echo json_encode(array_column($convenio_data, 'nome_convenio')); ?>;
    const convenioValores = <?php echo json_encode(array_column($convenio_data, 'total')); ?>;

    const topGlosasLabels = <?php echo json_encode(array_column($top_glosas_data, 'nome_convenio')); ?>;
    const topGlosasValores = <?php echo json_encode(array_column($top_glosas_data, 'total_glosa')); ?>;

    const comparativoLabels = <?php echo json_encode(array_column($comparativo_data, 'nome_convenio')); ?>;
    const comparativoInicial = <?php echo json_encode(array_column($comparativo_data, 'total_inicial')); ?>;
    const comparativoFinal = <?php echo json_encode(array_column($comparativo_data, 'total_final')); ?>;

    // 1. Gráfico de Evolução
    const ctxEvolucao = document.getElementById('chartEvolucao').getContext('2d');
    const gradientRetirado = ctxEvolucao.createLinearGradient(0, 0, 0, 400);
    gradientRetirado.addColorStop(0, 'rgba(239, 68, 68, 0.5)');
    gradientRetirado.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

    const gradientAcrescentado = ctxEvolucao.createLinearGradient(0, 0, 0, 400);
    gradientAcrescentado.addColorStop(0, 'rgba(34, 197, 94, 0.5)');
    gradientAcrescentado.addColorStop(1, 'rgba(34, 197, 94, 0.0)');

    new Chart(ctxEvolucao, {
        type: 'line',
        data: {
            labels: evolucaoLabels,
            datasets: [
                {
                    label: 'Valor Retirado',
                    data: evolucaoRetirado,
                    type: 'bar',
                    backgroundColor: gradientRetirado,
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: 0.6,
                    order: 2
                },
                {
                    label: 'Valor Acrescentado',
                    data: evolucaoAcrescentado,
                    type: 'line',
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderWidth: 2,
                    pointBackgroundColor: '#22c55e',
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
                            if (label) label += ': ';
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
                    grid: { color: 'rgba(51, 65, 85, 0.3)' },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('pt-BR', { notation: "compact", compactDisplay: "short" }).format(value);
                        }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Gráfico de Convênios
    const ctxConvenio = document.getElementById('chartConvenio').getContext('2d');
    new Chart(ctxConvenio, {
        type: 'doughnut',
        data: {
            labels: convenioLabels,
            datasets: [{
                data: convenioValores,
                backgroundColor: [
                    '#00a6fb', '#06b6d4', '#3b82f6', '#6366f1',
                    '#8b5cf6', '#d946ef', '#f43f5e', '#fb923c'
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
                        padding: 15,
                        color: '#94a3b8',
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    borderColor: '#334155',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) label += ': ';
                            return label + new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.raw);
                        }
                    }
                }
            }
        }
    });

    // 3. Gráfico Top Glosas
    const ctxTopGlosas = document.getElementById('chartTopGlosas').getContext('2d');
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
                y: { grid: { display: false } }
            }
        }
    });

    // 4. Gráfico Comparativo
    const ctxComparativo = document.getElementById('chartComparativo').getContext('2d');
    new Chart(ctxComparativo, {
        type: 'bar',
        data: {
            labels: comparativoLabels,
            datasets: [
                {
                    label: 'Valor Inicial',
                    data: comparativoInicial,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Valor Final',
                    data: comparativoFinal,
                    backgroundColor: 'rgba(234, 179, 8, 0.7)',
                    borderColor: '#eab308',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    borderColor: '#334155',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            return label + new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(51, 65, 85, 0.3)' },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('pt-BR', { notation: "compact" }).format(value);
                        }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    function toggleCards() {
        const moneys = document.querySelectorAll('.card-value-money');
        const percents = document.querySelectorAll('.card-value-percent');
        const subDefaults = document.querySelectorAll('.card-sub-default');
        const subAlts = document.querySelectorAll('.card-sub-alt');
        const btn = document.getElementById('btnToggleCards');
        
        const isShowingPercent = btn.textContent.includes('R$');

        if (isShowingPercent) {
            moneys.forEach(el => el.classList.remove('hidden'));
            percents.forEach(el => el.classList.add('hidden'));
            subDefaults.forEach(el => el.classList.remove('hidden'));
            subAlts.forEach(el => el.classList.add('hidden'));
            btn.textContent = 'Ver em %';
        } else {
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

        const dadosFiltrados = convenioData.filter(item => {
            const matchConvenio = item.convenio_id == convenioId;
            const matchCompetencia = competencia === "" || item.mes_competencia === competencia;
            return matchConvenio && matchCompetencia;
        });

        if (dadosFiltrados.length > 0) {
            let inicial = 0, retirado = 0, acrescentado = 0, final = 0, glosado = 0, aceito = 0;

            dadosFiltrados.forEach(d => {
                inicial += parseFloat(d.total_inicial || 0);
                retirado += parseFloat(d.total_retirado || 0);
                acrescentado += parseFloat(d.total_acrescentado || 0);
                final += parseFloat(d.total_final || 0);
                glosado += parseFloat(d.total_glosado || 0);
                aceito += parseFloat(d.total_aceito || 0);
            });

            // Calcular percentuais
            const percRetirado = inicial > 0 ? (retirado / inicial * 100) : 0;
            const percAcrescentado = inicial > 0 ? (acrescentado / inicial * 100) : 0;
            const percGlosado = final > 0 ? (glosado / final * 100) : 0;
            const percAceito = glosado > 0 ? (aceito / glosado * 100) : 0;

            document.getElementById('convInicial').textContent = formatMoney(inicial);
            document.getElementById('convRetirado').textContent = formatMoney(retirado);
            document.getElementById('convPercRetirado').textContent = formatPercent(percRetirado) + ' do Inicial';
            document.getElementById('convAcrescentado').textContent = formatMoney(acrescentado);
            document.getElementById('convPercAcrescentado').textContent = formatPercent(percAcrescentado) + ' do Inicial';
            document.getElementById('convFinal').textContent = formatMoney(final);
            document.getElementById('convGlosado').textContent = formatMoney(glosado);
            document.getElementById('convPercGlosado').textContent = formatPercent(percGlosado) + ' do Final';
            document.getElementById('convAceito').textContent = formatMoney(aceito);
            document.getElementById('convPercAceito').textContent = formatPercent(percAceito) + ' do Glosado';

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
