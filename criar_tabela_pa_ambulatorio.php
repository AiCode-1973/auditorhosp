<?php
require_once 'db_config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS pa_ambulatorio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data_recebimento DATE,
        setor VARCHAR(10) NOT NULL,
        convenio_id INT NOT NULL,
        guia_paciente VARCHAR(100),
        data_entrada DATE,
        data_saida DATE,
        valor_inicial DECIMAL(15, 2) DEFAULT 0.00,
        valor_retirado DECIMAL(15, 2) DEFAULT 0.00,
        valor_acrescentado DECIMAL(15, 2) DEFAULT 0.00,
        valor_total DECIMAL(15, 2) DEFAULT 0.00,
        valor_glosado DECIMAL(15, 2) DEFAULT 0.00,
        valor_aceito DECIMAL(15, 2) DEFAULT 0.00,
        valor_faturado DECIMAL(15, 2) DEFAULT 0.00,
        falta_nf VARCHAR(3) DEFAULT 'Não',
        status VARCHAR(50) DEFAULT 'Em Aberto',
        observacoes TEXT,
        competencia DATE NULL,
        FOREIGN KEY (convenio_id) REFERENCES convenios(id)
    ) ENGINE=InnoDB;";
    
    $pdo->exec($sql);
    echo "✅ Tabela 'pa_ambulatorio' criada com sucesso!<br>";
    echo "<a href='pa_ambulatorio.php'>Ir para Gestão PA/Ambulatório</a>";
} catch (PDOException $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage();
}
?>
