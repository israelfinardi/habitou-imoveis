<?php
/**
 * Criptografia simétrica (AES-256-GCM) para blindar valores sensíveis em
 * repouso no banco, disponível para qualquer campo que precise disso no
 * futuro. A chave nunca fica no código: é derivada (SHA-256) do segredo
 * "encryption" gerado e guardado em
 * data/.secrets.php na primeira execução, pelo mesmo mecanismo já usado
 * para AUTH_SECRET/CRON_SECRET (habitou_secret(), em config/config.php).
 *
 * Formato armazenado: base64(iv[12] . tag[16] . ciphertext) — autocontido,
 * então não precisa de coluna extra pra guardar o IV.
 */

function encryption_key(): string
{
    static $key = null;
    if ($key === null) {
        $key = hash('sha256', habitou_secret('encryption'), true);
    }
    return $key;
}

function encrypt_value(?string $plaintext): ?string
{
    if ($plaintext === null || $plaintext === '') {
        return $plaintext;
    }
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        throw new \RuntimeException('Falha ao criptografar o valor.');
    }
    return base64_encode($iv . $tag . $ciphertext);
}

function decrypt_value(?string $encoded): ?string
{
    if ($encoded === null || $encoded === '') {
        return $encoded;
    }
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 29) {
        // Não é um valor criptografado por essa função (ex.: dado legado
        // gravado antes de existir criptografia) — devolve como veio em vez
        // de quebrar a página, mas isso não deveria acontecer em dados
        // novos.
        return $encoded;
    }
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return $plaintext === false ? null : $plaintext;
}
