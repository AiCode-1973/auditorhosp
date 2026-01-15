<?php
require_once 'db_config.php';
include 'includes/header.php';

// Filtros
$filtro_convenio = isset($_GET['filtro_convenio']) ? $_GET['filtro_convenio'] : '';
$filtro_competencia = isset($_GET['filtro_competencia']) ? $_GET['filtro_competencia'] : '';

// Construir query
$where_clauses = [];
$params = [];

if ($filtro_convenio) {
    $where_clauses[] = "d.convenio_id = ?";
    $params[] = $filtro_convenio;
}

if ($filtro_competencia) {
    $where_clauses[] = "d.competencia = ?";
    $params[] = $filtro_competencia . '-01';
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Buscar documentos
try {
    $sql = "
        SELECT 
            d.*,
            c.nome_convenio,
            COUNT(a.id) as total_anexos
        FROM documentos_internacao d
        JOIN convenios c ON d.convenio_id = c.id
        LEFT JOIN documentos_internacao_anexos a ON d.id = a.documento_id
        $where_sql
        GROUP BY d.id
        ORDER BY d.competencia DESC, d.data_cadastro DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar convênios para o filtro
    $stmt_conv = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
    $convenios = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $documentos = [];
    $convenios = [];
    $erro = "Erro ao carregar documentos: " . $e->getMessage();
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Documentos de Internação</h1>
        <a href="documentos_internacao_form.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-150 flex items-center gap-2">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Novo Documento
        </a>
    </div>

    <?php if (isset($erro)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Convênio</label>
                <select name="filtro_convenio" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <?php foreach ($convenios as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $filtro_convenio == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nome_convenio']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Competência</label>
                <input type="month" name="filtro_competencia" value="<?php echo $filtro_competencia; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition duration-150">
                    Filtrar
                </button>
                <a href="documentos_internacao.php" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition duration-150">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Competência</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Convênio</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Anexos</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($documentos)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Nenhum documento encontrado
                        </td>
                    </tr>
                <?php else: ?>
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
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo $doc['total_anexos']; ?> arquivo(s)
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <a href="visualizar_documento_internacao.php?id=<?php echo $doc['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-4" title="Visualizar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="documentos_internacao_form.php?id=<?php echo $doc['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <a href="javascript:void(0);" onclick="if(confirm('Tem certeza que deseja excluir este documento e todos os seus anexos?')) window.location.href='excluir_documento_internacao.php?id=<?php echo $doc['id']; ?>';" class="text-red-600 hover:text-red-900" title="Excluir">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
