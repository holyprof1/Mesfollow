<?php
ob_start();
session_start();

// Error reporting - only for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once "connect.php";
include_once "functions.php";

$iN = new iN_UPDATES($db);
$inc = $iN->iN_Configurations();
$subscriptionType = isset($inc['subscription_type']) ? $inc['subscription_type'] : null;

// Fetch active subscriptions renewing today
$query = mysqli_query($db, "
    SELECT subscription_id, iuid_fk, subscribed_iuid_fk, plan_interval, SUM(user_net_earning) AS calculate
    FROM i_user_subscriptions
    WHERE status IN('active','inactive') AND in_status IN('1','0') AND finished = '0'
      AND plan_interval = 'month'
      AND DATE_FORMAT(plan_period_start, '%Y-%m-%d') = CURDATE()
    GROUP BY subscribed_iuid_fk
");

if (!$query) {
    error_log("Query error: " . mysqli_error($db));
}

$secondQuery = mysqli_query($db, "
    SELECT subscription_id, iuid_fk, subscribed_iuid_fk, plan_interval, SUM(user_net_earning) AS calculate
    FROM i_user_subscriptions
    WHERE status = 'inactive' AND in_status = '1' AND finished = '1'
      AND plan_interval = 'month'
      AND DATE(plan_period_start) = CURDATE()
    GROUP BY subscribed_iuid_fk
");

if (!$secondQuery) {
    error_log("Second query error: " . mysqli_error($db));
}

// Handle renewals
if (mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $subscriptionID = (int)$row['subscription_id'];
        $iuidFK = (int)$row['subscribed_iuid_fk'];
        $subscriberUidFK = (int)$row['iuid_fk'];
        $amountPayable = (float)$row['calculate'];
        $planInterval = $row['plan_interval'];

        // Extend subscription dates
        $startNewEnd = date("Y-m-d H:i:s", strtotime('+1 month'));

        // Normalize plan interval label
        $pInterval = match($planInterval) {
            'week' => 'weekly',
            'month' => 'monthly', // corrected typo
            default => 'yearly'
        };

        if ($subscriptionType === '2') {
            $planData = $iN->iN_GetUserSubscriptionPlanDetails($iuidFK, $pInterval);
            $planAmount = isset($planData['amount']) ? (float)$planData['amount'] : 0;
            $uDat = $iN->iN_GetUserDetails($subscriberUidFK);
            $walletPoint = (float)$uDat['wallet_points'];

            if ($walletPoint >= $planAmount) {
                mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + $amountPayable WHERE iuid = $iuidFK") or error_log(mysqli_error($db));
                mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points - $planAmount WHERE iuid = $subscriberUidFK") or error_log(mysqli_error($db));
                mysqli_query($db, "UPDATE i_user_subscriptions SET plan_period_start = '$startNewEnd', plan_period_end = '$startNewEnd' WHERE subscription_id = $subscriptionID") or error_log(mysqli_error($db));
            } else {
                // Downgrade to follower
                mysqli_query($db, "UPDATE i_friends SET fr_status = 'flwr' WHERE fr_one = $subscriberUidFK AND fr_two = $iuidFK") or error_log(mysqli_error($db));
                mysqli_query($db, "UPDATE i_user_subscriptions SET status = 'declined', finished = '1', in_status = '1' WHERE subscription_id = $subscriptionID") or error_log(mysqli_error($db));
            }

            // Clean up old inactive
            mysqli_query($db, "UPDATE i_user_subscriptions SET status = 'declined', finished = '1', in_status = '1' WHERE subscription_id = $subscriptionID AND status = 'inactive' AND in_status = '1'") or error_log(mysqli_error($db));
        } else {
            // Regular plan renew
            mysqli_query($db, "UPDATE i_user_subscriptions SET plan_period_start = '$startNewEnd', plan_period_end = '$startNewEnd' WHERE subscription_id = $subscriptionID") or error_log(mysqli_error($db));
            mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + $amountPayable WHERE iuid = $iuidFK") or error_log(mysqli_error($db));
        }
    }
}

// Handle expired/inactive subscriptions
if (mysqli_num_rows($secondQuery) > 0) {
    while ($row = mysqli_fetch_assoc($secondQuery)) {
        $subscriptionID = (int)$row['subscription_id'];
        $iuidFK = (int)$row['subscribed_iuid_fk'];
        $subscriberUidFK = (int)$row['iuid_fk'];

        if ($subscriptionType === '2') {
            mysqli_query($db, "UPDATE i_friends SET fr_status = 'flwr' WHERE fr_one = $subscriberUidFK AND fr_two = $iuidFK") or error_log(mysqli_error($db));
        }
    }
}
?>