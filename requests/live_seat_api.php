<?php
/* /requests/live_seat_api.php
 * Minimal, file-based seat request API (POC). No DB, no RTM.
 * Writes/reads /requests/logs/live_seats.json
 *
 * Query params:
 *   action = request | list | approve | poll | clear | diag
 *   channel (string)
 *   requester_uid (string)  -- for request/poll
 *   host_uid (string)       -- for list (optional; bookkeeping)
 *   target_uid (string)     -- for approve (who to approve)
 *
 * SECURITY: POC only. Do not expose approve/list without auth in production.
 */

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

$base    = dirname(__FILE__);
$logDir  = $base . '/logs';
$logFile = $logDir . '/live_seats.json';

if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
@chmod($logDir, 0777);
if (!file_exists($logFile)) {
    @file_put_contents($logFile, json_encode(["channels" => new stdClass()]));
}
@chmod($logFile, 0666);

function readState($file) {
    $raw  = @file_get_contents($file);
    $json = @json_decode($raw, true);
    if (!is_array($json) || !isset($json['channels'])) {
        $json = ["channels" => []];
    }
    return $json;
}

function writeState($file, $state) {
    $ok = @file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $ok !== false;
}

$action  = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : '';
$channel = isset($_REQUEST['channel']) ? trim((string)$_REQUEST['channel']) : '';

/* Quick diagnostics (no channel required) */
if ($action === 'diag') {
    echo json_encode([
        "ok"            => true,
        "dir_exists"    => is_dir($logDir),
        "dir_writable"  => is_writable($logDir),
        "file_exists"   => file_exists($logFile),
        "file_writable" => is_writable($logFile),
        "path_dir"      => $logDir,
        "path_file"     => $logFile,
    ]);
    exit;
}

if ($channel === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "channel required"]);
    exit;
}

$state = readState($logFile);
if (!isset($state['channels'][$channel])) {
    $state['channels'][$channel] = [
        "requests" => [],   // [{uid, at, status:"pending"|"approved"|"rejected"}]
        "grants"   => [],   // [{uid, at}]
        "host_uid" => null,
    ];
}
$ch = &$state['channels'][$channel];

switch ($action) {
    case 'request': {
        $uid = trim((string)($_REQUEST['requester_uid'] ?? ''));
        if ($uid === '') { echo json_encode(["ok"=>false,"error"=>"requester_uid required"]); exit; }

        foreach ($ch['requests'] as $r) {
            if ($r['uid'] === $uid && in_array($r['status'], ['pending','approved'], true)) {
                echo json_encode(["ok"=>true, "duplicate"=>true]); exit;
            }
        }
        $ch['requests'][] = ["uid"=>$uid, "at"=>time(), "status"=>"pending"];
        writeState($logFile, $state);
        echo json_encode(["ok"=>true]); exit;
    }

    case 'list': {
        $host_uid = trim((string)($_REQUEST['host_uid'] ?? ''));
        if ($host_uid !== '') $ch['host_uid'] = $host_uid;
        writeState($logFile, $state);
        $pending = array_values(array_filter($ch['requests'], function($r){ return isset($r['status']) && $r['status']==='pending'; }));
        echo json_encode(["ok"=>true, "pending"=>$pending]); exit;
    }

    case 'approve': {
        $target = trim((string)($_REQUEST['target_uid'] ?? ''));
        if ($target === '') { echo json_encode(["ok"=>false,"error"=>"target_uid required"]); exit; }

        $found = false;
        foreach ($ch['requests'] as &$r) {
            if ($r['uid'] === $target && $r['status'] === 'pending') {
                $r['status'] = 'approved';
                $found = true;
                break;
            }
        }
        if ($found) {
            $ch['grants'][] = ["uid"=>$target, "at"=>time()];
            writeState($logFile, $state);
            echo json_encode(["ok"=>true]); exit;
        } else {
            echo json_encode(["ok"=>false, "error"=>"not found or not pending"]); exit;
        }
    }

    case 'poll': {
        $uid = trim((string)($_REQUEST['requester_uid'] ?? ''));
        if ($uid === '') { echo json_encode(["ok"=>false,"error"=>"requester_uid required"]); exit; }

        $approved = false;
        foreach ($ch['grants'] as $g) {
            if ($g['uid'] === $uid) { $approved = true; break; }
        }
        echo json_encode(["ok"=>true, "approved"=>$approved]); exit;
    }

    case 'clear': {
        $ch['requests'] = [];
        $ch['grants']   = [];
        writeState($logFile, $state);
        echo json_encode(["ok"=>true, "cleared"=>true]); exit;
    }

    default:
        echo json_encode(["ok"=>false, "error"=>"invalid action"]);
}
