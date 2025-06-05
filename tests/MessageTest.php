<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseTests.php';

use GuzzleHttp\Client;

final class MessageTest extends BaseTests
{
    private static int $userId;
    private static int $groupId;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // 1) Create a new user
        $username = 'msguser_' . uniqid();
        $respUser = self::$client->post('/users', [
            'json' => ['username' => $username]
        ]);
        if ($respUser->getStatusCode() !== 201) {
            throw new \RuntimeException('Expected 201 creating user, got ' . $respUser->getStatusCode());
        }
        $bodyUser = json_decode((string) $respUser->getBody(), true);
        self::$userId = $bodyUser['id'];

        // 2) Create a new group
        $groupName = 'msggroup_' . uniqid();
        $respGroup = self::$client->post('/groups', [
            'json' => ['name' => $groupName]
        ]);
        if ($respGroup->getStatusCode() !== 201) {
            throw new \RuntimeException('Expected 201 creating group, got ' . $respGroup->getStatusCode());
        }
        $bodyGroup = json_decode((string) $respGroup->getBody(), true);
        self::$groupId = $bodyGroup['id'];

        // 3) Join user to the group
        $joinResp = self::$client->post("/groups/" . self::$groupId . "/join", [
            'json' => ['user_id' => self::$userId]
        ]);
        if (!in_array($joinResp->getStatusCode(), [201, 409])) {
            throw new \RuntimeException('Expected 201 or 409 joining group, got ' . $joinResp->getStatusCode());
        }
    }

    public function testSendMessageSuccessAscii(): void
    {
        $content = 'Hello world!';

        $response = self::$client->post("/groups/" . self::$groupId . "/message", [
            'json' => [
                'user_id' => self::$userId,
                'content' => $content
            ]
        ]);
        $this->assertEquals(201, $response->getStatusCode(), 'ASCII message should return 201.');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $body);
        $this->assertEquals($content, $body['content']);
        $this->assertEquals(self::$userId, $body['user_id']);
        $this->assertEquals(self::$groupId, $body['group_id']);
    }

    public function testSendMessageSuccessUtf8(): void
    {
        $content = 'Merhaba dünya!';

        $response = self::$client->post("/groups/" . self::$groupId . "/message", [
            'json' => [
                'user_id' => self::$userId,
                'content' => $content
            ]
        ]);
        $this->assertEquals(201, $response->getStatusCode(), 'UTF-8 message should return 201.');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertStringContainsString('dünya', $body['content'], 'Message content should contain UTF-8 chars.');
    }

    public function testSendMessageUserNotInGroup(): void
    {
        // Create another user not in the group → expect 403
        $otherUsername = 'other_' . uniqid();
        $respUser2 = self::$client->post('/users', [
            'json' => ['username' => $otherUsername]
        ]);
        if ($respUser2->getStatusCode() !== 201) {
            throw new \RuntimeException('Expected 201 creating other user, got ' . $respUser2->getStatusCode());
        }
        $bodyUser2 = json_decode((string) $respUser2->getBody(), true);
        $otherUserId = $bodyUser2['id'];

        $resp = self::$client->post("/groups/" . self::$groupId . "/message", [
            'json' => [
                'user_id' => $otherUserId,
                'content' => 'Hi!'
            ]
        ]);
        $this->assertEquals(403, $resp->getStatusCode(), 'User not in group should return 403.');
    }

    public function testSendMessageBadRequestMissingContent(): void
    {
        $resp = self::$client->post("/groups/" . self::$groupId . "/message", [
            'json' => ['user_id' => self::$userId]
        ]);
        $this->assertEquals(400, $resp->getStatusCode(), 'Missing content field should return 400.');
    }

    public function testSendMessageNotFoundGroup(): void
    {
        $resp = self::$client->post("/groups/999999/message", [
            'json' => [
                'user_id' => self::$userId,
                'content' => 'No group'
            ]
        ]);
        $this->assertEquals(404, $resp->getStatusCode(), 'Invalid group_id should return 404.');
    }

    public function testListMessagesSuccess(): void
    {
        // 1) Get initial message count
        $respBefore = self::$client->get("/groups/" . self::$groupId . "/messages", [
            'headers' => ['Accept' => 'application/json']
        ]);
        $this->assertEquals(200, $respBefore->getStatusCode());
        $messagesBefore = json_decode((string) $respBefore->getBody(), true);
        $initialCount = is_array($messagesBefore) ? count($messagesBefore) : 0;

        // 2) Send three new messages
        $toSend = ['Msg A', 'Msg B', 'Msg C'];
        foreach ($toSend as $content) {
            $resp = self::$client->post("/groups/" . self::$groupId . "/message", [
                'json' => [
                    'user_id' => self::$userId,
                    'content' => $content
                ]
            ]);
            $this->assertEquals(201, $resp->getStatusCode(), "Message '{$content}' failed to send.");
        }

        // 3) Get message list again
        $respAfter = self::$client->get("/groups/" . self::$groupId . "/messages", [
            'headers' => ['Accept' => 'application/json']
        ]);
        $this->assertEquals(200, $respAfter->getStatusCode());
        $messagesAfter = json_decode((string) $respAfter->getBody(), true);
        $this->assertIsArray($messagesAfter);

        // 4) Ensure total message count increased
        $sentCount = count($toSend);
        $this->assertGreaterThanOrEqual(
            $initialCount + $sentCount,
            count($messagesAfter),
            "At least {$sentCount} new messages should be listed."
        );

        // 5) Ensure each new message content exists
        $contents = array_column($messagesAfter, 'content');
        foreach ($toSend as $content) {
            $this->assertContains($content, $contents, "'{$content}' not found in message list.");
        }
    }

    public function testListMessagesNotFoundGroup(): void
    {
        $resp = self::$client->get("/groups/999999/messages", [
            'headers' => ['Accept' => 'application/json']
        ]);
        $this->assertEquals(404, $resp->getStatusCode(), 'Invalid group_id should return 404.');
    }
}