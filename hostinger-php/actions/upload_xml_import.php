<?php
/**
 * Upload do XML de imóveis (padrão VRSync) + processamento síncrono, tudo
 * na mesma requisição — sem fila/cron, mesmo padrão do resto do site. Form
 * comum (não AJAX) + flash de sessão, como actions/newsletter_action.php.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/xml_import_service.php';

$user = require_login();
verify_csrf();
@set_time_limit(0);

$back = base_url('importar-xml.php');

$file = $_FILES['xmlFile'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['xml_import_error'] = 'Erro ao enviar arquivo.';
    redirect($back);
}
if ($file['size'] > XML_IMPORT_MAX_FILE_BYTES) {
    $_SESSION['xml_import_error'] = 'Arquivo muito grande (máx. 20MB).';
    redirect($back);
}
$mime = mime_content_type($file['tmp_name']) ?: $file['type'];
$isXml = in_array($mime, ['text/xml', 'application/xml'], true) || str_ends_with(mb_strtolower($file['name']), '.xml');
if (!$isXml) {
    $_SESSION['xml_import_error'] = 'Envie um arquivo XML.';
    redirect($back);
}

$dir = __DIR__ . '/../uploads/xml-imports/' . $user['id'];
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$storedFilename = date('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.xml';
$storedPath = $dir . '/' . $storedFilename;
if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
    $_SESSION['xml_import_error'] = 'Falha ao salvar o arquivo.';
    redirect($back);
}

try {
    $importId = create_xml_import($user, $file['name'], $storedPath);
    $import = process_xml_import($importId, $user);

    if ($import['status'] === 'ERROR') {
        $_SESSION['xml_import_error'] = 'Falha ao importar: ' . ($import['error_summary'] ?: 'erro desconhecido.');
    } else {
        $parts = [];
        if ($import['total_created']) $parts[] = $import['total_created'] . ' criado(s)';
        if ($import['total_updated']) $parts[] = $import['total_updated'] . ' atualizado(s)';
        if ($import['total_deactivated']) $parts[] = $import['total_deactivated'] . ' arquivado(s)';
        if ($import['total_errors']) $parts[] = $import['total_errors'] . ' com erro';
        $_SESSION['xml_import_success'] = 'Importação concluída: ' . ($parts ? implode(', ', $parts) . '.' : 'nenhum imóvel processado.');
    }
} catch (\Throwable $e) {
    $_SESSION['xml_import_error'] = $e->getMessage();
}

redirect($back);
