<?php
/* ===== Bootstrap your app so $db, $base_url, $iN, $cookieName, etc. exist ===== */
require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inc.php';

/* ===== Path-safe includes for the Google OAuth library ===== */
require_once __DIR__ . '/google/http.php';
require_once __DIR__ . '/google/oauth_client.php';

/* ===== Base/redirect URLs (ensure trailing slash) ===== */
$SITE_URL = rtrim($base_url ?? '/', '/') . '/';
define('SITE_URL', $SITE_URL);
define('REDIRECT_URL', SITE_URL . 'googleLogin.php');

/* ===== Google credentials pulled from admin ===== */
$Keys = $iN->iN_SocialLoginDetails('google');  // <-- $iN is available after inc.php
define('CLIENT_ID',     $Keys['s_key_one'] ?? '');
define('CLIENT_SECRET', $Keys['s_key_two'] ?? '');

/* ===== Scopes ===== */
define('SCOPE', 'openid email profile');


/* Optional logout helper */
define('LOGOUT_URL', 'https://www.google.com/accounts/Logout?continue=https://appengine.google.com/_ah/logout?continue=' . urlencode(SITE_URL . 'logout.php'));

/* ===== OAuth client config ===== */
$client = new oauth_client_class;
$client->offline      = false;
$client->debug        = false;
$client->debug_http   = false;
$client->redirect_uri = REDIRECT_URL;
$client->client_id    = CLIENT_ID;
$client->client_secret= CLIENT_SECRET;

$application_line = __LINE__;
if (!$client->client_id || !$client->client_secret) {
  die('Google client credentials missing. Fill them in Admin → Social Logins.');
}

/* ===== Start+callback flow ===== */
$client->scope = SCOPE;
if (($success = $client->Initialize())) {
  if (($success = $client->Process())) {
    if (strlen($client->authorization_error)) {
      $client->error = $client->authorization_error;
      $success = false;
    } elseif (strlen($client->access_token)) {
      $success = $client->CallAPI(
        'https://openidconnect.googleapis.com/v1/userinfo', 'GET', [], ['FailOnAccessError'=>true], $user
      );
    }
  }
  $success = $client->Finalize($success);
}
if ($client->exit) exit;

if ($client->exit) exit;

if ($success) {
    // --- Basic profile from Google ---
    $GoogleAccountFullName   = mysqli_real_escape_string($db, $user->name    ?? '');
    $GoogleAccountEmail      = mysqli_real_escape_string($db, $user->email   ?? '');
    $GoogleAccountProfileImg = mysqli_real_escape_string($db, $user->picture ?? '');
    $UserGender = 'male';

    // If Google did not give an email, bail out cleanly
    if ($GoogleAccountEmail === '') {
        $_SESSION["e_msg"] = "Google did not return an email address.";
        header("Location: ".$base_url."login");
        exit;
    }

    // --- Build a SAFE username from email (no dots or plus tags) ---
    function toUsernameFromEmail($email){
        $local = strtolower(trim(strstr($email, '@', true))); // before @
        $local = preg_replace('/\+.*/', '', $local);           // drop +tag
        $u = preg_replace('/[^a-z0-9_]/', '', $local);         // keep a-z0-9_
        if ($u === '') { $u = 'user'.substr(sha1($email), 0, 6); }
        return $u;
    }
    $GoogleAccountRegisterUserName = toUsernameFromEmail($GoogleAccountEmail);

    // --- Ensure username uniqueness (append number if taken) ---
    $baseU = $GoogleAccountRegisterUserName; $try = 0;
    while (mysqli_num_rows(mysqli_query($db,
        "SELECT 1 FROM i_users WHERE i_username = '$GoogleAccountRegisterUserName' LIMIT 1")) > 0) {
        $try++;
        $GoogleAccountRegisterUserName = $baseU.$try;
    }

    $generatePassword = sha1(md5($GoogleAccountRegisterUserName.'_'.$GoogleAccountRegisterUserName));
    $GoogleAccountRegisterUserName = trim($GoogleAccountRegisterUserName);
    $GoogleAccountEmail = trim($GoogleAccountEmail);

    // --- Does user already exist (by email OR username)? ---
    $checkUserExist = mysqli_query($db, "SELECT * FROM i_users WHERE i_username = '$GoogleAccountRegisterUserName' LIMIT 1");
    $checkEmail     = mysqli_query($db, "SELECT * FROM i_users WHERE i_user_email = '$GoogleAccountEmail' LIMIT 1");

    if (mysqli_num_rows($checkUserExist) == 0 && mysqli_num_rows($checkEmail) == 0) {
        // --- New account ---
        $time = time();
        mysqli_query($db, "SET character_set_client=utf8mb4");
        mysqli_query($db, "SET character_set_connection=utf8mb4");
        $defaultLanguage = strtolower($defaultLanguage);

        $register = mysqli_query($db, "INSERT INTO i_users
            (i_user_fullname, i_user_email, user_gender, user_avatar, i_username, registered, i_password, login_with, lang, email_verify_status)
            VALUES
            ('$GoogleAccountFullName','$GoogleAccountEmail','male','$GoogleAccountProfileImg',
             '$GoogleAccountRegisterUserName','$time','$generatePassword','google','$defaultLanguage','yes')");

        if ($register) {
            $get = mysqli_query($db, "SELECT * FROM i_users WHERE i_username = '$GoogleAccountRegisterUserName' LIMIT 1");
            if (mysqli_num_rows($get) == 1) {
                $uData = mysqli_fetch_assoc($get);
                $userID       = $uData['iuid'];
                $userUsername = $uData['i_username'];
                $userEmail    = $uData['i_user_email'];
                $time = time();

                mysqli_query($db, "UPDATE i_users SET last_login_time = '$time' WHERE iuid = '$userID'");
                $hash = sha1($userUsername).$time;
                setcookie($cookieName, $hash, time()+31556926, '/');
                mysqli_query($db, "INSERT INTO `i_sessions`(session_uid, session_key, session_time) VALUES ('$userID','$hash','$time')");
                mysqli_query($db, "INSERT INTO `i_friends` (fr_one, fr_two, fr_time, fr_status) VALUES ('$userID','$userID','$time','me')");
                $_SESSION['iuid'] = $userID;

                header("Location: ".$base_url."settings"); // force settings for now
                exit;
            }
        }
    } else {
        // --- Existing user (by email or username) ---
        // Prefer lookup by email to avoid collisions
        $get = mysqli_num_rows($checkEmail) ? $checkEmail :
               mysqli_query($db, "SELECT * FROM i_users WHERE i_username = '$GoogleAccountRegisterUserName' LIMIT 1");

        if (mysqli_num_rows($get) == 1) {
            $uData = mysqli_fetch_assoc($get);
            $userID       = $uData['iuid'];
            $userUsername = $uData['i_username'];
            $userEmail    = $uData['i_user_email'];
            $time = time();

            mysqli_query($db, "UPDATE i_users SET last_login_time = '$time' WHERE iuid = '$userID'");
            $hash = sha1($userUsername).$time;
            setcookie($cookieName, $hash, time()+31556926, '/');
            mysqli_query($db, "INSERT INTO `i_sessions`(session_uid, session_key, session_time) VALUES ('$userID','$hash','$time')");
            $_SESSION['iuid'] = $userID;

            header("Location: ".$base_url."settings"); // force settings for now
            exit;
        }
    }

} else {
    $_SESSION["e_msg"] = $client->error;
    header("Location: ".$base_url."login");
    exit;
}

header("Location: ".$base_url);
exit;

exit;
?>