<?php
require_once 'db_config.php';

try {
    // Criar tabela de documentos
    $sql = "CREATE TABLE IF NOT EXISTS documentos_glosa (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        data_cadastro DATE NOT NULL,
        competencia DATE NOT NULL,
        convenio_id INT(11) NOT NULL,
        observacoes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (convenio_id) REFERENCES convenios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabela 'documentos_glosa' criada com sucesso!<br>";
    
    // Criar tabela de anexos dos documentos (múltiplos anexos por documento)
    $sql_anexos = "CREATE TABLE IF NOT EXISTS documentos_glosa_anexos (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        documento_id INT(11) NOT NULL,
        nome_arquivo VARCHAR(255) NOT NULL,
        caminho_arquivo VARCHAR(500) NOT NULL,
        tamanho_arquivo INT(11),
        tipo_arquivo VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (documento_id) REFERENCES documentos_glosa(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_anexos);
    echo "✅ Tabela 'documentos_glosa_anexos' criada com sucesso!<br>";
    
    echo "<br><strong>Estrutura criada com sucesso!</strong><br>";
    echo "<a href='documentos.php'>Ir para Documentos</a>";
    
} catch (PDOException $e) {
    echo "❌ Erro ao criar tabelas: " . $e->getMessage();
}
?>
