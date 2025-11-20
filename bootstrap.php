<?php
declare(strict_types=1);

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
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// DB config
$dbDsn  = $_ENV['DB_DSN'] ?? 'mysql:host=127.0.0.1;port=3306;dbname=auth_db;charset=utf8mb4';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

// PDO options
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $e) {
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Infra
$userRepo = new PDOUserRepository($pdo);
$verifRepo = new PDOVerificationRepository($pdo);
$refreshRepo = new PDORefreshTokenRepository($pdo);

// Email service
$emailService = new SMTPMailer([
    'host' => $_ENV['SMTP_HOST'] ?? '127.0.0.1',
    'port' => (int)($_ENV['SMTP_PORT'] ?? 1025),
    'username' => $_ENV['SMTP_USER'] ?? '',
    'password' => $_ENV['SMTP_PASS'] ?? '',
    'from' => $_ENV['MAIL_FROM'] ?? 'noreply@example.com',
]);

// Session/JWT
$jwtSecret = $_ENV['JWT_SECRET'] ?? bin2hex(random_bytes(32));
$jwtService = new JWTService(
    $jwtSecret,
    $refreshRepo,
    $userRepo,
    $_ENV['APP_NAME'] ?? 'my-app',
    (int)($_ENV['ACCESS_TOKEN_TTL'] ?? 900),
    (int)($_ENV['REFRESH_TOKEN_TTL'] ?? 604800)
);

// Use cases
$registerUser   = new RegisterUser($userRepo, $verifRepo, $emailService);
$verifyEmail    = new VerifyEmail($userRepo, $verifRepo);
$login          = new Login($userRepo, $verifRepo, $emailService, true);
$verifyLogin2FA = new VerifyLogin2FA($userRepo, $verifRepo);
$forgotPassword = new ForgotPassword($userRepo, $verifRepo, $emailService, $_ENV['APP_URL'] ?? 'http://localhost:8080');
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
