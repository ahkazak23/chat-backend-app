<?php

require __DIR__ . '/vendor/autoload.php';

use App\Database\Database;

// Initialize database schema (only if not already created)
Database::initializeSchema();

echo "SQLite tables have been created or already exist.\n";
