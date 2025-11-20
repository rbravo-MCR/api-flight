<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Ports\UserRepositoryInterface;
use App\Application\Ports\VerificationRepositoryInterface;
use App\Shared\Exceptions\DomainException;

final class VerifyLogin2FA
{
    private int $maxAttempts = 5;

    public function __construct(
        private UserRepositoryInterface $users,
        private VerificationRepositoryInterface $verifs
    ) {}

    /**
     * @param int $loginId verification_codes.id
     * @param string $code
     * @return \App\Domain\Entities\User  // user on success
     * @throws DomainException
     */
    public function execute(int $loginId, string $code)
    {
        $v = $this->verifs->findById($loginId);
        if (!$v || $v->type !== 'login_2fa') {
            throw new DomainException('Invalid login attempt');
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

        $user = $this->users->findById($v->userId);
        if (!$user) {
            throw new DomainException('User not found');
        }

        // Successful login 2FA: delete verification and return user
        $this->verifs->deleteById($v->id);
        return $user;
    }
}
