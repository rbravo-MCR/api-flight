<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\PDO;

use App\Application\Ports\UserRepositoryInterface;
use App\Domain\Entities\User;
use PDO;

final class PDOUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(User $user): User
    {
        $sql = 'INSERT INTO users (email, password_hash, is_verified, created_at) VALUES (:email, :pw, :v, NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':email' => mb_strtolower($user->email),
            ':pw'    => $user->passwordHash,
            ':v'     => $user->isVerified ? 1 : 0
        ]);
        $user->id = (int)$this->pdo->lastInsertId();
        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        $sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => mb_strtolower($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return $this->mapRowToUser($row);
    }

    public function findById(int $id): ?User
    {
        $sql = 'SELECT * FROM users WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return $this->mapRowToUser($row);
    }

    public function update(User $user): void
    {
        $sql = 'UPDATE users SET password_hash = :pw, is_verified = :v, updated_at = NOW() WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pw' => $user->passwordHash,
            ':v'  => $user->isVerified ? 1 : 0,
            ':id' => $user->id
        ]);
    }

    private function mapRowToUser(array $row): User
    {
        // Asumimos que App\Domain\Entities\User tiene constructor compatible:
        return new User(
            (int)$row['id'],
            $row['email'],
            $row['password_hash'],
            (bool)$row['is_verified'],
            new \DateTimeImmutable($row['created_at'])
        );
    }
}
