<?php
require_once 'db_config.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    try {
        // Buscar anexos para excluir os arquivos físicos
        $stmt = $pdo->prepare("SELECT caminho_arquivo FROM documentos_pa_ambulatorio_anexos WHERE documento_id = ?");
        $stmt->execute([$id]);
        $anexos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Excluir arquivos físicos
        foreach ($anexos as $anexo) {
            if (file_exists($anexo['caminho_arquivo'])) {
                unlink($anexo['caminho_arquivo']);
            }
        }
        
        // Excluir documento (anexos serão excluídos automaticamente por CASCADE)
        $stmt = $pdo->prepare("DELETE FROM documentos_pa_ambulatorio WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: documentos_pa_ambulatorio.php?msg=excluido');
    } catch (PDOException $e) {
        header('Location: documentos_pa_ambulatorio.php?erro=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: documentos_pa_ambulatorio.php');
}
exit;
?>
