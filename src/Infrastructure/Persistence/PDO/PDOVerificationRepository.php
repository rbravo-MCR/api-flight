<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\PDO;

use App\Application\Ports\VerificationRepositoryInterface;
use App\Domain\Entities\VerificationCode;
use PDO;

final class PDOVerificationRepository implements VerificationRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(VerificationCode $v): VerificationCode
    {
        $sql = 'INSERT INTO verification_codes 
            (user_id, type, code_hash, token_hash, expires_at, attempts, created_at)
            VALUES (:user_id, :type, :code_hash, :token_hash, :expires_at, :attempts, NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id'   => $v->userId,
            ':type'      => $v->type,
            ':code_hash' => $v->codeHash,
            ':token_hash'=> $v->tokenHash,
            ':expires_at'=> $v->expiresAt->format('Y-m-d H:i:s'),
            ':attempts'  => $v->attempts ?? 0
        ]);
        $v->id = (int)$this->pdo->lastInsertId();
        return $v;
    }

    public function findValidCodeForUser(int $userId, string $type): ?VerificationCode
    {
        // Obtener el más reciente no-expirado
        $sql = 'SELECT * FROM verification_codes
                WHERE user_id = :user_id AND type = :type
                ORDER BY created_at DESC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':type' => $type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $expires = new \DateTimeImmutable($row['expires_at']);
        if ($expires < new \DateTimeImmutable()) {
            return null; // expirado
        }
        return $this->mapRowToVerification($row);
    }

    public function findById(int $id): ?VerificationCode
    {
        $sql = 'SELECT * FROM verification_codes WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRowToVerification($row) : null;
    }

    public function findByTokenHash(string $tokenHash): ?VerificationCode
    {
        $sql = 'SELECT * FROM verification_codes WHERE token_hash = :h LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':h' => $tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRowToVerification($row) : null;
    }

    public function incrementAttempts(int $id): void
    {
        $sql = 'UPDATE verification_codes SET attempts = attempts + 1 WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM verification_codes WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    private function mapRowToVerification(array $row): VerificationCode
    {
        return new VerificationCode(
            (int)$row['id'],
            (int)$row['user_id'],
            $row['type'],
            $row['code_hash'] ?? null,
            $row['token_hash'] ?? null,
            new \DateTimeImmutable($row['expires_at'])
        );
    }
}
