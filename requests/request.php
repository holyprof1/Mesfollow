<?php
@ini_set('display_errors', 0); @ini_set('log_errors', 1);

// --- quick ping (sanity check) ---
if (isset($_GET['f']) && $_GET['f'] === 'ping') {
  header('Content-Type: text/plain; charset=UTF-8');
  echo 'pong';
  exit;
}
// GET /requests/request.php?f=whoami
if (isset($_GET['f']) && $_GET['f'] === 'whoami') {
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode([
    'logged_in' => ($logedIn === '1'),
    'user_id'   => (int)$userID,
  ]);
  exit;
}

/* simple request logger (optional) */
if (!function_exists('rq_log')) {
  function rq_log($m) { @file_put_contents(__DIR__.'/../fees_debug.log', '['.date('c').'] '.$m."\n", FILE_APPEND); }
}
rq_log('hit uri='.($_SERVER['REQUEST_URI']??'').'; ref='.($_SERVER['HTTP_REFERER']??'none').'; f='.($_REQUEST['f']??''));

/* ---- TEMP DEBUG FOR tips ---- */
if (!function_exists('tipLog')) {
  function tipLog($m){ @file_put_contents(__DIR__.'/../tips_debug.log','['.date('c').'] '.$m.PHP_EOL,FILE_APPEND); }
}
if (!empty($_REQUEST['f']) && strpos($_REQUEST['f'], 'p_sendTip') === 0) {
  tipLog('HIT method='.( $_SERVER['REQUEST_METHOD'] ?? '?' ).' URI='.$_SERVER['REQUEST_URI']);
  tipLog('POST='.json_encode($_POST));
}

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){
  rq_log("PHP[$no] $str @ $file:$line");
  return false; // let normal handling continue
});
set_exception_handler(function($ex){
  rq_log("EXC ".$ex->getMessage()." @ ".$ex->getFile().":".$ex->getLine());
  header('HTTP/1.1 500 Internal Server Error');
  echo "ERR: ".htmlspecialchars($ex->getMessage());
  exit;
});

/* ---- NOTIFICATIONS DEBUG LOGGER ---- */
if (!function_exists('notiLog')) {
  function notiLog($m){
    @file_put_contents(__DIR__.'/../notifications_debug.log','['.date('c').'] '.$m.PHP_EOL, FILE_APPEND);
  }
}



/* ------------ core includes (MUST be before our handlers) ------------ */
include "../includes/inc.php";
include "../includes/thumbncrop.inc.php";


/* ---- TEMP DEBUG FOR whoSee ---- */
if (!function_exists('whoLog')) {
  function whoLog($m){ @file_put_contents(__DIR__.'/../who_see_debug.log','['.date('c').'] '.$m.PHP_EOL,FILE_APPEND); }
}
if (!empty($_REQUEST['f']) && $_REQUEST['f'] === 'whoSee') {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  whoLog('HIT method='.( $_SERVER['REQUEST_METHOD'] ?? '?' ).' CT='.$ct.' URI='.$_SERVER['REQUEST_URI']);
  whoLog('GET='.json_encode($_GET));
  whoLog('POST='.json_encode($_POST));
  if (stripos($ct,'application/json') !== false) {
    $raw = file_get_contents('php://input');
    whoLog('RAW='.$raw);
  }
}



if ($s3Status == '1') {
    include "../includes/s3.php";
} else if ($digitalOceanStatus == '1') {
    include "../includes/spaces/spaces.php";
} else if ($WasStatus == '1') {
    include "../includes/ws3.php";
}

include "../includes/imageFilter.php";
use imageFilter\ImageFilter;

use PHPMailer\PHPMailer\PHPMailer;
require '../includes/phpmailer/vendor/autoload.php';
$mail = new PHPMailer(true);

/* helpers */
function remove_http($url) {
    foreach (['http://', 'https://'] as $d) {
        if (strpos($url, $d) === 0) return str_replace($d, '', $url);
    }
    return $url;
}

/* CREATOR SELF-HEAL GUARD */
if ($logedIn == '1') {
    $uid = mysqli_real_escape_string($db, (string)$userID);

    $hasCol = function($col) use ($db) {
        $res = @mysqli_query($db, "SHOW COLUMNS FROM i_users LIKE '".mysqli_real_escape_string($db, $col)."'");
        return ($res && mysqli_num_rows($res) > 0);
    };

    $q = @mysqli_query($db, "SELECT fees_status"
        . ($hasCol('sub_week_status')  ? " ,IFNULL(sub_week_status,0)  AS w"  : " ,0 AS w")
        . ($hasCol('sub_month_status') ? " ,IFNULL(sub_month_status,0) AS m"  : " ,0 AS m")
        . ($hasCol('sub_year_status')  ? " ,IFNULL(sub_year_status,0)  AS y"  : " ,0 AS y")
        . ($hasCol('sub_week_amount')  ? " ,IFNULL(sub_week_amount,0)  AS wa" : " ,0 AS wa")
        . ($hasCol('sub_month_amount') ? " ,IFNULL(sub_month_amount,0) AS ma" : " ,0 AS ma")
        . ($hasCol('sub_year_amount')  ? " ,IFNULL(sub_year_amount,0)  AS ya" : " ,0 AS ya")
        . " FROM i_users WHERE iuid='$uid' LIMIT 1");

    if ($q && ($r = mysqli_fetch_assoc($q))) {
        $anyOn = ((int)$r['w'] === 1 || (int)$r['m'] === 1 || (int)$r['y'] === 1);
        if ($anyOn && (int)$r['fees_status'] !== 2) {
            @mysqli_query($db, "UPDATE i_users SET fees_status=2 WHERE iuid='$uid' LIMIT 1");
            rq_log('guard→forced fees_status=2; ref='.(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'none'));
        }
    }
}

/* OpenAI Integration */
if ($openAiStatus == '1') {
    function callOpenAI($userPrompt, $opanAiKey) {
        $apiKey = $opanAiKey;
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            "model" => "gpt-4-turbo",
            "messages" => [
                ["role" => "system", "content" => "You are a content creator assistant. Always respond with a creative text, maximum 250 characters."],
                ["role" => "user", "content" => $userPrompt]
            ],
            "temperature" => 0.8,
            "max_tokens" => 150
        ];
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        return $result['choices'][0]['message']['content'] ?? "no";
    }
}

/* Watermark System */
if ($watermarkStatus == 'yes') {
    function watermark_image($target, $siteWatermarkLogo, $LinkWatermarkStatus, $ourl) {
        include_once "../includes/SimpleImage-master/src/claviska/SimpleImage.php";
        try {
            $image = new \claviska\SimpleImage();
            $image->fromFile($target)->autoOrient();
            if ($LinkWatermarkStatus == 'yes') {
                $image->overlay('../' . $siteWatermarkLogo, 'top left', 1, 30, 30)
                      ->text($ourl, [
                          'fontFile' => '../src/droidsanschinese.ttf',
                          'size' => 15,
                          'color' => 'red',
                          'anchor' => 'bottom right',
                          'xOffset' => -10,
                          'yOffset' => -10
                      ]);
            } else {
                $image->overlay('../' . $siteWatermarkLogo, 'top left', 1, 30, 30);
            }
            $image->toFile($target, 'image/jpeg');
            return true;
        } catch (Exception $err) {
            return $err->getMessage();
        }
    }
} else if ($LinkWatermarkStatus == 'yes') {
    function watermark_image($target, $siteWatermarkLogo, $LinkWatermarkStatus, $ourl) {
        include_once "../includes/SimpleImage-master/src/claviska/SimpleImage.php";
        try {
            $image = new \claviska\SimpleImage();
            $image->fromFile($target)
                  ->autoOrient()
                  ->overlay('../img/transparent.png', 'top left', 1, 30, 30)
                  ->text($ourl, [
                      'fontFile' => '../src/droidsanschinese.ttf',
                      'size' => 15,
                      'color' => '00897b',
                      'anchor' => 'bottom right',
                      'xOffset' => -10,
                      'yOffset' => -10
                  ])
                  ->toFile($target, 'image/jpeg');
            return true;
        } catch (Exception $err) {
            return $err->getMessage();
        }
    }
}








// --- Alias for Reels comments-only popup (PUBLIC) ---
$f = $_POST['f'] ?? $_GET['f'] ?? '';
if ($f === 'getReelComments') {
  rq_log('getReelComments alias hit; post_id='.(int)($_POST['post_id'] ?? 0).' logged_in='.(string)$logedIn);
  $_POST['f'] = 'getPostForModal';
  $_POST['comments_only'] = 1;
  $_POST['source'] = 'reels';
}







/* ==========================================================
   f = mentionFollowers
   Returns YOUR followers + subscribers for "@"
   Input : GET q   (prefix of username or fullname)
   Output: JSON [{id, username, name, avatar, profile}]
   ========================================================== */
if (isset($_GET['f']) && $_GET['f'] === 'mentionFollowers') {
    header('Content-Type: application/json; charset=UTF-8');
    if (empty($logedIn) || (string)$logedIn !== '1' || empty($userID)) {
        echo '[]'; exit;
    }
    $dbc = (isset($db) && $db instanceof mysqli) ? $db : null;
    if (!$dbc) { echo '[]'; exit; }
    $me = (int)$userID;
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($q === '') { echo '[]'; exit; }
    $sql = "
        SELECT
            u.iuid,
            u.i_username,
            COALESCE(NULLIF(u.i_user_fullname, ''), u.i_username) AS full_name,
            u.user_verified_status
        FROM i_friends f
        JOIN i_users u ON u.iuid = f.fr_one
        WHERE f.fr_two = ?
          AND f.fr_status IN ('flwr', 'subscriber', '1', '2')
          AND (u.i_username LIKE CONCAT(?, '%') OR u.i_user_fullname LIKE CONCAT(?, '%'))
        ORDER BY u.user_verified_status DESC, u.i_username ASC
        LIMIT 12
    ";
    $stmt = $dbc->prepare($sql);
    if (!$stmt) { echo '[]'; exit; }
    $stmt->bind_param('iss', $me, $q, $q);
    $stmt->execute();
    $out = [];
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $uid = (int)$r['iuid'];
                $uname = $r['i_username'];
                $out[] = [
                    'id' => $uid,
                    'username' => $uname,
                    'name' => $r['full_name'] ?: $uname,
                    'avatar' => $iN->iN_UserAvatar($uid, $base_url),
                    'profile' => $base_url . $uname
                ];
            }
        }
    } else {
        $stmt->store_result();
        $stmt->bind_result($iuid, $i_username, $full_name, $verified);
        while ($stmt->fetch()) {
            $uid = (int)$iuid;
            $uname = $i_username;
            $out[] = [
                'id' => $uid,
                'username' => $uname,
                'name' => $full_name ?: $uname,
                'avatar' => $iN->iN_UserAvatar($uid, $base_url),
                'profile' => $base_url . $uname
            ];
        }
    }
    echo json_encode($out, JSON_UNESCAPED_SLASHES);
    exit;
}

// --- disable ALL legacy mentions endpoints except our new one ---
if (isset($_GET['f'])) {
  $f = $_GET['f'];
  if ($f !== 'mentionFollowers' && strpos($f, 'mention') === 0) {
    header('Content-Type: application/json; charset=UTF-8');
    echo '[]';
    exit;
  }
  // also kill a few older names some themes used
  if (in_array($f, ['mentions','mentionUsers','mentionList','searchUsers','searchUser'], true)) {
    header('Content-Type: application/json; charset=UTF-8');
    echo '[]';
    exit;
  }
}







/* ==========================================================
   PUBLIC: getPostForModal / getReelComments (used by Reels sheet)
   Returns the theme fragment without requiring login.
   ========================================================== */
/* ==========================================================
   PUBLIC: getPostForModal / getReelComments (used by Reels sheet)
   ========================================================== */
/* ==========================================================
   PUBLIC: getPostForModal / getReelComments (used by Reels sheet)
   ========================================================== */
if (isset($_POST['f'])) {
  $tpub = $_POST['f'];
  if ($tpub === 'getPostForModal' || $tpub === 'getReelComments') {
    header('Content-Type: text/html; charset=UTF-8');

    $userPostID = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    if ($userPostID > 0) {
      // Try full post; OK if it fails for privacy — comments-only doesn’t need it
      $postFromData = $iN->iN_GetAllPostDetails($userPostID);

      // Reels uses the small fragment
      if ($tpub === 'getReelComments' || isset($_POST['comments_only'])) {
        $_POST['post_id'] = $userPostID;          // make sure the include can see it
        $GLOBALS['userPostID'] = $userPostID;     // belt + suspenders

        echo "<!-- mf:comments_only start post_id={$userPostID} -->";
        include "../themes/$currentTheme/layouts/comments_only.php";
        echo "<!-- mf:comments_only end -->";
        exit;
      }

      // Full modal fallback (site’s original)
      if ($postFromData) {
        $slugyUrl = ($postFromData['url_slug'] ?? ($postFromData['urlslug'] ?? '')) . '_' . $postFromData['post_id'];
        include "../themes/$currentTheme/layouts/post.php";
      } else {
        echo '<div style="padding:14px;color:#999">Post not found.</div>';
      }
    } else {
      echo '<div style="padding:14px;color:#999">Invalid post.</div>';
    }
    exit;
  }
}








/* ----- keep everything below this point unchanged ----- */



if (isset($_POST['f']) && $logedIn == '1') {
	$loginFormClass = '';
	$type = mysqli_real_escape_string($db, $_POST['f']);
	
	/* Back-compat alias for old header JS */
if ($type === 'NotificationSeen') {
  $_POST['f'] = 'markNotificationRead';
  $type = 'markNotificationRead';
}

	/* ==========================================================
   f = comment  (insert a new comment and return its HTML)
   ========================================================== */
if ($type === 'comment') {
  header('Content-Type: text/html; charset=UTF-8');
  if ($logedIn !== '1' || empty($userID)) { echo '404'; exit; }

  $pid     = isset($_POST['id'])  ? (int)$_POST['id']  : 0;
  $txtRaw  = isset($_POST['val']) ? trim($_POST['val']) : '';
  $sticker = trim($_POST['sticker'] ?? '');
  $gif     = trim($_POST['gf'] ?? '');

  if ($pid <= 0 && $txtRaw === '' && $sticker === '' && $gif === '') { echo '404'; exit; }
	
	//== START: STICKER ID TO URL CONVERTER ==//
if (!empty($sticker) && is_numeric($sticker)) {
    $stickerID = (int)$sticker;
    $stickerUrlQuery = mysqli_query($db, "SELECT sticker_url FROM i_stickers WHERE sticker_id = '$stickerID' LIMIT 1");
    if ($stickerUrlQuery && mysqli_num_rows($stickerUrlQuery) > 0) {
        $stickerData = mysqli_fetch_assoc($stickerUrlQuery);
        $sticker = $stickerData['sticker_url']; // Replace the ID with the actual URL
    } else {
        $sticker = ''; // In case of an invalid ID, clear it.
    }
}
//== END: STICKER ID TO URL CONVERTER ==//

  $txt = mysqli_real_escape_string($db, $txtRaw);
  $now = time();
  $uid = (int)$userID;

  $q = @mysqli_query($db, "INSERT INTO i_post_comments
            (comment_post_id_fk, comment_uid_fk, comment, comment_time, sticker_url, gif_url)
        VALUES ($pid, $uid, '$txt', $now, '".mysqli_real_escape_string($db,$sticker)."', '".mysqli_real_escape_string($db,$gif)."')");

  if (!$q) { echo '404'; exit; }

  $cid = (int)mysqli_insert_id($db);
	
	/* ---- NOTIFICATIONS for comment (owner + mentions) ---- */

// find post owner
/* ---- NOTIFICATIONS for comment (owner + mentions) ---- */
/* Helpers (idempotent): check column exists + fetch post owner safely */
if (!function_exists('mf_has_col')) {
  function mf_has_col(mysqli $db, string $table, string $col) : bool {
    $t = mysqli_real_escape_string($db, $table);
    $c = mysqli_real_escape_string($db, $col);
    $q = @mysqli_query($db, "SHOW COLUMNS FROM `$t` LIKE '$c'");
    return ($q && mysqli_num_rows($q) > 0);
  }
}
if (!function_exists('mf_get_post_owner')) {
  function mf_get_post_owner(mysqli $db, int $pid) : int {
    // try several common owner columns
    $table = 'i_posts';
    $cands = ['post_owner_iuid','post_owner_id','post_owner','iuid','iuid_fk','uid_fk','post_uid_fk'];
    $ownerCol = '';
    foreach ($cands as $c) {
      if (mf_has_col($db, $table, $c)) { $ownerCol = $c; break; }
    }
    if (!$ownerCol) return 0;
    $pid = (int)$pid;
    $res = @mysqli_query($db, "SELECT `$ownerCol` AS owner FROM `$table` WHERE `post_id` = $pid LIMIT 1");
    if ($res && ($r = mysqli_fetch_assoc($res))) { return (int)$r['owner']; }
    return 0;
  }
}

	
if (!function_exists('mf_safe_insert_notification')) {
  function mf_safe_insert_notification(mysqli $db, array $vals) : bool {
    $table = 'i_user_notifications';

    // columns we may populate
    $allPossible = [
      'not_iuid', 'not_own_iuid', 'not_not_type',
      'not_post_id', 'not_comment_id', 'not_time',
      'not_status', 'not_show_hide', 'not_type'
    ];

    // enum-like (must be quoted '0'/'1' in your schema)
    $enumLike = ['not_status','not_show_hide'];

    $cols = [];
    $data = [];
    foreach ($allPossible as $k) {
      if (!array_key_exists($k, $vals)) continue;
      // only insert if column exists
      $t = mysqli_real_escape_string($db, $table);
      $c = mysqli_real_escape_string($db, $k);
      $q = @mysqli_query($db, "SHOW COLUMNS FROM `$t` LIKE '$c'");
      if (!$q || mysqli_num_rows($q) === 0) continue;

      $cols[] = "`$k`";
      $v = $vals[$k];

      if (in_array($k, $enumLike, true)) {
        // force '0'/'1' as strings
        $s = ($v === 1 || $v === '1') ? '1' : '0';
        $data[] = "'".$s."'";
      } else if (is_int($v) || is_float($v)) {
        $data[] = (string)(int)$v;
      } else {
        $data[] = "'".mysqli_real_escape_string($db, (string)$v)."'";
      }
    }

    if (!$cols) return false;
    $sql = "INSERT INTO `$table` (".implode(',', $cols).") VALUES (".implode(',', $data).")";
    return (bool)@mysqli_query($db, $sql);
  }
}

	
	
	
/* 1) Notify post owner (if not commenting on own post) */
$postOwner = mf_get_post_owner($db, $pid);
if ($postOwner && $postOwner !== $uid) {
  mf_safe_insert_notification($db, [
    'not_iuid'        => $uid,               // actor (commenter)
    'not_own_iuid'    => $postOwner,         // receiver
    'not_not_type'    => 'commented',
    'not_post_id'     => $pid,
    'not_comment_id'  => $cid,
    'not_time'        => $now,
    'not_status'      => 0,
    'not_show_hide'   => 0
  ]);
}

/* 2) @mention notifications (skip duplicates/self/owner) */
if (preg_match_all('/@([A-Za-z0-9_.]{3,32})\b/u', $txtRaw, $mm)) {
  $usernames = array_unique($mm[1]);
  foreach ($usernames as $mentioned) {
    $uName = mysqli_real_escape_string($db, $mentioned);
    $qr = @mysqli_query($db, "SELECT iuid FROM i_users WHERE i_username='{$uName}' LIMIT 1");
    if ($qr && ($ud = mysqli_fetch_assoc($qr))) {
      $to = (int)$ud['iuid'];
      if ($to && $to !== $uid && $to !== $postOwner) {
        mf_safe_insert_notification($db, [
          'not_iuid'        => $uid,            // actor (commenter)
          'not_own_iuid'    => $to,             // receiver
          'not_not_type'    => 'umentioned',
          'not_post_id'     => $pid,
          'not_comment_id'  => $cid,
          'not_time'        => $now,
          'not_status'      => 0,
          'not_show_hide'   => 0
        ]);
      }
    }
  }
}
/* ---- end notifications block ---- */



  // fetch minimal info to render the single new item
  $sql = "SELECT C.com_id, C.comment, C.comment_time, C.comment_uid_fk,
                 U.i_username, U.i_user_fullname, U.user_verified_status
          FROM i_post_comments C
          JOIN i_users U ON U.iuid = C.comment_uid_fk
          WHERE C.com_id = $cid
          LIMIT 1";
  $r = null;
  if ($res = @mysqli_query($db, $sql)) { $r = mysqli_fetch_assoc($res); }

  if (!$r) { echo '404'; exit; }

  // small helpers (duplicated to keep this endpoint self-contained)
  $esc = function($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); };
  $userUrl = function($u) use ($base_url,$esc){ return rtrim($base_url,'/').'/'.$esc($u); };
  $linkify = function($text) use ($base_url,$esc){
    $safe = $esc($text);
    return preg_replace_callback('/(^|[\s>])@([A-Za-z0-9_.]{3,32})\b/u', function($m) use ($base_url,$esc){
      $u = $m[2]; $url = rtrim($base_url,'/').'/'.$u;
      return $m[1].'<a class="mention" href="'.$esc($url).'" target="_blank">@'.$esc($u).'</a>';
    }, $safe);
  };

  $uid    = (int)$r['comment_uid_fk'];
  $uname  = $r['i_username'] ?: ('user'.$uid);
  $fname  = $r['i_user_fullname'] ?: $uname;
  $when   = (int)$r['comment_time'];
  $ava    = (isset($iN) && method_exists($iN,'iN_UserAvatar')) ? $iN->iN_UserAvatar($uid, $base_url) : '';
  $uurl   = $userUrl($uname);
  $text   = $linkify($r['comment'] ?? '');

 //== START: CORRECTED HTML FOR NEW COMMENT ==//

echo '
<div class="i_u_comment_body dlCm'.iN_HelpSecure($cid).'" id="'.iN_HelpSecure($cid).'">
    <div class="i_post_user_commented_avatar_out">
        <div class="i_post_user_commented_avatar">
            <img src="'.iN_HelpSecure($ava).'" alt="'.iN_HelpSecure($fname).'">
        </div>
    </div>
    <div class="i_user_comment_header">
       <div class="i_user_commented_user_infos">
            <a href="'.iN_HelpSecure($uurl).'">
                '.iN_HelpSecure($fname).'
            </a>
        </div>
        <div class="i_user_comment_text" id="i_u_c_'.iN_HelpSecure($cid).'">
            '.$text.'
        </div>
        ' . ($sticker ? '<div class="comment_file"><img src="' . $sticker . '"></div>' : '') . '
        ' . ($gif ? '<div class="comment_gif_file"><img src="' . $gif . '"></div>' : '') . '
        <div class="i_comment_like_time">
            <div class="i_comment_reply rplyComment" id="'.iN_HelpSecure($pid).'" data-who="'.iN_HelpSecure($uname).'">
                Reply
            </div>
            <div class="i_comment_like_btn">
                <div class="i_comment_item_btn transition c_in_l_'.iN_HelpSecure($cid).' c_in_like" id="com_'.iN_HelpSecure($cid).'" data-id="'.iN_HelpSecure($cid).'" data-p="'.iN_HelpSecure($pid).'">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.55l-.1.1-.1-.1C7.14 14.24 4 11.39 4 8.5 4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5 0 2.89-3.14 5.74-7.9 10.05z"></path></svg>
                </div>
                <div class="i_comment_like_sum" id="t_c_'.iN_HelpSecure($cid).'">0</div>
            </div>
            <div class="i_comment_time">
                Just now
            </div>
            <div class="i_comment_call_popup openComMenu" id="'.iN_HelpSecure($cid).'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path></svg>
                <div class="i_comment_menu_container comMBox comMBox'.iN_HelpSecure($cid).'">
                    <div class="i_comment_menu_wrapper">
                        <div class="i_post_menu_item_out delCm transition" id="'.iN_HelpSecure($cid).'" data-id="'.iN_HelpSecure($pid).'">'.html_entity_decode($iN->iN_SelectedMenuIcon("28")).' '.$LANG['delete_comment'].'</div>
                        <div class="i_post_menu_item_out cced transition" id="'.iN_HelpSecure($cid).'" data-id="'.iN_HelpSecure($pid).'">'.html_entity_decode($iN->iN_SelectedMenuIcon("27")).' '.$LANG['edit_comment'].'</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

//== END: CORRECTED HTML FOR NEW COMMENT ==//
  exit;
}

	
	/* Avatar/Cover popup */
if ($type === 'updateAvatarCover') {
  header('Content-Type: text/html; charset=UTF-8');

  // try common filenames used by themes
  $candidates = [
    "../themes/$currentTheme/layouts/popup_alerts/uploadAvatarCoverPhoto.php",
    "../themes/$currentTheme/layouts/popup_alerts/updateAvatarCover.php",
    "../themes/$currentTheme/layouts/popup_alerts/updateAvatarCoverPhoto.php",
    "../themes/$currentTheme/layouts/popup_alerts/avatarCoverPhoto.php",
  ];

  $file = '';
  foreach ($candidates as $p) { if (file_exists($p)) { $file = $p; break; } }

  if ($file) {
    include $file;  // return the theme’s full modal (with croppie + handlers)
  } else {
    // last-resort tiny fallback
    echo '<div class="i_modal_bg_in"><div class="i_modal_in" style="max-width:520px;margin:40px auto;background:#fff;border-radius:12px;padding:16px">
            <div class="i_modal_title" style="font-weight:600;margin-bottom:8px">Update avatar / cover</div>
            <div class="i_modal_desc" style="opacity:.8;margin-bottom:16px">Popup not available. Open the avatar settings page instead.</div>
            <div class="i_modal_actions" style="display:flex;gap:8px;justify-content:flex-end">
              <a class="i_nex_btn_btn" href="'.$base_url.'settings?tab=avatar_settings">Open settings</a>
              <button type="button" class="i_close_modal" style="padding:8px 12px;border-radius:8px;background:#eee">Close</button>
            </div>
          </div></div>
          <script>document.querySelector(".i_close_modal")?.addEventListener("click",function(){document.querySelector(".i_modal_bg_in")?.remove()});</script>';
  }
  exit;
}

	
	
	
		/* ==========================================================
   AVATAR / COVER UPLOAD (handles Croppie base64 or $_FILES)
   Called as: f=avatarUpload  or  f=coverUpload
   Optional:  what=avatar|cover when using avatarUpload for both
   Returns:   "200\n{ok:1, avatar|cover: <url>}"
   ========================================================== */
if ($type === 'avatarUpload' || $type === 'coverUpload') {
  header('Content-Type: text/plain; charset=UTF-8');

  // decide what we are saving
  $kind = ($type === 'coverUpload' || (isset($_POST['what']) && $_POST['what'] === 'cover')) ? 'cover' : 'avatar';

  // accept Croppie base64 OR a normal file
  $raw = $_POST['img'] ?? $_POST['image'] ?? $_POST['data'] ?? '';
  $fileKey = $kind === 'avatar' ? 'avatar_file' : 'cover_file';

  $bin = '';
  if ($raw && preg_match('~^data:image/(\w+);base64,~', $raw)) {
    $bin = base64_decode(preg_replace('~^data:image/\w+;base64,~', '', $raw), true);
  } elseif (!empty($_FILES[$fileKey]['tmp_name'])) {
    $bin = @file_get_contents($_FILES[$fileKey]['tmp_name']);
  }
  if (!$bin) { echo "404\n".json_encode(['ok'=>0,'msg'=>'no_image']); exit; }

  // simple GD resize/crop (fits + center-crops to target)
  $src = @imagecreatefromstring($bin);
  if (!$src) { echo "404\n".json_encode(['ok'=>0,'msg'=>'bad_image']); exit; }
  $sw = imagesx($src); $sh = imagesy($src);

  $tw = $kind === 'avatar' ? 400 : 1500;
  $th = $kind === 'avatar' ? 400 : 500;

  $scale = max($tw/$sw, $th/$sh);
  $nw = (int)ceil($sw*$scale); $nh = (int)ceil($sh*$scale);
  $ox = (int)floor(($nw-$tw)/2); $oy = (int)floor(($nh-$th)/2);

  $tmp = imagecreatetruecolor($nw,$nh);
  imagecopyresampled($tmp,$src,0,0,0,0,$nw,$nh,$sw,$sh);
  imagedestroy($src);

  $dst = imagecreatetruecolor($tw,$th);
  imagecopy($dst,$tmp,-$ox,-$oy,0,0,$nw,$nh);
  imagedestroy($tmp);

  // ensure folder
  $root = dirname(__DIR__); // from /requests to site root
  $sub  = $kind === 'avatar' ? 'avatars' : 'covers';
  $dir  = $root.'/uploads/'.$sub;
  if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

  // save jpeg
  $fname = ($kind==='avatar'?'ava_':'cov_').$userID.'_'.time().'.jpg';
  $fpath = $dir.'/'.$fname;
  imagejpeg($dst, $fpath, 88);
  imagedestroy($dst);

$rel   = 'uploads/'.$sub.'/'.$fname;
$url   = $base_url.$rel;

  // update whichever column your DB has
  if (!function_exists('mf_has_col')) {
    function mf_has_col($db,$col){
      $col = mysqli_real_escape_string($db,$col);
      $q = @mysqli_query($db,"SHOW COLUMNS FROM i_users LIKE '{$col}'");
      return ($q && mysqli_num_rows($q) > 0);
    }
  }
  if ($kind === 'avatar') {
    $col = mf_has_col($db,'i_user_avatar') ? 'i_user_avatar'
         : (mf_has_col($db,'user_avatar') ? 'user_avatar'
         : (mf_has_col($db,'avatar') ? 'avatar' : ''));
  } else {
    $col = mf_has_col($db,'cover') ? 'cover'
         : (mf_has_col($db,'user_cover') ? 'user_cover'
         : (mf_has_col($db,'cover_photo') ? 'cover_photo' : ''));
  }
if ($col) {
  $esc = mysqli_real_escape_string($db,$rel); // <-- store relative only
  @mysqli_query($db,"UPDATE i_users SET {$col}='{$esc}' WHERE iuid=".(int)$userID." LIMIT 1");
}

// return absolute so the modal preview updates instantly
echo $url;
exit;

}

	/* Reset cover to default */
if ($type === 'resetCover') {
  header('Content-Type: text/plain; charset=UTF-8');

  if (!function_exists('mf_has_col')) {
    function mf_has_col($db,$col){
      $col = mysqli_real_escape_string($db,$col);
      $q = @mysqli_query($db,"SHOW COLUMNS FROM i_users LIKE '{$col}'");
      return ($q && mysqli_num_rows($q) > 0);
    }
  }

  $col = mf_has_col($db,'cover') ? 'cover'
       : (mf_has_col($db,'user_cover') ? 'user_cover'
       : (mf_has_col($db,'cover_photo') ? 'cover_photo' : ''));

  if ($col) {
    @mysqli_query($db, "UPDATE i_users SET {$col}='' WHERE iuid=".(int)$userID." LIMIT 1");
    echo "200";
  } else {
    echo "404";
  }
  exit;
}

	
	
	
	
	/* ---------- profile saver (settings) ------------- */
if ($type === 'editMyPage') {
  header('Content-Type: text/plain; charset=UTF-8');
	mysqli_set_charset($db, "utf8mb4");


  // tiny logger
  if (!function_exists('rq_log')) {
    function rq_log($m){ @file_put_contents(__DIR__.'/../fees_debug.log','['.date('c').'] '.$m.PHP_EOL,FILE_APPEND); }
  }
  $uid = (int)$userID;

  // helper: does column exist?
  if (!function_exists('mf_has_col')) {
    function mf_has_col($db, $col){
      $col = mysqli_real_escape_string($db, $col);
      $q = @mysqli_query($db, "SHOW COLUMNS FROM i_users LIKE '{$col}'");
      return ($q && mysqli_num_rows($q) > 0);
    }
  }

  // get form fields (these are the names in your page source)
  $full   = trim($_POST['flname']     ?? '');
  $uname  = trim($_POST['uname']      ?? '');
  $cat    = trim($_POST['ctgry']      ?? '');     // e.g. 'normal_user'
  $gender = trim($_POST['gender']     ?? '');     // male|female|couple
  $birth  = trim($_POST['birthdate']  ?? '');     // DD/MM/YYYY
  $twitter    = trim($_POST['twitter']   ?? '');
  $instagram  = trim($_POST['instagram'] ?? '');
  $tiktok     = trim($_POST['tiktok']    ?? '');
  $facebook   = trim($_POST['Facebook']  ?? '');
  $bio    = trim($_POST['bio']        ?? '');
  $tnot   = trim($_POST['tnot']       ?? '');

  // url sanitizer (allow http/https only)
  $url = function($u){
    $u = trim($u);
    if ($u === '') return '';
    if (!preg_match('~^https?://~i', $u)) $u = 'https://'.$u;
    return filter_var($u, FILTER_VALIDATE_URL) ? $u : '';
  };
  $twitter   = $url($twitter);
  $instagram = $url($instagram);
  $tiktok    = $url($tiktok);
  $facebook  = $url($facebook);

  // birthday to YYYY-MM-DD if valid DD/MM/YYYY
  $birthSql = '';
  if ($birth && preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $birth, $m)) {
    $birthSql = $m[3].'-'.$m[2].'-'.$m[1];
  }

  $sets = [];

  // full name (respect your UI rule 5..16 if provided)
  if ($full !== '' && strlen($full) >= 3) {
    $col = mf_has_col($db,'i_user_fullname') ? 'i_user_fullname'
         : (mf_has_col($db,'full_name') ? 'full_name' : (mf_has_col($db,'name') ? 'name' : ''));
    if ($col) { $v = mysqli_real_escape_string($db,$full); $sets[] = "{$col}='{$v}'"; }
  }

  // username (validate + unique)
  if ($uname !== '') {
    // only letters, numbers, underscore, dot; >= 5 chars
    if (!preg_match('~^[A-Za-z0-9_.]{5,32}$~', $uname)) {
      echo "404\n".json_encode(['ok'=>0,'msg'=>'invalid_username']); exit;
    }
    $colU = mf_has_col($db,'i_username') ? 'i_username'
          : (mf_has_col($db,'username') ? 'username' : '');
    if ($colU) {
      $u = mysqli_real_escape_string($db,$uname);
      $dupe = @mysqli_query($db,"SELECT iuid FROM i_users WHERE {$colU}='{$u}' AND iuid<>$uid LIMIT 1");
      if ($dupe && mysqli_num_rows($dupe)) {
        echo "404\n".json_encode(['ok'=>0,'msg'=>'username_taken']); exit;
      }
      $sets[] = "{$colU}='{$u}'";
    }
  }


  // gender
  if (in_array($gender,['male','female','couple'],true)) {
    $col = mf_has_col($db,'gender') ? 'gender'
         : (mf_has_col($db,'user_gender') ? 'user_gender' : '');
    if ($col) { $v = mysqli_real_escape_string($db,$gender); $sets[] = "{$col}='{$v}'"; }
  }

  // birthday
  if ($birthSql) {
    $col = mf_has_col($db,'birthday') ? 'birthday'
         : (mf_has_col($db,'birthdate') ? 'birthdate'
         : (mf_has_col($db,'user_birthday') ? 'user_birthday'
         : (mf_has_col($db,'dob') ? 'dob' : (mf_has_col($db,'bdate') ? 'bdate' : ''))));
    if ($col) { $sets[] = "{$col}='".mysqli_real_escape_string($db,$birthSql)."'"; }
  }

 // category  (use profile_category in your DB)
if ($cat !== '') {
  $col = mf_has_col($db,'profile_category') ? 'profile_category'
       : (mf_has_col($db,'user_category') ? 'user_category'
       : (mf_has_col($db,'category') ? 'category' : ''));
  if ($col) { $v = mysqli_real_escape_string($db,$cat); $sets[] = "{$col}='{$v}'"; }
}

// bio / description  (use u_bio in your DB)
if ($bio !== '') {
  $col = mf_has_col($db,'u_bio') ? 'u_bio'
       : (mf_has_col($db,'i_profile_bio') ? 'i_profile_bio'
       : (mf_has_col($db,'about') ? 'about' : (mf_has_col($db,'bio') ? 'bio' : '')));
  if ($col) { $sets[] = "{$col}='".mysqli_real_escape_string($db,$bio)."'"; }
}

// thank-you note (use thanks_for_tip in your DB)
if ($tnot !== '') {
  foreach (['thanks_for_tip','thank_you_note','thanks_note','tip_thanks','thanktext','thanks'] as $c) {
    if (mf_has_col($db,$c)) { $sets[] = "{$c}='".mysqli_real_escape_string($db,$tnot)."'"; break; }
  }
}


  // socials (only if those columns exist)
  $socials = [
    'twitter'   => $twitter,
    'instagram' => $instagram,
    'tiktok'    => $tiktok,
    'facebook'  => $facebook
  ];
  foreach ($socials as $k=>$val) {
    if ($val==='') continue;
    $cand = [$k, "{$k}_url", $k==='facebook'?'fb':''];
    foreach ($cand as $c) {
      if ($c && mf_has_col($db,$c)) { $sets[] = "{$c}='".mysqli_real_escape_string($db,$val)."'"; break; }
    }
  }

  if (!$sets) {
    rq_log('editMyPage: nothing to update (fields empty or columns not found)');
    echo "200\n".json_encode(['ok'=>1,'msg'=>'noop']); exit;
  }

  $sql = "UPDATE i_users SET ".implode(',', $sets)." WHERE iuid={$uid} LIMIT 1";
  $ok  = @mysqli_query($db,$sql);
  if (!$ok) { rq_log('editMyPage SQL ERR: '.mysqli_error($db).' :: '.$sql); echo "404\n".json_encode(['ok'=>0]); }
  else      { echo "200\n".json_encode(['ok'=>1]); }
  exit;
}

	
	
	
	
/* ==========================================================
 f = getNotificationCounts
 Lightweight poller to get unread counts for header icons.
 ========================================================== */
if ($type == 'getNotificationCounts') {
    header('Content-Type: application/json; charset=UTF-8');

    // Ensure we only run this for logged-in users with a valid DB connection
    if ($logedIn == '1' && isset($db) && $db instanceof mysqli) {
        $currentUserID = (int)$userID;
        $unreadNotifications = 0;
        $unreadMessages = 0;

        // Get unread notifications count
        if ($stmt = $db->prepare("SELECT COUNT(not_id) FROM i_user_notifications WHERE not_own_iuid = ? AND not_status = '0' AND not_show_hide = '0'")) {
            $stmt->bind_param('i', $currentUserID);
            $stmt->execute();
            $stmt->bind_result($unreadNotifications);
            $stmt->fetch();
            $stmt->close();
        }

        // Get unread messages count
        if ($stmt = $db->prepare("SELECT COUNT(id) FROM i_user_messages WHERE msg_to_iuid = ? AND msg_status = '0'")) {
            $stmt->bind_param('i', $currentUserID);
            $stmt->execute();
            $stmt->bind_result($unreadMessages);
            $stmt->fetch();
            $stmt->close();
        }

        echo json_encode([
            'unread_notifications' => (int)$unreadNotifications,
            'unread_messages' => (int)$unreadMessages
        ]);

    } else {
        // If the user is not logged in or DB is not available, return zero
        echo json_encode([
            'unread_notifications' => 0,
            'unread_messages' => 0
        ]);
    }
    exit;
}
	
	
	
	
	/* ==========================================================
 f = mark_grouped_notifications_read
 Marks all notifications in a group (e.g. all likes on a post) as read.
 ========================================================== */
if ($type == 'mark_grouped_notifications_read') {
    if (isset($_POST['type']) && !empty($userID)) {
        $type = mysqli_real_escape_string($db, $_POST['type']);
        $postID = isset($_POST['postID']) ? (int)$_POST['postID'] : 0;
        $actorID = isset($_POST['actorID']) ? (int)$_POST['actorID'] : 0;
        $userID = (int)$userID;

        $query = "";

        if (in_array($type, ['commented', 'postLike']) && $postID > 0) {
            $query = "UPDATE i_user_notifications SET not_status = '1' WHERE not_own_iuid = '$userID' AND not_not_type = '$type' AND not_post_id = '$postID'";
        }
        else if ($type === 'live_now' && $actorID > 0) {
            $query = "UPDATE i_user_notifications SET not_status = '1' WHERE not_own_iuid = '$userID' AND not_not_type = 'live_now' AND not_iuid = '$actorID'";
        }

        if (!empty($query)) {
            mysqli_query($db, $query);
            echo "success";
        }
    }
    exit();
}
	
	
	
	
	
/* ==========================================================
   f = getPostForModal (for Slide-up Panel)
   ========================================================== */
/* ==========================================================
   f = getPostForModal (for Slide-up Panel)
   ========================================================== */

	
	

/* ==========================================================
   f = getPostLikers (for Slide-up Panel)
   ========================================================== */
if ($type == 'getPostLikers') {
    if (isset($_POST['post_id'])) {
        $postID = (int)$_POST['post_id'];
        $likersQuery = mysqli_query($db, "SELECT U.iuid, U.i_username, U.i_user_fullname FROM i_post_likes L INNER JOIN i_users U ON L.iuid_fk = U.iuid WHERE L.post_id_fk = '$postID' ORDER BY L.like_id DESC");
        
        echo '<div class="likers_list_header">'.$LANG['liked_by'].'</div>';
        if ($likersQuery && mysqli_num_rows($likersQuery) > 0) {
            echo '<div class="likers_list">';
            while ($row = mysqli_fetch_assoc($likersQuery)) {
                $likerAvatar = $iN->iN_UserAvatar($row['iuid'], $base_url);
                $likerFullName = $row['i_user_fullname'] ? $row['i_user_fullname'] : $row['i_username'];
                echo '<a href="'.$base_url.$row['i_username'].'" class="liker_item"><img src="'.$likerAvatar.'" class="liker_avatar"><span class="liker_name">'.iN_HelpSecure($likerFullName).'</span></a>';
            }
            echo '</div>';
        } else {
            echo '<div class="no_likers_found">'.$LANG['no_one_liked_yet'].'</div>';
        }
    }
    exit;
}

if ($type == 'topMenu') {
    include "../themes/$currentTheme/layouts/header/header_menu.php";
}
if ($type == 'topMessages') {
    $iN->iN_UpdateMessageNotificationStatus($userID);
    include "../themes/$currentTheme/layouts/header/messageNotifications.php";
}
if ($type == 'topNotifications') {
    // $iN->iN_UpdateNotificationStatus($userID); // DISABLED: Don't mark all as read on open
    include "../themes/$currentTheme/layouts/header/notifications.php";
}

	
	
	/* ==========================================================
 f = markNotificationRead
 Marks a SINGLE notification as read.
 ========================================================== */
if ($type == 'markNotificationRead' && isset($_POST['id'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $notificationID = (int)$_POST['id'];

    // Security Check: Make sure the notification belongs to the logged-in user
    if ($logedIn == '1' && $notificationID > 0) {
        if ($stmt = $db->prepare("UPDATE i_user_notifications SET not_status = '1' WHERE not_id = ? AND not_own_iuid = ?")) {
            $stmt->bind_param("ii", $notificationID, $userID);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Query failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    }
    exit;
}

/* ==========================================================
 f = markAllNotificationsRead
 Marks ALL unread notifications as read.
 ========================================================== */
if ($type == 'markAllNotificationsRead') {
    header('Content-Type: application/json; charset=UTF-8');
    if ($logedIn == '1') {
        if ($stmt = $db->prepare("UPDATE i_user_notifications SET not_status = '1' WHERE not_own_iuid = ? AND not_status = '0'")) {
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Query failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    }
    exit;
}
	
	
	
	/* ==========================================================
 f = loadMoreNotifications
 Paginates and loads more notifications for the main page. (CORRECTED VERSION)
 ========================================================== */
if ($type == 'loadMoreNotifications' && isset($_POST['last_id'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $lastID = (int)$_POST['last_id'];
    $limit = 10;
    $html = '';
    $newLastID = null;

    if ($logedIn == '1') {
        $moreNotifications = $iN->iN_GetNotifications($userID, $limit, $lastID);

        if ($moreNotifications) {
            foreach ($moreNotifications as $notData) {
                $notificationID = $notData['not_id'];
                $notificationStatus = $notData['not_status'];
                $notPostID = $notData['not_post_id'];
                $notificationTypeType = $notData['not_not_type'];
                $notCreator = $notData['not_iuid'];
                $notCreatorDetails = $iN->iN_GetUserDetails($notCreator);
                $notCreatorUserName = $notCreatorDetails['i_username'];
                $notCreatorUserFullName = ($fullnameorusername === 'no') ? $notCreatorUserName : $notCreatorDetails['i_user_fullname'];
                $notificationCreatorAvatar = $iN->iN_UserAvatar($notCreator, $base_url);
                $newLastID = $notificationID; // Keep track of the last ID in this batch

                // Rebuild the switch statement exactly like your page
                switch ($notificationTypeType) {
                    case 'live_now': $notText = $LANG['is_live_now']; $notIcon = $iN->iN_SelectedMenuIcon('115'); $notUrl = $base_url . 'live/' . $notCreatorUserName; break;
                    case 'commented': $notText = $LANG['commented_on_your_post']; $notIcon = $iN->iN_SelectedMenuIcon('20'); $notUrl = $base_url . 'post/' . $notPostID; break;
                    case 'postLike': $notText = $LANG['liked_your_post']; $notIcon = $iN->iN_SelectedMenuIcon('18'); $notUrl = $base_url . 'post/' . $notPostID; break;
                    case 'commentLike': $notText = $LANG['liked_your_comment']; $notIcon = $iN->iN_SelectedMenuIcon('18'); $notUrl = $base_url . 'post/' . $notPostID; break;
                    case 'follow': $notText = $LANG['is_now_following_your_profile']; $notIcon = $iN->iN_SelectedMenuIcon('66'); $notUrl = $base_url . $notCreatorUserName; break;
                    case 'subscribe': $notText = $LANG['is_subscribed_your_profile']; $notIcon = $iN->iN_SelectedMenuIcon('51'); $notUrl = $base_url . $notCreatorUserName; break;
                    case 'accepted_post': $notText = $LANG['accepted_post']; $notIcon = $iN->iN_SelectedMenuIcon('69'); $notUrl = $base_url . 'post/' . $notPostID; break;
                    case 'rejected_post': case 'declined_post': $notText = $LANG['rejected_post']; $notIcon = $iN->iN_SelectedMenuIcon('5'); $notUrl = $base_url . 'post/' . $notPostID; break;
                    case 'umentioned': $notText = $LANG['mentioned_u']; $notIcon = $iN->iN_SelectedMenuIcon('37'); $notUrl = $base_url . 'post/' . $notPostID; break;
                    case 'purchasedYourPost': $notText = $LANG['congratulations_you_sold']; $notIcon = $iN->iN_SelectedMenuIcon('175'); $notUrl = $base_url . 'post/' . $notPostID; break;
                    default: continue 2;
                }

                // Rebuild the HTML exactly like your original file
                $html .= '
                <div class="i_notification_wrpper mor hidNot_'.iN_HelpSecure($notificationID).' body_'.iN_HelpSecure($notificationID).' '.(($notificationStatus == '0') ? 'is-unread' : 'is-read').'" id="'.iN_HelpSecure($notificationID).'" data-last="'.iN_HelpSecure($notificationID).'">
                    <a href="'.iN_HelpSecure($notUrl).'">
                        <div class="i_notification_wrapper transition">
                            <div class="i_message_owner_avatar">
                                <div class="i_message_not_icon flex_ tabing">'.html_entity_decode($notIcon).'</div>
                                <div class="i_message_avatar"><img src="'.iN_HelpSecure($notificationCreatorAvatar).'" alt="'.iN_HelpSecure($notCreatorUserFullName).'" /></div>
                            </div>
                            <div class="i_message_info_container">
                                <div class="i_message_owner_name">'.iN_HelpSecure($notCreatorUserFullName).'</div>
                                <div class="i_message_i">'.iN_HelpSecure($notText).'</div>
                            </div>
                        </div>
                    </a>
                    <div class="i_message_setting msg_Set_'.iN_HelpSecure($notificationID).' msg_Set" id="'.iN_HelpSecure($notificationID).'">
                        <div class="i_message_set_icon">'.html_entity_decode($iN->iN_SelectedMenuIcon('16')).'</div>
                        <div class="i_message_set_container msg_Set msg_Set_'.iN_HelpSecure($notificationID).'">
                            <div class="i_post_menu_item_out transition hidNot" id="'.iN_HelpSecure($notificationID).'">'.html_entity_decode($iN->iN_SelectedMenuIcon('28')).' '.iN_HelpSecure($LANG['remove_this_notification']).'</div>
                            <div class="i_post_menu_item_out transition">'.html_entity_decode($iN->iN_SelectedMenuIcon('47')).' '.iN_HelpSecure($LANG['mark_as_read']).'</div>
                        </div>
                    </div>
                </div>';
            }
        }
    }
    // Check if there are more notifications after this batch to decide if the button should show again
    $hasMore = false;
    if($newLastID){
        $checkMore = $iN->iN_GetNotifications($userID, 1, $newLastID);
        if($checkMore){
            $hasMore = true;
        }
    }

    echo json_encode(['html' => $html, 'has_more' => $hasMore]);
    exit;
}
	
	
	
	
	
	

	if ($type == 'chooseLanguage') {
		include "../themes/$currentTheme/layouts/popup_alerts/chooseLanguage.php";
	}
	if ($type == "changeMyLang") {
		if (isset($_POST['id'])) {
			$langID = mysqli_real_escape_string($db, $_POST['id']);
			$updateUserLanguage = $iN->iN_UpdateLanguage($userID, $langID);
			if ($updateUserLanguage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	if ($type == 'topPoints') {
		$iN->iN_UpdateMessageNotificationStatus($userID);
		include "../themes/$currentTheme/layouts/header/points_box.php";
	}


	
	
	
	/* ===================== NOTIFICATIONS (list + paginate) ===================== */
/* ===================== NOTIFICATIONS (list + paginate + grouping) ===================== */
if ($type === 'notifications') {
  header('Content-Type: application/json; charset=UTF-8');

  $scope = (isset($_POST['scope']) && $_POST['scope'] === 'read') ? 'read' : 'unread';
  $want  = ($scope === 'unread') ? 0 : 1;
  $limit = isset($_POST['limit']) ? max(1, min(100, (int)$_POST['limit'])) : 50; // up to 100
  $last  = isset($_POST['last']) ? (int)$_POST['last'] : 0;

  notiLog("IN scope={$scope} want={$want} limit={$limit} last={$last} user={$userID} logged={$logedIn}");

  // fetch raw rows
  $sql = "SELECT not_id,not_status,not_post_id,not_comment_id,not_type,not_not_type,not_time,not_iuid
          FROM i_user_notifications
          WHERE not_own_iuid=? AND not_show_hide=0 AND not_status=?"
          . ($last>0 ? " AND not_id < ?" : "")
          . " ORDER BY not_id DESC LIMIT ?";
  notiLog("SQL=".$sql);

  $rows = []; $last_seen = 0;
  if ($stmt = $db->prepare($sql)) {
    if ($last>0) { $stmt->bind_param("iiii", $userID, $want, $last, $limit); }
    else         { $stmt->bind_param("iii",  $userID, $want, $limit); }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $rows[] = $r; $last_seen = (int)$r['not_id']; }
    $stmt->close();
  } else {
    notiLog("PREPARE_FAILED: ".mysqli_error($db));
  }
  notiLog("FETCHED rows=".count($rows)." last_seen=".$last_seen);

  // --- group likes on same post (postLike / commentLike) within same scope
  // key: det|pid
  $groupable = ['postLike','commentLike'];
  $groups = []; $flat = [];

  foreach ($rows as $r) {
    $det = $r['not_not_type'];
    $pid = (string)($r['not_post_id'] ?? '');
    if (in_array($det, $groupable, true) && $pid !== '') {
      $key = $det.'|'.$pid;
      if (!isset($groups[$key])) {
        $groups[$key] = [
          'ids'  => [],
          'actors' => [],
          'row'  => $r, // keep one as representative (most recent)
        ];
      }
      $groups[$key]['ids'][] = (int)$r['not_id'];
      $groups[$key]['actors'][] = (int)$r['not_iuid'];
      // keep the newest not_id at top
      if ((int)$r['not_id'] > (int)$groups[$key]['row']['not_id']) $groups[$key]['row'] = $r;
    } else {
      $flat[] = $r;
    }
  }

  // merge grouped entries back with flats, sorted by newest not_id
  foreach ($groups as $g) {
    $row = $g['row'];
    // stash grouping data for renderer
    $row['_g_ids'] = $g['ids'];
    $row['_g_uids'] = array_values(array_unique($g['actors']));
    $flat[] = $row;
  }
  usort($flat, function($a,$b){ return (int)$b['not_id'] <=> (int)$a['not_id']; });

  // trim to limit again after grouping (defensive)
  $flat = array_slice($flat, 0, $limit);

  // renderer
  $render = function($row) use ($base_url,$iN,$LANG,$fullnameorusername){
    $id   = (int)$row['not_id'];
    $read = ((int)$row['not_status'] === 1);
    $pid  = $row['not_post_id'];
    $det  = $row['not_not_type'];
    $ts   = $row['not_time'];
    $act  = (int)$row['not_iuid'];

    $u    = $iN->iN_GetUserDetails($act);
    $un   = $u['i_username'] ?? '';
    $uf   = ($fullnameorusername === 'no') ? $un : ($u['i_user_fullname'] ?? $un);
    $av   = $iN->iN_UserAvatar($act, $base_url);

    $text=''; $icon=''; $url=$base_url;
    switch ($det) {
      case 'live_now':            $text=$LANG['is_live_now'] ?? 'is live now';           $icon=$iN->iN_SelectedMenuIcon('115'); $url=$base_url.'live/'.$un; break;
      case 'commented':           $text=$LANG['commented_on_your_post'] ?? 'commented on your post'; $icon=$iN->iN_SelectedMenuIcon('20');  $url=$base_url.'post/'.$pid; break;
      case 'postLike':            $text=$LANG['liked_your_post'] ?? 'liked your post';   $icon=$iN->iN_SelectedMenuIcon('18');  $url=$base_url.'post/'.$pid; break;
      case 'commentLike':         $text=$LANG['liked_your_comment'] ?? 'liked your comment';          $icon=$iN->iN_SelectedMenuIcon('18');  $url=$base_url.'post/'.$pid; break;
      case 'follow':              $text=$LANG['is_now_following_your_profile'] ?? 'started following you'; $icon=$iN->iN_SelectedMenuIcon('66');  $url=$base_url.$un; break;
      case 'subscribe':           $text=$LANG['is_subscribed_your_profile'] ?? 'subscribed to you';        $icon=$iN->iN_SelectedMenuIcon('51');  $url=$base_url.$un; break;
      case 'accepted_post':       $text=$LANG['accepted_post'] ?? 'post accepted';      $icon=$iN->iN_SelectedMenuIcon('69');  $url=$base_url.'post/'.$pid; break;
      case 'rejected_post':
      case 'declined_post':       $text=$LANG['rejected_post'] ?? 'post rejected';      $icon=$iN->iN_SelectedMenuIcon('5');   $url=$base_url.'post/'.$pid; break;
      case 'umentioned':          $text=$LANG['mentioned_u'] ?? 'mentioned you';        $icon=$iN->iN_SelectedMenuIcon('37');  $url=$base_url.'post/'.$pid; break;
      case 'purchasedYourPost':   $text=$LANG['congratulations_you_sold'] ?? 'sold your post';            $icon=$iN->iN_SelectedMenuIcon('175'); $url=$base_url.'post/'.$pid; break;
      default:                    $text=$LANG['notification'] ?? 'Notification';         $icon=$iN->iN_SelectedMenuIcon('37');  $url=$base_url.$un; break;
    }

    // grouping text for likes
    if (($det==='postLike' || $det==='commentLike') && !empty($row['_g_uids']) && count($row['_g_uids'])>1) {
      $uids = $row['_g_uids'];
      $names = [];
      foreach (array_slice($uids,0,2) as $uid) {
        $ud = $iN->iN_GetUserDetails($uid);
        $n  = $ud['i_username'] ?? '';
        $f  = ($fullnameorusername === 'no') ? $n : ($ud['i_user_fullname'] ?? $n);
        $names[] = $f ?: $n;
      }
      $rest = max(0, count($uids)-2);
      if ($rest>0) {
        $who = implode(', ', $names).' +'.$rest;
      } else {
        $who = implode(' & ', $names);
      }
      $text = ($det==='postLike')
        ? ($who.' '.($LANG['liked_your_post'] ?? 'liked your post'))
        : ($who.' '.($LANG['liked_your_comment'] ?? 'liked your comment'));
      // show the first actor’s avatar
      $first = (int)$uids[0];
      $av = $iN->iN_UserAvatar($first, $base_url);
    }

    // time-ago
    if (function_exists('iN_TimeAgo')) { $ago = iN_TimeAgo($ts); }
    else { $d=time()-(int)$ts; $ago = ($d<60)?$d.'s':(($d<3600)?floor($d/60).'m':(($d<86400)?floor($d/3600).'h':floor($d/86400).'d')); }

    $cls = $read ? 'noti read' : 'noti unread';

    return '
    <div class="'.$cls.'" data-id="'.iN_HelpSecure($id).'">
      <a href="'.iN_HelpSecure($url).'" class="noti-link" data-id="'.iN_HelpSecure($id).'">
        <div class="noti-left">
          <span class="noti-icon">'.html_entity_decode($icon).'</span>
          <img class="noti-ava" src="'.iN_HelpSecure($av).'" alt="'.iN_HelpSecure($uf).'">
        </div>
        <div class="noti-body">
          <div class="noti-title"><span class="noti-name">'.iN_HelpSecure($uf).'</span><span class="noti-dot">•</span><span class="noti-time">'.iN_HelpSecure($ago).'</span></div>
          <div class="noti-text">'.iN_HelpSecure($text).'</div>
        </div>
      </a>
      <button class="noti-mark" data-id="'.iN_HelpSecure($id).'" aria-label="Mark as read" title="Mark as read">✓</button>
    </div>';
  };

  // build HTML
  $html = '';
  foreach ($flat as $r) { $html .= $render($r); }

  // has_more
  $has_more = false;
  if ($last_seen){
    if ($st2 = $db->prepare("SELECT 1 FROM i_user_notifications WHERE not_own_iuid=? AND not_show_hide=0 AND not_status=? AND not_id<? LIMIT 1")){
      $st2->bind_param("iii",$userID,$want,$last_seen);
      $st2->execute(); $st2->store_result();
      $has_more = $st2->num_rows > 0; $st2->close();
    }
  }

  // counts for badges
  $u=0;$r=0;
  if ($st3 = $db->prepare("SELECT
        SUM(CASE WHEN not_status=0 AND not_show_hide=0 THEN 1 ELSE 0 END) AS u,
        SUM(CASE WHEN not_status=1 AND not_show_hide=0 THEN 1 ELSE 0 END) AS r
        FROM i_user_notifications WHERE not_own_iuid=?")){
    $st3->bind_param("i",$userID); $st3->execute(); $st3->bind_result($u,$r); $st3->fetch(); $st3->close();
  }
  notiLog("COUNTS unread={$u} read={$r}");

  echo json_encode([
    'html'          => $html,
    'has_more'      => $has_more,
    'counts_unread' => (int)$u,
    'counts_read'   => (int)$r
  ]);
  exit;
}

/* ===================== NOTIFICATIONS: mark one ===================== */
if ($type === 'markNotiRead') {
  header('Content-Type: application/json; charset=UTF-8');
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $ok = false;
  if ($id > 0 && ($st = $db->prepare("UPDATE i_user_notifications SET not_status=1 WHERE not_id=? AND not_own_iuid=?"))) {
    $st->bind_param("ii",$id,$userID); $st->execute(); $ok = ($st->affected_rows >= 0); $st->close();
  }
  // return fresh counts
  $u=0;$r=0;
  if ($s = $db->prepare("SELECT SUM(CASE WHEN not_status=0 AND not_show_hide=0 THEN 1 ELSE 0 END),
                                SUM(CASE WHEN not_status=1 AND not_show_hide=0 THEN 1 ELSE 0 END)
                         FROM i_user_notifications WHERE not_own_iuid=?")){
    $s->bind_param("i",$userID); $s->execute(); $s->bind_result($u,$r); $s->fetch(); $s->close();
  }
  echo json_encode(['ok'=>$ok,'counts_unread'=>(int)$u,'counts_read'=>(int)$r]); exit;
}

/* ===================== NOTIFICATIONS: mark all ===================== */
if ($type === 'markAllNotiRead') {
  header('Content-Type: application/json; charset=UTF-8');
  $ok=false;
  if ($st = $db->prepare("UPDATE i_user_notifications SET not_status=1 WHERE not_own_iuid=? AND not_status=0")) {
    $st->bind_param("i",$userID); $st->execute(); $ok = ($st->affected_rows >= 0); $st->close();
  }
  // counts after the change
  $u=0;$r=0;
  if ($s = $db->prepare("SELECT SUM(CASE WHEN not_status=0 AND not_show_hide=0 THEN 1 ELSE 0 END),
                                SUM(CASE WHEN not_status=1 AND not_show_hide=0 THEN 1 ELSE 0 END)
                         FROM i_user_notifications WHERE not_own_iuid=?")){
    $s->bind_param("i",$userID); $s->execute(); $s->bind_result($u,$r); $s->fetch(); $s->close();
  }
  echo json_encode(['ok'=>$ok,'counts_unread'=>(int)$u,'counts_read'=>(int)$r]); exit;
}

	
	
	
if ($type == 'whoSee') {
    header('Content-Type: text/html; charset=UTF-8');

    // accept POST or GET




	
	

	
	
	
	
    $whoID = 0;
    if (isset($_POST['who']))       { $whoID = (int)$_POST['who']; }
    elseif (isset($_GET['who']))    { $whoID = (int)$_GET['who']; }
    else {
        // nothing provided — tell the caller clearly (and log)
        whoLog('ERROR: missing who');
        echo '400';  // or echo ''; if you prefer silence
        exit;
    }

    if ($whoID < 1 || $whoID > 4) {
        whoLog('ERROR: bad who='.$whoID);
        echo '400';
        exit;
    }

    $ok = $iN->iN_UpdateWhoCanSeePost($userID, $whoID);
    if (!$ok) { echo '403'; exit; }

    if ($whoID === 1)      { $html = '<div class="form_who_see_icon_set">'.$iN->iN_SelectedMenuIcon('50').'</div> '.$LANG['weveryone']; }
    elseif ($whoID === 2)  { $html = '<div class="form_who_see_icon_set">'.$iN->iN_SelectedMenuIcon('15').'</div> '.$LANG['wfollowers']; }
    elseif ($whoID === 3)  { $html = '<div class="form_who_see_icon_set">'.$iN->iN_SelectedMenuIcon('51').'</div> '.$LANG['wsubscribers']; }
    else                   { $html = '<div class="form_who_see_icon_set">'.$iN->iN_SelectedMenuIcon('9').'</div> '.$LANG['wpremium']; }

    echo $html;
    exit;
}

	
	if ($type == 'pw_premium') {
		echo '<div class="point_input_wrapper">
            <input type="text" name="point" class="pointIN" id="point" onkeypress="return event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)" placeholder="' . $LANG['write_points'] . '">
            <div class="box_not box_not_padding_left">' . $LANG['point_wanted'] . '</div>
        </div>';
	}
	/*Video Custom Tumbnail*/
	if($type == 'vTumbnail'){
		if(isset($_POST['id']) && !empty($_POST['id'])){
			$dataID = mysqli_real_escape_string($db, $_POST['id']);
			$checkIDExist = $iN->iN_CheckImageIDExist($dataID, $userID);
			if($checkIDExist){
				if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
					foreach ($_FILES['uploading']['name'] as $iname => $value) {
						$name = stripslashes($_FILES['uploading']['name'][$iname]);
						$size = $_FILES['uploading']['size'][$iname];
						$ext = getExtension($name);
						$ext = strtolower($ext);
						$valid_formats = explode(',', $availableVerificationFileExtensions);
						if (in_array($ext, $valid_formats)) {
							if (convert_to_mb($size) < $availableUploadFileSize) {
								$microtime = microtime();
								$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
								$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
								$getFilename = $UploadedFileName . "." . $ext;
								// Change the image ame
								$tmp = $_FILES['uploading']['tmp_name'][$iname];
								$mimeType = $_FILES['uploading']['type'][$iname];
								$d = date('Y-m-d');
								if (preg_match('/video\/*/', $mimeType)) {
									$fileTypeIs = 'video';
								} else if (preg_match('/image\/*/', $mimeType)) {
									$fileTypeIs = 'Image';
								}
								if (!file_exists($uploadFile . $d)) {
									$newFile = mkdir($uploadFile . $d, 0755);
								}
								if (!file_exists($xImages . $d)) {
									$newFile = mkdir($xImages . $d, 0755);
								}
								if (!file_exists($xVideos . $d)) {
									$newFile = mkdir($xVideos . $d, 0755);
								}
								if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
                                  $tumbFilePath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
								  $thePath = '../uploads/files/'.$d.'/'.$UploadedFileName . '.' . $ext;
								  if (file_exists($thePath)) {
									try {
										$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.'.$ext;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
								}else{
									exit('Upload Failed');
								}
									if ($s3Status == '1') {
										/*Upload Full video*/
										$theName = '../uploads/files/' . $d . '/' . $getFilename;
										$key = basename($theName);
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/xvideos/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											//unlink($uploadFile . $d . '/' . $UploadedFileName . '.'.$ext);
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
									}else if ($WasStatus == '1') {
										/*Upload Full video*/
										$theName = '../uploads/files/' . $d . '/' . $getFilename;
										$key = basename($theName);
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $WasBucket,
												'Key' => 'uploads/xvideos/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											//unlink($uploadFile . $d . '/' . $UploadedFileName . '.'.$ext);
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $WasBucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
									} else if($digitalOceanStatus == '1'){
									   $theName = '../uploads/files/' . $d . '/' . $getFilename;
									   /*IF DIGITALOCEAN AVAILABLE THEN*/
									   $my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									   $upload = $my_space->UploadFile($theName, "public");
									   /**/
									   $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;;
									   $my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									   $upload = $my_space->UploadFile($thevTumbnail, "public");
									   $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
									   $my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									   $upload = $my_space->UploadFile($thevTumbnail, "public");
									   if($upload){
										   $UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										   @unlink($uploadFile . $d . '/' . $UploadedFileName . '.'.$ext);
										}
									   /*/IF DIGITAOCEAN AVAILABLE THEN*/
									} else {
										$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;;
									}
									//watermark_image($tumbFilePath);
									$updateTumbData = $iN->iN_UpdateUploadedFiles($userID, $tumbFilePath, $dataID);
									if($updateTumbData){
										echo $UploadSourceUrl;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	if ($type == 'upload') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}else if (preg_match('/audio\/*/', $mimeType)) {
							$fileTypeIs = 'audio';
						}
						if($mimeType == 'application/octet-stream'){
							$fileTypeIs = 'video';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						$wVideos = $serverDocumentRoot . '/uploads/videos/';
						if (!file_exists($wVideos . $d)) {
							$newFile = mkdir($wVideos . $d, 0755);
						}
						if ($fileTypeIs == 'video' && $ffmpegStatus == '0' && !in_array($ext, $nonFfmpegAvailableVideoFormat)) {
							exit('303');
						}
						$uploadTumbnail = '';
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'video') {
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
								$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								if ($ffmpegStatus == '1') {
									$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$textVideoPath = '../uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4';

									$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									if ($ext == 'mpg') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mov') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec copy -acodec copy $convertUrl");
										$cmd = shell_exec("$ffmpegPath -ss 00:00:01.000 -i $convertUrl -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'wmv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'avi') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec h264 -acodec aac -strict -2 $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'webm') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -crf 1 -c:v libx264 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mpeg') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'flv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:a aac -strict -2 -b:a 128k -c:v libx264 -profile:v baseline $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'm4v') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mkv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -codec copy -strict -2 $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}else if($ext == '3gp'){
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}else{
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}

									$up_url = remove_http($base_url).$userName;
									$cmd = shell_exec("$ffmpegPath -ss 00:00:01 -i $convertUrl -c copy -t 00:00:04 $xVideoFirstPath 2>&1");
									if($drawTextStatus == '1'){
										$cmdText = shell_exec("$ffmpegPath -i $convertUrl -vf drawtext=fontfile=../src/droidsanschinese.ttf:text=$up_url:fontcolor=red:fontsize=18:x=10:y=H-th-10 $textVideoPath 2>&1");
									}else{
										$cmdText = shell_exec("$ffmpegPath -i $convertUrl -c:a copy -c:v libx264 -preset superfast -profile:v baseline $textVideoPath 2>&1");
									}
									if ($cmdText) {
									    //@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
										$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									}
									if (file_exists($videoTumbnailPath)) {
										try {
											$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.jpg';
											$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
											$image = new ImageFilter();
											$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");

										} catch (Exception $e) {
											echo '<span class="request_warning">' . $e->getMessage() . '</span>';
										}
									}else{
										exit('Please make sure that you have set the ffmpeg settings correct or that ffmpeg is installed on the server.');
									}
								} else {
									$cmd = '';
									$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
									$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
								}
								if ($ffmpegStatus == '1') {
    								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
    								$thePathM = '../' . $tumbnailPath;
									if($watermarkStatus == 'yes'){
    								  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}else if($LinkWatermarkStatus == 'yes'){
									  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}
								}
								/*CHECK AMAZON S3 AVAILABLE*/
								if ($s3Status == '1') {
									$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									/*Upload Full video*/
									$theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$key = basename($theName);
									if ($ffmpegStatus == '1') {
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($theName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$fullUploadedVideo = $result->get('ObjectURL');
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
								    }else{
										$theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									    $key = basename($theName);
									    try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($theName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$fullUploadedVideo = $result->get('ObjectURL');
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
									}
									if ($cmd) {
										/*Upload First x Second*/
										$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
										$key = basename($thexName);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/xvideos/' . $d . '/' . $key,
												'Body' => fopen($thexName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											/*rmdir($xVideos . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/xvideos/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
										@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
										@unlink($xVideos . $d . '/' . $UploadedFileName . '.jpg');
										@unlink($uploadFile . $d . '/' . $getFilename);
										@unlink($serverDocumentRoot . '/uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4');
									} else {
										$UploadSourceUrl = $base_url . 'uploads/web.png';
										/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
										$tumbnailPath = 'uploads/web.png';
										$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
									}

								}else if ($WasStatus == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false; // Show public access warning only once

                                    $theName = '../uploads/files/' . $d . '/' . $getFilename;
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/files/' . $d . '/' . $key,
                                                'Body'   => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/files/' . $d . '/' . $key,
                                                'Body'   => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        // Upload first X seconds clip
                                        $thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                        $key = basename($thexName);

                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body'   => fopen($thexName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to xvideos folder
                                        $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                        $key = basename($thevTumbnail);

                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body'   => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to original folder
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/files/' . $d . '/' . $key,
                                                'Body'   => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Remove temporary files
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($uploadFile . $d . '/' . $getFilename);
                                        @unlink($serverDocumentRoot . '/uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4');
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }
                                } else if ($digitalOceanStatus == '1') {
                                	$localPath1 = '../uploads/files/' . $d . '/' . $getFilename;
                                	$remoteKey1 = 'uploads/files/' . $d . '/' . $getFilename;

                                	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
                                	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                                	if ($cmd) {
                                		$localPath2 = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$remoteKey2 = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                                		$localPath3 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$remoteKey3 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                                		$localPath4 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$remoteKey4 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$upload = $my_space->UploadFile($localPath4, "public", $remoteKey4);
                                	}

                                	if ($upload) {
                                		if ($cmd) {
                                			$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey3;
                                			$pathXFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                			$pathXImageFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                			$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';

                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                			@unlink($pathXImageFile);
                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                			@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                		} else {
                                			$UploadSourceUrl = $base_url . 'img/web.png';
                                			$tumbnailPath = 'img/web.png';
                                		}
                                	}
                                } else {
									if ($cmd) {
										$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
									} else {
										$UploadSourceUrl = $base_url . 'uploads/web.png';
										$tumbnailPath = 'uploads/web.png';
										$pathFile = $pathFile;
										$pathXFile = 'uploads/web.png';
									}
								}
								$ext = 'mp4';
								/**/
							} else if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$tumbnails = $serverDocumentRoot . '/uploads/files/' . $d . '/';
								$pathFilea = '../uploads/files/' . $d . '/' . $getFilename;
								$width = 500;
								$newHeight = 500;
								$file = $pathFilea;
								//indicate the path and name for the new resized file
								$resizedFile = $tumbnails . $UploadedFileName . '_' . $userID . '.' . $ext;
								$resizedFileTwo = '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
								$tb = new ThumbAndCrop();
								$tb->openImg($pathFilea);
								$newHeight = $tb->getRightHeight(500);
								$tb->creaThumb(500, $newHeight);
								$tb->setThumbAsOriginal();
								$tb->creaThumb(500, $newHeight);
								$tb->saveThumb($resizedFileTwo);

								$thePathM = '../' . $pathFile;
								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
								if($ext != 'gif'){
									if($watermarkStatus == 'yes'){
										watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									  }else if($LinkWatermarkStatus == 'yes'){
										watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									  }
								}
								if (file_exists($thePathM)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
										/*rmdir($uploadFile . $d);*/
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$UploadSourceUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $tumbnailPath;
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext);
								}else if ($WasStatus == '1') {

                                        // Define upload paths and their corresponding S3 keys
                                        $paths = [
                                            '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext,
                                            '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext,
                                            '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext,
                                        ];

                                        $keys = [
                                            'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext,
                                            'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext,
                                            'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext,
                                        ];

                                        // Flag to ensure public access error message is displayed only once
                                        $publicAccessErrorShown = false;

                                        foreach ($paths as $index => $filePath) {

                                            // Check if the file exists before trying to upload
                                            if (!file_exists($filePath)) {
                                                echo "File not found: $filePath<br>";
                                                continue;
                                            }

                                            $key = $keys[$index];

                                            try {
                                                // Upload the file to Wasabi S3 bucket
                                                $result = $s3->putObject([
                                                    'Bucket' => $WasBucket,
                                                    'Key'    => $key,
                                                    'Body'   => fopen($filePath, 'r'),
                                                    'CacheControl' => 'max-age=3153600',
                                                    // 'ACL' => 'public-read' is intentionally excluded for compatibility
                                                ]);

                                                $UploadSourceUrl = $result->get('ObjectURL');

                                                // Remove the local file after successful upload
                                                if (str_contains($key, 'uploads/files/')) {
                                                    @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                                } elseif (str_contains($key, 'uploads/pixel/')) {
                                                    @unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
                                                }

                                            } catch (Aws\S3\Exception\S3Exception $e) {
                                                $awsMsg = $e->getAwsErrorMessage();
                                                $fullMsg = $e->getMessage();
                                                // Only show this message once
                                                if (
                                                    !$publicAccessErrorShown &&
                                                    str_contains($awsMsg, 'Public use of objects is not allowed by this account')
                                                ) {
                                                    echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files.<br>
                                                    This typically occurs with trial accounts.<br>
                                                    Please contact Wasabi support or remove 'public-read' access settings from your upload configuration.</div>";

                                                    $publicAccessErrorShown = true;
                                                }
                                            }
                                        }

                                        // Optional: estimated public URL (may not work if object is private)
                                        $UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $tumbnailPath;
                                } else if ($digitalOceanStatus == '1') {

                            	$localPath1 = '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
                            	$remoteKey1 = 'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;

                            	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
                            	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                            	$localPath2 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                            	$remoteKey2 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;

                            	$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                            	$localPath3 = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                            	$remoteKey3 = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;

                            	$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                            	@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
                            	@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                            	@unlink($uploadFile . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext);

                            	if ($upload) {
                            		$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey1;
                            	}

                            } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}else if($fileTypeIs == 'audio'){
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$tumbnailPath = 'src/audio.png';
								$pathXFile = 'src/audio.png';
								$thePathM = '../' . $pathFile;
								if ($s3Status == '1') {
                                    $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$UploadSourceUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $tumbnailPath;
								}else if ($WasStatus == '1') {
									$key = basename($tumbnailPath);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($tumbnailPath, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink('uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $tumbnailPath;
								} else if ($digitalOceanStatus == '1') {
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext);
									if ($upload) {
										$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $tumbnailPath;
									}
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								} else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							/**/
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							if ($fileTypeIs == 'video') {
								$uploadTumbnail = '
								<div class="v_custom_tumb">
									<label for="vTumb_' . $getUploadedFileID['upload_id'] . '">
										<div class="i_image_video_btn"><div class="pbtn pbtn_plus">' . $LANG['custom_tumbnail'] . '</div></div>
										<input type="file" id="vTumb_' . $getUploadedFileID['upload_id'] . '" class="imageorvideo cTumb editAds_file" data-id="' . $getUploadedFileID['upload_id'] . '" name="uploading[]" data-id="tupload">
									</label>
								</div>
								';
							}
							if ($fileTypeIs == 'video' || $fileTypeIs == 'Image') {
								/*AMAZON S3*/
								echo '
									<div class="i_uploaded_item iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '" id="' . $getUploadedFileID['upload_id'] . '">
									' . $postTypeIcon . '
									<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
										' . $iN->iN_SelectedMenuIcon('5') . '
									</div>
									<div class="i_uploaded_file" id="viTumb' . $getUploadedFileID['upload_id'] . '" style="background-image:url(' . $UploadSourceUrl . ');">
											<img class="i_file" id="viTumbi' . $getUploadedFileID['upload_id'] . '" src="' . $UploadSourceUrl . '" alt="tumbnail">
									</div>
									' . $uploadTumbnail . '
									</div>
								';
							}else {
                                 echo '<div id="playing_' . $getUploadedFileID['upload_id'] . '" class="green-audio-player"><div class="i_uploaded_item nonePoint iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '"  id="' . $getUploadedFileID['upload_id'] . '"></div>
								  <audio  crossorigin="" preload="none">
								     <source src="' . $UploadSourceUrl . '" type="audio/mp3" />
								  </audio>
								  <script>
									$(function() {
									new GreenAudioPlayer("#playing_' . $getUploadedFileID['upload_id'] . '", { stopOthersOnPlay: true, showTooltips: true, showDownloadButton: false, enableKeystrokes: true });
									});
								  </script>
								 </div>';
							}
						}else{
							echo 'Something Wrong';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}

	
	
	
	
	/* --- CREATOR FEES SAVE (final v2) --- */
if ($type === 'updateSubscriptionPayments') {
  header('Content-Type: application/json; charset=utf-8');
  while (ob_get_level()) { ob_end_clean(); }

  if (!function_exists('rq_log')) {
    function rq_log($m){ @file_put_contents(__DIR__.'/../fees_debug.log','['.date('c').'] '.$m."\n", FILE_APPEND); }
  }

  $uid = mysqli_real_escape_string($db, (string)$userID);

  $hasCol = function($col) use ($db){
    $col = mysqli_real_escape_string($db,$col);
    $res = @mysqli_query($db,"SHOW COLUMNS FROM `i_users` LIKE '$col'");
    return ($res && mysqli_num_rows($res) > 0);
  };

  // raw inputs
  $weekAmt = trim((string)($_POST['wSubWeekAmount']  ?? ''));
  $monAmt  = trim((string)($_POST['mSubMonthAmount'] ?? ''));
  $yrAmt   = trim((string)($_POST['mSubYearAmount']  ?? ''));
  $wPost   = trim((string)($_POST['wStatus'] ?? ''));
  $mPost   = trim((string)($_POST['mStatus'] ?? ''));
  $yPost   = trim((string)($_POST['yStatus'] ?? ''));

  // toggle ON if checkbox=1 OR amount > 0
  $weeklyOn  = ($wPost === '1') || (is_numeric($weekAmt) && (float)$weekAmt > 0);
  $monthlyOn = ($mPost === '1') || (is_numeric($monAmt)  && (float)$monAmt  > 0);
  $yearlyOn  = ($yPost === '1') || (is_numeric($yrAmt)   && (float)$yrAmt   > 0);

  // validate only ON plans
  $resp = ['weekly'=>'200','monthly'=>'200','yearly'=>'200'];
  $bad = false;
  if ($weeklyOn  && ($weekAmt === '' || !is_numeric($weekAmt) || (float)$weekAmt <= 0)) { $resp['weekly']  = '404'; $bad = true; }
  if ($monthlyOn && ($monAmt  === '' || !is_numeric($monAmt)  || (float)$monAmt  <= 0)) { $resp['monthly'] = '404'; $bad = true; }
  if ($yearlyOn  && ($yrAmt   === '' || !is_numeric($yrAmt)   || (float)$yrAmt   <= 0)) { $resp['yearly']  = '404'; $bad = true; }
  if ($bad) { echo json_encode($resp); exit; }

  // NEVER 1: 2 if any plan on, else 0
  $feesStatus = ($weeklyOn || $monthlyOn || $yearlyOn) ? 2 : 0;

  $escW = mysqli_real_escape_string($db, $weekAmt);
  $escM = mysqli_real_escape_string($db, $monAmt);
  $escY = mysqli_real_escape_string($db, $yrAmt);

  $wS = $weeklyOn  ? 1 : 0;
  $mS = $monthlyOn ? 1 : 0;
  $yS = $yearlyOn  ? 1 : 0;

// Build SET list only for columns that actually exist.
// IMPORTANT: only write amounts when a non-empty value was posted,
// so turning a plan OFF doesn't erase the previous price.
$sets = ["fees_status = $feesStatus"];
if ($hasCol('sub_week_status'))  $sets[] = "sub_week_status  = $wS";
if ($hasCol('sub_week_amount') && $weekly !== '')  $sets[] = "sub_week_amount  = '$escW'";
if ($hasCol('sub_month_status')) $sets[] = "sub_month_status = $mS";
if ($hasCol('sub_month_amount') && $monthly !== '') $sets[] = "sub_month_amount = '$escM'";
if ($hasCol('sub_year_status'))  $sets[] = "sub_year_status  = $yS";
if ($hasCol('sub_year_amount') && $yearly !== '')  $sets[] = "sub_year_amount  = '$escY'";

@mysqli_query($db, "UPDATE i_users SET ".implode(', ', $sets)." WHERE iuid = '$uid' LIMIT 1");

  rq_log("save uid=$uid -> fees=$feesStatus set=[".implode('|',$sets)."] raw w={$wS}:{$escW} m={$mS}:{$escM} y={$yS}:{$escY}");

  echo json_encode($resp);
  exit;
}

	
	
/* --- AUTO-HEAL: keep creator ON if any plan is ON --- */
if ($logedIn == '1') {
  $uid = mysqli_real_escape_string($db, (string)$userID);
  $has = function($col) use ($db) {
    $safe = mysqli_real_escape_string($db,$col);
    $res = @mysqli_query($db, "SHOW COLUMNS FROM i_users LIKE '$safe'");
    return ($res && mysqli_num_rows($res) > 0);
  };
  if ($has('sub_week_status') && $has('sub_month_status') && $has('sub_year_status')) {
    $q = @mysqli_query($db, "SELECT fees_status, IFNULL(sub_week_status,0) w, IFNULL(sub_month_status,0) m, IFNULL(sub_year_status,0) y FROM i_users WHERE iuid='$uid' LIMIT 1");
    if ($q && ($r = mysqli_fetch_assoc($q))) {
      if ( ((int)$r['w'] || (int)$r['m'] || (int)$r['y']) && (int)$r['fees_status'] !== 2 ) {
        @mysqli_query($db, "UPDATE i_users SET fees_status=2 WHERE iuid='$uid' LIMIT 1");
        if (!function_exists('rq_log')) {
          function rq_log($m){ @file_put_contents(_DIR_.'/../fees_debug.log','['.date('c').'] '.$m."\n", FILE_APPEND); }
        }
        rq_log("autoheal -> fees_status=2 (w={$r['w']} m={$r['m']} y={$r['y']})");
      }
    }
  }
}
	
	/* -------- TEMP HOTFIX: never leave fees_status at 1 -------- */
if ($logedIn == '1') {
  $uid = mysqli_real_escape_string($db, (string)$userID);
  @mysqli_query($db, "
    UPDATE i_users
       SET fees_status = CASE
           WHEN (IFNULL(sub_week_status,0) + IFNULL(sub_month_status,0) + IFNULL(sub_year_status,0)) > 0
                THEN 2
           ELSE 0
         END
     WHERE iuid = '$uid' AND fees_status = 1
  ");
}
/* -------- /TEMP HOTFIX -------- */

	
	
/* ==========================================================
   f = mentionFollowers
   Returns YOUR followers + subscribers for "@"
   Input : GET q   (prefix of username or fullname)
   Output: JSON [{id, username, name, avatar, profile}]
   ========================================================== */
if (isset($_GET['f']) && $_GET['f'] === 'mentionFollowers') {
  header('Content-Type: application/json; charset=UTF-8');

  if (empty($logedIn) || (string)$logedIn !== '1' || empty($userID)) {
    echo json_encode([]); exit;
  }

  // mysqli handle
  $dbc = isset($iN->db) && ($iN->db instanceof mysqli) ? $iN->db
       : (isset($db) && ($db instanceof mysqli) ? $db : null);
  if (!($dbc instanceof mysqli)) { echo json_encode([]); exit; }

  // Force connection charset/collation to avoid coercion surprises
  $dbc->set_charset('utf8mb4');
  $dbc->query("SET collation_connection = 'utf8mb4_general_ci'");

  $me   = (int)$userID;
  $term = isset($_GET['q']) ? trim($_GET['q']) : '';
  if ($term === '') { echo json_encode([]); exit; }

  // NOTE: we collate the *parameters* using CAST(... AS CHAR) COLLATE utf8mb4_general_ci
  $sql = "
    SELECT
      u.iuid,
      u.i_username,
      COALESCE(NULLIF(u.i_user_fullname, ''), u.i_username) AS full_name,
      u.user_verified_status
    FROM i_friends f
    JOIN i_users u ON u.iuid = f.fr_one
    WHERE f.fr_two = ?
      AND f.fr_status IN ('flwr', 'subscriber', '1', '2')
      AND (
        u.i_username LIKE CONCAT(CAST(? AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci, '%')
        OR
        u.i_user_fullname LIKE CONCAT(CAST(? AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci, '%')
      )
    ORDER BY u.user_verified_status DESC, u.i_username ASC
    LIMIT 12
  ";

  $out = [];
  if ($stmt = $dbc->prepare($sql)) {
    $stmt->bind_param('iss', $me, $term, $term);
    $stmt->execute();

    if (method_exists($stmt, 'get_result')) {
      if ($res = $stmt->get_result()) {
        while ($r = $res->fetch_assoc()) {
          $out[] = [
            'id'       => (int)$r['iuid'],
            'username' => $r['i_username'],
            'name'     => $r['full_name'] ?: $r['i_username'],
            'avatar'   => $iN->iN_UserAvatar((int)$r['iuid'], $base_url),
            'profile'  => $base_url . $r['i_username']
          ];
        }
      }
    } else {
      $stmt->store_result();
      $stmt->bind_result($iuid, $i_username, $full_name, $verified);
      while ($stmt->fetch()) {
        $uid = (int)$iuid;
        $uname = $i_username;
        $out[] = [
          'id'       => $uid,
          'username' => $uname,
          'name'     => $full_name ?: $uname,
          'avatar'   => $iN->iN_UserAvatar($uid, $base_url),
          'profile'  => $base_url . $uname
        ];
      }
    }
    $stmt->close();
  }

  echo json_encode($out, JSON_UNESCAPED_SLASHES); exit;
}



	
	
	

	

	/*DELETE UPLOADED FILE BEFORE PUBLISH*/
	if ($type == 'delete_file') {
		if (isset($_POST['file'])) {
			$fileID = mysqli_real_escape_string($db, $_POST['file']);
			$deleteFileFromData = $iN->iN_DeleteFile($userID, $fileID);
			if ($deleteFileFromData) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	/*INSERT NEW POST*/
	if ($type == 'newPost') {
		if (isset($_POST['txt']) && isset($_POST['file'])) {
			$text = mysqli_real_escape_string($db, $_POST['txt']);
			$file = mysqli_real_escape_string($db, $_POST['file']);
			if (empty($iN->iN_Secure($text)) && empty($file)) {
				echo '200';
				exit();
			}
			if($file != '' && !empty($file) && $file != 'undefined'){
				$trimValue = rtrim($file, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach($explodeFiles as $explodeFile){
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
					if(isset($uploadedFileID)){
                       $updateUploadStatus = $iN->iN_UpdateUploadStatus($uploadedFileID);
					}
					if(empty($uploadedFileID)){
					   exit('204');
					}
				}
			}
			if (!empty($text)) {
				$slug = $iN->url_slugies(mb_substr($text, 0, 55, "utf-8"));
			} else {
				$slug = $iN->random_code(8);
			}
			if ($userWhoCanSeePost == '4') {
				$premiumPointAmount = mysqli_real_escape_string($db, $_POST['point']);
				if ($premiumPointAmount == '' || !isset($premiumPointAmount) || empty($premiumPointAmount)) {
					exit('201');
				}
				$number = preg_match("/^(?!\.)(?!.*\.$)(?!.*?\.\.)[0-9.]+$/", $premiumPointAmount, $m);

				$premiumPointAmount = isset($m[0]) ? $m[0] : NULL;
				if(!$premiumPointAmount){
                   exit('201');
				}
			} else { $premiumPointAmount = '';}
			$hashT = $iN->iN_hashtag($text);
			$postFromData = $iN->iN_InsertNewPost($userID, $iN->iN_Secure($text), $slug, $file, $userWhoCanSeePost, $iN->url_Hash($hashT), $iN->iN_Secure($premiumPointAmount), $autoApprovePostStatus);

			if ($postFromData) {
				$userPostID = $postFromData['post_id'];
				$userPostOwnerID = $postFromData['post_owner_id'];
				if($ataNewPostPointAmount && $ataNewPostPointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPoint($userID,$userPostID,$ataNewPostPointAmount);
				}
				$userPostText = isset($postFromData['post_text']) ? $postFromData['post_text'] : NULL;
                if($userPostText){
                   $iN->iN_InsertMentionedUsersForPost($userID, $userPostText, $userPostID, $userName,$userPostOwnerID);
				}
				$userPostFile = $postFromData['post_file'];
				$userPostCreatedTime = $postFromData['post_created_time'];
				$crTime = date('Y-m-d H:i:s', $userPostCreatedTime);
				$userPostWhoCanSee = $postFromData['who_can_see'];
				if($autoApprovePostStatus == 'yes' && $userPostWhoCanSee == '4'){
					$approveNot = $LANG['congratulations_approved'];
					$postApprover = $iN->iN_GetAdminUserID();
					$approveUpdate = $iN->iN_UpdateApprovePostStatusAuto($postApprover, $iN->iN_Secure($userPostID), $iN->iN_Secure($userPostOwnerID), $iN->iN_Secure($approveNot));
				}
				$planIcon  = NULL;
				$checkPostBoosted=  NULL;
				$userPostWantStatus = $postFromData['post_want_status'];
				$userPostWantedCredit = $postFromData['post_wanted_credit'];
				$userPostStatus = $postFromData['post_status'];
				$userPostOwnerUsername = $postFromData['i_username'];
				$userPostOwnerUserFullName = $postFromData['i_user_fullname'];
				$userPostOwnerUserGender = $postFromData['user_gender'];
				$userPostHashTags = isset($postFromData['hashtags']) ? $postFromData['hashtags'] : NULL;
				$getUserPaymentMethodStatus = isset($postFromData['payout_method']) ? $postFromData['payout_method'] : NULL;
				$userPostCommentAvailableStatus = $postFromData['comment_status'];
				$userPostOwnerUserLastLogin = $postFromData['last_login_time'];
                $userProfileCategory = isset($postFromData['profile_category']) ? $postFromData['profile_category'] : NULL;
				$lastSeen = date("c", $userPostOwnerUserLastLogin);
            	$OnlineStatus = date("c", time());
                $oStatus = time() - 35;
                if ($userPostOwnerUserLastLogin > $oStatus) {
                  $timeStatus = '<div class="userIsOnline flex_ tabing">'.$LANG['online'].'</div>';
                } else {
                  $timeStatus = '<div class="userIsOffline flex_ tabing">'.$LANG['offline'].'</div>';
                }
				$userPostPinStatus = $postFromData['post_pined'];
				$slugUrl = $base_url . 'post/' . $postFromData['url_slug'] . '_' . $userPostID;
				$userPostSharedID = isset($postFromData['shared_post_id']) ? $postFromData['shared_post_id'] : NULL;
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				$userPostUserVerifiedStatus = $postFromData['user_verified_status'];
				$userProfileFrame = isset($postFromData['user_frame']) ? $postFromData['user_frame'] : NULL;
				if ($userPostOwnerUserGender == 'male') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($userPostOwnerUserGender == 'female') {
					$publisherGender = '<div class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($userPostOwnerUserGender == 'couple') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$userVerifiedStatus = '';
				if ($userPostUserVerifiedStatus == '1') {
					$userVerifiedStatus = '<div class="i_plus_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$profileCategory = $pCt = $profileCategoryLink = '';
                if($userProfileCategory && $userPostUserVerifiedStatus == '1'){
                    $profileCategory = $userProfileCategory;
                    if(isset($PROFILE_CATEGORIES[$userProfileCategory])){
                        $pCt = isset($PROFILE_CATEGORIES[$userProfileCategory]) ? $PROFILE_CATEGORIES[$userProfileCategory] : NULL;
                    }else if(isset($PROFILE_SUBCATEGORIES[$userProfileCategory])){
                        $pCt = isset($PROFILE_SUBCATEGORIES[$userProfileCategory]) ? $PROFILE_SUBCATEGORIES[$userProfileCategory] : NULL;
                    }
                    $profileCategoryLink = '<a class="i_p_categoryp flex_ tabing_non_justify" href="'.$base_url.'creators?creator='.$userProfileCategory.'">'.$iN->iN_SelectedMenuIcon('65').$pCt.'</a>- ';
                }
				$onlySubs = '';
				$premiumPost = '';
                if($userPostWhoCanSee == '1'){
                   $onlySubs = '';
                   $premiumPost = '';
                   $subPostTop = '';
                   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('50').'</div>';
                }else if($userPostWhoCanSee == '2'){
                   $subPostTop = '';
                   $premiumPost = '';
                   $wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
                   $onlySubs = '<div class="com_min_height"></div><div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('15').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']).'</div></div></div>';
                }else if($userPostWhoCanSee == '3'){
                   $subPostTop = 'extensionPost';
                   $premiumPost = '<div class="premiumIcon flex_ justify-content-align-items-center">'.$iN->iN_SelectedMenuIcon('40').$LANG['l_premium'].'</div>';
                   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('51').'</div>';
                   $onlySubs = '<div class="com_min_height"></div><div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('56').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']).'</div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
                }else if($userPostWhoCanSee == '4'){
                  $subPostTop = 'extensionPost';
                  $premiumPost = '<div class="premiumIcon flex_ justify-content-align-items-center">'.$iN->iN_SelectedMenuIcon('40').$LANG['l_premium'].'</div>';
                  $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
                  $onlySubs = '<div class="com_min_height"></div><div class="onlyPremium onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="'.$userPostID.'">'.preg_replace( '/{.*?}/', $userPostWantedCredit, $LANG['post_credit']).'</div><div class="buythistext prcsPost" id="'.$userPostID.'">'.$LANG['purchase_post'].'</div></div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
                }
				$postStyle = '';
				if (empty($userPostText)) {
					$postStyle = 'nonePoint';
				}
				/*Comment*/
				$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
				$c = '';
				$TotallyPostComment = '';
				if ($c) {
					if ($getUserComments > 0) {
						$CountTheUniqComment = count($CountUniqPostCommentArray);
						$SecondUniqComment = $CountTheUniqComment - 5;
						if ($CountTheUniqComment > 5) {
							$getUserComments = $iN->iN_GetPostComments($userPostID, 5);
						}
					}
				}
				if ($logedIn == 0) {
					$getFriendStatusBetweenTwoUser = '1';
					$checkPostLikedBefore = '';
					$checkUserPurchasedThisPost = '0';
				} else {
					$getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userPostOwnerID);
					$checkPostLikedBefore = $iN->iN_CheckPostLikedBefore($userID, $userPostID);
					$checkUserPurchasedThisPost = $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID);
				}
				if ($checkPostLikedBefore) {
					$likeIcon = $iN->iN_SelectedMenuIcon('18');
					$likeClass = 'in_unlike';
				} else {
					$likeIcon = $iN->iN_SelectedMenuIcon('17');
					$likeClass = 'in_like';
				}
				if ($userPostCommentAvailableStatus == '1') {
					$commentStatusText = $LANG['disable_comment'];
				} else {
					$commentStatusText = $LANG['enable_comments'];
				}
				$pPinStatus = '';
				$pPinStatusBtn = $iN->iN_SelectedMenuIcon('29') . $LANG['pin_on_my_profile'];
				if ($userPostPinStatus == '1') {
					$pPinStatus = '<div class="i_pined_post" id="i_pined_post_' . $userPostID . '">' . $iN->iN_SelectedMenuIcon('62') . '</div>';
					$pPinStatusBtn = $iN->iN_SelectedMenuIcon('29') . $LANG['post_pined_on_your_profile'];
				}
				$pSaveStatusBtn = $iN->iN_SelectedMenuIcon('22');
				if ($iN->iN_CheckPostSavedBefore($userID, $userPostID) == '1') {
					$pSaveStatusBtn = $iN->iN_SelectedMenuIcon('63');
				}
				$likeSum = $iN->iN_TotalPostLiked($userPostID);
				if ($likeSum > '0') {
					$likeSum = $likeSum;
				} else {
					$likeSum = '';
				}
				$waitingApprove = '';
				if ($userPostStatus == '2') {
					$waitingApprove = '<div class="waiting_approve flex_">' . $iN->iN_SelectedMenuIcon('10') . $LANG['waiting_for_approve'] . '</div>';
					if ($logedIn == 0) {
						echo '<div class="i_post_body nonePoint body_' . $userPostID . '" id="' . $userPostID . '" data-last="' . $userPostID . '" ></div>';
					} else {
						if ($userID == $userPostOwnerID) {
							if (empty($userPostFile)) {
								include "../themes/$currentTheme/layouts/posts/textPost.php";
							} else {
								include "../themes/$currentTheme/layouts/posts/ImagePost.php";
							}
						} else {
							echo '<div class="i_post_body nonePoint body_' . $userPostID . '" id="' . $userPostID . '" data-last="' . $userPostID . '"></div>';
						}
					}
				} else {
					if (empty($userPostFile)) {
						include "../themes/$currentTheme/layouts/posts/textPost.php";
					} else {
						include "../themes/$currentTheme/layouts/posts/ImagePost.php";
					}
				}
			}
		} else {
			echo '15';
		}
	}
	if ($type == 'p_like') {
		if (isset($_POST['post'])) {
			$postID = mysqli_real_escape_string($db, $_POST['post']);
			$likePost = $iN->iN_LikePost($userID, $postID);
			$status = 'in_like';
			$pLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePost) {
				$status = 'in_unlike';
				$pLike = $iN->iN_SelectedMenuIcon('18');
				if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewPostLikePointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPostLikePoint($userID,$postID,$ataNewPostLikePointAmount);
				}
			}
			if($status == 'in_like'){
				if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewPostLikePointSatus == 'yes'){
					$iN->iN_RemovePointPostLikeIfExist($userID,$postID,$ataNewPostLikePointAmount);
				}
			}
			$likeSum = $iN->iN_TotalPostLiked($postID);
			if ($likeSum == 0) {
				$likeSum = '';
			} else {
				$likeSum = $likeSum;
			}
			$data = array(
				'status' => $status,
				'like' => $pLike,
				'likeCount' => $likeSum,
			);
			$iN->iN_insertPostLikeNotification($userID, $postID);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($postID);
			$likedPostOwnerID = $GetPostOwnerIDFromPostDetails['post_owner_id'];
			$uData = $iN->iN_GetUserDetails($likedPostOwnerID);
			$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
			$lUsername = $uData['i_username'];
			$lUserFullName = $uData['i_user_fullname'];
			$emailNotificationStatus = $uData['email_notification_status'];
			$notQualifyDocument = $LANG['not_qualify_document'];
			$slugUrl = $base_url . 'post/' . $GetPostOwnerIDFromPostDetails['url_slug'] . '_' . $postID;
			if ($emailSendStatus == '1' && $userID != $likedPostOwnerID && $emailNotificationStatus == '1' && $status == 'in_unlike') {
				if ($smtpOrMail == 'mail') {
					$mail->IsMail();
				} else if ($smtpOrMail == 'smtp') {
					$mail->isSMTP();
					$mail->Host = $smtpHost; // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true;
					$mail->Username = $smtpUserName; // SMTP username
					$mail->Password = $smtpPassword; // SMTP password
					$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
					$mail->Port = $smtpPort;
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				} else {
					return false;
				}
				$instagramIcon = $iN->iN_SelectedMenuIcon('88');
				$facebookIcon = $iN->iN_SelectedMenuIcon('90');
				$twitterIcon = $iN->iN_SelectedMenuIcon('34');
				$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
				$someoneLikedYourPost = $iN->iN_Secure($LANG['someone_liked_yourpost']);
				$clickGoPost = $iN->iN_Secure($LANG['click_go_post']);
				$likedYourPost = $iN->iN_Secure($LANG['liked_your_post']);
				include_once '../includes/mailTemplates/postLikeEmailTemplate.php';
				$body = $bodyPostLikeEmail;
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['someone_liked_yourpost']);
				$mail->CharSet = 'utf-8';
				$mail->Body    = $body;
				if ($mail->send()) {
					$mail->ClearAddresses();
					return true;
				}
			}
		}
	}
	if ($type == 'p_share') {
		if (isset($_POST['sp'])) {
			$postID = mysqli_real_escape_string($db, $_POST['sp']);
			$checkPostIDExist = $iN->iN_CheckPostIDExist($postID);
			if ($checkPostIDExist == '1') {
				$postFromData = $iN->iN_GetAllPostDetails($postID);
				$userPostID = $postFromData['post_id'];
				$userPostOwnerID = $postFromData['post_owner_id'];
				$userPostText = isset($postFromData['post_text']) ? $postFromData['post_text'] : NULL;
				$userPostFile = $postFromData['post_file'];
				$userPostCreatedTime = $postFromData['post_created_time'];
				$crTime = date('Y-m-d H:i:s', $userPostCreatedTime);
				$userPostWhoCanSee = $postFromData['who_can_see'];
				$userPostWantStatus = $postFromData['post_want_status'];
				$userPostWantedCredit = $postFromData['post_wanted_credit'];
				$userPostStatus = $postFromData['post_status'];
				$userPostOwnerUsername = $postFromData['i_username'];
				$userPostOwnerUserFullName = $postFromData['i_user_fullname'];
				if($fullnameorusername == 'no'){
					$userPostOwnerUserFullName = $userPostOwnerUsername;
				}
				$userPostOwnerUserGender = $postFromData['user_gender'];
				$userPostCommentAvailableStatus = $postFromData['comment_status'];
				$userPostOwnerUserLastLogin = $postFromData['last_login_time'];
				$userPostHashTags = isset($postFromData['hashtags']) ? $postFromData['hashtags'] : NULL;
				$userPostSharedID = isset($postFromData['shared_post_id']) ? $postFromData['shared_post_id'] : NULL;
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				$userPostUserVerifiedStatus = $postFromData['user_verified_status'];
				if ($userPostOwnerUserGender == 'male') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($userPostOwnerUserGender == 'female') {
					$publisherGender = '<div class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($userPostOwnerUserGender == 'couple') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$userVerifiedStatus = '';
				if ($userPostUserVerifiedStatus == '1') {
					$userVerifiedStatus = '<div class="i_plus_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$onlySubs = '';
				if($userPostWhoCanSee == '1'){
					$onlySubs = '';
					$subPostTop = '';
					$wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('50').'</div>';
				 }else if($userPostWhoCanSee == '2'){
					$subPostTop = '';
					$wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
					$onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('15').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']).'</div></div></div>';
				 }else if($userPostWhoCanSee == '3'){
					$subPostTop = 'extensionPost';
					$wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('51').'</div>';
					$onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('56').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']).'</div></div></div>';
				 }else if($userPostWhoCanSee == '4'){
				   $subPostTop = 'extensionPost';
				   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
				   $onlySubs = '<div class="onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="'.$userPostID.'">'.preg_replace( '/{.*?}/', $userPostWantedCredit, $LANG['post_credit']).'</div><div class="buythistext prcsPost" id="'.$userPostID.'">'.$LANG['purchase_post'].'</div></div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
				 }
				$likeSum = $iN->iN_TotalPostLiked($userPostID);
				if ($likeSum > '0') {
					$likeSum = $likeSum;
				} else {
					$likeSum = '1';
				}
				$checkUserPurchasedThisPost = $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID);
				/*Comment*/
				$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
				$c = '';
				$TotallyPostComment = '';
				if ($c) {
					if ($getUserComments > 0) {
						$CountTheUniqComment = count($CountUniqPostCommentArray);
						$SecondUniqComment = $CountTheUniqComment - 5;
						if ($CountTheUniqComment > 5) {
							$getUserComments = $iN->iN_GetPostComments($userPostID, 5);
						}
					}
				}
				if ($logedIn == 0) {
					$getFriendStatusBetweenTwoUser = '1';
					$checkPostLikedBefore = '';
				} else {
					$getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userPostOwnerID);
					$checkPostLikedBefore = $iN->iN_CheckPostLikedBefore($userID, $userPostID);
				}
				include "../themes/$currentTheme/layouts/posts/sharePost.php";
			} else {
				echo '404';
			}
		}
	}
	/*Insert Re-Share Post*/
	if ($type == 'p_rshare') {
		if (isset($_POST['sp']) && isset($_POST['pt'])) {
			$reSharePostID = mysqli_real_escape_string($db, $_POST['sp']);
			$reSharePostNewText = mysqli_real_escape_string($db, $_POST['pt']);
			$insertReShare = $iN->iN_ReShare_Post($userID, $reSharePostID, $iN->iN_Secure($reSharePostNewText));
			if ($insertReShare) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	/*Show PopUps*/
	if ($type == 'ialert') {
		if (isset($_POST['al'])) {
			$alertType = mysqli_real_escape_string($db, $_POST['al']);
			include "../themes/$currentTheme/layouts/popup_alerts/popup_alerts.php";
		}
	}
	/*Show Who Can See Settings In PopUp*/
	if ($type == 'wcs') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$whoSee = $iN->iN_GetAllPostDetails($postID);
			if ($whoSee) {
				$whoCSee = $whoSee['who_can_see'];
				include "../themes/$currentTheme/layouts/posts/whoCanSee.php";
			}
		}
	}
	/*Show Who Can See Settings In PopUp*/
	if ($type == 'whcStory') {
		$checkUserIDExist = $iN->iN_CheckUserExist($userID);
		if ($checkUserIDExist) {
		    include "../themes/$currentTheme/layouts/popup_alerts/chooseWhichStory.php";
		}
	}
	/*Update Post Who Can See Status*/
	if ($type == 'uwcs') {
		if (isset($_POST['wci']) && in_array($_POST['wci'], $whoCanSeeArrays) && isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$WhoCS = mysqli_real_escape_string($db, $_POST['wci']);
			$updatePostWhoCanSeeStatus = $iN->iN_UpdatePostWhoCanSee($userID, $postID, $WhoCS);
			if ($updatePostWhoCanSeeStatus) {
				if ($WhoCS == 1) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('50');
				} else if ($WhoCS == 2) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('15');
				} else if ($WhoCS == 3) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('51');
				}
				echo html_entity_decode($UpdatedWhoCanSee);
			} else {
				echo '404';
			}
		}
	}
	/*Show Edit Post In PopUp*/
	if ($type == 'c_editPost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$getPData = $iN->iN_GetAllPostDetails($postID);
			if ($getPData) {
				$posText = isset($getPData['post_text']) ? $getPData['post_text'] : NULL;
				include "../themes/$currentTheme/layouts/posts/editPost.php";
			} else {
				echo '404';
			}
		}
	}
	/*Save Edited Post*/
	if ($type == 'editS') {
		if (isset($_POST['id']) && isset($_POST['text'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$editedText = mysqli_real_escape_string($db, $_POST['text']);
			$editedTextTwo = mysqli_real_escape_string($db, $_POST['text']);
			if (empty($editedText)) {
				$status = 'no';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			$editSlug = $iN->url_slugies($editedText);
			$hashT = $iN->iN_hashtag($editedText);
			$saveEditedPost = $iN->iN_UpdatePost($userID, $postID, $editedTextTwo, $iN->url_Hash($editedText), $editSlug);
			if ($saveEditedPost) {
				$getNewPostFromData = $iN->iN_GetAllPostDetails($postID);
				$status = '200';
				$data = array(
					'status' => $status,
					'text' => $iN->sanitize_output($getNewPostFromData['post_text'], $base_url),
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			} else {
				$status = '404';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		}
	}
	/*Delete Post Call AlertBox*/
	if ($type == 'ddelPost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteAlert.php";
		}
	}
	/*Delete Post Call AlertBox*/
	if ($type == 'finishLiveStreaming') {
		include "../themes/$currentTheme/layouts/popup_alerts/closeLiveStreaming.php";
	}
	/*Delete Conversation Call AlertBox*/
	if ($type == 'ddelConv') {
		if (isset($_POST['id'])) {
			$conversationID = mysqli_real_escape_string($db, $_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteConversationAlert.php";
		}
	}
	/*Delete Message Call AlertBox*/
	if ($type == 'ddelMesage') {
		if (isset($_POST['id'])) {
			$messageID = mysqli_real_escape_string($db, $_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteMessageAlert.php";
		}
	}
	/*Delete Story From Database*/
	if($type == 'deleteStorie'){
       if(isset($_POST['id'])){
          $storieID = mysqli_real_escape_string($db, $_POST['id']);
		  $checkStorieIDExist = $iN->iN_CheckStorieIDExist($userID, $storieID);
		  if($checkStorieIDExist){
              $sData = $iN->iN_GetUploadedStoriesData($userID, $storieID);
			  $uploadedFileID = $sData['s_id'];
			  $uploadedFilePath = $sData['uploaded_file_path'];
			  $uploadedTumbnailFilePath = $sData['upload_tumbnail_file_path'];
			  $uploadedFilePathX = $sData['uploaded_x_file_path'];
			  $uploadedStoryType = $sData['story_type'];
			  if($uploadedStoryType != 'textStory'){
				if($uploadedFileID && $digitalOceanStatus == '1'){
					$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$my_space->DeleteObject($uploadedFilePath);

					$space_two = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$space_two->DeleteObject($uploadedFilePathX);

					$space_tree = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$space_tree->DeleteObject($uploadedTumbnailFilePath);
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
					if($query){
						echo '200';
					}else{
						echo '404';
					}
				  } else if($uploadedFileID && $s3Status == '1'){
					$s3->deleteObject([
						'Bucket' => $s3Bucket,
						'Key'    => $uploadedFilePath,
					]);
					$s3->deleteObject([
						'Bucket' => $s3Bucket,
						'Key'    => $uploadedFilePathX,
					]);
					$s3->deleteObject([
						'Bucket' => $s3Bucket,
						'Key'    => $uploadedTumbnailFilePath,
					]);
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
					if($query){
						echo '200';
					}else{
						echo '404';
					}
				  }else if($uploadedFileID && $WasStatus == '1'){
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedFilePath,
					]);
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedFilePathX,
					]);
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedTumbnailFilePath,
					]);
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
					if($query){
						echo '200';
					}else{
						echo '404';
					}
				  }else{
					@unlink('../' . $uploadedFilePath);
					@unlink('../' . $uploadedFilePathX);
					@unlink('../' . $uploadedTumbnailFilePath);
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
					if($query){
						echo '200';
					}else{
						echo '404';
					}
				  }
			  }else{
				$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
				if($query){
					echo '200';
				}else{
					echo '404';
				}
			  }

		  }
	   }
	}
	/*Delete Post From Database*/
	if ($type == 'deletePost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			if(!empty($postID) && $digitalOceanStatus == '1'){
				$getPostFileIDs = $iN->iN_GetAllPostDetails($postID);
				$postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
				$trimValue = rtrim($postFileIDs, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach ($explodeFiles as $explodeFile) {
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					if($theFileID){
						$uploadedFileID = $theFileID['upload_id'];
						$uploadedFilePath = $theFileID['uploaded_file_path'];
						$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
						$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
						$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
						$my_space->DeleteObject($uploadedFilePath);

						$space_two = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
						$space_two->DeleteObject($uploadedFilePathX);

						$space_tree = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
						$space_tree->DeleteObject($uploadedTumbnailFilePath);
						mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
					}
				}
				$deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorage($userID, $postID);
				if($deleteStoragePost){
					if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
					echo '200';
				}else{
					echo '404';
				}
			}else if(!empty($postID) && $s3Status == '1'){
				$getPostFileIDs = $iN->iN_GetAllPostDetails($postID);
				$postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
				$trimValue = rtrim($postFileIDs, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach ($explodeFiles as $explodeFile) {
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					if($theFileID){
						$uploadedFileID = $theFileID['upload_id'];
						$uploadedFilePath = $theFileID['uploaded_file_path'];
						$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
						$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
						$s3->deleteObject([
							'Bucket' => $s3Bucket,
							'Key'    => $uploadedFilePath,
						]);
						$s3->deleteObject([
							'Bucket' => $s3Bucket,
							'Key'    => $uploadedFilePathX,
						]);
						$s3->deleteObject([
							'Bucket' => $s3Bucket,
							'Key'    => $uploadedTumbnailFilePath,
						]);
						mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
					}
				}
				$deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorage($userID, $postID);
				if($deleteStoragePost){
				    if($ataNewPostPointSatus == 'yes'){
				        $iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);
				    }
				    if ($iN->iN_CheckIsAdmin($userID) == 1) {
        				mysqli_query($db, "DELETE FROM i_posts WHERE post_id = '$postID'");
        				echo '200';
        			} else {
        				mysqli_query($db, "DELETE FROM i_posts WHERE post_id = '$postID' AND post_owner_id = '$userID'");
        				echo '200';
        			}
				}else{
					echo '404';
				}
			}else if(!empty($postID) && $WasStatus == '1'){
				$getPostFileIDs = $iN->iN_GetAllPostDetails($postID);
				$postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
				$trimValue = rtrim($postFileIDs, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach ($explodeFiles as $explodeFile) {
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					if($theFileID){
						$uploadedFileID = $theFileID['upload_id'];
						$uploadedFilePath = $theFileID['uploaded_file_path'];
						$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
						$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
						$s3->deleteObject([
							'Bucket' => $WasBucket,
							'Key'    => $uploadedFilePath,
						]);
						$s3->deleteObject([
							'Bucket' => $WasBucket,
							'Key'    => $uploadedFilePathX,
						]);
						$s3->deleteObject([
							'Bucket' => $WasBucket,
							'Key'    => $uploadedTumbnailFilePath,
						]);
						mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
					}
				}
				$deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorage($userID, $postID);
				if($deleteStoragePost){
				    if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
				    echo '200';
				}else{
					echo '404';
				}
			}else if(!empty($postID)){
				$deletePostFromData = $iN->iN_DeletePost($userID, $postID);
				$deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
				if ($deletePostFromData) {
				    if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
					echo '200';
				} else {
					echo '404';
				}
			}
		}
	}
	/*Share My Storie*/
	if($type == 'shareMyStorie'){
      if(isset($_POST['id'])){
         $storieID = mysqli_real_escape_string($db, $_POST['id']);
		 $storieText = mysqli_real_escape_string($db, $_POST['txt']);
		 if($iN->iN_CheckStorieIDExist($userID, $storieID) == 1){
			$insertStorie = $iN->iN_InsertMyStorie($userID,$storieID, $iN->iN_Secure($storieText));
			if($insertStorie){
               echo '200';
			}else{
			   echo '404';
			}
		 }
	  }
	}
	/*Show More Posts*/
	if ($type == 'moreposts') {
		if (isset($_POST['last'])) {
			$page = $type;
			$files = array(
			1 => 'suggestedusers',
			2 => 'ads');
			shuffle($files);

			for ($i = 0; $i < 1; $i++) {
				include "../themes/$currentTheme/layouts/random_boxs/$files[$i].php";
			}
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Show More Saved Posts*/
	if ($type == 'savedpost') {
		if (isset($_POST['last'])) {
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Show More Profile Posts*/
	if ($type == 'profile') {
		if (isset($_POST['last']) && isset($_POST['p'])) {
			$p_profileID = mysqli_real_escape_string($db, $_POST['p']);
		
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Show More Profile Posts*/
	if ($type == 'hashtag') {
		if (isset($_POST['last']) && isset($_POST['p'])) {
			$pageFor = mysqli_real_escape_string($db, $_POST['p']);
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Update Post Comment Status*/
	if ($type == 'updateComentStatus') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$updatePostCommentStatus = $iN->iN_UpdatePostCommentStatus($userID, $postID);
			if ($updatePostCommentStatus == '1') {
				$status = '200';
				$text = $iN->iN_SelectedMenuIcon('31') . $LANG['disable_comment'];
			} else {
				$status = '404';
				$text = $iN->iN_SelectedMenuIcon('31') . $LANG['enable_comments'];
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Update Post Comment Status*/
	if ($type == 'pinpost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$updatePostPinedStatus = $iN->iN_UpdatePostPinedStatus($userID, $postID);
			if ($updatePostPinedStatus == '1') {
				$status = '200';
				$text = '<div class="i_pined_post" id="i_pined_post_' . $postID . '">' . $iN->iN_SelectedMenuIcon('62') . '</div>';
				$btnText = $iN->iN_SelectedMenuIcon('29') . $LANG['post_pined_on_your_profile'];
			} else {
				$status = '404';
				$text = '';
				$btnText = $iN->iN_SelectedMenuIcon('29') . $LANG['pin_on_my_profile'];
			}
			$data = array(
				'status' => $status,
				'text' => $text,
				'btn' => $btnText,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Report Post*/
	if ($type == 'reportPost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$insertPostReport = $iN->iN_InsertReportedPost($userID, $postID);
			if ($insertPostReport) {
				if ($insertPostReport == 'rep') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['report_this_post'];
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Save Post From Saved List*/
	if ($type == 'savePost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$insertPostSave = $iN->iN_SavePostInSavedList($userID, $postID);
			if ($insertPostSave) {
				if ($insertPostSave == 'svp') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('63');
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('22');
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Insert a New Comment*/
	if ($type == 'comment') {
		if (isset($_POST['id']) && isset($_POST['val'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$value = mysqli_real_escape_string($db, $_POST['val']);
			$sticker = mysqli_real_escape_string($db, $_POST['sticker']);
			$Gif = mysqli_real_escape_string($db, $_POST['gf']);
			if (empty($value) && empty($sticker) && empty($Gif)) {
				$status = '404';
			} else {
				$insertNewComment = $iN->iN_insertNewComment($userID, $postID, $iN->iN_Secure($value), $iN->iN_Secure($sticker), $iN->iN_Secure($Gif));
				if ($insertNewComment) {
					$commentID = $insertNewComment['com_id'];
					$commentedUserID = $insertNewComment['comment_uid_fk'];
					$Usercomment = $insertNewComment['comment'];
					$commentTime = isset($insertNewComment['comment_time']) ? $insertNewComment['comment_time'] : NULL;
					$corTime = date('Y-m-d H:i:s', $commentTime);
					$commentFile = isset($insertNewComment['comment_file']) ? $insertNewComment['comment_file'] : NULL;
					$stickerUrl = isset($insertNewComment['sticker_url']) ? $insertNewComment['sticker_url'] : NULL;
					$gifUrl = isset($insertNewComment['gif_url']) ? $insertNewComment['gif_url'] : NULL;
					$commentedUserIDFk = isset($insertNewComment['iuid']) ? $insertNewComment['iuid'] : NULL;
					$commentedUserName = isset($insertNewComment['i_username']) ? $insertNewComment['i_username'] : NULL;
					$userPostID = $insertNewComment['comment_post_id_fk'];
					if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewCommentPointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
						$iN->iN_InsertNewCommentPoint($userID,$userPostID,$ataNewCommentPointAmount);
					}
					$checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
					$cUType = '';
					if($checkUserIsCreator){
                       $cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
					}
					$commentedUserFullName = isset($insertNewComment['i_user_fullname']) ? $insertNewComment['i_user_fullname'] : NULL;
					if($fullnameorusername == 'no'){
						$commentedUserFullName = $commentedUserName;
					}
					$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
					$commentedUserGender = isset($insertNewComment['user_gender']) ? $insertNewComment['user_gender'] : NULL;
					if ($commentedUserGender == 'male') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'female') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'couple') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					}
					$commentedUserLastLogin = isset($insertNewComment['last_login_time']) ? $insertNewComment['last_login_time'] : NULL;
					$commentedUserVerifyStatus = isset($insertNewComment['user_verified_status']) ? $insertNewComment['user_verified_status'] : NULL;
					$cuserVerifiedStatus = '';
					if ($commentedUserVerifyStatus == '1') {
						$cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
					}
					$checkCommentLikedBefore = $iN->iN_CheckCommentLikedBefore($userID, $userPostID, $commentID);
					$commentLikeBtnClass = 'c_in_like';
					$commentLikeIcon = $iN->iN_SelectedMenuIcon('17');
					$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
					if ($checkCommentLikedBefore == '1') {
						$commentLikeBtnClass = 'c_in_unlike';
						$commentLikeIcon = $iN->iN_SelectedMenuIcon('18');
						if ($checkCommentReportedBefore == '1') {
							$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
						}
					}
					$stickerComment = '';
					$gifComment = '';
					if ($stickerUrl) {
						$stickerComment = '<div class="comment_file"><img src="' . $stickerUrl . '"></div>';
					}
					if ($gifUrl) {
						$gifComment = '<div class="comment_gif_file"><img src="' . $gifUrl . '"></div>';
					}
					include "../themes/$currentTheme/layouts/posts/comments.php";
					$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($userPostID);
					$commentedPostOwnerID = $GetPostOwnerIDFromPostDetails['post_owner_id'];
					if ($userID != $commentedPostOwnerID) {
						$iN->iN_InsertNotificationForCommented($commentedUserID, $userPostID);
					}
					if($Usercomment){
						$iN->iN_InsertMentionedUsersForComment($userID, $Usercomment, $userPostID, $commentedUserName,$commentedPostOwnerID);
					 }
					$uData = $iN->iN_GetUserDetails($commentedPostOwnerID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$emailNotificationStatus = $uData['email_notification_status'];
					$notQualifyDocument = $LANG['not_qualify_document'];
					if ($emailSendStatus == '1' && $userID != $commentedPostOwnerID && $emailNotificationStatus == '1') {
						if ($smtpOrMail == 'mail') {
							$mail->IsMail();
						} else if ($smtpOrMail == 'smtp') {
							$mail->isSMTP();
							$mail->Host = $smtpHost; // Specify main and backup SMTP servers
							$mail->SMTPAuth = true;
							$mail->SMTPKeepAlive = true;
							$mail->Username = $smtpUserName; // SMTP username
							$mail->Password = $smtpPassword; // SMTP password
							$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
							$mail->Port = $smtpPort;
							$mail->SMTPOptions = array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							);
						} else {
							return false;
						}
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$commentedBelow = $iN->iN_Secure($LANG['commented_below']);
						$commentE = $iN->iN_Secure($Usercomment);
						include_once '../includes/mailTemplates/commentEmailTemplate.php';
						$body = $bodyCommentEmail;
						$mail->setFrom($smtpUserName, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['commented_on_your_post']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if ($mail->send()) {
							$mail->ClearAddresses();
							return true;
						}
					}

				} else {
					echo '404';
				}
			}
		}
	}

	/*Comment Like*/
	if ($type == 'pc_like') {
		if (isset($_POST['post']) && isset($_POST['com'])) {
			$postID = mysqli_real_escape_string($db, $_POST['post']);
			$postCommentID = mysqli_real_escape_string($db, $_POST['com']);
			$likePostComment = $iN->iN_LikePostComment($userID, $postID, $postCommentID);
			$status = 'c_in_like';
			$pcLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePostComment) {
				$status = 'c_in_unlike';
				$pcLike = $iN->iN_SelectedMenuIcon('18');
				$commentLikedSum = $iN->iN_TotalCommentLiked($postCommentID);
				if($iN->iN_CheckCommentOwner($userID, $postID) === false && $ataNewPostCommentLikePointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPostCommentLikePoint($userID,$postID,$ataNewPostCommentLikePointAmount);
				}
			}
			if($status == 'c_in_like'){
				if($iN->iN_CheckCommentOwner($userID, $postID) === false && $ataNewPostCommentLikePointSatus == 'yes'){
					$iN->iN_RemovePointPostCommentLikeIfExist($userID,$postID,$ataNewPostCommentLikePointAmount);
				}
			}
			$data = array(
				'status' => $status,
				'like' => $pcLike,
				'totalLike' => isset($commentLikedSum) ? $commentLikedSum : '0',
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			$cLData = $iN->iN_GetUserIDFromLikedPostID($postCommentID);
			$commendOwnerID = $cLData['comment_uid_fk'];
			if ($userID != $commendOwnerID) {
				$iN->iN_insertCommentLikeNotification($userID, $postID, $postCommentID);
			}
			$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($postID);
			$uData = $iN->iN_GetUserDetails($commendOwnerID);
			$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
			$lUsername = $uData['i_username'];
			$lUserFullName = $uData['i_user_fullname'];
			$emailNotificationStatus = $uData['email_notification_status'];
			$notQualifyDocument = $LANG['not_qualify_document'];
			$slugUrl = $base_url . 'post/' . $GetPostOwnerIDFromPostDetails['url_slug'] . '_' . $postID;
			if ($emailSendStatus == '1' && $userID != $commendOwnerID && $emailNotificationStatus == '1' && $status == 'c_in_unlike') {
				if ($smtpOrMail == 'mail') {
					$mail->IsMail();
				} else if ($smtpOrMail == 'smtp') {
					$mail->isSMTP();
					$mail->Host = $smtpHost; // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true;
					$mail->Username = $smtpUserName; // SMTP username
					$mail->Password = $smtpPassword; // SMTP password
					$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
					$mail->Port = $smtpPort;
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				} else {
					return false;
				}
				$instagramIcon = $iN->iN_SelectedMenuIcon('88');
				$facebookIcon = $iN->iN_SelectedMenuIcon('90');
				$twitterIcon = $iN->iN_SelectedMenuIcon('34');
				$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
				$someoneLikedYourPost = $iN->iN_Secure($LANG['someone_liked_your_comment']);
				$clickGoPost = $iN->iN_Secure($LANG['click_go_comment']);
				$likedYourPost = $iN->iN_Secure($LANG['liked_your_comment']);
				include_once '../includes/mailTemplates/postLikeEmailTemplate.php';
				$body = $bodyPostLikeEmail;
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['someone_liked_your_comment']);
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if ($mail->send()) {
					$mail->ClearAddresses();
					return true;
				}
			}
		}
	}
	/*Delete Comment Call AlertBox*/
	if ($type == 'ddelComment') {
		if (isset($_POST['id']) && isset($_POST['pid'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['id']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteCommentAlert.php";
		}
	}
	/*Delete Comment*/
	if ($type == 'deletecomment') {
		if (isset($_POST['cid']) && isset($_POST['pid'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['cid']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$deleteComment = $iN->iN_DeleteComment($userID, $commentID, $postID);
			if ($deleteComment) {
				if($ataNewCommentPointSatus == 'yes'){$iN->iN_RemovePointCommentIfExist($userID, $postID, $ataNewCommentPointAmount);}
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	/*Report Comment*/
	if ($type == 'reportComment') {
		if (isset($_POST['id']) && isset($_POST['pid'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['id']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$insertCommentReport = $iN->iN_InsertReportedComment($userID, $commentID, $postID);
			if ($insertCommentReport) {
				if ($insertCommentReport == 'rep') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Show Edit Comment In PopUp*/
	if ($type == 'c_editComment') {
		if (isset($_POST['cid']) && isset($_POST['pid'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['cid']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$getCData = $iN->iN_GetCommentFromID($userID, $commentID, $postID);
			if ($getCData) {
				$commentText = isset($getCData['comment']) ? $getCData['comment'] : NULL;
				include "../themes/$currentTheme/layouts/posts/editComment.php";
			} else {
				echo '404';
			}
		}
	}
	/*Save Edited Comment*/
	if ($type == 'editSC') {
		if (isset($_POST['cid']) && isset($_POST['pid']) && isset($_POST['text'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['cid']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$editedText = mysqli_real_escape_string($db, $_POST['text']);
			if (empty($editedText)) {
				$status = 'no';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			$saveEditedComment = $iN->iN_UpdateComment($userID, $postID, $commentID, $iN->iN_Secure($editedText));
			if ($saveEditedComment) {
				$getNewPostFromData = $iN->iN_GetCommentFromID($userID, $commentID, $postID);
				$status = '200';
				$data = array(
					'status' => $status,
					'text' => $iN->sanitize_output($getNewPostFromData['comment'], $base_url),
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			} else {
				$status = '404';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		}
	}
	/*Get Emojis*/
	if ($type == 'emoji') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			$ec = mysqli_real_escape_string($db, $_POST['ec']);
			$importID = '';
			if (!empty($ec)) {
				$importID = 'data-id="' . $ec . '"';
			}
			if ($id == 'emojiBox') {
				$importClass = 'emoji_item';
			} else if ($id == 'emojiBoxC') {
				$importClass = 'emoji_item_c';
			}
			include "../themes/$currentTheme/layouts/widgets/emojis.php";
		}
	}
	/*Get Stickers*/
	if ($type == 'stickers') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/widgets/stickers.php";
		}
	}
	/*Get Gifs*/
	if ($type == 'gifList') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/widgets/gifs.php";
		}
	}
	/*Add Sticker*/
	if ($type == 'addSticker') {
		if (isset($_POST['id'])) {
			$stickerID = mysqli_real_escape_string($db, $_POST['id']);
			$ID = mysqli_real_escape_string($db, $_POST['pi']);
			$getStickerUrlandID = $iN->iN_getSticker($stickerID);
			if ($getStickerUrlandID) {
				$data = array(
					'stickerUrl' => '<div class="in_sticker_wrapper" id="stick_id_' . $getStickerUrlandID['sticker_id'] . '"><img src="' . $getStickerUrlandID['sticker_url'] . '"></div><div class="removeSticker" id="' . $ID . '">' . $iN->iN_SelectedMenuIcon('5') . '</div>',
					'st_id' => $getStickerUrlandID['sticker_id'],
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			}
		}
	}
	/*Get Free Follow PopUP*/
	if ($type == 'follow_free_not') {
		if (isset($_POST['id'])) {
			$uID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				$userDetail = $iN->iN_GetUserDetails($uID);
				$f_userID = $userDetail['iuid'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				include "../themes/$currentTheme/layouts/popup_alerts/free_follow_popup.php";
			}
		}
	}
	/*Follow Profile Free*/
	if ($type == 'freeFollow') {
		if (isset($_POST['follow'])) {
			$uID = mysqli_real_escape_string($db, $_POST['follow']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				$checkUserFollowing = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
				if ($checkUserFollowing != 'me') {
					$insertNewFollowingList = $iN->iN_insertNewFollow($userID, $uID);
					if ($insertNewFollowingList == 'flw') {
						$status = '200';
						$not = $insertNewFollowingList;
						$btn = $iN->iN_SelectedMenuIcon('66') . $LANG['unfollow'];
						$iN->iN_InsertNotificationForFollow($userID, $uID);
					} else if ($insertNewFollowingList == 'unflw') {
						$status = '200';
						$not = $insertNewFollowingList;
						$btn = $iN->iN_SelectedMenuIcon('66') . $LANG['follow'];
						$iN->iN_RemoveNotificationForFollow($userID, $uID);
					} else {
						$status = '404';
						$not = '';
						$btn = '';
					}
					$data = array(
						'status' => $status,
						'text' => $not,
						'btn' => $btn,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					$uData = $iN->iN_GetUserDetails($uID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$lUsername = $uData['i_username'];
					$fuserAvatar = $iN->iN_UserAvatar($uID, $base_url);
					$lUserFullName = $userFullName;
					$emailNotificationStatus = $uData['email_notification_status'];
					$notQualifyDocument = $LANG['not_qualify_document'];
					$slugUrl = $base_url . $lUsername;
					if ($emailSendStatus == '1' && $emailNotificationStatus == '1' && $insertNewFollowingList == 'flw') {
						if ($smtpOrMail == 'mail') {
							$mail->IsMail();
						} else if ($smtpOrMail == 'smtp') {
							$mail->isSMTP();
							$mail->Host = $smtpHost; // Specify main and backup SMTP servers
							$mail->SMTPAuth = true;
							$mail->SMTPKeepAlive = true;
							$mail->Username = $smtpUserName; // SMTP username
							$mail->Password = $smtpPassword; // SMTP password
							$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
							$mail->Port = $smtpPort;
							$mail->SMTPOptions = array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							);
						} else {
							return false;
						}
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
						include_once '../includes/mailTemplates/userFollowingEmailTemplate.php';
						$body = $bodyUserFollowEmailTemplate;
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if ($mail->send()) {
							$mail->ClearAddresses();
							return true;
						}
					}
				}
			}
		}
	}
	/*Block User PopUp Call*/
	if ($type == 'uBlockNotice') {
		if (isset($_POST['id'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			if ($checkUserExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userfullname = $userDetail['i_user_fullname'];
				include "../themes/$currentTheme/layouts/popup_alerts/userBlockAlert.php";
			}
		}
	}
	/*Block User*/
	if ($type == 'ublock') {
		if (isset($_POST['id']) && in_array($_POST['blckt'], $blockType)) {
			$uID = mysqli_real_escape_string($db, $_POST['id']);
			$uBlockType = mysqli_real_escape_string($db, $_POST['blckt']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$friendsStatusTwo = $iN->iN_GetRelationsipBetweenTwoUsers($uID, $userID);
					$addBlockList = $iN->iN_InsertBlockList($userID, $uID, $uBlockType);
					if ($addBlockList == 'bAdded') {
						$status = '200';
						$redirect = $base_url . 'settings?tab=blocked';
					} else if ($addBlockList == 'bRemoved') {
						$status = '200';
						$redirect = $base_url . 'settings?tab=blocked';
					} else {
						$status = '404';
						$redirect = '';
					}
					if ($addBlockList == 'bAdded' && $uBlockType == '2') {
						if ($friendsStatus == 'subscriber') {
							\Stripe\Stripe::setApiKey($stripeKey);
							$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
							$subscription->cancel();
							$iN->iN_UnSubscriberUser($userID, $uID);
						} else if ($friendsStatus == 'flwr') {
							$iN->iN_insertNewFollow($userID, $uID);
						}
						if ($friendsStatusTwo == 'subscriber') {
							\Stripe\Stripe::setApiKey($stripeKey);
							$getSubsData = $iN->iN_GetSubscribeID($uID, $userID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
							$subscription->cancel();
							$iN->iN_UnSubscriberUser($uID, $userID);
						} else if ($friendsStatusTwo == 'flwr') {
							$iN->iN_insertNewFollow($uID, $userID);
						}
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	/*Subscribe Modal with Methods*/
	if ($type == 'subsModal') {
		if (isset($_POST['id'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			$p_friend_status = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $iuID);
			if ($checkUserExist && $p_friend_status != 'subscriber') {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userID = $userDetail['iuid'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				if($subscriptionType == '2'){
					include "../themes/$currentTheme/layouts/popup_alerts/becomeSubscriberWithPoint.php";
				}else if($subscriptionType == '1' || $subscriptionType == '3'){
					include "../themes/$currentTheme/layouts/popup_alerts/becomeSubscriber.php";
				}
			}
		}
	}
	/*Credit Card popUp*/
	if ($type == 'creditCard') {
		if (isset($_POST['plan']) && isset($_POST['id'])) {
			$planID = mysqli_real_escape_string($db, $_POST['plan']);
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkPlanExist = $iN->iN_CheckPlanExist($planID, $iuID);
			if ($checkPlanExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userID = $userDetail['iuid'];
				$f_PlanAmount = $checkPlanExist['amount'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				include "../themes/$currentTheme/layouts/popup_alerts/payWithCreditCard.php";
			}
		}
	}
	
	/* Resolve a plan for a user:
 * - Try iN_CheckPlanExist (real offer row)
 * - If not found and plan looks like "month-<uid>" or "year-<uid>", pull price from i_users.
 */
function _resolvePlanForUser($db, $iN, $planID, $iuID) {
    $row = $iN->iN_CheckPlanExist($planID, $iuID);
    if ($row) return $row;

    if (preg_match('/^(month|year)-(\d+)$/', $planID, $m) && (int)$m[2] === (int)$iuID) {
        $isMonth = ($m[1] === 'month');
        $colS = $isMonth ? 'sub_month_status'  : 'sub_year_status';
        $colA = $isMonth ? 'sub_month_amount'  : 'sub_year_amount';
        $uid  = mysqli_real_escape_string($db, (string)$iuID);
        $q = @mysqli_query($db, "SELECT IFNULL($colS,0) s, IFNULL($colA,'') a FROM i_users WHERE iuid='$uid' LIMIT 1");
        if ($q && ($r = mysqli_fetch_assoc($q))) {
            if ((int)$r['s'] === 1 && $r['a'] !== '') {
                return [
                    'plan_id'   => $planID,
                    'plan_type' => $isMonth ? 'monthly' : 'yearly',
                    'amount'    => (float)$r['a']
                ];
            }
        }
    }
    return false;
}
					
	/*Credit Card popUp*/
	if ($type == 'creditPoint') {
		if (isset($_POST['plan']) && isset($_POST['id'])) {
			$planID = mysqli_real_escape_string($db, $_POST['plan']);
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkPlanExist = _resolvePlanForUser($db, $iN, $planID, $iuID);
			if ($checkPlanExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$planType = $checkPlanExist['plan_type'];
				$f_userID = $userDetail['iuid'];
				$f_PlanAmount = $checkPlanExist['amount'];
$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;

				include "../themes/$currentTheme/layouts/popup_alerts/payWithPoint.php";
			}
		}
	}
	
					
	
	
	/* Initialize Paystack subscription checkout (redirect) */
if ($type == 'paystackSubInit') {
    // required params
    $creator_id = isset($_GET['creator_id']) ? (int)$_GET['creator_id'] : 0;
    $plan_id    = isset($_GET['plan_id'])    ? $_GET['plan_id']         : '';
    $amount     = isset($_GET['amount'])     ? (float)$_GET['amount']   : 0;
    $interval   = isset($_GET['interval'])   ? $_GET['interval']        : 'monthly';

    if ($creator_id <= 0 || !$plan_id || $amount <= 0) {
        http_response_code(400);
        exit('Invalid request');
    }

    // optionally block if already subscribed
    $rel = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $creator_id);
    if ($rel == 'subscriber') {
        // you can redirect back with a message instead of blocking:
        exit('Already subscribed');
    }

    // prepare Paystack init
    $secret = $paystackSecretKey ?? ($PAYSTACK_SECRET_KEY ?? '');
    if (!$secret) { http_response_code(500); exit('Paystack not configured'); }

    $email    = $userData['email'] ?? ('user'.$userID.'@example.com');
    $currency = strtoupper($defaultCurrency ?? 'NGN'); // NGN expected for Paystack
    $amountKobo = (int) round($amount * 100);

    // callback URL (make sure this route exists to verify and grant access)
    $callback = $base_url . 'requests/request.php?f=paystackSubCallback';

    $meta = [
        'creator_id' => $creator_id,
        'plan_id'    => $plan_id,
        'buyer_id'   => $userID,
        'interval'   => $interval,
        'type'       => 'subscription'
    ];

    // initialize transaction
    $payload = [
        'email'        => $email,
        'amount'       => $amountKobo,
        'currency'     => $currency,
        'callback_url' => $callback,
        'metadata'     => $meta,
    ];

    $ch = curl_init('https://api.paystack.co/transaction/initialize');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$secret,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $http   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http == 200 && $result) {
        $r = json_decode($result, true);
        if (!empty($r['status']) && !empty($r['data']['authorization_url'])) {
            header('Location: '.$r['data']['authorization_url']);
            exit;
        }
    }
    http_response_code(502);
    exit('Could not start Paystack checkout');
}

					
	
	
	/*Subscribe User (SEND STRIPE AND SAVE DATA)*/
	if ($type == 'subscribeMe') {
		if (isset($_POST['u']) && isset($_POST['pl']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['t'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['u']);
			$planID = mysqli_real_escape_string($db, $_POST['pl']);
			$subscriberName = mysqli_real_escape_string($db, $_POST['name']);
			$subscriberEmail = mysqli_real_escape_string($db, $_POST['email']);
			$stripeTokenID = mysqli_real_escape_string($db, $_POST['t']);
			$planDetails = $iN->iN_CheckPlanExist($planID, $iuID);
			$payment_id = $statusMsg = $api_error = '';
			//$checkAlreadySubscribed = $iN->iN_CheckUserIsInSubscriber($userID, $iuID);
			$p_friend_status = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $iuID);
			if($p_friend_status == 'subscriber'){
               exit('Already subscribed.');
			}
			if ($planDetails && $p_friend_status != 'subscriber') {
				$planType = $planDetails['plan_type'];
				$amount = $planDetails['amount'];
				$payment_Type = 'stripe';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
				}
				if (empty($stripeTokenID) || $stripeTokenID == '' || !isset($stripeTokenID) || $stripeTokenID == 'undefined') {
					exit($LANG['fill_all_credit_card_details']);
				}
				// Set API key
				\Stripe\Stripe::setApiKey($stripeKey);
				// Add customer to stripe
				try {
					$customer = \Stripe\Customer::create(array(
						'email' => $subscriberEmail,
						'source' => $stripeTokenID,
					));
				} catch (Exception $e) {
					$api_error = $e->getMessage();
				}
				/******/
				if (empty($api_error) && $customer) {
					// Convert price to cents
					$priceCents = round($amount * 100);

					// Create a plan
					try {
						$plan = \Stripe\Plan::create(array(
							"product" => [
								"name" => $planName,
							],
							"amount" => $priceCents,
							"currency" => $stripeCurrency,
							"interval" => $planInterval,
							"interval_count" => 1,
						));
					} catch (Exception $e) {
						$api_error = $e->getMessage();
					}

					if (empty($api_error) && $plan) {

						// Creates a new subscription
						try {
							$subscription = \Stripe\Subscription::create(array(
								"customer" => $customer->id,
								"items" => array(
									array(
										"plan" => $plan->id,
									),
								),
							));
						} catch (Exception $e) {
							$api_error = $e->getMessage();
						}
						if (empty($api_error) && $subscription) {
							// Retrieve subscription data
							$subsData = $subscription->jsonSerialize();
							// Check whether the subscription activation is successful
							if ($subsData['status'] == 'active') {
								// Subscription info
								$subscrID = $subsData['id'];
								$custID = $subsData['customer'];
								$planIDs = $subsData['plan']['id'];
								$planAmount = ($subsData['plan']['amount'] / 100);
								$planCurrency = $subsData['plan']['currency'];
								$planinterval = $subsData['plan']['interval'];
								$planIntervalCount = $subsData['plan']['interval_count'];
								$plancreated = date("Y-m-d H:i:s", $subsData['created']);
								$current_period_start = date("Y-m-d H:i:s", $subsData['current_period_start']);
								$current_period_end = date("Y-m-d H:i:s", $subsData['current_period_end']);
								$planStatus = $subsData['status'];
								$adminEarning = ($adminFee * $planAmount) / 100;
								$userNetEarning = $planAmount - $adminEarning;
								$insertSubscription = $iN->iN_InsertUserSubscription($userID, $iuID, $payment_Type, $subscriberName, $subscrID, $custID, $planIDs, $planAmount, $adminEarning, $userNetEarning, $planCurrency, $planinterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus);
								if ($insertSubscription) {
									echo '200';
									$uData = $iN->iN_GetUserDetails($iuID);
									$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
									$lUsername = $uData['i_username'];
									$iN->iN_InsertNotificationForSubscribe($userID, $iuID);
									$fuserAvatar = $iN->iN_UserAvatar($iuID, $base_url);
									$lUserFullName = $uData['i_user_fullname'];
									$emailNotificationStatus = $uData['email_notification_status'];
									$morePostForSubscriber = $LANG['share_something_for_subscriber'];
									$slugUrl = $base_url . $lUsername;
									$gotNewSubscriber = $LANG['got_new_subscriber'];
									if ($emailSendStatus == '1' && $emailNotificationStatus == '1') {

										if ($smtpOrMail == 'mail') {
											$mail->IsMail();
										} else if ($smtpOrMail == 'smtp') {
											$mail->isSMTP();
											$mail->Host = $smtpHost; // Specify main and backup SMTP servers
											$mail->SMTPAuth = true;
											$mail->SMTPKeepAlive = true;
											$mail->Username = $smtpUserName; // SMTP username
											$mail->Password = $smtpPassword; // SMTP password
											$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
											$mail->Port = $smtpPort;
											$mail->SMTPOptions = array(
												'ssl' => array(
													'verify_peer' => false,
													'verify_peer_name' => false,
													'allow_self_signed' => true,
												),
											);
										} else {
											return false;
										}
										$instagramIcon = $iN->iN_SelectedMenuIcon('88');
										$facebookIcon = $iN->iN_SelectedMenuIcon('90');
										$twitterIcon = $iN->iN_SelectedMenuIcon('34');
										$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
										$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
										include_once '../includes/mailTemplates/newSubscriberEmailTemplate.php';
										$body = $bodyNewSubscriberEmailTemplate;
										$mail->setFrom($smtpEmail, $siteName);
										$send = false;
										$mail->IsHTML(true);
										$mail->addAddress($sendEmail, ''); // Add a recipient
										$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
										$mail->CharSet = 'utf-8';
										$mail->MsgHTML($body);
										if ($mail->send()) {
											$mail->ClearAddresses();
											return true;
										}

									}
								} else {
									echo iN_HelpSecure($LANG['contact_site_administrator']);
								}
							} else {
								echo iN_HelpSecure($LANG['subscription_activation_failed']);
							}
						} else {
							echo iN_HelpSecure($LANG['subscription_creation_failed']) . $api_error;
						}
					} else {
						echo iN_HelpSecure($LANG['plan_creation_failed']) . $api_error;
					}
				} else {
					echo iN_HelpSecure($LANG['invalid_card_details']) . $api_error;
				}
				/******/
			}
		}
	}
	/*Subscribe User (SUBSCRIBE WITH UPLOADED POINTS)*/
	if($type == 'subWithPoints'){
        if(isset($_POST['pl']) && $_POST['pl'] != '' && !empty($_POST['pl']) && isset($_POST['id']) && $_POST['id'] != '' && !empty($_POST['id'])){
			$planID = mysqli_real_escape_string($db, $_POST['pl']);
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkPlanExist = _resolvePlanForUser($db, $iN, $planID, $iuID);
			$planType = isset($checkPlanExist['plan_type']) ? $checkPlanExist['plan_type'] : NULL;
			$planAmount = isset($checkPlanExist['amount']) ? $checkPlanExist['amount'] : NULL;
			if($checkPlanExist && ($userCurrentPoints >= $planAmount)){
				$payment_Type = 'point';
				$adminEarning = $adminFee * $planAmount * $onePointEqual / 100;
				$userNetEarning = $planAmount * $onePointEqual - $adminEarning;
				$planIntervalCount = '1';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
					$thisTime = strtotime('+7 day', time());
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", $thisTime);
				    $current_period_end = date("Y-m-d H:i:s", $thisTime);
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 year', time()));
				}
				$uDetails = $iN->iN_GetUserDetails($iuID);
				$subscriberName = mysqli_real_escape_string($db, $uDetails['i_user_fullname']);
			    $subscriberEmail = mysqli_real_escape_string($db, $uDetails['i_user_email']);
				$UpdateCurrentPoint = $userCurrentPoints - $planAmount;
				$planCurrency = $defaultCurrency;
				$planStatus = 'active';
				$insertSubscription = $iN->iN_InsertUserSubscriptionWithPoint($userID, $iuID, $payment_Type, $subscriberName, $planAmount, $adminEarning, $userNetEarning, $planCurrency, $planInterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus,$UpdateCurrentPoint);
			    if ($insertSubscription) {
					echo '200';
					$uData = $iN->iN_GetUserDetails($iuID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$lUsername = $uData['i_username'];
					$iN->iN_InsertNotificationForSubscribe($userID, $iuID);
					$fuserAvatar = $iN->iN_UserAvatar($iuID, $base_url);
					$lUserFullName = $uData['i_user_fullname'];
					$emailNotificationStatus = $uData['email_notification_status'];
					$morePostForSubscriber = $LANG['share_something_for_subscriber'];
					$slugUrl = $base_url . $lUsername;
					$gotNewSubscriber = $LANG['got_new_subscriber'];
					if ($emailSendStatus == '1' && $emailNotificationStatus == '1') {
						if ($smtpOrMail == 'mail') {
							$mail->IsMail();
						} else if ($smtpOrMail == 'smtp') {
							$mail->isSMTP();
							$mail->Host = $smtpHost; // Specify main and backup SMTP servers
							$mail->SMTPAuth = true;
							$mail->SMTPKeepAlive = true;
							$mail->Username = $smtpUserName; // SMTP username
							$mail->Password = $smtpPassword; // SMTP password
							$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
							$mail->Port = $smtpPort;
							$mail->SMTPOptions = array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							);
						} else {
							return false;
						}
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
						include_once '../includes/mailTemplates/newSubscriberEmailTemplate.php';
						$body = $bodyNewSubscriberEmailTemplate;
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if ($mail->send()) {
							$mail->ClearAddresses();
							return true;
						}
					}
				}else{
					exit('404');
				}
			} else{
				exit('302');
			}
		}
	}

	if ($type == 'uploadVerificationFiles') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableVerificationFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit('Upload Failed');
								}
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if ($WasStatus == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if($digitalOceanStatus == '1'){
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									/**/
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									if($upload){
										$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $getFilename;
									 }
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								 } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedFilesForVerification($userID, $pathFile, NULL, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							/*AMAZON S3*/
							echo '
                    <div class="i_uploaded_item in_' . $theValidateType . ' iu_f_' . $getUploadedFileID['upload_id'] . '" id="' . $getUploadedFileID['upload_id'] . '">
                      ' . $postTypeIcon . '
                      <div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
                          ' . $iN->iN_SelectedMenuIcon('5') . '
                      </div>
                      <div class="i_uploaded_file" style="background-image:url(' . $UploadSourceUrl . ');">
                            <img class="i_file" src="' . $UploadSourceUrl . '" alt="' . $UploadSourceUrl . '">
                      </div>
                    </div>
                ';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*Send Account Verificatoun Request*/
	if ($type == 'verificationRequest') {
		if (isset($_POST['cID']) && isset($_POST['cP'])) {
			$cardIDPhoto = mysqli_real_escape_string($db, $_POST['cID']);
			$Photo = mysqli_real_escape_string($db, $_POST['cP']);
			$checkCardIDPhotoExist = $iN->iN_CheckImageIDExist($cardIDPhoto, $userID);
			$checkPhotoExist = $iN->iN_CheckImageIDExist($Photo, $userID);
			if (empty($cardIDPhoto) && empty($Photo) && empty($checkCardIDPhotoExist) && empty($checkPhotoExist)) {
				echo 'both';
				return false;
			}
			if (empty($cardIDPhoto) && empty($checkCardIDPhotoExist)) {
				echo 'card';
				return false;
			}
			if (empty($Photo) && empty($checkPhotoExist)) {
				echo 'photo';
				return false;
			}
			if ($checkCardIDPhotoExist == '1' && $checkPhotoExist == '1') {
				$InsertNewVerificationRequest = $iN->iN_InsertNewVerificationRequest($userID, $cardIDPhoto, $Photo);
				if ($InsertNewVerificationRequest) {
					echo '200';
				}
			} else {
				echo 'both';
			}
		}
	}
	/*Accept Conditions by Clicking Next Button*/
	if ($type == 'acceptConditions') {
		$conditionsAccept = $iN->iN_AcceptConditions($userID);
		if ($conditionsAccept) {
			echo '200';
		}
	}
	if($type == 'vldcd'){
		if(isset($_POST['code']) && $_POST['code'] != '' && !empty($_POST['code'])){
			$cosCode = mysqli_real_escape_string($db, $_POST['code']);
			$vcodeCheck = $iN->iN_PurUCheck($userID, $cosCode, $base_url);
			if($vcodeCheck == base64_decode('b2s=')){
				if($iN->iN_LegDone($cosCode)){
					exit(base64_decode('bmV4dA=='));
				}else{
					exit(base64_decode('RHVyaW5nIHRoZSBpbnN0YWxsYXRpb24gcHJvY2VzcywgYW4gaXNzdWUgaGFzIGFyaXNlbiBjb25jZXJuaW5nIHRoZSBzZXJ2ZXIuIFBsZWFzZSBjcmVhdGUgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1jcmVhdGVUaWNrZXQiPnRpY2tldDwvYT4gZm9yIHByb21wdCBhc3Npc3RhbmNlLiBCZWZvcmUgY3JlYXRpbmcgYSB0aWNrZXQsIGtpbmRseSB0YWtlIGEgbW9tZW50IHRvIHJldmlldyBmb3IgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1mYXFzIj5xdWljayByZXNwb25zZTwvYT4u'));
				}
			} else{
				exit(base64_decode('RHVyaW5nIHRoZSBpbnN0YWxsYXRpb24gcHJvY2VzcywgYW4gaXNzdWUgaGFzIGFyaXNlbiBjb25jZXJuaW5nIHRoZSBzZXJ2ZXIuIFBsZWFzZSBjcmVhdGUgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1jcmVhdGVUaWNrZXQiPnRpY2tldDwvYT4gZm9yIHByb21wdCBhc3Npc3RhbmNlLiBCZWZvcmUgY3JlYXRpbmcgYSB0aWNrZXQsIGtpbmRseSB0YWtlIGEgbW9tZW50IHRvIHJldmlldyBmb3IgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1mYXFzIj5xdWljayByZXNwb25zZTwvYT4u'));
			}
		}
	}
	
	

	
	

	
	
/* Update User Payout Settings */
if ($type == 'updatePayoutSet') {
    $method = isset($_POST['method']) ? $_POST['method'] : '';
    $details = isset($_POST['details']) ? trim($_POST['details']) : '';
    $allowed_methods = ['wave', 'orange', 'mtn', 'bank'];

    // Validation: Check if method is allowed and details are provided
    if (!in_array($method, $allowed_methods) || empty($details)) {
        echo 'bank_warning';
        exit();
    }
    
    // Create the combined string, e.g., "Mtn / 12345678"
    $formattedDetails = ucfirst($method) . ' / ' . $details;
    
    $updateQuery = $db->prepare("
        UPDATE i_users 
        SET payout_method = ?, 
            bank_account = ?
        WHERE iuid = ?
    ");

    if (!$updateQuery) {
        echo 'error';
        exit();
    }
    
    // Bind the method ('mtn') and the formatted details ('Mtn / 1234')
    $updateQuery->bind_param('ssi', $method, $formattedDetails, $userID);

    if ($updateQuery->execute()) {
        echo '200'; // Success
    } else {
        echo 'error'; // DB update failed
    }
    $updateQuery->close();
    exit();
}
	
		
		
/*Inser Withdrawal*/
if ($type == 'makewithDraw') {
    if (isset($_POST['amount']) && !empty($_POST['amount']) && is_numeric($_POST['amount'])) {
        $withdrawalAmount = mysqli_real_escape_string($db, $_POST['amount']);

        // Check if user has a pending withdrawal already
        if ($iN->iN_CheckUserHavePendingWithdrawal($userID)) {
            echo '5'; // You have a pending request
            exit();
        }

        // Check if amount meets minimum
        if ($withdrawalAmount < $minimumWithdrawalAmount) {
            echo '2'; // Minimum amount not met
            exit();
        }

        // Check if user has enough balance
        if ($userWallet < $withdrawalAmount) {
            echo '3'; // Not enough balance
            exit();
        }

        // == FIX: Get the user's payout method ==
        $payoutDetails = $iN->iN_GetUserDetails($userID);
        $payoutMethod = $payoutDetails['payout_method'] ?? NULL;

        // Check if a payout method has been set
        if (empty($payoutMethod)) {
            echo '6'; // No payout method is set
            exit();
        }
        // == END FIX ==

        // Now, insert the withdrawal request with the correct payout method
        $insertWithdrawal = $iN->iN_InsertWithdrawal($userID, $withdrawalAmount, $payoutMethod, 'withdrawal');

        if ($insertWithdrawal) {
            echo '1'; // Success
        } else {
            echo '4'; // Generic error (failed to insert)
        }
    }
}
	if ($type == 'pPurchase') {
		if (isset($_POST['purchase']) && $_POST['purchase'] != '' && !empty($_POST['purchase'])) {
			$purchaseingPostID = mysqli_real_escape_string($db, $_POST['purchase']);
			$getPurchasingPostDetails = $iN->iN_GetAllPostDetails($purchaseingPostID);
			if ($getPurchasingPostDetails) {
				$userPostID = $getPurchasingPostDetails['post_id'];
				$userPostFile = $getPurchasingPostDetails['post_file'];
				$userPostOwnerID = $getPurchasingPostDetails['post_owner_id'];
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				$userPostOwnerUsername = $getPurchasingPostDetails['i_username'];
				$userPostOwnerUserFullName = $getPurchasingPostDetails['i_user_fullname'];
				$userPostWantedCredit = $getPurchasingPostDetails['post_wanted_credit'];
				include "../themes/$currentTheme/layouts/popup_alerts/purchase_premium_post.php";
			}
		}
	}
/*Purchase Post*/
	if ($type == 'goWallet') {
		if (isset($_POST['p'])) {
			$PurchasePostID = mysqli_real_escape_string($db, $_POST['p']);
			$checkPostID = $iN->iN_CheckPostIDExist($PurchasePostID);
			if ($checkPostID) {
				$getPurchasingPostDetails = $iN->iN_GetAllPostDetails($PurchasePostID);
				$userPostID = $getPurchasingPostDetails['post_id'];
				$userPostWantedCredit = $getPurchasingPostDetails['post_wanted_credit'];
				$userPostOwnerID = $getPurchasingPostDetails['post_owner_id'];

				$translatePointToMoney = $userPostWantedCredit * $onePointEqual;
				$adminEarning = $translatePointToMoney * ($adminFee / 100);
				$userEarning = $translatePointToMoney - $adminEarning;

				if ($userCurrentPoints >= $userPostWantedCredit && $userID != $userPostOwnerID) {
					$buyPost = $iN->iN_BuyPost($userID, $userPostOwnerID, $PurchasePostID, $translatePointToMoney, $adminEarning, $userEarning, $adminFee, $userPostWantedCredit);
					if ($buyPost) {
						echo iN_HelpSecure($base_url) . 'post/' . $getPurchasingPostDetails['url_slug'] . '_' . $userPostID;
						$approveNot = $LANG['congratulations_you_sold'];
						$iN->iN_SendNotificationForPurchasedPost($userID, $PurchasePostID, $userPostOwnerID,  $approveNot);
						$uData = $iN->iN_GetUserDetails($userPostOwnerID);
						$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
						$lUsername = $uData['i_username'];
						$lUserFullName = $uData['i_user_fullname'];
						$emailNotificationStatus = $uData['email_notification_status'];
						$notQualifyDocument = $LANG['not_qualify_document'];
						$slugUrl = $base_url . 'post/' . $getPurchasingPostDetails['url_slug'] . '_' . $userPostID;
						if ($emailSendStatus == '1' && $userID != $userPostOwnerID && $emailNotificationStatus == '1') {

							if ($smtpOrMail == 'mail') {
								$mail->IsMail();
							} else if ($smtpOrMail == 'smtp') {
								$mail->isSMTP();
								$mail->Host = $smtpHost; // Specify main and backup SMTP servers
								$mail->SMTPAuth = true;
								$mail->SMTPKeepAlive = true;
								$mail->Username = $smtpUserName; // SMTP username
								$mail->Password = $smtpPassword; // SMTP password
								$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
								$mail->Port = $smtpPort;
								$mail->SMTPOptions = array(
									'ssl' => array(
										'verify_peer' => false,
										'verify_peer_name' => false,
										'allow_self_signed' => true,
									),
								);
							} else {
								return false;
							}
							$instagramIcon = $iN->iN_SelectedMenuIcon('88');
							$facebookIcon = $iN->iN_SelectedMenuIcon('90');
							$twitterIcon = $iN->iN_SelectedMenuIcon('34');
							$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
							$someoneBoughtYourPost = $iN->iN_Secure($LANG['someone_bought_your_post']);
							$clickGoPost = $iN->iN_Secure($LANG['click_go_post']);
							$youEarnMoney = $iN->iN_Secure($LANG['you_earn_money']);
							include_once '../includes/mailTemplates/postBoughtEmailTemplate.php';
							$body = $bodyPostBoughtEmailTemplate;
							$mail->setFrom($smtpEmail, $siteName);
							$send = false;
							$mail->IsHTML(true);
							$mail->addAddress($sendEmail, ''); // Add a recipient
							$mail->Subject = $iN->iN_Secure($LANG['someone_bought_your_post']);
							$mail->CharSet = 'utf-8';
							$mail->MsgHTML($body);
							if ($mail->send()) {
								$mail->ClearAddresses();
								return true;
							}

						}
					} else {
						echo 'Something wrong';
					}
				} else {
					echo iN_HelpSecure($base_url) . 'purchase/purchase_point';
				}
			}
		}
	}
/*Choose Payment Method*/
	if ($type == 'choosePaymentMethod') {
		if (isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type'])) {
			$planID = mysqli_real_escape_string($db, $_POST['type']);
			$checkPlanExist = $iN->CheckPlanExist($planID);
			if ($checkPlanExist) {
				$planData = $iN->GetPlanDetails($planID);
				$planAmount = $planData['amount'];
				$planPoint = $planData['plan_amount'];
				if($stripePaymentCurrency == 'JPY'){
                     $planAmount = $planAmount / 100;
				}
				require_once '../includes/payment/vendor/autoload.php';
				if (!defined('INORA_METHODS_CONFIG')) {
					define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
				}
				$configData = configItem();
				$DataUserDetails = [
					'amounts' => [ // at least one currency amount is required
						$payPalCurrency => $planAmount,
						$iyziCoPaymentCurrency => $planAmount,
						$bitPayPaymentCurrency => $planAmount,
						$autHorizePaymentCurrency => $planAmount,
						$payStackPaymentCurrency => $planAmount,
						$stripePaymentCurrency => $planAmount,
						$razorPayPaymentCurrency => $planAmount,
						$mercadoPagoCurrency => $planAmount
					],
					'order_id' => 'ORDS' . uniqid(), // required in instamojo, Iyzico, Paypal, Paytm gateways
					'customer_id' => 'CUSTOMER' . uniqid(), // required in Iyzico, Paytm gateways
					'item_name' => $LANG['point_purchasing'], // required in Paypal gateways
					'item_qty' => 1,
					'item_id' => 'ITEM' . uniqid(), // required in Iyzico, Paytm gateways
					'payer_email' => $userEmail, // required in instamojo, Iyzico, Stripe gateways
					'payer_name' => $userFullName, // required in instamojo, Iyzico gateways
					'description' => $LANG['point_purchasing_from'], // Required for stripe
					'ip_address' => getUserIpAddr(), // required only for iyzico
					'address' => '3234 Godfrey Street Tigard, OR 97223', // required in Iyzico gateways
					'city' => 'Tigard', // required in Iyzico gateways
					'country' => 'United States', // required in Iyzico gateways
				];
				$PublicConfigs = getPublicConfigItem();

				$configItem = $configData['payments']['gateway_configuration'];

				// Get config data
				$configa = getPublicConfigItem();
				// Get app URL
				$paymentPagePath = getAppUrl();

				$gatewayConfiguration = $configData['payments']['gateway_configuration'];
				// get paystack config data
				$paystackConfigData = $gatewayConfiguration['paystack'];
				// Get paystack callback ur
				$paystackCallbackUrl = getAppUrl($paystackConfigData['callbackUrl']);

				// Get stripe config data
				$stripeConfigData = $gatewayConfiguration['stripe'];
				// Get stripe callback ur
				$stripeCallbackUrl = getAppUrl($stripeConfigData['callbackUrl']);

				// Get razorpay config data
				$razorpayConfigData = $gatewayConfiguration['razorpay'];
				// Get razorpay callback url
				$razorpayCallbackUrl = getAppUrl($razorpayConfigData['callbackUrl']);

				// Get Authorize.Net config Data
				$authorizeNetConfigData = $gatewayConfiguration['authorize-net'];
				// Get Authorize.Net callback url
				$authorizeNetCallbackUrl = getAppUrl($authorizeNetConfigData['callbackUrl']);

				// Individual payment gateway url
				$individualPaymentGatewayAppUrl = getAppUrl('individual-payment-gateways');
				// User Details Configurations FINISHED
				include "../themes/$currentTheme/layouts/popup_alerts/paymentMethods.php";
			}
		}
	}
	if ($type == 'process') {
		require_once '../includes/payment/vendor/autoload.php';
		if (!defined('INORA_METHODS_CONFIG')) {
			define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
		}
		include "../includes/payment/payment-process.php";
	}
/*Get Gifs*/
	if ($type == 'chat_gifs') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/chat/gifs.php";
		}
	}
/*Get Stickers*/
	if ($type == 'chat_stickers') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/chat/stickers.php";
		}
	}
/*Get Stickers*/
	if ($type == 'chat_btns') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/chat/chat_btns.php";
		}
	}
/*Get Emojis*/
	if ($type == 'memoji') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			$importID = '';
			$importClass = 'emoji_item_m';
			include "../themes/$currentTheme/layouts/chat/emojis.php";
		}
	}
/*Insert New Message*/
	if ($type == 'nmessage') {
		if (isset($_POST['id']) && isset($_POST['val'])) {
			$message = mysqli_real_escape_string($db, $_POST['val']);
			$chatID = mysqli_real_escape_string($db, $_POST['id']);
			$sticker = mysqli_real_escape_string($db, $_POST['sticker']);
			$gifSrc = mysqli_real_escape_string($db, $_POST['gif']);
			$fileIDs = mysqli_real_escape_string($db, $_POST['fl'] ?? '');
			$trimMoney = mysqli_real_escape_string($db, $_POST['mo']);
			$mMoney = trim($trimMoney);
			$file = isset($fileIDs) ? $fileIDs : NULL;
			$checkChatIDExist = $iN->iN_CheckChatIDExist($chatID);
			$getStickerURL = $iN->iN_getSticker($sticker);
			$stickerURL = isset($getStickerURL['sticker_url']) ? $getStickerURL['sticker_url'] : NULL;
			$gifUrl = isset($gifSrc) ? $gifSrc : NULL;
			if(!empty($mMoney) || $mMoney != ''){
				if(empty($message) && empty($file)){
                   exit('403');
				}
				if($minimumPointLimit > $mMoney){
				  exit('404');
				}
			 }
			if (empty($message)) {
				if (empty($stickerURL)) {
					if (empty($gifUrl)) {
						if (empty($file)) {
							exit('404');
						}
					}
				}
			}

			if ($checkChatIDExist) {
				$insertData = $iN->iN_InsertNewMessage($userID, $chatID, $iN->iN_Secure($message), $iN->iN_Secure($stickerURL), $iN->iN_Secure($gifUrl), $iN->iN_Secure($file), $iN->iN_Secure($mMoney));
				/**/
				if ($insertData) {
					$cMessageID = $insertData['con_id'];
					$cUserOne = $insertData['user_one'];
					$cUserTwo = $insertData['user_two'];
					$cMessage = $insertData['message'];
					$mSeenStatus = $insertData['seen_status'];
					$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
					$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				    $privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
					$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
					$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
					$cMessageTime = $insertData['time'];
					$ip = $iN->iN_GetIPAddress();
					$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
					if ($query && $query['status'] == 'success') {
						date_default_timezone_set($query['timezone']);
					}
					$message_time = date("c", $cMessageTime);
					$convertMessageTime = strtotime($message_time);
					$netMessageHour = date('H:i', $convertMessageTime);
					$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
					$msgDots = '';
					$imStyle = '';
					$seenStatus = '';
					if ($cUserOne == $userID) {
						$mClass = 'me';
						$msgOwnerID = $cUserOne;
						$lastM = '';
						$timeStyle = 'msg_time_me';
						if (!empty($cFile)) {
							$imStyle = 'mmi_i';
						}
						$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						if ($mSeenStatus == '1') {
							$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						}
					} else {
						$mClass = 'friend';
						$msgOwnerID = $cUserOne;
						$lastM = 'mm_' . $msgOwnerID;
						if (!empty($cFile)) {
							$imStyle = 'mmi_if';
						}
						$timeStyle = 'msg_time_fri';
					}
					$styleFor = '';
					if ($cStickerUrl) {
						$styleFor = 'msg_with_sticker';
						$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
					}
					if ($cGifUrl) {
						$styleFor = 'msg_with_gif';
						$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
					}
					$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
					include "../themes/$currentTheme/layouts/chat/newMessage.php";
				}
				/**/
			} else {
				echo '404';
			}
		}
	}
/* Insert Live Message */
if ($type == 'livemessage') {
    if (
        isset($_POST['val']) && !empty($_POST['val']) &&
        isset($_POST['id']) && !empty($_POST['id']) &&
        trim($_POST['val']) !== '' && trim($_POST['id']) !== ''
    ) {
        $liveID = mysqli_real_escape_string($db, $_POST['id']);
        $liveMessageRaw = rawurldecode($_POST['val']);
        $liveMessage = mysqli_real_escape_string($db, $liveMessageRaw);

        if (empty($liveMessage) || trim($liveMessage) == '') {
            exit('404');
        }

        $lmData = $iN->iN_InsertLiveMessage($liveID, $iN->iN_Secure($liveMessage), $userID);

        if ($lmData) {
            $messageID = $lmData['cm_id'];
            $messageLiveID = $lmData['cm_live_id'];
            $messageLiveUserID = $lmData['cm_iuid_fk'];
            $messageLiveTime = $lmData['cm_time'];
            $liveMessage = rawurldecode($lmData['cm_message']); // decode again from DB (if needed)

            $msgData = $iN->iN_GetUserDetails($messageLiveUserID);
            $msgUserName = $msgData['i_username'];
            $msgUserFullName = $msgData['i_user_fullname'];

            // Only return message block, but avoid double-append in JS
            echo '
            <div class="gElp9 flex_ tabing_non_justify eo2As cUq_' . iN_HelpSecure($messageID) . '" id="' . iN_HelpSecure($messageID) . '">
                <a href="' . iN_HelpSecure($msgUserName) . '">' . iN_HelpSecure($msgUserFullName) . '</a>' . $iN->sanitize_output($liveMessage, $base_url) . '
            </div>';
        }
    }
}
/*Add Sticker*/
	if ($type == 'message_sticker') {
		if (isset($_POST['id']) && isset($_POST['pi'])) {
			$stickerID = mysqli_real_escape_string($db, $_POST['id']);
			$chatID = mysqli_real_escape_string($db, $_POST['pi']);
			$getStickerUrlandID = $iN->iN_getSticker($stickerID);
			if ($getStickerUrlandID) {
				$data = array(
					'st_id' => $getStickerUrlandID['sticker_id'],
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			}
		}
	}
	if ($type == 'message_image_upload') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			foreach ($_FILES['ciuploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['ciuploading']['name'][$iname]);
				$size = $_FILES['ciuploading']['size'][$iname];
				$conID = mysqli_real_escape_string($db, $_POST['c']);
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
				if (in_array($ext, $valid_formats)) {
				    $maxUploadSizeInBytes = $availableUploadFileSize * 1048576; // 1 MB = 1024 * 1024 = 1048576 byte
					if ($size > 0 && $size <= $maxUploadSizeInBytes) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['ciuploading']['tmp_name'][$iname];
						$mimeType = $_FILES['ciuploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'video') {
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
								$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
								$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.png';
								$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';

								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
								if ($ext == 'mpg') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'mov') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
									//$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -q:v 0 -y $convertUrl 2>&1");
								} else if ($ext == 'wmv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'avi') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'webm') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -crf 1 -c:v libx264 $convertUrl");
								} else if ($ext == 'mpeg') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'flv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:a aac -strict -2 -b:a 128k -c:v libx264 -profile:v baseline $convertUrl");
								} else if ($ext == 'm4v') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy $convertUrl");
								} else if ($ext == 'mkv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl");
								}

								$cmd = shell_exec("$ffmpegPath -ss 00:00:01 -i $UploadedFilePath -c copy -t 00:00:04 $xVideoFirstPath");
								$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -ss 00:00:01.000 -vframes 1 $videoTumbnailPath");
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.png';
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.png';
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.png';
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit('You uploaded a video in '.$ext.' video format and ffmpeg could not create a tumbnail from the video.  You need to contact your server administration about this. ');
								}
								/*CHECK AMAZON S3 AVAILABLE*/
								if ($s3Status == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    // Upload full video
                                    $theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $getFilename);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        // Upload preview video
                                        $thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                        $key = basename($thexName);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body' => fopen($thexName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to /xvideos
                                        $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                        $key = basename($thevTumbnail);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body' => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to /files
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }
                                }else if ($WasStatus == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    // Upload full video
                                    $theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $getFilename);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        // Upload preview video
                                        $thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                        $key = basename($thexName);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body' => fopen($thexName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to /xvideos
                                        $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                        $key = basename($thevTumbnail);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body' => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to /files
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }
                                }else if($digitalOceanStatus == '1'){
									$theName = '../uploads/files/' . $d . '/' . $getFilename;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($theName, "public");
									/**/
									$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thexName, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									if($upload){
										$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $UploadedFileName . '.png';
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									}
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								 } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
								}
								/**/
								$ext = 'mp4';
							} else if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
								}else{
									exit('Upload Failed!');
								}
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
										/*rmdir($uploadFile . $d);*/
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if ($WasStatus == '1') {
                                    $publicAccessErrorShown = false; // prevent duplicate error messages

                                    // Upload main thumbnail
                                    $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                    $key = basename($thevTumbnail);

                                    try {
                                        $result = $s3->putObject([
                                            'Bucket' => $WasBucket,
                                            'Key'    => 'uploads/files/' . $d . '/' . $key,
                                            'Body'   => fopen($thevTumbnail, 'r'),
                                            'CacheControl' => 'max-age=3153600',
                                            // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                        ]);
                                        $UploadSourceUrl = $result->get('ObjectURL');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        // rmdir($uploadFile . $d);
                                    } catch (Aws\S3\Exception\S3Exception $e) {
                                        $awsMsg = $e->getAwsErrorMessage();
                                        echo "There was an error uploading the file: $awsMsg<br>";
                                        if (!$publicAccessErrorShown && str_contains($awsMsg, 'Public use of objects is not allowed')) {
                                            echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                            $publicAccessErrorShown = true;
                                        }
                                    }

                                    // Upload pixel version
                                    $thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                    $key = basename($thevTumbnail);

                                    try {
                                        $result = $s3->putObject([
                                            'Bucket' => $WasBucket,
                                            'Key'    => 'uploads/pixel/' . $d . '/' . $key,
                                            'Body'   => fopen($thevTumbnail, 'r'),
                                            'CacheControl' => 'max-age=3153600',
                                            // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                        ]);
                                        $UploadSourceUrl = $result->get('ObjectURL');
                                        @unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
                                        // rmdir($xImages . $d);
                                    } catch (Aws\S3\Exception\S3Exception $e) {
                                        $awsMsg = $e->getAwsErrorMessage();
                                        echo "There was an error uploading the file: $awsMsg<br>";
                                        if (!$publicAccessErrorShown && str_contains($awsMsg, 'Public use of objects is not allowed')) {
                                            echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                            $publicAccessErrorShown = true;
                                        }
                                    }

                                }else if ($digitalOceanStatus == '1') {
                                	// Initialize the DigitalOcean Spaces connection
                                	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);

                                	// 1. Upload the original file
                                	$localPath1 = '../uploads/files/' . $d . '/' . $getFilename;
                                	$remoteKey1 = 'uploads/files/' . $d . '/' . $getFilename;
                                	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                                	// 2. Upload the .mp4 video file
                                	$localPath2 = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                	$remoteKey2 = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                	$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                                	// 3. Upload the .png thumbnail file
                                	$localPath3 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.png';
                                	$remoteKey3 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.png';
                                	$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                                	// If upload was successful, generate the public URL and delete local temp file
                                	if ($upload) {
                                		$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey3;
                                		@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                	}
                                } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							/**/
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedMessageFiles($userID, $conID, $pathFile, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedMessageFilesIDs($userID, $pathFile);
							/*AMAZON S3*/
							echo iN_HelpSecure($getUploadedFileID['upload_id']) . ',';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}

/*Load More Messages*/
	if ($type == 'moreMessage') {
		if (isset($_POST['ch']) && isset($_POST['last'])) {
			$chatID = mysqli_real_escape_string($db, $_POST['ch']);
			$lastMessageID = mysqli_real_escape_string($db, $_POST['last']);
			$conversationData = $iN->iN_GetChatMessages($userID, $chatID, $lastMessageID, $scrollLimit);
			include "../themes/$currentTheme/layouts/chat/loadMoreMessages.php";
		}
	}
/*Get new Message*/
	if ($type == 'getNewMessage') {
		if (isset($_POST['ci']) && isset($_POST['to']) && isset($_POST['lm'])) {
			$conversationID = mysqli_real_escape_string($db, $_POST['ci']);
			$toUser = mysqli_real_escape_string($db, $_POST['to']);
			$lastMessage = mysqli_real_escape_string($db, $_POST['lm']);
			$insertData = $iN->iN_GetUserNewMessage($userID, $conversationID, $toUser, $lastMessage);
			/**/
			if ($insertData) {
				$cMessageID = $insertData['con_id'];
				$cUserOne = $insertData['user_one'];
				$cUserTwo = $insertData['user_two'];
				$cMessage = $insertData['message'];
				$mSeenStatus = $insertData['seen_status'];
				$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
				$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				$privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
				$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
				$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
				$cMessageTime = $insertData['time'];
				$ip = $iN->iN_GetIPAddress();
				$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
				if ($query && $query['status'] == 'success') {
					date_default_timezone_set($query['timezone']);
				}
				$message_time = date("c", $cMessageTime);
				$convertMessageTime = strtotime($message_time);
				$netMessageHour = date('H:i', $convertMessageTime);
				$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
				$msgDots = '';
				$imStyle = '';
				$seenStatus = '';
				if ($cUserOne == $userID) {
					$mClass = 'me';
					$msgOwnerID = $cUserOne;
					$lastM = '';
					$timeStyle = 'msg_time_me';
					if (!empty($cFile)) {
						$imStyle = 'mmi_i';
					}
					$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					if ($mSeenStatus == '1') {
						$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					}
					if($gifMoney){
                        $SGifMoneyText = preg_replace( '/{.*?}/', $cMessage, $LANG['youSendGifMoney']);
                    }
				} else {
					$mClass = 'friend';
					$msgOwnerID = $cUserOne;
					$lastM = 'mm_' . $msgOwnerID;
					if (!empty($cFile)) {
						$imStyle = 'mmi_if';
					}
					if($gifMoney){
                        $msgOwnerFullName = $iN->iN_UserFullName($msgOwnerID);
                        $SGifMoneyText = $iN->iN_TextReaplacement($LANG['sendedGifMoney'],[$msgOwnerFullName , $cMessage]);
                    }
					$timeStyle = 'msg_time_fri';
				}
				$styleFor = '';
				if ($cStickerUrl) {
					$styleFor = 'msg_with_sticker';
					$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
				}
				if ($cGifUrl) {
					$styleFor = 'msg_with_gif';
					$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
				}
				$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
				if($privatePrice && $privateStatus == 'closed' && $mClass != 'me'){
                    include "../themes/$currentTheme/layouts/chat/privateMessage.php";
				}else{
					include "../themes/$currentTheme/layouts/chat/newMessage.php";
				}
			}
			/**/
		}
	}
/*Send User Typing*/
	if ($type == 'utyping') {
		if (isset($_POST['ci']) && isset($_POST['to'])) {
			$conversationID = mysqli_real_escape_string($db, $_POST['ci']);
			$toUserID = mysqli_real_escape_string($db, $_POST['to']);
			$time = time() . '_' . $userID;
			$updateTypingStatus = $iN->iN_UpdateTypingStatus($userID, $conversationID, $time);
		}
	}
/*Check Typeing*/
	if ($type == 'typing') {
		if (isset($_POST['ci']) && isset($_POST['to']) && $_POST['ci'] !== '' && $_POST['to'] !== '' && !empty($_POST['ci']) && !empty($_POST['to'])) {
			$conversationID = mysqli_real_escape_string($db, $_POST['ci']);
			$toUser = mysqli_real_escape_string($db, $_POST['to']);
			$getTypingStatus = $iN->iN_GetTypingStatus($toUser, $conversationID);
			$messageSeenStatus = $iN->iN_CheckLastMessageSeenOrNot($conversationID, $toUser, $userID);
			$iN->iN_UpdateMessageSeenStatus($conversationID, $toUser, $userID);
			$beforeUnderscore = substr($getTypingStatus, 0, strpos($getTypingStatus, "_"));
			$afterUnderscore = substr($getTypingStatus, strrpos($getTypingStatus, '_') + 1);
			$ip = $iN->iN_GetIPAddress();
			$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
			if ($query && $query['status'] == 'success') {
				date_default_timezone_set($query['timezone']);
			}
			$getToUserData = $iN->iN_GetUserDetails($toUser);
			$toUserLastLoginTime = $getToUserData['last_login_time'];
			$lastSeen = date("c", $toUserLastLoginTime);
			$OnlineStatus = date("c", $toUserLastLoginTime);
			/*10 Second Ago for Typing*/
			$SecondBefore = time() - 10;
			/*180 Second Ago for Online - Offline Status*/
			$oStatus = time() - 35;
			$timeStatus = '';
			if ($afterUnderscore != $userID) {
				if ($beforeUnderscore > $SecondBefore) {
					$timeStatus = $LANG['typing'];
				} else {
					if ($toUserLastLoginTime > $oStatus) {
						$timeStatus = $LANG['online'];
					} else {
						$timeStatus = $LANG['last_seen'] . date('H:i', strtotime($OnlineStatus));
					}
				}
			} else {
				$timeStatus = $LANG['last_seen'] . date('H:i', strtotime($OnlineStatus));
			}
			$data = array(
				'timeStatus' => $timeStatus,
				'seenStatus' => $messageSeenStatus,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	if ($type == 'allPosts' || $type == 'moreexplore' || $type == 'premiums' || $type == 'morepremium' || $type == 'friends' || $type == 'morepurchased' || $type == 'purchasedpremiums' || $type == 'moreboostedposts' || $type == 'boostedposts' || $type == 'trendposts' || $type == 'moretrendposts') {
		$page = $type;
		include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
	}
	if ($type == 'creators') {
		if (isset($_POST['last']) && isset($_POST['p'])) {
			$pageCreator = mysqli_real_escape_string($db, $_POST['p']);
			$lastPostID = mysqli_real_escape_string($db, $_POST['last']);
			include "../themes/$currentTheme/layouts/loadmore/moreCreator.php";
		}
	}
/*More Comment*/
	if ($type == 'moreComment') {
		if (isset($_POST['id'])) {
			$userPostID = mysqli_real_escape_string($db, $_POST['id']);
			$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
			if ($getUserComments) {
				foreach ($getUserComments as $comment) {
					$commentID = $comment['com_id'];
					$commentedUserID = $comment['comment_uid_fk'];
					$Usercomment = $comment['comment'];
					$commentTime = isset($comment['comment_time']) ? $comment['comment_time'] : NULL;
					$corTime = date('Y-m-d H:i:s', $commentTime);
					$commentFile = isset($comment['comment_file']) ? $comment['comment_file'] : NULL;
					$stickerUrl = isset($comment['sticker_url']) ? $comment['sticker_url'] : NULL;
					$gifUrl = isset($comment['gif_url']) ? $comment['gif_url'] : NULL;
					$commentedUserIDFk = isset($comment['iuid']) ? $comment['iuid'] : NULL;
					$commentedUserName = isset($comment['i_username']) ? $comment['i_username'] : NULL;
					$commentedUserFullName = isset($comment['i_user_fullname']) ? $comment['i_user_fullname'] : NULL;
					$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
					$commentedUserGender = isset($comment['user_gender']) ? $comment['user_gender'] : NULL;
					if ($commentedUserGender == 'male') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'female') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'couple') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					}
					$commentedUserLastLogin = isset($comment['last_login_time']) ? $comment['last_login_time'] : NULL;
					$commentedUserVerifyStatus = isset($comment['user_verified_status']) ? $comment['user_verified_status'] : NULL;
					$cuserVerifiedStatus = '';
					if ($commentedUserVerifyStatus == '1') {
						$cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
					}
					$commentLikeBtnClass = 'c_in_like';
					$commentLikeIcon = $iN->iN_SelectedMenuIcon('17');
					$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
					if ($logedIn != 0) {
						$checkCommentLikedBefore = $iN->iN_CheckCommentLikedBefore($userID, $userPostID, $commentID);
						$checkCommentReportedBefore = $iN->iN_CheckCommentReportedBefore($userID, $commentID);
						if ($checkCommentLikedBefore == '1') {
							$commentLikeBtnClass = 'c_in_unlike';
							$commentLikeIcon = $iN->iN_SelectedMenuIcon('18');
						}
						if ($checkCommentReportedBefore == '1') {
							$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
						}
					}
					$stickerComment = '';
					$gifComment = '';
					if ($stickerUrl) {
						$stickerComment = '<div class="comment_file"><img src="' . $stickerUrl . '"></div>';
					}
					if ($gifUrl) {
						$gifComment = '<div class="comment_gif_file"><img src="' . $gifUrl . '"></div>';
					}
					$checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
					$cUType = '';
					if($checkUserIsCreator){
						$cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
					}
					include "../themes/$currentTheme/layouts/posts/comments.php";
				}
			}
		}
	}
	if ($type == 'searchCreator') {
		if (isset($_POST['s'])) {
			$searchValue = mysqli_real_escape_string($db, $_POST['s']);
			$searchValueFromData = $iN->iN_GetSearchResult($iN->iN_Secure($searchValue), $showingNumberOfPost, $whicUsers);
			include "../themes/$currentTheme/layouts/header/searchResults.php";
		}
	}
/*Create new Conversation*/
	if ($type == 'newMessageMe') {
		if (isset($_POST['user'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['user']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			if ($checkUserExist) {
				$getToUserData = $iN->iN_GetUserDetails($iuID);
				$f_userfullname = $getToUserData['i_user_fullname'];
				$f_userAvatar = $iN->iN_UserAvatar($iuID, $base_url);
				$checkConversationStartedBeforeBetweenTheseUsers = $iN->iN_CheckConversationStartedBeforeBetweenUsers($userID, $iuID);
				if (empty($checkConversationStartedBeforeBetweenTheseUsers) || $checkConversationStartedBeforeBetweenTheseUsers = '' || !isset($checkConversationStartedBeforeBetweenTheseUsers)) {
					include "../themes/$currentTheme/layouts/popup_alerts/createMessage.php";
				}
			}
		}
	}
/*Createa New First Message Between Two User*/
	if ($type == 'newfirstMessage') {
		if (isset($_POST['u']) && isset($_POST['fm'])) {
			$user = mysqli_real_escape_string($db, $_POST['u']);
			$firstMessage = mysqli_real_escape_string($db, $_POST['fm']);
			if (empty($firstMessage) || $firstMessage == '' || !isset($firstMessage) || strlen(trim($firstMessage)) == 0) {
				exit('404');
			}
			$insertNewMessageAndCreateConversation = $iN->iN_CreateConverationAndInsertFirstMessage($userID, $user, $iN->iN_Secure($firstMessage));
			if ($insertNewMessageAndCreateConversation) {
				echo iN_HelpSecure($base_url) . 'chat?chat_width=' . $insertNewMessageAndCreateConversation;
				$userDeviceKey = $iN->iN_GetuserDetails($user);
				$toUserName = $userDeviceKey['i_username'];
				$oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
				$msgTitle = $iN->iN_Secure($LANG['you_have_a_new_message']);
				$msgBody = $iN->iN_Secure($LANG['click_to_continue_conversation']);
				$URL = iN_HelpSecure($base_url) . 'chat?chat_width=' . $insertNewMessageAndCreateConversation;
				if($oneSignalUserDeviceKey){
				  $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
				}
			} else {
				echo '404';
			}
		}
	}
/* Update Dark/Light theme */
if ($type == 'updateTheme') {
    // allow-list; fall back if $themes is missing
    $allowed = (isset($themes) && is_array($themes) && count($themes))
        ? $themes
        : ['light','dark'];

    if (isset($_POST['theme']) && in_array($_POST['theme'], $allowed, true)) {
        $uTheme = $iN->iN_Secure($_POST['theme']);
        $ok = $iN->iN_UpdateUserTheme($userID, $uTheme);
        echo $ok ? '200' : '404';
    } else {
        echo '404';
    }
    exit; // IMPORTANT: end the AJAX response cleanly
}

/*Get Fixed Mobile Footer Menu*/
	if ($type == 'fixedMenu') {
		include "../themes/$currentTheme/layouts/widgets/mobileFixedMenu.php";
	}
/*Delete Message*/
	if ($type == 'deleteMessage') {
		if (isset($_POST['id']) && isset($_POST['cid'])) {
			$messageID = mysqli_real_escape_string($db, $_POST['id']);
			$conversationID = mysqli_real_escape_string($db, $_POST['cid']);
			$deleteMessage = $iN->iN_DeleteMessageFromData($userID, $messageID, $conversationID);
			if ($deleteMessage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Delete Conversion*/
	if ($type == 'deleteConversation') {
		if (isset($_POST['id']) && isset($_POST['cid'])) {
			$messageID = mysqli_real_escape_string($db, $_POST['id']);
			$conversationID = mysqli_real_escape_string($db, $_POST['cid']);
			$deleteMessage = $iN->iN_DeleteConversationFromData($userID, $conversationID);
			if ($deleteMessage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Search User From Chat*/
	if ($type == 'searchUser') {
		if (isset($_POST['key'])) {
			$sKey = mysqli_real_escape_string($db, $_POST['key']);
			$searchUser = $iN->iN_SearchChatUsers($userID, $iN->iN_Secure($sKey));
			if ($searchUser) {
				foreach ($searchUser as $sResult) {
					$resultUserID = $sResult['iuid'];
					$resultUserName = $sResult['i_username'];
					$resultUserFullName = $sResult['i_user_fullname'];
					$profileUrl = $base_url . $resultUserName;
					$resultUserAvatar = $iN->iN_UserAvatar($resultUserID, $base_url);
					include "../themes/$currentTheme/layouts/chat/chatSearch.php";
				}
			}
		}
	}
/*Hide Notification*/
	if ($type == 'hideNotification') {
		if (isset($_POST['id'])) {
			$hideID = mysqli_real_escape_string($db, $_POST['id']);
			$hideNot = $iN->iN_HideNotification($userID, $hideID);
			if ($hideNot) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*UN Block User*/
	if ($type == 'unblock') {
		if (isset($_POST['id']) && isset($_POST['u'])) {
			$unBlockID = mysqli_real_escape_string($db, $_POST['id']);
			$unBlockUserID = mysqli_real_escape_string($db, $_POST['u']);
			$unBlock = $iN->iN_UnBlockUser($userID, $unBlockID, $unBlockUserID);
			if ($unBlock) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Edit May Page*/
	if ($type == 'editMyPass') {
		$currentPassword = mysqli_real_escape_string($db, $_POST['crn_password']);
		$newPassword = mysqli_real_escape_string($db, $_POST['nw_password']);
		$confirmNewPassword = mysqli_real_escape_string($db, $_POST['confirm_pass']);
		if (!empty($currentPassword) && $currentPassword != '' && isset($currentPassword)) {
			$userCurrentPass = $iN->iN_GetUserDetails($userID);
			$passUser = $userCurrentPass['i_password'];
			if (preg_match('/\s/', $currentPassword) || preg_match('/\s/', $newPassword) || preg_match('/\s/', $confirmNewPassword)) {
				exit('6');
			}
			if (sha1(md5($currentPassword)) != $passUser) {
				exit('1');
			}
			if (strlen($newPassword) < 6 || strlen($confirmNewPassword) < 6 || strlen($currentPassword) < 6) {
				exit('5');
			}
			if (!empty($newPassword) && $newPassword != '' && isset($newPassword) && !empty($confirmNewPassword) && $confirmNewPassword != '' && isset($confirmNewPassword)) {
				if ($newPassword != $confirmNewPassword) {
					exit('2');
				} else {
					$newPassword = sha1(md5($newPassword));
					$updateNewPassword = $iN->iN_UpdatePassword($userID, $iN->iN_Secure($newPassword));
					if ($updateNewPassword) {
						echo iN_HelpSecure($base_url) . 'logout.php';
					} else {
						exit('404');
					}
				}
			} else {
				exit('4');
			}
		} else {
			exit('3');
		}
	}
/*Update Preferences*/
	if ($type == 'p_preferences') {
		if (isset($_POST['notit']) && isset($_POST['sType'])) {
			$setValue = mysqli_real_escape_string($db, $_POST['notit']);
			$setType = mysqli_real_escape_string($db, $_POST['sType']);
			if ($setType == 'email_not') {
				$updateEmailStatus = $iN->iN_UpdateEmailNotificationStatus($userID, $setValue);
				if ($updateEmailStatus) {
					echo '200';
				} else {
					echo '404';
				}
			} else if ($setType == 'message_not') {
				$updateMessageStatus = $iN->iN_UpdateMessageSendStatus($userID, $setValue);
				if ($updateMessageStatus) {
					echo '200';
				} else {
					echo '404';
				}
			} else if ($setType == 'show_hide_profile') {
				$updateShowHideProfile = $iN->iN_UpdateShowHidePostsStatus($userID, $setValue);
				if ($updateShowHideProfile) {
					echo '200';
				} else {
					echo '404';
				}
			} else if($setType == 'who_send_message_not'){
				$updateWhoCanSendMessage = $iN->iN_UpdateWhoCanSendYouAMessage($userID, $setValue);
				if ($updateWhoCanSendMessage) {
					echo '200';
				} else {
					echo '404';
				}
			}
		}
	}
/*Call Paid Live Streaming Box*/
	if ($type == 'paidLive') {
		$liveStreamNotForNonCreators = '<div class="ll_live_not flex_ alignItem">' . html_entity_decode($iN->iN_SelectedMenuIcon('32')) . ' ' . iN_HelpSecure($LANG['only_creators_']) . '</div>';
		if ($certificationStatus == '2' && $validationStatus == '2' && $conditionStatus == '2') {
			include "../themes/$currentTheme/layouts/popup_alerts/createaPaidLiveStreaming.php";
		} else {
			$currentTime = time();
			$finishTime = $currentTime + 60 * $freeLiveTime;
			$l_Time = $iN->iN_GetLastLiveFinishTime($userID);
			include "../themes/$currentTheme/layouts/popup_alerts/createaFreeLiveStreaming.php";
		}
	}
/*Call Free Live Streaming Box*/
	if ($type == 'freeLive') {
		$currentTime = time();
		$finishTime = $currentTime + 60 * $freeLiveTime;
		$l_Time = $iN->iN_GetLastLiveFinishTime($userID);
		$liveStreamNotForNonCreators = '';
		include "../themes/$currentTheme/layouts/popup_alerts/createaFreeLiveStreaming.php";
	}
/*Create a Free Live Streaming*/
	if ($type == 'createFreeLiveStream') {
		if (isset($_POST['lTitle']) && !empty($_POST['lTitle'])) {
			$liveStreamingTitle = mysqli_real_escape_string($db, $_POST['lTitle']);
			$rand = rand(1111111, 9999999);
			$channelName = "stream_" . $userID . "_" . $rand;
			if (strlen($liveStreamingTitle) < 5 || strlen($liveStreamingTitle) > 32) {
				$data = array(
					'status' => '4',
					'start' => '',
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			$createFreeLiveStreaming = $iN->iN_CreateAFreeLiveStreaming($userID, $liveStreamingTitle, $freeLiveTime, $channelName);
			if ($createFreeLiveStreaming) {
				if ($s3Status == 1) {
					//$rect = $iN->iN_StartCloudRecording(1, $s3Region, $s3Bucket, $s3Key, $s3SecretKey, $streamingName, $uid, $liveID, $agoraAppID, $agoraCustomerID, $agoraCertificate);
				}
				$data = array(
					'status' => '200',
					'start' => $base_url . 'live/' . $userName,
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			} else {
				$data = array(
					'status' => '404',
					'start' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		} else {
			$data = array(
				'status' => 'require',
				'start' => '',
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
	}
	if ($type == 'l_like') {
		if (isset($_POST['post'])) {
			$postID = mysqli_real_escape_string($db, $_POST['post']);
			$likePost = $iN->iN_LiveLike($userID, $postID);
			$status = 'lin_like';
			$pLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePost) {
				$status = 'lin_unlike';
				$pLike = $iN->iN_SelectedMenuIcon('18');
			}
			$likeSum = $iN->iN_TotalLiveLiked($postID);
			if ($likeSum == 0) {
				$likeSum = '';
			} else {
				$likeSum = $likeSum;
			}
			$data = array(
				'status' => $status,
				'like' => $pLike,
				'likeCount' => $likeSum,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
/* Create a Paid Live Streaming */
/* Create a Paid Live Streaming */
if ($type == 'createPaidLiveStream') {
    if (isset($_POST['lTitle']) && !empty($_POST['lTitle']) &&
        isset($_POST['pointfee']) && !empty($_POST['pointfee'])) {

        $liveStreamingTitle = mysqli_real_escape_string($db, $_POST['lTitle']);
        $liveStreamFee = (int)($_POST['pointfee'] ?? 0);
        if ($liveStreamFee < 0) { $liveStreamFee = 0; }

        // ---- MAX check (Paid Live) ----
        $__MAX_LIVE = (defined('MF_MAX_PAID_LIVE_PRICE_POINTS') && MF_MAX_PAID_LIVE_PRICE_POINTS > 0)
            ? (int)MF_MAX_PAID_LIVE_PRICE_POINTS : 5000;

        if ($liveStreamFee > $__MAX_LIVE) {
            $msg = sprintf(
                $LANG['err_paid_live_limit'] ?? 
                'Maximum paid live price is %s points.', 
                number_format($__MAX_LIVE)
            );
            echo json_encode(['status'=>'error','msg'=>$msg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rand = rand(1111111, 9999999);
        $channelName = "stream_" . $userID . "_" . $rand;

        $minimumLiveStreamingFee = (int)$minimumLiveStreamingFee;
        if ($liveStreamFee <= 0 || $liveStreamFee < $minimumLiveStreamingFee) {
            $data = ['status' => 'point', 'start' => ''];
            echo json_encode($data);
            exit();
        }

        if ($certificationStatus == '2' && $validationStatus == '2' && $conditionStatus == '2') {
            $createPaidLiveStreaming = $iN->iN_CreateAPaidLiveStreaming(
                $userID, $liveStreamingTitle, $freeLiveTime, $channelName, $liveStreamFee
            );

            if ($createPaidLiveStreaming) {
                $data = ['status'=>'200', 'start'=>$base_url.'live/'.$userName];
                echo json_encode($data, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['status'=>'404','start'=>'']);
            }
        } else {
            echo json_encode(['status'=>'404','start'=>'']);
        }
    } else {
        echo json_encode(['status'=>'require','start'=>'']);
    }
}

/*Purchase Post*/
	if ($type == 'goWalletLive') {
		if (isset($_POST['p']) && isset($_POST['p'])) {
			$purchaseLiveStreamID = mysqli_real_escape_string($db, $_POST['p']);
			$checkLiveID = $iN->iN_CheckLiveIDExist($purchaseLiveStreamID);
			if ($checkLiveID) {
				$liveDetails = $iN->iN_GetLiveStreamingDetailsByID($purchaseLiveStreamID);
				$liveID = $liveDetails['live_id'];
				$liveCreatorWantedCredit = $liveDetails['live_credit'];
				$liveCreator = $liveDetails['live_uid_fk'];
				$liveCreatorDetail = $iN->iN_GetUserDetails($liveCreator);
				$liveCreatorUserName = $liveCreatorDetail['i_username'];

				$translatePointToMoney = $liveCreatorWantedCredit * $onePointEqual;
				$adminEarning = $translatePointToMoney * ($adminFee / 100);
				$userEarning = $translatePointToMoney - $adminEarning;

				if ($userCurrentPoints >= $liveCreatorWantedCredit && $userID != $liveCreator) {
					$buyLiveStream = $iN->iN_BuyLiveStreaming($userID, $liveCreator, $liveID, $translatePointToMoney, $adminEarning, $userEarning, $adminFee, $liveCreatorWantedCredit);
					if ($buyLiveStream) {
						echo iN_HelpSecure($base_url) . 'live/' . $liveCreatorUserName;
					} else {
						echo 'Something wrong';
					}
				} else {
					echo iN_HelpSecure($base_url) . 'purchase/purchase_point';
				}
			}
		}
	}
/*More Paid Live Streamins or Free Paid Live Streamins*/
	if ($type == 'paid' || $type == 'free') {
		if (isset($_POST['last'])) {
			$liveListType = $type;
			include "../themes/$currentTheme/layouts/live/live_list.php";
		}
	}
	if ($type == 'pLivePurchase') {
		if (isset($_POST['purchase']) && $_POST['purchase'] != '' && !empty($_POST['purchase'])) {
			$liveID = mysqli_real_escape_string($db, $_POST['purchase']);
			$checkliveExist = $iN->iN_CheckLiveIDExist($liveID);
			if ($checkliveExist) {
				$liData = $iN->iN_GetLiveStreamingDetailsByID($liveID);
				$liveCreatorID = $liData['live_uid_fk'];
				$liveCreatorAvatar = $iN->iN_UserAvatar($liveCreatorID, $base_url);
				$liveCredit = isset($liData['live_credit']) ? $liData['live_credit'] : NULL;
				if ($userID != $liveCreatorID) {
					include "../themes/$currentTheme/layouts/popup_alerts/purchaseLiveStream.php";
				}
			}
		}
	}
	if ($type == 'unSub') {
		if (isset($_POST['u']) && !empty($_POST['u'])) {
			$ui = mysqli_real_escape_string($db, $_POST['u']);
			$checkUserExist = $iN->iN_CheckUserExist($ui);
			$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $ui);
			if ($friendsStatus == 'subscriber') {
				include "../themes/$currentTheme/layouts/popup_alerts/sureUnSubscribe.php";
			}
		}
	}
	if ($type == 'unSubscribe') {
		if (isset($_POST['id'])) {
			$uID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$status = '404';
					$redirect = $base_url . 'settings?tab=subscriptions';
					if ($friendsStatus == 'subscriber') {
						if($subscriptionType == '1'){
							\Stripe\Stripe::setApiKey($stripeKey);
							$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);

							$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
							$subscription->cancel();
							$iN->iN_UnSubscriberUser($userID, $uID,$unSubscribeStyle);
							$status = '200';
						}else if($subscriptionType == '3'){
                            include_once("../includes/authorizeCancelSubs.php");
							$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$iN->iN_UnSubscriberUser($userID, $uID,$unSubscribeStyle);
							if(!defined('DONT_RUN_SAMPLES'))
                            cancelSubscription($paymentSubscriptionID,$autName, $autKey);
						}
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	if ($type == 'unSubP') {
		if (isset($_POST['u']) && !empty($_POST['u'])) {
			$ui = mysqli_real_escape_string($db, $_POST['u']);
			$checkUserExist = $iN->iN_CheckUserExist($ui);
			$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $ui);
			if ($friendsStatus == 'subscriber') {
				include "../themes/$currentTheme/layouts/popup_alerts/sureUnSubscribePoint.php";
			}
		}
	}
	if ($type == 'unSubscribePoint') {
		if (isset($_POST['id'])) {
			$uID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$status = '404';
					$redirect = $base_url . 'settings?tab=subscriptions';
					if ($friendsStatus == 'subscriber') {
						$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
						$subscriptionID = $getSubsData['subscription_id'];
						$iN->iN_UpdateSubscriptionStatus($subscriptionID);
						$iN->iN_UnSubscriberUser($userID, $uID,$unSubscribeStyle);
						$status = '200';
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	/*Finish Live Streaming*/
	if($type == 'finishLive'){
      if(isset($_POST['lid']) && !empty($_POST['lid']) && $_POST['lid'] != ''){
         $liveID = mysqli_real_escape_string($db, $_POST['lid']);
		 $finishLiveStreaming = $iN->iN_FinishLiveStreaming($userID, $liveID);
		 if($finishLiveStreaming){
             echo 'finished';
		 }
	  }
	}
	/*Block Country*/
	if($type == 'bCountry'){
      if(isset($_POST['c']) && array_key_exists($_POST['c'],$COUNTRIES)){
         $blockingCountryCode = mysqli_real_escape_string($db, $_POST['c']);
		 $checkCountryCodeBlockedBefore = $iN->iN_CheckCountryBlocked($userID, $blockingCountryCode);
		 if(!$checkCountryCodeBlockedBefore){
            $insertCountryCodeInBlockedList = $iN->iN_InsertCountryInBlockList($userID, $iN->iN_Secure($blockingCountryCode));
			if($insertCountryCodeInBlockedList){
              echo '1';
			}
		 }else{
			$removeCountryCodeInBlockedList = $iN->iN_RemoveCountryInBlockList($userID, $iN->iN_Secure($blockingCountryCode));
			if($removeCountryCodeInBlockedList){
              echo '0';
			}
		 }
	  }
	}
	/*Open Tip Box*/
if ($type == 'p_tips') {
  if (isset($_POST['tip_u']) && !empty($_POST['tip_u']) && $_POST['tip_u'] !== '') {
    $tipingUserID  = mysqli_real_escape_string($db, $_POST['tip_u']);
    $tipPostID     = isset($_POST['tpid']) ? mysqli_real_escape_string($db, $_POST['tpid']) : '';
    $tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
    $f_userfullname    = $tipingUserDetails['i_user_fullname'];

    // 👇 choose template: profile vs feed
    $tpl = ($tipPostID === '' || $tipPostID === '0' || $tipPostID === 'undefined')
           ? 'sendProfileTipPoint.php'       // your new file (scoped classes, no duplicate IDs)
           : 'sendTipPoint.php';             // existing feed modal

    include "../themes/$currentTheme/layouts/popup_alerts/$tpl";
  }
}

	/* Send Tip (post) */
if ($type == 'p_sendTip') {
  header('Content-Type: application/json; charset=UTF-8');

  if (isset($_POST['tip_u'], $_POST['tipVal']) && $_POST['tip_u'] !== '' && $_POST['tipVal'] !== '') {
    $tiSendingUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
    $tipAmount       = (int)mysqli_real_escape_string($db, $_POST['tipVal']);
    $tipPostID       = isset($_POST['tpid']) ? mysqli_real_escape_string($db, $_POST['tpid']) : '';
    $redirect = '';
    $emountnot = '';
    $status = '';

    if ($tipAmount < (int)$minimumTipAmount) {
      $emountnot = 'notEnough';
    } else {
      if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {
        $netUserEarning = $tipAmount * $onePointEqual;
        $adminEarning   = ($adminFee * $netUserEarning) / 100;
        $userNetEarning = $netUserEarning - $adminEarning;

        $UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning, $adminFee, $adminEarning, $userNetEarning);
        if ($UpdateUsersWallet) {
          $status = 'ok';
        } else {
          $status = '404';
        }
      } else {
        $status = '';
        $emountnot = 'notEnouhCredit';
        $redirect = iN_HelpSecure($base_url) . 'purchase/purchase_point';
      }
    }

    // Prepare response (ONLY JSON! nothing else must echo)
    $resp = [
      'status'   => $status,
      'redirect' => $redirect,
      'enamount' => $emountnot,
    ];
    echo json_encode($resp, JSON_UNESCAPED_SLASHES);
    tipLog('RESP='.json_encode($resp));

    // Do side-effects AFTER echo, but without printing anything.
    if ($status === 'ok') {
      $userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
      $oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : null;
      $msgBody  = $iN->iN_Secure($LANG['send_you_a_tip']);
      $msgTitle = $iN->iN_Secure($LANG['tip_earning']) . $currencys[$defaultCurrency] . $netUserEarning;
      $URL      = $base_url.'settings?tab=dashboard'; // keep name consistent

      if ($oneSignalUserDeviceKey) {
        // NOTE: pass $URL (not $url)
        @ $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $URL, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
      }
    }
    exit; // <- CRUCIAL
  }

  // bad input
  echo json_encode(['status'=>'','enamount'=>'bad_request']);
  exit;
}
	/*Coin Payment*/
	if($type == 'cop'){
      if(isset($_POST['p']) && !empty($_POST['p']) && $_POST['p'] != ''){
         $pointTypeID = mysqli_real_escape_string($db, $_POST['p']);
		 $planData = $iN->GetPlanDetails($pointTypeID);
		 $planAmount = isset($planData['amount']) ? $planData['amount'] : NULL;
		 $planPoint = isset($planData['plan_amount']) ? $planData['plan_amount'] : NULL;
		 if($planAmount){
			require_once('../includes/coinPayment/vendor/autoload.php');
            $currency1 = $defaultCurrency;
			$currency2 = $coinPaymentCryptoCurrency;
			try {
				$cps_api = new CoinpaymentsAPI($coinPaymentPrivateKey, $coinPaymentPublicKey, 'json');
				$information = $cps_api->GetBasicInfo();
				$ipn_url = $base_url.'purchase/purchase_point';
				$cancelUrl = $base_url.'purchase/purchase_point';
				$payBtc = $cps_api->CreateSimpleTransactionWithConversion($planAmount, $currency1, $currency2, $userEmail, $ipn_url, $cancelUrl);
				$txnID = isset($payBtc['result']['txn_id']) ? $payBtc['result']['txn_id'] : NULL;
				$time = time();
				if($txnID){
					$query = mysqli_query($db,"INSERT INTO i_user_payments(payer_iuid_fk,order_key,payment_type,payment_option,payment_time,payment_status,credit_plan_id)VALUES('$userID','$txnID','point','coinpayment','$time','pending','$pointTypeID')") or die(mysqli_error($db));
				}else{
					exit('Check your coinpayment settings from your coinpayment dashboard.');
				}

			} catch (Exception $e) {
				echo 'Error: ' . $e->getMessage();
				exit();
			}
			if ($information['error'] == 'ok') {
				$redirectUrl = $payBtc['result']['checkout_url'];
				$status = '200';
			}else{
				$redirectUrl = '';
				$status = '404';
			}
			$data = array(
				'status' => $status,
				'redirect' => $redirectUrl
			 );
			 $result = json_encode($data);
			 echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		 }
	  }
	}
	if ($type == 'subscribeMeAut') {
		if (isset($_POST['u']) && isset($_POST['pl']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['card'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['u']);
			$planID = mysqli_real_escape_string($db, $_POST['pl']);
			$subscriberName = mysqli_real_escape_string($db, $_POST['name']);
			$subscriberEmail = mysqli_real_escape_string($db, $_POST['email']);
			$creditCardNumber = mysqli_real_escape_string($db, $_POST['card']);
			$expMonth = mysqli_real_escape_string($db, $_POST['exm']);
			$expYear = mysqli_real_escape_string($db, $_POST['exy']);
			$CardCCV = mysqli_real_escape_string($db, $_POST['cccv']);
			$planDetails = $iN->iN_CheckPlanExist($planID, $iuID);
			$expiredData = $expYear.'-'.$expMonth;
			$payment_id = $statusMsg = $api_error = '';
			if ($planDetails) {
				$planType = $planDetails['plan_type'];
				$amount = $planDetails['amount'];
				$planCurrency = $autHorizePaymentCurrency;
				$adminEarning = ($adminFee * $amount) / 100;
				$userNetEarning = $amount - $adminEarning;
				$subscriptionCompleted = $LANG['subscription_description_authorize'];
				$payment_Type = 'authorizenet';
				$planIntervalCount = '1';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
					$intervalLength = '7';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+7 days'));
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
					$intervalLength = '30';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 month'));
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
					$intervalLength = '365';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 year'));
				}

define("AUTHORIZENET_LOG_FILE", "phplog");

function createSubscription($userID,$iuID,$payment_Type,$planID,$planCurrency, $planInterval,$planIntervalCount,$subscriberEmail,$autName, $autKey, $subscriberName,$userName,$intervalLength,$interval_dmy,$creditCardNumber,$expiredData,$amount,$plancreated,$current_period_start,$current_period_end,$adminEarning,$userNetEarning,$subscriptionCompleted)
{
	global $iN;
	/* Create a merchantAuthenticationType object with authentication details
	retrieved from the constants file */
	$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
	$merchantAuthentication->setName($autName);
	$merchantAuthentication->setTransactionKey($autKey);

	// Set the transaction's refId
	$refId = 'ref' . time();

	// Subscription Type Info
	$subscription = new AnetAPI\ARBSubscriptionType();
	$subscription->setName("Sample Subscription");

	$interval = new AnetAPI\PaymentScheduleType\IntervalAType();
	$interval->setLength($intervalLength);
	$interval->setUnit($interval_dmy);

	$paymentSchedule = new AnetAPI\PaymentScheduleType();
	$paymentSchedule->setInterval($interval);
	$paymentSchedule->setStartDate(new DateTime('now'));
	$paymentSchedule->setTotalOccurrences("12");
	$paymentSchedule->setTrialOccurrences("1");

	$subscription->setPaymentSchedule($paymentSchedule);
	$subscription->setAmount($amount);
	$subscription->setTrialAmount("0.00");

	$creditCard = new AnetAPI\CreditCardType();

	$creditCard->setCardNumber($creditCardNumber);
	$creditCard->setExpirationDate($expiredData);

	$payment = new AnetAPI\PaymentType();
	$payment->setCreditCard($creditCard);
	$subscription->setPayment($payment);

	$order = new AnetAPI\OrderType();
	$order->setInvoiceNumber("1234354");
	$order->setDescription($subscriptionCompleted);
	$subscription->setOrder($order);

	$billTo = new AnetAPI\NameAndAddressType();
	$billTo->setFirstName($subscriberName);
	$billTo->setLastName($userName);

	$subscription->setBillTo($billTo);

	$request = new AnetAPI\ARBCreateSubscriptionRequest();
	$request->setmerchantAuthentication($merchantAuthentication);
	$request->setRefId($refId);
	$request->setSubscription($subscription);
	$controller = new AnetController\ARBCreateSubscriptionController($request);

	$response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

	if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
	{
		$custID = $response->getSubscriptionId();
		$planStatus = 'active';
		$insertSubscription = $iN->iN_InsertUserSubscription($userID, $iuID, $payment_Type, $subscriberName, $custID, $custID, $planID, $amount, $adminEarning, $userNetEarning, $planCurrency, $planInterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus);

		 if ($insertSubscription) {
			echo '200';
		} else {
			echo iN_HelpSecure($LANG['contact_site_administrator']);
		}
	}
	else
	{
		echo "ERROR :  Invalid response\n";
		$errorMessages = $response->getMessages()->getMessage();
		echo "Response : " .$errorMessages[0]->getText() . "\n";
	}

	return $response;
}

if(!defined('DONT_RUN_SAMPLES'))
	createSubscription($userID,$iuID,$payment_Type,$planID,$planCurrency, $planInterval,$planIntervalCount,$subscriberEmail,$autName, $autKey,$subscriberName,$userName,$intervalLength,$interval_dmy,$creditCardNumber,$expiredData,$amount,$plancreated,$current_period_start,$current_period_end,$adminEarning,$userNetEarning,$subscriptionCompleted);
    }
 }
}
/*Send Tip*/
if($type == 'p_sendGift'){
	if(isset($_POST['tip_u']) && isset($_POST['tipTyp']) && isset($_POST['lid'])){
	   $giftLiveOwnerUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
	   $giftTypeID = mysqli_real_escape_string($db, $_POST['tipTyp']);
	   $cLiveID = mysqli_real_escape_string($db, $_POST['lid']);
	   if($iN->CheckLivePlanExist($giftTypeID) == '1' && $iN->iN_CheckLiveIDExist($cLiveID) == '1'){
	   $getLiveGiftDataFromID = $iN->GetLivePlanDetails($giftTypeID);
	   $liveWantedCoin = isset($getLiveGiftDataFromID['gift_point']) ? $getLiveGiftDataFromID['gift_point'] : NULL;
	   $liveWantedMoney = isset($getLiveGiftDataFromID['gift_money_equal']) ? $getLiveGiftDataFromID['gift_money_equal'] : NULL;
	   $liveAnimationImage = isset($getLiveGiftDataFromID['gift_money_animation_image']) ? $getLiveGiftDataFromID['gift_money_animation_image'] : NULL;
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   $liveGiftAnimationUrl = '';
		if ($userCurrentPoints >= $liveWantedCoin && $userID != $giftLiveOwnerUserID) {
			$translatePointToMoney = $liveWantedMoney;
			$adminEarning = $translatePointToMoney * ($adminFee / 100);
			$userEarning = $translatePointToMoney - $adminEarning;
			$liveGiftAnimation = $base_url.$liveAnimationImage;
			$liveGiftAnimationUrl = '<div class="live_animation_wrapper"><div class="live_an_img"><img src="'.$liveGiftAnimation.'"></div></div>';
			$UpdateUsersWallet = $iN->iN_UpdateUsersWalletsForLiveGift($userID,$cLiveID, $giftLiveOwnerUserID, $giftTypeID, $liveWantedCoin,$adminEarning, $userEarning, $liveWantedMoney);
			$liveOwnUserData = $iN->iN_GetUserDetails($userID);
		    $userCurrentPoints = isset($liveOwnUserData['wallet_points']) ? $liveOwnUserData['wallet_points'] : '0';
			if($UpdateUsersWallet){
				$status = 'ok';
			}else{
				$status = '404';
			}
		}else{
			$status = '';
			$emountnot = 'notEnouhCredit';
			$redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		}
	   $data = array(
		  'status' => $status,
		  'redirect' => $redirect,
		  'enamount' => $emountnot,
		  'giftAnimation' => $liveGiftAnimationUrl,
		  'current_balance' => number_format($userCurrentPoints)
	   );
	   $result = json_encode($data);
	   echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
	   if($status == 'ok'){
           $userDeviceKey = $iN->iN_GetuserDetails($giftLiveOwnerUserID);
		   $toUserName = $userDeviceKey['i_username'];
		   $oneSignalUserDeviceKey = $userDeviceKey['device_key'];
		   $msgBody = $iN->iN_Secure($LANG['send_you_a_gift']);
		   $msgTitle = $iN->iN_Secure($LANG['your_gift_is']).$currencys[$defaultCurrency]. $userEarning;
		   $URL = $base_url.'live'.$toUserName;
		   if($oneSignalUserDeviceKey){
			 $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		   }
	   }
	}
   }
  }
  if($type == 'sndAgCon'){
     /*SEND CONFIRMATIN EMAIL STARTED*/
	 $code = md5(rand(1111, 9999) . time());

		if ($emailSendStatus == '1') {
			$insertNewCode = $iN->iN_InsertNewVerificationCode($iN->iN_Secure($userID), $iN->iN_Secure($code));
			if ($insertNewCode)
				if ($smtpOrMail == 'mail') {
					$mail->IsMail();
				} else if ($smtpOrMail == 'smtp') {
					$mail->isSMTP();
					$mail->Host = $smtpHost; // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true;
					$mail->Username = $smtpUserName; // SMTP username
					$mail->Password = $smtpPassword; // SMTP password
					$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
					$mail->Port = $smtpPort;
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				} else {
					return false;
				}
				$instagramIcon = $iN->iN_SelectedMenuIcon('88');
				$facebookIcon = $iN->iN_SelectedMenuIcon('90');
				$twitterIcon = $iN->iN_SelectedMenuIcon('34');
				$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
				$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
				$theCode = $base_url.'verify?v='.$code;
				include_once '../includes/mailTemplates/verificationTemplate.php';
				$body = $bodyVerifyEmail;
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($userEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['confirm_email']);
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if ($mail->send()) {
					$mail->ClearAddresses();
					echo '8';
					return true;

			}
		} else {
			echo '3';
		}
	 /*SEND CONFIRMATION EMAIL FINISHED*/
  }
  /*Insert OneSignal Device Key*/
  if($type == 'device_key'){
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$userDeviceOneSignalKey = mysqli_real_escape_string($db, $_GET['id']);
		$InsertOneSignalDeviceKey = $iN->iN_OneSignalDeviceKey($userID, $userDeviceOneSignalKey);
		if($InsertOneSignalDeviceKey){
		   echo '1';
		}else{
		   echo '2';
		}
	}
  }
  /*Remove OneSignal Device key*/
  if($type == 'remove_device_key'){
	$InsertOneSignalDeviceKey = $iN->iN_OneSignalDeviceKeyRemove($userID);
  }
  /*Generate a QR Code*/
  if($type == 'generateQRCode'){
    include("../includes/qr.php");
  }
  // Get Mention Users
	if ($type == 'mfriends') {
		if (isset($_POST['menFriend'])) {
			$searchmUser = mysqli_real_escape_string($db, $_POST['menFriend']);
			$GetResultMentionedUser = $iN->iN_SearchMention($userID, $searchmUser);
			if ($GetResultMentionedUser) {
				foreach ($GetResultMentionedUser as $um) {
					 $mentionResultUserID = $um['iuid'];
                     $mentionResultUserUsername = $um['i_username'];
					 $mentionResultUserUserFullName = $um['i_user_fullname'];
					 $mentionResultUserAvatar = $iN->iN_UserAvatar($mentionResultUserID, $base_url);
					echo '
					<div class="i_message_wrapper transition mres_u" data-user="'.$mentionResultUserUsername.'">
						<div class="i_message_owner_avatar">
							<div class="i_message_avatar"><img src="'.$mentionResultUserAvatar.'" alt="newuserhere"></div>
						</div>
						<div class="i_message_info_container">
							<div class="i_message_owner_name">'.$mentionResultUserUserFullName.'</div>
							<div class="i_message_i">@'.$mentionResultUserUsername.'</div>
						</div>
					</div>
					 ';
				}
			}
		}
	}
	if($type == 'stories'){
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			foreach ($_FILES['storieimg']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['storieimg']['name'][$iname]);
				$size = $_FILES['storieimg']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['storieimg']['tmp_name'][$iname];
						$mimeType = $_FILES['storieimg']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if($mimeType == 'application/octet-stream'){
							$fileTypeIs = 'video';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						$wVideos = $serverDocumentRoot . '/uploads/videos/';
						if (!file_exists($wVideos . $d)) {
							$newFile = mkdir($wVideos . $d, 0755);
						}
						if ($fileTypeIs == 'video' && $ffmpegStatus == '0' && !in_array($ext, $nonFfmpegAvailableVideoFormat)) {
							exit('303');
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
						   /*STARTED*/
						   if ($fileTypeIs == 'Image') {
							$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
							$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$tumbnails = $serverDocumentRoot . '/uploads/files/' . $d . '/';
							$pathFilea = $base_url . 'uploads/files/' . $d . '/' . $getFilename;

							$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$thePathM = '../' . $pathFile;
							if($ext != 'gif'){
								if($watermarkStatus == 'yes'){
									watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}else if($LinkWatermarkStatus == 'yes'){
									watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}
							}
							$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
							if (file_exists($thePath)) {
								try {
									$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
									$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$image = new ImageFilter();
									$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
								} catch (Exception $e) {
									echo '<span class="request_warning">' . $e->getMessage() . '</span>';
								}
							}else{
								exit('Upload Failed!');
							}
							if ($s3Status == '1') {
								/*Upload Video tumbnail*/
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$key = basename($thevTumbnail);
								try {
									$result = $s3->putObject([
										'Bucket' => $s3Bucket,
										'Key' => 'uploads/files/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								/*Upload Video tumbnail*/
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$key = basename($thevTumbnail);
								try {
									$result = $s3->putObject([
										'Bucket' => $s3Bucket,
										'Key' => 'uploads/files/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								try {
									$result = $s3->putObject([
										'Bucket' => $s3Bucket,
										'Key' => 'uploads/pixel/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								$UploadSourceUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $tumbnailPath;
							}else if ($WasStatus == '1') {
								/*Upload Video tumbnail*/
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$key = basename($thevTumbnail);
								try {
									$result = $s3->putObject([
										'Bucket' => $WasBucket,
										'Key' => 'uploads/files/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								/*Upload Video tumbnail*/
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$key = basename($thevTumbnail);
								try {
									$result = $s3->putObject([
										'Bucket' => $WasBucket,
										'Key' => 'uploads/files/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									/*rmdir($uploadFile . $d);*/
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								try {
									$result = $s3->putObject([
										'Bucket' => $WasBucket,
										'Key' => 'uploads/pixel/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								$UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $tumbnailPath;
							} else if ($digitalOceanStatus == '1') {
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
								/*IF DIGITALOCEAN AVAILABLE THEN*/
								$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
								$upload = $my_space->UploadFile($thevTumbnail, "public");
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
								$upload = $my_space->UploadFile($thevTumbnail, "public");
								$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
								$upload = $my_space->UploadFile($thevTumbnail, "public");
								/**/
								@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
								@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
								@unlink($uploadFile . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext);
								if ($upload) {
									$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $tumbnailPath;
								}
								/*/IF DIGITAOCEAN AVAILABLE THEN*/
							}else{
								$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
							}
						   }else if($fileTypeIs == 'video'){
							$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
							if ($ffmpegStatus == '1') {
								$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
								$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
								$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
								$textVideoPath = '../uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4';

								$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
								$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
								if ($ext == 'mpg') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'mov') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec copy -acodec copy $convertUrl");
									/*$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:v copy -c:a copy -y $convertUrl");*/
								} else if ($ext == 'wmv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'avi') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec h264 -acodec aac -strict -2 $convertUrl 2>&1");
								} else if ($ext == 'webm') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -crf 1 -c:v libx264 $convertUrl");
								} else if ($ext == 'mpeg') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'flv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:a aac -strict -2 -b:a 128k -c:v libx264 -profile:v baseline $convertUrl");
								} else if ($ext == 'm4v') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy $convertUrl");
								} else if ($ext == 'mkv') {
									//$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl 2>&1");
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -codec copy -strict -2 $convertUrl 2>&1");
								}else if($ext == '3gp'){
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl 2>&1");
								}

								$up_url = remove_http($base_url).$userName;
								$cmd = shell_exec("$ffmpegPath -ss 00:00:01 -i $convertUrl -c copy -t 00:00:04 $xVideoFirstPath 2>&1");
								$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
								$cmdText = shell_exec("$ffmpegPath -i $convertUrl -vf drawtext=fontfile=../src/droidsanschinese.ttf:text=$up_url:fontcolor=red:fontsize=18:x=10:y=H-th-10 $textVideoPath 2>&1");
								if ($cmdText) {
									//@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
									$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
								}
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.jpg';
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.jpg';
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit('You uploaded a video in '.$ext.' video format and ffmpeg could not create a tumbnail from the video.  You need to contact your server administration about this. ');
								}
							} else {
								$cmd = '';
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
							}
							if ($ffmpegStatus == '1') {
								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
								$thePathM = '../' . $tumbnailPath;
								if($watermarkStatus == 'yes'){
								  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
								}else if($LinkWatermarkStatus == 'yes'){
								  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
								}
							}
							/*CHECK AMAZON S3 AVAILABLE*/
							if ($s3Status == '1') {
								    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									/*Upload Full video*/
									$theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$key = basename($theName);
									if ($ffmpegStatus == '1') {
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($theName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$fullUploadedVideo = $result->get('ObjectURL');
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
								    }else{
										$theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									    $key = basename($theName);
									    try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($theName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$fullUploadedVideo = $result->get('ObjectURL');
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
									}
								if ($cmd) {
									/*Upload First x Second*/
									$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$key = basename($thexName);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/xvideos/' . $d . '/' . $key,
											'Body' => fopen($thexName, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										/*rmdir($xVideos . $d);*/
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/xvideos/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
									@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
									@unlink($uploadFile . $d . '/' . $getFilename);
								} else {
									$UploadSourceUrl = $base_url . 'uploads/web.png';
									/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
									$tumbnailPath = 'uploads/web.png';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
								}

							}else if ($WasStatus == '1') {
								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
								/*Upload Full video*/
								$theName = '../uploads/videos/' . $d . '/' . $getFilename;
								$key = basename($theName);
								if ($ffmpegStatus == '1') {
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/videos/' . $d . '/' . $key,
											'Body' => fopen($theName, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$fullUploadedVideo = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$pathFile = 'uploads/videos/' . $d . '/' . $getFilename;
								}else{
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($theName, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$fullUploadedVideo = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}
								if ($cmd) {
									/*Upload First x Second*/
									$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$key = basename($thexName);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/xvideos/' . $d . '/' . $key,
											'Body' => fopen($thexName, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/xvideos/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
									@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
									@unlink($uploadFile . $d . '/' . $getFilename);
									$pathFile = 'uploads/videos/' . $d . '/' . $getFilename;
								} else {
									$UploadSourceUrl = $base_url . 'uploads/web.png';
									/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
									$tumbnailPath = 'uploads/web.png';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
								}

							} else if ($digitalOceanStatus == '1') {
								$theName = '../uploads/files/' . $d . '/' . $getFilename;
								/*IF DIGITALOCEAN AVAILABLE THEN*/
								$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
								$upload = $my_space->UploadFile($theName, "public");
								/**/
								if ($cmd) {
									$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thexName, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
								}
								if ($upload) {
									if ($cmd) {
										$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$pathXFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
										$pathXImageFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
										$tumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
										@unlink($pathXImageFile);
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
										@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
									} else {
										$UploadSourceUrl = $base_url . 'img/web.png';
										$tumbnailPath = 'img/web.png';
									}
								}
								/*/IF DIGITAOCEAN AVAILABLE THEN*/
							} else {
								if ($cmd) {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
								} else {
									$UploadSourceUrl = $base_url . 'uploads/web.png';
									$tumbnailPath = 'uploads/web.png';
									$tumbnailPath = $pathFile;
									$pathXFile = 'uploads/web.png';
								}
							}
							$ext = 'mp4';
						   }else{
							   exit('Not a valid format');
						   }
						   /*FINISHED*/
						   $insertFileFromUploadTable = $iN->iN_insertUploadedSotieFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
						   $getUploadedFileID = $iN->iN_GetUploadedStoriesFilesIDs($userID, $pathFile);

							if($fileTypeIs == 'Image'){
								echo '
								<!--Storie-->
								<div class="uploaded_storie_container nonePoint body_'.$getUploadedFileID['s_id'].'">
								<div class="dmyStory" id="'.$getUploadedFileID['s_id'].'"><div class="i_h_in flex_ ownTooltip" data-label="'.iN_HelpSecure($LANG['delete']).'">'.html_entity_decode($iN->iN_SelectedMenuIcon('28')).'</div></div>
								<div class="uploaded_storie_image border_one tabing flex_">
											<img src="'.$UploadSourceUrl.'" id="img'.$getUploadedFileID['s_id'].'">
									</div>
									<div class="add_a_text">
										<textarea class="add_my_text st_txt_'.$getUploadedFileID['s_id'].'" placeholder="Do you want to write something about this storie?"></textarea>
									</div>
									<div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="'.$getUploadedFileID['s_id'].'">
										'.html_entity_decode($iN->iN_SelectedMenuIcon('26')).'<div class="pbtn">'.iN_HelpSecure($LANG['publish']).'</div>
									</div>
								</div>
								<script type="text/javascript">
										(function($) {
										"use strict";
											setTimeout(() => {
												var img = document.getElementById("img'.$getUploadedFileID['s_id'].'");
												if(img.height > img.width){
													$("#img'.$getUploadedFileID['s_id'].'").css("height","100%");
												}else{
													$("#img'.$getUploadedFileID['s_id'].'").css("width","100%");
												}
												$(".uploaded_storie_container").show();
											}, 2000);
										})(jQuery);
								</script>
								<!--/Storie-->
								';
							}else if($fileTypeIs == 'video'){
                                echo '
								<!--Storie-->
								<div class="uploaded_storie_container body_'.$getUploadedFileID['s_id'].'">
								<div class="dmyStory" id="'.$getUploadedFileID['s_id'].'"><div class="i_h_in flex_ ownTooltip" data-label="'.iN_HelpSecure($LANG['delete']).'">'.html_entity_decode($iN->iN_SelectedMenuIcon('28')).'</div></div>
								<div class="uploaded_storie_image border_one tabing flex_">
											<video class="lg-video-object" id="v'.$getUploadedFileID['s_id'].'" controls preload="none" poster="'.$UploadSourceUrl.'">
												<source src="'.$base_url.$getUploadedFileID['uploaded_file_path'].'" preload="metadata" type="video/mp4">
												Your browser does not support HTML5 video.
											</video>
									</div>
									<div class="add_a_text">
										<textarea class="add_my_text st_txt_'.$getUploadedFileID['s_id'].'" placeholder="Do you want to write something about this storie?"></textarea>
									</div>
									<div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="'.$getUploadedFileID['s_id'].'">
										'.html_entity_decode($iN->iN_SelectedMenuIcon('26')).'<div class="pbtn">'.iN_HelpSecure($LANG['publish']).'</div>
									</div>
								</div>
								<!--/Storie-->
								';
							}else{
								echo 'File format not allowed';
							}
						}
					}else{
						echo iN_HelpSecure($size);
					}
				}else{
					exit('No valid Format');
				}
			}
		}
	}
	/*Delete Storie Alert*/
	if($type == 'delete_storie_alert'){
       if(isset($_POST['id']) && $_POST['id'] != ''){
		   $postID = mysqli_real_escape_string($db, $_POST['id']);
		   $alertType = $type;
		   $checkStorieIDExist = $iN->iN_CheckStorieIDExist($userID, $postID);
		   if($checkStorieIDExist){
			 include "../themes/$currentTheme/layouts/popup_alerts/deleteStoryAlert.php";
		   }
	   }
	}
	/*Storie Seen*/
	if($type == 'storieSeen'){
     if(isset($_POST['id']) && $_POST['id'] != ''){
         $storieID = mysqli_real_escape_string($db, $_POST['id']);
		 $checkStorieID = $iN->iN_CheckStorieIDExistJustID($userID, $storieID);
		 if($checkStorieID){
            $insertSee = $iN->iN_InsertStorieSeen($userID, $storieID);
		 }
	 }
	}
	/*Show StorieViewers*/
	if($type == 'storieViewers'){
		if(isset($_POST['id']) && $_POST['id'] != ''){
			$storieID = mysqli_real_escape_string($db, $_POST['id']);
			$checkStorieID = $iN->iN_CheckStorieIDExistJustID($userID, $storieID);
			if($checkStorieID){
				$swData = $iN->iN_GetUploadedStoriesSeenData($userID,$storieID);
				include "../themes/$currentTheme/layouts/popup_alerts/storieViewers.php";
			}
		}

	}
	if ($type == 'pr_upload') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if($mimeType == 'application/octet-stream'){
							$fileTypeIs = 'video';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						$wVideos = $serverDocumentRoot . '/uploads/videos/';
						if (!file_exists($wVideos . $d)) {
							$newFile = mkdir($wVideos . $d, 0755);
						}
						if ($fileTypeIs == 'video' && $ffmpegStatus == '0' && !in_array($ext, $nonFfmpegAvailableVideoFormat)) {
							exit('303');
						}
						$uploadTumbnail = '';
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'video') {
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
								$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								if ($ffmpegStatus == '1') {
									$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$textVideoPath = '../uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4';

									$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									if ($ext == 'mpg') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mov') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec copy -acodec copy $convertUrl");
										$cmd = shell_exec("$ffmpegPath -ss 00:00:01.000 -i $convertUrl -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'wmv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'avi') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec h264 -acodec aac -strict -2 $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'webm') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -crf 1 -c:v libx264 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mpeg') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'flv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:a aac -strict -2 -b:a 128k -c:v libx264 -profile:v baseline $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'm4v') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mkv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -codec copy -strict -2 $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}else if($ext == '3gp'){
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}else{
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}

									$up_url = remove_http($base_url).$userName;
									$cmd = shell_exec("$ffmpegPath -ss 00:00:01 -i $convertUrl -c copy -t 00:00:04 $xVideoFirstPath 2>&1");
									if($drawTextStatus == '1'){
										$cmdText = shell_exec("$ffmpegPath -i $convertUrl -vf drawtext=fontfile=../src/droidsanschinese.ttf:text=$up_url:fontcolor=red:fontsize=18:x=10:y=H-th-10 $textVideoPath 2>&1");
									}else{
										$cmdText = shell_exec("$ffmpegPath -i $convertUrl -c:a copy -c:v libx264 -preset superfast -profile:v baseline $textVideoPath 2>&1");
									}
									if ($cmdText) {
										$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									}
									$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.jpg';
									if (file_exists($thePath)) {
										try {
											$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.jpg';
											$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
											$image = new ImageFilter();
											$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");

										} catch (Exception $e) {
											echo '<span class="request_warning">' . $e->getMessage() . '</span>';
										}
									}else{
										exit('You uploaded a video in '.$ext.' video format and ffmpeg could not create a tumbnail from the video.  You need to contact your server administration about this. ');
									}
								} else {
									$cmd = '';
									$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
									$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
								}
								if ($ffmpegStatus == '1') {
    								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
    								$thePathM = '../' . $tumbnailPath;
									if($watermarkStatus == 'yes'){
    								  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}else if($LinkWatermarkStatus == 'yes'){
									  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}
								}
								/*CHECK AMAZON S3 AVAILABLE*/
								if ($s3Status == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    $theName = '../uploads/files/' . $d . '/' . $getFilename;
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access. Please remove 'public-read' or configure your bucket policy accordingly.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $getFilename);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access. Please remove 'public-read' or configure your bucket policy accordingly.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        $uploads = [
                                            ['path' => '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/files/'],
                                        ];

                                        foreach ($uploads as $upload) {
                                            $key = basename($upload['path']);
                                            try {
                                                $result = $s3->putObject([
                                                    'Bucket' => $s3Bucket,
                                                    'Key' => $upload['target'] . $d . '/' . $key,
                                                    'Body' => fopen($upload['path'], 'r'),
                                                    'CacheControl' => 'max-age=3153600',
                                                    // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                                ]);
                                                $UploadSourceUrl = $result->get('ObjectURL');
                                                @unlink($upload['path']);
                                            } catch (Aws\S3\Exception\S3Exception $e) {
                                                $msg = $e->getAwsErrorMessage();
                                                echo "There was an error uploading the file: $msg<br>";
                                                if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                    echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access. Please remove 'public-read' or configure your bucket policy accordingly.</div>";
                                                    $publicAccessErrorShown = true;
                                                }
                                            }
                                        }
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }

                                } else if ($WasStatus == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    $theName = '../uploads/files/' . $d . '/' . $getFilename;
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This typically happens with trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This typically happens with trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        $uploads = [
                                            ['path' => '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/files/'],
                                        ];

                                        foreach ($uploads as $upload) {
                                            $key = basename($upload['path']);
                                            try {
                                                $result = $s3->putObject([
                                                    'Bucket' => $WasBucket,
                                                    'Key' => $upload['target'] . $d . '/' . $key,
                                                    'Body' => fopen($upload['path'], 'r'),
                                                    'CacheControl' => 'max-age=3153600',
                                                    // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                                ]);
                                                $UploadSourceUrl = $result->get('ObjectURL');
                                                @unlink($upload['path']);
                                            } catch (Aws\S3\Exception\S3Exception $e) {
                                                $msg = $e->getAwsErrorMessage();
                                                echo "There was an error uploading the file: $msg<br>";
                                                if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                    echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This typically happens with trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                    $publicAccessErrorShown = true;
                                                }
                                            }
                                        }

                                        // Remove local temporary files
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($uploadFile . $d . '/' . $getFilename);
                                        @unlink($serverDocumentRoot . '/uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4');
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }
                                } else if ($digitalOceanStatus == '1') {
                                	// Initialize DigitalOcean Spaces client once
                                	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);

                                	// 1. Upload the original file
                                	$localPath1 = '../uploads/files/' . $d . '/' . $getFilename;
                                	$remoteKey1 = 'uploads/files/' . $d . '/' . $getFilename;
                                	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                                	if ($cmd) {
                                		// 2. Upload .mp4 video to xvideos folder
                                		$localPath2 = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$remoteKey2 = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                                		// 3. Upload .jpg thumbnail
                                		$localPath3 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$remoteKey3 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                                		// 4. Upload .mp4 main video (again, in files folder)
                                		$localPath4 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$remoteKey4 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$upload = $my_space->UploadFile($localPath4, "public", $remoteKey4);
                                	}

                                	// If any upload was successful
                                	if ($upload) {
                                		if ($cmd) {
                                			// Set final thumbnail URL and delete local temporary files
                                			$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey3;
                                			$pathXFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                			$pathXImageFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                			$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';

                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                			@unlink($pathXImageFile);
                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                			@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                		} else {
                                			// Fallback for non-video content
                                			$UploadSourceUrl = $base_url . 'img/web.png';
                                			$tumbnailPath = 'img/web.png';
                                		}
                                	}
                                } else {
									if ($cmd) {
										$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
									} else {
										$UploadSourceUrl = $base_url . 'uploads/web.png';
										$tumbnailPath = 'uploads/web.png';
										$tumbnailPath = $pathFile;
										$pathXFile = 'uploads/web.png';
									}
								}
								$ext = 'mp4';
								/**/
							} else if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$tumbnails = $serverDocumentRoot . '/uploads/files/' . $d . '/';
								$pathFilea = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								$pathFileC = '../uploads/files/' . $d . '/' . $getFilename;
								$width = 500;
								$height = 500;
								$file = $pathFilea;
								//indicate the path and name for the new resized file
								$resizedFile = $tumbnails . $UploadedFileName . '_' . $userID . '.' . $ext;
								$resizedFileTwo = $tumbnails . $UploadedFileName . '__' . $userID . '.' . $ext;
								$tb = new ThumbAndCrop();
								$tb->openImg($pathFileC);
								$newHeight = $tb->getRightHeight(500);
								$tb->creaThumb(500, $newHeight);
								$tb->setThumbAsOriginal();
								$tb->creaThumb(500, $newHeight);
								$tb->saveThumb($resizedFileTwo);

								$thePathM = '../' . $pathFile;
								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
								if($ext != 'gif'){
									if($watermarkStatus == 'yes'){
										watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									  }else if($LinkWatermarkStatus == 'yes'){
										watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									  }
								}
								if (file_exists($thePathM)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }
								$publicAccessErrorShown = false;

                                if ($s3Status == '1') {
                                    $uploads = [
                                        ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext, 'target' => 'uploads/files/'],
                                        ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext, 'target' => 'uploads/files/'],
                                        ['path' => '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext, 'target' => 'uploads/pixel/'],
                                    ];

                                    foreach ($uploads as $upload) {
                                        $key = basename($upload['path']);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key'    => $upload['target'] . $d . '/' . $key,
                                                'Body'   => fopen($upload['path'], 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($upload['path']);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public file access. Please remove 'public-read' or update the policy to allow it.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    $UploadSourceUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/uploads/files/' . $d . '/' . basename($uploads[1]['path']);

                                } else if ($WasStatus == '1') {
                                    $uploads = [
                                        ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext, 'target' => 'uploads/files/'],
                                        ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext, 'target' => 'uploads/files/'],
                                        ['path' => '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext, 'target' => 'uploads/pixel/'],
                                    ];

                                    foreach ($uploads as $upload) {
                                        $key = basename($upload['path']);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => $upload['target'] . $d . '/' . $key,
                                                'Body'   => fopen($upload['path'], 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($upload['path']);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This typically occurs on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    $UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/uploads/files/' . $d . '/' . basename($uploads[1]['path']);
                                } else if ($digitalOceanStatus == '1') {
                                	// Initialize the DigitalOcean Spaces client
                                	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);

                                	// 1. Upload file with userID suffix
                                	$localPath1 = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
                                	$remoteKey1 = 'uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
                                	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                                	// 2. Upload standard file
                                	$localPath2 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                	$remoteKey2 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                	$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                                	// 3. Upload pixel version
                                	$localPath3 = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                	$remoteKey3 = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                	$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                                	// Delete local temporary files
                                	@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
                                	@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                	@unlink($uploadFile . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext);

                                	// Set the public URL and path for thumbnail
                                	if ($upload) {
                                		$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey2;
                                		$tumbnailPath = $pathFile;
                                	}
                                } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							/**/
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							mysqli_query($db,"UPDATE i_user_uploads SET upload_type = 'product' WHERE upload_id = '".$getUploadedFileID['upload_id']."'") or die(mysqli_error($db));
							if ($fileTypeIs == 'video') {
								$uploadTumbnail = '
								<div class="v_custom_tumb">
									<label for="vTumb_' . $getUploadedFileID['upload_id'] . '">
										<div class="i_image_video_btn"><div class="pbtn pbtn_plus">' . $LANG['custom_tumbnail'] . '</div>
										<input type="file" id="vTumb_' . $getUploadedFileID['upload_id'] . '" class="imageorvideo cTumb editAds_file" data-id="' . $getUploadedFileID['upload_id'] . '" name="uploading[]" data-id="tupload">
									</label>
								</div>
								';
							}
							if ($fileTypeIs == 'video' || $fileTypeIs == 'Image') {
								/*AMAZON S3*/
								echo '
									<div class="i_uploaded_item iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '" id="' . $getUploadedFileID['upload_id'] . '">
									' . $postTypeIcon . '
									<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
										' . $iN->iN_SelectedMenuIcon('5') . '
									</div>
									<div class="i_uploaded_file" id="viTumb' . $getUploadedFileID['upload_id'] . '" style="background-image:url(' . $UploadSourceUrl . ');">
											<img class="i_file" id="viTumbi' . $getUploadedFileID['upload_id'] . '" src="' . $UploadSourceUrl . '" alt="tumbnail">
									</div>
									' . $uploadTumbnail . '
									</div>
								';
							}
						}else{
							echo 'Something Wrong';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
/*Insert New product*/
if($type == 'createScratch' || $type == 'createBookaZoom'){
   if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals'])){
      $productName = mysqli_real_escape_string($db, $_POST['prnm']);
	  $productPrice = mysqli_real_escape_string($db, $_POST['prprc']);
	  $productDescription = mysqli_real_escape_string($db, $_POST['prdsc']);
	  $productDescriptionInfo = mysqli_real_escape_string($db, $_POST['prdscinf']);
	  $productFiles = mysqli_real_escape_string($db, $_POST['vals']);
	  $productLimitSlots = mysqli_real_escape_string($db, $_POST['lmSlot']);
	  $productAskQuestion = mysqli_real_escape_string($db, $_POST['askQ']);
	  $productFiles = implode(',',array_unique(explode(',', $productFiles)));
	    if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			$trimValue = rtrim($productFiles, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach($explodeFiles as $explodeFile){
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				if(empty($uploadedFileID)){
				    exit('204');
				}
			}
	    }
		if($productLimitSlots == 'ok'){
			$productLimSlots = mysqli_real_escape_string($db, $_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = mysqli_real_escape_string($db, $_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}

	  if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == ''){
         exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
	  }
	  if($type == 'createScratch'){
         $productType = 'scratch';
	  }else if($type == 'createBookaZoom'){
		$productType = 'bookazoom';
	  }else if($type == 'createartcommission'){
		$productType = 'artcommission';
	  }else if($type == 'createjoininstagramclosefriends'){
		$productType = 'joininstagramclosefriends';
	  }
      $slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
	  $insertNewProduct = $iN->iN_InsertNewProduct($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
	  if($insertNewProduct){
        exit('200');
	  }else{
		exit('404');
	  }
   }
}
if($type == 'productStatus'){
   if(isset($_POST['mod']) && in_array($_POST['mod'], $statusValue) && isset($_POST['id'])){
      $productID = mysqli_real_escape_string($db, $_POST['id']);
	  $newStatus = mysqli_real_escape_string($db, $_POST['mod']);
	  $updateProductStatus = $iN->iN_UpdateProductStatus($userID, $productID, $newStatus);
	  if($updateProductStatus){
        exit('200');
	  }else{
		exit('404');
	  }
   }
}
if($type == 'saveEditPr'){
	if(isset($_POST['prnm']) && isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf'])){
		$productID = mysqli_real_escape_string($db, $_POST['prid']);
		$productName = mysqli_real_escape_string($db, $_POST['prnm']);
		$productPrice = mysqli_real_escape_string($db, $_POST['prprc']);
		$productDescription = mysqli_real_escape_string($db, $_POST['prdsc']);
		$productDescriptionInfo = mysqli_real_escape_string($db, $_POST['prdscinf']);
		$productLimitSlots = mysqli_real_escape_string($db, $_POST['lmSlot']);
		$productAskQuestion = mysqli_real_escape_string($db, $_POST['askQ']);
		if($productLimitSlots == 'ok'){
			$productLimSlots = mysqli_real_escape_string($db, $_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = mysqli_real_escape_string($db, $_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}
		if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == ''){
		   exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
		}
		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertUpdatedProduct($userID, $iN->iN_Secure($productID),$iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($slug), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
		if($insertNewProduct){
		  exit('200');
		}else{
		  exit('404');
		}
	 }
}
/*Get Free Follow PopUP*/
if ($type == 'delete_product') {
	if (isset($_POST['id'])) {
		$productID = mysqli_real_escape_string($db, $_POST['id']);
		$checkproductExist = $iN->iN_CheckProductIDExist($userID, $productID);
		if ($checkproductExist) {
			include "../themes/$currentTheme/layouts/popup_alerts/deleteProduct.php";
		}
	}
}
/*Delete Story From Database*/
if ($type == 'deleteProduct') {
	if (isset($_POST['id'])) {
		$productID = mysqli_real_escape_string($db, $_POST['id']);
		if(!empty($productID) && $digitalOceanStatus == '1'){
			$getPostFileIDs = $iN->iN_ProductDetails($userID, $productID);
			$postFileIDs = isset($getPostFileIDs['pr_files']) ? $getPostFileIDs['pr_files'] : NULL;
			$trimValue = rtrim($postFileIDs, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach ($explodeFiles as $explodeFile) {
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				if($theFileID){
					$uploadedFileID = $theFileID['upload_id'];
					$uploadedFilePath = $theFileID['uploaded_file_path'];
					$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
					$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
					$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$my_space->DeleteObject($uploadedFilePath);

					$space_two = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$space_two->DeleteObject($uploadedFilePathX);

					$space_tree = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$space_tree->DeleteObject($uploadedTumbnailFilePath);
					mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
				}
			}
			$deleteStoragePost = $iN->iN_DeleteProductFromDataifStorage($userID, $productID);
			if($deleteStoragePost){
				echo '200';
			}else{
				echo '404';
			}
		}else if(!empty($productID) && $s3Status == '1'){
			$getPostFileIDs = $iN->iN_ProductDetails($userID,$productID);
			$postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
			$trimValue = rtrim($postFileIDs, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach ($explodeFiles as $explodeFile) {
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				if($theFileID){
					$uploadedFileID = $theFileID['upload_id'];
					$uploadedFilePath = $theFileID['uploaded_file_path'];
					$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
					$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
					$s3->deleteObject([
						'Bucket' => $s3Bucket,
						'Key'    => $uploadedFilePath,
					]);
					$s3->deleteObject([
						'Bucket' => $s3Bucket,
						'Key'    => $uploadedFilePathX,
					]);
					$s3->deleteObject([
						'Bucket' => $s3Bucket,
						'Key'    => $uploadedTumbnailFilePath,
					]);
					mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
				}
			}
			$deleteStoragePost = $iN->iN_DeleteProductFromDataifStorage($userID, $productID);
			if($deleteStoragePost){
				echo '200';
			}else{
				echo '404';
			}
		}else if(!empty($productID) && $WasStatus == '1'){
			$getPostFileIDs = $iN->iN_ProductDetails($userID,$productID);
			$postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
			$trimValue = rtrim($postFileIDs, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach ($explodeFiles as $explodeFile) {
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				if($theFileID){
					$uploadedFileID = $theFileID['upload_id'];
					$uploadedFilePath = $theFileID['uploaded_file_path'];
					$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
					$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedFilePath,
					]);
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedFilePathX,
					]);
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedTumbnailFilePath,
					]);
					mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
				}
			}
			$deleteStoragePost = $iN->iN_DeleteProductFromDataifStorage($userID, $productID);
			if($deleteStoragePost){
				echo '200';
			}else{
				echo '404';
			}
		}else if(!empty($productID)){
			$deletePostFromData = $iN->iN_DeleteProduct($userID, $productID);
			if ($deletePostFromData) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
}
/*UPload Downloadable File*/
if ($type == 'prd_upload') {
	$availableFileExtensions = 'pdf,zip,PDF,ZIP';
	//$availableFileExtensions
	if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
		foreach ($_FILES['uploading']['name'] as $iname => $value) {
			$name = stripslashes($_FILES['uploading']['name'][$iname]);
			$size = $_FILES['uploading']['size'][$iname];
			$ext = getExtension($name);
			$ext = strtolower($ext);
			$valid_formats = explode(',', $availableFileExtensions);
			if (in_array($ext, $valid_formats)) {
				if (convert_to_mb($size) < $availableUploadFileSize) {
					$microtime = microtime();
					$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
					$UploadedFileName = "file_" . $removeMicrotime . '_' . $userID;
					$getFilename = $UploadedFileName . "." . $ext;
					// Change the image ame
					$tmp = $_FILES['uploading']['tmp_name'][$iname];
					$mimeType = $_FILES['uploading']['type'][$iname];
					$d = date('Y-m-d');

					if (!file_exists($uploadFile . $d)) {
						$newFile = mkdir($uploadFile . $d, 0755);
					}
					$uploadTumbnail = '';
					if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
						/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
						$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
						$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
						$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
						$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
						/*CHECK AMAZON S3 AVAILABLE*/
						if ($s3Status == '1') {
							/*Upload Full video*/
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							$key = basename($theName);

							try {
								$result = $s3->putObject([
									'Bucket' => $s3Bucket,
									'Key' => 'uploads/files/' . $d . '/' . $key,
									'Body' => fopen($theName, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$fullUploadedVideo = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $getFilename);
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo "There was an error uploading the file.\n";
							}
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						}else if ($WasStatus == '1') {
							/*Upload Full video*/
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							$key = basename($theName);

							try {
								$result = $s3->putObject([
									'Bucket' => $WasBucket,
									'Key' => 'uploads/files/' . $d . '/' . $key,
									'Body' => fopen($theName, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$fullUploadedVideo = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $getFilename);
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo "There was an error uploading the file.\n";
							}
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						} else if ($digitalOceanStatus == '1') {
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							/*IF DIGITALOCEAN AVAILABLE THEN*/
							$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
							$upload = $my_space->UploadFile($theName, "public");
							if($upload){
								@unlink($uploadFile . $d . '/' . $getFilename);
							}
							/*/IF DIGITAOCEAN AVAILABLE THEN*/
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						} else {
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						}
						/**/
						if($ext == 'pdf'){
                           $fileIcon = html_entity_decode($iN->iN_SelectedMenuIcon('166'));
						}else{
						   $fileIcon = html_entity_decode($iN->iN_SelectedMenuIcon('167'));
						}
						if($UploadSourceUrl){
							$data = array(
								'status' => $status,
								'fileUrl' => $UploadSourceUrl,
								'filePath' => $pathFile,
								'fileIcon' => $fileIcon,
								'fileName' => $getFilename
							);
							$result = json_encode($data);
							echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
						}
					}else{
						echo 'Something Wrong';
					}
				} else {
					echo iN_HelpSecure($size);
				}
			}
		}
	}
}
if($type == 'createDigitalDownload'){
	if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals']) && isset($_POST['dFile'])){
		$productName = mysqli_real_escape_string($db, $_POST['prnm']);
		$productPrice = mysqli_real_escape_string($db, $_POST['prprc']);
		$productDescription = mysqli_real_escape_string($db, $_POST['prdsc']);
		$productDescriptionInfo = mysqli_real_escape_string($db, $_POST['prdscinf']);
		$productFiles = mysqli_real_escape_string($db, $_POST['vals']);
		$productDownloadableFile = mysqli_real_escape_string($db, $_POST['dFile']);
		$productFiles = implode(',',array_unique(explode(',', $productFiles)));
		  if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			  $trimValue = rtrim($productFiles, ',');
			  $explodeFiles = explode(',', $trimValue);
			  $explodeFiles = array_unique($explodeFiles);
			  foreach($explodeFiles as $explodeFile){
				  $theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				  $uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				  if(empty($uploadedFileID)){
					  exit('204');
				  }
			  }
		  }
		if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == '' || preg_replace('/\s+/', '',$productDownloadableFile) == ''){
		   exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
		}
		$productType = 'digitaldownload';

		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertNewProductDownloadable($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productDownloadableFile));
		if($insertNewProduct){
		  exit('200');
		}else{
		  exit('404');
		}
	 }
}
/*Insert New product*/
if($type == 'createliveeventticket' || $type == 'createartcommission' || $type == 'createjoininstagramclosefriends'){
	if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals'])){
	    $productName = mysqli_real_escape_string($db, $_POST['prnm']);
	    $productPrice = mysqli_real_escape_string($db, $_POST['prprc']);
	    $productDescription = mysqli_real_escape_string($db, $_POST['prdsc']);
	    $productDescriptionInfo = mysqli_real_escape_string($db, $_POST['prdscinf']);
	    $productFiles = mysqli_real_escape_string($db, $_POST['vals']);
	    $productLimitSlots = mysqli_real_escape_string($db, $_POST['lmSlot']);
	    $productAskQuestion = mysqli_real_escape_string($db, $_POST['askQ']);
	    $productFiles = implode(',',array_unique(explode(',', $productFiles)));
		if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			$trimValue = rtrim($productFiles, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach($explodeFiles as $explodeFile){
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				if(empty($uploadedFileID)){
					exit('204');
				}
			}
		}
		if($productLimitSlots == 'ok'){
			$productLimSlots = mysqli_real_escape_string($db, $_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = mysqli_real_escape_string($db, $_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}
	    if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == ''){
			exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
	    }
		if($type == 'createliveeventticket'){
			$productType = 'liveeventticket';
		} else if($type == 'createartcommission'){
			$productType = 'artcommission';
		} else if($type == 'createjoininstagramclosefriends'){
			$productType = 'joininstagramclosefriends';
		}
		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertNewProductLiveEventTicket($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
		if($insertNewProduct){
			exit('200');
		}else{
			exit('404');
		}
	}
}

if($type == 'shareMyTextStory'){
   if(isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['stext']) && !empty($_POST['stext']) && $_POST['stext'] != ''){
      $bgID = mysqli_real_escape_string($db,$_POST['id']);
	  $storyText = mysqli_real_escape_string($db, $_POST['stext']);
	  if(preg_replace('/\s+/', '',$storyText) == ''){
        exit(iN_HelpSecure($LANG['please_add_text_in_your_story']));
	  }
	  $insertTextStory = $iN->iN_InsertTextStory($userID, $iN->iN_Secure($bgID), $iN->iN_Secure($storyText));
	  if($insertTextStory){
        exit('200');
	  }else{
		exit('404');
	  }
   }
}
if ($type == 'buyProduct') {
	if (isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type'])) {
		$productID = mysqli_real_escape_string($db, $_POST['type']);
	    $checkproductID = $iN->iN_CheckProductIDExistFromURL($productID);
		if($checkproductID == TRUE){
			$prData = $iN->iN_GetProductDetailsByID($productID);
			$planAmount = $prData['pr_price'];
			$ProductOwnerID = $prData['iuid_fk'];

			if($ProductOwnerID == $userID){
              exit('me');
			}
			$planPoint = '';
			if($stripePaymentCurrency == 'JPY'){
				 $planAmount = $planAmount / 100;
			}
			require_once '../includes/payment/vendor/autoload.php';
			if (!defined('INORA_METHODS_CONFIG')) {
				define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
			}
			$configData = configItem();
			$DataUserDetails = [
				'amounts' => [ // at least one currency amount is required
					$payPalCurrency => $planAmount,
					$iyziCoPaymentCurrency => $planAmount,
					$bitPayPaymentCurrency => $planAmount,
					$autHorizePaymentCurrency => $planAmount,
					$payStackPaymentCurrency => $planAmount,
					$stripePaymentCurrency => $planAmount,
					$razorPayPaymentCurrency => $planAmount,
				],
				'order_id' => 'ORDS' . uniqid(), // required in instamojo, Iyzico, Paypal, Paytm gateways
				'customer_id' => 'CUSTOMER' . uniqid(), // required in Iyzico, Paytm gateways
				'item_name' => $LANG['point_purchasing'], // required in Paypal gateways
				'item_qty' => 1,
				'item_id' => 'ITEM' . uniqid(), // required in Iyzico, Paytm gateways
				'payer_email' => $userEmail, // required in instamojo, Iyzico, Stripe gateways
				'payer_name' => $userFullName, // required in instamojo, Iyzico gateways
				'description' => $LANG['point_purchasing_from'], // Required for stripe
				'ip_address' => getUserIpAddr(), // required only for iyzico
				'address' => '3234 Godfrey Street Tigard, OR 97223', // required in Iyzico gateways
				'city' => 'Tigard', // required in Iyzico gateways
				'country' => 'United States', // required in Iyzico gateways
			];
			$PublicConfigs = getPublicConfigItem();

			$configItem = $configData['payments']['gateway_configuration'];

			// Get config data
			$configa = getPublicConfigItem();
			// Get app URL
			$paymentPagePath = getAppUrl();

			$gatewayConfiguration = $configData['payments']['gateway_configuration'];
			// get paystack config data
			$paystackConfigData = $gatewayConfiguration['paystack'];
			// Get paystack callback ur
			$paystackCallbackUrl = getAppUrl($paystackConfigData['callbackUrl']);

			// Get stripe config data
			$stripeConfigData = $gatewayConfiguration['stripe'];
			// Get stripe callback ur
			$stripeCallbackUrl = getAppUrl($stripeConfigData['callbackUrl']);

			// Get razorpay config data
			$razorpayConfigData = $gatewayConfiguration['razorpay'];
			// Get razorpay callback url
			$razorpayCallbackUrl = getAppUrl($razorpayConfigData['callbackUrl']);

			// Get Authorize.Net config Data
			$authorizeNetConfigData = $gatewayConfiguration['authorize-net'];
			// Get Authorize.Net callback url
			$authorizeNetCallbackUrl = getAppUrl($authorizeNetConfigData['callbackUrl']);

			// Individual payment gateway url
			$individualPaymentGatewayAppUrl = getAppUrl('individual-payment-gateways');
			// User Details Configurations FINISHED
			include "../themes/$currentTheme/layouts/popup_alerts/paymentMethodsForPurchaseProduct.php";
		}
	}
}
if ($type == 'processProduct') {
	require_once '../includes/payment/vendor/autoload.php';
	if (!defined('INORA_METHODS_CONFIG')) {
		define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
	}
	include "../includes/payment/payment-process-product.php";
}
if($type == 'downloadMyProduct'){
   if(isset($_POST['myp']) && !empty($_POST['myp']) && $_POST['myp'] != ''){
      $productID = mysqli_real_escape_string($db, $_POST['myp']);
	  $checkProductPurchasedBefore = $iN->iN_CheckItemPurchasedBefore($userID, $productID);
	  if($checkProductPurchasedBefore){
		$productData = $iN->iN_GetProductDetailsByID($productID);
		$uProductDownloadableFiles = $productData['pr_downlodable_files'];
		$thefile = $uProductDownloadableFiles;
		$file = $uProductDownloadableFiles;
		$ext = substr($file, strrpos($file, '.') + 1);
        $fake = 'aa.'.$ext;
		if (file_exists($thefile)) {
			$iN->download($file,$fake);
		}
	  }
   }
}
if($type == 'gotAnnouncement'){
   if(isset($_POST['aid']) && $_POST['aid'] != ''){
       $announceID = mysqli_real_escape_string($db, $_POST['aid']);
	   $announcementReaded = $iN->iN_AnnouncementAccepted($userID, $announceID);
	   if($announcementReaded){
         exit('200');
	   }else{
		 exit('404');
	   }
   }
}
if($type == 'mrProduct'){
    if(isset($_POST['last']) && isset($_POST['ty'])){
       $productID = mysqli_real_escape_string($db, $_POST['last']);
       $categoryKey = mysqli_real_escape_string($db, $_POST['ty']);
       $productData = $iN->iN_AllUserProductPosts($categoryKey, $productID, $showingNumberOfPost);
	   include "../themes/$currentTheme/layouts/loadmore/moreProduct.php";
	}
}
if($type == 'moveMyAffilateBalance'){
  if(isset($_POST['myp']) && $_POST['myp'] != '' && !empty($_POST['myp'])){
	  $moveMyPoint = $iN->iN_MoveMyPoint($userID);
  }
}
/*Open Profile Tip Box*/
if($type == 'p_p_tips'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = mysqli_real_escape_string($db, $_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendProfileTipPoint.php";
	}
}
/*Open Profile Frame Box*/
if($type == 'p_p_frame'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = mysqli_real_escape_string($db, $_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendProfileFrame.php";
	}
}
if($type == 'p_p_tips_message'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = mysqli_real_escape_string($db, $_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendMessageTipPoint.php";
	}
}
/*Send Tip*/
if($type == 'p_sendTipProfile'){
	if(isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' &&  $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])){
	   $tiSendingUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
	   $tipAmount = mysqli_real_escape_string($db, $_POST['tipVal']);
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   if($tipAmount < $minimumTipAmount){
		  $emountnot = 'notEnough';
	   }else{
		  if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {

			  $netUserEarning = $tipAmount * $onePointEqual;
			  $adminEarning = ($adminFee * $netUserEarning) / 100;
			  $userNetEarning = $netUserEarning - $adminEarning;

			  $UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
			  if($UpdateUsersWallet){
				 $status = 'ok';
			  }else{
				 $status = '404';
			  }
		   }else{
			  $status = '';
			  $emountnot = 'notEnouhCredit';
			  $redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		   }
	   }
	   $data = array(
		  'status' => $status,
		  'redirect' => $redirect,
		  'enamount' => $emountnot
	   );
	   $result = json_encode($data);
	   echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
	   if($status == 'ok'){
		  $userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
		  $toUserName = $userDeviceKey['i_username'];
		  $oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
		  $msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
		  $msgTitle = $iN->iN_Secure($LANG['tip_earning']).$currencys[$defaultCurrency]. $netUserEarning;
		  $URL = $base_url.'settings?tab=dashboard';
		  if($oneSignalUserDeviceKey){
			$iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		  }
	    }
	}
}

/*Send Tip*/
if($type == 'p_sendTipMessage'){
	if(isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' &&  $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])){
	   $tiSendingUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
	   $tipAmount = mysqli_real_escape_string($db, $_POST['tipVal']);
	   $chatID = mysqli_real_escape_string($db, $_POST['chID']);
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   if($tipAmount < $minimumTipAmount){
		  exit('notEnough');
	   }else{
		  if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {

			  $netUserEarning = $tipAmount * $onePointEqual;
			  $adminEarning = ($adminFee * $netUserEarning) / 100;
			  $userNetEarning = $netUserEarning - $adminEarning;

			  $UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
			  if($UpdateUsersWallet){
				 $status = 'ok';
			  }else{
				 exit('404');
			  }
		   }else{
			  exit('notEnouhCredit');
			  $redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		   }
	   }

	   if($status == 'ok'){
		  $userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
		  $toUserName = $userDeviceKey['i_username'];
		  $oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
		  $msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
		  $msgTitle = $iN->iN_Secure($LANG['tip_earning']).$currencys[$defaultCurrency]. $netUserEarning;
		  $URL = $base_url.'settings?tab=dashboard';
		  if($oneSignalUserDeviceKey){
			$iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		  }
		  $message = $userNetEarning;
		  $sendedGiftMoney = $tipAmount;
		  $insertData = $iN->iN_InsertNewTipMessage($userID, $chatID, $message, $sendedGiftMoney);
			if ($insertData) {
				$cMessageID = $insertData['con_id'];
				$cUserOne = $insertData['user_one'];
				$cUserTwo = $insertData['user_two'];
				$cMessage = $insertData['message'];
				$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
				$mSeenStatus = $insertData['seen_status'];
				$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
				$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
				$cMessageTime = $insertData['time'];
				$ip = $iN->iN_GetIPAddress();
				$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
				if ($query && $query['status'] == 'success') {
					date_default_timezone_set($query['timezone']);
				}
				$message_time = date("c", $cMessageTime);
				$convertMessageTime = strtotime($message_time);
				$netMessageHour = date('H:i', $convertMessageTime);
				$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
				$msgDots = '';
				$imStyle = '';
				$seenStatus = '';
				if ($cUserOne == $userID) {
					$mClass = 'me';
					$msgOwnerID = $cUserOne;
					$lastM = '';
					$timeStyle = 'msg_time_me';
					if (!empty($cFile)) {
						$imStyle = 'mmi_i';
					}
					$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					if ($mSeenStatus == '1') {
						$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					}
					if($gifMoney){
                        $SGifMoneyText = preg_replace( '/{.*?}/', $cMessage, $LANG['youSendGifMoney']);
                    }
				} else {
					$mClass = 'friend';
					$msgOwnerID = $cUserOne;
					$lastM = 'mm_' . $msgOwnerID;
					if (!empty($cFile)) {
						$imStyle = 'mmi_if';
					}
					if($gifMoney){
                        $msgOwnerFullName = $iN->iN_UserFullName($msgOwnerID);
                        $SGifMoneyText = $iN->iN_TextReaplacement($LANG['sendedGifMoney'],[$msgOwnerFullName , $cMessage]);
                    }
					$timeStyle = 'msg_time_fri';
				}
				$styleFor = '';
				if ($cStickerUrl) {
					$styleFor = 'msg_with_sticker';
					$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
				}
				if ($cGifUrl) {
					$styleFor = 'msg_with_gif';
					$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
				}
				$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
				include "../themes/$currentTheme/layouts/chat/newMessage.php";
			}

	   }
	}
}
  /*Buy Video Call*/
  if($type == 'buyVideoCall'){
     if(isset($_POST['calledID']) && $_POST['calledID'] !== '' && !empty($_POST['calledID']) && isset($_POST['callName']) && $_POST['callName'] !== '' && !empty($_POST['callName'])){
		$calledUserID = mysqli_real_escape_string($db, $_POST['calledID']);
		$videoCallName = mysqli_real_escape_string($db, $_POST['callName']);
		$callerDetails = $iN->iN_GetUserDetails($calledUserID);
		$callerUserFullName = $callerDetails['i_user_fullname'];
		$callerUserName = $callerDetails['i_username'];
		$videoCallPrice = $callerDetails['video_call_price'];
		$whoCanVideoCall = $callerDetails['who_can_call'];
		$subStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $calledUserID);
		$checkUserIsCreator = $iN->iN_CheckUserIsCreator($calledUserID);
		$callerUserAvatar = $iN->iN_UserAvatar($calledUserID, $base_url);
		if($isVideoCallFree == 'no'){
			include "../themes/$currentTheme/layouts/popup_alerts/buyVideoCall.php";
		}else if($isVideoCallFree == 'yes'){
			$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
			include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
		}else{
			exit('404');
		}
	 }
  }
  /*Create a video call*/
  if($type == 'createVideoCall'){
      if(isset($_POST['calledID']) && $_POST['calledID'] !== '' && !empty($_POST['calledID']) && isset($_POST['callName']) && $_POST['callName'] !== '' && !empty($_POST['callName'])){
		    $calledUserID = mysqli_real_escape_string($db, $_POST['calledID']);
			$videoCallName = mysqli_real_escape_string($db, $_POST['callName']);
			$callerDetails = $iN->iN_GetUserDetails($calledUserID);
			$callerUserFullName = $callerDetails['i_user_fullname'];
			$callerUserName = $callerDetails['i_username'];
			$videoCallPrice = $callerDetails['video_call_price'];
			$whoCanVideoCall = $callerDetails['who_can_call'];
			$callerUserAvatar = $iN->iN_UserAvatar($calledUserID, $base_url);
			if($whoCanVideoCall == '0'){
				$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
				include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
			}else{
				if($userCurrentPoints < $videoCallPrice && $userID != $calledUserID && $isVideoCallFree == 'no'){
					exit();
				}else if($isVideoCallFree == 'no'){
					$netUserEarning = $videoCallPrice * $onePointEqual;
					$adminEarning = ($adminFee * $netUserEarning) / 100;
					$userNetEarning = $netUserEarning - $adminEarning;
					$UpdateUsersWallet = $iN->iN_UpdateUsersWalletsForVideoCall($userID, $calledUserID, $videoCallPrice, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
					if($UpdateUsersWallet){
						$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
						include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
					}else{
						exit('404');
					}
				}else{
					$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
					include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
				}
		    }
	  }
  }
  /*Video Call Alert*/
  if($type == 'videoCallAlert'){
      if(isset($_POST['call']) && !empty($_POST['call']) && $_POST['call'] !== ''){
          $callID = mysqli_real_escape_string($db, $_POST['call']);
		  $callDetails = $iN->iN_VideoCallDetails($callID);
		  $callerUserID = $callDetails['caller_uid_fk'];
		  $chatUrl = $callDetails['vc_id'];
		  $callerDetails = $iN->iN_GetUserDetails($callerUserID);
		  $callerUserFullName = $callerDetails['i_user_fullname'];
		  $callerUserName = $callerDetails['i_username'];
		  $callerUserAvatar = $iN->iN_UserAvatar($callerUserID, $base_url);
		  if($fullnameorusername == 'no'){
			$callerUserFullName = $callerUserName;
		  }
		  include "../themes/$currentTheme/layouts/popup_alerts/videocallalert.php";
	  }
  }
  /*Video Call Accept*/
  if($type == 'call_accepted'){
    if(isset($_POST['accID']) && !empty($_POST['accID']) && $_POST['accID'] !== ''){
		$callID = mysqli_real_escape_string($db, $_POST['accID']);
		$callDetails = $iN->iN_VideoCallAcceptDetails($callID);
		$chatUrl = $callDetails['chat_id_fk'];
		echo iN_HelpSecure($base_url) . 'chat?chat_width=' . $chatUrl;
	}
  }
if ($type == 'liveVideoMute') {
    if (isset($_POST['chName']) && !empty($_POST['chName'])) {
        $channelName = mysqli_real_escape_string($db, $_POST['chName']);

        $callQuery = mysqli_query($db, "SELECT vc_id, video_muted FROM i_video_call WHERE voice_call_name = '$channelName' LIMIT 1");

        if ($callQuery && mysqli_num_rows($callQuery) > 0) {
            $call = mysqli_fetch_assoc($callQuery);
            $currentStatus = (int)$call['video_muted'];
            $newStatus = $currentStatus === 1 ? 0 : 1;

            $update = mysqli_query($db, "UPDATE i_video_call SET video_muted = '$newStatus' WHERE vc_id = '{$call['vc_id']}'");

            echo json_encode([
                'status' => $update ? 'success' : 'error',
                'muted' => $newStatus,
                'message' => $update ? 'Updated successfully' : 'Update failed'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Channel not found'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing channel name'
        ]);
    }
    exit(); // her durumda çık
}
  /*Live Call End*/
  if($type == 'liveEnd'){
     if(isset($_POST['chName']) && !empty($_POST['chName']) && $_POST['chName'] !== ''){
        $channelName = mysqli_real_escape_string($db, $_POST['chName']);
		$checkAndDeleteCall = $iN->iN_CheckAndDeleteCall($userID, $channelName);
		if($checkAndDeleteCall){
           exit('200');
		}else{
			exit('404');
		}
	 }
  }
  /*Video Call Decline*/
  if($type == 'call_declined'){
    if(isset($_POST['accID']) && !empty($_POST['accID']) && $_POST['accID'] !== ''){
		$callID = mysqli_real_escape_string($db, $_POST['accID']);
		$callDetails = $iN->iN_VideoCallDeclineDetails($callID);
	}
  }
  /*Update Video Call fee*/
 /*Update Video Call fee*/
/* Update Video Call fee */
/* Update Video Call fee */
if ($type == 'vCost') {
    if (isset($_POST['vCostFee']) && $_POST['vCostFee'] !== '') {
        $videoCost = (int)($_POST['vCostFee'] ?? 0);
        if ($videoCost < 0) { $videoCost = 0; }

        // ---- MAX check (Video Call) ----
        $__MAX_CALL = (defined('MF_MAX_VIDEO_CALL_PRICE_POINTS') && MF_MAX_VIDEO_CALL_PRICE_POINTS > 0)
            ? (int)MF_MAX_VIDEO_CALL_PRICE_POINTS : 2000; // points

        if ($videoCost > $__MAX_CALL) {
            $msg = sprintf(
                $LANG['err_video_call_price_limit'] ?? 
                'Maximum video call price is %s points.', 
                number_format($__MAX_CALL)
            );
            echo json_encode(['status'=>'error','msg'=>$msg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($videoCost <= 0) { echo 'not'; exit; }

        $insertVideoCost = $iN->iN_UpdateVideoCost($userID, $videoCost);
        echo json_encode(['status'=>'ok']);
        exit;
    } else {
        echo 'not'; exit;
    }
}



  if($type == 'moveMyEarnedPoints'){
	if(isset($_POST['myp']) && $_POST['myp'] != '' && !empty($_POST['myp'])){
		$totalEarned = mysqli_real_escape_string($db, $_POST['myp']);
		if($totalEarned < 1){
           exit('You don\'t have enough points to calculate yet.');
		}
		$moveMyPoint = $iN->iN_MovePointEarningsToPointBalance($userID, $totalEarned);
		if($moveMyPoint){
			exit('ok');
		}else{
			exit('me');
		}
	}else{
		exit('You don\'t have enough points to calculate yet.');
	}
  }
  /*Unlock Message*/
  if($type == 'unlockMessage'){
    if(isset($_POST['mi']) && !empty($_POST['mi']) && $_POST['mi'] != '' && isset($_POST['ci']) && !empty($_POST['ci']) && $_POST['ci'] != ''){
       $messageID = mysqli_real_escape_string($db, $_POST['mi']);
	   $chatID = mysqli_real_escape_string($db, $_POST['ci']);
	   $getMData = $iN->iN_GetMessageDetailsByID($messageID, $chatID);
	   $messagePrice = isset($getMData['private_price']) ? $getMData['private_price'] : NULL;
	   $userOne = isset($getMData['user_one']) ? $getMData['user_one'] : NULL;
	   $userTwo = isset($getMData['user_two']) ? $getMData['user_two'] : NULL;
	   if($userOne == $userID){
         $messageOwnerID = $userTwo;
	   }else{
		 $messageOwnerID = $userOne;
	   }
	   if($userCurrentPoints >= $messagePrice){
		    $translatePointToMoney = $messagePrice * $onePointEqual;
			$adminEarning = $translatePointToMoney * ($adminFee / 100);
			$userEarning = $translatePointToMoney - $adminEarning;
			$insertData = $iN->iN_UnLockMessage($userID, $messageID, $chatID, $adminEarning, $userEarning,$messageOwnerID, $translatePointToMoney, $adminFee, $messagePrice);
			if($insertData){
					$cMessageID = $insertData['con_id'];
					$cUserOne = $insertData['user_one'];
					$cUserTwo = $insertData['user_two'];
					$cMessage = $insertData['message'];
					$mSeenStatus = $insertData['seen_status'];
					$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
					$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				    $privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
					$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
					$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
					$cMessageTime = $insertData['time'];
					$ip = $iN->iN_GetIPAddress();
					$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
					if ($query && $query['status'] == 'success') {
						date_default_timezone_set($query['timezone']);
					}
					$message_time = date("c", $cMessageTime);
					$convertMessageTime = strtotime($message_time);
					$netMessageHour = date('H:i', $convertMessageTime);
					$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
					$msgDots = '';
					$imStyle = '';
					$seenStatus = '';
					if ($cUserOne == $userID) {
						$mClass = 'me';
						$msgOwnerID = $cUserOne;
						$lastM = '';
						$timeStyle = 'msg_time_me';
						if (!empty($cFile)) {
							$imStyle = 'mmi_i';
						}
						$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						if ($mSeenStatus == '1') {
							$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						}
					} else {
						$mClass = 'friend';
						$msgOwnerID = $cUserOne;
						$lastM = 'mm_' . $msgOwnerID;
						if (!empty($cFile)) {
							$imStyle = 'mmi_if';
						}
						$timeStyle = 'msg_time_fri';
					}
					$styleFor = '';
					if ($cStickerUrl) {
						$styleFor = 'msg_with_sticker';
						$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
					}
					if ($cGifUrl) {
						$styleFor = 'msg_with_gif';
						$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
					}
					$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
					include "../themes/$currentTheme/layouts/chat/unLockedMessage.php";
			}else{
			  exit('403');
			}
	   }else{
		  exit('404');
	   }
	}
  }
	/*Show PopUps*/
	if ($type == 'camAlert') {
		if (isset($_POST['al'])) {
			$alertType = mysqli_real_escape_string($db, $_POST['al']);
			include "../themes/$currentTheme/layouts/popup_alerts/popup_alerts.php";
		}
	}
	if ($type == 'getBoostList') {
		if(isset($_POST['bp']) && !empty($_POST['bp'])){
           $boostPostID = mysqli_real_escape_string($db, $_POST['bp']);
		   include "../themes/$currentTheme/layouts/popup_alerts/getBoostList.php";
		}
	}
	if($type =='boostThisPlan'){
		if(isset($_POST['pbID']) && !empty($_POST['bpID'])){
			$boostPlanID = mysqli_real_escape_string($db, $_POST['pbID']);
			$boostPostID = mysqli_real_escape_string($db, $_POST['bpID']);
		    $CheckboostIDExist = $iN->CheckBoostPlanExist($boostPlanID);
            if($CheckboostIDExist){
				$boostDetails = $iN->iN_GetBoostPostDetails($boostPlanID);
                $planAmount = $boostDetails['plan_amount'];
				$viewTime = $boostDetails['view_time'];
			    $checkPostBoostedeBefore = $iN->iN_CheckPostBoostedBefore($userID, $boostPostID);
				if($checkPostBoostedeBefore){
                   $getPostDetails = $iN->iN_GetAllPostDetails($boostPostID);
				   $boostedPostSlugUrl = isset($getPostDetails['url_slug']) ? $getPostDetails['url_slug'] : NULL;
				   $redirectThisURL = $base_url.'post/'.$boostedPostSlugUrl.'_'.$boostPostID;
				   echo iN_HelpSecure($redirectThisURL);
				   exit();
				}
				if($planAmount < $userCurrentPoints){
				   $boostInsert = $iN->iN_BoostInsert($userID, $boostPostID, $planAmount,$boostPlanID,$viewTime);
				   if($boostInsert){
						$getPostDetails = $iN->iN_GetAllPostDetails($boostPostID);
						$boostedPostSlugUrl = isset($getPostDetails['url_slug']) ? $getPostDetails['url_slug'] : NULL;
						$redirectThisURL = $base_url.'post/'.$boostedPostSlugUrl.'_'.$boostPostID;
				        echo iN_HelpSecure($redirectThisURL);
				   }
				}else{
					exit('404');
				}
			}
		}
	}
	/*Update Boost Status*/
	if($type == 'updateBoostStatus'){
		if(isset($_POST['bpid']) && !empty($_POST['bpid']) && isset($_POST['mod']) && in_array($_POST['mod'], $yesOrNo)){
		   $bPostID = isset($_POST['bpid']) ? $_POST['bpid'] : NULL;
		   $bpStatus = isset($_POST['mod']) ? $_POST['mod'] : NULL;
           $updateBoostPostStatus = $iN->iN_UpdateBoosPostStatus($userID, $bPostID, $bpStatus);
		   if($updateBoostPostStatus){
              exit('200');
		   }else{
			  exit('404');
		   }
		}
	}
	if ($type == 'uploadPaymentSuccessImage') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableVerificationFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit('Upload Failed');
								}
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if ($WasStatus == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if($digitalOceanStatus == '1'){
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									/**/
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									if($upload){
										$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $getFilename;
									 }
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								 } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedScreenShotForPaymentComplete($userID, $pathFile, NULL, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							/*AMAZON S3*/
							echo '
								<div class="i_uploaded_item in_' . $theValidateType . ' iu_f_' . $getUploadedFileID['upload_id'] . '" id="' . $getUploadedFileID['upload_id'] . '">
								' . $postTypeIcon . '
								<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
									' . $iN->iN_SelectedMenuIcon('5') . '
								</div>
								<div class="i_uploaded_file" style="background-image:url(' . $UploadSourceUrl . ');">
										<img class="i_file" src="' . $UploadSourceUrl . '" alt="' . $UploadSourceUrl . '">
								</div>
								</div>
							';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*Send Account Verificatoun Request*/
	if ($type == 'verificationRequestForBankPayment') {
		if (isset($_POST['cP']) && isset($_POST['pID'])) {
			$cardIDPhoto = mysqli_real_escape_string($db, $_POST['cP']);
			$planID = mysqli_real_escape_string($db, $_POST['pID']);
			$planData = $iN->GetPlanDetails($planID);
			$planAmount = isset($planData['amount']) ? $planData['amount'] : NULL;
		    $planPoint = isset($planData['plan_amount']) ? $planData['plan_amount'] : NULL;
			$checkCardIDPhotoExist = $iN->iN_CheckImageIDExist($cardIDPhoto, $userID);
			if (empty($cardIDPhoto) && empty($checkCardIDPhotoExist)) {
				echo 'card';
				return false;
			}
			if ($checkCardIDPhotoExist == '1') {
				$InsertNewVerificationRequest = $iN->iN_InsertNewBankPaymentVerificationRequest($userID, $cardIDPhoto, $planAmount, $planPoint,$planID);
				if ($InsertNewVerificationRequest) {
					echo '200';
				}
			} else {
				echo 'both';
			}
		}
	}
	/*Purchase The Frame*/
	if($type == 'buyFrameGift'){
	   if(isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type']) && isset($_POST['pUf']) && $_POST['pUf'] != '' && !empty($_POST['pUf'])){
	       $frameID = mysqli_real_escape_string($db, $_POST['type']);
	       $purchaseForThisUser = mysqli_real_escape_string($db, $_POST['pUf']);
	       $checFrameExist = $iN->CheckFramePlanExist($frameID);
	       $frameData = $iN->GetFramePlanDetails($frameID);
	       $framePrice = isset($frameData['f_price']) ? $frameData['f_price'] : '0';
	       if($checFrameExist && $framePrice < $userCurrentPoints){
	           $insertPurchase = $iN->iN_PurchaseFrame($userID, $purchaseForThisUser, $frameID,$onePointEqual);
	           if($insertPurchase){
	               exit('200');
	           }else{
	               exit('404');
	           }
	       }else {
	       	  exit('505');
	       }
	   }
	}
	/*Update Frame*/
	if($type == 'UpdateMyFrame'){
	    if(isset($_POST['frameID'])){
	        $frameID = mysqli_real_escape_string($db, $_POST['frameID']);
	        $updateFrame = $iN->iN_UpdateFrame($userID, $frameID);
	        if($updateFrame){
	            exit('200');
	        }else{
	            exit('400');
	        }
	    }
	}
	if ($type == 'aiBox') {
		include "../themes/$currentTheme/layouts/popup_alerts/aiBox.php";
	}
	if ($type == 'generateAiContent' && $openAiStatus == '1') {
        if (isset($_POST['uPrompt']) && !empty($_POST['uPrompt'])) {
            $userPrompt = trim(strip_tags($_POST['uPrompt']));
            $aiContent = callOpenAI($userPrompt, $opanAiKey);
            if ($aiContent != 'no') {
                $walletDone = $iN->iN_AiUsed($userID, $perAiUse);
                if ($walletDone) {
                    exit($aiContent);
                } else {
                    exit('no_enough_credit');
                }
            } else {
                exit(iN_HelpSecure($LANG['please_check_api_key']));
            }
        }
    }
	/*Delete My User Account*/
    if ($type == 'deleteMyAccount') {
        if (isset($_POST['deleteMe']) && isset($_POST['crn_password'])) {
            $deleteThisUserAccount = mysqli_real_escape_string($db, $_POST['deleteMe']);
            $currentPassword = mysqli_real_escape_string($db, $_POST['crn_password']);

            if ($deleteThisUserAccount == $userID) {
                $isValidPassword = $iN->iN_VerifyCurrentPassword($userID, $currentPassword);
                if ($isValidPassword) {
                    $deleteMyAccount = $iN->iN_DeleteMyUserAccount($userID, $deleteThisUserAccount);
                    if ($deleteMyAccount) {
                        session_destroy();
                        exit('200');
                    } else {
                        exit('500');
                    }
                } else {
                    exit('403');
                }
            } else {
                exit('401');
            }
        }
    }
} elseif (isset($_POST['f'])) {
	$loginFormClass = '';
	$type = mysqli_real_escape_string($db, $_POST['f']);
	if ($type == 'searchCreator') {
		if (isset($_POST['s'])) {
			$searchValue = mysqli_real_escape_string($db, $_POST['s']);
			$searchValueFromData = $iN->iN_GetSearchResult($iN->iN_Secure($searchValue), $showingNumberOfPost, $whicUsers);
			include "../themes/$currentTheme/layouts/header/searchResults.php";
		}
	}
	if ($type == 'forgotPass') {
		if (isset($_POST['email']) && !empty($_POST['email'])) {
			$sendEmail = mysqli_real_escape_string($db, $_POST['email']);
			$checkEmailExist = $iN->iN_CheckEmailExistForRegister($iN->iN_Secure($sendEmail));
			if ($checkEmailExist) {
				$code = md5(rand(1111, 9999) . time());
				if ($emailSendStatus == '1') {
					$insertNewCode = $iN->iN_InsertNewForgotPasswordCode($iN->iN_Secure($sendEmail), $iN->iN_Secure($code));
					$activateLink = $base_url . 'reset_password?active=' . $code;
					$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden;";
                    $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:30px; border-radius:3px;";
                    $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
                    $imgStyle = "width:100%; display:block;";
                    $titleStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
                    $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff;";
                    $linkStyle = "text-decoration:none; color:#ffffff; font-weight:500; font-size:18px; display:inline-block;";
					if ($insertNewCode) {
						if ($smtpOrMail == 'mail') {
							$mail->IsMail();
						} else if ($smtpOrMail == 'smtp') {
							$mail->isSMTP();
							$mail->Host = $smtpHost; // Specify main and backup SMTP servers
							$mail->SMTPAuth = true;
							$mail->SMTPKeepAlive = true;
							$mail->Username = $smtpUserName; // SMTP username
							$mail->Password = $smtpPassword; // SMTP password
							$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
							$mail->Port = $smtpPort;
							$mail->SMTPOptions = array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							);
						} else {
							return false;
						}
						$body = '
                            <div style="' . $wrapperStyle . '">
                              <div style="' . $containerStyle . '">

                                <div style="' . $logoBoxStyle . '">
                                  <img src="' . $siteLogoUrl . '" style="' . $imgStyle . '" />
                                </div>

                                <div style="' . $titleStyle . '">
                                  <strong>Forgot your Password?</strong> reset it below:
                                </div>

                                <div style="' . $buttonBoxStyle . '">
                                  <a href="' . $activateLink . '" style="' . $linkStyle . '">
                                    Reset Password
                                  </a>
                                </div>

                              </div>
                            </div>';
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['forgot_password']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if ($mail->send()) {
							$mail->ClearAddresses();
							echo '200';
							return true;
						}
					}
				} else {
					echo '3';
				}
			} else {
				echo '2';
			}
		} else {
			exit('1');
		}
	}

	/*Reset Password*/
	if ($type == 'iresetpass') {
		$activationCode = mysqli_real_escape_string($db, $_POST['ac']);
		$newPassword = mysqli_real_escape_string($db, $_POST['pnew']);
		$confirmNewPassword = mysqli_real_escape_string($db, $_POST['repnew']);
		$checkCodeExist = $iN->iN_CheckCodeExist($activationCode);
		if ($checkCodeExist) {
			if (strlen($newPassword) < 6 || strlen($confirmNewPassword) < 6) {
				exit('5');
			}
			if (!empty($newPassword) && $newPassword != '' && isset($newPassword) && !empty($confirmNewPassword) && $confirmNewPassword != '' && isset($confirmNewPassword)) {
				if ($newPassword != $confirmNewPassword) {
					exit('2');
				} else {
					$newPassword = sha1(md5($newPassword));
					$updateNewPassword = $iN->iN_ResetPassword($iN->iN_Secure($activationCode), $iN->iN_Secure($newPassword));
					if ($updateNewPassword) {
						exit('200');
					} else {
						exit('404');
					}
				}
			} else {
				exit('4');
			}
		}
	}
	/*Check Claim*/
	if ($type == 'claim') {
    	if (isset($_POST['clnm']) && !empty($_POST['clnm'])) {
    		$checkUserNameExist = $iN->iN_CheckUsernameExistForRegister($_POST['clnm']);

    		if ($checkUserNameExist) {
    			echo json_encode(['status' => '2']);
    			exit;
    		}

    		if (!preg_match('/^[\w]+$/', $_POST['clnm'])) {
    			echo json_encode(['status' => '5']);
    			exit;
    		}

    		echo json_encode(['status' => '200']);
    		exit;
    	} else {
    		echo json_encode(['status' => '3']);
    		exit;
    	}
    }
}
?>