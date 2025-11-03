<?php
// +------------------------------------------------------------------------+
// | @author Mustafa Öztürk (mstfoztrk)
// | @author_url 1: http://www.duhovit.com
// | @author_url 2: http://codecanyon.net/user/mstfoztrk
// | @author_email: socialmaterial@hotmail.com
// +------------------------------------------------------------------------+
// | dizzy Support Creators Content Script
// | Copyright (c) 2021 mstfoztrk. All rights reserved.
// +------------------------------------------------------------------------+
define('APP_DEBUG', false);
// --------------------------------------------------------------------------
// DATABASE CONFIGURATION
// --------------------------------------------------------------------------
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'admin_mesfollow');
define('DB_PASSWORD', '!gkgvNQf@h587Oam');
define('DB_DATABASE', 'admin_mesfollow');

// --------------------------------------------------------------------------
// DATABASE CONNECTION WITH ERROR HANDLING
// --------------------------------------------------------------------------
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    mysqli_select_db($db, DB_DATABASE);
} catch (mysqli_sql_exception $e) {
    $error = $e->getMessage();
    if (APP_DEBUG === true) {
        echo '<pre>
====================================================
 DATABASE CONNECTION ERROR
====================================================

We were unable to connect to your MySQL database using the
credentials provided in the connect.php file.

Error details:
' . htmlspecialchars($error) . '

Possible causes:
- Incorrect database name
- Invalid username or password
- Database does not exist on the server
- Database server is not running or refusing connection

 To fix this, open the file:
  /includes/connect.php

And check the following values:
  - DB_SERVER
  - DB_USERNAME
  - DB_PASSWORD
  - DB_DATABASE

Once you enter the correct credentials, reload the page.
====================================================
</pre>';

        exit();
    }
}

// --------------------------------------------------------------------------
// MYSQL CHARACTER SET
// --------------------------------------------------------------------------
// ---- Connection character set (emojis + multilingual safe) ----
// ---- Connection character set (emojis + multilingual safe) ----
mysqli_set_charset($db, 'utf8mb4'); // returns bool; fine to ignore
// Keep the connection collation consistent with your tables (general_ci)
mysqli_query($db, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci'");
mysqli_query($db, "SET CHARACTER SET utf8mb4");
mysqli_query($db, "SET collation_connection = 'utf8mb4_general_ci'");



// --------------------------------------------------------------------------
// BASE URL DETECTION
// --------------------------------------------------------------------------
$protocol   = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'];
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$rootPath   = rtrim(preg_replace('/(\/requests|\/includes|\/themes|\/langs|\/src|\/ajax|\/admin|\/panel).*/i', '', $scriptName), '/');
$base_url   = $protocol . '://' . $host . $rootPath . '/';

// --------------------------------------------------------------------------
// FILE SYSTEM PATHS
// --------------------------------------------------------------------------
$serverDocumentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$projectRoot        = realpath(dirname(__FILE__));
$relativePath       = str_replace($serverDocumentRoot, '', $projectRoot);
$relativePath       = trim(str_replace('\\', '/', $relativePath), '/');
$fullUploadPath     = $serverDocumentRoot . ($relativePath ? "/$relativePath" : '');

$uploadFile     = $serverDocumentRoot . '/uploads/files/';
$xVideos        = $serverDocumentRoot . '/uploads/xvideos/';
$xImages        = $serverDocumentRoot . '/uploads/pixel/';
$uploadCover    = $serverDocumentRoot . '/uploads/covers/';
$uploadAvatar   = $serverDocumentRoot . '/uploads/avatars/';
$uploadIconLogo = $serverDocumentRoot . '/img/';
$uploadAdsImage = $serverDocumentRoot . '/uploads/spImages/';

// --------------------------------------------------------------------------
// META BASE & COOKIE
// --------------------------------------------------------------------------
$metaBaseUrl = $base_url;
$cookieName  = 'dizzy';

// --------------------------------------------------------------------------
// OPTIONAL: CLOSE DB ON SHUTDOWN
// --------------------------------------------------------------------------
register_shutdown_function(function () use ($db) {
    if ($db instanceof mysqli) {
        mysqli_close($db);
    }
});
