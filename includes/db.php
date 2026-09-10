<?php
/**
 * Fail sambungan pangkalan data.
 * Fail ni yang buat sambungan ke database — semua fail lain dalam sistem ni panggil fail ni
 * bila diorang nak cakap dengan database.
 */
declare(strict_types=1); // bagitahu PHP kita nak jenis data yang ketat, tak boleh tukar jenis sesuka hati

// kedai kita kat Malaysia, so kita set waktu sistem ikut waktu Malaysia —
// kalau tak set, pengiraan "esok" untuk reminder check-in boleh jadi salah
date_default_timezone_set('Asia/Kuala_Lumpur');

$dbHost = 'localhost'; // hosting production (ruangprojek.com) — localhost merujuk kepada server hosting itu sendiri
$dbName = 'sabrisae_casadivevilla'; // nama database production
$dbUser = 'sabrisae_casadivevilla'; // username untuk masuk database production
$dbPass = 'casaDiveVilla_2026'; // password untuk masuk database production

try {
    // sini kita betul-betul sambung ke database, guna maklumat yang kita set kat atas tadi
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // kalau ada error SQL, terus bagitahu kita — jangan senyap-senyap je
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // bila kita ambil data, bagi dalam bentuk array biasa, senang nak guna
            PDO::ATTR_EMULATE_PREPARES => false,                  // guna cara query yang lebih selamat, elak serangan SQL injection
        ]
    );
} catch (PDOException $e) {
    // kalau sambungan database gagal (contohnya XAMPP tak start lagi), kita stop terus
    // dan bagitahu pengguna dengan mesej yang jelas, daripada biar sistem crash pelik-pelik
    http_response_code(500);
    die('Database connection failed. Make sure MySQL is running and database/database.sql has been imported.');
}
