<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Register route files
$routes = [
    __DIR__ . '/../src/Routes/users.php',
    __DIR__ . '/../src/Routes/groups.php',
    __DIR__ . '/../src/Routes/messages.php',
];

foreach ($routes as $routeFile) {
    (require $routeFile)($app);
}

// Health check route
$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write('Chat API is running!');
    return $response->withHeader('Content-Type', 'text/plain');
});

$app->run();
