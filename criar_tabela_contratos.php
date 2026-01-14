<?php
require_once 'db_config.php';

try {
    echo "<h2>Criando tabela de Contratos</h2><br>";
    
    // Criar tabela contratos
    $sql = "CREATE TABLE IF NOT EXISTS contratos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        convenio_id INT NOT NULL,
        numero_contrato VARCHAR(100) NOT NULL,
        data_inicio DATE NOT NULL,
        data_fim DATE NULL,
        valor_contrato DECIMAL(15, 2) NULL,
        arquivo_contrato VARCHAR(255) NULL,
        data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
        observacoes TEXT NULL,
        ativo TINYINT(1) DEFAULT 1,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        usuario_criacao INT NULL,
        FOREIGN KEY (convenio_id) REFERENCES convenios(id) ON DELETE RESTRICT,
        INDEX idx_convenio (convenio_id),
        INDEX idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "✓ Tabela 'contratos' criada com sucesso!<br><br>";
    
    // Verificar se a pasta de uploads existe
    $pasta_contratos = __DIR__ . '/uploads/contratos';
    if (!file_exists($pasta_contratos)) {
        mkdir($pasta_contratos, 0755, true);
        echo "✓ Pasta 'uploads/contratos' criada com sucesso!<br>";
    } else {
        echo "✓ Pasta 'uploads/contratos' já existe.<br>";
    }
    
    echo "<br><strong>✅ Módulo de Contratos configurado com sucesso!</strong><br>";
    echo "<br><a href='contratos.php'>→ Ir para Contratos</a>";
    
} catch (PDOException $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage();
}
?>
