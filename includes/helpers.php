<?php
/**
 * Fungsi bantuan untuk format dan pengiraan harga.
 * Fungsi-fungsi kecil ini memudahkan pengurusan dan paparan proses tempahan.
 */
declare(strict_types=1);

/** Tukar nilai enum snake_case seperti 'checked_in' kepada 'Checked In' untuk paparan. */
function format_status(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

/**
 * Bahagikan tempoh penginapan kepada malam hari biasa vs. hujung minggu (Jumaat & Sabtu
 * dikira sebagai hujung minggu) dan kira harga setiap malam mengikutnya. $weekendPrice
 * akan guna $weekdayPrice jika null. Harga cuti umum tidak digunakan di sini — itu
 * memerlukan kalendar cuti yang sistem ini tiada; price_holiday pada jadual
 * `accommodation` hanya untuk paparan sahaja buat masa ini.
 */
function compute_stay_price(float $weekdayPrice, ?float $weekendPrice, DateTime $checkIn, DateTime $checkOut): array
{
    $weekendPrice ??= $weekdayPrice; // kalau tak set harga weekend, guna harga weekday je
    $weekdayNights = 0; // kira jumlah malam hari biasa
    $weekendNights = 0; // kira jumlah malam hujung minggu

    $cursor = clone $checkIn; // kita "jalan" dari tarikh check-in, hari demi hari
    while ($cursor < $checkOut) {
        $isWeekend = in_array((int) $cursor->format('N'), [5, 6], true); // Jumaat, Sabtu
        $isWeekend ? $weekendNights++ : $weekdayNights++; // tambah kira ikut hari tu weekend ke tak
        $cursor->modify('+1 day'); // pergi ke hari seterusnya
    }

    // pulangkan berapa malam setiap jenis, dan jumlah harga keseluruhan
    return [
        'weekday_nights' => $weekdayNights,
        'weekend_nights' => $weekendNights,
        'total' => round($weekdayNights * $weekdayPrice + $weekendNights * $weekendPrice, 2),
    ];
}
