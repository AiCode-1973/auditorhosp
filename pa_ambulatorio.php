<?php
require_once 'db_config.php';
include 'includes/header.php';

// Filtros
$filtro_setor = isset($_GET['filtro_setor']) ? $_GET['filtro_setor'] : '';
$filtro_guia = isset($_GET['filtro_guia']) ? $_GET['filtro_guia'] : '';
$filtro_status = isset($_GET['filtro_status']) ? $_GET['filtro_status'] : '';
$filtro_competencia = isset($_GET['filtro_competencia']) ? $_GET['filtro_competencia'] : '';

// Construção da query
$where_clauses = [];
$params = [];

if ($filtro_setor) {
    $where_clauses[] = "p.setor = ?";
    $params[] = $filtro_setor;
}

if ($filtro_guia) {
    $where_clauses[] = "p.guia_paciente LIKE ?";
    $params[] = "%$filtro_guia%";
}

if ($filtro_status) {
    $where_clauses[] = "p.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_competencia) {
    $where_clauses[] = "p.competencia = ?";
    $params[] = $filtro_competencia . '-01';
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

try {
    $sql = "
        SELECT 
            p.*, 
            c.nome_convenio 
        FROM pa_ambulatorio p
        JOIN convenios c ON p.convenio_id = c.id
        $where_sql
        ORDER BY p.data_recebimento ASC, p.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular totais
    $total_inicial = 0;
    $total_retirado = 0;
    $total_acrescentado = 0;
    $total_final = 0;
    $total_glosado = 0;
    $total_aceito = 0;
    $total_faturado = 0;

    foreach ($atendimentos as $at) {
        $total_inicial += $at['valor_inicial'];
        $total_retirado += $at['valor_retirado'];
        $total_acrescentado += $at['valor_acrescentado'];
        $total_final += $at['valor_total'];
        $total_glosado += $at['valor_glosado'];
        $total_aceito += $at['valor_aceito'];
        $total_faturado += $at['valor_faturado'];
    }
} catch (PDOException $e) {
    $atendimentos = [];
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao carregar dados: " . $e->getMessage() . "</div>";
}
?>

<div class="w-full px-2 mt-4">
    <div class="flex justify-between items-center mb-6 px-2">
        <h2 class="text-2xl font-bold text-gray-800">Gestão PA/Ambulatório</h2>
        <div class="flex gap-2">
            <a href="consolidar_pa_ambulatorio.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                ⚡ Consolidar para Relatório Mensal
            </a>
            <a href="pa_ambulatorio_form.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                + Novo Atendimento
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-4 rounded-lg shadow border border-gray-200 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="filtro_setor" class="block text-sm font-medium text-gray-700 mb-1">Setor</label>
                <select name="filtro_setor" id="filtro_setor" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[120px]">
                    <option value="">Todos</option>
                    <option value="PA" <?php echo $filtro_setor == 'PA' ? 'selected' : ''; ?>>PA</option>
                    <option value="AMB" <?php echo $filtro_setor == 'AMB' ? 'selected' : ''; ?>>AMB</option>
                </select>
            </div>
            <div>
                <label for="filtro_guia" class="block text-sm font-medium text-gray-700 mb-1">Guia</label>
                <input type="text" name="filtro_guia" id="filtro_guia" value="<?php echo htmlspecialchars($filtro_guia); ?>" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nº da guia">
            </div>
            <div>
                <label for="filtro_competencia" class="block text-sm font-medium text-gray-700 mb-1">Competência</label>
                <input type="month" name="filtro_competencia" id="filtro_competencia" value="<?php echo htmlspecialchars($filtro_competencia); ?>" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="filtro_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="filtro_status" id="filtro_status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[150px]">
                    <option value="">Todos</option>
                    <option value="Em Aberto" <?php echo $filtro_status == 'Em Aberto' ? 'selected' : ''; ?>>Em Aberto</option>
                    <option value="Auditado" <?php echo $filtro_status == 'Auditado' ? 'selected' : ''; ?>>Auditado</option>
                    <option value="Fechado" <?php echo $filtro_status == 'Fechado' ? 'selected' : ''; ?>>Fechado</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition text-sm font-medium">
                Filtrar
            </button>
            <?php if ($filtro_setor || $filtro_guia || $filtro_status || $filtro_competencia): ?>
                <a href="pa_ambulatorio.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition text-sm font-medium">
                    Limpar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela -->
    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-1 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comp.</th>
                    <th class="px-1 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receb.</th>
                    <th class="px-1 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setor</th>
                    <th class="px-1 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Convênio</th>
                    <th class="px-1 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guia</th>
                    <th class="px-1 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ent./Saída</th>
                    <th class="px-1 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">V. Inicial</th>
                    <th class="px-1 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">V. Ret.</th>
                    <th class="px-1 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">V. Acres.</th>
                    <th class="px-1 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">V. Final</th>
                    <th class="px-1 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">V. Glosa</th>
                    <th class="px-1 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">V. Aceito</th>
                    <th class="px-1 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">V. Fat.</th>
                    <th class="px-1 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">NF</th>
                    <th class="px-1 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-1 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($atendimentos) > 0): ?>
                    <?php foreach ($atendimentos as $at): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-500">
                                <?php echo $at['competencia'] ? date('m/Y', strtotime($at['competencia'])) : '-'; ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-500">
                                <?php echo $at['data_recebimento'] ? date('d/m/Y', strtotime($at['data_recebimento'])) : '-'; ?>
                            </td>
                            <td class="px-1 py-2 text-xs font-bold text-gray-900">
                                <span class="px-2 py-1 rounded <?php echo $at['setor'] == 'PA' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                    <?php echo htmlspecialchars($at['setor']); ?>
                                </span>
                            </td>
                            <td class="px-1 py-2 text-xs text-gray-500 max-w-[100px] break-words"><?php echo htmlspecialchars($at['nome_convenio']); ?></td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-500"><?php echo htmlspecialchars($at['guia_paciente']); ?></td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-500">
                                <div>E: <?php echo $at['data_entrada'] ? date('d/m/Y', strtotime($at['data_entrada'])) : '-'; ?></div>
                                <div>S: <?php echo $at['data_saida'] ? date('d/m/Y', strtotime($at['data_saida'])) : '-'; ?></div>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-900 text-right">
                                <?php echo number_format($at['valor_inicial'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-red-600 text-right">
                                <?php echo number_format($at['valor_retirado'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-green-600 text-right">
                                <?php echo number_format($at['valor_acrescentado'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-900 text-right font-bold">
                                <?php echo number_format($at['valor_total'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-500 text-right">
                                <?php echo number_format($at['valor_glosado'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-500 text-right">
                                <?php echo number_format($at['valor_aceito'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-xs text-gray-500 text-right">
                                <?php echo number_format($at['valor_faturado'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-center text-xs text-gray-500">
                                <?php echo htmlspecialchars($at['falta_nf']); ?>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-center">
                                <span class="px-1 inline-flex text-[10px] leading-4 font-semibold rounded-full 
                                    <?php 
                                        if($at['status'] == 'Em Aberto') echo 'bg-yellow-100 text-yellow-800';
                                        elseif($at['status'] == 'Auditado') echo 'bg-blue-100 text-blue-800';
                                        elseif($at['status'] == 'Fechado') echo 'bg-green-100 text-green-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo htmlspecialchars($at['status']); ?>
                                </span>
                            </td>
                            <td class="px-1 py-2 whitespace-nowrap text-center text-xs font-medium">
                                <div class="flex justify-center items-center">
                                    <button onclick='verObservacao(<?php echo json_encode($at['observacoes']); ?>)' class="<?php echo !empty($at['observacoes']) ? 'text-orange-500 hover:text-orange-700' : 'text-gray-400 hover:text-gray-600'; ?> mr-1" title="Ver Observações">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <a href="pa_ambulatorio_form.php?id=<?php echo $at['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <a href="javascript:void(0);" onclick="if(confirm('Tem certeza que deseja excluir este atendimento?')) window.location.href='excluir_pa_ambulatorio.php?id=<?php echo $at['id']; ?>';" class="text-red-600 hover:text-red-900" title="Excluir">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="16" class="px-6 py-4 text-center text-gray-500">Nenhum atendimento encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="bg-gray-100 font-bold border-t-2 border-gray-300">
                <tr>
                    <td colspan="6" class="px-1 py-2 text-right text-xs text-gray-700 uppercase">Totais:</td>
                    <td class="px-1 py-2 text-right text-xs text-gray-900"><?php echo number_format($total_inicial, 2, ',', '.'); ?></td>
                    <td class="px-1 py-2 text-right text-xs text-red-600"><?php echo number_format($total_retirado, 2, ',', '.'); ?></td>
                    <td class="px-1 py-2 text-right text-xs text-green-600"><?php echo number_format($total_acrescentado, 2, ',', '.'); ?></td>
                    <td class="px-1 py-2 text-right text-xs text-gray-900"><?php echo number_format($total_final, 2, ',', '.'); ?></td>
                    <td class="px-1 py-2 text-right text-xs text-gray-500"><?php echo number_format($total_glosado, 2, ',', '.'); ?></td>
                    <td class="px-1 py-2 text-right text-xs text-gray-500"><?php echo number_format($total_aceito, 2, ',', '.'); ?></td>
                    <td class="px-1 py-2 text-right text-xs text-gray-500"><?php echo number_format($total_faturado, 2, ',', '.'); ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Modal de Observações -->
<div id="modalObservacao" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50" onclick="fecharModal(event)">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" onclick="event.stopPropagation()">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Observações</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 text-left whitespace-pre-wrap" id="textoObservacao"></p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="ok-btn" onclick="fecharModal()" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function verObservacao(texto) {
        const modal = document.getElementById('modalObservacao');
        const textoElem = document.getElementById('textoObservacao');
        
        textoElem.textContent = texto || 'Nenhuma observação registrada.';
        modal.classList.remove('hidden');
    }

    function fecharModal(event) {
        if (event && event.target.id !== 'modalObservacao') return;
        
        const modal = document.getElementById('modalObservacao');
        modal.classList.add('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>
