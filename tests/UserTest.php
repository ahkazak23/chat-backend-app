<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseTests.php';

use GuzzleHttp\Client;

final class UserTest extends BaseTests
{
    public function testCreateUserSuccess(): void
    {
        // Generate a unique username
        $username = 'user_' . uniqid();

        $response = self::$client->post('/users', [
            'json' => ['username' => $username]
        ]);
        $this->assertEquals(201, $response->getStatusCode(), 'New user should be created (201).');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $body, 'Response JSON should contain an "id" field.');
        $this->assertEquals($username, $body['username'], 'Username should match the input.');
    }

    public function testCreateUserConflict(): void
    {
        // Create a unique username
        $username = 'user_' . uniqid();

        // 1) First creation → should return 201
        $resp1 = self::$client->post('/users', [
            'json' => ['username' => $username]
        ]);
        $this->assertEquals(201, $resp1->getStatusCode(), 'First creation should return 201.');

        // 2) Try to create again with the same username → should return 409
        $resp2 = self::$client->post('/users', [
            'json' => ['username' => $username]
        ]);
        $this->assertEquals(409, $resp2->getStatusCode(), 'Duplicate username should return 409.');
    }
}
