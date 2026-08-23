<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/xml_import_service.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);

$import = get_xml_import($id, $user);
if (!$import) {
    http_response_code(404);
    exit('Importação não encontrada.');
}
if (!is_file($import['stored_path'])) {
    http_response_code(404);
    exit('O arquivo original não está mais disponível no servidor.');
}

$safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($import['original_filename'])) ?: 'importacao.xml';
header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
header('Content-Length: ' . filesize($import['stored_path']));
readfile($import['stored_path']);
