<?php
require_once 'db_config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS relatorio_mensal_pa_consolidado (
        id INT AUTO_INCREMENT PRIMARY KEY,
        competencia DATE NOT NULL,
        convenio_id INT NOT NULL,
        setor ENUM('PA', 'AMB') NOT NULL DEFAULT 'PA',
        valor_inicial DECIMAL(15,2) DEFAULT 0.00,
        valor_retirado DECIMAL(15,2) DEFAULT 0.00,
        valor_acrescentado DECIMAL(15,2) DEFAULT 0.00,
        valor_final DECIMAL(15,2) DEFAULT 0.00,
        valor_glosado DECIMAL(15,2) DEFAULT 0.00,
        valor_aceito DECIMAL(15,2) DEFAULT 0.00,
        valor_faturado DECIMAL(15,2) DEFAULT 0.00,
        perc_retirado DECIMAL(5,2) DEFAULT 0.00,
        perc_acrescentado DECIMAL(5,2) DEFAULT 0.00,
        perc_glosado DECIMAL(5,2) DEFAULT 0.00,
        perc_aceito DECIMAL(5,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (convenio_id) REFERENCES convenios(id),
        UNIQUE KEY unique_comp_conv_setor (competencia, convenio_id, setor)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Tabela relatorio_mensal_pa_consolidado criada com sucesso!";

} catch (PDOException $e) {
    die("Erro ao criar tabela: " . $e->getMessage());
}
?>
