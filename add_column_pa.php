<?php
require 'db_config.php';
try {
    $pdo->exec("ALTER TABLE relatorio_mensal_pa_consolidado ADD COLUMN qtd_atendimentos INT DEFAULT 0");
    echo "Column added successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
