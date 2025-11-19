<?php
declare(strict_types=1);

use App\Infrastructure\Persistence\PDO\PDOUserRepository;
use App\Infrastructure\Persistence\PDO\PDOVerificationRepository;
use App\Infrastructure\Persistence\PDO\PDORefreshTokenRepository;
use App\Infrastructure\Email\SMTPMailer;
use App\Auth\JWTService;

require __DIR__ . '/vendor/autoload.php';

// Cargar .env con vlucas/phpdotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$pdo = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$userRepo = new PDOUserRepository($pdo);
$verifRepo = new PDOVerificationRepository($pdo);
$refreshRepo = new PDORefreshTokenRepository($pdo);
$emailService = new SMTPMailer([
    'host' => $_ENV['SMTP_HOST'] ?? '',
    'port' => (int)($_ENV['SMTP_PORT'] ?? 587),
    'username' => $_ENV['SMTP_USER'] ?? '',
    'password' => $_ENV['SMTP_PASS'] ?? '',
    'from' => $_ENV['MAIL_FROM'] ?? 'noreply@example.com'
]);

$jwtService = new JWTService(
    $_ENV['JWT_SECRET'] ?? '',
    $refreshRepo,
    $userRepo,
    $_ENV['APP_NAME'] ?? 'my-app',
    (int)($_ENV['ACCESS_TOKEN_TTL'] ?? 900),
    (int)($_ENV['REFRESH_TOKEN_TTL'] ?? 604800)
);

// Construir use cases e inyectar según tu implementación (ejemplo):
$registerUser = new \App\Application\UseCases\RegisterUser($userRepo, $verifRepo, $emailService);
$verifyEmail = new \App\Application\UseCases\VerifyEmail($userRepo, $verifRepo);
// ... crear Login, VerifyLogin2FA, ForgotPassword, ResetPassword

return [
    'pdo' => $pdo,
    'userRepo' => $userRepo,
    'verifRepo' => $verifRepo,
    'refreshRepo' => $refreshRepo,
    'emailService' => $emailService,
    'sessionService' => $jwtService,
    'registerUser' => $registerUser,
    'verifyEmail' => $verifyEmail,
    // ... otros use cases
];
