<?php

declare(strict_types=1);

namespace App\Services;

final class MailService
{
    public function send(string $to, string $subject, string $html): void
    {
        $s = new SettingsService();
        $host = $s->get('mail_host');
        $user = $s->get('mail_user');
        $pass = $s->get('mail_pass');
        $from = $s->get('mail_from', $user);
        $fromName = $s->get('mail_from_name', 'Boosterino');

        if ($host === '' || $user === '' || $pass === '') {
            error_log("Mail skip (not configured): {$subject} -> {$to}");
            return;
        }

        $port = (int) $s->get('mail_port', '587');
        $this->smtpSend($host, $port, $user, $pass, $from, $fromName, $to, $subject, $html);
    }

    public function verifyLink(string $email, string $token): void
    {
        $url = rtrim((new SettingsService())->get('app_url'), '/') . '/verify-email?token=' . urlencode($token);
        $this->send($email, 'Подтверждение email - Boosterino', "<p><a href=\"{$url}\">Подтвердить email</a></p>");
    }

    public function resetLink(string $email, string $token): void
    {
        $url = rtrim((new SettingsService())->get('app_url'), '/') . '/reset-password?token=' . urlencode($token);
        $this->send($email, 'Восстановление пароля - Boosterino', "<p><a href=\"{$url}\">Сбросить пароль</a></p>");
    }

    private function smtpSend(string $host, int $port, string $user, string $pass, string $from, string $fromName, string $to, string $subject, string $body): void
    {
        $fp = @fsockopen('tcp://' . $host, $port, $errno, $errstr, 15);
        if (!$fp) {
            error_log("SMTP connect fail: {$errstr}");
            return;
        }
        stream_set_timeout($fp, 15);
        $read = static fn () => fgets($fp, 512);
        $write = static fn (string $c) => fwrite($fp, $c . "\r\n");

        $read();
        $write('EHLO boosterino.ru');
        while ($line = $read()) {
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $write('STARTTLS');
        $read();
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $write('EHLO boosterino.ru');
        while ($line = $read()) {
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $write('AUTH LOGIN');
        $read();
        $write(base64_encode($user));
        $read();
        $write(base64_encode($pass));
        $read();
        $write('MAIL FROM:<' . $from . '>');
        $read();
        $write('RCPT TO:<' . $to . '>');
        $read();
        $write('DATA');
        $read();

        $msg = "From: {$fromName} <{$from}>\r\nTo: {$to}\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$body}\r\n.";
        $write($msg);
        $read();
        $write('QUIT');
        fclose($fp);
    }
}
