<?php
require_once 'db_config.php';

try {
    // Tentar remover as colunas
    $pdo->exec("ALTER TABLE internacoes DROP COLUMN senha_autorizacao");
    echo "Coluna senha_autorizacao removida.<br>";
} catch (Exception $e) {
    echo "Info: " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE internacoes DROP COLUMN data_internacao");
    echo "Coluna data_internacao removida.<br>";
} catch (Exception $e) {
    echo "Info: " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE internacoes DROP COLUMN data_alta");
    echo "Coluna data_alta removida.<br>";
} catch (Exception $e) {
    echo "Info: " . $e->getMessage() . "<br>";
}

echo "Atualização da tabela concluída.";
?>