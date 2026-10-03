<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\LoggerFactory;
use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\ProfessionalController;
use App\Middleware\CorsMiddleware;
use App\Middleware\ProfessionalAuthMiddleware;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$logger = LoggerFactory::create();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$app->add(new CorsMiddleware(
    $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173'
));

$errorMiddleware = $app->addErrorMiddleware(
    filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    true,
    true,
    $logger
);

$healthController = new HealthController();
$authController = new AuthController();
$professionalController = new ProfessionalController();

$app->get('/api/health', [$healthController, 'app']);
$app->get('/api/health/database', [$healthController, 'database']);
$app->post('/api/auth/login', [$authController, 'login']);

$app->group('/api/profissional', function (RouteCollectorProxy $group) use (
    $authController,
    $professionalController
) {
    $group->get('/me', [$authController, 'me']);
    $group->put('/senha', [$authController, 'changePassword']);
    $group->get('/perfil', [$professionalController, 'profile']);
    $group->put('/perfil', [$professionalController, 'updateProfile']);
})->add(new ProfessionalAuthMiddleware());

$app->get('/api', function ($request, $response) {
    $payload = json_encode([
        'name' => 'Avaliacao de Percepcao Relacional API',
        'status' => 'ok',
        'version' => '0.1.0'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $response->getBody()->write($payload ?: '{}');

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
