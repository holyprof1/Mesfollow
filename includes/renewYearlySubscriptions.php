<?php
ob_start();
session_start();

// Enable error reporting (recommended only during development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once "connect.php";
include_once "functions.php";

// Initialize class instance and get configuration data
$iN = new iN_UPDATES($db);
$inc = $iN->iN_Configurations();
$subscriptionType = isset($inc['subscription_type']) ? $inc['subscription_type'] : null;

// Retrieve active or inactive yearly subscriptions that are not finished and should be processed today
$query = mysqli_query($db, "
    SELECT subscription_id, iuid_fk, subscribed_iuid_fk, plan_interval, SUM(user_net_earning) AS calculate
    FROM i_user_subscriptions
    WHERE status IN('active', 'inactive') AND in_status IN('1', '0') AND finished = '0'
      AND plan_interval = 'year'
      AND DATE(plan_period_start) = CURDATE()
    GROUP BY subscribed_iuid_fk
");

if (!$query) {
    error_log("Query error: " . mysqli_error($db));
}

// Retrieve old inactive yearly subscriptions that were marked as finished
$secondQuery = mysqli_query($db, "
    SELECT subscription_id, iuid_fk, subscribed_iuid_fk, plan_interval, SUM(user_net_earning) AS calculate
    FROM i_user_subscriptions
    WHERE status = 'inactive' AND in_status = '1' AND finished = '1'
      AND plan_interval = 'year'
      AND DATE(plan_period_start) = CURDATE()
    GROUP BY subscribed_iuid_fk
");

if (!$secondQuery) {
    error_log("Second query error: " . mysqli_error($db));
}

// Process active or renewing subscriptions
if (mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $subscriptionID = (int)$row['subscription_id'];
        $iuidFK = (int)$row['subscribed_iuid_fk'];
        $subscriberUidFK = (int)$row['iuid_fk'];
        $amountPayable = (float)$row['calculate'];
        $planInterval = $row['plan_interval'];

        // Set new subscription period (1 year from today)
        $startNewEnd = date("Y-m-d H:i:s", strtotime('+1 year'));

        // Normalize plan interval slug
        $pInterval = match($planInterval) {
            'week' => 'weekly',
            'month' => 'monthly',
            default => 'yearly'
        };

        if ($subscriptionType === '2') {
            // Point-based subscription logic
            $planData = $iN->iN_GetUserSubscriptionPlanDetails($iuidFK, $pInterval);
            $planAmount = isset($planData['amount']) ? (float)$planData['amount'] : 0;

            $uDat = $iN->iN_GetUserDetails($subscriberUidFK);
            $walletPoint = (float)$uDat['wallet_points'];

            if ($walletPoint >= $planAmount) {
                // Subscriber has enough points, process renewal
                mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + $amountPayable WHERE iuid = $iuidFK") or error_log(mysqli_error($db));
                mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points - $planAmount WHERE iuid = $subscriberUidFK") or error_log(mysqli_error($db));
                mysqli_query($db, "UPDATE i_user_subscriptions SET plan_period_start = '$startNewEnd', plan_period_end = '$startNewEnd' WHERE subscription_id = $subscriptionID") or error_log(mysqli_error($db));
            } else {
                // Insufficient points, downgrade relationship and cancel subscription
                mysqli_query($db, "UPDATE i_friends SET fr_status = 'flwr' WHERE fr_one = $subscriberUidFK AND fr_two = $iuidFK") or error_log(mysqli_error($db));
                mysqli_query($db, "UPDATE i_user_subscriptions SET status = 'declined', finished = '1', in_status = '1' WHERE subscription_id = $subscriptionID") or error_log(mysqli_error($db));
            }

            // Mark old inactive subscriptions as finished
            mysqli_query($db, "UPDATE i_user_subscriptions SET finished = '1' WHERE subscription_id = $subscriptionID AND status = 'inactive' AND in_status = '1'") or error_log(mysqli_error($db));
        } else {
            // Classic subscription logic (non-point based)
            mysqli_query($db, "UPDATE i_user_subscriptions SET plan_period_start = '$startNewEnd', plan_period_end = '$startNewEnd' WHERE subscription_id = $subscriptionID") or error_log(mysqli_error($db));
            mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + $amountPayable WHERE iuid = $iuidFK") or error_log(mysqli_error($db));
        }
    }
}

// Process previously finished inactive subscriptions for yearly plans
elseif (mysqli_num_rows($secondQuery) > 0) {
    while ($row = mysqli_fetch_assoc($secondQuery)) {
        $subscriptionID = (int)$row['subscription_id'];
        $iuidFK = (int)$row['subscribed_iuid_fk'];
        $subscriberUidFK = (int)$row['iuid_fk'];

        if ($subscriptionType === '2') {
            // Update friendship status to "follower"
            mysqli_query($db, "UPDATE i_friends SET fr_status = 'flwr' WHERE fr_one = $subscriberUidFK AND fr_two = $iuidFK") or error_log(mysqli_error($db));
        }
    }
}
?>