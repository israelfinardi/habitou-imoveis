<?php
require_once __DIR__ . '/../includes/bootstrap.php';

verify_csrf();
$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['newsletter_error'] = 'Digite um e-mail válido.';
    redirect(base_url((string) ($_POST['voltar'] ?? '/')));
}

db()->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?) ON CONFLICT (email) DO NOTHING')->execute([$email]);

$_SESSION['newsletter_success'] = 'Inscrição confirmada! Você vai receber as novidades no seu e-mail.';
redirect(base_url((string) ($_POST['voltar'] ?? '/')));
