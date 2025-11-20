<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Ports\UserRepositoryInterface;
use App\Application\Ports\VerificationRepositoryInterface;
use App\Application\Ports\EmailServiceInterface;

final class ForgotPassword
{
    private int $tokenTtlHours = 1;

    public function __construct(
        private UserRepositoryInterface $users,
        private VerificationRepositoryInterface $verifs,
        private EmailServiceInterface $emailService,
        private string $appUrl = ''
    ) {}

    /**
     * No revela si el email existe: si no existe, simplemente retorna sin enviar.
     */
    public function execute(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            // Do not reveal existence
            return;
        }

        // Generate secure token and persist only the hash
        $token = bin2hex(random_bytes(32)); // 64 hex chars
        $tokenHash = hash('sha256', $token);
        $expiresAt = new \DateTimeImmutable("+{$this->tokenTtlHours} hours");

        $v = new \App\Domain\Entities\VerificationCode(
            null,
            (int)$user->id,
            'password_reset',
            null,
            $tokenHash,
            $expiresAt
        );

        $this->verifs->create($v);

        // Compose reset link
        $base = $this->appUrl ?: ($_ENV['APP_URL'] ?? 'http://localhost:8080');
        $resetLink = rtrim($base, '/') . '/reset-password?token=' . $token;

        $this->emailService->sendPasswordReset($user->email, $resetLink);
    }
}
