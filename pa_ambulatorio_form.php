<?php
require_once 'db_config.php';
include 'includes/header.php';

// Função para registrar logs
function registrarLog($pdo, $atendimento_id, $acao, $detalhes, $valores_anteriores = null, $valores_novos = null) {
    try {
        $usuario_id = $_SESSION['usuario_id'];
        $usuario_nome = $_SESSION['usuario_nome'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        
        $sql = "INSERT INTO logs_atendimento (usuario_id, usuario_nome, atendimento_id, acao, detalhes, valores_anteriores, valores_novos, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $usuario_id,
            $usuario_nome,
            $atendimento_id,
            $acao,
            $detalhes,
            $valores_anteriores ? json_encode($valores_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null,
            $valores_novos ? json_encode($valores_novos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null,
            $ip_address
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
        return false;
    }
}

$mensagem = '';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Variáveis
$data_recebimento = date('Y-m-d');
$competencia = '';
$setor = 'PA';
$convenio_id = '';
$guia_paciente = '';
$data_entrada = '';
$data_saida = '';
$valor_inicial = '';
$valor_retirado = '';
$valor_acrescentado = '';
$valor_total = '';
$valor_glosado = '';
$valor_aceito = '';
$valor_faturado = '';
$falta_nf = 'Não';
$status = 'Em Aberto';
$observacoes = '';
$titulo = 'Novo Atendimento PA/Ambulatório';

// Carregar dados se for edição
if ($id) {
    $titulo = 'Editar Atendimento PA/Ambulatório';
    try {
        $stmt = $pdo->prepare("SELECT * FROM pa_ambulatorio WHERE id = ?");
        $stmt->execute([$id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados) {
            $data_recebimento = $dados['data_recebimento'];
            $competencia = $dados['competencia'] ? date('Y-m', strtotime($dados['competencia'])) : '';
            $setor = $dados['setor'];
            $convenio_id = $dados['convenio_id'];
            $guia_paciente = $dados['guia_paciente'];
            $data_entrada = $dados['data_entrada'];
            $data_saida = $dados['data_saida'];
            $valor_inicial = number_format($dados['valor_inicial'], 2, ',', '.');
            $valor_retirado = number_format($dados['valor_retirado'], 2, ',', '.');
            $valor_acrescentado = number_format($dados['valor_acrescentado'], 2, ',', '.');
            $valor_total = number_format($dados['valor_total'], 2, ',', '.');
            $valor_glosado = number_format($dados['valor_glosado'], 2, ',', '.');
            $valor_aceito = number_format($dados['valor_aceito'], 2, ',', '.');
            $valor_faturado = number_format($dados['valor_faturado'], 2, ',', '.');
            $falta_nf = $dados['falta_nf'];
            $status = $dados['status'];
            $observacoes = $dados['observacoes'];
        } else {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Registro não encontrado.</div>";
        }
    } catch (PDOException $e) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro ao carregar: " . $e->getMessage() . "</div>";
    }
}

// Verificar se é edição com setor NC (para desabilitar campos)
$is_nc_edit = ($id && ($setor == 'PA/NC' || $setor == 'AMB/NC'));

// Buscar convênios
try {
    $stmt = $pdo->query("SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio");
    $convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $convenios = [];
}

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se é inclusão rápida
    $inclusao_rapida = isset($_POST['inclusao_rapida']) && $_POST['inclusao_rapida'] == '1';
    
    if ($inclusao_rapida) {
        // Processamento simplificado para inclusão rápida
        $guia_paciente = trim($_POST['guia_paciente']);
        $setor = isset($_POST['setor']) ? $_POST['setor'] : 'PA/NC';
        $competencia = isset($_POST['competencia']) ? $_POST['competencia'] . '-01' : null;
        $convenio_id = isset($_POST['convenio_id']) ? $_POST['convenio_id'] : 1;
        $status = isset($_POST['status']) ? $_POST['status'] : 'Em Aberto';
        
        function formatCurrency($val) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
            return $val ?: 0;
        }
        
        $valor_total_db = formatCurrency($_POST['valor_total']);
        $valor_glosado_db = isset($_POST['valor_glosado']) ? formatCurrency($_POST['valor_glosado']) : 0;
        $valor_aceito_db = isset($_POST['valor_aceito']) ? formatCurrency($_POST['valor_aceito']) : 0;
        
        if (empty($guia_paciente) || $valor_total_db <= 0) {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Preencha o número da guia e o valor final.</div>";
        } else {
            try {
                // Inserir atendimento com dados mínimos
                $sql = "INSERT INTO pa_ambulatorio 
                        (data_recebimento, setor, convenio_id, guia_paciente, competencia, valor_inicial, valor_retirado, 
                         valor_acrescentado, valor_total, valor_glosado, valor_aceito, valor_faturado, 
                         falta_nf, status, observacoes)
                        VALUES (NOW(), ?, ?, ?, ?, 0, 0, 0, ?, ?, ?, ?, 'Não', ?, 'Inclusão rápida')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$setor, $convenio_id, $guia_paciente, $competencia, $valor_total_db, $valor_glosado_db, $valor_aceito_db, $valor_total_db, $status]);
                
                $novo_id = $pdo->lastInsertId();
                
                // Registrar log
                registrarLog($pdo, null, 'INCLUSAO_RAPIDA_PA', "Atendimento PA incluído rapidamente - Guia: $guia_paciente", null, [
                    'guia_paciente' => $guia_paciente,
                    'valor_total' => $valor_total_db,
                    'competencia' => $competencia,
                    'convenio_id' => $convenio_id,
                    'status' => $status
                ]);
                
                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Atendimento incluído com sucesso!</div>";
                echo "<script>setTimeout(function(){ window.location.href = 'pa_ambulatorio.php'; }, 1500);</script>";
            } catch (PDOException $e) {
                $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro ao inserir: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        // Processamento normal
        $data_recebimento = $_POST['data_recebimento'];
        $competencia = !empty($_POST['competencia']) ? $_POST['competencia'] . '-01' : null;
        $setor = $_POST['setor'];
        $convenio_id = $_POST['convenio_id'];
        $guia_paciente = $_POST['guia_paciente'];
        $data_entrada = !empty($_POST['data_entrada']) ? $_POST['data_entrada'] : null;
        $data_saida = !empty($_POST['data_saida']) ? $_POST['data_saida'] : null;
        
        // Helper function to format currency
        function formatCurrency($val) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
            return $val ?: 0;
        }

        $valor_inicial_db = formatCurrency($_POST['valor_inicial']);
        $valor_retirado_db = formatCurrency($_POST['valor_retirado']);
        $valor_acrescentado_db = formatCurrency($_POST['valor_acrescentado']);
        $valor_total_db = formatCurrency($_POST['valor_total']);
        $valor_glosado_db = formatCurrency($_POST['valor_glosado']);
        $valor_aceito_db = formatCurrency($_POST['valor_aceito']);
        $valor_faturado_db = formatCurrency($_POST['valor_faturado']);

        $falta_nf = $_POST['falta_nf'];
        $status = $_POST['status'];
        $observacoes = $_POST['observacoes'];

        if (empty($data_recebimento) || empty($setor) || empty($convenio_id)) {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Preencha os campos obrigatórios.</div>";
        } else {
            try {
                if ($id) {
                    // Buscar valores anteriores para o log
                    $stmt_anterior = $pdo->prepare("SELECT * FROM pa_ambulatorio WHERE id = ?");
                    $stmt_anterior->execute([$id]);
                    $valores_anteriores = $stmt_anterior->fetch(PDO::FETCH_ASSOC);
                
                $sql = "UPDATE pa_ambulatorio SET data_recebimento=?, competencia=?, setor=?, convenio_id=?, guia_paciente=?, data_entrada=?, data_saida=?, valor_inicial=?, valor_retirado=?, valor_acrescentado=?, valor_total=?, valor_glosado=?, valor_aceito=?, valor_faturado=?, falta_nf=?, status=?, observacoes=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data_recebimento, $competencia, $setor, $convenio_id, $guia_paciente, $data_entrada, $data_saida, $valor_inicial_db, $valor_retirado_db, $valor_acrescentado_db, $valor_total_db, $valor_glosado_db, $valor_aceito_db, $valor_faturado_db, $falta_nf, $status, $observacoes, $id]);
                
                // Registrar log de edição
                $valores_novos = [
                    'setor' => $setor,
                    'convenio_id' => $convenio_id,
                    'guia_paciente' => $guia_paciente,
                    'data_recebimento' => $data_recebimento,
                    'competencia' => $competencia,
                    'valor_inicial' => $valor_inicial_db,
                    'valor_retirado' => $valor_retirado_db,
                    'valor_acrescentado' => $valor_acrescentado_db,
                    'valor_total' => $valor_total_db,
                    'valor_glosado' => $valor_glosado_db,
                    'valor_aceito' => $valor_aceito_db,
                    'valor_faturado' => $valor_faturado_db,
                    'status' => $status
                ];
                
                // Identificar campos alterados
                $campos_alterados = [];
                foreach ($valores_novos as $campo => $valor_novo) {
                    if (isset($valores_anteriores[$campo]) && $valores_anteriores[$campo] != $valor_novo) {
                        $campos_alterados[] = $campo;
                    }
                }
                
                $detalhes = "Atendimento PA/AMB editado: $setor (Guia: $guia_paciente)";
                if (!empty($campos_alterados)) {
                    $detalhes .= " - Campos alterados: " . implode(', ', $campos_alterados);
                }
                
                registrarLog($pdo, $id, 'EDICAO_PA_AMB', $detalhes, $valores_anteriores, $valores_novos);
                
                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Atualizado com sucesso!</div>";
                
                // Atualiza variáveis para exibição imediata dos novos dados
                $valor_inicial = $_POST['valor_inicial'];
                $valor_retirado = $_POST['valor_retirado'];
                $valor_acrescentado = $_POST['valor_acrescentado'];
                $valor_total = $_POST['valor_total'];
                $valor_glosado = $_POST['valor_glosado'];
                $valor_aceito = $_POST['valor_aceito'];
                $valor_faturado = $_POST['valor_faturado'];
            } else {
                $sql = "INSERT INTO pa_ambulatorio (data_recebimento, competencia, setor, convenio_id, guia_paciente, data_entrada, data_saida, valor_inicial, valor_retirado, valor_acrescentado, valor_total, valor_glosado, valor_aceito, valor_faturado, falta_nf, status, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data_recebimento, $competencia, $setor, $convenio_id, $guia_paciente, $data_entrada, $data_saida, $valor_inicial_db, $valor_retirado_db, $valor_acrescentado_db, $valor_total_db, $valor_glosado_db, $valor_aceito_db, $valor_faturado_db, $falta_nf, $status, $observacoes]);
                
                $novo_id = $pdo->lastInsertId();
                
                // Registrar log de criação
                $valores_novos = [
                    'setor' => $setor,
                    'convenio_id' => $convenio_id,
                    'guia_paciente' => $guia_paciente,
                    'data_recebimento' => $data_recebimento,
                    'competencia' => $competencia,
                    'valor_inicial' => $valor_inicial_db,
                    'valor_retirado' => $valor_retirado_db,
                    'valor_acrescentado' => $valor_acrescentado_db,
                    'valor_total' => $valor_total_db,
                    'valor_glosado' => $valor_glosado_db,
                    'valor_aceito' => $valor_aceito_db,
                    'valor_faturado' => $valor_faturado_db,
                    'status' => $status
                ];
                registrarLog($pdo, $novo_id, 'CRIACAO_PA_AMB', "Atendimento PA/AMB criado: $setor (Guia: $guia_paciente)", null, $valores_novos);
                
                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Cadastrado com sucesso!</div>";
                
                // Limpar campos após cadastro
                if (!$id) {
                    $setor = 'PA'; $guia_paciente = ''; $valor_inicial = ''; $valor_retirado = ''; $valor_acrescentado = ''; $valor_total = ''; $valor_glosado = ''; $valor_aceito = ''; $valor_faturado = ''; $falta_nf = 'Não'; $observacoes = '';
                }
            }
        } catch (PDOException $e) {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro ao salvar: " . $e->getMessage() . "</div>";
        }
        }
    }
}
?>

<div class="container mx-auto mt-8 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><?php echo $titulo; ?></h2>
        <a href="pa_ambulatorio.php" class="text-blue-600 hover:text-blue-800 font-medium">Voltar para Lista</a>
    </div>

    <?php echo $mensagem; ?>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Competência -->
                <div class="col-span-2 md:col-span-1">
                    <label for="competencia" class="block text-sm font-medium text-gray-700 mb-1">Competência</label>
                    <input type="month" name="competencia" id="competencia" value="<?php echo $competencia; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Data Recebimento -->
                <div class="col-span-2 md:col-span-1">
                    <label for="data_recebimento" class="block text-sm font-medium text-gray-700 mb-1">Data Recebimento *</label>
                    <input type="date" name="data_recebimento" id="data_recebimento" value="<?php echo $data_recebimento; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $is_nc_edit ? 'bg-gray-100' : ''; ?>" <?php echo $is_nc_edit ? 'disabled' : 'required'; ?>>
                </div>

                <!-- Setor -->
                <div class="col-span-2 md:col-span-1">
                    <label for="setor" class="block text-sm font-medium text-gray-700 mb-1">Setor *</label>
                    <select name="setor" id="setor" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="PA" <?php echo $setor == 'PA' ? 'selected' : ''; ?>>PA (Pronto Atendimento)</option>
                        <option value="AMB" <?php echo $setor == 'AMB' ? 'selected' : ''; ?>>AMB (Ambulatório)</option>
                        <option value="PA/NC" <?php echo $setor == 'PA/NC' ? 'selected' : ''; ?>>PA/NC - PA Não Corrigida</option>
                        <option value="AMB/NC" <?php echo $setor == 'AMB/NC' ? 'selected' : ''; ?>>AMB/NC - AMB Não Corrigida</option>
                    </select>
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

                <!-- Guia Paciente -->
                <div>
                    <label for="guia_paciente" class="block text-sm font-medium text-gray-700 mb-1">Guia Paciente *</label>
                    <input type="text" name="guia_paciente" id="guia_paciente" value="<?php echo htmlspecialchars($guia_paciente); ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <!-- Data Entrada -->
                <div>
                    <label for="data_entrada" class="block text-sm font-medium text-gray-700 mb-1">Data Entrada</label>
                    <input type="date" name="data_entrada" id="data_entrada" value="<?php echo $data_entrada; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $is_nc_edit ? 'bg-gray-100' : ''; ?>" <?php echo $is_nc_edit ? 'disabled' : ''; ?>>
                </div>

                <!-- Data Saída -->
                <div>
                    <label for="data_saida" class="block text-sm font-medium text-gray-700 mb-1">Data Saída</label>
                    <input type="date" name="data_saida" id="data_saida" value="<?php echo $data_saida; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $is_nc_edit ? 'bg-gray-100' : ''; ?>" <?php echo $is_nc_edit ? 'disabled' : ''; ?>>
                </div>

                <!-- Valor Inicial -->
                <div>
                    <label for="valor_inicial" class="block text-sm font-medium text-gray-700 mb-1">Valor Inicial (R$)</label>
                    <input type="text" name="valor_inicial" id="valor_inicial" value="<?php echo $valor_inicial; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $is_nc_edit ? 'bg-gray-100' : ''; ?>" placeholder="0,00" onkeyup="formatarMoeda(this); calcularAcrescentado();" <?php echo $is_nc_edit ? 'disabled' : ''; ?>>
                </div>

                <!-- Valor Retirado -->
                <div>
                    <label for="valor_retirado" class="block text-sm font-medium text-gray-700 mb-1">Valor Retirado (R$)</label>
                    <input type="text" name="valor_retirado" id="valor_retirado" value="<?php echo $valor_retirado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-red-600 <?php echo $is_nc_edit ? 'bg-gray-100' : ''; ?>" placeholder="0,00" onkeyup="formatarMoeda(this); calcularAcrescentado();" <?php echo $is_nc_edit ? 'disabled' : ''; ?>>
                </div>

                <!-- Valor Total -->
                <div>
                    <label for="valor_total" class="block text-sm font-medium text-gray-700 mb-1">Valor Final (R$)</label>
                    <input type="text" name="valor_total" id="valor_total" value="<?php echo $valor_total; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold" placeholder="0,00" onkeyup="formatarMoeda(this); calcularAcrescentado();">
                </div>

                <!-- Valor Acrescentado -->
                <div>
                    <label for="valor_acrescentado" class="block text-sm font-medium text-gray-700 mb-1">Valor Acrescentado (R$) - Calculado</label>
                    <input type="text" name="valor_acrescentado" id="valor_acrescentado" value="<?php echo $valor_acrescentado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-green-600 bg-gray-50 font-bold" placeholder="0,00" readonly>
                </div>

                <!-- Valor Glosado -->
                <div>
                    <label for="valor_glosado" class="block text-sm font-medium text-gray-700 mb-1">Valor Glosado (R$)</label>
                    <input type="text" name="valor_glosado" id="valor_glosado" value="<?php echo $valor_glosado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0,00" onkeyup="formatarMoeda(this)">
                </div>

                <!-- Valor Aceito -->
                <div>
                    <label for="valor_aceito" class="block text-sm font-medium text-gray-700 mb-1">Valor Aceito (R$)</label>
                    <input type="text" name="valor_aceito" id="valor_aceito" value="<?php echo $valor_aceito; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0,00" onkeyup="formatarMoeda(this); calcularFaturado();">
                </div>

                <!-- Valor Faturado -->
                <div>
                    <label for="valor_faturado" class="block text-sm font-medium text-gray-700 mb-1">Valor Faturado (R$) - Calculado</label>
                    <input type="text" name="valor_faturado" id="valor_faturado" value="<?php echo $valor_faturado; ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 font-bold" placeholder="0,00" readonly>
                </div>

                <!-- Falta NF -->
                <div>
                    <label for="falta_nf" class="block text-sm font-medium text-gray-700 mb-1">Falta NF?</label>
                    <select name="falta_nf" id="falta_nf" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $is_nc_edit ? 'bg-gray-100' : ''; ?>" <?php echo $is_nc_edit ? 'disabled' : ''; ?>>
                        <option value="Não" <?php echo $falta_nf == 'Não' ? 'selected' : ''; ?>>Não</option>
                        <option value="Sim" <?php echo $falta_nf == 'Sim' ? 'selected' : ''; ?>>Sim</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Pré análise" <?php echo $status == 'Pré análise' ? 'selected' : ''; ?>>Pré análise</option>
                        <option value="Auditado" <?php echo $status == 'Auditado' ? 'selected' : ''; ?>>Auditado</option>
                    </select>
                </div>

                <!-- Observações -->
                <div class="col-span-2">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea name="observacoes" id="observacoes" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $is_nc_edit ? 'bg-gray-100' : ''; ?>" <?php echo $is_nc_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($observacoes); ?></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-4">
                <a href="pa_ambulatorio.php" class="bg-gray-500 text-white font-bold py-2 px-6 rounded hover:bg-gray-600 transition duration-150 flex items-center">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded hover:bg-blue-700 transition duration-150">
                    Salvar
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

function calcularAcrescentado() {
    let inicial = document.getElementById('valor_inicial').value.replace(/\./g, '').replace(',', '.') || 0;
    let retirado = document.getElementById('valor_retirado').value.replace(/\./g, '').replace(',', '.') || 0;
    let valorFinal = document.getElementById('valor_total').value.replace(/\./g, '').replace(',', '.') || 0;

    // Fórmula: Valor Acrescentado = Valor Final + Valor Retirado - Valor Inicial
    let acrescentado = parseFloat(valorFinal) + parseFloat(retirado) - parseFloat(inicial);

    let acrescentadoFormatado = acrescentado.toFixed(2).replace('.', ',');
    acrescentadoFormatado = acrescentadoFormatado.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

    document.getElementById('valor_acrescentado').value = acrescentadoFormatado;
    
    // Calcular faturado também quando o valor final mudar
    calcularFaturado();
}

function calcularFaturado() {
    let valorFinal = document.getElementById('valor_total').value.replace(/\./g, '').replace(',', '.') || 0;
    let aceito = document.getElementById('valor_aceito').value.replace(/\./g, '').replace(',', '.') || 0;

    // Fórmula: Valor Faturado = Valor Final - Valor Aceito
    let faturado = parseFloat(valorFinal) - parseFloat(aceito);

    let faturadoFormatado = faturado.toFixed(2).replace('.', ',');
    faturadoFormatado = faturadoFormatado.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

    document.getElementById('valor_faturado').value = faturadoFormatado;
}
</script>

<?php include 'includes/footer.php'; ?>
