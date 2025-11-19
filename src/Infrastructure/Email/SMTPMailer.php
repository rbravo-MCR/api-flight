<?php
declare(strict_types=1);

namespace App\Infrastructure\Email;

use App\Application\Ports\EmailServiceInterface;

class SMTPMailer implements EmailServiceInterface
{
    public function __construct(private array $config) {}

    public function sendVerificationCode(string $toEmail, string $code, string $purpose): void
    {
        // En stub: solo lanzar excepción o registrar en log temporalmente
        throw new \LogicException('SMTPMailer::sendVerificationCode not implemented');
    }

    public function sendPasswordReset(string $toEmail, string $resetLink): void
    {
        throw new \LogicException('SMTPMailer::sendPasswordReset not implemented');
    }
}
