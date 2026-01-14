<?php
require_once 'db_config.php';
include 'includes/header.php';

$mensagem = '';
$tipo_msg = '';

// Excluir contrato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    try {
        // Buscar arquivo antes de excluir
        $stmt = $pdo->prepare("SELECT arquivo_contrato FROM contratos WHERE id = ?");
        $stmt->execute([$id]);
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Excluir registro
        $stmt = $pdo->prepare("DELETE FROM contratos WHERE id = ?");
        $stmt->execute([$id]);
        
        // Excluir arquivo físico se existir
        if ($contrato && $contrato['arquivo_contrato']) {
            $arquivo_path = __DIR__ . '/uploads/contratos/' . $contrato['arquivo_contrato'];
            if (file_exists($arquivo_path)) {
                unlink($arquivo_path);
            }
        }
        
        $mensagem = "Contrato excluído com sucesso!";
        $tipo_msg = 'success';
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir contrato: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}

// Filtros
$filtro_convenio = isset($_GET['convenio']) ? $_GET['convenio'] : '';
$filtro_ativo = isset($_GET['ativo']) ? $_GET['ativo'] : '';

// Buscar convênios para filtro
try {
    $stmt = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
    $convenios_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenios_lista = [];
}

// Construir query com filtros
$where_clauses = ["1=1"];
$params = [];

if ($filtro_convenio) {
    $where_clauses[] = "ct.convenio_id = ?";
    $params[] = $filtro_convenio;
}

if ($filtro_ativo !== '') {
    $where_clauses[] = "ct.ativo = ?";
    $params[] = $filtro_ativo;
}

$where_sql = implode(" AND ", $where_clauses);

// Buscar contratos
try {
    $sql = "
        SELECT 
            ct.*,
            c.nome_convenio,
            DATEDIFF(ct.data_fim, CURDATE()) as dias_para_vencer
        FROM contratos ct
        JOIN convenios c ON ct.convenio_id = c.id
        WHERE $where_sql
        ORDER BY ct.data_inicio DESC, c.nome_convenio
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas
    $total_contratos = count($contratos);
    $contratos_ativos = count(array_filter($contratos, fn($c) => $c['ativo'] == 1));
    $contratos_vencendo = count(array_filter($contratos, fn($c) => $c['dias_para_vencer'] > 0 && $c['dias_para_vencer'] <= 30 && $c['ativo'] == 1));
    $contratos_vencidos = count(array_filter($contratos, fn($c) => $c['dias_para_vencer'] < 0 && $c['ativo'] == 1));
    
} catch (PDOException $e) {
    $contratos = [];
    $mensagem = "Erro ao carregar contratos: " . $e->getMessage();
    $tipo_msg = 'error';
}
?>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">📄 Gestão de Contratos</h2>
        <a href="contratos_form.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
            + Novo Contrato
        </a>
    </div>

    <?php if ($mensagem): ?>
        <div class="<?php echo $tipo_msg === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> border px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 font-medium">Total de Contratos</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_contratos; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 font-medium">Contratos Ativos</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $contratos_ativos; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 font-medium">Vencendo (30 dias)</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $contratos_vencendo; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 font-medium">Contratos Vencidos</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $contratos_vencidos; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-4 rounded-lg shadow border border-gray-200 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="convenio" class="block text-sm font-medium text-gray-700 mb-1">Convênio</label>
                <select name="convenio" id="convenio" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[200px]">
                    <option value="">Todos</option>
                    <?php foreach ($convenios_lista as $conv): ?>
                        <option value="<?php echo $conv['id']; ?>" <?php echo $filtro_convenio == $conv['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($conv['nome_convenio']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="ativo" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="ativo" id="ativo" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="1" <?php echo $filtro_ativo === '1' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="0" <?php echo $filtro_ativo === '0' ? 'selected' : ''; ?>>Inativos</option>
                </select>
            </div>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition text-sm font-medium">
                Filtrar
            </button>
            
            <?php if ($filtro_convenio || $filtro_ativo !== ''): ?>
                <a href="contratos.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition text-sm font-medium">
                    Limpar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela de Contratos -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Convênio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nº Contrato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vigência</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Arquivo</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($contratos) > 0): ?>
                        <?php foreach ($contratos as $contrato): ?>
                            <?php
                            $status_class = 'bg-green-100 text-green-800';
                            $status_text = 'Ativo';
                            
                            if ($contrato['ativo'] == 0) {
                                $status_class = 'bg-gray-100 text-gray-800';
                                $status_text = 'Inativo';
                            } elseif ($contrato['dias_para_vencer'] < 0) {
                                $status_class = 'bg-red-100 text-red-800';
                                $status_text = 'Vencido';
                            } elseif ($contrato['dias_para_vencer'] <= 30) {
                                $status_class = 'bg-yellow-100 text-yellow-800';
                                $status_text = 'A Vencer';
                            }
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($contrato['nome_convenio']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($contrato['numero_contrato']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo date('d/m/Y', strtotime($contrato['data_inicio'])); ?>
                                    <?php if ($contrato['data_fim']): ?>
                                        → <?php echo date('d/m/Y', strtotime($contrato['data_fim'])); ?>
                                    <?php else: ?>
                                        → Indeterminado
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                    <?php echo $contrato['valor_contrato'] ? 'R$ ' . number_format($contrato['valor_contrato'], 2, ',', '.') : '-'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($contrato['arquivo_contrato']): ?>
                                        <a href="visualizar_contrato.php?id=<?php echo $contrato['id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-900" title="Visualizar contrato">
                                            <svg class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Sem arquivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <a href="contratos_form.php?id=<?php echo $contrato['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3 inline-block" title="Editar">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este contrato?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $contrato['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 inline-block" title="Excluir">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2">Nenhum contrato cadastrado.</p>
                                <a href="contratos_form.php" class="mt-2 inline-block text-blue-600 hover:text-blue-800">
                                    Cadastrar primeiro contrato
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
