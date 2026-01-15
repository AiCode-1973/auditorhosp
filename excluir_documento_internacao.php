<?php
require_once 'auth.php';
require_once 'db_config.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    try {
        // Buscar anexos para deletar arquivos físicos
        $stmt = $pdo->prepare("SELECT caminho_arquivo FROM documentos_internacao_anexos WHERE documento_id = ?");
        $stmt->execute([$id]);
        $anexos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Deletar arquivos físicos
        foreach ($anexos as $anexo) {
            if (file_exists($anexo['caminho_arquivo'])) {
                unlink($anexo['caminho_arquivo']);
            }
        }
        
        // Deletar documento (os anexos serão deletados automaticamente pelo ON DELETE CASCADE)
        $stmt = $pdo->prepare("DELETE FROM documentos_internacao WHERE id = ?");
        $stmt->execute([$id]);
        
        header("Location: documentos_internacao.php?msg=excluido");
        exit;
    } catch (PDOException $e) {
        header("Location: documentos_internacao.php?erro=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: documentos_internacao.php");
    exit;
}
?>
