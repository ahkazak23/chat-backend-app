# Chat Backend API

This project is a RESTful backend for a basic group messaging system. Built using the Slim Framework and SQLite, it supports user registration, group creation, joining groups, and exchanging messages. Integration tests are provided using PHPUnit and Guzzle.

## Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Database Migration](#database-migration)
- [API Endpoints](#api-endpoints)
- [Running Tests](#running-tests)
- [Optional Improvements](#optional-improvements)
- [License](#license)

## Features

- User creation
- Group creation and membership
- Sending messages to groups
- Listing messages in a group
- Test-driven development with full test coverage

## Technology Stack

- PHP 7.4+
- Slim Framework 4
- SQLite (file-based database)
- PHPUnit (testing)
- Guzzle (HTTP client for tests)
- Composer (dependency management)

## Project Structure

```

.
├── app/              # Controllers and DB classes
├── routes/           # Route definitions for users, groups, messages
├── public/           # Entry point (index.php)
├── data/             # SQLite database files
├── tests/            # PHPUnit tests
├── migrate.php       # CLI database migration script
├── composer.json     # Composer package config
└── .env              # Optional: override SQLITE\_PATH

````

## Installation

1. Clone the repository:

```bash
git clone <your-repo-url>
cd chat-backend-app
````

2. Install dependencies:

```bash
composer install
```

3. Start the development server:

```bash
php -S localhost:8080 -t public
```

## Database Migration

Run the migration script to create required tables:

```bash
php migrate.php
```

> Default DB location is `data/database.sqlite`.
> You can override it with an environment variable:

```bash
export SQLITE_PATH=./data/custom.sqlite
```

## API Endpoints

| Method | Endpoint                | Description            |
| ------ | ----------------------- | ---------------------- |
| POST   | `/users`                | Create new user        |
| POST   | `/groups`               | Create new group       |
| POST   | `/groups/{id}/join`     | Add user to group      |
| POST   | `/groups/{id}/message`  | Send message to group  |
| GET    | `/groups/{id}/messages` | List messages in group |

Request bodies are expected to be in JSON format with `Content-Type: application/json`.

## Running Tests

All tests are integration-level and simulate actual HTTP requests using a fresh test database.

To run all tests:

```bash
./vendor/bin/phpunit tests
```

Typical output:

```
OK (16 tests, 41 assertions)
```

Each test run will:

* Reset the test database (`data/database_test.sqlite`)
* Create test users and groups
* Validate all edge cases and HTTP response codes

## Optional Improvements

The following features are not required but may be added:

* **Frontend**: A simple HTML/JS page to interact with the API.
* **Authentication**: Token-based system (e.g., JWT) to protect endpoints.
* **Docker Support**: Dockerfile and `docker-compose.yml` for containerized setup.

## License

This project is licensed under the MIT License.
