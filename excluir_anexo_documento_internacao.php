<?php
require_once 'auth.php';
require_once 'db_config.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$doc_id = isset($_GET['doc_id']) ? $_GET['doc_id'] : null;

if ($id && $doc_id) {
    try {
        // Buscar caminho do arquivo
        $stmt = $pdo->prepare("SELECT caminho_arquivo FROM documentos_internacao_anexos WHERE id = ?");
        $stmt->execute([$id]);
        $anexo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($anexo) {
            // Deletar arquivo físico
            if (file_exists($anexo['caminho_arquivo'])) {
                unlink($anexo['caminho_arquivo']);
            }
            
            // Deletar registro do banco
            $stmt = $pdo->prepare("DELETE FROM documentos_internacao_anexos WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        header("Location: documentos_internacao_form.php?id=" . $doc_id);
        exit;
    } catch (PDOException $e) {
        header("Location: documentos_internacao_form.php?id=" . $doc_id . "&erro=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: documentos_internacao.php");
    exit;
}
?>
