<?php
/**
 * Exclui uma importação de XML: apaga o arquivo original, todos os imóveis
 * criados por ela (com fotos) e o próprio registro. Mesmo padrão de
 * form comum + flash de sessão do restante do site.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/xml_import_service.php';

$user = require_login();
verify_csrf();

$back = $user['role'] === 'ADMIN' && !empty($_POST['from_admin'])
    ? base_url('admin/importacoes-xml.php')
    : base_url('importar-xml.php');

$id = (int) ($_POST['id'] ?? 0);

try {
    delete_xml_import($id, $user);
    $_SESSION['xml_import_success'] = 'Importação e imóveis relacionados excluídos.';
} catch (\Throwable $e) {
    $_SESSION['xml_import_error'] = $e->getMessage();
}

redirect($back);
