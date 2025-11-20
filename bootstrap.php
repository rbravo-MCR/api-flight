<?php
declare(strict_types=1);

// bootstrap.php (raíz del proyecto)

require __DIR__ . '/vendor/autoload.php';
use App\Infrastructure\Persistence\PDO\PDOUserRepository;
use App\Infrastructure\Persistence\PDO\PDOVerificationRepository;
use App\Infrastructure\Persistence\PDO\PDORefreshTokenRepository;
use App\Infrastructure\Email\SMTPMailer;
use App\Auth\JWTService;
use App\Application\UseCases\RegisterUser;
use App\Application\UseCases\VerifyEmail;
use App\Application\UseCases\Login;
use App\Application\UseCases\VerifyLogin2FA;
use App\Application\UseCases\ForgotPassword;
use App\Application\UseCases\ResetPassword;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Configuración PDO
$pdo = new PDO($_ENV['DB_DSN'] ?? 'sqlite::memory:', $_ENV['DB_USER'] ?? null, $_ENV['DB_PASS'] ?? null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Repositorios e infra (asegúrate de tener estas clases realmente)
$userRepo = new PDOUserRepository($pdo);
$verifRepo = new PDOVerificationRepository($pdo);
$refreshRepo = new PDORefreshTokenRepository($pdo);
$emailService = new SMTPMailer([
    'host' => $_ENV['SMTP_HOST'] ?? '',
    'port' => (int)($_ENV['SMTP_PORT'] ?? 1025),
    'username' => $_ENV['SMTP_USER'] ?? '',
    'password' => $_ENV['SMTP_PASS'] ?? '',
    'from' => $_ENV['MAIL_FROM'] ?? 'noreply@example.com'
]);

// JWT / sesión
$jwtService = new JWTService(
    $_ENV['JWT_SECRET'] ?? bin2hex(random_bytes(32)),
    $refreshRepo,
    $userRepo,
    $_ENV['APP_NAME'] ?? 'my-app',
    (int)($_ENV['ACCESS_TOKEN_TTL'] ?? 900),
    (int)($_ENV['REFRESH_TOKEN_TTL'] ?? 604800)
);

// UseCases: crea instancias pasando las dependencias concretas.
// Si aún no las tienes implementadas, aquí puedes inyectar stubs/objetos anónimos.
$registerUser = new RegisterUser($userRepo, $verifRepo, $emailService);
$verifyEmail  = new VerifyEmail($userRepo, $verifRepo);
$login        = new Login($userRepo, $verifRepo /*,...*/);
$verifyLogin2FA = new VerifyLogin2FA($userRepo, $verifRepo /*,...*/);
$forgotPassword = new ForgotPassword($userRepo, $verifRepo, $emailService);
$resetPassword  = new ResetPassword($userRepo, $verifRepo);

return [
    'pdo' => $pdo,
    'userRepo' => $userRepo,
    'verifRepo' => $verifRepo,
    'refreshRepo' => $refreshRepo,
    'emailService' => $emailService,
    'sessionService' => $jwtService,
    'registerUser' => $registerUser,
    'verifyEmail' => $verifyEmail,
    'login' => $login,
    'verifyLogin2FA' => $verifyLogin2FA,
    'forgotPassword' => $forgotPassword,
    'resetPassword' => $resetPassword,
];
