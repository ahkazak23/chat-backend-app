<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database\Database;

class UserController
{
    /**
     * Create a new user.
     * Route: POST /users
     * Body: { "username": "alice" }
     */
    public function create(Request $request, Response $response, array $args): Response
    {
        $pdo = Database::getConnection();
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['username']) || trim($data['username']) === '') {
            return $this->jsonResponse($response, ['error' => 'username is required and cannot be empty.'], 400);
        }

        $username = trim($data['username']);

        $stmtCheck = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $stmtCheck->execute([':username' => $username]);

        if ($stmtCheck->fetch()) {
            return $this->jsonResponse($response, ['error' => 'This username already exists.'], 409);
        }

        $stmt = $pdo->prepare('INSERT INTO users (username) VALUES (:username)');
        $stmt->execute([':username' => $username]);

        $result = [
            'id' => (int) $pdo->lastInsertId(),
            'username' => $username
        ];

        return $this->jsonResponse($response, $result, 201);
    }

    /**
     * Return JSON response with status code.
     */
    private function jsonResponse(Response $response, array $data, int $statusCode): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
