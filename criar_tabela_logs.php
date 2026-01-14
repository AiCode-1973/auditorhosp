<?php
require_once 'db_config.php';

try {
    // Criar tabela de logs de auditoria
    $sql = "CREATE TABLE IF NOT EXISTS logs_atendimento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        usuario_nome VARCHAR(255) NOT NULL,
        atendimento_id INT,
        acao VARCHAR(50) NOT NULL,
        detalhes TEXT,
        valores_anteriores TEXT,
        valores_novos TEXT,
        ip_address VARCHAR(45),
        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        FOREIGN KEY (atendimento_id) REFERENCES internacoes(id) ON DELETE SET NULL,
        INDEX idx_atendimento (atendimento_id),
        INDEX idx_usuario (usuario_id),
        INDEX idx_data (data_hora)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "<div style='padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 5px; margin: 20px;'>";
    echo "<strong>✓ Sucesso!</strong><br>";
    echo "Tabela 'logs_atendimento' criada com sucesso!<br><br>";
    echo "<a href='index.php' style='color: #155724; text-decoration: underline;'>Voltar ao sistema</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 5px; margin: 20px;'>";
    echo "<strong>✗ Erro!</strong><br>";
    echo "Erro ao criar tabela: " . $e->getMessage();
    echo "</div>";
}
?>
