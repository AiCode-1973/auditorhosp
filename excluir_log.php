<?php
require_once 'db_config.php';
session_start();

// Apenas administradores podem excluir logs
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] != 'admin') {
    header('Location: index.php');
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM logs_atendimento WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: logs_atendimento.php?msg=excluido");
        exit;
    } catch (PDOException $e) {
        header("Location: logs_atendimento.php?erro=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: logs_atendimento.php");
    exit;
}
?>
