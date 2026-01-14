<?php
require_once 'db_config.php';

try {
    $sql = "ALTER TABLE recursos ADD COLUMN valor_recebido DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER valor_aceito";
    $pdo->exec($sql);
    echo "Coluna 'valor_recebido' adicionada com sucesso na tabela 'recursos'.<br>";
} catch (PDOException $e) {
    echo "Erro ou coluna já existe: " . $e->getMessage() . "<br>";
}
?>