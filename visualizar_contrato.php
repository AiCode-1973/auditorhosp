<?php
require_once 'db_config.php';
require_once 'auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die('ID do contrato não informado.');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->execute([$id]);
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contrato || !$contrato['arquivo_contrato']) {
        die('Contrato ou arquivo não encontrado.');
    }
    
    $arquivo_path = __DIR__ . '/uploads/contratos/' . $contrato['arquivo_contrato'];
    
    if (!file_exists($arquivo_path)) {
        die('Arquivo não encontrado no servidor.');
    }
    
    // Determinar tipo MIME
    $extensao = strtolower(pathinfo($contrato['arquivo_contrato'], PATHINFO_EXTENSION));
    $mime_types = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
    
    $mime_type = $mime_types[$extensao] ?? 'application/octet-stream';
    
    // Enviar headers
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: inline; filename="' . basename($contrato['arquivo_contrato']) . '"');
    header('Content-Length: ' . filesize($arquivo_path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Enviar arquivo
    readfile($arquivo_path);
    exit;
    
} catch (PDOException $e) {
    die('Erro ao buscar contrato: ' . $e->getMessage());
}
?>
