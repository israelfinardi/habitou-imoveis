<?php
/**
 * Envio de e-mail simples. Usa SMTP se configurado em config.php (SMTP_HOST),
 * caso contrário tenta a função mail() nativa do PHP (disponível na maioria
 * das hospedagens compartilhadas, incluindo a Hostinger). Se nada estiver
 * disponível, apenas registra no log — nunca quebra o fluxo do usuário.
 */
function send_mail(string $to, string $subject, string $html): bool
{
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
