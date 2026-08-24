<?php
/**
 * Endpoint leve usado pelo modal de "Entrar ou cadastrar-se"
 * (assets/js/auth-modal.js): recebe o e-mail digitado na primeira etapa e
 * diz se já existe conta com ele, pra decidir se o modal segue pro fluxo
 * de login (pede senha) ou de cadastro (próximas etapas). Não é uma
 * exposição nova de dado — cadastro.php já revela a mesma informação hoje
 * (erro "Este e-mail já está cadastrado." ao tentar registrar de novo).
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
verify_csrf();
rate_limit_enforce('check_email_ip', client_ip(), 30, 600); // 30 verificações / 10 min por IP
rate_limit_hit('check_email_ip', client_ip());

$email = trim(strtolower($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'E-mail inválido.']);
    exit;
}

$stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
$stmt->execute([$email]);
echo json_encode(['exists' => (bool) $stmt->fetchColumn()]);
