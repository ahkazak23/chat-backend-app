<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database\Database;

class MessageController
{
    /**
     * Send a message to a group.
     * Route: POST /groups/{id}/message
     * Body: { "user_id": 1, "content": "Hello world!" }
     */
    public function send(Request $request, Response $response, array $args): Response
    {
        $pdo = Database::getConnection();
        $groupId = (int) $args['id'];
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'user_id is required and must be numeric.'], 400);
        }

        if (!isset($data['content']) || trim($data['content']) === '') {
            return $this->jsonResponse($response, ['error' => 'content is required and cannot be empty.'], 400);
        }

        $userId = (int) $data['user_id'];
        $content = trim($data['content']);

        if (!$this->exists($pdo, 'groups', 'id', $groupId)) {
            return $this->jsonResponse($response, ['error' => 'Group not found.'], 404);
        }

        if (!$this->exists($pdo, 'users', 'id', $userId)) {
            return $this->jsonResponse($response, ['error' => 'User not found.'], 404);
        }

        if (!$this->isUserInGroup($pdo, $userId, $groupId)) {
            return $this->jsonResponse($response, ['error' => 'User has not joined this group.'], 403);
        }

        $stmtInsert = $pdo->prepare('
            INSERT INTO messages (group_id, user_id, content) 
            VALUES (:gid, :uid, :content)
        ');
        $stmtInsert->execute([
            ':gid' => $groupId,
            ':uid' => $userId,
            ':content' => $content
        ]);

        $result = [
            'id' => (int) $pdo->lastInsertId(),
            'group_id' => $groupId,
            'user_id' => $userId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->jsonResponse($response, $result, 201);
    }

    /**
     * List all messages in a group.
     * Route: GET /groups/{id}/messages
     */
    public function list(Request $request, Response $response, array $args): Response
    {
        $pdo = Database::getConnection();
        $groupId = (int) $args['id'];

        if (!$this->exists($pdo, 'groups', 'id', $groupId)) {
            return $this->jsonResponse($response, ['error' => 'Group not found.'], 404);
        }

        $stmt = $pdo->prepare('
            SELECT 
                m.id, 
                m.content, 
                m.created_at, 
                u.id AS user_id, 
                u.username 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.group_id = :gid 
            ORDER BY m.created_at ASC
        ');
        $stmt->execute([':gid' => $groupId]);
        $messages = $stmt->fetchAll();

        return $this->jsonResponse($response, $messages, 200);
    }

    /**
     * Check if a record exists in a given table.
     */
    private function exists(\PDO $pdo, string $table, string $column, $value): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM $table WHERE $column = :value");
        $stmt->execute([':value' => $value]);
        return (bool) $stmt->fetch();
    }

    /**
     * Check if a user is part of the group.
     */
    private function isUserInGroup(\PDO $pdo, int $userId, int $groupId): bool
    {
        $stmt = $pdo->prepare('
            SELECT 1 FROM group_users 
            WHERE user_id = :uid AND group_id = :gid
        ');
        $stmt->execute([':uid' => $userId, ':gid' => $groupId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Return JSON response with HTTP status code.
     */
    private function jsonResponse(Response $response, array $data, int $statusCode): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
