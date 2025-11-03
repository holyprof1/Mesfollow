<?php
/**
 * Paystack Webhook — marks i_user_payments as PAID and credits the user/seller.
 * File:  httpdocs/requests/paystack-webhook.php
 */

@http_response_code(200); // default OK (avoid Paystack retries due to 500s until we decide otherwise)
header('Content-Type: application/json; charset=utf-8');

// ---------- Bootstrap your app ----------
require_once __DIR__ . '/../includes/inc.php';
require_once __DIR__ . '/../includes/payment/vendor/autoload.php';
if (!defined('INORA_METHODS_CONFIG')) {
    define('INORA_METHODS_CONFIG', realpath(__DIR__ . '/../includes/payment/paymentConfig.php'));
}
$configData = configItem();
$gatewayCfg = $configData['payments']['gateway_configuration']['paystack'] ?? [];

// Determine the right secret (test vs live)
$testMode = $gatewayCfg['testMode'];
if ($testMode === 'true') { $testMode = true; }  // sometimes stored as string
$secret = $testMode ? ($gatewayCfg['paystackTestingSecretKey'] ?? '') : ($gatewayCfg['paystackLiveSecretKey'] ?? '');
if (!$secret) {
    // No secret means we cannot verify signature — bail out
    echo json_encode(['ok' => false, 'reason' => 'missing_secret']);
    exit;
}

// ---------- Helpers ----------
function ps_log($line) {
    // while testing: write a small log next to this file
    $f = __DIR__ . '/paystack_debug.log';
    $dt = date('Y-m-d H:i:s');
    @file_put_contents($f, "[$dt] $line\n", FILE_APPEND);
}

function jexit($arr) {
    echo json_encode($arr);
    exit;
}

// ---------- Verify signature ----------
$input = file_get_contents('php://input');
$sig   = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$calc  = hash_hmac('sha512', $input, $secret);

if (!$sig || !hash_equals($calc, $sig)) {
    ps_log('signature_mismatch');
    http_response_code(401);
    jexit(['ok' => false, 'reason' => 'bad_signature']);
}

$payload = json_decode($input, true);
if (!is_array($payload)) {
    ps_log('invalid_json');
    jexit(['ok' => false, 'reason' => 'invalid_json']);
}

$event     = $payload['event'] ?? '';
$data      = $payload['data']  ?? [];
$status    = $data['status']   ?? '';
$reference = $data['reference'] ?? '';
$amountKobo= isset($data['amount']) ? (int)$data['amount'] : 0;
$amount    = $amountKobo > 0 ? ($amountKobo / 100) : 0.0; // NGN base units
$currency  = $data['currency'] ?? '';
$email     = $data['customer']['email'] ?? '';
ps_log("evt=$event ref=$reference status=$status amt=$amount $currency email=$email");

// Only process successful charges
if (!in_array($event, ['charge.success', 'transfer.success']) && $status !== 'success') {
    jexit(['ok' => true, 'skipped' => true, 'reason' => 'not_success']);
}

// ---------- Find matching i_user_payments row ----------
$paymentRow = null;

// 1) Try exact match by reference -> order_key
if (!empty($reference)) {
    $refEsc = mysqli_real_escape_string($db, $reference);
    $q1 = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key='$refEsc' LIMIT 1");
    if ($q1 && mysqli_num_rows($q1) > 0) {
        $paymentRow = mysqli_fetch_array($q1, MYSQLI_ASSOC);
        ps_log("match_by_reference payment_id={$paymentRow['payment_id']}");
    }
}

// 2) Fallback: map email -> user -> last pending paystack row (recent)
if (!$paymentRow && !empty($email)) {
    $emailEsc = mysqli_real_escape_string($db, $email);
    $u = mysqli_query($db, "SELECT iuid FROM i_users WHERE i_user_email='$emailEsc' LIMIT 1");
    if ($u && mysqli_num_rows($u) > 0) {
        $uidArr = mysqli_fetch_array($u, MYSQLI_ASSOC);
        $uid    = $uidArr['iuid'];

        // last pending Paystack by this user in last 24h
        $since = time() - 86400;
        $q2 = mysqli_query(
            $db,
            "SELECT * FROM i_user_payments 
             WHERE payer_iuid_fk = '$uid'
               AND payment_option = 'paystack'
               AND payment_status = 'pending'
               AND payment_time >= '$since'
             ORDER BY payment_id DESC
             LIMIT 1"
        );
        if ($q2 && mysqli_num_rows($q2) > 0) {
            $paymentRow = mysqli_fetch_array($q2, MYSQLI_ASSOC);
            ps_log("match_by_email payment_id={$paymentRow['payment_id']}");
        }
    }
}

if (!$paymentRow) {
    // We could not confidently map this webhook to a pending row.
    // Let the front-end callback (which you already patched) do the credit.
    ps_log("no_row_found");
    jexit(['ok' => true, 'skipped' => true, 'reason' => 'no_row_found']);
}

// ---------- Idempotency ----------
$paymentId   = (int)$paymentRow['payment_id'];
$payerUserID = $paymentRow['payer_iuid_fk'];
$planId      = $paymentRow['credit_plan_id'];
$productId   = $paymentRow['paymet_product_id'];
$alreadyPaid = (isset($paymentRow['payment_status']) && $paymentRow['payment_status'] === 'paid');

if ($alreadyPaid) {
    ps_log("already_paid payment_id=$paymentId");
    jexit(['ok' => true, 'idempotent' => true]);
}

// ---------- Credit logic ----------
/**
 * Uses same logic as your callback:
 * - Points: add plan_amount to i_users.wallet_points; mark payment PAID; store amount we received
 * - Product: compute admin fee vs seller earning; mark PAID; credit seller wallet_money
 *
 * Relies on $adminFee from your includes.
 */

if (!empty($planId)) {
    // Points purchase
    $planIdEsc = mysqli_real_escape_string($db, $planId);
    $planQ = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id='$planIdEsc' LIMIT 1");
    $plan  = $planQ ? mysqli_fetch_array($planQ, MYSQLI_ASSOC) : null;
    $planPoints = $plan ? (int)$plan['plan_amount'] : 0;

    // Credit points once
    mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planPoints WHERE iuid = '$payerUserID'");

    // Mark payment as PAID and store the actual amount we got
    $amountEsc = mysqli_real_escape_string($db, (string)$amount);
    mysqli_query($db, "
        UPDATE i_user_payments
           SET payment_status = 'paid',
               amount = '$amountEsc'
         WHERE payment_id = $paymentId
           AND payment_status = 'pending'
    ");

    ps_log("points_credited user=$payerUserID points=$planPoints payment_id=$paymentId amount=$amount");

} elseif (!empty($productId)) {
    // Product purchase
    $productIdEsc = mysqli_real_escape_string($db, $productId);
    $prQ = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id='$productIdEsc' LIMIT 1");
    $product = $prQ ? mysqli_fetch_array($prQ, MYSQLI_ASSOC) : null;

    if ($product) {
        $productPrice   = (float)$product['pr_price'];
        $productOwnerID = $product['iuid_fk'];
        // $adminFee is available from includes/inc.php (percent)
        $adminPercent   = isset($adminFee) ? (float)$adminFee : 0.0;
        $adminEarning   = ($adminPercent * $productPrice) / 100.0;
        $userEarning    = $productPrice - $adminEarning;

        // Mark as PAID with earnings split
        $pe  = mysqli_real_escape_string($db, (string)$productOwnerID);
        $pa  = mysqli_real_escape_string($db, (string)$productPrice);
        $af  = mysqli_real_escape_string($db, (string)$adminPercent);
        $ae  = mysqli_real_escape_string($db, (string)$adminEarning);
        $ue  = mysqli_real_escape_string($db, (string)$userEarning);

        mysqli_query($db, "
            UPDATE i_user_payments
               SET payment_status = 'paid',
                   payed_iuid_fk   = '$pe',
                   amount          = '$pa',
                   fee             = '$af',
                   admin_earning   = '$ae',
                   user_earning    = '$ue'
             WHERE payment_id = $paymentId
               AND payment_status = 'pending'
        ");

        // Credit seller wallet once
        mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$ue' WHERE iuid = '$pe'");

        ps_log("product_credited seller=$productOwnerID ue=$userEarning payment_id=$paymentId");
    } else {
        ps_log("product_not_found pr_id=$productId");
    }
} else {
    // Unknown payment type — leave log only
    ps_log("no_plan_no_product payment_id=$paymentId");
}

// Done
jexit(['ok' => true, 'paid' => true, 'payment_id' => $paymentId, 'reference' => $reference]);
