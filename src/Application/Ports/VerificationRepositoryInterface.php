<?php
declare(strict_types=1);

namespace App\Application\Ports;

use App\Domain\Entities\VerificationCode;

interface VerificationRepositoryInterface
{
    public function create(VerificationCode $v): VerificationCode;

    /**
     * Busca un código válido (más reciente) para un usuario y tipo.
     * @return VerificationCode|null
     */
    public function findValidCodeForUser(int $userId, string $type): ?VerificationCode;

    /**
     * Buscar por id (necesario para flujos que pasan login_id).
     */
    public function findById(int $id): ?VerificationCode;

    /**
     * Buscar por token_hash (para reset password).
     */
    public function findByTokenHash(string $tokenHash): ?VerificationCode;

    public function incrementAttempts(int $id): void;

    public function deleteById(int $id): void;
}
