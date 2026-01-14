<?php
require_once 'db_config.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$nome_convenio = '';
$titulo = 'Novo Convênio';
$mensagem = '';

// Se for edição, busca os dados
if ($id) {
    $titulo = 'Editar Convênio';
    try {
        $stmt = $pdo->prepare("SELECT * FROM convenios WHERE id = ?");
        $stmt->execute([$id]);
        $convenio = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($convenio) {
            $nome_convenio = $convenio['nome_convenio'];
        } else {
            echo "<script>window.location.href='convenios.php';</script>";
            exit;
        }
    } catch (PDOException $e) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao buscar dados: " . $e->getMessage() . "</div>";
    }
}

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_convenio = trim($_POST['nome_convenio']);
    
    if (empty($nome_convenio)) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>O nome do convênio é obrigatório.</div>";
    } else {
        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE convenios SET nome_convenio = ? WHERE id = ?");
                $stmt->execute([$nome_convenio, $id]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO convenios (nome_convenio) VALUES (?)");
                $stmt->execute([$nome_convenio]);
            }
            // Redirecionar para a lista
            echo "<script>window.location.href='convenios.php';</script>";
            exit;
        } catch (PDOException $e) {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Erro ao salvar: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="max-w-lg mx-auto mt-10">
    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800"><?php echo $titulo; ?></h2>
            <a href="convenios.php" class="text-gray-600 hover:text-gray-800">
                &larr; Voltar
            </a>
        </div>
        
        <?php echo $mensagem; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="nome_convenio">
                    Nome do Convênio
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="nome_convenio" name="nome_convenio" type="text" placeholder="Ex: Unimed" value="<?php echo htmlspecialchars($nome_convenio); ?>" required>
            </div>
            
            <div class="flex items-center justify-end">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out" type="submit">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
