<?php
require_once 'db_config.php';

// Criar usuário admin inicial
$nome = "Administrador";
$email = "admin@auditorhosp.com";
$senha = "admin123"; // ALTERE ESTA SENHA APÓS O PRIMEIRO LOGIN!
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$nivel = "admin";

try {
    // Verificar se já existe um usuário admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "❌ Usuário administrador já existe!<br>";
        echo "E-mail: $email<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel, ativo) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$nome, $email, $senha_hash, $nivel]);
        
        echo "✅ <strong>Usuário administrador criado com sucesso!</strong><br><br>";
        echo "<div style='background-color: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0;'>";
        echo "<strong>📧 E-mail:</strong> $email<br>";
        echo "<strong>🔑 Senha:</strong> $senha<br><br>";
        echo "<strong style='color: #dc2626;'>⚠️ IMPORTANTE: Altere esta senha após o primeiro login!</strong>";
        echo "</div>";
        echo "<a href='login.php' style='display: inline-block; background: linear-gradient(to right, #3b82f6, #8b5cf6); color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin-top: 10px;'>Ir para Login</a>";
    }
} catch (PDOException $e) {
    echo "❌ Erro ao criar usuário: " . $e->getMessage();
}
?>
