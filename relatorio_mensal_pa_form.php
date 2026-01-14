<?php
require_once 'db_config.php';
include 'includes/header.php';

$mensagem = '';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Variáveis do formulário
$competencia = '';
$convenio_id = '';
$setor = '';
$valor_inicial = '';
$valor_retirado = '';
$valor_acrescentado = '';
$valor_final = '';
$valor_glosado = '';
$valor_aceito = '';
$valor_faturado = '';
$qtd_atendimentos = '';
$titulo = 'Novo Registro - Relatório PA/Ambulatório';

// Carregar dados se for edição
if ($id) {
    $titulo = 'Editar Registro - Relatório PA/Ambulatório';
    try {
        $stmt = $pdo->prepare("SELECT * FROM relatorio_mensal_pa_consolidado WHERE id = ?");
        $stmt->execute([$id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados) {
            $competencia = date('Y-m', strtotime($dados['competencia']));
            $convenio_id = $dados['convenio_id'];
            $setor = $dados['setor'];
            $valor_inicial = number_format($dados['valor_inicial'], 2, ',', '.');
            $valor_retirado = number_format($dados['valor_retirado'], 2, ',', '.');
            $valor_acrescentado = number_format($dados['valor_acrescentado'], 2, ',', '.');
            $valor_final = number_format($dados['valor_final'], 2, ',', '.');
            $valor_glosado = number_format($dados['valor_glosado'], 2, ',', '.');
            $valor_aceito = number_format($dados['valor_aceito'], 2, ',', '.');
            $valor_faturado = number_format($dados['valor_faturado'], 2, ',', '.');
            $qtd_atendimentos = $dados['qtd_atendimentos'];
        } else {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Registro não encontrado.</div>";
        }
    } catch (PDOException $e) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro ao carregar: " . $e->getMessage() . "</div>";
    }
}

// Buscar convênios
try {
    $stmt = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
    $convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenios = [];
}

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $competencia = $_POST['competencia'] . '-01';
    $convenio_id = $_POST['convenio_id'];
    $setor = $_POST['setor'];
    
    // Converter valores
    function formatCurrency($val) {
        $val = str_replace('.', '', $val);
        $val = str_replace(',', '.', $val);
        return $val ?: 0;
    }

    $valor_inicial_db = formatCurrency($_POST['valor_inicial']);
    $valor_retirado_db = formatCurrency($_POST['valor_retirado']);
    $valor_acrescentado_db = formatCurrency($_POST['valor_acrescentado']);
    $valor_final_db = formatCurrency($_POST['valor_final']);
    $valor_glosado_db = formatCurrency($_POST['valor_glosado']);
    $valor_aceito_db = formatCurrency($_POST['valor_aceito']);
    $valor_faturado_db = formatCurrency($_POST['valor_faturado']);
    $qtd_atendimentos_db = intval($_POST['qtd_atendimentos']);

    if (empty($competencia) || empty($convenio_id) || empty($setor)) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Preencha os campos obrigatórios (Competência, Convênio e Setor).</div>";
    } else {
        try {
            if ($id) {
                // Atualizar
                $sql = "UPDATE relatorio_mensal_pa_consolidado SET 
                        competencia=?, convenio_id=?, setor=?, valor_inicial=?, valor_retirado=?, 
                        valor_acrescentado=?, valor_final=?, valor_glosado=?, valor_aceito=?,
                        valor_faturado=?, qtd_atendimentos=?
                        WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $competencia, $convenio_id, $setor, $valor_inicial_db, $valor_retirado_db,
                    $valor_acrescentado_db, $valor_final_db, $valor_glosado_db, $valor_aceito_db,
                    $valor_faturado_db, $qtd_atendimentos_db, $id
                ]);
                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Atualizado com sucesso!</div>";
                echo "<script>setTimeout(function(){ window.location.href = 'relatorio_mensal_pa_ambulatorio.php'; }, 1500);</script>";
            } else {
                // Inserir
                $sql = "INSERT INTO relatorio_mensal_pa_consolidado 
                        (competencia, convenio_id, setor, valor_inicial, valor_retirado, valor_acrescentado, 
                         valor_final, valor_glosado, valor_aceito, valor_faturado, qtd_atendimentos)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $competencia, $convenio_id, $setor, $valor_inicial_db, $valor_retirado_db,
                    $valor_acrescentado_db, $valor_final_db, $valor_glosado_db, $valor_aceito_db,
                    $valor_faturado_db, $qtd_atendimentos_db
                ]);
                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Cadastrado com sucesso!</div>";
                echo "<script>setTimeout(function(){ window.location.href = 'relatorio_mensal_pa_ambulatorio.php'; }, 1500);</script>";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Já existe um registro para esta competência, convênio e setor.</div>";
            } else {
                $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro ao salvar: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<div class="container mx-auto mt-8 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><?php echo $titulo; ?></h2>
        <a href="relatorio_mensal_pa_ambulatorio.php" class="text-blue-600 hover:text-blue-800 font-medium">Voltar para Lista</a>
    </div>

    <?php echo $mensagem; ?>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Competência -->
                <div>
                    <label for="competencia" class="block text-sm font-medium text-gray-700 mb-1">Competência (Mês/Ano) *</label>
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
                    </select>
                </div>

                <!-- Quantidade de Atendimentos -->
                <div>
                    <label for="qtd_atendimentos" class="block text-sm font-medium text-gray-700 mb-1">Qtd. Atendimentos</label>
                    <input type="number" name="qtd_atendimentos" id="qtd_atendimentos" value="<?php echo $qtd_atendimentos; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" min="0">
                </div>

                <!-- Valor Inicial -->
                <div>
                    <label for="valor_inicial" class="block text-sm font-medium text-gray-700 mb-1">Valor Inicial (R$)</label>
                    <input type="text" name="valor_inicial" id="valor_inicial" value="<?php echo $valor_inicial; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-right" onkeyup="formatMoney(this)">
                </div>

                <!-- Valor Retirado -->
                <div>
                    <label for="valor_retirado" class="block text-sm font-medium text-gray-700 mb-1">Valor Retirado (R$)</label>
                    <input type="text" name="valor_retirado" id="valor_retirado" value="<?php echo $valor_retirado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-right" onkeyup="formatMoney(this)">
                </div>

                <!-- Valor Acrescentado -->
                <div>
                    <label for="valor_acrescentado" class="block text-sm font-medium text-gray-700 mb-1">Valor Acrescentado (R$)</label>
                    <input type="text" name="valor_acrescentado" id="valor_acrescentado" value="<?php echo $valor_acrescentado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-right" onkeyup="formatMoney(this)">
                </div>

                <!-- Valor Final -->
                <div>
                    <label for="valor_final" class="block text-sm font-medium text-gray-700 mb-1">Valor Final (R$)</label>
                    <input type="text" name="valor_final" id="valor_final" value="<?php echo $valor_final; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-right" onkeyup="formatMoney(this)">
                </div>

                <!-- Valor Glosado -->
                <div>
                    <label for="valor_glosado" class="block text-sm font-medium text-gray-700 mb-1">Valor Glosado (R$)</label>
                    <input type="text" name="valor_glosado" id="valor_glosado" value="<?php echo $valor_glosado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-right" onkeyup="formatMoney(this)">
                </div>

                <!-- Valor Aceito -->
                <div>
                    <label for="valor_aceito" class="block text-sm font-medium text-gray-700 mb-1">Valor Aceito (R$)</label>
                    <input type="text" name="valor_aceito" id="valor_aceito" value="<?php echo $valor_aceito; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-right" onkeyup="formatMoney(this)">
                </div>

                <!-- Valor Faturado -->
                <div>
                    <label for="valor_faturado" class="block text-sm font-medium text-gray-700 mb-1">Valor Faturado (R$)</label>
                    <input type="text" name="valor_faturado" id="valor_faturado" value="<?php echo $valor_faturado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-right" onkeyup="formatMoney(this)">
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end gap-4 mt-8">
                <a href="relatorio_mensal_pa_ambulatorio.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Formatação de moeda
function formatMoney(input) {
    let value = input.value.replace(/\D/g, '');
    value = (value / 100).toFixed(2) + '';
    value = value.replace(".", ",");
    value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = value;
}
</script>

<?php include 'includes/footer.php'; ?>
