<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

$dbHost = 'localhost';
$dbName = 'sabrisae_casadivevilla';
$dbUser = 'sabrisae_casadivevilla';
$dbPass = 'casaDiveVilla_2026';

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $e) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=sabrisae_casadivevilla;charset=utf8mb4', 'root', '', $pdoOptions);
    } catch (PDOException $e2) {
        http_response_code(500);
        die('Database connection failed. Make sure MySQL is running and database/database.sql has been imported.');
    }
}
