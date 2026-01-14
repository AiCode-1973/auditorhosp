<?php
require_once 'db_config.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM relatorio_mensal_consolidado WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: relatorio_mensal.php");
        exit;
    } catch (PDOException $e) {
        echo "Erro ao excluir: " . $e->getMessage();
    }
} else {
    header("Location: relatorio_mensal.php");
    exit;
}
?>
