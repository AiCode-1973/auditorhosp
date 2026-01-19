<?php
require_once 'db_config.php';

echo "<h2>Corrigir Campo Setor (ENUM → VARCHAR)</h2><hr>";

echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='margin-top: 0;'>⚠ PROBLEMA IDENTIFICADO</h3>";
echo "<p>O campo <code>setor</code> está definido como <strong>ENUM('PA','AMB')</strong>.</p>";
echo "<p>Isso impede a inserção de valores como <strong>PA/NC</strong> e <strong>AMB/NC</strong>.</p>";
echo "<p>Quando tenta inserir um valor não permitido, o MySQL converte para vazio, causando erro de chave duplicada.</p>";
echo "</div>";

if (isset($_POST['corrigir'])) {
    try {
        echo "<h3>Executando Correção:</h3>";
        
        // 1. Remover constraint única temporariamente
        echo "<p>1. Removendo constraint única...</p>";
        $sql_drop_constraint = "ALTER TABLE relatorio_mensal_pa_consolidado DROP INDEX unique_comp_conv_setor";
        $pdo->exec($sql_drop_constraint);
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>✓ Constraint removida</div>";
        
        // 2. Alterar campo de ENUM para VARCHAR
        echo "<p>2. Alterando campo setor de ENUM para VARCHAR(20)...</p>";
        $sql_alter = "ALTER TABLE relatorio_mensal_pa_consolidado 
                      MODIFY COLUMN setor VARCHAR(20) NOT NULL DEFAULT 'PA'";
        $pdo->exec($sql_alter);
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>✓ Campo alterado para VARCHAR(20)</div>";
        
        // 3. Recriar constraint única
        echo "<p>3. Recriando constraint única...</p>";
        $sql_create_constraint = "ALTER TABLE relatorio_mensal_pa_consolidado 
                                  ADD UNIQUE KEY unique_comp_conv_setor (competencia, convenio_id, setor)";
        $pdo->exec($sql_create_constraint);
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>✓ Constraint recriada</div>";
        
        echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='margin-top: 0; color: #155724;'>✓✓✓ CORREÇÃO CONCLUÍDA COM SUCESSO! ✓✓✓</h3>";
        echo "<p>O campo <code>setor</code> agora é <strong>VARCHAR(20)</strong> e aceita:</p>";
        echo "<ul>";
        echo "<li>PA</li>";
        echo "<li>AMB</li>";
        echo "<li>PA/NC</li>";
        echo "<li>AMB/NC</li>";
        echo "<li>Qualquer outro valor até 20 caracteres</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='diagnostico_e_corrige_automatico.php' style='display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Executar Consolidação Novamente</a>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; border: 2px solid #dc3545; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✗ ERRO:</strong><br>";
        echo $e->getMessage();
        echo "</div>";
    }
} else {
    // Mostrar estrutura atual
    echo "<h3>Estrutura Atual do Campo Setor:</h3>";
    $sql_desc = "DESCRIBE relatorio_mensal_pa_consolidado setor";
    $stmt_desc = $pdo->query($sql_desc);
    $campo = $stmt_desc->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    echo "<tr>";
    echo "<td>" . $campo['Field'] . "</td>";
    echo "<td style='background: #ffcccc;'><strong>" . $campo['Type'] . "</strong></td>";
    echo "<td>" . $campo['Null'] . "</td>";
    echo "<td>" . $campo['Default'] . "</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<div style='background: #e7f3ff; border: 2px solid #004085; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='margin-top: 0;'>Solução:</h3>";
    echo "<p>Alterar o campo <code>setor</code> de <strong>ENUM('PA','AMB')</strong> para <strong>VARCHAR(20)</strong>.</p>";
    echo "<p>Isso permitirá armazenar valores como PA, AMB, PA/NC, AMB/NC sem problemas.</p>";
    echo "</div>";
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='corrigir' value='1' style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 18px; font-weight: bold;' onclick='return confirm(\"Tem certeza? Esta operação vai alterar a estrutura da tabela.\");'>";
    echo "🔧 CORRIGIR AGORA (Alterar ENUM → VARCHAR)";
    echo "</button>";
    echo "</form>";
}

echo "<br><br><a href='verificar_constraint.php' style='display: inline-block; background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Voltar</a>";
?>
