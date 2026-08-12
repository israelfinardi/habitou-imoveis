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

send_mail(defined('SMTP_FROM') ? SMTP_FROM : 'contato@habitou.com.br', "Novo contato: $subject", "<p><strong>" . e($name) . "</strong> (" . e($email) . ") enviou:</p><p>" . nl2br(e($message)) . '</p>');

$_SESSION['contact_success'] = 'Mensagem enviada! Normalmente respondemos em até 2 horas úteis.';
redirect(base_url('fale-conosco.php'));
