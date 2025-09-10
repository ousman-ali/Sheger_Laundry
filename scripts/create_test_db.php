<?php
// scripts/create_test_db.php
// Usage: php scripts/create_test_db.php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$root = dirname(__DIR__);
if (file_exists($root . '/.env.testing')) {
    $env = Dotenv::createImmutable($root, '.env.testing');
} elseif (file_exists($root . '/.env')) {
    $env = Dotenv::createImmutable($root, '.env');
} else {
    echo "No .env or .env.testing found. Copy .env.example -> .env.testing and edit credentials.\n";
    exit(1);
}
$env->load();

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_DATABASE') ?: 'shebar_laundry_testing';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

// Fallback: if getenv didn't populate values (common in some CLI setups), parse the .env.testing file directly
if (empty($dbUser) || ($dbPass === '')) {
    $envPath = file_exists($root . '/.env.testing') ? $root . '/.env.testing' : ($root . '/.env');
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue; // skip comments
            if (strpos($line, '=') === false) continue;
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $v = trim($v, "\"'");
            switch ($k) {
                case 'DB_HOST': $dbHost = $v; break;
                case 'DB_PORT': $dbPort = $v; break;
                case 'DB_USERNAME': $dbUser = $v; break;
                case 'DB_PASSWORD': $dbPass = $v; break;
                case 'DB_DATABASE': $dbName = $v; break;
                case 'DB_CHARSET': $charset = $v; break;
            }
        }
    }
}

$dsn = "mysql:host={$dbHost};port={$dbPort};charset={$charset}";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci;");
    echo "Created or verified database: {$dbName}\n";
    exit(0);
} catch (PDOException $e) {
    echo "Failed to create database: " . $e->getMessage() . "\n";
    exit(2);
}
