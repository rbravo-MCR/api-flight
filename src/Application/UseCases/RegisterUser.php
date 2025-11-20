<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Ports\UserRepositoryInterface;
use App\Application\Ports\VerificationRepositoryInterface;
use App\Application\Ports\EmailServiceInterface;
use App\Domain\Entities\User;
use App\Domain\Entities\VerificationCode;
use App\Shared\Exceptions\DomainException;

final class RegisterUser
{
    private int $codeTtlMinutes = 15;

    public function __construct(
        private UserRepositoryInterface $users,
        private VerificationRepositoryInterface $verifs,
        private EmailServiceInterface $emailService
    ) {}

    /**
     * @return User the created user (with id)
     * @throws DomainException
     */
    public function execute(string $email, string $password): User
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Invalid email');
        }
        if (strlen($password) < 8) {
            throw new DomainException('Password must be at least 8 characters');
        }

        $existing = $this->users->findByEmail($email);
        if ($existing) {
            throw new DomainException('Email already registered');
        }

        // Create user (domain logic)
        $user = User::register($email, $password);
        $user = $this->users->save($user);

        // Generate 6-digit code
        $code = (string) random_int(100000, 999999);
        $codeHash = hash('sha256', $code);
        $expiresAt = new \DateTimeImmutable("+{$this->codeTtlMinutes} minutes");

        $v = new VerificationCode(
            null,
            (int)$user->id,
            'email_verification',
            $codeHash,
            null,
            $expiresAt
        );

        $created = $this->verifs->create($v);

        // Send email with plain code
        $this->emailService->sendVerificationCode($user->email, $code, 'registration');

        return $user;
    }
}
