<?php
require_once 'db_config.php';
include 'includes/header.php';

// Apenas administradores podem acessar
if ($_SESSION['usuario_nivel'] != 'admin') {
    header('Location: index.php');
    exit();
}

$mensagem = '';
$erro = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar') {
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? 'usuario';
        
        if (empty($nome) || empty($email) || empty($senha)) {
            $erro = 'Preencha todos os campos obrigatórios.';
        } else {
            try {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel, ativo) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$nome, $email, $senha_hash, $nivel]);
                $mensagem = 'Usuário criado com sucesso!';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erro = 'E-mail já cadastrado.';
                } else {
                    $erro = 'Erro ao criar usuário: ' . $e->getMessage();
                }
            }
        }
    } elseif ($acao === 'editar') {
        $id = $_POST['id'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? 'usuario';
        $ativo = $_POST['ativo'] ?? 0;
        
        if (empty($nome) || empty($email)) {
            $erro = 'Nome e e-mail são obrigatórios.';
        } else {
            try {
                if (!empty($senha)) {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, nivel = ?, ativo = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $senha_hash, $nivel, $ativo, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, nivel = ?, ativo = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $nivel, $ativo, $id]);
                }
                $mensagem = 'Usuário atualizado com sucesso!';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erro = 'E-mail já cadastrado.';
                } else {
                    $erro = 'Erro ao atualizar usuário: ' . $e->getMessage();
                }
            }
        }
    } elseif ($acao === 'excluir') {
        $id = $_POST['id'] ?? '';
        if ($id == $_SESSION['usuario_id']) {
            $erro = 'Você não pode excluir sua própria conta.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
                $mensagem = 'Usuário excluído com sucesso!';
            } catch (PDOException $e) {
                $erro = 'Erro ao excluir usuário: ' . $e->getMessage();
            }
        }
    }
}

// Buscar usuários
try {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nome");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
    $erro = 'Erro ao buscar usuários: ' . $e->getMessage();
}
?>

<div class="max-w-7xl mx-auto">
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Gerenciar Usuários</h2>
            <button onclick="abrirModalCriar()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                + Novo Usuário
            </button>
        </div>

        <?php if ($mensagem): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($mensagem); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($erro); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">E-mail</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Nível</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Último Acesso</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($usuario['nome']); ?>
                                <?php if ($usuario['id'] == $_SESSION['usuario_id']): ?>
                                    <span class="ml-2 text-xs text-blue-600">(Você)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($usuario['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($usuario['nivel'] == 'admin'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Administrador
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Usuário
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($usuario['ativo']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Ativo
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Inativo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Nunca'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button onclick='abrirModalEditar(<?php echo json_encode($usuario); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                    <button onclick="confirmarExclusao(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES); ?>')" class="text-red-600 hover:text-red-900" title="Excluir">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Criar Usuário -->
<div id="modalCriar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Novo Usuário</h3>
            <button onclick="fecharModalCriar()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="acao" value="criar">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nome *</label>
                <input type="text" name="nome" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">E-mail *</label>
                <input type="email" name="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Senha *</label>
                <input type="password" name="senha" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nível</label>
                <select name="nivel" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="usuario">Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="fecharModalCriar()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Cancelar
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Criar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Usuário -->
<div id="modalEditar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Editar Usuário</h3>
            <button onclick="fecharModalEditar()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nome *</label>
                <input type="text" name="nome" id="edit_nome" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">E-mail *</label>
                <input type="email" name="email" id="edit_email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nova Senha (deixe em branco para manter)</label>
                <input type="password" name="senha" id="edit_senha" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nível</label>
                <select name="nivel" id="edit_nivel" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="usuario">Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                <select name="ativo" id="edit_ativo" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="fecharModalEditar()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Cancelar
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Form Excluir (oculto) -->
<form id="formExcluir" method="POST" action="" style="display: none;">
    <input type="hidden" name="acao" value="excluir">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function abrirModalCriar() {
    document.getElementById('modalCriar').classList.remove('hidden');
}

function fecharModalCriar() {
    document.getElementById('modalCriar').classList.add('hidden');
}

function abrirModalEditar(usuario) {
    document.getElementById('edit_id').value = usuario.id;
    document.getElementById('edit_nome').value = usuario.nome;
    document.getElementById('edit_email').value = usuario.email;
    document.getElementById('edit_senha').value = '';
    document.getElementById('edit_nivel').value = usuario.nivel;
    document.getElementById('edit_ativo').value = usuario.ativo;
    document.getElementById('modalEditar').classList.remove('hidden');
}

function fecharModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
}

function confirmarExclusao(id, nome) {
    if (confirm('Tem certeza que deseja excluir o usuário "' + nome + '"?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('formExcluir').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
