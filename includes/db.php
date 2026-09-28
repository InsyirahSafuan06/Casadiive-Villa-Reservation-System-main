<?php
declare(strict_types=1);

# calling function date_default_timezone_set() untuk set default timezone semua fungsi tarikh/masa ikut Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

# assign value host database ke $dbHost
$dbHost = 'localhost';
# assign value nama database ke $dbName
$dbName = 'sabrisae_casadivevilla';
# assign value username database ke $dbUser
$dbUser = 'sabrisae_casadivevilla';
# assign value password database ke $dbPass
$dbPass = 'casaDiveVilla_2026';

# assign array setting PDO ke $pdoOptions untuk throw exception bila error, fetch as assoc array, guna real prepared statement
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    # calling function new PDO() that assign to variable name $pdo untuk sambung ke database guna kredential production
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $e) {
    try {
        # calling function new PDO() that assign to variable name $pdo untuk cuba sambung guna kredential root local (fallback XAMPP)
        $pdo = new PDO('mysql:host=localhost;dbname=sabrisae_casadivevilla;charset=utf8mb4', 'root', '', $pdoOptions);
    } catch (PDOException $e2) {
        # calling function http_response_code() untuk set response code 500 (server error)
        http_response_code(500);
        # calling function die() untuk stop script dan papar mesej sambungan database gagal
        die('Database connection failed. Make sure MySQL is running and database/database.sql has been imported.');
    }
}
