<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function db(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbDir = __DIR__ . '/../database';
    $dbPath = $dbDir . '/docura.sqlite';

    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        // Automatically initialize SQLite on first use.
        // This makes the deployed app work directly from the main URL;
        // visiting setup.php is no longer required before Sign Up/Sign In.
        $usersTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
        if (!$usersTable) {
            $schemaPath = __DIR__ . '/../database/schema.sql';
            $schema = file_get_contents($schemaPath);
            if ($schema === false || trim($schema) === '') {
                throw new RuntimeException('SQLite schema file is missing.');
            }
            $pdo->exec($schema);
        }

        return $pdo;
    } catch (Throwable $e) {
        json_response(['success' => false, 'message' => 'SQLite database connection failed: ' . $e->getMessage()], 500);
    }
}
