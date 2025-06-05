<?php

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    /** @var PDO|null */
    private static ?PDO $instance = null;

    /**
     * Returns a singleton PDO connection to the SQLite database.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $path = getenv('SQLITE_PATH') ?: (__DIR__ . '/../../data/database.sqlite');
                $pdo = new PDO('sqlite:' . $path);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Enable foreign key constraints in SQLite
                $pdo->exec('PRAGMA foreign_keys = ON');

                self::$instance = $pdo;
            } catch (PDOException $e) {
                die('SQLite connection error: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Creates all required tables if they do not exist.
     */
    public static function initializeSchema(): void
    {
        $pdo = self::getConnection();

        $queries = [
            // Users table
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE
            )',

            // Groups table
            'CREATE TABLE IF NOT EXISTS groups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE
            )',

            // Group membership table
            'CREATE TABLE IF NOT EXISTS group_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                group_id INTEGER NOT NULL,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE CASCADE,
                UNIQUE(user_id, group_id)
            )',

            // Messages table
            'CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE CASCADE,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            )',
        ];

        foreach ($queries as $sql) {
            $pdo->exec($sql);
        }
    }
}
