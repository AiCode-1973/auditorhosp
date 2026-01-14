<?php
session_start();
require_once 'db_config.php';

echo "<h2>Informações da Sessão</h2>";
echo "<pre>";
echo "Sessão iniciada: " . (session_status() == PHP_SESSION_ACTIVE ? 'Sim' : 'Não') . "\n";
echo "Usuário ID: " . (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'Não definido') . "\n";
echo "Usuário Nome: " . (isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Não definido') . "\n";
echo "Usuário Email: " . (isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : 'Não definido') . "\n";
echo "Usuário Nível: " . (isset($_SESSION['usuario_nivel']) ? $_SESSION['usuario_nivel'] : 'Não definido') . "\n";
echo "</pre>";

echo "<h3>Resultado da verificação Admin:</h3>";
if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] == 'admin') {
    echo "<p style='color: green; font-weight: bold;'>✓ Você É um administrador - Menu Admin DEVE aparecer</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Você NÃO é um administrador - Menu Admin NÃO aparece</p>";
    echo "<p>Nível atual: '" . (isset($_SESSION['usuario_nivel']) ? $_SESSION['usuario_nivel'] : 'não definido') . "'</p>";
}

echo "<h3>Todos os dados da sessão:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Usuários no banco:</h3>";
try {
    $stmt = $pdo->query("SELECT id, nome, email, nivel FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Nível</th></tr>";
    foreach ($usuarios as $u) {
        $destaque = ($u['nivel'] == 'admin') ? 'style="background-color: #90ee90;"' : '';
        echo "<tr $destaque>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['nome']}</td>";
        echo "<td>{$u['email']}</td>";
        echo "<td><strong>{$u['nivel']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

echo "<br><br><a href='index.php'>Voltar ao sistema</a>";
?>
