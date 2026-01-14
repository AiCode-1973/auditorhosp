<?php
require_once 'db_config.php';
include 'includes/header.php';

$mensagem = '';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Variáveis do formulário
$competencia = '';
$convenio_id = '';
$valor_inicial = '';
$valor_retirado = '';
$valor_acrescentado = '';
$valor_final = '';
$valor_glosado = '';
$valor_aceito = '';
$titulo = 'Novo Registro - Relatório Mensal';

// Carregar dados se for edição
if ($id) {
    $titulo = 'Editar Registro - Relatório Mensal';
    try {
        $stmt = $pdo->prepare("SELECT * FROM relatorio_mensal_consolidado WHERE id = ?");
        $stmt->execute([$id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados) {
            $competencia = date('Y-m', strtotime($dados['competencia']));
            $convenio_id = $dados['convenio_id'];
            $valor_inicial = number_format($dados['valor_inicial'], 2, ',', '.');
            $valor_retirado = number_format($dados['valor_retirado'], 2, ',', '.');
            $valor_acrescentado = number_format($dados['valor_acrescentado'], 2, ',', '.');
            $valor_final = number_format($dados['valor_final'], 2, ',', '.');
            $valor_glosado = number_format($dados['valor_glosado'], 2, ',', '.');
            $valor_aceito = number_format($dados['valor_aceito'], 2, ',', '.');
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

    // Calcular percentuais
    $perc_retirado = $valor_inicial_db > 0 ? round(($valor_retirado_db / $valor_inicial_db) * 100, 2) : 0;
    $perc_acrescentado = $valor_inicial_db > 0 ? round(($valor_acrescentado_db / $valor_inicial_db) * 100, 2) : 0;
    $perc_glosado = $valor_final_db > 0 ? round(($valor_glosado_db / $valor_final_db) * 100, 2) : 0;
    $perc_aceito = $valor_glosado_db > 0 ? round(($valor_aceito_db / $valor_glosado_db) * 100, 2) : 0;

    if (empty($competencia) || empty($convenio_id)) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Preencha os campos obrigatórios (Competência e Convênio).</div>";
    } else {
        try {
            if ($id) {
                // Atualizar
                $sql = "UPDATE relatorio_mensal_consolidado SET 
                        competencia=?, convenio_id=?, valor_inicial=?, valor_retirado=?, 
                        valor_acrescentado=?, valor_final=?, valor_glosado=?, valor_aceito=?,
                        perc_retirado=?, perc_acrescentado=?, perc_glosado=?, perc_aceito=?
                        WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $competencia, $convenio_id, $valor_inicial_db, $valor_retirado_db,
                    $valor_acrescentado_db, $valor_final_db, $valor_glosado_db, $valor_aceito_db,
                    $perc_retirado, $perc_acrescentado, $perc_glosado, $perc_aceito, $id
                ]);
                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Atualizado com sucesso!</div>";
                echo "<script>setTimeout(function(){ window.location.href = 'relatorio_mensal.php'; }, 1500);</script>";
            } else {
                // Inserir
                $sql = "INSERT INTO relatorio_mensal_consolidado 
                        (competencia, convenio_id, valor_inicial, valor_retirado, valor_acrescentado, 
                         valor_final, valor_glosado, valor_aceito, perc_retirado, perc_acrescentado, 
                         perc_glosado, perc_aceito)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $competencia, $convenio_id, $valor_inicial_db, $valor_retirado_db,
                    $valor_acrescentado_db, $valor_final_db, $valor_glosado_db, $valor_aceito_db,
                    $perc_retirado, $perc_acrescentado, $perc_glosado, $perc_aceito
                ]);
                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Cadastrado com sucesso!</div>";
                echo "<script>setTimeout(function(){ window.location.href = 'relatorio_mensal.php'; }, 1500);</script>";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Já existe um registro para esta competência e convênio.</div>";
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
        <a href="relatorio_mensal.php" class="text-blue-600 hover:text-blue-800 font-medium">Voltar para Lista</a>
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

                <!-- Valor Inicial -->
                <div>
                    <label for="valor_inicial" class="block text-sm font-medium text-gray-700 mb-1">Valor Inicial (R$)</label>
                    <input type="text" name="valor_inicial" id="valor_inicial" value="<?php echo $valor_inicial; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0,00" onkeyup="formatarMoeda(this); calcularValorAcrescentado();">
                </div>

                <!-- Valor Retirado -->
                <div>
                    <label for="valor_retirado" class="block text-sm font-medium text-gray-700 mb-1">Valor Retirado (R$)</label>
                    <input type="text" name="valor_retirado" id="valor_retirado" value="<?php echo $valor_retirado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-red-600" placeholder="0,00" onkeyup="formatarMoeda(this); calcularValorAcrescentado();">
                </div>

                <!-- Valor Acrescentado -->
                <div>
                    <label for="valor_acrescentado" class="block text-sm font-medium text-gray-700 mb-1">Valor Acrescentado (R$) - Calculado</label>
                    <input type="text" name="valor_acrescentado" id="valor_acrescentado" value="<?php echo $valor_acrescentado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-green-600 font-bold bg-gray-50" placeholder="0,00" readonly>
                </div>

                <!-- Valor Final -->
                <div>
                    <label for="valor_final" class="block text-sm font-medium text-gray-700 mb-1">Valor Final (R$)</label>
                    <input type="text" name="valor_final" id="valor_final" value="<?php echo $valor_final; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold" placeholder="0,00" onkeyup="formatarMoeda(this); calcularValorAcrescentado();">
                </div>

                <!-- Valor Glosado -->
                <div>
                    <label for="valor_glosado" class="block text-sm font-medium text-gray-700 mb-1">Valor Glosado (R$)</label>
                    <input type="text" name="valor_glosado" id="valor_glosado" value="<?php echo $valor_glosado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0,00" onkeyup="formatarMoeda(this)">
                </div>

                <!-- Valor Aceito -->
                <div>
                    <label for="valor_aceito" class="block text-sm font-medium text-gray-700 mb-1">Valor Aceito (R$)</label>
                    <input type="text" name="valor_aceito" id="valor_aceito" value="<?php echo $valor_aceito; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0,00" onkeyup="formatarMoeda(this)">
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-4">
                <a href="relatorio_mensal.php" class="bg-gray-500 text-white font-bold py-2 px-6 rounded hover:bg-gray-600 transition duration-150 flex items-center">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded hover:bg-blue-700 transition duration-150">
                    <?php echo $id ? 'Atualizar' : 'Salvar'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function formatarMoeda(i) {
    var v = i.value.replace(/\D/g,'');
    v = (v/100).toFixed(2) + '';
    v = v.replace(".", ",");
    v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
    i.value = v;
}

function calcularValorAcrescentado() {
    let valorFinal = document.getElementById('valor_final').value.replace(/\./g, '').replace(',', '.') || 0;
    let valorRetirado = document.getElementById('valor_retirado').value.replace(/\./g, '').replace(',', '.') || 0;
    let valorInicial = document.getElementById('valor_inicial').value.replace(/\./g, '').replace(',', '.') || 0;

    // valor_acrescentado = valor_final + valor_retirado - valor_inicial
    let valorAcrescentado = parseFloat(valorFinal) + parseFloat(valorRetirado) - parseFloat(valorInicial);

    let valorFormatado = valorAcrescentado.toFixed(2).replace('.', ',');
    valorFormatado = valorFormatado.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

    document.getElementById('valor_acrescentado').value = valorFormatado;
}
</script>

<?php include 'includes/footer.php'; ?>
