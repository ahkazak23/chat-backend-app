<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseTests.php';

use GuzzleHttp\Client;

final class GroupJoinTest extends BaseTests
{
    private static int $userId;
    private static int $groupId;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Create a new user
        $username = 'joinuser_' . uniqid();
        $respUser = self::$client->post('/users', [
            'json' => ['username' => $username]
        ]);
        if ($respUser->getStatusCode() !== 201) {
            throw new \RuntimeException('Expected 201 creating user, got ' . $respUser->getStatusCode());
        }
        $bodyUser = json_decode((string) $respUser->getBody(), true);
        self::$userId = $bodyUser['id'];

        // Create a new group
        $groupName = 'joingroup_' . uniqid();
        $respGroup = self::$client->post('/groups', [
            'json' => ['name' => $groupName]
        ]);
        if ($respGroup->getStatusCode() !== 201) {
            throw new \RuntimeException('Expected 201 creating group, got ' . $respGroup->getStatusCode());
        }
        $bodyGroup = json_decode((string) $respGroup->getBody(), true);
        self::$groupId = $bodyGroup['id'];
    }

    public function testJoinGroupSuccess(): void
    {
        $response = self::$client->post("/groups/" . self::$groupId . "/join", [
            'json' => ['user_id' => self::$userId]
        ]);
        $this->assertEquals(201, $response->getStatusCode(), 'User should join the group successfully (201).');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('message', $body);
        $this->assertEquals(self::$groupId, $body['group_id']);
        $this->assertEquals(self::$userId, $body['user_id']);
    }

    public function testJoinGroupConflict(): void
    {
        // First attempt (could be 201 or 409 depending on state)
        $resp1 = self::$client->post("/groups/" . self::$groupId . "/join", [
            'json' => ['user_id' => self::$userId]
        ]);
        $this->assertTrue(
            in_array($resp1->getStatusCode(), [201, 409]),
            'First join request should return 201 or 409.'
        );

        // Second attempt must return 409
        $resp2 = self::$client->post("/groups/" . self::$groupId . "/join", [
            'json' => ['user_id' => self::$userId]
        ]);
        $this->assertEquals(409, $resp2->getStatusCode(), 'Duplicate join should return 409.');
    }

    public function testJoinGroupNotFoundGroup(): void
    {
        $invalidGroupId = 999999;
        $resp = self::$client->post("/groups/$invalidGroupId/join", [
            'json' => ['user_id' => self::$userId]
        ]);
        $this->assertEquals(404, $resp->getStatusCode(), 'Invalid group_id should return 404.');
    }

    public function testJoinGroupNotFoundUser(): void
    {
        $invalidUserId = 999999;
        $resp = self::$client->post("/groups/" . self::$groupId . "/join", [
            'json' => ['user_id' => $invalidUserId]
        ]);
        $this->assertEquals(404, $resp->getStatusCode(), 'Invalid user_id should return 404.');
    }
}
