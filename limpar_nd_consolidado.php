<?php
require_once 'db_config.php';
session_start();

// Verificar autenticação
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] != 'admin') {
    die("Acesso negado. Apenas administradores podem executar este script.");
}

$mensagem = '';
$tipo_msg = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    try {
        // Deletar registros com setor vazio, NULL ou 'N/D'
        $sql = "DELETE FROM relatorio_mensal_pa_consolidado 
                WHERE setor IS NULL 
                   OR TRIM(setor) = '' 
                   OR setor = 'N/D'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $registros_deletados = $stmt->rowCount();
        
        $mensagem = "✓ Limpeza concluída com sucesso!<br>";
        $mensagem .= "Registros deletados: <strong>$registros_deletados</strong>";
        $tipo_msg = 'success';
        
    } catch (PDOException $e) {
        $mensagem = "Erro ao deletar registros: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}

// Verificar quantos registros serão afetados
try {
    $sql_count = "SELECT COUNT(*) as total 
                  FROM relatorio_mensal_pa_consolidado 
                  WHERE setor IS NULL OR TRIM(setor) = '' OR setor = 'N/D'";
    $stmt_count = $pdo->query($sql_count);
    $total_afetados = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $total_afetados = 0;
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">🧹 Limpar Registros N/D</h1>
        <p class="text-gray-600">Remove registros com setor vazio, NULL ou N/D da tabela consolidada.</p>
    </div>

    <?php if ($mensagem): ?>
        <div class="mb-6 p-4 rounded-lg <?php 
            echo $tipo_msg === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-700' : 
                ($tipo_msg === 'error' ? 'bg-red-50 border-l-4 border-red-500 text-red-700' : 
                'bg-blue-50 border-l-4 border-blue-500 text-blue-700');
        ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <!-- Card de Status -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-gray-500 text-sm font-medium">Registros a Deletar</p>
                <p class="text-4xl font-bold <?php echo $total_afetados > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                    <?php echo $total_afetados; ?>
                </p>
            </div>
            <div class="<?php echo $total_afetados > 0 ? 'bg-red-100' : 'bg-green-100'; ?> rounded-full p-4">
                <svg class="w-10 h-10 <?php echo $total_afetados > 0 ? 'text-red-600' : 'text-green-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
        </div>

        <?php if ($total_afetados > 0): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Atenção!</strong> Esta ação irá deletar permanentemente <strong><?php echo $total_afetados; ?></strong> registro(s) com setor vazio, NULL ou N/D.
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" onsubmit="return confirm('Tem certeza que deseja deletar <?php echo $total_afetados; ?> registro(s)? Esta ação não pode ser desfeita!');">
                <button type="submit" name="confirmar" value="1" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                    🗑️ Deletar <?php echo $total_afetados; ?> Registro(s)
                </button>
            </form>
        <?php else: ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            <strong>Tudo limpo!</strong> Não há registros com setor vazio, NULL ou N/D na tabela consolidada.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center">
        <a href="relatorio_mensal_pa_ambulatorio.php" class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
            ← Voltar ao Relatório
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
