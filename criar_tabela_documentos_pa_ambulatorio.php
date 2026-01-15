<?php
require_once 'db_config.php';

try {
    // Criar tabela principal de documentos
    $sql_documentos = "CREATE TABLE IF NOT EXISTS documentos_pa_ambulatorio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data_cadastro DATE NOT NULL,
        competencia DATE NOT NULL,
        convenio_id INT NOT NULL,
        setor VARCHAR(50) NOT NULL,
        observacoes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (convenio_id) REFERENCES convenios(id)
    )";
    
    $pdo->exec($sql_documentos);
    echo "Tabela 'documentos_pa_ambulatorio' criada com sucesso!<br>";
    
    // Criar tabela de anexos
    $sql_anexos = "CREATE TABLE IF NOT EXISTS documentos_pa_ambulatorio_anexos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        documento_id INT NOT NULL,
        nome_arquivo VARCHAR(255) NOT NULL,
        caminho_arquivo VARCHAR(500) NOT NULL,
        tamanho_arquivo INT NOT NULL,
        tipo_arquivo VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (documento_id) REFERENCES documentos_pa_ambulatorio(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql_anexos);
    echo "Tabela 'documentos_pa_ambulatorio_anexos' criada com sucesso!<br>";
    
    echo "<br><strong>Tabelas criadas com sucesso!</strong><br>";
    echo "<a href='documentos_pa_ambulatorio.php'>Ir para Documentos PA/Ambulatório</a>";
    
} catch (PDOException $e) {
    echo "Erro ao criar tabelas: " . $e->getMessage();
}
?>
