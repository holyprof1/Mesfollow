<?php
include_once 'includes/connect.php';
session_start(); // Start session for the current user

/**
 * Securely remove a cookie by setting it to expire
 *
 * @param string $name The cookie name
 */
function removeCookie(string $name): void
{
    setcookie($name, '', time() - 31556926, '/');
}

// Check if the session cookie is set
if (isset($_COOKIE[$cookieName])) {
    $hashCookie = mysqli_real_escape_string($db, $_COOKIE[$cookieName]);

    // Check if a matching session exists in the database
    $checkSession = mysqli_query($db, "SELECT * FROM i_sessions WHERE session_key = '$hashCookie'");

    if ($checkSession && mysqli_num_rows($checkSession) > 0) {
        $sessionData = mysqli_fetch_assoc($checkSession);
        $loginUserID = (int)$sessionData['session_uid'];
        $loginHash = $sessionData['session_key'];

        // Remove the session from DB
        mysqli_query($db, "DELETE FROM i_sessions WHERE session_key = '$loginHash'") or error_log(mysqli_error($db));

        // Clear cookie and destroy session
        removeCookie($cookieName);
        session_destroy();

        header("Location: $base_url");
        exit();
    } else {
        // Session not found, ensure cleanup
        removeCookie($cookieName);

        $safeCookieValue = mysqli_real_escape_string($db, $_COOKIE[$cookieName]);
        mysqli_query($db, "DELETE FROM i_sessions WHERE session_key = '$safeCookieValue'") or error_log(mysqli_error($db));

        session_destroy();
        header("Location: $base_url");
        exit();
    }
} else {
    // No cookie found, attempt cleanup just in case
    if (isset($_COOKIE[$cookieName])) {
        removeCookie($cookieName);

        $safeCookieValue = mysqli_real_escape_string($db, $_COOKIE[$cookieName]);
        mysqli_query($db, "DELETE FROM i_sessions WHERE session_key = '$safeCookieValue'") or error_log(mysqli_error($db));
    }

    session_destroy();
    header("Location: $base_url");
    exit();
}