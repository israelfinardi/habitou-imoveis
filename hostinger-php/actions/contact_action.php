<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/mailer.php';

verify_csrf();
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (mb_strlen($name) < 2) $errors[] = 'name';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
if (!$subject) $errors[] = 'subject';
if (mb_strlen($message) < 10) $errors[] = 'message';

if ($errors) {
    $_SESSION['contact_error'] = 'Verifique os campos destacados e tente novamente.';
    $_SESSION['contact_form'] = compact('name', 'email', 'phone', 'subject', 'message');
    redirect(base_url('fale-conosco.php'));
}

$user = current_user();
db()->prepare('INSERT INTO contact_messages (name, email, phone, subject, message, user_id) VALUES (?,?,?,?,?,?)')
    ->execute([$name, $email, $phone ?: null, $subject, $message, $user['id'] ?? null]);

// Mantém o e-mail interno como registro/backup — best-effort, não bloqueia
// o fluxo principal, que agora é o WhatsApp da Habitou Imóveis.
$adminNotify = db()->query('SELECT COALESCE(notify_email, email) FROM users WHERE role = "ADMIN" AND status = "ACTIVE" ORDER BY id LIMIT 1')->fetchColumn();
$notifyTo = $adminNotify ?: (defined('SMTP_FROM') ? SMTP_FROM : 'contato@habitou.com.br');
send_mail($notifyTo, "Novo contato: $subject", "<p><strong>" . e($name) . "</strong> (" . e($email) . ") enviou:</p><p>" . nl2br(e($message)) . '</p>');

// Redireciona pro WhatsApp da Habitou Imóveis com os dados já digitados
// pelo cliente preenchidos na mensagem, pra ele só confirmar o envio.
$waText = "Olá! Enviei uma mensagem pelo site Habitou Imóveis:\n\n"
    . "Assunto: {$subject}\nNome: {$name}\nE-mail: {$email}"
    . ($phone ? "\nTelefone: {$phone}" : '')
    . "\n\nMensagem: {$message}";
redirect('https://wa.me/5547991872805?text=' . urlencode($waText));
