<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

abstract class BaseTests extends TestCase
{
    protected static Client $client;

    public static function setUpBeforeClass(): void
    {
        $dbFile = __DIR__ . '/../data/database_test.sqlite';

        // Remove old test database if it exists
        if (file_exists($dbFile)) {
            @unlink($dbFile);
            usleep(100_000); // Wait briefly to avoid file lock issues
        }

        // Create new test database file
        touch($dbFile);

        // Set environment variable to use test database
        putenv('SQLITE_PATH=' . realpath($dbFile));

        // Run database migrations
        require __DIR__ . '/../migrate.php';

        // Initialize HTTP client
        self::$client = new Client([
            'base_uri'    => 'http://localhost:8080',
            'http_errors' => false,
        ]);
    }
}
