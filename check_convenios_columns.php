<?php
require_once 'db_config.php';

try {
    $stmt = $pdo->query('DESCRIBE convenios');
    echo "Estrutura da tabela convenios:\n\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
