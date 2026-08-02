<?php
/**
 * Database connection file.
 * This file creates the PDO connection used by most pages in the system.
 */
declare(strict_types=1);

$dbHost = 'localhost';
$dbName = 'casadive_villa_reservation';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Make sure MySQL is running and database/database.sql has been imported.');
}
