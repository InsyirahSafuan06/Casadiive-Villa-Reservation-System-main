<?php
# declare strict_types=1 untuk php check jenis data dgn ketat dalam fail ni
declare(strict_types=1);
# calling function require_once() untuk load fail db.php, dapatkan sambungan $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail api_auth.php, dapatkan function api_authenticate()
require_once __DIR__ . '/../includes/api_auth.php';

# calling function api_authenticate() untuk check api key valid ke tak, kalau tak valid terus stop
api_authenticate();

# calling method query() & fetchAll() dari object $pdo that assign to variable name $rows untuk ambil data setiap unit accommodation dgn jumlah booking & revenue dia
$rows = $pdo->query(
    "SELECT a.accommodation_id,
            a.accommodation_name,
            a.accommodation_type,
            a.capacity,
            a.price,
            a.price_weekend,
            a.status,
            COUNT(DISTINCT CASE WHEN b.booking_status <> 'cancelled' THEN b.booking_id END) AS total_bookings,
            COALESCE(SUM(CASE WHEN b.booking_status <> 'cancelled' THEN DATEDIFF(b.check_out, b.check_in) ELSE 0 END), 0) AS total_nights_booked,
            COALESCE(SUM(CASE WHEN b.booking_status IN ('confirmed','checked_in','checked_out') THEN bi.price * bi.quantity ELSE 0 END), 0) AS revenue_from_unit
     FROM accommodation a
     LEFT JOIN booking_item bi ON bi.accommodation_id = a.accommodation_id
     LEFT JOIN booking b ON b.booking_id = bi.booking_id
     GROUP BY a.accommodation_id
     ORDER BY a.accommodation_type, a.accommodation_id"
)->fetchAll();

# loop setiap row dalam $rows (guna reference &) untuk tukar jenis data ke int/float yang betul supaya output json tepat
foreach ($rows as &$row) {
    $row['capacity'] = (int) $row['capacity'];
    $row['price'] = (float) $row['price'];
    # check kalau price_weekend ada value ke tak, kalau ada tukar jadi float, kalau tak biar null
    $row['price_weekend'] = $row['price_weekend'] !== null ? (float) $row['price_weekend'] : null;
    $row['total_bookings'] = (int) $row['total_bookings'];
    $row['total_nights_booked'] = (int) $row['total_nights_booked'];
    $row['revenue_from_unit'] = (float) $row['revenue_from_unit'];
}
# calling function unset() untuk buang reference $row lepas guna dalam foreach, elak bug
unset($row);

# calling function api_send() untuk hantar $rows sebagai output json
api_send($rows);
