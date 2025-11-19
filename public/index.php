<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$container = require __DIR__ . '/../bootstrap.php';

$authController = new \App\UI\Http\Controllers\AuthController(
    $container['registerUser'],
    $container['verifyEmail'],
    $container['login'],
    $container['verifyLogin2FA'],
    $container['forgotPassword'],
    $container['resetPassword'],
    $container['sessionService'],
    $container['userRepo']
);

// Rutas
Flight::route('POST /api/register', [$authController, 'register']);
Flight::route('POST /api/verify-email', [$authController, 'verifyEmail']);
Flight::route('POST /api/login', [$authController, 'login']);
Flight::route('POST /api/verify-2fa', [$authController, 'verify2fa']);
Flight::route('POST /api/forgot', [$authController, 'forgot']);
Flight::route('POST /api/reset', [$authController, 'reset']);
Flight::route('POST /api/refresh', [$authController, 'refresh']);

Flight::start();
