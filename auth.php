<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Atualizar último acesso
require_once 'db_config.php';
try {
    $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
} catch (PDOException $e) {
    // Ignora erro de atualização de último acesso
}
?>
