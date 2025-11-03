<?php
/**
 * /cp_webhook.php  — CoinPayments IPN webhook (server-to-server)
 * Validates HMAC + merchant, updates i_user_payments & credits wallets.
 * IMPORTANT: This file must be publicly reachable and configured
 * as your IPN URL in CoinPayments settings.
 */

@http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Keep output quiet (we'll just echo "OK" at the very end)
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@error_reporting(E_ERROR | E_PARSE);

/* ---- app bootstrap ---- */
require_once __DIR__ . '/includes/inc.php';
require_once __DIR__ . '/includes/payment/vendor/autoload.php';

/* Helpers may be needed depending on your build */
if (!function_exists('configItem')) {
    require_once __DIR__ . '/includes/payment/app/Support/app-helpers.php';
}

/* ---------- tiny logger ---------- */
function cp_log($msg) {
    @file_put_contents(__DIR__ . '/cp_debug.log',
        '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

/* ---------- read config ---------- */
$cfg = configItem();
$payments = $cfg['payments'] ?? ($cfg['inoraPaymentConfig']['payments'] ?? []);
// From your inc.php we know these exist in $method; they might also be mirrored here.
// We'll read from $inc via globals if present:
global $coinPaymentMerchandID, $coinPaymentIPNSecret;

// If not loaded via globals, try to pull from payments structure:
if (empty($coinPaymentMerchandID) || empty($coinPaymentIPNSecret)) {
    $method = is_callable([$GLOBALS['iN'], 'iN_PaymentMethods']) ? $GLOBALS['iN']->iN_PaymentMethods() : [];
    if (is_array($method) && $method) {
        if (empty($coinPaymentMerchandID)) $coinPaymentMerchandID = $method['coinpayments_merchand_id'] ?? '';
        if (empty($coinPaymentIPNSecret))  $coinPaymentIPNSecret  = $method['coinpayments_ipn_secret'] ?? '';
    }
}

if (empty($coinPaymentMerchandID) || empty($coinPaymentIPNSecret)) {
    cp_log('config_missing merchant or ipn secret');
    exit('OK'); // do not leak details to the client; IPN should just be 200
}

/* ---------- validate HMAC ---------- */
if (!isset($_SERVER['HTTP_HMAC']) || empty($_SERVER['HTTP_HMAC'])) {
    cp_log('no_hmac_header');
    exit('OK');
}
$hmac = $_SERVER['HTTP_HMAC'];

// CoinPayments requires ipn_mode = hmac
if (!isset($_POST['ipn_mode']) || $_POST['ipn_mode'] !== 'hmac') {
    cp_log('ipn_mode_not_hmac');
    exit('OK');
}

// Verify merchant
$merchant = $_POST['merchant'] ?? '';
if (strcasecmp(trim($merchant), trim($coinPaymentMerchandID)) !== 0) {
    cp_log('merchant_mismatch got=' . $merchant);
    exit('OK');
}

// Compute HMAC on raw body
$raw = file_get_contents('php://input');
$calc_hmac = hash_hmac('sha512', $raw, trim($coinPaymentIPNSecret));
if (!hash_equals($calc_hmac, $hmac)) {
    cp_log('hmac_mismatch');
    exit('OK');
}

/* ---------- extract fields ---------- */
$status      = isset($_POST['status']) ? (int)$_POST['status'] : 0; // >=100 or ==2 => confirmed
$status_text = (string)($_POST['status_text'] ?? '');
$txn_id      = (string)($_POST['txn_id'] ?? '');
$amount1     = (float)($_POST['amount1'] ?? 0);
$currency1   = (string)($_POST['currency1'] ?? '');
$email       = (string)($_POST['email'] ?? ($_POST['buyer_email'] ?? ''));
$invoice     = (string)($_POST['invoice'] ?? '');     // we prefer this as our order_key
$custom      = (string)($_POST['custom'] ?? '');
$item_num    = (string)($_POST['item_number'] ?? '');
$refGuess    = $invoice ?: ($custom ?: $item_num);

cp_log("ipn_ok status=$status amt=$amount1 $currency1 txn=$txn_id ref=$refGuess email=$email");

// Only proceed on paid/confirmed
// 100+ = confirmed, 2 = queued for nightly confirm (treated as paid), 1 = pending
if ($status < 2 && $status < 100) {
    cp_log("status_not_paid status=$status ($status_text)");
    exit('OK');
}

/* ---------- locate our payment row ---------- */
global $db, $adminFee;

$pData = null;
if ($refGuess !== '') {
    $refEsc = mysqli_real_escape_string($db, $refGuess);
    $q = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key='$refEsc' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $pData = mysqli_fetch_array($q, MYSQLI_ASSOC);
        cp_log("match_by_reference payment_id={$pData['payment_id']}");
    }
}

// fallback by email + last pending coinpayments within 48h
if (!$pData && $email !== '') {
    $emailEsc = mysqli_real_escape_string($db, $email);
    $u = mysqli_query($db, "SELECT iuid FROM i_users WHERE i_user_email='$emailEsc' LIMIT 1");
    if ($u && mysqli_num_rows($u) > 0) {
        $uid   = mysqli_fetch_array($u, MYSQLI_ASSOC)['iuid'];
        $since = time() - 172800; // 48h
        $uidEsc = mysqli_real_escape_string($db, (string)$uid);
        $sinceEsc = mysqli_real_escape_string($db, (string)$since);
        $q2 = mysqli_query($db, "SELECT * FROM i_user_payments
            WHERE payer_iuid_fk='$uidEsc'
              AND payment_option='coinpayments'
              AND payment_status='pending'
              AND payment_time>='$sinceEsc'
            ORDER BY payment_id DESC LIMIT 1");
        if ($q2 && mysqli_num_rows($q2) > 0) {
            $pData = mysqli_fetch_array($q2, MYSQLI_ASSOC);
            cp_log("match_by_email payment_id={$pData['payment_id']}");
        }
    }
}

if (!$pData) {
    cp_log("row_not_found ref=$refGuess email=$email");
    exit('OK');
}

/* ---------- idempotency ---------- */
$paymentId   = (int)$pData['payment_id'];
$payerUserID = $pData['payer_iuid_fk'];
$planId      = $pData['credit_plan_id'];
$productId   = $pData['paymet_product_id'];
$type        = (string)$pData['payment_type']; // 'point' or 'product'
$already     = isset($pData['payment_status']) ? (string)$pData['payment_status'] : '';

if (in_array($already, ['ok','paid'], true)) {
    cp_log("idempotent payment_id=$paymentId status=$already");
    exit('OK');
}

/* ---------- credit logic ---------- */
if ($type === 'point' || (!empty($planId) && empty($productId))) {
    // POINTS purchase
    $points = null;
    if (!empty($planId)) {
        $planIdEsc = mysqli_real_escape_string($db, (string)$planId);
        $pQ = mysqli_query($db, "SELECT plan_amount FROM i_premium_plans WHERE plan_id='$planIdEsc' LIMIT 1");
        if ($pQ && mysqli_num_rows($pQ) > 0) {
            $points = (int)mysqli_fetch_array($pQ, MYSQLI_ASSOC)['plan_amount'];
        }
    }
    // fallback: use amount1 rounded (crypto -> points heuristic)
    if ($points === null) $points = max(0, (int)round($amount1));

    mysqli_query($db, "UPDATE i_users
                          SET wallet_points = wallet_points + $points
                        WHERE iuid = '".mysqli_real_escape_string($db,(string)$payerUserID)."'");

    $amtEsc = mysqli_real_escape_string($db, (string)$amount1);
    $txnEsc = mysqli_real_escape_string($db, $txn_id);
    mysqli_query($db, "UPDATE i_user_payments
                          SET payment_status='ok',
                              amount='$amtEsc',
                              trans_id='$txnEsc'
                        WHERE payment_id = $paymentId
                          AND payment_status='pending'");

    cp_log("points_credited uid=$payerUserID +$points payment_id=$paymentId amt=$amount1");

} elseif ($type === 'product' || !empty($productId)) {
    // PRODUCT purchase → credit seller & mark paid
    $prIdEsc = mysqli_real_escape_string($db, (string)$productId);
    $prQ = mysqli_query($db, "SELECT pr_price, iuid_fk FROM i_user_product_posts WHERE pr_id='$prIdEsc' LIMIT 1");
    if ($prQ && mysqli_num_rows($prQ) > 0) {
        $pr     = mysqli_fetch_array($prQ, MYSQLI_ASSOC);
        $price  = (float)$pr['pr_price'];
        $seller = $pr['iuid_fk'];

        $adminPercent = isset($adminFee) ? (float)$adminFee : 0.0;
        $adminEarn    = ($adminPercent * $price) / 100.0;
        $userEarn     = $price - $adminEarn;

        $pe = mysqli_real_escape_string($db, (string)$seller);
        $pa = mysqli_real_escape_string($db, (string)$price);
        $af = mysqli_real_escape_string($db, (string)$adminPercent);
        $ae = mysqli_real_escape_string($db, (string)$adminEarn);
        $ue = mysqli_real_escape_string($db, (string)$userEarn);
        $txnEsc = mysqli_real_escape_string($db, $txn_id);

        mysqli_query($db, "UPDATE i_user_payments
                              SET payment_status='paid',
                                  payed_iuid_fk='$pe',
                                  amount='$pa',
                                  fee='$af',
                                  admin_earning='$ae',
                                  user_earning='$ue',
                                  trans_id='$txnEsc'
                            WHERE payment_id = $paymentId
                              AND payment_status='pending'");

        mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$ue' WHERE iuid='$pe'");

        cp_log("product_credited seller=$seller +$userEarn payment_id=$paymentId");
    } else {
        cp_log("product_missing pr_id=$productId");
    }

} else {
    // Unknown type → mark OK with amount to avoid loops
    $amtEsc = mysqli_real_escape_string($db, (string)$amount1);
    $txnEsc = mysqli_real_escape_string($db, $txn_id);
    mysqli_query($db, "UPDATE i_user_payments
                          SET payment_status='ok',
                              amount='$amtEsc',
                              trans_id='$txnEsc'
                        WHERE payment_id = $paymentId
                          AND payment_status='pending'");
    cp_log("unknown_type_mark_ok payment_id=$paymentId");
}

/* Done. CoinPayments expects 200 OK. */
echo "OK";