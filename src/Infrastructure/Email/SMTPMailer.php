<?php
declare(strict_types=1);

namespace App\Infrastructure\Email;

use App\Application\Ports\EmailServiceInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class SMTPMailer implements EmailServiceInterface
{
    private array $config;
    private PHPMailer $mailer;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = $config['host'] ?? '127.0.0.1';
        $this->mailer->Port = $config['port'] ?? 1025;
        if (!empty($config['username'])) {
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $config['username'];
            $this->mailer->Password = $config['password'] ?? '';
        } else {
            $this->mailer->SMTPAuth = false;
        }
        $this->mailer->SMTPSecure = $config['secure'] ?? '';
        $this->mailer->setFrom($config['from'] ?? 'noreply@example.com', $config['from_name'] ?? 'NoReply');
        $this->mailer->isHTML(false);
    }

    public function sendVerificationCode(string $toEmail, string $code, string $purpose): void
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress($toEmail);
            $this->mailer->Subject = "Código de verificación";
            $this->mailer->Body = "Tu código para {$purpose} es: {$code}. Expira en 15 minutos.";
            $this->mailer->send();
        } catch (Exception $e) {
            // En dev, no interrumpir: loguear
            error_log('Mail error: ' . $e->getMessage());
            throw new \RuntimeException('Unable to send email');
        }
    }

    public function sendPasswordReset(string $toEmail, string $resetLink): void
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress($toEmail);
            $this->mailer->Subject = "Reset de contraseña";
            $this->mailer->Body = "Haz clic aquí para resetear tu contraseña: {$resetLink}";
            $this->mailer->send();
        } catch (Exception $e) {
            error_log('Mail error: ' . $e->getMessage());
            throw new \RuntimeException('Unable to send email');
        }
    }
}
