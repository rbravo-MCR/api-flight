<?php
declare(strict_types=1);

namespace App\Application\Ports;

interface RefreshTokenRepositoryInterface
{
    public function save(int $userId, string $tokenHash, \DateTimeImmutable $expiresAt): void;

    /**
     * Retorna array con keys: id, user_id, token_hash, expires_at  o null
     */
    public function findByTokenHash(string $tokenHash): ?array;

    public function deleteById(int $id): void;

    public function deleteAllForUser(int $userId): void;
}
