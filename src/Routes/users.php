<?php

use Slim\App;
use App\Controllers\UserController;

return function (App $app): void {
    // Create a new user
    $app->post('/users', UserController::class . ':create');
};
