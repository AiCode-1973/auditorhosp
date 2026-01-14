<?php
require_once 'db_config.php';
include 'includes/header.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar'])) {
    try {
        $pdo->beginTransaction();

        // Limpar dados antigos (opcional, mas bom para testes repetidos)
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE recursos");
        $pdo->exec("TRUNCATE TABLE glosas");
        $pdo->exec("TRUNCATE TABLE faturas");
        $pdo->exec("TRUNCATE TABLE convenios");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        // 1. Inserir Convênios
        $convenios = ['Unimed', 'Bradesco Saúde', 'SulAmérica', 'Amil', 'NotreDame'];
        foreach ($convenios as $nome) {
            $stmt = $pdo->prepare("INSERT INTO convenios (nome_convenio) VALUES (?)");
            $stmt->execute([$nome]);
        }
        
        // Recuperar IDs
        $stmt = $pdo->query("SELECT id, nome_convenio FROM convenios");
        $convenios_db = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // id => nome

        // 2. Inserir Faturas, Glosas e Recursos
        // Vamos gerar dados para os últimos 3 meses
        for ($i = 0; $i < 3; $i++) {
            // Data sempre no dia 1 do mês para agrupar fácil
            $data = date('Y-m-01', strtotime("-$i months"));
            
            foreach ($convenios_db as $id_conv => $nome_conv) {
                // Faturamento aleatório entre 50k e 500k
                $valor_fatura = rand(50000, 500000) + (rand(0, 99) / 100);
                
                $stmt = $pdo->prepare("INSERT INTO faturas (convenio_id, data_competencia, valor_total) VALUES (?, ?, ?)");
                $stmt->execute([$id_conv, $data, $valor_fatura]);
                $fatura_id = $pdo->lastInsertId();

                // Chance de ter Glosa (70%)
                if (rand(0, 100) < 70) {
                    $valor_glosa = $valor_fatura * (rand(5, 20) / 100); // 5% a 20% de glosa
                    $stmt = $pdo->prepare("INSERT INTO glosas (fatura_id, valor_glosa, motivo) VALUES (?, ?, ?)");
                    $stmt->execute([$fatura_id, $valor_glosa, 'Glosa administrativa teste']);

                    // Chance de ter Recurso (80% das glosas)
                    if (rand(0, 100) < 80) {
                        $valor_recursado = $valor_glosa; // Recursa tudo
                        // Chance de Aceite (60% do recursado)
                        $valor_aceito = $valor_recursado * (rand(30, 90) / 100);

                        $stmt = $pdo->prepare("INSERT INTO recursos (fatura_id, valor_recursado, valor_aceito, data_recurso) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$fatura_id, $valor_recursado, $valor_aceito, date('Y-m-d')]);
                    }
                }
            }
        }

        $pdo->commit();
        $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Dados de teste gerados com sucesso!</div>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao gerar dados: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Gerador de Dados de Teste</h2>
        
        <?php echo $mensagem; ?>

        <p class="mb-4 text-gray-600">
            Esta ferramenta irá limpar o banco de dados atual e inserir dados fictícios para:
            <ul class="list-disc list-inside ml-4 mb-4 text-gray-600">
                <li>Convênios (Unimed, Bradesco, etc.)</li>
                <li>Faturas dos últimos 3 meses</li>
                <li>Glosas aleatórias</li>
                <li>Recursos e Aceites simulados</li>
            </ul>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Atenção: Todos os dados existentes nas tabelas serão apagados!
                        </p>
                    </div>
                </div>
            </div>
        </p>

        <form method="POST">
            <div class="flex items-center justify-between">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out" type="submit" name="gerar">
                    Gerar Dados
                </button>
                <a class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800" href="index.php">
                    Voltar para o Relatório
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
