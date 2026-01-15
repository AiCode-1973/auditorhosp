<?php
require_once 'db_config.php';
include 'includes/header.php';

// Filtros
$filtro_convenio = isset($_GET['convenio']) ? $_GET['convenio'] : '';
$filtro_setor = isset($_GET['setor']) ? $_GET['setor'] : '';
$filtro_competencia = isset($_GET['competencia']) ? $_GET['competencia'] : '';

// Buscar convênios para o filtro
$stmt_convenios = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
$convenios = $stmt_convenios->fetchAll(PDO::FETCH_ASSOC);

// Construir query com filtros
$sql = "SELECT d.*, c.nome_convenio,
        (SELECT COUNT(*) FROM documentos_pa_ambulatorio_anexos WHERE documento_id = d.id) as total_anexos
        FROM documentos_pa_ambulatorio d
        LEFT JOIN convenios c ON d.convenio_id = c.id
        WHERE 1=1";

$params = [];

if ($filtro_convenio) {
    $sql .= " AND d.convenio_id = ?";
    $params[] = $filtro_convenio;
}

if ($filtro_setor) {
    $sql .= " AND d.setor = ?";
    $params[] = $filtro_setor;
}

if ($filtro_competencia) {
    $sql .= " AND DATE_FORMAT(d.competencia, '%Y-%m') = ?";
    $params[] = $filtro_competencia;
}

$sql .= " ORDER BY d.data_cadastro DESC, d.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Documentos PA/Ambulatório</h1>
        <a href="documentos_pa_ambulatorio_form.php" class="bg-blue-600 text-white font-bold py-2 px-6 rounded hover:bg-blue-700 transition duration-150">
            Novo Documento
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Convênio</label>
                <select name="convenio" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <?php foreach ($convenios as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $filtro_convenio == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nome_convenio']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Setor</label>
                <select name="setor" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="PA" <?php echo $filtro_setor == 'PA' ? 'selected' : ''; ?>>PA</option>
                    <option value="AMB" <?php echo $filtro_setor == 'AMB' ? 'selected' : ''; ?>>AMB</option>
                    <option value="PA/NC" <?php echo $filtro_setor == 'PA/NC' ? 'selected' : ''; ?>>PA/NC</option>
                    <option value="AMB/NC" <?php echo $filtro_setor == 'AMB/NC' ? 'selected' : ''; ?>>AMB/NC</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Competência</label>
                <input type="month" name="competencia" value="<?php echo $filtro_competencia; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition duration-150 flex-1">
                    Filtrar
                </button>
                <a href="documentos_pa_ambulatorio.php" class="bg-gray-500 text-white font-bold py-2 px-4 rounded hover:bg-gray-600 transition duration-150">
                    Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Documentos -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Competência</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Convênio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anexos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($documentos) > 0): ?>
                    <?php foreach ($documentos as $doc): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('d/m/Y', strtotime($doc['data_cadastro'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('m/Y', strtotime($doc['competencia'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($doc['nome_convenio']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php
                                $badge_color = 'bg-gray-100 text-gray-800';
                                if ($doc['setor'] == 'PA') $badge_color = 'bg-green-100 text-green-800';
                                elseif ($doc['setor'] == 'AMB') $badge_color = 'bg-blue-100 text-blue-800';
                                elseif ($doc['setor'] == 'PA/NC' || $doc['setor'] == 'AMB/NC') $badge_color = 'bg-red-100 text-red-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badge_color; ?>">
                                    <?php echo htmlspecialchars($doc['setor']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold">
                                    <?php echo $doc['total_anexos']; ?> arquivo(s)
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-2">
                                    <a href="visualizar_documento_pa_ambulatorio.php?id=<?php echo $doc['id']; ?>" class="text-green-600 hover:text-green-900" title="Visualizar">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="documentos_pa_ambulatorio_form.php?id=<?php echo $doc['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Editar">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <a href="excluir_documento_pa_ambulatorio.php?id=<?php echo $doc['id']; ?>" onclick="return confirm('Deseja excluir este documento e todos os seus anexos?');" class="text-red-600 hover:text-red-900" title="Excluir">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Nenhum documento encontrado.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
