<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Ports\UserRepositoryInterface;
use App\Application\Ports\VerificationRepositoryInterface;

class VerifyEmail
{
    public function __construct(
        private UserRepositoryInterface $users,
        private VerificationRepositoryInterface $verifs
    ) {}

    public function execute(int $userId, string $code): void
    {
        throw new \LogicException('VerifyEmail::execute not implemented');
    }
}
