<?php
/**
 * Envío de email vía AWS SES SMTP
 */

require_once __DIR__ . '/../app_config.php';

function obtener_smtp_password(): string {
    $secret_key = AWS_SES_SECRET;
    $region = AWS_SES_REGION ?: 'us-east-1';

    $sig = hash_hmac('sha256', '11111111', 'AWS4' . $secret_key, true);
    $sig = hash_hmac('sha256', $region, $sig, true);
    $sig = hash_hmac('sha256', 'ses', $sig, true);
    $sig = hash_hmac('sha256', 'aws4_request', $sig, true);
    $sig = hash_hmac('sha256', 'SendRawEmail', $sig, true);

    return base64_encode(chr(0x04) . $sig);
}

function smtp_read($fp): string {
    $response = '';
    while ($line = fgets($fp, 512)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $response;
}

function enviar_email_ses(string $para, string $asunto, string $cuerpo_html, string $cuerpo_texto = ''): bool {
    $smtp_host = 'ssl://email-smtp.' . (AWS_SES_REGION ?: 'us-east-1') . '.amazonaws.com';
    $smtp_port = 465;
    $smtp_user = AWS_SES_KEY;
    $smtp_pass = obtener_smtp_password();
    $from = 'go@guadalupe.gob.mx';
    $from_name = 'GuadalupeGO';

    if (empty($smtp_user) || empty(AWS_SES_SECRET)) {
        return false;
    }

    if (empty($cuerpo_texto)) {
        $cuerpo_texto = strip_tags($cuerpo_html);
    }

    $fp = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
    if (!$fp) return false;

    smtp_read($fp);

    fputs($fp, "EHLO go.guadalupe.gob.mx\r\n");
    smtp_read($fp);

    fputs($fp, "AUTH LOGIN\r\n");
    $auth_resp = smtp_read($fp);
    if (strpos($auth_resp, '334') === false) { fclose($fp); return false; }

    fputs($fp, base64_encode($smtp_user) . "\r\n");
    $user_resp = smtp_read($fp);
    if (strpos($user_resp, '334') === false) { fclose($fp); return false; }

    fputs($fp, base64_encode($smtp_pass) . "\r\n");
    $pass_resp = smtp_read($fp);
    if (strpos($pass_resp, '235') === false) { fclose($fp); return false; }

    fputs($fp, "MAIL FROM:<$from>\r\n");
    $from_resp = smtp_read($fp);
    if (strpos($from_resp, '250') === false) { fclose($fp); return false; }

    fputs($fp, "RCPT TO:<$para>\r\n");
    $to_resp = smtp_read($fp);
    if (strpos($to_resp, '250') === false) { fclose($fp); return false; }

    fputs($fp, "DATA\r\n");
    $data_resp = smtp_read($fp);
    if (strpos($data_resp, '354') === false) { fclose($fp); return false; }

    $boundary = md5(uniqid(time()));

    $message = "From: $from_name <$from>\r\n";
    $message .= "To: $para\r\n";
    $message .= "Subject: =?UTF-8?B?" . base64_encode($asunto) . "?=\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $message .= "\r\n";
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "\r\n";
    $message .= chunk_split(base64_encode($cuerpo_texto));
    $message .= "\r\n--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "\r\n";
    $message .= chunk_split(base64_encode($cuerpo_html));
    $message .= "\r\n--$boundary--\r\n";

    fputs($fp, $message);
    fputs($fp, "\r\n.\r\n");
    $send_resp = smtp_read($fp);

    fputs($fp, "QUIT\r\n");
    fclose($fp);

    return strpos($send_resp, '250') !== false;
}

function enviar_codigo_verificacion(string $email, string $codigo): bool {
    $html = '
    <div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:20px;">
        <h2 style="color:#1a5632;text-align:center;">GuadalupeGO</h2>
        <p>Tu código de verificación es:</p>
        <div style="background:#f4f4f4;border-radius:8px;padding:20px;text-align:center;margin:20px 0;">
            <span style="font-size:32px;font-weight:bold;letter-spacing:8px;color:#1a5632;">' . htmlspecialchars($codigo) . '</span>
        </div>
        <p style="color:#666;font-size:14px;">Este código expira en 4 horas. Si no solicitaste este código, ignora este mensaje.</p>
    </div>';

    return enviar_email_ses($email, 'Tu código de acceso - GuadalupeGO', $html);
}

function enviar_email_bienvenida(string $email, string $nombre): bool {
    $html = '
    <div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:20px;">
        <h2 style="color:#1a5632;text-align:center;">¡Bienvenido a GuadalupeGO!</h2>
        <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Tu cuenta ha sido creada exitosamente. Ya puedes explorar lugares, eventos y el directorio de Guadalupe, NL.</p>
        <p style="color:#666;font-size:14px;">— Equipo GuadalupeGO</p>
    </div>';

    return enviar_email_ses($email, '¡Bienvenido a GuadalupeGO!', $html);
}
