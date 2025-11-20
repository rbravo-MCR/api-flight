<?php
declare(strict_types=1);

namespace App\Auth;

use App\Application\Ports\SessionServiceInterface;
use App\Application\Ports\RefreshTokenRepositoryInterface;
use App\Application\Ports\UserRepositoryInterface;
use App\Domain\Entities\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService implements SessionServiceInterface
{
    private string $alg = 'HS256';
    private string $jwtSecret;
    private int $accessTokenTtl;
    private int $refreshTokenTtl;
    private string $issuer;

    public function __construct(
        string $jwtSecret,
        RefreshTokenRepositoryInterface $refreshRepo,
        UserRepositoryInterface $users,
        string $issuer = 'app',
        int $accessTokenTtl = 900,      // 15 minutos
        int $refreshTokenTtl = 604800   // 7 días
    ) {
        if (empty($jwtSecret)) {
            throw new \InvalidArgumentException('JWT secret required');
        }
        $this->jwtSecret = $jwtSecret;
        $this->refreshRepo = $refreshRepo;
        $this->users = $users;
        $this->issuer = $issuer;
        $this->accessTokenTtl = $accessTokenTtl;
        $this->refreshTokenTtl = $refreshTokenTtl;
    }

    public function issueAccessToken(User $user): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'sub' => (int)$user->id,
            'email' => $user->email,
            'iat' => $now,
            'exp' => $now + $this->accessTokenTtl,
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->alg);
    }

    public function issueRefreshToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32)); // 64 chars hex
        $hash = hash('sha256', $token);
        $expires = new \DateTimeImmutable('+'. $this->refreshTokenTtl .' seconds');

        // Persistir hash
        $this->refreshRepo->save($userId, $hash, $expires);

        return $token;
    }

    public function validateAccessToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->alg));
            // convertir stdClass a array
            return json_decode(json_encode($decoded), true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function refreshUsingRefreshToken(string $refreshToken): ?array
    {
        $hash = hash('sha256', $refreshToken);
        $row = $this->refreshRepo->findByTokenHash($hash);

        if (!$row) {
            return null;
        }

        $expiresAt = new \DateTimeImmutable($row['expires_at']);
        if ($expiresAt < new \DateTimeImmutable()) {
            // token expirado: borrar
            $this->refreshRepo->deleteById((int)$row['id']);
            return null;
        }

        $user = $this->users->findById((int)$row['user_id']);
        if (!$user) return null;

        // Rotación: borrar token usado y emitir uno nuevo
        $this->refreshRepo->deleteById((int)$row['id']);

        $access = $this->issueAccessToken($user);
        $refresh = $this->issueRefreshToken((int)$user->id);

        return [
            'access_token' => $access,
            'refresh_token' => $refresh,
        ];
    }
}
