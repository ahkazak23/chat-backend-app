<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseTests.php';

use GuzzleHttp\Client;

final class GroupTest extends BaseTests
{
    public function testCreateGroupSuccess(): void
    {
        $groupName = 'group_' . uniqid();

        $response = self::$client->post('/groups', [
            'json' => ['name' => $groupName]
        ]);

        $this->assertEquals(201, $response->getStatusCode(), 'Group should be created (201).');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $body, 'Response JSON should contain "id".');
        $this->assertEquals($groupName, $body['name'], 'Returned group name should match.');
    }

    public function testCreateGroupConflict(): void
    {
        $groupName = 'group_' . uniqid();

        // First creation should succeed
        $resp1 = self::$client->post('/groups', [
            'json' => ['name' => $groupName]
        ]);
        $this->assertEquals(201, $resp1->getStatusCode(), 'First group creation should return 201.');

        // Second creation with same name should fail with 409
        $resp2 = self::$client->post('/groups', [
            'json' => ['name' => $groupName]
        ]);
        $this->assertEquals(409, $resp2->getStatusCode(), 'Duplicate group name should return 409.');
    }

    public function testCreateGroupBadRequest(): void
    {
        // Missing 'name' field → should return 400
        $response = self::$client->post('/groups', [
            'json' => []
        ]);
        $this->assertEquals(400, $response->getStatusCode(), 'Missing name field should return 400.');
    }
}
