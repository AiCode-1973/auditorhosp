<?php
require_once 'db_config.php';
require_once 'auth.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header('Location: pa_ambulatorio.php');
    exit();
}

try {
    // Buscar dados antes de excluir para o log
    $stmt = $pdo->prepare("SELECT p.*, c.nome_convenio FROM pa_ambulatorio p JOIN convenios c ON p.convenio_id = c.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($atendimento) {
        // Registrar log antes de excluir
        $usuario_id = $_SESSION['usuario_id'];
        $usuario_nome = $_SESSION['usuario_nome'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        
        $detalhes = "Atendimento PA/AMB excluído - Setor: {$atendimento['setor']}, Convênio: {$atendimento['nome_convenio']}, Guia: {$atendimento['guia_paciente']}";
        
        $valores_anteriores = json_encode([
            'setor' => $atendimento['setor'],
            'convenio' => $atendimento['nome_convenio'],
            'guia_paciente' => $atendimento['guia_paciente'],
            'valor_inicial' => $atendimento['valor_inicial'],
            'valor_total' => $atendimento['valor_total'],
            'status' => $atendimento['status']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $log_sql = "INSERT INTO logs_atendimento (usuario_id, usuario_nome, atendimento_id, acao, detalhes, valores_anteriores, ip_address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([$usuario_id, $usuario_nome, null, 'EXCLUSAO_PA_AMB', $detalhes, $valores_anteriores, $ip_address]);
        
        // Excluir o atendimento
        $stmt = $pdo->prepare("DELETE FROM pa_ambulatorio WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: pa_ambulatorio.php?msg=excluido');
    } else {
        header('Location: pa_ambulatorio.php?msg=nao_encontrado');
    }
} catch (PDOException $e) {
    echo "Erro ao excluir: " . $e->getMessage();
}
?>
