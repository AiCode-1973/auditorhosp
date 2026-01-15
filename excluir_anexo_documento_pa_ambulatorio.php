<?php
require_once 'db_config.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$doc_id = isset($_GET['doc_id']) ? $_GET['doc_id'] : null;

if ($id && $doc_id) {
    try {
        // Buscar caminho do arquivo
        $stmt = $pdo->prepare("SELECT caminho_arquivo FROM documentos_pa_ambulatorio_anexos WHERE id = ?");
        $stmt->execute([$id]);
        $anexo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($anexo) {
            // Excluir arquivo físico
            if (file_exists($anexo['caminho_arquivo'])) {
                unlink($anexo['caminho_arquivo']);
            }
            
            // Excluir registro do banco
            $stmt = $pdo->prepare("DELETE FROM documentos_pa_ambulatorio_anexos WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        header('Location: documentos_pa_ambulatorio_form.php?id=' . $doc_id);
    } catch (PDOException $e) {
        header('Location: documentos_pa_ambulatorio_form.php?id=' . $doc_id . '&erro=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: documentos_pa_ambulatorio.php');
}
exit;
?>
