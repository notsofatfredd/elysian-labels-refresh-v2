<?php
declare(strict_types=1);

session_name('elysian_quote');
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Lax',
]);
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$recipient = 'orders@elysianlabels.co.za';
$industries = [
    'shipping-logistics' => 'Shipping & logistics',
    'automotive-industrial' => 'Automotive & industrial',
    'food-cold-storage' => 'Food, beverage & cold storage',
    'retail-ecommerce' => 'Retail & e-commerce',
    'other' => 'Other',
];
$products = [
    'unsure' => 'Not sure — please advise',
    'direct-thermal' => 'Direct thermal',
    'thermal-transfer' => 'Thermal transfer',
    'custom-garment' => 'Custom garment labelling',
    'printer-hardware' => 'Printer or hardware',
];

function clean_line(string $value): string {
    $value = str_replace(["\r", "\n"], ' ', $value);
    return trim((string)preg_replace('/[\x00-\x1F\x7F]/', '', $value));
}

function result_page(string $title, string $copy, int $status): void {
    http_response_code($status);
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeCopy = htmlspecialchars($copy, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="dark"><title>' . $safeTitle . ' — Elysian Labels</title><style>:root{color-scheme:dark;--navy:#081B3C;--paper:#FBFAF7;--gold:#C69A3B;--ink:#15233D;--steel:#657188}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:var(--navy);color:var(--ink);font-family:Arial,sans-serif}main{max-width:680px;width:100%;background:var(--paper);padding:clamp(32px,7vw,72px);border-top:5px solid var(--gold)}img{display:block;width:min(240px,70%);height:auto;margin-bottom:44px}p.eyebrow{padding-bottom:18px;border-bottom:1px solid rgba(8,27,60,.18);font-size:10px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#795817}h1{font-family:Georgia,serif;font-size:clamp(36px,7vw,64px);font-weight:600;line-height:1.04;margin:38px 0 18px}p{color:var(--steel);line-height:1.7;max-width:520px}a{display:inline-block;margin-top:26px;padding:15px 19px;background:var(--navy);color:#fff;text-decoration:none;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}</style></head><body><main><img src="/assets/elysian-full-logo-navy.png" alt="Elysian Labels"><p class="eyebrow">Quote request / status</p><h1>' . $safeTitle . '</h1><p>' . $safeCopy . '</p><a href="/#quote">Return to Elysian Labels</a></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['status'] ?? '') === 'submitted') {
    if (!empty($_SESSION['elysian_quote_submitted'])) {
        unset($_SESSION['elysian_quote_submitted']);
        result_page('Request received.', 'Your quote request has been submitted for delivery to orders@elysianlabels.co.za.', 200);
    }
    header('Location: /#quote', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /#quote', true, 303);
    exit;
}

$origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '') {
    $originHost = strtolower((string)parse_url($origin, PHP_URL_HOST));
    if (!in_array($originHost, ['elysianlabels.co.za', 'www.elysianlabels.co.za'], true)) {
        result_page('The request could not be sent.', 'Please submit the form from elysianlabels.co.za or email orders@elysianlabels.co.za directly.', 403);
    }
}

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 30000) {
    result_page('The request is too large.', 'Please shorten the details and try again, or email orders@elysianlabels.co.za directly.', 413);
}

if (!empty($_POST['website'] ?? '')) {
    header('Location: /#quote', true, 303);
    exit;
}

$allowedFields = ['name', 'company', 'email', 'phone', 'industry', 'product', 'detail', 'consent', 'website'];
if (array_diff(array_keys($_POST), $allowedFields)) {
    result_page('The request could not be sent.', 'Please return to the form and try again, or email orders@elysianlabels.co.za directly.', 400);
}
foreach ($allowedFields as $field) {
    if (isset($_POST[$field]) && !is_string($_POST[$field])) {
        result_page('The request could not be sent.', 'Please return to the form and try again, or email orders@elysianlabels.co.za directly.', 400);
    }
}

$name = clean_line((string)($_POST['name'] ?? ''));
$company = clean_line((string)($_POST['company'] ?? ''));
$email = clean_line((string)($_POST['email'] ?? ''));
$phone = clean_line((string)($_POST['phone'] ?? ''));
$industryKey = clean_line((string)($_POST['industry'] ?? ''));
$productKey = clean_line((string)($_POST['product'] ?? ''));
$detail = trim((string)($_POST['detail'] ?? ''));
$consent = clean_line((string)($_POST['consent'] ?? ''));

$valid = $name !== '' && strlen($name) <= 200
    && $company !== '' && strlen($company) <= 200
    && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 254
    && strlen($phone) <= 100
    && isset($industries[$industryKey])
    && isset($products[$productKey])
    && preg_match('//u', $detail) === 1 && strlen($detail) <= 5000
    && $consent === 'yes';

if (!$valid) {
    result_page('Check the request details.', 'One or more required details are missing or invalid. Return to the form and try again, or email orders@elysianlabels.co.za directly.', 422);
}

$industry = $industries[$industryKey];
$product = $products[$productKey];
$subject = 'Elysian quote request - ' . $company;
$body = "Name: {$name}\nCompany: {$company}\nEmail: {$email}\nPhone: " . ($phone ?: 'Not supplied') . "\nIndustry: {$industry}\nLabel type: {$product}\n\nSize, volume and conditions:\n" . ($detail ?: 'Not supplied') . "\n\nPOPIA consent: Yes\n";
$headers = "From: Elysian Labels website <{$recipient}>\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=UTF-8\r\n";

$now = time();
if (!empty($_SESSION['elysian_quote_last_sent']) && $now - (int)$_SESSION['elysian_quote_last_sent'] < 30) {
    result_page('Request already received.', 'This browser has already submitted a recent request. Wait a moment before sending another, or email orders@elysianlabels.co.za directly.', 429);
}

if (mail($recipient, $subject, $body, $headers)) {
    $_SESSION['elysian_quote_last_sent'] = $now;
    $_SESSION['elysian_quote_submitted'] = true;
    header('Location: /quote.php?status=submitted', true, 303);
    exit;
}

result_page('The request could not be delivered.', 'Delivery was not confirmed. Your details have not been recorded as sent. Please email orders@elysianlabels.co.za directly.', 500);
