<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\PDO;

use App\Application\Ports\UserRepositoryInterface;
use App\Domain\Entities\User;
use PDO;

class PDOUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(User $user): User
    {
        throw new \LogicException('PDOUserRepository::save not implemented');
    }

    public function findByEmail(string $email): ?User
    {
        throw new \LogicException('PDOUserRepository::findByEmail not implemented');
    }

    public function findById(int $id): ?User
    {
        throw new \LogicException('PDOUserRepository::findById not implemented');
    }

    public function update(User $user): void
    {
        throw new \LogicException('PDOUserRepository::update not implemented');
    }
}
