<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Ports\UserRepositoryInterface;
use App\Application\Ports\VerificationRepositoryInterface;
use App\Shared\Exceptions\DomainException;

final class VerifyEmail
{
    private int $maxAttempts = 5;

    public function __construct(
        private UserRepositoryInterface $users,
        private VerificationRepositoryInterface $verifs
    ) {}

    /**
     * @throws DomainException
     */
    public function execute(int $userId, string $code): void
    {
        $v = $this->verifs->findValidCodeForUser($userId, 'email_verification');
        if (!$v) {
            throw new DomainException('Code invalid or expired');
        }

        if ($v->attempts >= $this->maxAttempts) {
            $this->verifs->deleteById($v->id);
            throw new DomainException('Too many attempts');
        }

        if ($v->expiresAt < new \DateTimeImmutable()) {
            $this->verifs->deleteById($v->id);
            throw new DomainException('Code expired');
        }

        $hash = hash('sha256', (string)$code);
        if (!hash_equals($v->codeHash ?? '', $hash)) {
            $this->verifs->incrementAttempts($v->id);
            throw new DomainException('Invalid code');
        }

        $user = $this->users->findById($userId);
        if (!$user) {
            throw new DomainException('User not found');
        }

        $user->verify();
        $this->users->update($user);

        // Delete verification after successful use
        $this->verifs->deleteById($v->id);
    }
}
