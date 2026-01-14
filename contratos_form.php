<?php
require_once 'db_config.php';
include 'includes/header.php';

$mensagem = '';
$tipo_msg = '';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Variáveis do formulário
$convenio_id = '';
$numero_contrato = '';
$data_inicio = '';
$data_fim = '';
$valor_contrato = '';
$arquivo_atual = '';
$observacoes = '';
$ativo = 1;
$titulo = 'Novo Contrato';

// Carregar dados se for edição
if ($id) {
    $titulo = 'Editar Contrato';
    try {
        $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id = ?");
        $stmt->execute([$id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dados) {
            $convenio_id = $dados['convenio_id'];
            $numero_contrato = $dados['numero_contrato'];
            $data_inicio = $dados['data_inicio'];
            $data_fim = $dados['data_fim'];
            $valor_contrato = $dados['valor_contrato'] ? number_format($dados['valor_contrato'], 2, ',', '.') : '';
            $arquivo_atual = $dados['arquivo_contrato'];
            $observacoes = $dados['observacoes'];
            $ativo = $dados['ativo'];
        } else {
            $mensagem = "Contrato não encontrado.";
            $tipo_msg = 'error';
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao carregar dados: " . $e->getMessage();
        $tipo_msg = 'error';
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
    $convenio_id = $_POST['convenio_id'];
    $numero_contrato = trim($_POST['numero_contrato']);
    $data_inicio = $_POST['data_inicio'];
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
    $valor_contrato = !empty($_POST['valor_contrato']) ? str_replace(['.', ','], ['', '.'], $_POST['valor_contrato']) : null;
    $observacoes = trim($_POST['observacoes']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $remover_arquivo = isset($_POST['remover_arquivo']) ? true : false;
    
    if (empty($convenio_id) || empty($numero_contrato) || empty($data_inicio)) {
        $mensagem = "Preencha os campos obrigatórios: Convênio, Número do Contrato e Data de Início.";
        $tipo_msg = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Processar upload de arquivo
            $nome_arquivo = $arquivo_atual;
            
            if (isset($_FILES['arquivo_contrato']) && $_FILES['arquivo_contrato']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['arquivo_contrato'];
                $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $extensoes_permitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                
                if (!in_array($extensao, $extensoes_permitidas)) {
                    throw new Exception("Tipo de arquivo não permitido. Use: PDF, DOC, DOCX, JPG, PNG");
                }
                
                if ($arquivo['size'] > 30 * 1024 * 1024) { // 30MB
                    throw new Exception("Arquivo muito grande. Máximo: 30MB");
                }
                
                // Gerar nome único
                $nome_arquivo = 'contrato_' . time() . '_' . uniqid() . '.' . $extensao;
                $destino = __DIR__ . '/uploads/contratos/' . $nome_arquivo;
                
                // Excluir arquivo antigo se existir
                if ($arquivo_atual && file_exists(__DIR__ . '/uploads/contratos/' . $arquivo_atual)) {
                    unlink(__DIR__ . '/uploads/contratos/' . $arquivo_atual);
                }
                
                if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                    throw new Exception("Erro ao fazer upload do arquivo");
                }
            } elseif ($remover_arquivo && $arquivo_atual) {
                // Remover arquivo existente
                if (file_exists(__DIR__ . '/uploads/contratos/' . $arquivo_atual)) {
                    unlink(__DIR__ . '/uploads/contratos/' . $arquivo_atual);
                }
                $nome_arquivo = null;
            }
            
            if ($id) {
                // Atualizar
                $sql = "UPDATE contratos SET 
                    convenio_id = ?,
                    numero_contrato = ?,
                    data_inicio = ?,
                    data_fim = ?,
                    valor_contrato = ?,
                    arquivo_contrato = ?,
                    observacoes = ?,
                    ativo = ?
                    WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $convenio_id,
                    $numero_contrato,
                    $data_inicio,
                    $data_fim,
                    $valor_contrato,
                    $nome_arquivo,
                    $observacoes,
                    $ativo,
                    $id
                ]);
                
                $mensagem = "Contrato atualizado com sucesso!";
            } else {
                // Inserir
                $sql = "INSERT INTO contratos (convenio_id, numero_contrato, data_inicio, data_fim, valor_contrato, arquivo_contrato, observacoes, ativo, usuario_criacao) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $convenio_id,
                    $numero_contrato,
                    $data_inicio,
                    $data_fim,
                    $valor_contrato,
                    $nome_arquivo,
                    $observacoes,
                    $ativo,
                    $_SESSION['usuario_id']
                ]);
                
                $mensagem = "Contrato cadastrado com sucesso!";
            }
            
            $pdo->commit();
            $tipo_msg = 'success';
            
            // Redirecionar após sucesso
            echo "<script>setTimeout(function(){ window.location.href = 'contratos.php'; }, 2000);</script>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "Erro ao salvar: " . $e->getMessage();
            $tipo_msg = 'error';
        }
    }
}
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800"><?php echo $titulo; ?></h2>
            <a href="contratos.php" class="text-gray-600 hover:text-gray-800">
                &larr; Voltar
            </a>
        </div>

        <?php if ($mensagem): ?>
            <div class="<?php echo $tipo_msg === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> border px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <!-- Convênio -->
            <div>
                <label for="convenio_id" class="block text-gray-700 text-sm font-bold mb-2">
                    Convênio <span class="text-red-500">*</span>
                </label>
                <select name="convenio_id" id="convenio_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Selecione um convênio</option>
                    <?php foreach ($convenios as $conv): ?>
                        <option value="<?php echo $conv['id']; ?>" <?php echo $convenio_id == $conv['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($conv['nome_convenio']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Número do Contrato -->
            <div>
                <label for="numero_contrato" class="block text-gray-700 text-sm font-bold mb-2">
                    Número do Contrato <span class="text-red-500">*</span>
                </label>
                <input type="text" name="numero_contrato" id="numero_contrato" value="<?php echo htmlspecialchars($numero_contrato); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Ex: CT-2024-001">
            </div>

            <!-- Datas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="data_inicio" class="block text-gray-700 text-sm font-bold mb-2">
                        Data de Início <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="data_inicio" id="data_inicio" value="<?php echo $data_inicio; ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div>
                    <label for="data_fim" class="block text-gray-700 text-sm font-bold mb-2">
                        Data de Fim (Opcional)
                    </label>
                    <input type="date" name="data_fim" id="data_fim" value="<?php echo $data_fim; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>

            <!-- Valor do Contrato -->
            <div>
                <label for="valor_contrato" class="block text-gray-700 text-sm font-bold mb-2">
                    Valor do Contrato (Opcional)
                </label>
                <input type="text" name="valor_contrato" id="valor_contrato" value="<?php echo $valor_contrato; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="0,00">
            </div>

            <!-- Upload de Arquivo -->
            <div>
                <label for="arquivo_contrato" class="block text-gray-700 text-sm font-bold mb-2">
                    Arquivo do Contrato
                </label>
                <?php if ($arquivo_atual): ?>
                    <div class="mb-2 p-3 bg-gray-50 border border-gray-200 rounded flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm text-gray-700">Arquivo atual: <?php echo htmlspecialchars($arquivo_atual); ?></span>
                        </div>
                        <div class="flex gap-2">
                            <a href="visualizar_contrato.php?id=<?php echo $id; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                Visualizar
                            </a>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="remover_arquivo" class="mr-1">
                                <span class="text-sm text-red-600">Remover</span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="arquivo_contrato" id="arquivo_contrato" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <p class="text-xs text-gray-500 mt-1">Formatos aceitos: PDF, DOC, DOCX, JPG, PNG (máx. 30MB)</p>
            </div>

            <!-- Observações -->
            <div>
                <label for="observacoes" class="block text-gray-700 text-sm font-bold mb-2">
                    Observações
                </label>
                <textarea name="observacoes" id="observacoes" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Observações sobre o contrato..."><?php echo htmlspecialchars($observacoes); ?></textarea>
            </div>

            <!-- Status Ativo -->
            <div>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="ativo" <?php echo $ativo ? 'checked' : ''; ?> class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="ml-2 text-gray-700 text-sm font-bold">Contrato Ativo</span>
                </label>
            </div>

            <!-- Botões -->
            <div class="flex items-center justify-between pt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    <?php echo $id ? 'Atualizar Contrato' : 'Cadastrar Contrato'; ?>
                </button>
                <a href="contratos.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Máscara para valor monetário
document.getElementById('valor_contrato').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = (value / 100).toFixed(2) + '';
    value = value.replace(".", ",");
    value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    e.target.value = value;
});
</script>

<?php include 'includes/footer.php'; ?>
