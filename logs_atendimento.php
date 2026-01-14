<?php
require_once 'db_config.php';
include 'includes/header.php';

// Apenas administradores podem ver logs
if ($_SESSION['usuario_nivel'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Filtros
$filtro_atendimento_id = isset($_GET['atendimento_id']) ? $_GET['atendimento_id'] : '';
$filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';
$filtro_acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Paginação
$itens_por_pagina = 50;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Construir query
$where_clauses = [];
$params = [];

if ($filtro_atendimento_id) {
    $where_clauses[] = "l.atendimento_id = ?";
    $params[] = $filtro_atendimento_id;
}

if ($filtro_usuario) {
    $where_clauses[] = "l.usuario_id = ?";
    $params[] = $filtro_usuario;
}

if ($filtro_acao) {
    $where_clauses[] = "l.acao = ?";
    $params[] = $filtro_acao;
}

if ($filtro_data_inicio) {
    $where_clauses[] = "DATE(l.data_hora) >= ?";
    $params[] = $filtro_data_inicio;
}

if ($filtro_data_fim) {
    $where_clauses[] = "DATE(l.data_hora) <= ?";
    $params[] = $filtro_data_fim;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Buscar logs
try {
    // Contar total
    $sql_count = "SELECT COUNT(*) as total FROM logs_atendimento l $where_sql";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $itens_por_pagina);
    
    // Buscar registros
    $sql = "
        SELECT 
            l.*,
            i.paciente,
            i.guia_paciente
        FROM logs_atendimento l
        LEFT JOIN internacoes i ON l.atendimento_id = i.id
        $where_sql
        ORDER BY l.data_hora DESC
        LIMIT $itens_por_pagina OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar usuários para filtro
    $stmt_usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $logs = [];
    $usuarios = [];
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro ao carregar logs: " . $e->getMessage() . "</div>";
}
?>

<div class="mt-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Logs de Atendimento</h2>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'excluido'): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong>Sucesso!</strong> Log excluído com sucesso.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['erro'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong>Erro!</strong> <?php echo htmlspecialchars($_GET['erro']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Filtros -->
    <div class="bg-white p-4 rounded-lg shadow border border-gray-200 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div>
                <label for="atendimento_id" class="block text-sm font-medium text-gray-700 mb-1">ID Atendimento</label>
                <input type="number" name="atendimento_id" id="atendimento_id" value="<?php echo htmlspecialchars($filtro_atendimento_id); ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label for="usuario" class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
                <select name="usuario" id="usuario" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $filtro_usuario == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="acao" class="block text-sm font-medium text-gray-700 mb-1">Ação</label>
                <select name="acao" id="acao" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">Todas</option>
                    <option value="CRIACAO" <?php echo $filtro_acao == 'CRIACAO' ? 'selected' : ''; ?>>Criação</option>
                    <option value="EDICAO" <?php echo $filtro_acao == 'EDICAO' ? 'selected' : ''; ?>>Edição</option>
                    <option value="EXCLUSAO" <?php echo $filtro_acao == 'EXCLUSAO' ? 'selected' : ''; ?>>Exclusão</option>
                </select>
            </div>
            <div>
                <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                <input type="date" name="data_inicio" id="data_inicio" value="<?php echo htmlspecialchars($filtro_data_inicio); ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                <input type="date" name="data_fim" id="data_fim" value="<?php echo htmlspecialchars($filtro_data_fim); ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition text-sm font-medium">
                    Filtrar
                </button>
            </div>
        </form>
    </div>
    
    <!-- Estatísticas -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-gray-700">
            <strong>Total de registros:</strong> <?php echo number_format($total_registros, 0, ',', '.'); ?> logs
            | <strong>Página:</strong> <?php echo $pagina_atual; ?> de <?php echo $total_paginas; ?>
        </p>
    </div>
    
    <!-- Tabela de Logs -->
    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ação</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Atendimento</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalhes</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                <?php echo date('d/m/Y H:i:s', strtotime($log['data_hora'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo htmlspecialchars($log['usuario_nome']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php
                                $cor = $log['acao'] == 'CRIACAO' ? 'text-green-600' : ($log['acao'] == 'EDICAO' ? 'text-blue-600' : 'text-red-600');
                                echo "<span class='font-semibold $cor'>" . $log['acao'] . "</span>";
                                ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if ($log['atendimento_id']): ?>
                                    <a href="registrar_internacao.php?id=<?php echo $log['atendimento_id']; ?>" class="text-blue-600 hover:underline">
                                        #<?php echo $log['atendimento_id']; ?>
                                        <?php if ($log['paciente']): ?>
                                            - <?php echo htmlspecialchars($log['paciente']); ?>
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm max-w-md">
                                <div class="truncate" title="<?php echo htmlspecialchars($log['detalhes']); ?>">
                                    <?php echo htmlspecialchars($log['detalhes']); ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <div class="flex justify-center items-center gap-2">
                                    <button onclick="verDetalhes(<?php echo $log['id']; ?>)" class="text-blue-600 hover:text-blue-900" title="Ver detalhes completos">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <a href="javascript:void(0);" onclick="if(confirm('Tem certeza que deseja excluir este log?')) window.location.href='excluir_log.php?id=<?php echo $log['id']; ?>';" class="text-red-600 hover:text-red-900" title="Excluir log">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <!-- Linha expandível com detalhes -->
                        <tr id="detalhe_<?php echo $log['id']; ?>" class="hidden bg-gray-50">
                            <td colspan="7" class="px-4 py-3">
                                <?php
                                // Decodificar JSON para comparação
                                $anteriores = $log['valores_anteriores'] ? json_decode($log['valores_anteriores'], true) : [];
                                $novos = $log['valores_novos'] ? json_decode($log['valores_novos'], true) : [];
                                
                                // Identificar campos alterados
                                $campos_alterados = [];
                                if (is_array($anteriores) && is_array($novos)) {
                                    foreach ($novos as $campo => $valor_novo) {
                                        if (isset($anteriores[$campo]) && $anteriores[$campo] != $valor_novo) {
                                            $campos_alterados[] = $campo;
                                        }
                                    }
                                }
                                ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php if ($log['valores_anteriores']): ?>
                                        <div>
                                            <h4 class="font-semibold text-gray-700 mb-2">Valores Anteriores:</h4>
                                            <div class="bg-white p-3 rounded border text-xs overflow-x-auto">
                                                <?php if (is_array($anteriores)): ?>
                                                    <table class="w-full">
                                                        <?php foreach ($anteriores as $campo => $valor): ?>
                                                            <tr class="<?php echo in_array($campo, $campos_alterados) ? 'bg-red-50' : ''; ?>">
                                                                <td class="font-semibold py-1 pr-2 <?php echo in_array($campo, $campos_alterados) ? 'text-red-700' : 'text-gray-700'; ?>">
                                                                    <?php echo htmlspecialchars($campo); ?>:
                                                                </td>
                                                                <td class="py-1 <?php echo in_array($campo, $campos_alterados) ? 'text-red-600' : 'text-gray-600'; ?>">
                                                                    <?php echo htmlspecialchars($valor ?? 'null'); ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </table>
                                                <?php else: ?>
                                                    <pre><?php echo htmlspecialchars($log['valores_anteriores']); ?></pre>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($log['valores_novos']): ?>
                                        <div>
                                            <h4 class="font-semibold text-gray-700 mb-2">Valores Novos:</h4>
                                            <div class="bg-white p-3 rounded border text-xs overflow-x-auto">
                                                <?php if (is_array($novos)): ?>
                                                    <table class="w-full">
                                                        <?php foreach ($novos as $campo => $valor): ?>
                                                            <tr class="<?php echo in_array($campo, $campos_alterados) ? 'bg-red-50' : ''; ?>">
                                                                <td class="font-semibold py-1 pr-2 <?php echo in_array($campo, $campos_alterados) ? 'text-red-700' : 'text-gray-700'; ?>">
                                                                    <?php echo htmlspecialchars($campo); ?>:
                                                                </td>
                                                                <td class="py-1 <?php echo in_array($campo, $campos_alterados) ? 'text-red-600' : 'text-gray-600'; ?>">
                                                                    <?php echo htmlspecialchars($valor ?? 'null'); ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </table>
                                                <?php else: ?>
                                                    <pre><?php echo htmlspecialchars($log['valores_novos']); ?></pre>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Nenhum log encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginação -->
    <?php if ($total_paginas > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="inline-flex rounded-md shadow">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'pagina'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                       class="px-4 py-2 text-sm font-medium border <?php echo $i == $pagina_atual ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
function verDetalhes(id) {
    const elemento = document.getElementById('detalhe_' + id);
    elemento.classList.toggle('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
