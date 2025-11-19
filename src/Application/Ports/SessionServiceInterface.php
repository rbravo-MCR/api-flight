<?php
declare(strict_types=1);

namespace App\Application\Ports;

use App\Domain\Entities\User;

interface SessionServiceInterface
{
    /**
     * Devuelve access token (JWT)
     */
    public function issueAccessToken(User $user): string;

    /**
     * Crea y persiste un refresh token y devuelve el token en texto plano.
     */
    public function issueRefreshToken(int $userId): string;

    /**
     * Valida access token y devuelve payload (array) o null si inválido.
     */
    public function validateAccessToken(string $token): ?array;

    /**
     * Refresca tokens usando un refresh token válido.
     * Retorna ['access_token' => ..., 'refresh_token' => ...] o null si inválido/expirado.
     */
    public function refreshUsingRefreshToken(string $refreshToken): ?array;
}
