<?php
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

if ((int)$logedIn === 1) {
    // Load Composer autoload (if not globally loaded elsewhere)
    require_once __DIR__ . '/vendor/autoload.php';

    // Create a clean user URL for QR code
    $url = rtrim($base_url, '/') . '/' . ltrim($userName, '/');

    // Generate unique filename using microtime for uniqueness
    $microtime = microtime();
    $removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
    $qrCodeFile = 'qr_' . $removeMicrotime . '_' . (int)$userID . '1.png';

    // Define the upload path for storing the QR code image
    $serverDocumentRoot = $_SERVER['DOCUMENT_ROOT'];
    $d = date('Y-m-d');
    $uploadFile = $serverDocumentRoot . '/uploads/files/';
    $qrCodeDirectory = $uploadFile . $d . '/';

    // Create directory if it doesn't exist
    if (!file_exists($qrCodeDirectory)) {
        mkdir($qrCodeDirectory, 0755, true);
    }

    $fullPath = $qrCodeDirectory . $qrCodeFile;

    // Generate QR code image
    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($url)
        ->size(300)
        ->margin(10)
        ->build();

    file_put_contents($fullPath, $result->getString());

    // Remove old QR code if exists
    if (!empty($userQrCode)) {
        $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($userQrCode, '/');
        if (file_exists($oldPath)) {
            if (!unlink($oldPath)) {
                error_log("Failed to delete old QR code at: " . $oldPath);
            }
        }
    }

    // Save new QR code path
    $qrImage = 'uploads/files/' . $d . '/' . $qrCodeFile;

    // Escape variables for security
    $qrImageEscaped = mysqli_real_escape_string($db, $qrImage);
    $userIDEscaped = (int)$userID;

    // Update QR code path in the database
    $query = mysqli_query($db, "UPDATE i_users SET qr_image = '$qrImageEscaped' WHERE iuid = '$userIDEscaped'");
    if (!$query) {
        error_log("MySQL update error: " . mysqli_error($db));
    }

    echo $qrImageEscaped;
}
?>