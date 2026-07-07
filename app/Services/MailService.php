<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    private SettingsService $settings;

    public function __construct()
    {
        $this->settings = new SettingsService();
    }

    public function sendVerification(string $email, string $token): void
    {
        $url = rtrim($this->settings->get('app_url'), '/') . '/verify-email?token=' . urlencode($token);
        $this->send(
            $email,
            'Подтверждение email — Boosterino',
            "<p>Здравствуйте!</p><p>Подтвердите email: <a href=\"{$url}\">{$url}</a></p>"
        );
    }

    public function sendPasswordReset(string $email, string $token): void
    {
        $url = rtrim($this->settings->get('app_url'), '/') . '/reset-password?token=' . urlencode($token);
        $this->send(
            $email,
            'Восстановление пароля — Boosterino',
            "<p>Ссылка для сброса пароля: <a href=\"{$url}\">{$url}</a></p><p>Ссылка действует 1 час.</p>"
        );
    }

    private function send(string $to, string $subject, string $html): void
    {
        $host = $this->settings->get('mail_host');
        $user = $this->settings->get('mail_user');
        $pass = $this->settings->get('mail_pass');

        if ($host === '' || $user === '' || $pass === '') {
            error_log("Mail not configured. Would send to {$to}: {$subject}");
            return;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $this->settings->get('mail_port', '587');
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(
                $this->settings->get('mail_from', $user),
                $this->settings->get('mail_from_name', 'Boosterino')
            );
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;

            $mail->send();
        } catch (MailerException $e) {
            error_log('Mail error: ' . $e->getMessage());
        }
    }
}
