<?php
// Subscription expiration checker - Run this every 6 hours via cron
error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . "/includes/inc.php";

$logFile = __DIR__ . '/subscription_cron.log';

function logMessage($message) {
	global $logFile;
	$timestamp = date('Y-m-d H:i:s');
	file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("=== Subscription Cron Job Started ===");

// 1. Process expired subscriptions (mark as inactive)
$expiredCount = $iN->iN_ProcessExpiredSubscriptions();
logMessage("Expired subscriptions processed: $expiredCount");

// 2. Send expiring notifications (5 days, 2 days, same day)
$notificationsSent = $iN->iN_SendExpiringSubscriptionNotifications();
logMessage("Expiring subscription notifications sent: $notificationsSent");

logMessage("=== Subscription Cron Job Completed ===\n");

echo json_encode([
	'status' => 'success',
	'expired_count' => $expiredCount,
	'notifications_sent' => $notificationsSent
]);
?>
