<?php
/**
 * /paystack-cb.php  (PUBLIC ENTRYPOINT)
 * Verifies Paystack payment & credits wallets (desktop + mobile safe),
 * then redirects to /purchase/purchase_point with a status flag.
 */

@http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

/* keep output clean on PHP 8.2+ */
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@error_reporting(E_ERROR | E_PARSE);

/* ---- app bootstrap ---- */
require_once __DIR__ . '/includes/inc.php';
require_once __DIR__ . '/includes/payment/vendor/autoload.php';

/* Make sure the payment config loader knows the file path */
if (!defined('INORA_METHODS_CONFIG')) {
    define('INORA_METHODS_CONFIG', realpath(__DIR__ . '/includes/payment/paymentConfig.php'));
}
/* If helpers aren’t autoloaded by inc.php in your build, include them */
if (!function_exists('configItem')) {
    require_once __DIR__ . '/includes/payment/app/Support/app-helpers.php';
}

use Yabacon\Paystack;

/* ------------ helpers ------------ */
function ps_log($msg) {
    @file_put_contents(__DIR__ . '/paystack_debug.log',
        '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}
function app_base_url() {
    $cfg  = configItem();
    $root = $cfg['inoraPaymentConfig']['base_url'] ?? ($cfg['base_url'] ?? '');
    return rtrim((string)$root, '/');
}
function success_url() {
    return function_exists('getAppUrl')
        ? getAppUrl('payment-success.php')
        : app_base_url() . '/payment-success.php';
}
function fail_url() {
    return function_exists('getAppUrl')
        ? getAppUrl('payment-failed.php')
        : app_base_url() . '/payment-failed.php';
}

function redirect_to($url) {
    if (!headers_sent()) {
        header('Location: ' . $url, true, 303);
        exit;
    }
    // Fallback if something already echoed (e.g., from inc.php)
    $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $safe .
         '"><script>location.replace(' . json_encode($url) . ');</script></head><body></body></html>';
    exit;
}
function redirect_success() { redirect_to(success_url()); }
function redirect_failed($why = '') { if ($why) ps_log("FAIL: $why"); redirect_to(fail_url()); }

/* ------------ input ------------ */
$reference = '';
if (isset($_GET['reference'])) $reference = trim((string)$_GET['reference']);
if ($reference === '' && isset($_GET['trxref'])) $reference = trim((string)$_GET['trxref']);
if ($reference === '' && isset($_POST['reference'])) $reference = trim((string)$_POST['reference']);
if ($reference === '' && isset($_POST['trxref'])) $reference = trim((string)$_POST['trxref']);
if ($reference === '') redirect_failed('missing_reference');

/* ------------ config ------------ */
$configData   = configItem();
$paymentsRoot = $configData['payments'] ?? ($configData['inoraPaymentConfig']['payments'] ?? []);
if (empty($paymentsRoot['gateway_configuration']['paystack'])) {
    redirect_failed('missing_payments_config');
}
$gw       = $paymentsRoot['gateway_configuration']['paystack'];
$testMode = (!empty($gw['testMode']) && ($gw['testMode'] === true || $gw['testMode'] === 'true'));
$secret   = $testMode ? ($gw['paystackTestingSecretKey'] ?? '') : ($gw['paystackLiveSecretKey'] ?? '');
if (!$secret) redirect_failed('missing_secret_key');
if (substr((string)$secret, 0, 3) !== 'sk_') $secret = 'sk_' . $secret;

/* ------------ verify with Paystack ------------ */
try {
    $ps     = new Paystack($secret);
    $verify = $ps->transaction->verify(['reference' => $reference]);
} catch (Exception $e) {
    ps_log('verify_exception: ' . $e->getMessage());
    redirect_failed('verify_exception');
}
if (empty($verify->status) || empty($verify->data) || empty($verify->data->status)) {
    redirect_failed('verify_no_status');
}

$status     = (string)$verify->data->status;                 // 'success'
$amountKobo = (int)($verify->data->amount ?? 0);
$amountBase = $amountKobo > 0 ? ($amountKobo / 100) : 0.0;   // base units (XOF/NGN…)
$email      = (string)($verify->data->customer->email ?? '');
$txid       = (string)($verify->data->id ?? '');
$currency   = (string)($verify->data->currency ?? '');
ps_log("verify_ok ref=$reference txid=$txid amt=$amountBase $currency status=$status email=$email");

if ($status !== 'success') redirect_failed('status_not_success');

/* ------------ find our row ------------ */
global $db, $adminFee;
$refEsc = mysqli_real_escape_string($db, $reference);
$q = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key='$refEsc' LIMIT 1");

$pData = null;
if ($q && mysqli_num_rows($q) > 0) {
    $pData = mysqli_fetch_array($q, MYSQLI_ASSOC);
    ps_log("match_by_reference payment_id={$pData['payment_id']}");
} elseif ($email !== '') {
    // fallback: by email → payer → last pending paystack within 48h
    $emailEsc = mysqli_real_escape_string($db, $email);
    $u = mysqli_query($db, "SELECT iuid FROM i_users WHERE i_user_email='$emailEsc' LIMIT 1");
    if ($u && mysqli_num_rows($u) > 0) {
        $uid     = mysqli_fetch_array($u, MYSQLI_ASSOC)['iuid'];
        $since   = time() - 172800; // 48h
        $uidEsc  = mysqli_real_escape_string($db, (string)$uid);
        $sinceEsc= mysqli_real_escape_string($db, (string)$since);
        $q2 = mysqli_query($db, "SELECT * FROM i_user_payments
            WHERE payer_iuid_fk='$uidEsc'
              AND payment_option='paystack'
              AND payment_status='pending'
              AND payment_time>='$sinceEsc'
            ORDER BY payment_id DESC LIMIT 1");
        if ($q2 && mysqli_num_rows($q2) > 0) {
            $pData = mysqli_fetch_array($q2, MYSQLI_ASSOC);
            ps_log("match_by_email payment_id={$pData['payment_id']}");
        }
    }
}
if (!$pData) {
    ps_log("row_not_found ref=$reference");
    redirect_failed('no_payment_row');
}

/* ------------ idempotency ------------ */
$paymentId   = (int)$pData['payment_id'];
$payerUserID = $pData['payer_iuid_fk'];
$planId      = $pData['credit_plan_id'];
$productId   = $pData['paymet_product_id'];
$type        = (string)$pData['payment_type'];               // 'point' or 'product'
$already     = isset($pData['payment_status']) ? (string)$pData['payment_status'] : '';

if (in_array($already, ['ok','paid'], true)) {
    ps_log("idempotent payment_id=$paymentId status=$already");
    redirect_success();
}

/* ------------ credit logic ------------ */
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
    if ($points === null) $points = max(0, (int)round($amountBase)); // fallback

    mysqli_query($db, "UPDATE i_users
                          SET wallet_points = wallet_points + $points
                        WHERE iuid = '".mysqli_real_escape_string($db,(string)$payerUserID)."'");

    $amtEsc = mysqli_real_escape_string($db, (string)$amountBase);
    mysqli_query($db, "UPDATE i_user_payments
                          SET payment_status='ok',
                              amount='$amtEsc'
                        WHERE payment_id = $paymentId
                          AND payment_status='pending'");

    ps_log("points_credited uid=$payerUserID +$points payment_id=$paymentId amt=$amountBase");

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

        mysqli_query($db, "UPDATE i_user_payments
                              SET payment_status='paid',
                                  payed_iuid_fk='$pe',
                                  amount='$pa',
                                  fee='$af',
                                  admin_earning='$ae',
                                  user_earning='$ue'
                            WHERE payment_id = $paymentId
                              AND payment_status='pending'");

        mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$ue' WHERE iuid='$pe'");

        ps_log("product_credited seller=$seller +$userEarn payment_id=$paymentId");
    } else {
        ps_log("product_missing pr_id=$productId");
    }

} else {
    // Unknown type → mark OK with amount to avoid loops
    $amtEsc = mysqli_real_escape_string($db, (string)$amountBase);
    mysqli_query($db, "UPDATE i_user_payments
                          SET payment_status='ok',
                              amount='$amtEsc'
                        WHERE payment_id = $paymentId
                          AND payment_status='pending'");
    ps_log("unknown_type_mark_ok payment_id=$paymentId");
}

/* ------------ done ------------ */
redirect_success();