<?php
require_once 'db_config.php';
include 'includes/header.php';

$mensagem = '';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Variáveis para preencher o formulário
$convenio_id = '';
$data_competencia = '';
$valor_faturamento = '';
$valor_glosa = '';
$valor_recursado = '';
$valor_aceito = '';
$valor_recebido = '';
$titulo = 'Novo Registro de Auditoria';

// Se for edição, carregar dados
if ($id) {
    $titulo = 'Editar Registro de Auditoria';
    try {
        $sql = "
            SELECT 
                f.convenio_id, 
                f.data_competencia, 
                f.valor_total,
                (SELECT SUM(valor_glosa) FROM glosas WHERE fatura_id = f.id) as valor_glosa,
                (SELECT SUM(valor_recursado) FROM recursos WHERE fatura_id = f.id) as valor_recursado,
                (SELECT SUM(valor_aceito) FROM recursos WHERE fatura_id = f.id) as valor_aceito,
                (SELECT SUM(valor_recebido) FROM recursos WHERE fatura_id = f.id) as valor_recebido
            FROM faturas f
            WHERE f.id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados) {
            $convenio_id = $dados['convenio_id'];
            $data_competencia = date('Y-m', strtotime($dados['data_competencia'])); // Formato para input month
            $valor_faturamento = $dados['valor_total'];
            $valor_glosa = $dados['valor_glosa'];
            $valor_recursado = $dados['valor_recursado'];
            $valor_aceito = $dados['valor_aceito'];
            $valor_recebido = $dados['valor_recebido'];
        } else {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Registro não encontrado.</div>";
        }
    } catch (PDOException $e) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao carregar dados: " . $e->getMessage() . "</div>";
    }
}

// Buscar convênios para o dropdown
try {
    $stmt = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
    $convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenios = [];
    $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao carregar convênios.</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $convenio_id = $_POST['convenio_id'];
    // Adiciona o dia 01 para transformar o formato YYYY-MM em YYYY-MM-DD
    $data_competencia = $_POST['data_competencia'] . '-01';
    $valor_faturamento = str_replace(',', '.', $_POST['valor_faturamento']);
    $valor_glosa = !empty($_POST['valor_glosa']) ? str_replace(',', '.', $_POST['valor_glosa']) : 0;
    $valor_recursado = !empty($_POST['valor_recursado']) ? str_replace(',', '.', $_POST['valor_recursado']) : 0;
    $valor_aceito = !empty($_POST['valor_aceito']) ? str_replace(',', '.', $_POST['valor_aceito']) : 0;
    $valor_recebido = !empty($_POST['valor_recebido']) ? str_replace(',', '.', $_POST['valor_recebido']) : 0;

    if (empty($convenio_id) || empty($data_competencia) || empty($valor_faturamento)) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Preencha os campos obrigatórios (Convênio, Data e Faturamento).</div>";
    } else {
        try {
            $pdo->beginTransaction();

            if ($id) {
                // --- ATUALIZAÇÃO ---
                
                // 1. Atualizar Fatura
                $stmt = $pdo->prepare("UPDATE faturas SET convenio_id = ?, data_competencia = ?, valor_total = ? WHERE id = ?");
                $stmt->execute([$convenio_id, $data_competencia, $valor_faturamento, $id]);

                // 2. Atualizar Glosa
                // Simplificação: Removemos todas as glosas dessa fatura e inserimos novamente se houver valor
                $pdo->prepare("DELETE FROM glosas WHERE fatura_id = ?")->execute([$id]);
                if ($valor_glosa > 0) {
                    $stmt = $pdo->prepare("INSERT INTO glosas (fatura_id, valor_glosa, motivo) VALUES (?, ?, ?)");
                    $stmt->execute([$id, $valor_glosa, 'Registro Manual (Editado)']);
                }

                // 3. Atualizar Recurso
                // Simplificação: Removemos todos os recursos dessa fatura e inserimos novamente se houver valor
                $pdo->prepare("DELETE FROM recursos WHERE fatura_id = ?")->execute([$id]);
                if ($valor_recursado > 0 || $valor_aceito > 0 || $valor_recebido > 0) {
                    $stmt = $pdo->prepare("INSERT INTO recursos (fatura_id, valor_recursado, valor_aceito, valor_recebido, data_recurso) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$id, $valor_recursado, $valor_aceito, $valor_recebido]);
                }

                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Registro atualizado com sucesso!</div>";

            } else {
                // --- INSERÇÃO (Código original) ---

                // 1. Inserir Fatura
                $stmt = $pdo->prepare("INSERT INTO faturas (convenio_id, data_competencia, valor_total) VALUES (?, ?, ?)");
                $stmt->execute([$convenio_id, $data_competencia, $valor_faturamento]);
                $fatura_id = $pdo->lastInsertId();

                // 2. Inserir Glosa (se houver)
                if ($valor_glosa > 0) {
                    $stmt = $pdo->prepare("INSERT INTO glosas (fatura_id, valor_glosa, motivo) VALUES (?, ?, ?)");
                    $stmt->execute([$fatura_id, $valor_glosa, 'Registro Manual']);
                }

                // 3. Inserir Recurso (se houver valor recursado ou aceito)
                if ($valor_recursado > 0 || $valor_aceito > 0 || $valor_recebido > 0) {
                    $stmt = $pdo->prepare("INSERT INTO recursos (fatura_id, valor_recursado, valor_aceito, valor_recebido, data_recurso) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$fatura_id, $valor_recursado, $valor_aceito, $valor_recebido]);
                }

                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Registro adicionado com sucesso!</div>";
            }

            $pdo->commit();
            
            // Se for edição, redirecionar após um tempo ou manter na página
            if ($id) {
                 echo "<script>setTimeout(function(){ window.location.href = 'faturas.php'; }, 1500);</script>";
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao salvar: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800"><?php echo $titulo; ?></h2>
            <a href="faturas.php" class="text-gray-600 hover:text-gray-800">
                &larr; Voltar
            </a>
        </div>
        
        <?php echo $mensagem; ?>

        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <!-- Convênio -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="convenio_id">
                        Convênio *
                    </label>
                    <div class="relative">
                        <select class="block appearance-none w-full bg-white border border-gray-300 text-gray-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:shadow-outline" id="convenio_id" name="convenio_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($convenios as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $convenio_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['nome_convenio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Competência -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="data_competencia">
                        Mês Competência *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="data_competencia" name="data_competencia" type="month" value="<?php echo $data_competencia; ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="valor_faturamento">
                    Valor Faturamento (R$) *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="valor_faturamento" name="valor_faturamento" type="number" step="0.01" min="0" placeholder="0.00" value="<?php echo $valor_faturamento; ?>" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="valor_glosa">
                        Valor Glosado (R$)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="valor_glosa" name="valor_glosa" type="number" step="0.01" min="0" placeholder="0.00" value="<?php echo $valor_glosa; ?>">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="valor_recursado">
                        Valor Recursado (R$)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="valor_recursado" name="valor_recursado" type="number" step="0.01" min="0" placeholder="0.00" value="<?php echo $valor_recursado; ?>">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="valor_aceito">
                        Valor Aceito (R$)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="valor_aceito" name="valor_aceito" type="number" step="0.01" min="0" placeholder="0.00" value="<?php echo $valor_aceito; ?>">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="valor_recebido">
                        Valor Recebido (R$)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="valor_recebido" name="valor_recebido" type="number" step="0.01" min="0" placeholder="0.00" value="<?php echo $valor_recebido; ?>">
                </div>
            </div>
            
            <div class="flex items-center justify-end">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out" type="submit">
                    <?php echo $id ? 'Atualizar Registro' : 'Salvar Registro'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
