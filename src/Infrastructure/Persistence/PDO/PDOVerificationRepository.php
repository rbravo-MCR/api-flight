<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\PDO;

use App\Application\Ports\VerificationRepositoryInterface;
use App\Domain\Entities\VerificationCode;
use PDO;

class PDOVerificationRepository implements VerificationRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(VerificationCode $v): VerificationCode
    {
        throw new \LogicException('PDOVerificationRepository::create not implemented');
    }

    public function findValidCodeForUser(int $userId, string $type): ?VerificationCode
    {
        throw new \LogicException('PDOVerificationRepository::findValidCodeForUser not implemented');
    }

    public function incrementAttempts(int $id): void
    {
        throw new \LogicException('PDOVerificationRepository::incrementAttempts not implemented');
    }

    public function deleteById(int $id): void
    {
        throw new \LogicException('PDOVerificationRepository::deleteById not implemented');
    }
}
