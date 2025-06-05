<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database\Database;

class GroupController
{
    /**
     * Create a new group.
     * Route: POST /groups
     * Body: { "name": "groupname" }
     */
    public function create(Request $request, Response $response, array $args): Response
    {
        $pdo = Database::getConnection();
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['name']) || trim($data['name']) === '') {
            $error = ['error' => 'Group name is required and cannot be empty.'];
            return $this->jsonResponse($response, $error, 400);
        }

        $name = trim($data['name']);

        $stmtCheck = $pdo->prepare('SELECT id FROM groups WHERE name = :name');
        $stmtCheck->execute([':name' => $name]);

        if ($stmtCheck->fetch()) {
            $error = ['error' => 'A group with this name already exists.'];
            return $this->jsonResponse($response, $error, 409);
        }

        $stmt = $pdo->prepare('INSERT INTO groups (name) VALUES (:name)');
        $stmt->execute([':name' => $name]);

        $result = [
            'id' => (int) $pdo->lastInsertId(),
            'name' => $name
        ];

        return $this->jsonResponse($response, $result, 201);
    }

    /**
     * Add user to a group.
     * Route: POST /groups/{id}/join
     * Body: { "user_id": 1 }
     */
    public function join(Request $request, Response $response, array $args): Response
    {
        $pdo = Database::getConnection();
        $groupId = (int) $args['id'];
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            $error = ['error' => 'user_id is required and must be numeric.'];
            return $this->jsonResponse($response, $error, 400);
        }

        $userId = (int) $data['user_id'];

        if (!$this->exists($pdo, 'groups', 'id', $groupId)) {
            return $this->jsonResponse($response, ['error' => 'Group not found.'], 404);
        }

        if (!$this->exists($pdo, 'users', 'id', $userId)) {
            return $this->jsonResponse($response, ['error' => 'User not found.'], 404);
        }

        $stmtCheck = $pdo->prepare('SELECT id FROM group_users WHERE user_id = :uid AND group_id = :gid');
        $stmtCheck->execute([':uid' => $userId, ':gid' => $groupId]);

        if ($stmtCheck->fetch()) {
            return $this->jsonResponse($response, ['error' => 'User is already in the group.'], 409);
        }

        $stmtInsert = $pdo->prepare('INSERT INTO group_users (user_id, group_id) VALUES (:uid, :gid)');
        $stmtInsert->execute([':uid' => $userId, ':gid' => $groupId]);

        $result = [
            'message' => 'User successfully joined the group.',
            'group_id' => $groupId,
            'user_id' => $userId
        ];

        return $this->jsonResponse($response, $result, 201);
    }

    /**
     * Helper: check if a record exists in a given table.
     */
    private function exists(\PDO $pdo, string $table, string $column, $value): bool
    {
        $query = sprintf('SELECT 1 FROM %s WHERE %s = :value', $table, $column);
        $stmt = $pdo->prepare($query);
        $stmt->execute([':value' => $value]);
        return (bool) $stmt->fetch();
    }

    /**
     * Helper: return a JSON response with status.
     */
    private function jsonResponse(Response $response, array $data, int $statusCode): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
