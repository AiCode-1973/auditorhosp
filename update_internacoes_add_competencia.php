<?php
require_once 'db_config.php';

try {
    $pdo->exec("ALTER TABLE internacoes ADD COLUMN competencia DATE NULL AFTER data_recebimento");
    echo "Coluna 'competencia' adicionada com sucesso na tabela 'internacoes'.";
} catch (PDOException $e) {
    echo "Erro ao adicionar coluna: " . $e->getMessage();
}
?>