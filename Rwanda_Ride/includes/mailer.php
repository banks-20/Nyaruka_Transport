<?php
declare(strict_types=1);

function email_log(string $recipientEmail, string $subject, string $message, bool $sent, string $mode, string $error = ''): void
{
    $storageDir = __DIR__ . '/../storage';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0775, true);
    }
    $logLine = sprintf(
        "[%s] to=%s subject=%s sent=%s mode=%s error=%s\n%s\n----\n",
        date('Y-m-d H:i:s'),
        $recipientEmail,
        $subject,
        $sent ? 'yes' : 'no',
        $mode,
        $error !== '' ? $error : '-',
        $message
    );
    @file_put_contents($storageDir . '/email-log.txt', $logLine, FILE_APPEND);
}

function smtp_read_response($socket): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (preg_match('/^\d{3}\s/', $line) === 1) {
            break;
        }
    }
    return trim($response);
}

function smtp_expect($socket, array $expectedCodes): bool
{
    $response = smtp_read_response($socket);
    if ($response === '') {
        return false;
    }
    $code = (int) substr($response, 0, 3);
    return in_array($code, $expectedCodes, true);
}

function smtp_write($socket, string $command): bool
{
    return fwrite($socket, $command . "\r\n") !== false;
}

function smtp_send_plaintext_email(string $to, string $subject, string $message, string &$error): bool
{
    if (SMTP_HOST === '') {
        $error = 'SMTP host is not configured.';
        return false;
    }

    $host = SMTP_SECURE === 'ssl' ? 'ssl://' . SMTP_HOST : SMTP_HOST;
    $socket = @stream_socket_client($host . ':' . SMTP_PORT, $errno, $errstr, 20);
    if (!$socket) {
        $error = 'SMTP connection failed: ' . $errstr;
        return false;
    }

    stream_set_timeout($socket, 20);

    if (!smtp_expect($socket, [220])) {
        $error = 'SMTP handshake failed.';
        fclose($socket);
        return false;
    }

    if (!smtp_write($socket, 'EHLO localhost') || !smtp_expect($socket, [250])) {
        $error = 'SMTP EHLO failed.';
        fclose($socket);
        return false;
    }

    if (SMTP_SECURE === 'tls') {
        if (!smtp_write($socket, 'STARTTLS') || !smtp_expect($socket, [220])) {
            $error = 'SMTP STARTTLS failed.';
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $error = 'SMTP TLS encryption failed.';
            fclose($socket);
            return false;
        }
        if (!smtp_write($socket, 'EHLO localhost') || !smtp_expect($socket, [250])) {
            $error = 'SMTP EHLO after TLS failed.';
            fclose($socket);
            return false;
        }
    }

    if (SMTP_USER !== '') {
        if (!smtp_write($socket, 'AUTH LOGIN') || !smtp_expect($socket, [334])) {
            $error = 'SMTP AUTH command failed.';
            fclose($socket);
            return false;
        }
        if (!smtp_write($socket, base64_encode(SMTP_USER)) || !smtp_expect($socket, [334])) {
            $error = 'SMTP username rejected.';
            fclose($socket);
            return false;
        }
        if (!smtp_write($socket, base64_encode(SMTP_PASS)) || !smtp_expect($socket, [235])) {
            $error = 'SMTP password rejected.';
            fclose($socket);
            return false;
        }
    }

    if (!smtp_write($socket, 'MAIL FROM:<' . MAIL_FROM_ADDRESS . '>') || !smtp_expect($socket, [250])) {
        $error = 'SMTP MAIL FROM failed.';
        fclose($socket);
        return false;
    }
    if (!smtp_write($socket, 'RCPT TO:<' . $to . '>') || !smtp_expect($socket, [250, 251])) {
        $error = 'SMTP RCPT TO failed.';
        fclose($socket);
        return false;
    }
    if (!smtp_write($socket, 'DATA') || !smtp_expect($socket, [354])) {
        $error = 'SMTP DATA failed.';
        fclose($socket);
        return false;
    }

    $headers = [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'Reply-To: ' . SUPPORT_EMAIL,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'To: <' . $to . '>',
        'Subject: ' . $subject,
    ];
    $payload = implode("\r\n", $headers) . "\r\n\r\n" . $message;
    $payload = preg_replace('/^\./m', '..', $payload);
    if ($payload === null) {
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $message;
    }

    if (fwrite($socket, $payload . "\r\n.\r\n") === false || !smtp_expect($socket, [250])) {
        $error = 'SMTP message body rejected.';
        fclose($socket);
        return false;
    }

    smtp_write($socket, 'QUIT');
    fclose($socket);
    return true;
}

function send_driver_credentials_email(string $recipientEmail, string $fullName, string $password): bool
{
    $subject = 'Your NyarukaTransport Driver Login Credentials';
    $message = "Hello {$fullName},\n\n"
        . "Your driver account has been created on NyarukaTransport.\n"
        . "Login URL: " . app_url('login.php?role=driver') . "\n"
        . "Email: {$recipientEmail}\n"
        . "Temporary Password: {$password}\n\n"
        . "Please login and update your password immediately.\n\n"
        . "Regards,\nNyarukaTransport Team";

    $error = '';
    $sent = smtp_send_plaintext_email($recipientEmail, $subject, $message, $error);
    $mode = 'smtp';

    // Last-resort fallback for environments without SMTP configuration.
    if (!$sent && SMTP_HOST === '') {
        $headers = "From: " . MAIL_FROM_ADDRESS . "\r\n"
            . "Reply-To: " . SUPPORT_EMAIL . "\r\n"
            . "X-Mailer: PHP/" . PHP_VERSION;
        $sent = @mail($recipientEmail, $subject, $message, $headers);
        $mode = 'php-mail-fallback';
        if (!$sent && $error === '') {
            $error = 'PHP mail() fallback failed.';
        }
    }

    email_log($recipientEmail, $subject, $message, $sent, $mode, $error);

    return $sent;
}
