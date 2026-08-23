<?php
/**
 * Upload do contrato assinado (PDF) — enviado pelo cliente ou pela
 * corretora/imobiliária depois de imprimir/assinar o PDF gerado por
 * includes/contract_pdf.php. Form comum (não AJAX) + flash de sessão,
 * mesmo padrão de actions/newsletter_action.php — não precisa de JS pra um
 * upload de arquivo único sem preview.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/contract_service.php';

$user = require_login();
verify_csrf();

$id = (int) ($_POST['contractId'] ?? 0);
$back = base_url('contrato.php?id=' . $id);

$stmt = db()->prepare('SELECT * FROM contracts WHERE id = ?');
$stmt->execute([$id]);
$contract = $stmt->fetch();
if (!$contract || !can_view_contract($user, $contract)) {
    http_response_code(403);
    exit('Você não tem acesso a este contrato.');
}

$file = $_FILES['document'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['contract_error'] = 'Erro ao enviar arquivo.';
    redirect($back);
}
$type = mime_content_type($file['tmp_name']) ?: $file['type'];
if ($type !== 'application/pdf') {
    $_SESSION['contract_error'] = 'Envie o contrato assinado em PDF.';
    redirect($back);
}
if ($file['size'] > UPLOAD_MAX_BYTES) {
    $_SESSION['contract_error'] = 'Arquivo muito grande (máx. 8MB).';
    redirect($back);
}

$dir = __DIR__ . '/../uploads/contracts/' . $id;
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$filename = bin2hex(random_bytes(12)) . '.pdf';
if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
    $_SESSION['contract_error'] = 'Falha ao salvar o arquivo.';
    redirect($back);
}
$url = base_url('uploads/contracts/' . $id . '/' . $filename);

try {
    set_contract_signed_document($id, $user, $url);
    $_SESSION['contract_success'] = 'Documento assinado enviado com sucesso.';
} catch (\Throwable $e) {
    $_SESSION['contract_error'] = $e->getMessage();
}
redirect($back);
