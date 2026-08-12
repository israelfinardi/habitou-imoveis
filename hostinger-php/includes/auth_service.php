<?php
require_once __DIR__ . '/mailer.php';

class AuthServiceError extends \RuntimeException {}

function register_user(string $firstName, string $lastName, string $email, string $phone, string $password): int
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) {
        throw new AuthServiceError('Este e-mail já está cadastrado.');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, phone, password_hash, role, status) VALUES (?,?,?,?,?,"USER","ACTIVE")');
    $stmt->execute([$firstName, $lastName, $email, $phone ?: null, $hash]);
    return (int) $pdo->lastInsertId();
}

function authenticate_user(string $email, string $password): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        throw new AuthServiceError('E-mail ou senha inválidos.');
    }
    if ($user['status'] !== 'ACTIVE') {
        throw new AuthServiceError('Esta conta está inativa.');
    }
    if (!password_verify($password, $user['password_hash'])) {
        throw new AuthServiceError('E-mail ou senha inválidos.');
    }
    $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
    return $user;
}

function request_password_reset(string $email): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, first_name, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        return; // nunca revela se o e-mail existe
    }

    $rawToken = random_token(32);
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?,?,?)')
        ->execute([$user['id'], $tokenHash, $expiresAt]);

    $resetUrl = base_url('redefinir-senha.php?token=' . $rawToken);
    send_mail($user['email'], 'Recuperação de senha — ' . APP_NAME, "
        <p>Olá, {$user['first_name']}.</p>
        <p>Recebemos uma solicitação para redefinir sua senha. Este link expira em 1 hora e só pode ser usado uma vez:</p>
        <p><a href=\"{$resetUrl}\">{$resetUrl}</a></p>
        <p>Se você não solicitou, ignore este e-mail.</p>
    ");
}

function reset_password(string $rawToken, string $newPassword): void
{
    $pdo = db();
    $tokenHash = hash('sha256', $rawToken);
    $stmt = $pdo->prepare('SELECT * FROM password_reset_tokens WHERE token_hash = ?');
    $stmt->execute([$tokenHash]);
    $token = $stmt->fetch();

    if (!$token || $token['used_at'] || strtotime($token['expires_at']) < time()) {
        throw new AuthServiceError('Este link de redefinição é inválido ou expirou.');
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $token['user_id']]);
    $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([$token['id']]);
    $pdo->commit();
}

function change_password(int $userId, string $currentPassword, string $newPassword): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($currentPassword, $hash)) {
        throw new AuthServiceError('Senha atual incorreta.');
    }
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, $userId]);
}
