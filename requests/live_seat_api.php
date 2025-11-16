<?php
/* live_seat_api.php - FINAL WORKING with proper remove + AVATAR */

error_reporting(0);
ini_set('display_errors', 0);

$base = dirname(__FILE__);
$incPath = $base . '/../includes/inc.php';

if (file_exists($incPath)) {
    require_once($incPath);
}

global $db, $base_url;

if (!isset($db) || !$db) {
    $connectPath = $base . '/../connect.php';
    if (file_exists($connectPath)) {
        require_once($connectPath);
        global $db;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

header('Content-Type: application/json');

$logDir   = $base . '/logs';
$dataFile = $logDir . '/live_seats.json';

if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}

if (!file_exists($dataFile)) {
    @file_put_contents($dataFile, json_encode(["channels" => []]));
}

function readState($file) {
    $raw  = @file_get_contents($file);
    $json = @json_decode($raw, true);
    if (!is_array($json) || !isset($json['channels'])) {
        $json = ["channels" => []];
    }
    return $json;
}

function writeState($file, $state) {
    return @file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT));
}

$action  = $_REQUEST['action'] ?? '';
$channel = trim($_REQUEST['channel'] ?? '');

if ($channel === '') {
    echo json_encode(["ok" => false, "error" => "channel required"]);
    exit;
}

$state = readState($dataFile);
if (!isset($state['channels'][$channel])) {
    $state['channels'][$channel] = [
        "requests" => [],
        "grants"   => [],
        "host_uid" => null,
    ];
}
$ch = &$state['channels'][$channel];

switch ($action) {
        /* ---- viewer sends request ---- */
    case 'request':
        global $db, $iN, $base_url;

        $uid = trim($_REQUEST['requester_uid'] ?? '');
        if ($uid === '') {
            echo json_encode(["ok" => false, "error" => "requester_uid required"]);
            exit;
        }

        // --- Build username, fullname, avatar from DB + the same helper Mesfollow uses ---
        $username = '';
        $fullname = '';
        $avatar   = '';

        if (isset($db) && ($db instanceof mysqli)) {
            $safeUid = mysqli_real_escape_string($db, $uid);
            $q = "SELECT i_username, i_user_fullname
                  FROM i_users
                  WHERE iuid = '$safeUid'
                  LIMIT 1";

            if ($res = @mysqli_query($db, $q)) {
                if (mysqli_num_rows($res) > 0) {
                    $row      = mysqli_fetch_assoc($res);
                    $username = trim($row['i_username'] ?? '');
                    $fullname = trim($row['i_user_fullname'] ?? '');
                }
            }
        }

        if ($username === '') { $username = 'user_' . $uid; }
        if ($fullname === '') { $fullname = 'User ' . $uid; }

        // Use the SAME avatar helper the rest of the site uses
        if (isset($iN) && method_exists($iN, 'iN_UserAvatar')) {
            $avatar = $iN->iN_UserAvatar($uid, $base_url);
        }
        if (!$avatar) {
            $avatar = rtrim($base_url, '/') .
                      '/content/themes/default/images/default_avatar.jpg';
        }

        // --- SINGLE ROW PER USER: if he requested before, just update that row ---
        $updated = false;
        foreach ($ch['requests'] as &$r) {
            if ($r['uid'] === $uid) {
                $r['username'] = $username;
                $r['fullname'] = $fullname;
                $r['avatar']   = $avatar;
                $r['status']   = 'pending';
                $r['at']       = time();
                $updated       = true;
                break;
            }
        }
        unset($r);

        if (!$updated) {
            $ch['requests'][] = [
                "uid"      => $uid,
                "username" => $username,
                "fullname" => $fullname,
                "avatar"   => $avatar,
                "at"       => time(),
                "status"   => "pending",
            ];
        }

        writeState($dataFile, $state);
        echo json_encode([
            "ok"       => true,
            "username" => $username,
            "fullname" => $fullname,
            "avatar"   => $avatar,
        ]);
        exit;

    case 'list':
        $host_uid = trim($_REQUEST['host_uid'] ?? '');
        if ($host_uid !== '') {
            $ch['host_uid'] = $host_uid;
        }
        writeState($dataFile, $state);

        echo json_encode(["ok" => true, "all" => $ch['requests']]);
        exit;

    case 'approve':
        $target = trim($_REQUEST['target_uid'] ?? '');
        if ($target === '') {
            echo json_encode(["ok" => false, "error" => "target_uid required"]);
            exit;
        }

        $found = false;
        foreach ($ch['requests'] as &$r) {
            if ($r['uid'] === $target) {
                $r['status'] = 'approved';
                $found       = true;
                break;
            }
        }

        if ($found) {
            $ch['grants'][] = ["uid" => $target, "at" => time()];
            writeState($dataFile, $state);
            echo json_encode(["ok" => true]);
        } else {
            echo json_encode(["ok" => false, "error" => "not found"]);
        }
        exit;

    case 'reject':
        $target = trim($_REQUEST['target_uid'] ?? '');
        if ($target === '') {
            echo json_encode(["ok" => false, "error" => "target_uid required"]);
            exit;
        }

        $found = false;
        foreach ($ch['requests'] as &$r) {
            if ($r['uid'] === $target) {
                $r['status'] = 'rejected';
                $found       = true;
                break;
            }
        }

        if ($found) {
            $ch['grants'] = array_values(array_filter($ch['grants'], function ($g) use ($target) {
                return $g['uid'] !== $target;
            }));

            writeState($dataFile, $state);
            echo json_encode(["ok" => true]);
        } else {
            echo json_encode(["ok" => false, "error" => "not found"]);
        }
        exit;

    case 'remove':  // host kicks out existing cohost
        $target = trim($_REQUEST['target_uid'] ?? '');
        if ($target === '') {
            echo json_encode(["ok" => false, "error" => "target_uid required"]);
            exit;
        }

        $found = false;
        foreach ($ch['requests'] as &$r) {
            if ($r['uid'] === $target && $r['status'] === 'approved') {
                $r['status'] = 'rejected';
                $found       = true;
                break;
            }
        }

        if ($found) {
            $ch['grants'] = array_values(array_filter($ch['grants'], function ($g) use ($target) {
                return $g['uid'] !== $target;
            }));

            writeState($dataFile, $state);
            echo json_encode(["ok" => true]);
        } else {
            echo json_encode(["ok" => false, "error" => "not found"]);
        }
        exit;

    case 'poll':
        $uid = trim($_REQUEST['requester_uid'] ?? '');
        if ($uid === '') {
            echo json_encode(["ok" => false, "error" => "requester_uid required"]);
            exit;
        }

        $approved = false;
        $rejected = false;

        foreach ($ch['grants'] as $g) {
            if ($g['uid'] === $uid) {
                $approved = true;
                break;
            }
        }

        if (!$approved) {
            foreach ($ch['requests'] as $r) {
                if ($r['uid'] === $uid && $r['status'] === 'rejected') {
                    $rejected = true;
                    break;
                }
            }
        }

        echo json_encode(["ok" => true, "approved" => $approved, "rejected" => $rejected]);
        exit;

    case 'clear':
        $ch['requests'] = [];
        $ch['grants']   = [];
        writeState($dataFile, $state);
        echo json_encode(["ok" => true, "cleared" => true]);
        exit;

    default:
        echo json_encode(["ok" => false, "error" => "invalid action"]);
}

