<?php

use Slim\App;
use App\Controllers\MessageController;

return function (App $app): void {
    // Send a message to a group
    $app->post('/groups/{id}/message', MessageController::class . ':send');

    // List all messages in a group
    $app->get('/groups/{id}/messages', MessageController::class . ':list');
};
