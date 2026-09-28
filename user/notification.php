<?php
# calling function require_once() untuk load fail db.php supaya boleh guna $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna require_login()
require_once __DIR__ . '/../includes/auth.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna fetch_booking_door_codes_plain()
require_once __DIR__ . '/../includes/helpers.php';
# calling function require_login() untuk pastikan hanya manager je boleh buka page ni
require_login(['manager']);

# calling function filter_input() that assign to variable name $booking_id untuk ambil & sahkan id booking dari url
$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
# check kalau $booking_id tak sah/takde
if (!$booking_id) {
    # calling function die() untuk stop script dan papar mesej id booking tak jumpa
    die("Booking ID not found.");
}

# ambil value $_GET['type'] that assign to variable name $type, default 'booking_confirmation' kalau takde
$type = $_GET['type'] ?? "booking_confirmation";
# calling function in_array() untuk check jenis notifikasi yang diminta memang valid
if (!in_array($type, ['booking_confirmation', 'pending', 'check_in', 'check_out','cancelled'], true)) {
    # assign value default 'booking_confirmation' ke $type sebab jenis yang diminta tak valid
    $type = 'booking_confirmation';
}

# assign query sql ke $sql untuk ambil detail booking & customer ikut booking_id
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

# calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query $sql
$stmt = $pdo->prepare($sql);
# calling method execute() dari object $stmt untuk jalankan query, isi placeholder dengan $booking_id
$stmt->execute([$booking_id]);

# calling method fetch() dari object $stmt that assign to variable name $data untuk ambil 1 row hasil query
$data = $stmt->fetch(PDO::FETCH_ASSOC);

# check kalau data booking tak jumpa
if (!$data) {
    # calling function die() untuk stop script dan papar mesej booking tak jumpa
    die("Booking not found.");
}

# ambil value $data['customer_id'] that assign to variable name $customer_id
$customer_id = $data['customer_id'];
# ambil value $data['full_name'] that assign to variable name $name
$name = $data['full_name'];
# ambil value $data['phone'] that assign to variable name $phone
$phone = $data['phone'];
# calling function date() & strtotime() that assign to variable name $checkin untuk format tarikh check-in jadi "hari Bulan Tahun"
$checkin = date("d F Y", strtotime($data['check_in']));
# calling function date() & strtotime() that assign to variable name $checkout untuk format tarikh check-out jadi "hari Bulan Tahun"
$checkout = date("d F Y", strtotime($data['check_out']));
# assign value waktu check-in tetap ke $checkinTime
$checkinTime = "3.00 PM";
# assign value waktu check-out tetap ke $checkoutTime
$checkoutTime = "12.00 PM";

# calling function preg_replace() that assign to variable name $phone untuk buang semua aksara bukan nombor dari no telefon
$phone = preg_replace('/[^0-9]/', '', $phone);

# calling function substr() untuk check kalau no telefon start dengan '0'
if (substr($phone, 0, 1) == "0") {
    # assign value "6" digabung dengan $phone ke $phone untuk tukar prefix ke kod negara Malaysia (60)
    $phone = "6" . $phone;
}

# check jenis notifikasi ($type) untuk tentukan mesej whatsapp yang sesuai
switch ($type) {

    case "booking_confirmation":

        # assign mesej confirmation booking ke $message
        $message = " CasaDive Villa

Hi $name,
Your booking has been confirmed!

 Check-in : $checkin
 Check-out : $checkout

Thank you for choosing CasaDive Villa.
We look forward to welcoming you!";

    break;

        case "pending":
            # assign mesej reminder payment pending ke $message
            $message = " CasaDive Villa
Hi $name,
We noticed that your booking is still pending payment.

Check-in : $checkin
Check-out : $checkout

Kindly complete your payment to confirm your reservation. Thank you.";

 break;

    case "check_in":

        # calling function fetch_booking_door_codes_plain() that assign to variable name $doorCode untuk ambil kod pintu booking ni
        $doorCode = fetch_booking_door_codes_plain($pdo, $booking_id);

        # assign mesej reminder check-in sekali dengan kod pintu ke $message
        $message = " CasaDive Villa

Hi $name,
This is a friendly reminder that your check-in date is:

 Check-in Date : $checkin
 Check-in Time : After $checkinTime
 Check-out Time : Before $checkoutTime

Door Code: $doorCode
Kindly keep this code confidential and do not share it with anyone.

If you have any questions or need assistance, feel free to contact us.
We hope you have a safe journey and enjoy your stay at CasaDive Villa.

See you soon! ";

        break;


    case "check_out":

        # assign mesej terima kasih selepas check-out ke $message
        $message = " CasaDive Villa

Hi $name,

Thank you for staying with CasaDive Villa.

 Check-in : $checkin
 Check-out : $checkout

We hope you enjoyed your vacation.
Have a safe journey and we hope to see you again soon! ";

        break;
             case "cancelled":
                 # assign mesej booking dibatalkan ke $message
                 $message = " CasaDive Villa

Hi $name,

We regret to inform you that your booking has been cancelled.

 Check-in : $checkin
 Check-out : $checkout

Please provide your bank account number or QR code so we can process your refund.

If you have any questions or wish to make a new booking, feel free to contact us. Thank you! ";

    break;

    default:

        # assign mesej default ringkas ke $message kalau jenis notifikasi tak match mana-mana case
        $message = "Hi $name!";
}


# calling method prepare() dari object $pdo that assign to variable name $insert untuk sediakan query simpan rekod notifikasi yang dihantar
$insert = $pdo->prepare("
INSERT INTO notification_status
(booking_id, customer_id, sent_date, status, notification_type, channel)
VALUES (?, ?, NOW(), 'sent', ?, 'whatsapp')
");

# calling method execute() dari object $insert untuk simpan rekod notifikasi whatsapp yang baru dihantar
$insert->execute([
    $booking_id,
    $customer_id,
    $type
]);

# calling function urlencode() that assign to variable name $link untuk bina link wa.me sekali dengan mesej yang dah di-encode
$link = "https://wa.me/".$phone."?text=".urlencode($message);

# calling function header() untuk redirect browser terus ke link whatsapp
header("Location: ".$link);
exit;
