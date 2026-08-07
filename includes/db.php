<?php
/**
 * Fail sambungan pangkalan data.
 * Fail ini mencipta sambungan PDO yang digunakan oleh kebanyakan halaman dalam sistem ini.
 */
declare(strict_types=1);

// Perniagaan ini berada di Malaysia, jadi semua semakan tarikh "hari ini"/"esok" (contohnya
// logik peringatan check-in) mesti guna waktu Malaysia, bukan zon waktu lalai pelayan.
date_default_timezone_set('Asia/Kuala_Lumpur');

// Kelayakan pangkalan data lalai XAMPP — tukar ini jika anda deploy
// ke pelayan sebenar dengan pengguna/kata laluan MySQL yang berbeza.
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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // lontar exception bila ada ralat SQL, bukan gagal senyap
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // fetch() / fetchAll() pulangkan array bersekutu secara lalai
            PDO::ATTR_EMULATE_PREPARES => false,                  // guna prepared statement sebenar (lebih selamat dari SQL injection)
        ]
    );
} catch (PDOException $e) {
    // Tiada apa yang boleh dibuat tanpa pangkalan data, jadi hentikan halaman dengan mesej yang jelas.
    http_response_code(500);
    die('Database connection failed. Make sure MySQL is running and database/database.sql has been imported.');
}
