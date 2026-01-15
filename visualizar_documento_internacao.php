<?php
require_once 'db_config.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header("Location: Documento de Internaçãos_internacao.php");
    exit;
}

try {
    // Buscar dados do Documento de Internação
    $stmt = $pdo->prepare("
        SELECT d.*, c.nome_convenio 
        FROM Documento de Internaçãos_internacao d
        JOIN convenios c ON d.convenio_id = c.id
        WHERE d.id = ?
    ");
    $stmt->execute([$id]);
    $Documento de Internação = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$Documento de Internação) {
        header("Location: Documento de Internaçãos_internacao.php");
        exit;
    }
    
    // Buscar anexos
    $stmt_anexos = $pdo->prepare("SELECT * FROM Documento de Internaçãos_internacao_anexos WHERE Documento de Internação_id = ? ORDER BY created_at DESC");
    $stmt_anexos->execute([$id]);
    $anexos = $stmt_anexos->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erro = "Erro ao carregar Documento de Internação: " . $e->getMessage();
}
?>

<div class="container mx-auto px-4 py-6 max-w-5xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Visualizar Documento de Internação</h1>
        <a href="Documento de Internaçãos_internacao.php" class="text-blue-600 hover:text-blue-800 font-medium">Voltar para Lista</a>
    </div>

    <?php if (isset($erro)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erro; ?>
        </div>
    <?php else: ?>
        <!-- Dados do Documento de Internação -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">InformaÃ§Ãµes do Documento de Internação</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Data</label>
                    <p class="text-base text-gray-900"><?php echo date('d/m/Y', strtotime($Documento de Internação['data_cadastro'])); ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">CompetÃªncia</label>
                    <p class="text-base text-gray-900"><?php echo date('m/Y', strtotime($Documento de Internação['competencia'])); ?></p>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-500 mb-1">ConvÃªnio</label>
                    <p class="text-base text-gray-900 font-semibold"><?php echo htmlspecialchars($Documento de Internação['nome_convenio']); ?></p>
                </div>
                
                <?php if (!empty($Documento de Internação['observacoes'])): ?>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-500 mb-1">ObservaÃ§Ãµes</label>
                        <p class="text-base text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($Documento de Internação['observacoes']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Anexos -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-xl font-semibold text-gray-800">Anexos (<?php echo count($anexos); ?>)</h2>
                <a href="Documento de Internaçãos_internacao_form.php?id=<?php echo $id; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Adicionar Anexo
                </a>
            </div>
            
            <?php if (empty($anexos)): ?>
                <div class="text-center py-8 text-gray-500">
                    <svg class="h-16 w-16 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>Nenhum anexo cadastrado</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($anexos as $anexo): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <?php 
                                    $extensao = strtolower(pathinfo($anexo['nome_arquivo'], PATHINFO_EXTENSION));
                                    $icone_cor = 'text-gray-400';
                                    if (in_array($extensao, ['pdf'])) $icone_cor = 'text-red-500';
                                    elseif (in_array($extensao, ['doc', 'docx'])) $icone_cor = 'text-blue-500';
                                    elseif (in_array($extensao, ['xls', 'xlsx'])) $icone_cor = 'text-green-500';
                                    elseif (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif'])) $icone_cor = 'text-purple-500';
                                    ?>
                                    <svg class="h-10 w-10 <?php echo $icone_cor; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($anexo['nome_arquivo']); ?>">
                                        <?php echo htmlspecialchars($anexo['nome_arquivo']); ?>
                                    </p>
                                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                        <span><?php echo number_format($anexo['tamanho_arquivo'] / 1024, 2); ?> KB</span>
                                        <span>â€¢</span>
                                        <span><?php echo strtoupper($extensao); ?></span>
                                        <span>â€¢</span>
                                        <span><?php echo date('d/m/Y H:i', strtotime($anexo['created_at'])); ?></span>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <a href="<?php echo $anexo['caminho_arquivo']; ?>" target="_blank" class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded hover:bg-blue-200 transition-colors">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Visualizar
                                        </a>
                                        <a href="<?php echo $anexo['caminho_arquivo']; ?>" download class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded hover:bg-green-200 transition-colors">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Baixar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- BotÃµes de AÃ§Ã£o -->
        <div class="flex justify-end gap-3 mt-6">
            <a href="Documento de Internaçãos_internacao.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-150">
                Voltar
            </a>
            <a href="Documento de Internaçãos_internacao_form.php?id=<?php echo $id; ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
                Editar Documento de Internação
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
