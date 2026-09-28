<?php
declare(strict_types=1);

const TOYYIBPAY_SECRET_KEY = '5srvns99-2dvf-pzbf-teb1-fvnraubics5e';
const TOYYIBPAY_CATEGORY_CODE = 'jq9987ht';

const TOYYIBPAY_CATEGORY_CODES_BY_ACCOMMODATION = [
    'Casa 1' => 'ar03kmpx',
    'Casa 2' => '9dzvj2xh',
    'Casa 3' => 'shd4i55p',
    'Casa 4' => 'xioo7g8f',
    'Campsite Package 1' => 'qbmf6rev',
    'Campsite Package 2' => '4sw9sf22',
    'Campsite Package 3' => '5fnhw1fb',
    'Campsite Package 4' => 'nqt9sjsc',
];

const TOYYIBPAY_BASE_URL = 'https://toyyibpay.com';

function toyyibpay_configured(): bool
{
    # check kalau secret key & category code dua-dua tak kosong, maknanya ToyyibPay dah disetup
    return TOYYIBPAY_SECRET_KEY !== '' && TOYYIBPAY_CATEGORY_CODE !== '';
}

function toyyibpay_category_code_for(string $accommodationName): string
{
    # ambil value category code ikut nama accommodation kalau ada mapping, kalau tak guna default TOYYIBPAY_CATEGORY_CODE
    return TOYYIBPAY_CATEGORY_CODES_BY_ACCOMMODATION[$accommodationName] ?? TOYYIBPAY_CATEGORY_CODE;
}

function toyyibpay_api_post(string $path, array $fields): array|false
{
    # calling function curl_init() that assign to variable name $ch untuk sediakan sambungan curl ke API ToyyibPay
    $ch = curl_init(TOYYIBPAY_BASE_URL . $path);
    # calling function curl_setopt_array() untuk set setting curl - method POST, data yang dihantar, timeout, dan verify SSL
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    # calling function curl_exec() that assign to variable name $response untuk hantar request betul-betul dan ambil balasan
    $response = curl_exec($ch);
    # calling function curl_error() that assign to variable name $error untuk ambil mesej error curl kalau ada
    $error = curl_error($ch);
    # calling function curl_close() untuk tutup sambungan curl lepas selesai guna
    curl_close($ch);

    # check kalau request curl gagal (response false), log error dan pulangkan false
    if ($response === false) {
        error_log('toyyibpay_api_post: cURL error calling ' . $path . ': ' . $error);
        return false;
    }

    # calling function json_decode() that assign to variable name $decoded untuk tukar balasan json jadi array php
    $decoded = json_decode($response, true);
    # check kalau hasil decode bukan array (response tak dijangka), log error dan pulangkan false
    if (!is_array($decoded)) {
        error_log('toyyibpay_api_post: unexpected response from ' . $path . ': ' . $response);
        return false;
    }

    return $decoded;
}

function toyyibpay_create_bill(array $fields): string|false
{
    # calling function toyyibpay_configured() untuk check ToyyibPay dah disetup ke belum, kalau belum tolak terus
    if (!toyyibpay_configured()) {
        error_log('toyyibpay_create_bill: ToyyibPay is not configured yet (see includes/toyyibpay.php).');
        return false;
    }

    # calling function array_merge() that assign to variable name $payload untuk gabung setting default dgn $fields yang dibagi
    $payload = array_merge([
        'userSecretKey' => TOYYIBPAY_SECRET_KEY,
        'categoryCode' => TOYYIBPAY_CATEGORY_CODE,
        'billPriceSetting' => 1,
        'billPayorInfo' => 1,
        'billPaymentChannel' => 0,
        'billExpiryDays' => 3,
    ], $fields);

    # calling function toyyibpay_api_post() that assign to variable name $result untuk hantar request create bill ke ToyyibPay
    $result = toyyibpay_api_post('/index.php/api/createBill', $payload);

    # ambil value $result[0]['BillCode'] kalau wujud, kalau tak wujud pulangkan null
    $billCode = $result[0]['BillCode'] ?? null;

    # check kalau tiada bill code dipulangkan, maknanya create bill gagal
    if (!$billCode) {
        error_log('toyyibpay_create_bill failed: ' . json_encode($result));
        return false;
    }

    return $billCode;
}

function toyyibpay_bill_payment_url(string $billCode): string
{
    # cantum base url ToyyibPay dgn bill code untuk bina link page pembayaran
    return TOYYIBPAY_BASE_URL . '/' . $billCode;
}

function toyyibpay_get_latest_transaction(string $billCode): array|null|false
{
    # calling function toyyibpay_api_post() that assign to variable name $result untuk tanya ToyyibPay status transaksi bill ni
    $result = toyyibpay_api_post('/index.php/api/getBillTransactions', [
        'billCode' => $billCode,
        'userSecretKey' => TOYYIBPAY_SECRET_KEY,
    ]);

    # check kalau request ke ToyyibPay gagal terus, pulangkan false
    if ($result === false) {
        return false;
    }

    # check kalau tiada transaksi lagi untuk bill ni, pulangkan null
    if (!$result) {
        return null;
    }

    # calling function end() untuk ambil transaksi paling terkini (last item) dalam array $result
    return end($result);
}
