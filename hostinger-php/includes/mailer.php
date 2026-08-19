<?php
/**
 * Envio de e-mail simples, em ordem de preferência: 1) API de e-mail da
 * Hostinger (HOSTINGER_EMAIL_API_TOKEN) — a mais confiável, entrega direto
 * na caixa via HTTPS; 2) SMTP se configurado (SMTP_HOST); 3) mail() nativo
 * do PHP como último recurso (mais propenso a cair em spam por não ter
 * SPF/DKIM alinhados). Se nada funcionar, só registra no log — nunca
 * quebra o fluxo do usuário.
 */
function send_mail(string $to, string $subject, string $html): bool
{
    if (defined('HOSTINGER_EMAIL_API_TOKEN') && HOSTINGER_EMAIL_API_TOKEN && defined('HOSTINGER_MAILBOX_RESOURCE_ID') && HOSTINGER_MAILBOX_RESOURCE_ID) {
        try {
            return hostinger_email_api_send($to, $subject, $html);
        } catch (\Throwable $e) {
            error_log('[mailer] Falha na API de e-mail da Hostinger: ' . $e->getMessage());
        }
    }

    if (defined('SMTP_HOST') && SMTP_HOST) {
        try {
            return smtp_send(SMTP_HOST, (int) SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM, SMTP_FROM_NAME, $to, $subject, $html);
        } catch (\Throwable $e) {
            error_log('[mailer] Falha SMTP: ' . $e->getMessage());
        }
    }

    if (function_exists('mail')) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= 'From: ' . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : APP_NAME) . ' <' . (defined('SMTP_FROM') ? SMTP_FROM : 'no-reply@localhost') . ">\r\n";
        if (@mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers)) {
            return true;
        }
    }

    error_log("[mailer] E-mail não enviado (nenhum transporte disponível) para {$to}: {$subject}");
    return false;
}

/**
 * Envia via https://developers.hostinger.com/ (produto de e-mail — mesma
 * API do painel hPanel > E-mails). Bearer token, HTTPS puro: mais provável
 * de funcionar em hospedagem compartilhada do que SMTP direto, que alguns
 * provedores restringem em portas de saída. Confirmado funcionando via
 * teste real em 19/08/2026.
 */
function hostinger_email_api_send(string $to, string $subject, string $html): bool
{
    $url = 'https://api.mail.hostinger.com/api/v1/mailboxes/' . rawurlencode(HOSTINGER_MAILBOX_RESOURCE_ID) . '/send';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . HOSTINGER_EMAIL_API_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode(['to' => [$to], 'subject' => $subject, 'html' => $html], JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \RuntimeException('Falha de conexão: ' . $error);
    }
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 204) {
        throw new \RuntimeException('HTTP ' . $status . ': ' . $raw);
    }
    return true;
}

function smtp_send(string $host, int $port, string $user, string $pass, string $from, string $fromName, string $to, string $subject, string $html): bool
{
    $transport = $port === 465 ? 'ssl://' : '';
    $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 15);
    if (!$socket) {
        throw new \RuntimeException("Não foi possível conectar ao SMTP: $errstr");
    }

    $expect = function (int $code) use ($socket) {
        $line = '';
        do {
            $line = fgets($socket, 515);
        } while (isset($line[3]) && $line[3] === '-');
        if (!$line || (int) substr($line, 0, 3) !== $code) {
            throw new \RuntimeException('Resposta SMTP inesperada: ' . $line);
        }
        return $line;
    };
    $send = function (string $cmd) use ($socket) {
        fwrite($socket, $cmd . "\r\n");
    };

    $expect(220);
    $send('EHLO ' . $host);
    $expect(250);

    if ($port === 587) {
        $send('STARTTLS');
        $expect(220);
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $send('EHLO ' . $host);
        $expect(250);
    }

    if ($user) {
        $send('AUTH LOGIN');
        $expect(334);
        $send(base64_encode($user));
        $expect(334);
        $send(base64_encode($pass));
        $expect(235);
    }

    $send('MAIL FROM:<' . $from . '>');
    $expect(250);
    $send('RCPT TO:<' . $to . '>');
    $expect(250);
    $send('DATA');
    $expect(354);

    $headers = "From: {$fromName} <{$from}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $body = str_replace("\n.", "\n..", $html);
    $send($headers . "\r\n" . $body . "\r\n.");
    $expect(250);
    $send('QUIT');
    fclose($socket);

    return true;
}
