<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Ports\UserRepositoryInterface;
use App\Application\Ports\VerificationRepositoryInterface;
use App\Application\Ports\EmailServiceInterface;
use App\Shared\Exceptions\DomainException;
use App\Domain\Entities\VerificationCode;

final class Login
{
    private int $codeTtlMinutes = 10;

    public function __construct(
        private UserRepositoryInterface $users,
        private VerificationRepositoryInterface $verifs,
        private EmailServiceInterface $emailService,
        private bool $force2fa = true // si true siempre 2FA por email; si false se puede cambiar
    ) {}

    /**
     * Retorna:
     * - ['2fa_required' => true, 'login_id' => <verif_id>]   // if 2FA
     * - ['user' => User]  // if no 2fa and login successful
     *
     * @throws DomainException
     */
    public function execute(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            throw new DomainException('Invalid credentials');
        }

        if (!$user->checkPassword($password)) {
            throw new DomainException('Invalid credentials');
        }

        if (!$user->isVerified) {
            throw new DomainException('User not verified');
        }

        if ($this->force2fa) {
            // Create login_2fa code and send email
            $code = (string) random_int(100000, 999999);
            $codeHash = hash('sha256', $code);
            $expiresAt = new \DateTimeImmutable("+{$this->codeTtlMinutes} minutes");

            $v = new VerificationCode(
                null,
                (int)$user->id,
                'login_2fa',
                $codeHash,
                null,
                $expiresAt
            );

            $created = $this->verifs->create($v);

            // Send code to email
            $this->emailService->sendVerificationCode($user->email, $code, 'login');

            return [
                '2fa_required' => true,
                'login_id' => $created->id,
            ];
        }

        // No 2FA: return user for controller to issue tokens
        return ['user' => $user];
    }
}
