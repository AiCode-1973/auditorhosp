<?php
require_once 'db_config.php';
include 'includes/header.php';

$mensagem = '';

// Lógica de Exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    try {
        $pdo->beginTransaction();
        
        // Excluir dependências primeiro
        $pdo->prepare("DELETE FROM recursos WHERE fatura_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM glosas WHERE fatura_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM faturas WHERE id = ?")->execute([$id]);
        
        $pdo->commit();
        $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Lançamento excluído com sucesso!</div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao excluir: " . $e->getMessage() . "</div>";
    }
}

// Filtros
$filtro_convenio = isset($_GET['convenio']) ? $_GET['convenio'] : '';
$filtro_competencia = isset($_GET['competencia']) ? $_GET['competencia'] : '';

// Paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Buscar convênios para o filtro
try {
    $stmt_conv = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
    $convenios = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenios = [];
}

// Listagem com filtros
try {
    // 1. Contar total de registros para paginação
    $sql_count = "
        SELECT COUNT(*) as total
        FROM faturas f
        JOIN convenios c ON f.convenio_id = c.id
        WHERE 1=1
    ";

    $params = [];

    if ($filtro_convenio) {
        $sql_count .= " AND f.convenio_id = ?";
        $params[] = $filtro_convenio;
    }

    if ($filtro_competencia) {
        $sql_count .= " AND DATE_FORMAT(f.data_competencia, '%Y-%m') = ?";
        $params[] = $filtro_competencia;
    }

    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $itens_por_pagina);

    // 2. Buscar registros da página atual
    $sql = "
        SELECT 
            f.id, 
            f.data_competencia, 
            c.nome_convenio, 
            f.valor_total,
            (SELECT SUM(valor_glosa) FROM glosas WHERE fatura_id = f.id) as valor_glosa,
            (SELECT SUM(valor_recursado) FROM recursos WHERE fatura_id = f.id) as valor_recursado
        FROM faturas f
        JOIN convenios c ON f.convenio_id = c.id
        WHERE 1=1
    ";

    // Reutilizar params e where clauses seria ideal, mas como são poucos, vou repetir a lógica de concatenação
    // para garantir a ordem correta dos parâmetros no LIMIT/OFFSET se necessário (embora PDO trate isso bem)
    
    if ($filtro_convenio) {
        $sql .= " AND f.convenio_id = ?";
    }

    if ($filtro_competencia) {
        $sql .= " AND DATE_FORMAT(f.data_competencia, '%Y-%m') = ?";
    }

    $sql .= " ORDER BY f.data_competencia DESC, c.nome_convenio LIMIT $itens_por_pagina OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $faturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao listar lançamentos: " . $e->getMessage() . "</div>";
    $faturas = [];
    $total_paginas = 0;
}
?>

<div class="mt-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Gerenciar Lançamentos</h2>
        <a href="registrar_auditoria.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
            + Novo Lançamento
        </a>
    </div>

    <?php echo $mensagem; ?>

    <!-- Filtros -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label for="convenio" class="block text-sm font-medium text-gray-700 mb-1">Convênio</label>
                <select name="convenio" id="convenio" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <?php foreach ($convenios as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $filtro_convenio == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nome_convenio']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="competencia" class="block text-sm font-medium text-gray-700 mb-1">Competência</label>
                <input type="month" name="competencia" id="competencia" value="<?php echo $filtro_competencia; ?>" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">Filtrar</button>
                <?php if ($filtro_convenio || $filtro_competencia): ?>
                    <a href="faturas.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition flex items-center">Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Competência</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Convênio</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Faturamento</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Glosa</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Recurso</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($faturas) > 0): ?>
                        <?php foreach ($faturas as $fatura): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('m/Y', strtotime($fatura['data_competencia'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($fatura['nome_convenio']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    R$ <?php echo number_format($fatura['valor_total'], 2, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    <?php echo $fatura['valor_glosa'] ? 'R$ ' . number_format($fatura['valor_glosa'], 2, ',', '.') : '-'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    <?php echo $fatura['valor_recursado'] ? 'R$ ' . number_format($fatura['valor_recursado'], 2, ',', '.') : '-'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="registrar_auditoria.php?id=<?php echo $fatura['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3 inline-block" title="Editar">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este lançamento?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $fatura['id']; ?>">
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
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                Nenhum lançamento encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginação -->
    <?php if ($total_paginas > 1): ?>
        <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 mt-4 rounded-lg shadow">
            <div class="flex flex-1 justify-between sm:hidden">
                <?php if ($pagina_atual > 1): ?>
                    <a href="?pagina=<?php echo $pagina_atual - 1; ?>&convenio=<?php echo $filtro_convenio; ?>&competencia=<?php echo $filtro_competencia; ?>" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Anterior</a>
                <?php else: ?>
                    <span class="relative inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">Anterior</span>
                <?php endif; ?>

                <?php if ($pagina_atual < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina_atual + 1; ?>&convenio=<?php echo $filtro_convenio; ?>&competencia=<?php echo $filtro_competencia; ?>" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Próximo</a>
                <?php else: ?>
                    <span class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">Próximo</span>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Mostrando <span class="font-medium"><?php echo ($offset + 1); ?></span> a <span class="font-medium"><?php echo min($offset + $itens_por_pagina, $total_registros); ?></span> de <span class="font-medium"><?php echo $total_registros; ?></span> resultados
                    </p>
                </div>
                <div>
                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        <!-- Botão Anterior -->
                        <?php if ($pagina_atual > 1): ?>
                            <a href="?pagina=<?php echo $pagina_atual - 1; ?>&convenio=<?php echo $filtro_convenio; ?>&competencia=<?php echo $filtro_competencia; ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                <span class="sr-only">Anterior</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        <?php else: ?>
                            <span class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-300 cursor-not-allowed">
                                <span class="sr-only">Anterior</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        <?php endif; ?>

                        <!-- Números das Páginas -->
                        <?php
                        $range = 2; // Quantas páginas mostrar antes e depois da atual
                        for ($i = 1; $i <= $total_paginas; $i++):
                            if ($i == 1 || $i == $total_paginas || ($i >= $pagina_atual - $range && $i <= $pagina_atual + $range)):
                                if ($i == $pagina_atual):
                        ?>
                                    <span aria-current="page" class="relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?pagina=<?php echo $i; ?>&convenio=<?php echo $filtro_convenio; ?>&competencia=<?php echo $filtro_competencia; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php elseif ($i == $pagina_atual - $range - 1 || $i == $pagina_atual + $range + 1): ?>
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Botão Próximo -->
                        <?php if ($pagina_atual < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina_atual + 1; ?>&convenio=<?php echo $filtro_convenio; ?>&competencia=<?php echo $filtro_competencia; ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                <span class="sr-only">Próximo</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        <?php else: ?>
                            <span class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-300 cursor-not-allowed">
                                <span class="sr-only">Próximo</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
