<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\PDO;

use App\Application\Ports\RefreshTokenRepositoryInterface;
use PDO;

class PDORefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(int $userId, string $tokenHash, \DateTimeImmutable $expiresAt): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (:u, :h, :e)');
        $stmt->execute([
            ':u' => $userId,
            ':h' => $tokenHash,
            ':e' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM refresh_tokens WHERE token_hash = :h LIMIT 1');
        $stmt->execute([':h' => $tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM refresh_tokens WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function deleteAllForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM refresh_tokens WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
    }
}
