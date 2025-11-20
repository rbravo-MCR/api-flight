<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Ports\UserRepositoryInterface;
use App\Application\Ports\VerificationRepositoryInterface;
use App\Shared\Exceptions\DomainException;

final class ResetPassword
{
    public function __construct(
        private UserRepositoryInterface $users,
        private VerificationRepositoryInterface $verifs
    ) {}

    /**
     * @throws DomainException
     */
    public function execute(string $token, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new DomainException('Password must be at least 8 characters');
        }

        $tokenHash = hash('sha256', $token);
        $v = $this->verifs->findByTokenHash($tokenHash);
        if (!$v || $v->type !== 'password_reset') {
            throw new DomainException('Invalid or expired token');
        }

        if ($v->expiresAt < new \DateTimeImmutable()) {
            $this->verifs->deleteById($v->id);
            throw new DomainException('Token expired');
        }

        $user = $this->users->findById($v->userId);
        if (!$user) {
            throw new DomainException('User not found');
        }

        $user->changePassword($newPassword);
        $this->users->update($user);

        // Invalidate password reset token
        $this->verifs->deleteById($v->id);
    }
}
