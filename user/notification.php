<?php
/**
 * Penghantar notifikasi WhatsApp.
 * Bina mesej pra-isi untuk tempahan yang diberikan dan buka melalui pautan
 * wa.me — admin semak dalam WhatsApp Web/App dan tekan Hantar sendiri.
 * (Menghantar secara automatik tanpa langkah manual itu memerlukan WhatsApp
 * Business Cloud API, yang perlukan akaun Meta Business, token akses kekal,
 * dan templat mesej yang diluluskan terlebih dahulu — belum disediakan di sini.)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

// Semak ID tempahan
$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
if (!$booking_id) {
    die("Booking ID not found.");
}

$type = $_GET['type'] ?? "booking_confirmation";
if (!in_array($type, ['booking_confirmation', 'pending', 'check_in', 'check_out','cancelled'], true)) {
    $type = 'booking_confirmation';
}

// Dapatkan butiran tempahan + pelanggan
$sql = "SELECT
            b.booking_id,
            b.customer_id,
            b.check_in,
            b.check_out,
            c.full_name,
            c.phone
        FROM booking b
        INNER JOIN customer c
            ON b.customer_id = c.customer_id
        WHERE b.booking_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$booking_id]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Booking not found.");
}

$customer_id = $data['customer_id'];
$name = $data['full_name'];
$phone = $data['phone'];
$checkin = date("d F Y", strtotime($data['check_in']));
$checkout = date("d F Y", strtotime($data['check_out']));
$checkinTime = "3.00 PM";
$checkoutTime = "12.00 PM";

// WhatsApp perlukan nombor telefon dalam format antarabangsa penuh (bermula dengan
// kod negara, tiada simbol) — contoh: "012-345 6789" jadi "60123456789".
$phone = preg_replace('/[^0-9]/', '', $phone);

if (substr($phone, 0, 1) == "0") {
    $phone = "6" . $phone;
}

// Mesej notifikasi
switch ($type) {

    case "booking_confirmation":

        $message = " CasaDive Villa

Hi $name,
Your booking has been confirmed!

 Check-in : $checkin
 Check-out : $checkout

Thank you for choosing CasaDive Villa.
We look forward to welcoming you!";

    break;

        case "pending":
            $message = " CasaDive Villa
Hi $name,
We noticed that your booking is still pending payment.

Check-in : $checkin
Check-out : $checkout

Kindly complete your payment to confirm your reservation. Thank you.";

 break;

    case "check_in":

        $message = " CasaDive Villa

Hi $name,
This is a friendly reminder that your check-in date is:

 Check-in Date : $checkin
 Check-in Time : After $checkinTime
 Check-out Time : Before $checkoutTime

Door Code: 8876
Kindly keep this code confidential and do not share it with anyone.

If you have any questions or need assistance, feel free to contact us.
We hope you have a safe journey and enjoy your stay at CasaDive Villa.

See you soon! ";

        break;


    case "check_out":

        $message = " CasaDive Villa

Hi $name,

Thank you for staying with CasaDive Villa.

 Check-in : $checkin
 Check-out : $checkout

We hope you enjoyed your vacation.
Have a safe journey and we hope to see you again soon! ";

        break;
             case "cancelled":
                 $message = " CasaDive Villa

Hi $name,

We regret to inform you that your booking has been cancelled.

 Check-in : $checkin
 Check-out : $checkout

If you have any questions or wish to make a new booking, feel free to contact us. Thank you! ";

    break;

    default:

        $message = "Hi $name!";
}


// Simpan rekod notifikasi. Status direkod sebagai "sent" sebaik sahaja admin buka
// pautan ini — tiada cara untuk sahkan dari server sama ada mereka betul-betul tekan Hantar
// dalam WhatsApp selepas itu, kerana langkah terakhir itu berlaku sepenuhnya pada peranti admin sendiri.
$insert = $pdo->prepare("
INSERT INTO notification_status
(booking_id, customer_id, sent_date, status, notification_type, channel)
VALUES (?, ?, NOW(), 'sent', ?, 'whatsapp')
");

$insert->execute([
    $booking_id,
    $customer_id,
    $type
]);

// Buka WhatsApp dengan mesej pra-isi, sedia untuk admin semak dan hantar.
$link = "https://wa.me/".$phone."?text=".urlencode($message);

header("Location: ".$link);
exit;
