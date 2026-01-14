<?php
require_once 'db_config.php';

try {
    // Tabela Convenios
    $sql = "CREATE TABLE IF NOT EXISTS convenios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_convenio VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "Tabela 'convenios' verificada/criada com sucesso.<br>";

    // Tabela Faturas
    $sql = "CREATE TABLE IF NOT EXISTS faturas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        convenio_id INT NOT NULL,
        data_competencia DATE NOT NULL,
        valor_total DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (convenio_id) REFERENCES convenios(id)
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "Tabela 'faturas' verificada/criada com sucesso.<br>";

    // Tabela Glosas
    $sql = "CREATE TABLE IF NOT EXISTS glosas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fatura_id INT NOT NULL,
        valor_glosa DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        motivo TEXT,
        FOREIGN KEY (fatura_id) REFERENCES faturas(id)
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "Tabela 'glosas' verificada/criada com sucesso.<br>";

    // Tabela Recursos
    $sql = "CREATE TABLE IF NOT EXISTS recursos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fatura_id INT NOT NULL,
        valor_recursado DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        valor_aceito DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        valor_recebido DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        data_recurso DATE,
        FOREIGN KEY (fatura_id) REFERENCES faturas(id)
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "Tabela 'recursos' verificada/criada com sucesso.<br>";

    // Tabela Atendimento
    $sql = "CREATE TABLE IF NOT EXISTS internacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data_recebimento DATE,
        paciente VARCHAR(255) NOT NULL,
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
        conta_corrigida VARCHAR(255) DEFAULT NULL,
        falta_nf VARCHAR(3) DEFAULT 'Não',
        status VARCHAR(50) DEFAULT 'Em Aberto',
        observacoes TEXT,
        FOREIGN KEY (convenio_id) REFERENCES convenios(id)
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "Tabela 'internacoes' verificada/criada com sucesso.<br>";

    // Tabela Relatório Mensal Consolidado
    $sql = "CREATE TABLE IF NOT EXISTS relatorio_mensal_consolidado (
        id INT AUTO_INCREMENT PRIMARY KEY,
        competencia DATE NOT NULL,
        convenio_id INT NOT NULL,
        valor_inicial DECIMAL(15, 2) DEFAULT 0.00,
        valor_retirado DECIMAL(15, 2) DEFAULT 0.00,
        valor_acrescentado DECIMAL(15, 2) DEFAULT 0.00,
        valor_final DECIMAL(15, 2) DEFAULT 0.00,
        valor_glosado DECIMAL(15, 2) DEFAULT 0.00,
        valor_aceito DECIMAL(15, 2) DEFAULT 0.00,
        perc_retirado DECIMAL(5, 2) DEFAULT 0.00,
        perc_acrescentado DECIMAL(5, 2) DEFAULT 0.00,
        perc_glosado DECIMAL(5, 2) DEFAULT 0.00,
        perc_aceito DECIMAL(5, 2) DEFAULT 0.00,
        FOREIGN KEY (convenio_id) REFERENCES convenios(id),
        UNIQUE KEY unique_competencia_convenio (competencia, convenio_id)
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "Tabela 'relatorio_mensal_consolidado' verificada/criada com sucesso.<br>";

    // Tabela Usuários
    $sql = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        nivel VARCHAR(50) DEFAULT 'usuario',
        ativo TINYINT(1) DEFAULT 1,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ultimo_acesso DATETIME NULL
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "Tabela 'usuarios' verificada/criada com sucesso.<br>";

    echo "<br><strong>Estrutura do banco de dados configurada com sucesso!</strong>";

} catch (PDOException $e) {
    echo "Erro ao criar tabelas: " . $e->getMessage();
}
?>
