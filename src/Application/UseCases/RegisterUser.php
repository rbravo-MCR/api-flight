<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Ports\UserRepositoryInterface;
use App\Application\Ports\VerificationRepositoryInterface;
use App\Application\Ports\EmailServiceInterface;

class RegisterUser
{
    public function __construct(
        private UserRepositoryInterface $users,
        private VerificationRepositoryInterface $verifs,
        private EmailServiceInterface $emailService
    ) {}

    /**
     * Registra al usuario y envía código de verificación.
     * @throws \LogicException si no está implementado aún
     */
    public function execute(string $email, string $password): void
    {
        throw new \LogicException('RegisterUser::execute not implemented');
    }
}
