<?php
require_once 'db_config.php';

try {
    $sql = "ALTER TABLE internacoes 
            ADD COLUMN guia_paciente VARCHAR(100) AFTER convenio_id,
            ADD COLUMN data_entrada DATE AFTER guia_paciente,
            ADD COLUMN data_saida DATE AFTER data_entrada,
            ADD COLUMN valor_inicial DECIMAL(15, 2) DEFAULT 0.00 AFTER data_saida,
            ADD COLUMN valor_retirado DECIMAL(15, 2) DEFAULT 0.00 AFTER valor_inicial,
            ADD COLUMN valor_acrescentado DECIMAL(15, 2) DEFAULT 0.00 AFTER valor_retirado,
            ADD COLUMN valor_total DECIMAL(15, 2) DEFAULT 0.00 AFTER valor_acrescentado";
    $pdo->exec($sql);
    echo "Novas colunas adicionadas com sucesso.<br>";
} catch (PDOException $e) {
    echo "Erro ou colunas já existem: " . $e->getMessage() . "<br>";
}
?>