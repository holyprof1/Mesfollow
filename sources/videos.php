<?php
/* videos.php — Reels page with DOM-based comments popup (no iframe/shadow) */

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* Bootstrap (adjust to your project if needed) */
if (!isset($db) || !isset($base_url)) {
  $init = __DIR__.'/../includes/init.php';
  if (is_file($init)) { require_once $init; }
}
if (!isset($db) || !$db) { die('DB not available'); }
if (!isset($base_url) || !$base_url) { die('base_url not set'); }

/* Remote storage helpers (keep your own vars) */
$s3Status           = $s3Status           ?? 0;
$WasStatus          = $WasStatus          ?? 0;
$digitalOceanStatus = $digitalOceanStatus ?? 0;
$s3Bucket = $s3Bucket ?? ''; $s3Region = $s3Region ?? '';
$WasBucket = $WasBucket ?? ''; $WasRegion = $WasRegion ?? '';
$oceanspace_name = $oceanspace_name ?? ''; $oceanregion = $oceanregion ?? '';

function mf_video_url($path,$base_url,$s3Status,$s3Bucket,$s3Region,$WasStatus,$WasBucket,$WasRegion,$digitalOceanStatus,$oceanspace_name,$oceanregion){
  if (!$path) return '';
  if ($s3Status == 1)             return 'https://'.$s3Bucket.'.s3.'.$s3Region.'.amazonaws.com/'.$path;
  if ($WasStatus == 1)            return 'https://'.$WasBucket.'.s3.'.$WasRegion.'.wasabisys.com/'.$path;
  if ($digitalOceanStatus == '1') return 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/'.$path;
  return rtrim($base_url,'/').'/'.$path;
}

function mf_truncate_words($text, $limit = 4){
  $text = trim(preg_replace('/\s+/', ' ', (string)$text));
  if ($text === '') return ['', false];
  $w = explode(' ', $text);
  if (count($w) <= $limit) return [$text, false];
  return [implode(' ', array_slice($w, 0, $limit)), true];
}

function mf_full_url($path, $base){
  if (!$path) return '';
  if (preg_match('~^https?://~',$path)) return $path;
  return rtrim($base,'/').'/'.ltrim($path,'/');
}

/* Fetch random mp4 posts + counts */
$rows = [];

// Get the target reel ID from URL hash (JavaScript will send it)
$targetReelId = isset($_GET['start']) ? (int)$_GET['start'] : 0;

// If coming from a reel link, check the URL hash
if (!$targetReelId && isset($_SERVER['HTTP_REFERER'])) {
  // Try to extract from referrer if it has #reel_123
  if (preg_match('/#reel_(\d+)/', $_SERVER['HTTP_REFERER'], $m)) {
    $targetReelId = (int)$m[1];
  }
}

$sql = "
  SELECT
    p.post_id,
    p.url_slug,
    p.post_text,
    p.post_owner_id,
    (
      SELECT up.uploaded_file_path
        FROM i_user_uploads up
       WHERE FIND_IN_SET(up.upload_id, p.post_file)
         AND LOWER(up.uploaded_file_ext) = 'mp4'
       ORDER BY RAND()
       LIMIT 1
    ) AS uploaded_file_path,
    (SELECT COUNT(*) FROM i_post_likes    L WHERE L.post_id_fk         = p.post_id) AS like_count,
    (SELECT COUNT(*) FROM i_post_comments C WHERE C.comment_post_id_fk = p.post_id) AS comment_count,
	(SELECT COUNT(*) FROM i_posts R WHERE R.shared_post_id = p.post_id) AS reshare_count,
	(SELECT COUNT(*) FROM i_saved_posts S WHERE S.saved_post_id = p.post_id) AS save_count,
    u.i_username       AS owner_username,
    u.i_user_fullname  AS owner_fullname,
    u.user_avatar      AS owner_avatar,
    -- Add priority field: 1 if this is the target reel, 0 otherwise
    IF(p.post_id = {$targetReelId}, 1, 0) AS is_priority
  FROM i_posts p
  JOIN i_users u ON u.iuid = p.post_owner_id
 WHERE p.post_status='1'
   AND EXISTS (
         SELECT 1 FROM i_user_uploads up2
          WHERE FIND_IN_SET(up2.upload_id, p.post_file)
            AND LOWER(up2.uploaded_file_ext)='mp4'
       )
 ORDER BY 
   is_priority DESC,  -- Priority reel comes FIRST
   RAND()             -- Then random order for others
 LIMIT 50
";

if ($q = mysqli_query($db,$sql)) { while ($r=mysqli_fetch_assoc($q)) $rows[]=$r; }

$currentTheme = $currentTheme ?? ($siteSettings['theme'] ?? 'Default');
$themeBase    = rtrim($base_url,'/')."/themes/{$currentTheme}";
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Reels</title>

<link rel="stylesheet" href="<?php echo $themeBase; ?>/stylesheet/style.css">
<link rel="stylesheet" href="<?php echo $themeBase; ?>/stylesheet/responsive.css">
<link rel="stylesheet" href="<?php echo $themeBase; ?>/stylesheet/colors/dark.css">


<style>
/* ===== Page shell ===== */
html,body{height:100%;margin:0;background:#000;-webkit-text-size-adjust:100%}
.pageMiddle{padding:0!important}

/* Header */
.reels-header{position:sticky;top:0;z-index:50;height:56px;display:flex;align-items:center;gap:12px;padding:0 14px;color:#fff;background:rgba(0,0,0,.85);backdrop-filter:blur(8px)}
.reels-back{display:grid;place-items:center;width:44px;height:44px;border-radius:999px;color:#fff;text-decoration:none;font-size:24px}
.reels-title{font-weight:800;font-size:18px}

/* Scroller */
.reels-container{width:100vw;max-width:100%!important;height:calc(100svh - 56px);overflow-y:scroll;scroll-snap-type:y mandatory;background:#000;overscroll-behavior:contain}

/* Each reel */
.reel-item{width:100%;height:100%;position:relative;scroll-snap-align:start;display:flex;align-items:center;justify-content:center;background:#000}
.reel-item video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;background:#000}
.reel-item video.letterbox{object-fit:contain}

/* Tap overlay */
.reel-overlay{position:absolute; inset:0; cursor:pointer}

/* Side rail */
.reel-actions{position:absolute;right:12px;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:14px;z-index:20}
.reel-btn{width:52px;height:52px;border-radius:999px;border:0;cursor:pointer;display:grid;place-items:center;text-decoration:none;background:transparent}
.reel-btn:hover{opacity:.9}
.reel-count{display:block;text-align:center;color:#fff;font-weight:700;font-size:12px;text-shadow:0 2px 8px rgba(0,0,0,.5); pointer-events:none}
.icon {
  width: 28px;
  height: 28px;
  display: block;
  fill: #fff;
  color: #fff;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,.7));
}
.reel-btn.is-liked .icon{fill:#ff3b5c;color:#ff3b5c}

/* Caption */
.reel-caption{position:absolute;left:0;right:0;top:0;z-index:15;padding:12px 14px 10px;color:#fff;font-size:14px;line-height:1.35;background:linear-gradient(180deg,rgba(0,0,0,.55) 0%,rgba(0,0,0,0) 100%);max-height:30%;overflow:hidden}
.reel-title{font-weight:800;font-size:15px;margin-bottom:4px}
.reel-desc{opacity:.95}

/* Play/Pause blip */
.play-pause-indicator{position:absolute;inset:0;display:grid;place-items:center;pointer-events:none;z-index:12;opacity:0;transition:opacity .2s ease;font-size:54px;color:rgba(255,255,255,.9);text-shadow:0 2px 12px rgba(0,0,0,.5)}
.play-pause-indicator.show{opacity:1}

/* ===== Bottom-sheet shell ===== */
.universal-panel-container{position:fixed;inset:0;z-index:1000;display:flex;align-items:flex-end;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s ease}
.universal-panel-container.is-visible{opacity:1;pointer-events:auto}
.universal-panel-overlay{position:absolute;inset:0;background:rgba(0,0,0,0);transition:background-color .25s ease}
.universal-panel-container.is-visible .universal-panel-overlay{background:rgba(0,0,0,.55)}
.universal-panel-content{position:relative;width:100%;max-width:640px;max-height:92svh;border-radius:18px 18px 0 0;overflow:hidden;transform:translateY(100%);transition:transform .25s ease;background:#fff;color:#000}
.universal-panel-container.is-visible .universal-panel-content{transform:translateY(0)}
body.panel-open{position:fixed;width:100%;overflow:hidden}

/* Inner sheet header */
.panel-header{display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid #e6e6e6;background:#fafafa}
.panel-close-btn{width:40px;height:40px;display:grid;place-items:center;cursor:pointer;border-radius:999px;background:rgba(0,0,0,.08)}
.panel-close-btn svg{width:20px;height:20px;fill:#000;}
.panel-title-text{font-weight:800;color:#000}

/* Panel body: let theme handle everything except scroll container */
.panel-body {
  display: flex;
  flex-direction: column;
  height: calc(92svh - 52px);
  overflow: hidden;
  background: #fff;
}

.panel-body .modal-comments-wrapper {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.panel-body .i_user_comments {
  flex: 1 1 auto;
  overflow-y: auto;
  padding: 15px;
}

.panel-body .i_comment_form {
  position: sticky;
  bottom: 0;
  background: #fff;
  border-top: 1px solid #e6e6e6;
  padding: 10px 12px;
  z-index: 10;
}

/* Mobile adjustments */
@media (max-width: 768px) {
  .reels-container {
    max-width: 480px;
    margin: 0 auto;
  }
  .universal-panel-content {
    max-width: 100%;
  }
}
/* ===== Reels comments: make sure it looks sane even outside the original modal ===== */
.panel-body .modal-comments-wrapper { display:flex; flex-direction:column; height:100%; background:#fff; }
.panel-body .i_user_comments      { flex:1 1 auto; overflow-y:auto; padding:12px; }
.panel-body .i_comment_form       { position:sticky; bottom:0; background:#fff; border-top:1px solid #e6e6e6; padding:10px 12px; z-index:10; }

/* Comment list avatars */
.panel-body .i_post_user_commented_avatar_out { width:40px; flex:0 0 40px; }
.panel-body .i_post_user_commented_avatar     { width:32px; height:32px; border-radius:999px; object-fit:cover; display:block; }

/* Comment form avatar */
.panel-body .i_post_user_comment_avatar img   { width:36px; height:36px; border-radius:999px; object-fit:cover; display:block; }

/* Keep images in comments from blowing up */
.panel-body .i_user_comments img { max-width:100%; height:auto; }

/* Optional: compact spacing */
.panel-body .i_u_comment_body { display:flex; gap:10px; padding:8px 0; border-bottom:1px solid #f3f3f3; }
.panel-body .i_user_commented_body { flex:1; min-width:0; }
.panel-body .i_user_commented_user_infos a { font-weight:600; color:#111; text-decoration:none; }
.panel-body .i_user_comment_text { color:#222; line-height:1.35; word-wrap:break-word; }
	/* === Reels comments (scoped to the sheet) === */
.panel-body .modal-comments-wrapper{display:flex;flex-direction:column;height:100%;background:#fff}
.panel-body .i_user_comments{flex:1 1 auto;overflow:auto;padding:12px}
.panel-body .i_u_comment_body{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f3f3f3}
.panel-body .i_post_user_commented_avatar_out{width:36px;flex:0 0 36px}
.panel-body .i_post_user_commented_avatar{width:36px;height:36px;border-radius:999px;object-fit:cover;display:block}

.panel-body .i_user_commented_body{flex:1;min-width:0}
.panel-body .i_user_commented_user_infos a{font-weight:700;color:#111;text-decoration:none}
.panel-body .i_user_comment_text{color:#222;line-height:1.4;word-wrap:break-word}

.panel-body .i_comment_form{position:sticky;bottom:0;background:#fff;border-top:1px solid #e6e6e6;padding:10px 12px;z-index:10;display:flex;gap:10px;align-items:flex-start}
.panel-body .i_post_user_comment_avatar img{width:36px;height:36px;border-radius:999px;object-fit:cover;display:block}
.panel-body .i_comment_form_textarea{flex:1}
.panel-body textarea.comment{width:100%;min-height:42px;max-height:160px;padding:10px;border:1px solid #ddd;border-radius:12px;font:inherit;resize:vertical}

/* keep inline media small */
.panel-body .modal-comments-wrapper img{max-width:100%;height:auto}
.panel-body .modal-comments-wrapper svg{max-width:24px;max-height:24px}


/* avatars */
.panel-body .i_post_user_comment_avatar img,
.panel-body .i_post_user_commented_avatar { width:36px; height:36px; border-radius:999px; object-fit:cover; display:block; }

/* keep any SVG/icons small – many themes default svg {width:100%} */
.panel-body .i_comment_fast_answers svg { width:20px; height:20px; display:block; }
.panel-body svg { max-width:24px; max-height:24px; }

/* list items */
.panel-body .i_u_comment_body { display:flex; gap:10px; padding:8px 12px; border-bottom:1px solid #f3f3f3; }
.panel-body .i_user_commented_body { flex:1; min-width:0; }
.panel-body .i_user_commented_user_infos a { font-weight:600; color:#111; text-decoration:none; }
.panel-body .i_user_comment_text { color:#222; line-height:1.35; word-wrap:break-word; }

/* ensure images inside comments don’t blow up */
.panel-body .i_user_comments img { max-width:100%; height:auto; }


	</style>
<style>
/* the sheet is a flex column; let the list shrink to enable scrolling */
.panel-body{display:flex;flex-direction:column;height:calc(92svh - 52px);overflow:hidden}
.modal-comments-wrapper{display:flex;flex-direction:column;min-height:0;height:100%}

/* scroll area */
.modal-comments-wrapper .i_user_comments{
  flex:1 1 auto;
  min-height:0;              /* <- important */
  overflow-y:auto;
  -webkit-overflow-scrolling:touch;
  touch-action:pan-y;
  padding:12px;
}

/* composer (one row, Send at the front/right) */
.modal-comments-wrapper .i_comment_form{
  position:sticky;bottom:0;z-index:10;
  display:flex;align-items:center;gap:10px;
  padding:10px 12px;background:#fff;border-top:1px solid #e6e6e6;
}
.modal-comments-wrapper .i_comment_form_textarea{flex:1 1 auto; display:flex}
.modal-comments-wrapper textarea.comment{
  width:100%;min-height:42px;max-height:140px;resize:vertical;
  padding:10px;border:1px solid #ddd;border-radius:12px;font:inherit;
}
.modal-comments-wrapper .sndcom{
  padding:8px 14px;border:0;border-radius:12px;background:#111;color:#fff;cursor:pointer;
}

/* mentions */
.modal-comments-wrapper a.mention{ color:#3897f0;text-decoration:none }
.modal-comments-wrapper a.mention:hover{ text-decoration:underline }
	
	/* comments sheet scroll overrides */
.universal-panel-content{ display:flex; flex-direction:column; max-height:92svh; }
.panel-header{ flex:0 0 auto; }
.panel-body{ flex:1 1 auto; min-height:0; overflow:hidden; }
.panel-body .modal-comments-wrapper{ display:flex; flex-direction:column; flex:1 1 auto; min-height:0; }
.panel-body .i_user_comments{ flex:1 1 auto; min-height:0; overflow:auto; -webkit-overflow-scrolling:touch; }
	
	/* let the ajax root flex, so the inner list can shrink and scroll */
.panel-body .mf-comments-root{
  display:flex;
  flex:1 1 auto;
  min-height:0;
}
/* ===== Author chip (top-left) ===== */
.reel-author{
  position:absolute; left:12px; top:12px; z-index:25;
  display:inline-flex; align-items:center; gap:10px;
  padding:8px 10px; border-radius:999px;
  background:rgba(255,255,255,.92); backdrop-filter:blur(8px);
  box-shadow:0 6px 20px rgba(0,0,0,.18);
}
.reel-author .ra-ava{
  display:block; width:28px; height:28px; border-radius:999px; overflow:hidden; flex:0 0 28px;
}
.reel-author .ra-ava img{ width:100%; height:100%; object-fit:cover; display:block }
.reel-author .ra-name{
  font-weight:700; color:#111; text-decoration:none; max-width:38vw; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.reel-author .ra-follow{
  margin-left:6px; padding:6px 10px; border:0; border-radius:999px; cursor:pointer;
  background:#111; color:#fff; font-weight:700; font-size:12px;
}
.reel-author .ra-follow.is-following{
  background:#e5e7eb; color:#111;
}
@media (max-width: 480px){
  .reel-author{ gap:8px; padding:7px 9px }
  .reel-author .ra-name{ max-width:46vw }
}

/* Right rail: keep white icons by default */
.icon{fill:#fff;color:#fff}

/* Bottom meta (author + caption) */
.reel-meta{
  position:absolute; left:12px; right:12px; bottom:12px; z-index:25;
  display:flex; gap:10px; align-items:flex-start;
  padding:10px 12px; border-radius:14px;
  background:rgba(0,0,0,.55); backdrop-filter:blur(8px);
  color:#fff;
}
.rm-ava{display:block; width:36px; height:36px; border-radius:999px; overflow:hidden; flex:0 0 36px}
.rm-ava img{width:100%;height:100%;object-fit:cover;display:block}
.rm-text{flex:1; min-width:0}
.rm-top{display:flex; align-items:center; gap:10px; margin-bottom:6px}
.rm-name{color:#fff; font-weight:800; text-decoration:none; max-width:40vw; white-space:nowrap; overflow:hidden; text-overflow:ellipsis}
.rm-follow{
  padding:6px 12px; border-radius:999px; background:transparent; border:1px solid rgba(255,255,255,.6);
  color:#fff; font-weight:700; cursor:pointer
}
.rm-follow.is-following{ background:rgba(255,255,255,.2); }

.rm-cap{font-size:14px; line-height:1.35}
.rm-more{
  margin-left:6px; padding:0; border:0; background:transparent; color:#ddd; cursor:pointer;
  text-decoration:underline; font-weight:600; font-size:14px
}
/* Right rail layout stays the same; normalize icon paint */

	
	
	/* ===== icon painting ===== */
.reel-btn, .reel-btn .icon,
.reel-open, .reel-open .icon { color:#fff }           /* default = white */

.reel-btn .icon [stroke], .reel-open .icon [stroke] {  /* make all strokes use currentColor */
  stroke: currentColor !important;
}
.reel-btn .icon [fill], .reel-open .icon [fill] {      /* make all fills use currentColor */
  fill: currentColor !important;
}

/* Active like = blue (change if you prefer another color) */
.reel-btn.is-liked .icon { color:#3b82f6 }

/* the overlay must sit UNDER the controls */
.reel-overlay{ position:absolute; inset:0; cursor:pointer; z-index:5 }
.reel-actions{ z-index:20 }

/* Top-right OPEN button */
.reel-open{
  position:absolute; top:8px; right:8px; z-index:30;
  width:44px; height:44px; display:grid; place-items:center;
  border-radius:999px; background:rgba(0,0,0,.35); text-decoration:none;
}
.reel-open:hover{ background:rgba(0,0,0,.5) }
.reel-open .icon{ width:24px; height:24px; display:block }
	
	/* Keep overlay *under* the buttons */
.reel-overlay{ position:absolute; inset:0; cursor:pointer; z-index:5; }

/* Right rail reliably above overlay */
.reel-actions{ position:absolute; right:12px; top:50%; transform:translateY(-50%);
  display:flex; flex-direction:column; gap:14px; z-index:20; }

/* Scroller: smooth, reliable pan */
.reels-container{ overflow-y:auto; -webkit-overflow-scrolling:touch; touch-action:pan-y; }

/* Icon painting from currentColor for all inline svgs (theme-safe) */
.reel-btn, .reel-btn .icon, .reel-open, .reel-open .icon { color:#fff; }
.reel-btn .icon [stroke], .reel-open .icon [stroke]{ stroke:currentColor !important; }
.reel-btn .icon [fill],   .reel-open .icon [fill]{   fill:currentColor   !important; }

/* Liked state color (blue). Change if you want */
.reel-btn.is-liked .icon{ color:#3b82f6; }

/* Top-right "Open Post" button */
.reel-open{
  position:absolute; top:8px; right:8px; z-index:30;
  width:40px; height:40px; display:grid; place-items:center;
  border-radius:999px; background:rgba(0,0,0,.35); text-decoration:none;
}
.reel-open:hover{ background:rgba(0,0,0,.5); }
.reel-open .icon{ width:22px; height:22px; }

/* ==== 1) Fullscreen reels (no header space) ==== */
.reels-container{ height:100svh; }

/* Floating back button to mirror .reel-open (top-right) */
.reel-back-fab{
  position:fixed; top:8px; left:8px; z-index:30;
  width:40px; height:40px; display:grid; place-items:center;
  border-radius:999px; background:rgba(0,0,0,.35); text-decoration:none;
}
.reel-back-fab:hover{ background:rgba(0,0,0,.5); }
.reel-back-fab .icon{ width:22px; height:22px; color:#fff; }

/* Ensure overlay sits under controls (kept) */
.reel-overlay{ z-index:5; }
.reel-actions{ z-index:20; }

/* ==== 2) Unify fonts with Follow/Following look ==== */
/* Follow button already uses a bold system font. Apply globally. */
:root{
  --reels-font: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue",
                Arial, "Noto Sans", "Liberation Sans", sans-serif;
}
html, body, .reels-container, .reel-item,
.reel-count, .reel-meta, .rm-name, .rm-cap, .rm-more,
.panel-body, .panel-body *{
  font-family: var(--reels-font) !important;
  letter-spacing: 0; /* match button look */
}

/* Make labels feel like the Follow button weight */
.reels-title, .rm-name, .reel-count, .panel-title-text { font-weight:700; }

/* ==== 3) Lighter bottom meta background (clearer) ==== */
.reel-meta{
  /* was rgba(0,0,0,.55) */
  background: rgba(0,0,0,.35);
  backdrop-filter: blur(8px);
}

/* Slightly soften the author chip too (optional) */
.reel-author{
  /* was rgba(255,255,255,.92) */
  background: rgba(255,255,255,.85);
}

/* If you want the name+Follow capsule even clearer, reduce just its fill */
.rm-ava + .rm-text .rm-top + .rm-cap{ opacity:.95; }      /* caption text */
.rm-top .rm-name{ opacity:.95; }                          /* name text */
.reel-meta{ box-shadow: 0 6px 20px rgba(0,0,0,.18); }     /* keep lift */
	
	/* --- Reels: no page scroll, only the container scrolls --- */
html, body {
  height: 100%;
  margin: 0;
  overflow: hidden;            /* kill browser/page scrollbar */
  background: #000;
}

/* Full viewport height for the scroller */
.reels-container{
  height: 100dvh;              /* dynamic viewport height (iOS safe) */
  max-width: 100% !important;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
  touch-action: pan-y;

  /* Snap settings */
  scroll-snap-type: y mandatory;

  /* Hide scrollbar (Firefox + WebKit) */
  scrollbar-width: none;
}
.reels-container::-webkit-scrollbar{
  width: 0;
  height: 0;
  display: none;
}

/* Each reel is exactly one screen tall and must stop at every snap point */
.reel-item{
  height: 100dvh;
  scroll-snap-align: start;
  scroll-snap-stop: always;    /* prevents skipping multiple items */
  position: relative;
}


/* lift the follow/caption card so the line can live underneath it */
/* keep caption just above the bottom scrubber */
.reel-meta{
  bottom: calc(18px + env(safe-area-inset-bottom)); /* closer to bottom */
}

/* scrubber: last line at the very bottom */
.reel-scrubber{
  position:absolute;
  left:12px; right:12px;
  bottom:calc(0px + env(safe-area-inset-bottom));
  z-index:40;
  height:20px;                /* tap target height (visual track is inside) */
  display:flex; align-items:center;
  pointer-events:auto;
}
.reel-scrubber-track{
  position:relative;
  width:100%;
  height:8px;                 /* >= dot so it’s never clipped */
  border-radius:999px;
  background:rgba(255,255,255,.25);
  overflow:hidden;
}
.reel-scrubber-buffer, .reel-scrubber-fill{
  position:absolute; left:0; top:0; bottom:0; width:0%;
}
.reel-scrubber-buffer{ background:rgba(255,255,255,.45); }
.reel-scrubber-fill  { background:#fff; }

/* visible dot */
.reel-scrubber-thumb{
  position:absolute; top:50%;
  width:8px; height:8px;      /* equals track height => not clipped */
  border-radius:50%;
  background:#fff;
  transform:translate(-50%,-50%);
}
.reel-scrubber.is-active .reel-scrubber-track{ height:10px; }
.reel-scrubber.is-active .reel-scrubber-thumb{ width:10px; height:10px; }

/* TikTok-like play icon (no circle, slightly dim) */
.play-pause-indicator{
  position:absolute; inset:0;
  display:grid; place-items:center;
  pointer-events:none; z-index:32;
  opacity:0; transition:opacity .18s ease, transform .18s ease;
  transform:scale(.96);
}
.play-pause-indicator.is-paused{
  opacity:.22;                /* much fainter */
  transform:scale(1);
}
/* make the triangle smaller without changing your SVG */
.play-pause-indicator svg{
  width:80px; height:80px;    /* was 120 */
}

.reel-scrubber-thumb{
  box-shadow: 0 0 0 2px rgba(0,0,0,.35);
}

/* === Reels scrubber: hide thumb, make bar thinner + low-contrast === */

/* kill the dot entirely */
.reel-scrubber-thumb{ display:none !important; box-shadow:none !important; }

/* slimmer track */
.reel-scrubber-track{
  height:4px !important;               /* was 8px */
  background: rgba(255,255,255,.12) !important;  /* lighter */
}

/* even when dragging, keep it slim */
.reel-scrubber.is-active .reel-scrubber-track{
  height:6px !important;               /* was 10px */
}

/* make buffer + progress subtle */
.reel-scrubber-buffer{ background: rgba(255,255,255,.18) !important; }
.reel-scrubber-fill  { background: rgba(255,255,255,.65) !important; } /* not full white */

/* if the whole bar still feels heavy, you can fade the wrapper too */
.reel-scrubber{ opacity:.85; }
	
	/* --- Make all inline SVG icons look bolder --- */
.reel-btn .icon, .reel-open .icon { 
  /* minor scale fattens shapes a bit without blurring */
  transform: scale(1.06);
}

/* Thicken strokes if the SVG uses strokes */
.reel-btn .icon [stroke], .reel-open .icon [stroke]{
  stroke: currentColor !important;
  stroke-width: 2.2 !important;    /* was usually ~1.5 */
  vector-effect: non-scaling-stroke; /* keeps lines crisp on scale */
  paint-order: stroke fill;          /* stroke drawn on top to look bolder */
}

/* Filled-only icons: add a subtle shadow edge so they read “heavier” */
.reel-btn .icon path {
  filter: drop-shadow(0 0 0.6px rgba(0,0,0,.25));
}

/* ===== Reel action icons: bolder + shadow (matches counter legibility) ===== */
:root{
  --reel-icon-shadow: 0 3px 12px rgba(0,0,0,.85); /* deep soft shadow, like .reel-count */
}

/* 1) Stronger drop-shadow + slight scale to visually “thicken” */
.reel-actions .reel-btn .icon,
.reel-open .icon{
  filter: drop-shadow(var(--reel-icon-shadow));
  transform: scale(1.08);                 /* gentle heft without blur */
  will-change: transform, filter;         /* keep it crisp while animating */
}

/* 2) Make stroked icons look heavier (keeps edges crisp when scaled) */
.reel-actions .reel-btn .icon [stroke],
.reel-open .icon [stroke]{
  stroke: currentColor !important;
  stroke-width: 2.4 !important;           /* was ~1.5–2 in most sets */
  vector-effect: non-scaling-stroke;      /* prevents fuzzy lines */
  paint-order: stroke fill;               /* stroke drawn above fill */
}

/* 3) Filled-only icons: add a subtle dark edge so they pop on video */
.reel-actions .reel-btn .icon path:not([stroke]),
.reel-open .icon path:not([stroke]){
  /* tiny outline to boost contrast over busy frames */
  stroke: rgba(0,0,0,.55);
  stroke-width: .9px;
  paint-order: stroke fill;
}

/* 4) Keep “liked” state bright but still shadowed */
.reel-btn.is-liked .icon{
  color: #3b82f6;                          /* your liked color */
  filter: drop-shadow(var(--reel-icon-shadow));
}

/* Optional: a touch larger on very small screens for readability */
@media (max-width: 420px){
  .reel-actions .reel-btn .icon,
  .reel-open .icon{ transform: scale(1.12); }
}

</style>
	

<script>window.siteurl="<?php echo htmlspecialchars(rtrim($base_url,'/').'/',ENT_QUOTES,'UTF-8'); ?>";</script>

<!-- jQuery -->
<script src="<?php echo $themeBase; ?>/javascript/jquery-3.6.0.min.js"></script>
<script>if(!window.jQuery){document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>')}</script>
	<script src="<?php echo $themeBase; ?>/javascript/inora.js"></script>
</head>
<body>

<a class="reel-back-fab"
   href="<?= htmlspecialchars(rtrim($base_url,'/').'/',ENT_QUOTES,'UTF-8'); ?>"
   aria-label="Back to home">
  <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
  </svg>
</a>


<div class="reels-container">
<?php
/* ====== COPY ICON IDS FROM YOUR FEED ====== */
$ICON_LIKE_OFF = '17';
$ICON_LIKE_ON  = '18';
$ICON_COMMENT  = '20';
$ICON_SHARE    = '21';   // device share
$ICON_RESHARE  = '19';   // quote / re-share (in-app)
$ICON_SAVE_OFF = '22';   // bookmark empty
$ICON_SAVE_ON  = '63';   // bookmark filled
/* ========================================= */

/* Feed endpoints (kept for your like JS) */
$EP_LIKE_F       = 'p_like';   $EP_LIKE_KEY   = 'post';
$EP_SAVE_F      = 'savePost';    $EP_SAVE_KEY    = 'post_id';
$EP_RESHARE_F   = 'reSharePost'; $EP_RESHARE_KEY = 'post_id';
?>
	
	<?php
// Safe htmlspecialchars that accepts null without warnings
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Always-visible inline SVGs for SAVE OFF / ON
$SVG_SAVE_OFF = '<svg viewBox="0 0 24 24" class="icon" aria-hidden="true"><path d="M17 3H7a2 2 0 0 0-2 2v16l7-3 7 3V5a2 2 0 0 0-2-2zm0 15.17-5-2.14-5 2.14V5h10v13.17z"/></svg>';
$SVG_SAVE_ON  = '<svg viewBox="0 0 24 24" class="icon" aria-hidden="true"><path d="M17 3H7a2 2 0 0 0-2 2v16l7-3 7 3V5a2 2 0 0 0-2-2z"/></svg>';
?>


<?php if ($rows): foreach ($rows as $r):
  $pid   = (int)$r['post_id'];
  $slug  = trim($r['url_slug'] ?? '');
  $likes = (int)$r['like_count'];
  $comms = (int)$r['comment_count'];
	$reshares = (int)($r['reshare_count'] ?? 0);
	$saves = (int)($r['save_count'] ?? 0);
  $vurl  = mf_video_url($r['uploaded_file_path'] ?? '', $base_url, $s3Status,$s3Bucket,$s3Region,$WasStatus,$WasBucket,$WasRegion,$digitalOceanStatus,$oceanspace_name,$oceanregion);
  $purl  = rtrim($base_url,'/').'/post/'.($slug ? "{$slug}_{$pid}" : $pid);

  /* liked/saved before? */
  $likedBefore = (!empty($userID) && isset($iN) && method_exists($iN,'iN_CheckPostLikedBefore')
                  && $iN->iN_CheckPostLikedBefore((int)$userID, (int)$pid)) ? 1 : 0;

  $savedBefore = (!empty($userID) && isset($iN) && method_exists($iN,'iN_CheckPostSavedBefore')
                  && $iN->iN_CheckPostSavedBefore((int)$userID, (int)$pid)) ? 1 : 0;

  /* owner + caption (optional) */
  $ownerId   = (int)$r['post_owner_id'];
  $ownerU    = trim($r['owner_username'] ?? '');
  $ownerName = trim($r['owner_fullname'] ?: $ownerU);
  $ownerUrl  = rtrim($base_url,'/').'/'.$ownerU;
  $ownerAva  = mf_full_url($r['owner_avatar'] ?? '', $base_url);
  $isFollowing = (!empty($userID) && (int)$userID !== $ownerId && isset($iN) && method_exists($iN,'iN_GetRelationsipBetweenTwoUsers'))
                 ? in_array((string)$iN->iN_GetRelationsipBetweenTwoUsers((int)$userID, $ownerId), ['flwr','following','subscriber','1','2'], true)
                 : false;

  $capRaw = trim((string)($r['post_text'] ?? ''));
  list($capShort, $needsMore) = mf_truncate_words(strip_tags($capRaw), 4);

  /* pre-render save icon exactly as feed expects (22/63) */
  $pSaveStatusBtn = html_entity_decode($iN->iN_SelectedMenuIcon($savedBefore ? $ICON_SAVE_ON : $ICON_SAVE_OFF));
?>
  <div class="reel-item" data-post-id="<?php echo $pid; ?>">

    <!-- Video & tap overlay -->
    <video src="<?php echo htmlspecialchars($vurl,ENT_QUOTES,'UTF-8'); ?>" muted playsinline preload="metadata" loop></video>
    <div class="reel-overlay" aria-label="Play/Pause"></div>

    <!-- Right rail -->
    <div class="reel-actions">

      <!-- LIKE (17/18) -->
      <div class="rail-like">
        <button type="button"
                class="reel-btn js-like <?php echo $likedBefore ? 'is-liked' : ''; ?>"
                data-id="<?php echo $pid; ?>"
                data-liked="<?php echo $likedBefore; ?>"
                data-f="<?php echo $EP_LIKE_F; ?>"
                data-key="<?php echo $EP_LIKE_KEY; ?>"
                aria-label="Like">
          <span class="icon js-like-ico">
            <?php
              echo $likedBefore
                ? html_entity_decode($iN->iN_SelectedMenuIcon($ICON_LIKE_ON))
                : html_entity_decode($iN->iN_SelectedMenuIcon($ICON_LIKE_OFF));
            ?>
          </span>
        </button>
        <span class="reel-count" id="lkc_<?php echo $pid; ?>"><?php echo number_format($likes); ?></span>
      </div>

      <!-- COMMENT (20) -->
      <div class="rail-comment">
        <a href="javascript:void(0)" class="reel-btn open-post-modal" data-id="<?php echo $pid; ?>" aria-label="Comments">
          <span class="icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon($ICON_COMMENT)); ?></span>
        </a>
        <span class="reel-count" id="cmc_<?php echo $pid; ?>"><?php echo number_format($comms); ?></span>
      </div>
		<div class="rail-save">
    <div class="reel-btn reels-save-button in_save_<?php echo iN_HelpSecure($pid); ?>" data-id="<?php echo iN_HelpSecure($pid); ?>" data-issaved="<?php echo $savedBefore ? '1' : '0'; ?>">
        <?php if ($savedBefore) { ?>
            <svg id="Layer_1" class="icon" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M386.123,0H125.877a59.47,59.47,0,0,0-59.4,59.4V480.191a31.8,31.8,0,0,0,52.156,24.375l132.717-110.98a7.074,7.074,0,0,1,9.3,0l132.717,110.98A31.578,31.578,0,0,0,413.684,512a31.9,31.9,0,0,0,13.522-3.034,31.489,31.489,0,0,0,18.317-28.8V59.4A59.47,59.47,0,0,0,386.123,0Z"/></svg>
        <?php } else { ?>
            <svg id="Layer_1" class="icon" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m386.123 0h-260.246a59.47 59.47 0 0 0 -59.4 59.4v420.791a31.765 31.765 0 0 0 52.156 24.375l132.717-110.98a7.074 7.074 0 0 1 9.3 0l132.717 110.98a31.578 31.578 0 0 0 20.317 7.456 31.9 31.9 0 0 0 13.522-3.034 31.489 31.489 0 0 0 18.317-28.8v-420.788a59.47 59.47 0 0 0 -59.4-59.4zm23.4 471.148-125.778-105.179a43.219 43.219 0 0 0 -55.491 0l-125.779 105.179v-411.748a23.428 23.428 0 0 1 23.4-23.4h260.248a23.428 23.428 0 0 1 23.4 23.4z"/></svg>
        <?php } ?>
    </div>
	<span class="reel-count" id="svc_<?php echo $pid; ?>"><?php echo number_format($saves); ?></span>
</div>

     

      <!-- RE-SHARE / QUOTE (19) – class in_share, id=share_<postId> -->
      <div class="rail-reshare">
        <div class="reel-btn in_share"
             id="share_<?php echo $pid; ?>" data-id="<?php echo $pid; ?>"
             role="button" aria-label="Re-share">
          <span class="icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon($ICON_RESHARE)); ?></span>
        </div>
		  <span class="reel-count" id="rsc_<?php echo $pid; ?>"><?php echo number_format($reshares); ?></span>
      </div>


 <!-- DEVICE SHARE (21) – native share/copy -->
      <div class="rail-share">
        <button type="button"
                class="reel-btn js-share"
                data-url="<?php echo htmlspecialchars($purl,ENT_QUOTES); ?>"
                aria-label="Share">
          <span class="icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon($ICON_SHARE)); ?></span>
        </button>
      </div>

    </div><!-- /.reel-actions -->

    <!-- Top-right OPEN post button -->
    <a class="reel-open"
       href="<?php echo htmlspecialchars($purl,ENT_QUOTES,'UTF-8'); ?>"
       target="_blank" rel="noopener" aria-label="Open post">
      <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42 9.3-9.29H14V3z"/>
        <path d="M5 5h5V3H3v7h2V5z"/>
      </svg>
    </a>

    <!-- Bottom meta: author + follow + caption -->
    <div class="reel-meta">
      <a class="rm-ava" href="<?php echo htmlspecialchars($ownerUrl,ENT_QUOTES); ?>">
        <img src="<?php echo htmlspecialchars($ownerAva ?: $themeBase.'/img/defaults/avatar.png',ENT_QUOTES); ?>" alt="">
      </a>
      <div class="rm-text">
        <div class="rm-top">
          <a class="rm-name" href="<?php echo htmlspecialchars($ownerUrl,ENT_QUOTES); ?>"><?php echo htmlspecialchars($ownerName,ENT_QUOTES); ?></a>
          <?php if (!empty($userID) && (int)$userID !== $ownerId): ?>
           <button class="rm-follow js-follow <?php echo $isFollowing?'is-following':''; ?>"
        data-uid="<?php echo $ownerId; ?>">
  <?php echo $isFollowing ? ($LANG['following'] ?? 'Following') : ($LANG['follow'] ?? 'Follow'); ?>
</button>
          <?php endif; ?>
        </div>

        <?php if ($capRaw !== ''): ?>
          <div class="rm-cap" data-full="<?php echo htmlspecialchars($capRaw,ENT_QUOTES); ?>">
            <span class="rm-cap-short"><?php echo htmlspecialchars($capShort,ENT_QUOTES); ?></span>
            <?php if ($needsMore): ?><button class="rm-more">Show more</button><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

   <div class="play-pause-indicator" aria-hidden="true" role="presentation">
  <svg viewBox="0 0 120 120" width="120" height="120">
    <path d="M50 40 L50 80 L85 60 Z" fill="#fff"></path>
  </svg>
</div>


  </div>
<?php endforeach; else: ?>
  <div style="color:#fff;text-align:center;padding:40px;">No videos have been posted yet.</div>
<?php endif; ?>
</div><!-- /.reels-container -->






<script>
/* ===== Reels behaviour ===== */
document.addEventListener('DOMContentLoaded', function () {
  const reels = Array.from(document.querySelectorAll('.reel-item'));
  let userInteracted  = false;
  let isMutedGlobally = false;

  function fitVideo(v){
    const box = v.parentElement.getBoundingClientRect();
    const videoAR = v.videoWidth / Math.max(1, v.videoHeight);
    const boxAR   = box.width    / Math.max(1, box.height);
    if (videoAR > boxAR) v.classList.add('letterbox'); else v.classList.remove('letterbox');
  }
  function refitAll(){ document.querySelectorAll('.reel-item video').forEach(v=>{ if (v.videoWidth) fitVideo(v); }); }
  addEventListener('resize', refitAll, { passive:true });
  addEventListener('orientationchange', refitAll);

  function updateMuteUI(){
    reels.forEach(r=>{
      const v = r.querySelector('video'); if (!v) return;
      v.muted = !userInteracted ? true : isMutedGlobally;
    });
  }
  function pauseOthers(except){ reels.forEach(r=>{ if (r!==except){ const v=r.querySelector('video'); if (v && !v.paused) v.pause(); } }); }
  function playOne(reel){
    const v = reel.querySelector('video'); if (!v) return;
    pauseOthers(reel);
    v.muted = !userInteracted ? true : isMutedGlobally;
    if (v.readyState >= 1) fitVideo(v); else v.addEventListener('loadedmetadata', () => fitVideo(v), { once:true });
    v.play().catch(()=>{});
  }

 function pauseOthers(except){
  reels.forEach(r=>{
    if(r!==except){
      const v=r.querySelector('video');
      if(v && !v.paused) v.pause();
    }
  });
}
function playOne(reel){
  const v = reel.querySelector('video'); if(!v) return;
  pauseOthers(reel);
  v.muted = !userInteracted ? true : isMutedGlobally;
  // encourage buffering for the current one
  v.setAttribute('preload','auto');
  if (v.readyState >= 2) { v.play().catch(()=>{}); }
  else {
    v.load(); // ensure it actually starts loading on some devices
    const tryPlay = ()=> v.play().catch(()=>{});
    v.addEventListener('canplay', tryPlay, { once:true });
  }
}

const io = new IntersectionObserver((entries)=>{
  entries.forEach(entry=>{
    const reel = entry.target;
    const v = reel.querySelector('video'); if(!v) return;
    if (entry.isIntersecting) {
      playOne(reel);
    } else {
      v.pause();
    }
  });
}, {
  threshold: 0.5,
  rootMargin: '0px 0px -20% 0px'   // start a bit earlier, avoid stalling at edges
});

reels.forEach(reel=>{
  io.observe(reel);
  const v  = reel.querySelector('video');
  const ov = reel.querySelector('.reel-overlay');
  const pp = reel.querySelector('.play-pause-indicator');

  v.addEventListener('loadedmetadata', ()=>fitVideo(v));
  v.addEventListener('waiting', ()=>{ /* gently try resume when data arrives */ v.addEventListener('canplay', ()=>v.play().catch(()=>{}), { once:true }); });
  v.addEventListener('error', ()=>{ /* optional: fallback UI */ });

 ov.addEventListener('click', ()=>{
  userInteracted = true;
  if (v.paused) { playOne(reel); } else { v.pause(); }
  updateMuteUI();
}, { passive:true });

/* keep icon in sync */
v.addEventListener('play',  ()=> { pauseOthers(reel); if (pp) pp.classList.remove('is-paused'); });
v.addEventListener('pause', ()=> { if (pp) pp.classList.add('is-paused'); });

/* initial state */
if (v.paused && pp) pp.classList.add('is-paused');

});

updateMuteUI();

});




/* ===== Bottom sheet (MAIN DOM) ===== */
function lockBodyScroll() {
  if (document.body.classList.contains('panel-open')) return;
  const y = window.scrollY || document.documentElement.scrollTop || 0;
  document.body.dataset.scrollY = String(y);
  document.body.style.top = `-${y}px`;
  document.body.classList.add('panel-open');
}
function unlockBodyScroll() {
  if (!document.body.classList.contains('panel-open')) return;
  const y = parseInt(document.body.dataset.scrollY || '0', 10);
  document.body.classList.remove('panel-open');
  document.body.style.top = '';
  delete document.body.dataset.scrollY;
  window.scrollTo(0, y);
}

function openCommentsDOM(postID){
  $('.universal-panel-container').remove();

  var shell = $(`
    <div class="universal-panel-container">
      <div class="universal-panel-overlay"></div>
      <div class="universal-panel-content">
        <div class="panel-header">
          <div class="panel-close-btn"><svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg></div>
          <div class="panel-title-text">Comments</div>
        </div>
        <div class="panel-body">
          <div class="mf-comments-root">
            <div style="padding:14px">Loading…</div>
          </div>
        </div>
      </div>
    </div>
  `);

  $('body').append(shell);
  lockBodyScroll();
  setTimeout(()=> shell.addClass('is-visible'), 15);

  shell.on('click', '.panel-close-btn, .universal-panel-overlay', function(){
    shell.removeClass('is-visible'); unlockBodyScroll(); setTimeout(()=> shell.remove(), 180);
  });

  // Fetch the same fragment you showed me
  $.ajax({
    type:'POST',
    url: window.siteurl + 'requests/request.php',
    data: { f:'getReelComments', post_id: postID },
success: function (html) {
  var $root = shell.find('.mf-comments-root').empty();

  // append the fragment we got
  $root.append(html);
	initSheetMentions($root);


  // scroll comments list to bottom if present
  var $list = $root.find('.i_user_comments').first();
  if ($list.length) { $list.scrollTop($list[0].scrollHeight); }

  // ensure “Send” is visible if your theme hides icons
  var $snd = $root.find('.i_comment_footer .i_fa_body.sndcom');
  if ($snd.length && !$snd.text().trim()) { $snd.text('Send'); }

  // notify any theme JS that a comments block mounted
  setTimeout(function(){ $(document).trigger('mf:comments-mounted', [postID]); }, 0);
},


    error: function(){ shell.find('.mf-comments-root').html('<div style="padding:14px;color:#f88">Failed to load.</div>'); }
  });
}

$(document).on('click', '.open-post-modal', function(e){
  e.preventDefault(); e.stopPropagation();
  var id = $(this).data('id');
  if (!id) return;
  openCommentsDOM(id);
});
</script>
	<script>
/* Fallback: send comment if the main inora handlers are not active */
$(document).on('click', '.mf-send-comment', function(){
  var $btn = $(this);
  var $form = $btn.closest('.i_comment_form');
  var pid = $btn.data('id') || $form.find('.nwComment').data('id');
  var txt = ($form.find('.nwComment').val() || '').trim();
  if (!pid || !txt) return;

  var st = $form.find('#stic_'+pid).val() || '';
  var gf = $form.find('#cgif_'+pid).val() || '';

  $.ajax({
    type:'POST',
    url: window.siteurl + 'requests/request.php',
    data: { f:'comment', id:pid, val:txt, sticker:st, gf:gf },
    success:function(html){
      var $list = $('#i_user_comments_'+pid);
      if ($list.length && html && html !== '404'){ $list.append(html); }
      $form.find('.nwComment').val('');
      $form.find('#stic_'+pid).val(''); $form.find('#cgif_'+pid).val('');
      // autoscroll inside the sheet
      var host = $list.closest('.i_user_comments')[0]; if (host) { host.scrollTop = host.scrollHeight; }
    }
  });
});
</script>
<script>

	
	/* RE-SHARE — call the same endpoint your feed uses */
$(document).on('click', '.js-reshare', function(e){
  e.preventDefault();
  var postId = $(this).data('id');
  if(!postId) return;

  // Use your existing feed endpoint + payload.
  // Common patterns in your codebase are: f:'reSharePost' or f:'reshare', key 'post_id' or 'id'.
  $.post(window.siteurl + 'requests/request.php', { f:'reSharePost', post_id: postId })
    .done(function(res){
      // Optional: small toast. Your theme may already have a notifier.
      // showToast('Post re-shared');
    });
});

</script>
<script>
/* =========================
   Reels comments – fallbacks
   ========================= */

/* Hide any open menus when clicking outside */
$(document).on('click', function(){ $('.i_comment_menu_container').hide(); });

/* 3-dot menu toggle (owner only) */
$(document).on('click', '.openComMenu', function(e){
  e.preventDefault(); e.stopPropagation();
  var cid = $(this).attr('id');
  $('.i_comment_menu_container').hide();
  $('.comMBox' + cid).show();
});

/* Reply: prefill @mention in the right composer */
$(document).on('click', '.rplyComment', function(e){
  e.preventDefault();
  var postID = $(this).attr('id');
  var who    = $(this).data('who') || '';
  var $ta    = $('#comment' + postID);
  if (!$ta.length) return;
  var cur = $ta.val() || '';
  var at  = '@' + who + ' ';
  $ta.val(cur ? (cur.endsWith(' ') ? cur + at : cur + ' ' + at) : at).focus();
});

/* Like/unlike (optimistic) — same API your inora uses: f=pc_like */
// Comment like/unlike — single robust handler
$(document).off('click.mfCmtLike', '.i_comment_item_btn');
$(document).on('click.mfCmtLike', '.i_comment_item_btn', function(e){
  e.preventDefault();

  var $btn = $(this);
  if ($btn.data('busy')) return;        // guard
  $btn.data('busy', 1);

  var cid  = $btn.data('id');           // comment id
  var pid  = $btn.data('p');            // post id
  if (!cid || !pid) { $btn.data('busy',0); return; }

  var $sum = $('#t_c_' + cid);
  var n = parseInt(($sum.text()||'0').replace(/\D+/g,''),10) || 0;

  var wasLiked = $btn.hasClass('c_in_unlike'); // in your HTML: liked => c_in_unlike
  var goingToLike = !wasLiked;

  // optimistic toggle (classes + aria)
  $btn.toggleClass('c_in_like c_in_unlike')
      .attr('aria-pressed', goingToLike ? 'true' : 'false');

  $sum.text((n + (goingToLike ? 1 : -1)));

  // reconcile with server
  $.ajax({
    type:'POST',
    url: window.siteurl + 'requests/request.php',
    dataType:'json',
    data:{ f:'pc_like', post: pid, com: cid }
  }).done(function(r){
    if (r && typeof r.totalLike !== 'undefined') $sum.text(r.totalLike);
    // if server says it's now unliked but UI shows liked (or vice versa), force class:
    if (r && typeof r.liked !== 'undefined'){
      $btn.toggleClass('c_in_like',  !r.liked)
          .toggleClass('c_in_unlike', !!r.liked)
          .attr('aria-pressed', r.liked ? 'true' : 'false');
    }
  }).always(function(){
    $btn.data('busy', 0);
  });
});



</script>
<script>
/* ====== Reels comments fallback actions (edit / delete) ====== */

/* DELETE (direct, with confirm) */
$(document).on('click', '.i_comment_menu_container .delCm', function(e){
  e.preventDefault();
  var $btn = $(this);
  var cid  = $btn.attr('id');
  var pid  = $btn.data('id');
  if (!cid || !pid) return;
  if (!window.confirm('Delete this comment?')) return;

  $.ajax({
    type:'POST',
    url: window.siteurl + 'requests/request.php',
    data:{ f:'deletecomment', cid: cid, pid: pid }
  }).done(function(resp){
    // remove the row on 200
    $('.dlCm' + cid).slideUp(120, function(){ $(this).remove(); });
  });
});

/* EDIT (inline, small UI) */
$(document).on('click', '.i_comment_menu_container .cced', function(e){
  e.preventDefault();
  var $btn = $(this);
  var cid  = $btn.attr('id');
  var pid  = $btn.data('id');
  if (!cid || !pid) return;

  var $bubble = $('#i_u_c_' + cid);
  if (!$bubble.length || $bubble.data('editing')) return;

  var originalHTML = $bubble.html();
  var originalText = $bubble.text();

  var form = $(
    '<div class="i-comment-edit">'+
      '<textarea class="i-comment-edit-ta" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:10px;">'+
        $('<div>').text(originalText).html() +
      '</textarea>'+
      '<div style="margin-top:6px;display:flex;gap:8px">'+
        '<button class="i-edit-save" style="padding:6px 10px;border:0;border-radius:8px;background:#111;color:#fff">Save</button>'+
        '<button class="i-edit-cancel" type="button" style="padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#fff">Cancel</button>'+
      '</div>'+
    '</div>'
  );

  $bubble.data('editing',1).addClass('is-editing').html(form);
  var $ta = $bubble.find('.i-comment-edit-ta').focus();

  function restore(){ $bubble.html(originalHTML).removeData('editing').removeClass('is-editing'); }

  $bubble.off('.edit').on('click.edit', '.i-edit-cancel', function(e2){
    e2.preventDefault(); restore();
  });

  $bubble.on('click.edit', '.i-edit-save', function(e2){
    e2.preventDefault();
    var val = ($ta.val() || '').trim();
    if (!val){ restore(); return; }
    var $save = $(this).prop('disabled', true);

    $.ajax({
      type:'POST',
      url: window.siteurl + 'requests/request.php',
      dataType:'json',
      data:{ f:'editSC', cid: cid, pid: pid, text: val }
    }).done(function(r){
      if (r && r.status === '200' && typeof r.text !== 'undefined'){
        $bubble.html(r.text);
      } else {
        $bubble.text(val);
      }
    }).always(function(){
      $save.prop('disabled', false);
      $bubble.removeData('editing').removeClass('is-editing');
    });
  });
});
</script>

<script>
/* Follow / Unfollow tiny fallback.
   Change f:'follow_toggle' to your real endpoint if different. */
$(document).on('click', '.js-follow', function(e){
  e.preventDefault();
  var $b = $(this), uid = $b.data('uid');
  if (!uid) return;

  var following = $b.hasClass('is-following');
  $b.prop('disabled', true);

  $.post(window.siteurl + 'requests/request.php', { f:'follow_toggle', uid: uid })
    .done(function(res){
      try{ res = (typeof res==='string') ? JSON.parse(res) : res; }catch(e){}
      if (res && (res.following===1 || res.status==='followed')) {
        $b.addClass('is-following').text('Following');
      } else if (res && (res.following===0 || res.status==='unfollowed')) {
        $b.removeClass('is-following').text('Follow');
      } else {
        // fallback: optimistic toggle
        $b.toggleClass('is-following').text($b.hasClass('is-following')?'Following':'Follow');
      }
    })
    .always(function(){ $b.prop('disabled', false); });
});
</script>
<script>
/* Show more/less for captions */
$(document).on('click', '.rm-more', function(){
  var $cap = $(this).closest('.rm-cap');
  var full = $cap.attr('data-full') || '';
  if (!full) return;
  // expand once; change to “Show less” if you want toggling
  $cap.html($('<span/>',{text:full}));
});

/* SAVE (wire to your real endpoint if different) */
$(document).on('click', '.js-save', function(e){
  e.preventDefault();
  var id = $(this).data('id');
  if (!id) return;
  $.post(window.siteurl + 'requests/request.php', { f:'savePost', post_id:id });
});

/* SHARE */
$(document).on('click', '.js-share', function(e){
  e.preventDefault();
  var url = $(this).data('url');
  if (navigator.share){
    navigator.share({ url:url }).catch(()=>{});
  }else{
    try{
      navigator.clipboard.writeText(url);
      // optional toast
    }catch(e){}
  }
});


</script>
<script>
/* Like: swap theme icons OFF/ON + optimistic count + post to same endpoint */
$(document).on('click', '.js-like', function(e){
  e.preventDefault();
  var $btn = $(this), id = $btn.data('id');
  var $ico = $btn.find('.js-like-ico');
  if(!id) return;

  var liked = $btn.attr('data-liked') === '1';
  var $cnt  = $('#lkc_'+id);
  var n = parseInt(($cnt.text()||'0').replace(/,/g,''),10) || 0;

  // swap icon html using the same IDs set in PHP (rendered in data attrs)
  // We’ll read the alternate icon by pinging the server? No. Easier:
  // Just store both versions in data once, then swap.
  if(!$btn.data('svgOff')) {
    $btn.data('svgOff', $ico.html()); // current
    $btn.data('svgOn',  $ico.html()); // placeholder; next line will fix
  }

  // server-rendered initial state already correct, so to get the "other"
  // icon we’ll temporarily create two spans at render time. Instead of over
  // engineering, we simply request PHP to output the correct one on first click:
  // When toggling we just re-call iN_SelectedMenuIcon on the back end? Not needed.
  // We’ll swap innerHTML using a hidden copy we baked at render:

  // Because we printed the actual off/on on initial render, easiest way:
  // set the innerHTML now:
  var svgOff = '<?php echo addslashes(html_entity_decode($iN->iN_SelectedMenuIcon($ICON_LIKE_OFF))); ?>';
  var svgOn  = '<?php echo addslashes(html_entity_decode($iN->iN_SelectedMenuIcon($ICON_LIKE_ON))); ?>';
  $ico.html(liked ? svgOff : svgOn);

  $btn.attr('data-liked', liked ? '0' : '1').toggleClass('is-liked', !liked);
  $cnt.text((liked ? n-1 : n+1).toLocaleString());

  var payload = {}; payload[$btn.data('key')] = id; payload.f = $btn.data('f') || 'likePost';
  $.post(window.siteurl + 'requests/request.php', payload);
});

/* Generic API poster for buttons that should behave like the feed (save/reshare) */

</script>

<script>
/* ========== RESHARE (in-app) ========== */

/* ========== SAVE / BOOKMARK ========== */
/* Button markup: <div class="reel-btn svp in_save in_save_<id>" id="<id>" data-saved data-svg-on data-svg-off>… */
$(document).on('click', '.in_save', function(e){
  e.preventDefault();
  var $btn   = $(this);
  var id     = $btn.attr('id');               // raw post id
  if(!id){ console.warn('in_save: missing id'); return; }

  var saved  = String($btn.data('saved')) === '1';
  var onSVG  = $btn.data('svg-on')  || '';
  var offSVG = $btn.data('svg-off') || '';

  // optimistic icon swap (the SVG is the direct innerHTML of the button)
  $btn.html(saved ? offSVG : onSVG);
  $btn.data('saved', saved ? 0 : 1);

  $.post(window.siteurl + 'requests/request.php', { f:'savePost', post_id:id })
    .done(function(res){ console.log('savePost response:', res); })
    .fail(function(xhr){
      // revert on error
      $btn.html(saved ? onSVG : offSVG);
      $btn.data('saved', saved ? 1 : 0);
      console.error('savePost failed', xhr);
      alert('Save failed. Please try again.');
    });
});
	
	
	// Save post


</script>
<script>
$(document).on('click', '.reels-save-button', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var postID = $btn.data('id');
    var isSaved = $btn.data('issaved') == 1;

    if (!postID) {
        return;
    }

    // These are the raw SVG codes for your icons. This is the most reliable way.
   // These are the raw SVG codes for your icons. This is the most reliable way.
var iconSaved = `<svg id="Layer_1" class="icon" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M386.123,0H125.877a59.47,59.47,0,0,0-59.4,59.4V480.191a31.8,31.8,0,0,0,52.156,24.375l132.717-110.98a7.074,7.074,0,0,1,9.3,0l132.717,110.98A31.578,31.578,0,0,0,413.684,512a31.9,31.9,0,0,0,13.522-3.034,31.489,31.489,0,0,0,18.317-28.8V59.4A59.47,59.47,0,0,0,386.123,0Z"/></svg>`;
var iconNotSaved = `<svg id="Layer_1" class="icon" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m386.123 0h-260.246a59.47 59.47 0 0 0 -59.4 59.4v420.791a31.765 31.765 0 0 0 52.156 24.375l132.717-110.98a7.074 7.074 0 0 1 9.3 0l132.717 110.98a31.578 31.578 0 0 0 20.317 7.456 31.9 31.9 0 0 0 13.522-3.034 31.489 31.489 0 0 0 18.317-28.8v-420.788a59.47 59.47 0 0 0 -59.4-59.4zm23.4 471.148-125.778-105.179a43.219 43.219 0 0 0 -55.491 0l-125.779 105.179v-411.748a23.428 23.428 0 0 1 23.4-23.4h260.248a23.428 23.428 0 0 1 23.4 23.4z"/></svg>`;
    // 1. Optimistically update the icon immediately
    if (isSaved) {
        $btn.html(iconNotSaved);
        $btn.data('issaved', 0);
    } else {
        $btn.html(iconSaved);
        $btn.data('issaved', 1);
    }
	// 2. Optimistically update counter
    var $counter = $('#svc_' + postID);
    var currentCount = parseInt($counter.text().replace(/,/g, ''), 10) || 0;
    if (isSaved) {
        $counter.text(Math.max(0, currentCount - 1).toLocaleString()); // unsaving
    } else {
        $counter.text((currentCount + 1).toLocaleString()); // saving
    }

    // 2. Send the request to the server in the background
    $.ajax({
        type: 'POST',
        url: window.siteurl + 'requests/request.php',
        data: {
            f: 'savePost',
            id: postID
        },
        dataType: 'json', // Expect a JSON response
        success: function(response) {
            // Server has confirmed. We don't need to do anything on success
            // because we already updated the icon.
            console.log('Save action successful for post ' + postID);
        },
        error: function() {
            // If the server fails, revert the icon back to its original state
            console.error('Save action failed for post ' + postID);
            if (isSaved) {
                $btn.html(iconSaved);
                $btn.data('issaved', 1);
            } else {
                $btn.html(iconNotSaved);
                $btn.data('issaved', 0);
				// Revert counter too
    var $counter = $('#svc_' + postID);
    if (isSaved) {
        $counter.text((currentCount + 1).toLocaleString());
    } else {
        $counter.text(Math.max(0, currentCount - 1).toLocaleString());
    }
            }
        }
    });
});
	
// NEW INSTANT RESHARE SCRIPT (NO POP-UP)
$(document).on('click', '.in_share', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var postID = $btn.data('id');

    if (!postID || $btn.hasClass('shared')) {
        return; // Don't do anything if no ID or already shared
    }

    // Give immediate visual feedback
    $btn.css('opacity', '0.5').addClass('shared');
	// Optimistically update counter
    var $counter = $('#rsc_' + postID);
    var currentCount = parseInt($counter.text().replace(/,/g, ''), 10) || 0;
    $counter.text((currentCount + 1).toLocaleString());

    // Make the AJAX call to instantly reshare the post
    $.ajax({
        type: 'POST',
        url: window.siteurl + 'requests/request.php',
        data: {
            f: 'p_rshare', // IMPORTANT: This is the endpoint for direct resharing
            sp: postID,
            pt: '' // Empty comment text
        },
        cache: false,
        success: function(response) {
            if (response === '200') {
                // Use your existing alert system to show success
                if (typeof PopUPAlerts !== 'undefined') {
                    PopUPAlerts('reshared_successfully', 'ialert'); // You might need to add this text in your language file
                } else {
                    alert('Reshared!');
                }
                // Make the icon green to show it was successful
                $btn.find('.icon').css('color', '#4CAF50');
            } else {
                PopUPAlerts('not_Shared', 'ialert');
                // Revert button on failure
                $btn.css('opacity', '1').removeClass('shared');
            }
        },
        error: function() {
            PopUPAlerts('sWrong', 'ialert');
            // Revert button on failure
            $btn.css('opacity', '1').removeClass('shared');
        }
    });
});
</script>
<script>
/* --- Scoped @mention autocomplete for the Reels sheet --- */
function initSheetMentions($scope){
  // textarea selector used by your comment composer
  const sel = '.nwComment';

  // create (or get) a dropdown next to the textarea
  function ensureMenu($ta){
    let $menu = $ta.siblings('.mf-mention-menu');
    if (!$menu.length){
      $menu = $('<div class="mf-mention-menu"></div>').css({
        position:'absolute', zIndex: 99999, maxHeight:'220px', overflowY:'auto',
        background:'#fff', border:'1px solid #eee', borderRadius:'8px',
        boxShadow:'0 6px 24px rgba(0,0,0,.08)'
      });
      const $wrap = $ta.closest('.i_comment_form');
      ($wrap.length ? $wrap : $ta.parent()).css('position','relative').append($menu);
    }
    return $menu.hide().empty();
  }

  function findTrigger(text, caret){
    const before = text.slice(0, caret);
    const m = before.match(/@([A-Za-z0-9_.]{1,32})$/);
    return m ? m[1] : null;
  }

  function replaceTrigger($ta, username){
    const el = $ta.get(0);
    const caret = el.selectionStart;
    const val = $ta.val();
    const before = val.slice(0, caret);
    const after  = val.slice(caret);
    const replaced = before.replace(/@([A-Za-z0-9_.]{1,32})$/, '@'+username+' ');
    const newVal = replaced + after;
    $ta.val(newVal).focus();
    const pos = replaced.length;
    el.setSelectionRange(pos, pos);
  }

  // bind inside the provided scope only
  let activeReq = null;

  $scope.off('.mfMentions'); // prevent double-binding if sheet reopens
  $scope.on('keyup.mfMentions click.mfMentions', sel, function(e){
    const $ta = $(this);
    const el = this;
    const caret = el.selectionStart || $ta.val().length;
    const q = findTrigger($ta.val(), caret);
    const $menu = ensureMenu($ta);

    if (!q || e.key === 'Escape'){ $menu.hide().empty(); return; }

    // abort previous request
    if (activeReq && activeReq.abort) { try{ activeReq.abort(); }catch(_){ } }

    $menu.show().html('<div style="padding:10px;opacity:.6">Searching…</div>');

    activeReq = $.ajax({
      type: 'GET',
      url: window.siteurl + 'requests/request.php',
      data: { f:'mentionFollowers', q:q },
      dataType: 'json'
    }).done(function(list){
      if (!Array.isArray(list) || !list.length){
        $menu.html('<div style="padding:10px;opacity:.6">No matches</div>');
        return;
      }
      // support both new keys (username/name) and old ones (i_username/i_user_fullname)
      const html = list.map(u => {
        const uname = u.username || u.i_username || '';
        const name  = u.name || u.i_user_fullname || '';
        const ava   = u.avatar || '';
        return `
          <div class="mf-mention-item" data-u="${uname}">
            <div style="display:flex;gap:8px;align-items:center;padding:8px 10px;cursor:pointer">
              <img src="${ava}" onerror="this.style.display='none'" style="width:28px;height:28px;border-radius:50%">
              <div>
                <div style="font-weight:600">@${uname}</div>
                <div style="opacity:.8;font-size:12px">${name}</div>
              </div>
            </div>
          </div>`;
      }).join('');
      $menu.html(html);
    }).fail(function(){
      $menu.hide().empty();
    });
  });

  // pick a user
  $scope.on('click.mfMentions', '.mf-mention-item', function(){
    const $menu = $(this).closest('.mf-mention-menu');
    const $ta = $menu.siblings(sel).first();
    replaceTrigger($ta, $(this).data('u'));
    $menu.hide().empty();
  });

  // outside click hides menu (limit to sheet)
  $(document).on('mousedown.mfMentionsGlobal', function(ev){
    if (!$(ev.target).closest('.mf-mention-menu, ' + sel).length) {
      $scope.find('.mf-mention-menu').hide().empty();
    }
  });
}
</script>


<script>
(function(){
  // keep overlay click from pausing (client wanted no pause)
  document.querySelectorAll('.reel-item .reel-overlay').forEach(ov=>{
    const clone = ov.cloneNode(true);
    ov.parentNode.replaceChild(clone, ov);
    clone.addEventListener('click', (e)=> {
      e.stopPropagation();
      const cont = document.querySelector('.reels-container');
      if (cont) cont.focus();
    }, {passive:true});
  });

  function buildScrubber(reel){
    if (reel.querySelector('.reel-scrubber')) return;
    const v = reel.querySelector('video'); if(!v) return;

    const wrap = document.createElement('div');
    wrap.className = 'reel-scrubber';
    wrap.innerHTML = `
      <div class="reel-scrubber-track" role="slider" aria-label="Seek"
           aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
        <div class="reel-scrubber-buffer"></div>
        <div class="reel-scrubber-fill"></div>
        <div class="reel-scrubber-thumb" aria-hidden="true"></div>
      </div>`;

    /* place scrubber right after the follow/caption box (.reel-meta) */
    const meta = reel.querySelector('.reel-meta');
    if (meta && meta.parentNode) meta.insertAdjacentElement('afterend', wrap);
    else reel.appendChild(wrap);

    const track  = wrap.querySelector('.reel-scrubber-track');
    const fill   = wrap.querySelector('.reel-scrubber-fill');
    const buffer = wrap.querySelector('.reel-scrubber-buffer');
    const thumb  = wrap.querySelector('.reel-scrubber-thumb');

 function updateFill(){
  if (v.duration){
    let p = (v.currentTime / v.duration) * 100;
    // keep the dot visible at the edges (0% and 100%)
    if (p <= 0)  p = 0.8;
    if (p >= 100) p = 99.2;

    fill.style.width = p + '%';
    thumb.style.left = p + '%';
    track.setAttribute('aria-valuenow', Math.round(p));
  } else {
    fill.style.width = '0%';
    // nudge dot slightly inside so it’s visible even before play
    thumb.style.left = '0.8%';
    track.setAttribute('aria-valuenow', 0);
  }
}

    function updateBuffer(){
      try{
        if (v.duration && v.buffered && v.buffered.length){
          const end = v.buffered.end(v.buffered.length - 1);
          buffer.style.width = (end / v.duration * 100) + '%';
        }
      }catch(_){}
    }
    v.addEventListener('loadedmetadata', ()=>{ updateFill(); updateBuffer(); });
    v.addEventListener('timeupdate', updateFill);
    v.addEventListener('progress', updateBuffer);

    // drag to seek
    let dragging = false;
    function pctFromEvent(e){
      const r = track.getBoundingClientRect();
      const x = (e.touches ? e.touches[0].clientX : e.clientX) - r.left;
      return Math.min(1, Math.max(0, x / Math.max(1, r.width)));
    }
    function seekToPct(p){ if (v.duration) v.currentTime = p * v.duration; }
    const onMove = (e)=>{ if(!dragging) return; e.preventDefault(); seekToPct(pctFromEvent(e)); };
    const onUp   = ()=>{ dragging=false; wrap.classList.remove('is-active');
                         window.removeEventListener('mousemove', onMove);
                         window.removeEventListener('mouseup', onUp);
                         window.removeEventListener('touchmove', onMove);
                         window.removeEventListener('touchend', onUp); };
    const start  = (e)=>{ dragging=true; wrap.classList.add('is-active'); onMove(e);
                          window.addEventListener('mousemove', onMove, {passive:false});
                          window.addEventListener('mouseup', onUp);
                          window.addEventListener('touchmove', onMove, {passive:false});
                          window.addEventListener('touchend', onUp); };
    track.addEventListener('mousedown', start);
    track.addEventListener('touchstart', start, {passive:false});

    // arrow keys still nudge when container focused (optional)
    const cont = document.querySelector('.reels-container');
    if (cont){
      cont.addEventListener('keydown', (e)=>{
        if (e.key === 'ArrowRight'){ e.preventDefault(); v.currentTime = Math.min((v.duration||0), v.currentTime + 5); }
        if (e.key === 'ArrowLeft'){  e.preventDefault(); v.currentTime = Math.max(0, v.currentTime - 5); }
      });
    }
  }

  document.querySelectorAll('.reel-item').forEach(buildScrubber);
  const mo = new MutationObserver(()=> {
    document.querySelectorAll('.reel-item').forEach(buildScrubber);
  });
  mo.observe(document.body, { childList:true, subtree:true });
})();

</script>
<script>
/* Auto-scroll to specific reel from hash (e.g., #reel_123) */
(function(){
  const hash = window.location.hash; // e.g., "#reel_123"
  if (!hash || !hash.startsWith('#reel_')) return;

  const postId = hash.replace('#reel_', '');
  const targetReel = document.querySelector(`.reel-item[data-post-id="${postId}"]`);
  
  if (targetReel) {
    // Wait for videos to be ready, then scroll
    setTimeout(() => {
      targetReel.scrollIntoView({ behavior: 'auto', block: 'start' });
      
      // Auto-play the target video
      const video = targetReel.querySelector('video');
      if (video) {
        video.play().catch(() => {});
      }
    }, 300); // Small delay to ensure DOM is ready
  }
})();
</script>
	
	<script>
/* Auto-scroll to specific reel from hash - IMPROVED */
(function(){
  const hash = window.location.hash;
  if (!hash || !hash.startsWith('#reel_')) return;

  const postId = hash.replace('#reel_', '');
  const targetReel = document.querySelector(`.reel-item[data-post-id="${postId}"]`);
  
  if (!targetReel) {
    console.warn('Reel not found:', postId);
    return;
  }

  // CRITICAL: Use immediate scroll (no smooth) to ensure it works
  function scrollToReel() {
    const container = document.querySelector('.reels-container');
    if (!container) return;

    // Method 1: Direct scroll (most reliable)
    const offset = targetReel.offsetTop;
    container.scrollTop = offset;

    // Method 2: Fallback with scrollIntoView
    setTimeout(() => {
      targetReel.scrollIntoView({ 
        behavior: 'auto',  // 'auto' is faster than 'smooth'
        block: 'start',
        inline: 'nearest'
      });

      // Auto-play the video
      const video = targetReel.querySelector('video');
      if (video) {
        video.muted = true; // Ensure muted for autoplay
        video.play().catch(e => console.log('Autoplay blocked:', e));
      }
    }, 100);
  }

  // Try immediately, then retry after DOM is fully ready
  scrollToReel();
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scrolalToReel);
  }

  // Final retry after videos load
  window.addEventListener('load', () => {
    setTimeout(scrollToReel, 200);
  });
})();
</script>
</body>
</html>
