<?php

class AvatarUploadError extends \RuntimeException {}

/**
 * Valida e salva a foto de perfil enviada em $_FILES, atualiza users.avatar_url
 * e devolve a URL pública. Compartilhado entre actions/upload_avatar.php (AJAX,
 * na guia Perfil) e cadastro.php (upload já no formulário de cadastro).
 */
function handle_avatar_upload(int $userId, array $file): string
{
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new AvatarUploadError('Erro ao enviar arquivo.');
    }
    $type = mime_content_type($file['tmp_name']) ?: $file['type'];
    if (!isset($allowed[$type])) {
        throw new AvatarUploadError('Formato não suportado. Envie JPG, PNG ou WEBP.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new AvatarUploadError('Arquivo muito grande (máx. 8MB).');
    }

    $dir = __DIR__ . '/../uploads/avatars/' . $userId;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$type];
    $destination = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new AvatarUploadError('Falha ao salvar o arquivo.');
    }

    $url = base_url('uploads/avatars/' . $userId . '/' . $filename);
    db()->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$url, $userId]);
    return $url;
}
