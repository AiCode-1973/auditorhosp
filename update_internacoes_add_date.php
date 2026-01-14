<?php
require_once 'db_config.php';

try {
    // Adicionar coluna data_recebimento
    $sql = "ALTER TABLE internacoes ADD COLUMN data_recebimento DATE AFTER id";
    $pdo->exec($sql);
    echo "Coluna 'data_recebimento' adicionada com sucesso.<br>";
} catch (PDOException $e) {
    echo "Erro ou coluna já existe: " . $e->getMessage() . "<br>";
}
?>