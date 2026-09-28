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
    return TOYYIBPAY_SECRET_KEY !== '' && TOYYIBPAY_CATEGORY_CODE !== '';
}

function toyyibpay_category_code_for(string $accommodationName): string
{
    return TOYYIBPAY_CATEGORY_CODES_BY_ACCOMMODATION[$accommodationName] ?? TOYYIBPAY_CATEGORY_CODE;
}

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

function toyyibpay_create_bill(array $fields): string|false
{
    if (!toyyibpay_configured()) {
        error_log('toyyibpay_create_bill: ToyyibPay is not configured yet (see includes/toyyibpay.php).');
        return false;
    }

    $payload = array_merge([
        'userSecretKey' => TOYYIBPAY_SECRET_KEY,
        'categoryCode' => TOYYIBPAY_CATEGORY_CODE,
        'billPriceSetting' => 1,
        'billPayorInfo' => 1,
        'billPaymentChannel' => 0,
        'billExpiryDays' => 3,
    ], $fields);

    $result = toyyibpay_api_post('/index.php/api/createBill', $payload);

    $billCode = $result[0]['BillCode'] ?? null;

    if (!$billCode) {
        error_log('toyyibpay_create_bill failed: ' . json_encode($result));
        return false;
    }

    return $billCode;
}

function toyyibpay_bill_payment_url(string $billCode): string
{
    return TOYYIBPAY_BASE_URL . '/' . $billCode;
}

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
        return null;
    }

    return end($result);
}
