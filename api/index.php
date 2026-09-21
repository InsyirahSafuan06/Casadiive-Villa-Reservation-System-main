<?php
/**
 * Senarai endpoint API laporan yang ada — takde data sebenar dipaparkan sini, so
 * page ni sendiri takyah API key (cuma dokumentasi macam mana nak guna endpoint lain).
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'description' => 'Read-only reporting API for Casadive Villa — built for Power BI.',
    'auth' => 'Every endpoint below requires the API key, sent as either the "X-Api-Key" header or a "?key=" query parameter.',
    'endpoints' => [
        'bookings.php' => 'One row per booking — dates, status, guest count, revenue, accommodation booked.',
        'occupancy.php' => 'One row per accommodation unit — bookings, nights booked, and revenue for that unit.',
        'payments.php' => 'One row per payment record — amount, method, status, linked booking.',
        'reviews.php' => 'One row per review — rating, comment, public display name (never the real booking name), photo flag.',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
