<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/contract_service.php';
require_once __DIR__ . '/../includes/contract_pdf.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);

try {
    $contract = get_contract($id, $user);
} catch (\Throwable $e) {
    http_response_code(403);
    exit(e($e->getMessage()));
}
if (!$contract) {
    http_response_code(404);
    exit('Contrato não encontrado.');
}

stream_contract_pdf($contract);
