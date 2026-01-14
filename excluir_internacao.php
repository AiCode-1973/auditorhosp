<?php
require_once 'db_config.php';
session_start();

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    try {
        // Buscar dados antes de excluir para o log
        $stmt_dados = $pdo->prepare("SELECT * FROM internacoes WHERE id = ?");
        $stmt_dados->execute([$id]);
        $dados = $stmt_dados->fetch(PDO::FETCH_ASSOC);
        
        // Excluir
        $stmt = $pdo->prepare("DELETE FROM internacoes WHERE id = ?");
        $stmt->execute([$id]);
        
        // Registrar log de exclusão
        if ($dados && isset($_SESSION['usuario_id'])) {
            $usuario_id = $_SESSION['usuario_id'];
            $usuario_nome = $_SESSION['usuario_nome'];
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
            
            $detalhes = "Atendimento excluído: {$dados['paciente']} (Guia: {$dados['guia_paciente']})";
            
            $sql_log = "INSERT INTO logs_atendimento (usuario_id, usuario_nome, atendimento_id, acao, detalhes, valores_anteriores, ip_address) 
                        VALUES (?, ?, ?, 'EXCLUSAO', ?, ?, ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([
                $usuario_id,
                $usuario_nome,
                null, // atendimento_id null pois foi excluído
                $detalhes,
                json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                $ip_address
            ]);
        }
        
        header("Location: internacoes.php");
        exit;
    } catch (PDOException $e) {
        echo "Erro ao excluir: " . $e->getMessage();
    }
} else {
    header("Location: internacoes.php");
    exit;
}
?>