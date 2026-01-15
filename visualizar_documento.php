<?php
require_once 'db_config.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header('Location: documentos.php');
    exit;
}

try {
    // Buscar documento
    $stmt = $pdo->prepare("SELECT d.*, c.nome_convenio 
                           FROM documentos_glosa d
                           LEFT JOIN convenios c ON d.convenio_id = c.id
                           WHERE d.id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doc) {
        header('Location: documentos.php');
        exit;
    }
    
    // Buscar anexos
    $stmt_anexos = $pdo->prepare("SELECT * FROM documentos_glosa_anexos WHERE documento_id = ? ORDER BY created_at DESC");
    $stmt_anexos->execute([$id]);
    $anexos = $stmt_anexos->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

// Função para obter ícone e cor baseado na extensão do arquivo
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $icons = [
        'pdf' => ['color' => 'text-red-600', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
        'doc' => ['color' => 'text-blue-600', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'docx' => ['color' => 'text-blue-600', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'xls' => ['color' => 'text-green-600', 'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'],
        'xlsx' => ['color' => 'text-green-600', 'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'],
        'jpg' => ['color' => 'text-purple-600', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'jpeg' => ['color' => 'text-purple-600', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'png' => ['color' => 'text-purple-600', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
    ];
    
    return $icons[$ext] ?? ['color' => 'text-gray-600', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'];
}
?>

<div class="container mx-auto px-4 py-6 max-w-6xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Visualizar Documento</h1>
        <div class="flex gap-2">
            <a href="documentos_form.php?id=<?php echo $id; ?>" class="bg-blue-600 text-white font-bold py-2 px-6 rounded hover:bg-blue-700 transition duration-150">
                Editar
            </a>
            <a href="documentos.php" class="bg-gray-500 text-white font-bold py-2 px-6 rounded hover:bg-gray-600 transition duration-150">
                Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Informações do Documento -->
        <div class="md:col-span-1">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Data</label>
                        <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($doc['data_cadastro'])); ?></p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Competência</label>
                        <p class="text-gray-900"><?php echo date('m/Y', strtotime($doc['competencia'])); ?></p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Convênio</label>
                        <p class="text-gray-900"><?php echo htmlspecialchars($doc['nome_convenio']); ?></p>
                    </div>
                    
                    <?php if ($doc['observacoes']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Observações</label>
                        <p class="text-gray-900 text-sm"><?php echo nl2br(htmlspecialchars($doc['observacoes'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Total de Anexos</label>
                        <p class="text-gray-900"><?php echo count($anexos); ?> arquivo(s)</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Criado em</label>
                        <p class="text-gray-900 text-sm"><?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Anexos -->
        <div class="md:col-span-2">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Anexos</h2>
                
                <?php if (count($anexos) > 0): ?>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($anexos as $anexo): 
                            $fileInfo = getFileIcon($anexo['nome_arquivo']);
                        ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:shadow-md transition duration-150">
                                <div class="flex items-center gap-4 flex-1">
                                    <svg class="h-8 w-8 <?php echo $fileInfo['color']; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $fileInfo['icon']; ?>" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($anexo['nome_arquivo']); ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo number_format($anexo['tamanho_arquivo'] / 1024, 2); ?> KB • 
                                            <?php echo date('d/m/Y H:i', strtotime($anexo['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="<?php echo $anexo['caminho_arquivo']; ?>" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-150 text-sm font-medium">
                                        Visualizar
                                    </a>
                                    <a href="<?php echo $anexo['caminho_arquivo']; ?>" download class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-150 text-sm font-medium">
                                        Download
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-gray-500">Nenhum anexo encontrado</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
