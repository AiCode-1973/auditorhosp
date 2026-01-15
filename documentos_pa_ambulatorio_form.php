<?php
require_once 'db_config.php';
include 'includes/header.php';

$mensagem = '';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Variáveis
$data_cadastro = date('Y-m-d');
$competencia = '';
$convenio_id = '';
$setor = '';
$observacoes = '';
$anexos_existentes = [];

// Carregar dados se for edição
if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM documentos_pa_ambulatorio WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doc) {
            $data_cadastro = $doc['data_cadastro'];
            $competencia = date('Y-m', strtotime($doc['competencia']));
            $convenio_id = $doc['convenio_id'];
            $setor = $doc['setor'];
            $observacoes = $doc['observacoes'];
            
            // Buscar anexos existentes
            $stmt_anexos = $pdo->prepare("SELECT * FROM documentos_pa_ambulatorio_anexos WHERE documento_id = ? ORDER BY created_at DESC");
            $stmt_anexos->execute([$id]);
            $anexos_existentes = $stmt_anexos->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>Erro: " . $e->getMessage() . "</div>";
    }
}

// Buscar convênios
try {
    $stmt = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
    $convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenios = [];
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_cadastro = $_POST['data_cadastro'];
    $competencia = $_POST['competencia'] . '-01';
    $convenio_id = $_POST['convenio_id'];
    $setor = $_POST['setor'];
    $observacoes = $_POST['observacoes'];
    
    if (empty($data_cadastro) || empty($convenio_id) || empty($setor)) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>Preencha os campos obrigatórios.</div>";
    } else {
        try {
            if ($id) {
                // Atualizar documento
                $sql = "UPDATE documentos_pa_ambulatorio SET data_cadastro=?, competencia=?, convenio_id=?, setor=?, observacoes=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data_cadastro, $competencia, $convenio_id, $setor, $observacoes, $id]);
                $documento_id = $id;
            } else {
                // Inserir novo documento
                $sql = "INSERT INTO documentos_pa_ambulatorio (data_cadastro, competencia, convenio_id, setor, observacoes) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data_cadastro, $competencia, $convenio_id, $setor, $observacoes]);
                $documento_id = $pdo->lastInsertId();
            }
            
            // Processar uploads de arquivos
            if (!empty($_FILES['anexos']['name'][0])) {
                $upload_dir = 'uploads/documentos_pa_ambulatorio/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $total_files = count($_FILES['anexos']['name']);
                for ($i = 0; $i < $total_files; $i++) {
                    if ($_FILES['anexos']['error'][$i] == 0) {
                        $nome_original = $_FILES['anexos']['name'][$i];
                        $extensao = pathinfo($nome_original, PATHINFO_EXTENSION);
                        $nome_arquivo = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $nome_original);
                        $caminho_destino = $upload_dir . $nome_arquivo;
                        
                        if (move_uploaded_file($_FILES['anexos']['tmp_name'][$i], $caminho_destino)) {
                            $tamanho = $_FILES['anexos']['size'][$i];
                            $tipo = $_FILES['anexos']['type'][$i];
                            
                            $sql_anexo = "INSERT INTO documentos_pa_ambulatorio_anexos (documento_id, nome_arquivo, caminho_arquivo, tamanho_arquivo, tipo_arquivo) VALUES (?, ?, ?, ?, ?)";
                            $stmt_anexo = $pdo->prepare($sql_anexo);
                            $stmt_anexo->execute([$documento_id, $nome_original, $caminho_destino, $tamanho, $tipo]);
                        }
                    }
                }
            }
            
            $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>Documento salvo com sucesso!</div>";
            
            if (!$id) {
                echo "<script>setTimeout(function(){ window.location.href = 'documentos_pa_ambulatorio.php'; }, 1500);</script>";
            } else {
                // Recarregar anexos
                $stmt_anexos = $pdo->prepare("SELECT * FROM documentos_pa_ambulatorio_anexos WHERE documento_id = ? ORDER BY created_at DESC");
                $stmt_anexos->execute([$id]);
                $anexos_existentes = $stmt_anexos->fetchAll(PDO::FETCH_ASSOC);
            }
            
        } catch (PDOException $e) {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>Erro: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $id ? 'Editar' : 'Novo'; ?> Documento PA/Ambulatório</h1>
        <a href="documentos_pa_ambulatorio.php" class="text-blue-600 hover:text-blue-800 font-medium">Voltar para Lista</a>
    </div>

    <?php echo $mensagem; ?>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Data -->
                <div>
                    <label for="data_cadastro" class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                    <input type="date" name="data_cadastro" id="data_cadastro" value="<?php echo $data_cadastro; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <!-- Competência -->
                <div>
                    <label for="competencia" class="block text-sm font-medium text-gray-700 mb-1">Competência *</label>
                    <input type="month" name="competencia" id="competencia" value="<?php echo $competencia; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <!-- Convênio -->
                <div>
                    <label for="convenio_id" class="block text-sm font-medium text-gray-700 mb-1">Convênio *</label>
                    <select name="convenio_id" id="convenio_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($convenios as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $convenio_id == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nome_convenio']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Setor -->
                <div>
                    <label for="setor" class="block text-sm font-medium text-gray-700 mb-1">Setor *</label>
                    <select name="setor" id="setor" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Selecione...</option>
                        <option value="PA" <?php echo $setor == 'PA' ? 'selected' : ''; ?>>PA</option>
                        <option value="AMB" <?php echo $setor == 'AMB' ? 'selected' : ''; ?>>AMB</option>
                        <option value="PA/NC" <?php echo $setor == 'PA/NC' ? 'selected' : ''; ?>>PA/NC</option>
                        <option value="AMB/NC" <?php echo $setor == 'AMB/NC' ? 'selected' : ''; ?>>AMB/NC</option>
                    </select>
                </div>

                <!-- Observações -->
                <div class="md:col-span-2">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea name="observacoes" id="observacoes" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($observacoes); ?></textarea>
                </div>

                <!-- Upload de Anexos -->
                <div class="md:col-span-2">
                    <label for="anexos" class="block text-sm font-medium text-gray-700 mb-1">Anexar Arquivos</label>
                    <input type="file" name="anexos[]" id="anexos" multiple class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos</p>
                </div>
            </div>

            <!-- Anexos Existentes -->
            <?php if (!empty($anexos_existentes)): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Anexos Existentes</h3>
                    <div class="grid grid-cols-1 gap-3">
                        <?php foreach ($anexos_existentes as $anexo): ?>
                            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($anexo['nome_arquivo']); ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo number_format($anexo['tamanho_arquivo'] / 1024, 2); ?> KB • 
                                            <?php echo date('d/m/Y H:i', strtotime($anexo['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="<?php echo $anexo['caminho_arquivo']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800" title="Visualizar">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="excluir_anexo_documento_pa_ambulatorio.php?id=<?php echo $anexo['id']; ?>&doc_id=<?php echo $id; ?>" onclick="return confirm('Deseja excluir este anexo?');" class="text-red-600 hover:text-red-800" title="Excluir">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex justify-end gap-4">
                <a href="documentos_pa_ambulatorio.php" class="bg-gray-500 text-white font-bold py-2 px-6 rounded hover:bg-gray-600 transition duration-150">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded hover:bg-blue-700 transition duration-150">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
