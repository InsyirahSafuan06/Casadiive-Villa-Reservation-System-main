<?php
/**
 * Klien API ToyyibPay yang ringkas.
 * Tiada kebergantungan Composer/SDK — cakap terus dengan REST API ToyyibPay guna cURL,
 * selari dengan seluruh kod projek ini (bandingkan includes/mailer.php untuk SMTP mentah).
 *
 * PERSEDIAAN DIPERLUKAN: isikan TOYYIBPAY_SECRET_KEY dan TOYYIBPAY_CATEGORY_CODE di bawah
 * dengan nilai sebenar dari dashboard ToyyibPay anda (Settings > User Profile untuk Secret Key,
 * dan halaman Category anda untuk Category Code) sebelum bil sebenar boleh dicipta.
 * Sehingga itu, toyyibpay_create_bill() akan pulangkan false sahaja.
 */
declare(strict_types=1);

const TOYYIBPAY_SECRET_KEY = '5srvns99-2dvf-pzbf-teb1-fvnraubics5e';
const TOYYIBPAY_CATEGORY_CODE = 'ar03kmpx'; // default/fallback — juga category code untuk Casa 1

// satu category ToyyibPay setiap villa, supaya bayaran untuk villa tu masuk category yang betul
// dalam dashboard ToyyibPay. Villa yang takde entri di sini guna TOYYIBPAY_CATEGORY_CODE (Casa 1).
const TOYYIBPAY_CATEGORY_CODES_BY_ACCOMMODATION = [
    'Casa 1' => 'ar03kmpx',
    'Casa 2' => '9dzvj2xh',
];

// tukar ke 'https://dev.toyyibpay.com' kalau nak test guna sandbox/staging environment ToyyibPay
const TOYYIBPAY_BASE_URL = 'https://toyyibpay.com';

function toyyibpay_configured(): bool
{
    return TOYYIBPAY_SECRET_KEY !== '' && TOYYIBPAY_CATEGORY_CODE !== '';
}

/** Category code ToyyibPay untuk satu villa/accommodation, atau default kalau tiada entri khusus. */
function toyyibpay_category_code_for(string $accommodationName): string
{
    return TOYYIBPAY_CATEGORY_CODES_BY_ACCOMMODATION[$accommodationName] ?? TOYYIBPAY_CATEGORY_CODE;
}

/**
 * Panggil satu endpoint API ToyyibPay (POST form-encoded) dan pulangkan response yang dah
 * di-decode sebagai array, atau false kalau request gagal / response bukan JSON yang sah.
 */
function toyyibpay_api_post(string $path, array $fields): array|false
{
    $ch = curl_init(TOYYIBPAY_BASE_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('toyyibpay_api_post: cURL error calling ' . $path . ': ' . $error);
        return false;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        error_log('toyyibpay_api_post: unexpected response from ' . $path . ': ' . $response);
        return false;
    }

    return $decoded;
}

/**
 * Cipta satu bil ToyyibPay untuk satu booking. $fields kena ada: billName, billDescription,
 * billAmount (dalam SEN, cth. RM10.50 = 1050), billReturnUrl, billCallbackUrl,
 * billExternalReferenceNo, billTo, billEmail, billPhone.
 * Pulangkan kod bil (string) bila berjaya, atau false bila gagal/config tak lengkap.
 */
function toyyibpay_create_bill(array $fields): string|false
{
    if (!toyyibpay_configured()) {
        error_log('toyyibpay_create_bill: ToyyibPay is not configured yet (see includes/toyyibpay.php).');
        return false;
    }

    $payload = array_merge([
        'userSecretKey' => TOYYIBPAY_SECRET_KEY,
        'categoryCode' => TOYYIBPAY_CATEGORY_CODE,
        'billPriceSetting' => 1,       // 1 = harga tetap (bukan "nama harga sendiri")
        'billPayorInfo' => 1,          // 1 = wajib isi nama/emel/fon pembayar (kita dah hantar terus)
        'billPaymentChannel' => 0,     // 0 = FPX Online Banking sahaja
        'billExpiryDays' => 3,
    ], $fields);

    $result = toyyibpay_api_post('/index.php/api/createBill', $payload);

    // ToyyibPay pulangkan array satu item [['BillCode' => '...']] bila berjaya,
    // atau array bersekutu ['status'=>'error','msg'=>'...'] bila gagal
    $billCode = $result[0]['BillCode'] ?? null;

    if (!$billCode) {
        error_log('toyyibpay_create_bill failed: ' . json_encode($result));
        return false;
    }

    return $billCode;
}

/** URL page pembayaran ToyyibPay sebenar untuk satu bil — sini customer scan/bayar. */
function toyyibpay_bill_payment_url(string $billCode): string
{
    return TOYYIBPAY_BASE_URL . '/' . $billCode;
}

/**
 * Tanya terus kat ToyyibPay status sebenar satu bil (jangan sekali percaya status_id yang
 * datang dari query string return URL semata-mata — sesiapa boleh karang URL tu, so kita
 * sahkan balik terus dengan ToyyibPay guna secret key kita). Pulangkan transaction terbaru
 * (array) kalau ada, atau null kalau tiada transaksi lagi / bil tak wujud, atau false kalau
 * request API sendiri gagal.
 */
function toyyibpay_get_latest_transaction(string $billCode): array|null|false
{
    $result = toyyibpay_api_post('/index.php/api/getBillTransactions', [
        'billCode' => $billCode,
        'userSecretKey' => TOYYIBPAY_SECRET_KEY,
    ]);

    if ($result === false) {
        return false;
    }

    if (!$result) {
        return null; // takde transaksi lagi untuk bil ni (cth. customer belum bayar/tinggalkan page)
    }

    // ambil transaksi terkini je — biasanya satu bil ada satu percubaan bayaran, tapi
    // customer boleh cuba lebih dari sekali kalau percubaan pertama gagal
    return end($result);
}
