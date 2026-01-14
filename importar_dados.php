<?php
require_once 'db_config.php';

$file_path = 'dados_importacao.txt';

if (!file_exists($file_path)) {
    die("Arquivo de dados não encontrado.");
}

$lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Ignorar cabeçalho e linhas de total
$data_lines = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    if (strpos($line, 'Data recebimento') !== false) continue;
    if (strpos($line, 'Total') === 0) continue;
    $data_lines[] = $line;
}

echo "Iniciando importação de " . count($data_lines) . " registros...<br>";

$count_success = 0;
$count_error = 0;

// Cache de convênios para evitar muitas consultas
$convenios_cache = [];
$stmt_conv = $pdo->query("SELECT id, nome_convenio FROM convenios");
while ($row = $stmt_conv->fetch(PDO::FETCH_ASSOC)) {
    $convenios_cache[strtoupper(trim($row['nome_convenio']))] = $row['id'];
}

$stmt_insert = $pdo->prepare("
    INSERT INTO internacoes (
        data_recebimento, competencia, paciente, convenio_id, guia_paciente, 
        data_entrada, data_saida, valor_inicial, valor_retirado, 
        valor_acrescentado, valor_total, valor_glosado, valor_aceito, 
        valor_faturado, status, falta_nf
    ) VALUES (
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, 'Em Aberto', 'Não'
    )
");

foreach ($data_lines as $line) {
    // Tenta separar por tabulação
    $cols = explode("\t", $line);
    
    // Se não tiver colunas suficientes, tenta separar por múltiplos espaços (caso o copy-paste tenha perdido tabs)
    if (count($cols) < 13) {
        // Tenta regex para separar por 2 ou mais espaços
        $cols = preg_split('/\s{2,}/', $line);
    }

    // Normalizar array para garantir índices
    $cols = array_pad($cols, 13, '');

    // Mapeamento das colunas
    $data_recebimento_raw = trim($cols[0]);
    $paciente = trim($cols[1]);
    $nome_convenio = trim($cols[2]);
    $guia = trim($cols[3]);
    $data_entrada_raw = trim($cols[4]);
    $data_saida_raw = trim($cols[5]);
    
    // Valores monetários
    $v_inicial = cleanMoney($cols[6]);
    $v_retirado = cleanMoney($cols[7]);
    $v_acrescentado = cleanMoney($cols[8]);
    $v_final = cleanMoney($cols[9]);
    $v_glosado = cleanMoney($cols[10]);
    $v_aceito = cleanMoney($cols[11]);
    $v_faturado = cleanMoney($cols[12]);

    // Converter datas
    $data_recebimento = convertDate($data_recebimento_raw);
    $data_entrada = convertDate($data_entrada_raw);
    $data_saida = convertDate($data_saida_raw);

    // Calcular competência (primeiro dia do mês do recebimento)
    $competencia = null;
    if ($data_recebimento) {
        $competencia = date('Y-m-01', strtotime($data_recebimento));
    }

    // Resolver Convênio
    $convenio_id = null;
    $nome_convenio_upper = strtoupper($nome_convenio);
    
    if (isset($convenios_cache[$nome_convenio_upper])) {
        $convenio_id = $convenios_cache[$nome_convenio_upper];
    } else {
        // Inserir novo convênio
        try {
            $stmt_new_conv = $pdo->prepare("INSERT INTO convenios (nome_convenio) VALUES (?)");
            $stmt_new_conv->execute([$nome_convenio]);
            $convenio_id = $pdo->lastInsertId();
            $convenios_cache[$nome_convenio_upper] = $convenio_id;
        } catch (Exception $e) {
            echo "Erro ao criar convênio '$nome_convenio': " . $e->getMessage() . "<br>";
            $count_error++;
            continue;
        }
    }

    try {
        $stmt_insert->execute([
            $data_recebimento, $competencia, $paciente, $convenio_id, $guia,
            $data_entrada, $data_saida, $v_inicial, $v_retirado,
            $v_acrescentado, $v_final, $v_glosado, $v_aceito,
            $v_faturado
        ]);
        $count_success++;
    } catch (Exception $e) {
        echo "Erro ao inserir linha ($line): " . $e->getMessage() . "<br>";
        $count_error++;
    }
}

echo "Importação concluída!<br>";
echo "Sucesso: $count_success<br>";
echo "Erros: $count_error<br>";

function cleanMoney($str) {
    $str = trim($str);
    if ($str == '-' || empty($str)) return 0.00;
    
    // Remove R$ e espaços
    $str = str_replace(['R$', ' '], '', $str);
    
    // Verifica sinal negativo
    $is_negative = false;
    if (strpos($str, '-') !== false) {
        $is_negative = true;
        $str = str_replace('-', '', $str);
    }
    
    // Converte formato BR para US (1.234,56 -> 1234.56)
    $str = str_replace('.', '', $str);
    $str = str_replace(',', '.', $str);
    
    $val = (float)$str;
    return $is_negative ? -$val : $val;
}

function convertDate($str) {
    $d = DateTime::createFromFormat('d/m/Y', trim($str));
    return $d ? $d->format('Y-m-d') : null;
}
?>