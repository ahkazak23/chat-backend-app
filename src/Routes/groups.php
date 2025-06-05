<?php

use Slim\App;
use App\Controllers\GroupController;

return function (App $app): void {
    // Create a new group
    $app->post('/groups', GroupController::class . ':create');

    // Add a user to an existing group
    $app->post('/groups/{id}/join', GroupController::class . ':join');
};
