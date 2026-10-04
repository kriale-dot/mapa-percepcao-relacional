<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\LoggerFactory;
use App\Controller\AlternativeController;
use App\Controller\ApplicationController;
use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\InstrumentController;
use App\Controller\InstrumentVersionController;
use App\Controller\ItemController;
use App\Controller\ParticipantAccessController;
use App\Controller\PersonController;
use App\Controller\ProfessionalController;
use App\Controller\PublicEvaluationController;
use App\Controller\RelationshipController;
use App\Controller\SectionController;
use App\Middleware\CorsMiddleware;
use App\Service\AccessTokenService;
use App\Service\MailService;
use App\Service\ResultService;
use App\Middleware\ProfessionalAuthMiddleware;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

$phpLogDir = $root . '/storage/logs';

if (!is_dir($phpLogDir)) {
    @mkdir($phpLogDir, 0775, true);
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $phpLogDir . '/php-error.log');

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
$accessTokenService = new AccessTokenService();
$mailService = new MailService();
$resultService = new ResultService();
$alternativeController = new AlternativeController();
$applicationController = new ApplicationController($resultService);
$authController = new AuthController();
$professionalController = new ProfessionalController();
$publicEvaluationController = new PublicEvaluationController($accessTokenService, $mailService, $logger);
$participantAccessController = new ParticipantAccessController($accessTokenService, $resultService);
$instrumentController = new InstrumentController();
$instrumentVersionController = new InstrumentVersionController();
$sectionController = new SectionController();
$itemController = new ItemController();
$personController = new PersonController();
$relationshipController = new RelationshipController();

$app->get('/api/health', [$healthController, 'app']);
$app->get('/api/health/database', [$healthController, 'database']);
$app->post('/api/auth/login', [$authController, 'login']);

$app->get('/api/public/avaliacoes', [$publicEvaluationController, 'index']);
$app->get(
    '/api/public/avaliacoes/{versionId:[0-9]+}',
    [$publicEvaluationController, 'show']
);
$app->post(
    '/api/public/avaliacoes/{versionId:[0-9]+}/iniciar',
    [$publicEvaluationController, 'create']
);
$app->get(
    '/api/public/acessos/{token}',
    [$participantAccessController, 'show']
);
$app->post(
    '/api/public/acessos/{token}/identificacao',
    [$participantAccessController, 'identify']
);
$app->get(
    '/api/public/acessos/{token}/questionario',
    [$participantAccessController, 'questionnaire']
);
$app->put(
    '/api/public/acessos/{token}/respostas/{itemId:[0-9]+}',
    [$participantAccessController, 'saveResponse']
);
$app->post(
    '/api/public/acessos/{token}/itens/{itemId:[0-9]+}/nao-se-aplica',
    [$participantAccessController, 'excludeItem']
);
$app->post(
    '/api/public/acessos/{token}/concluir',
    [$participantAccessController, 'complete']
);

$app->group('/api/profissional', function (RouteCollectorProxy $group) use (
    $alternativeController,
    $applicationController,
    $authController,
    $professionalController,
    $instrumentController,
    $instrumentVersionController,
    $sectionController,
    $itemController,
    $personController,
    $relationshipController
) {
    $group->get('/me', [$authController, 'me']);
    $group->put('/senha', [$authController, 'changePassword']);
    $group->get('/perfil', [$professionalController, 'profile']);
    $group->put('/perfil', [$professionalController, 'updateProfile']);

    $group->get('/pessoas', [$personController, 'index']);
    $group->post('/pessoas', [$personController, 'create']);
    $group->get('/pessoas/{id:[0-9]+}', [$personController, 'show']);
    $group->put('/pessoas/{id:[0-9]+}', [$personController, 'update']);
    $group->delete('/pessoas/{id:[0-9]+}', [$personController, 'delete']);

    $group->get('/vinculos', [$relationshipController, 'index']);
    $group->post('/vinculos', [$relationshipController, 'create']);
    $group->get('/vinculos/{id:[0-9]+}', [$relationshipController, 'show']);
    $group->put('/vinculos/{id:[0-9]+}', [$relationshipController, 'update']);
    $group->delete('/vinculos/{id:[0-9]+}', [$relationshipController, 'delete']);

    $group->get('/aplicacoes/opcoes', [$applicationController, 'options']);
    $group->get('/aplicacoes', [$applicationController, 'index']);
    $group->post('/aplicacoes', [$applicationController, 'create']);
    $group->get('/aplicacoes/{id:[0-9]+}', [$applicationController, 'show']);
    $group->get(
        '/aplicacoes/{id:[0-9]+}/resultados',
        [$applicationController, 'results']
    );
    $group->post(
        '/aplicacoes/{id:[0-9]+}/resultados/calcular',
        [$applicationController, 'calculateResults']
    );

    $group->get('/instrumentos', [$instrumentController, 'index']);
    $group->post('/instrumentos', [$instrumentController, 'create']);
    $group->get('/instrumentos/{id:[0-9]+}', [$instrumentController, 'show']);
    $group->put('/instrumentos/{id:[0-9]+}', [$instrumentController, 'update']);
    $group->delete('/instrumentos/{id:[0-9]+}', [$instrumentController, 'delete']);

    $group->get(
        '/instrumentos/{instrumentId:[0-9]+}/versoes',
        [$instrumentVersionController, 'index']
    );
    $group->post(
        '/instrumentos/{instrumentId:[0-9]+}/versoes',
        [$instrumentVersionController, 'create']
    );
    $group->put(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}',
        [$instrumentVersionController, 'update']
    );
    $group->post(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/publicar',
        [$instrumentVersionController, 'publish']
    );
    $group->post(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/arquivar',
        [$instrumentVersionController, 'archive']
    );
    $group->delete(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}',
        [$instrumentVersionController, 'delete']
    );

    $group->get(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes',
        [$sectionController, 'index']
    );
    $group->post(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes',
        [$sectionController, 'create']
    );
    $group->put(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}',
        [$sectionController, 'update']
    );
    $group->delete(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}',
        [$sectionController, 'delete']
    );

    $group->get(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}/itens',
        [$itemController, 'index']
    );
    $group->post(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}/itens',
        [$itemController, 'create']
    );
    $group->put(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}/itens/{itemId:[0-9]+}',
        [$itemController, 'update']
    );
    $group->delete(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}/itens/{itemId:[0-9]+}',
        [$itemController, 'delete']
    );

    $group->get(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}/itens/{itemId:[0-9]+}/alternativas',
        [$alternativeController, 'index']
    );
    $group->post(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}/itens/{itemId:[0-9]+}/alternativas',
        [$alternativeController, 'create']
    );
    $group->put(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}/itens/{itemId:[0-9]+}/alternativas/{alternativeId:[0-9]+}',
        [$alternativeController, 'update']
    );
    $group->delete(
        '/instrumentos/{instrumentId:[0-9]+}/versoes/{versionId:[0-9]+}/secoes/{sectionId:[0-9]+}/itens/{itemId:[0-9]+}/alternativas/{alternativeId:[0-9]+}',
        [$alternativeController, 'delete']
    );
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
