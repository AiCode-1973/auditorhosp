<?php
require_once 'db_config.php';

function addOrMoveColumn($pdo, $table, $column, $definition) {
    try {
        // Tenta selecionar a coluna para ver se existe
        $pdo->query("SELECT $column FROM $table LIMIT 1");
        // Se não der erro, existe. Vamos modificar para garantir a posição.
        $pdo->exec("ALTER TABLE $table MODIFY COLUMN $column $definition");
        echo "Coluna '$column' atualizada/movida.<br>";
    } catch (PDOException $e) {
        // Se der erro, não existe. Vamos adicionar.
        try {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "Coluna '$column' adicionada.<br>";
        } catch (PDOException $e2) {
            echo "Erro ao adicionar '$column': " . $e2->getMessage() . "<br>";
        }
    }
}

addOrMoveColumn($pdo, 'internacoes', 'valor_glosado', "DECIMAL(15, 2) DEFAULT 0.00 AFTER valor_total");
addOrMoveColumn($pdo, 'internacoes', 'valor_aceito', "DECIMAL(15, 2) DEFAULT 0.00 AFTER valor_glosado");
addOrMoveColumn($pdo, 'internacoes', 'valor_faturado', "DECIMAL(15, 2) DEFAULT 0.00 AFTER valor_aceito");
addOrMoveColumn($pdo, 'internacoes', 'conta_corrigida', "VARCHAR(255) DEFAULT NULL AFTER valor_faturado");
addOrMoveColumn($pdo, 'internacoes', 'falta_nf', "VARCHAR(3) DEFAULT 'Não' AFTER conta_corrigida");
?>