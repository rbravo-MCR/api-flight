<?php
declare(strict_types=1);

namespace App\UI\Http\Controllers;

use App\Shared\Helpers\Request;
use App\Shared\Helpers\Response;
use App\Application\UseCases\RegisterUser;
use App\Application\UseCases\VerifyEmail;
use App\Application\UseCases\Login;
use App\Application\UseCases\VerifyLogin2FA;
use App\Application\UseCases\ForgotPassword;
use App\Application\UseCases\ResetPassword;
use App\Application\Ports\SessionServiceInterface;
use App\Application\Ports\UserRepositoryInterface;
use App\Shared\Exceptions\DomainException;

final class AuthController
{
    public function __construct(
        private RegisterUser $registerUser,
        private VerifyEmail $verifyEmail,
        private Login $login,
        private VerifyLogin2FA $verifyLogin2FA,
        private ForgotPassword $forgotPassword,
        private ResetPassword $resetPassword,
        private SessionServiceInterface $sessionService,
        private UserRepositoryInterface $users
    ) {}

    public function register(): void
    {
        try {
            $data = Request::getJson();
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;
            if (!$email || !$password) Response::json(['error' => 'email and password required'], 400);
            $user = $this->registerUser->execute($email, $password);
            Response::json(['message' => 'Verification code sent to email', 'user_id' => $user->id], 201);
        } catch (DomainException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            Response::json(['error' => 'Internal error'], 500);
        }
    }

    public function verifyEmail(): void
    {
        try {
            $data = Request::getJson();
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
            $code = $data['code'] ?? null;
            if (!$userId || !$code) Response::json(['error' => 'user_id and code required'], 400);
            $this->verifyEmail->execute($userId, $code);
            Response::json(['message' => 'User verified'], 200);
        } catch (DomainException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            Response::json(['error' => 'Internal error'], 500);
        }
    }

    public function login(): void
    {
        try {
            $data = Request::getJson();
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;
            if (!$email || !$password) Response::json(['error' => 'email and password required'], 400);

            $result = $this->login->execute($email, $password);

            if (isset($result['2fa_required']) && $result['2fa_required']) {
                Response::json(['message' => '2FA code sent to email', 'login_id' => $result['login_id']], 200);
                return;
            }

            if (isset($result['user'])) {
                $user = $result['user'];
                $access = $this->sessionService->issueAccessToken($user);
                $refresh = $this->sessionService->issueRefreshToken((int)$user->id);
                Response::json([
                    'access_token' => $access,
                    'refresh_token' => $refresh,
                    'token_type' => 'bearer',
                    'expires_in' => 900
                ], 200);
                return;
            }

            Response::json(['error' => 'Invalid credentials'], 401);
        } catch (DomainException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            Response::json(['error' => 'Internal error'], 500);
        }
    }

    public function verify2fa(): void
    {
        try {
            $data = Request::getJson();
            $loginId = $data['login_id'] ?? null;
            $code = $data['code'] ?? null;
            if (!$loginId || !$code) Response::json(['error' => 'login_id and code required'], 400);

            $user = $this->verifyLogin2FA->execute((int)$loginId, (string)$code);
            $access = $this->sessionService->issueAccessToken($user);
            $refresh = $this->sessionService->issueRefreshToken((int)$user->id);
            Response::json([
                'access_token' => $access,
                'refresh_token' => $refresh,
                'token_type' => 'bearer',
                'expires_in' => 900
            ], 200);
        } catch (DomainException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            Response::json(['error' => 'Internal error'], 500);
        }
    }

    public function forgot(): void
    {
        try {
            $data = Request::getJson();
            $email = $data['email'] ?? null;
            if (!$email) Response::json(['error' => 'email required'], 400);
            $this->forgotPassword->execute($email);
            Response::json(['message' => 'If the account exists, a reset email has been sent'], 200);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            Response::json(['error' => 'Internal error'], 500);
        }
    }

    public function reset(): void
    {
        try {
            $data = Request::getJson();
            $token = $data['token'] ?? null;
            $newPassword = $data['password'] ?? null;
            if (!$token || !$newPassword) Response::json(['error' => 'token and password required'], 400);
            $this->resetPassword->execute($token, $newPassword);
            Response::json(['message' => 'Password reset successful'], 200);
        } catch (DomainException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            Response::json(['error' => 'Internal error'], 500);
        }
    }

    public function refresh(): void
    {
        try {
            $data = Request::getJson();
            $refreshToken = $data['refresh_token'] ?? null;
            if (!$refreshToken) Response::json(['error' => 'refresh_token required'], 400);

            $tokens = $this->sessionService->refreshUsingRefreshToken($refreshToken);
            if (!$tokens) Response::json(['error' => 'Invalid or expired refresh token'], 401);
            Response::json($tokens, 200);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            Response::json(['error' => 'Internal error'], 500);
        }
    }
}
