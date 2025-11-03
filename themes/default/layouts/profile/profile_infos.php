<?php
/* =============================================================================
   layouts/profile/profile_infos.php — Mesfollow (clean)
   - LIVE badge via widget + i_live
   - Put counts inside the 3 top tabs (Following • Followers • Subscribers)
   - Remove those three from the counts row below (leave Posts/Pictures/Videos/Audios/Products)
   - No grey hover background on the tabs; mobile-friendly
   - Defensive DB fallbacks (PDO or mysqli)
   ========================================================================== */

/* ---------- tiny helpers ---------- */
if (!function_exists('mf_h')) { function mf_h($v){ return iN_HelpSecure($v); } }
if (!function_exists('mf_live_url_for')) {
  function mf_live_url_for($base_url, $username){
    $base = rtrim($base_url ?? '/', '/'); return $base . '/live/' . ($username ?? '');
  }
}
if (!function_exists('mf_db')) {
  function mf_db($iN, $db){ return isset($iN->db) ? $iN->db : ($db ?? null); }
}
if (!function_exists('mf_db_is_pdo'))    { function mf_db_is_pdo($dbh){ return $dbh instanceof PDO; } }
if (!function_exists('mf_db_is_mysqli')) { function mf_db_is_mysqli($dbh){ return $dbh instanceof mysqli; } }
if (!function_exists('mf_cols')) {
  function mf_cols($dbh, $table){
    try{
      if (mf_db_is_pdo($dbh)){
        $st = $dbh->query("SHOW COLUMNS FROM `$table`");
        if (!$st) { return []; }
        return array_map(fn($r)=>$r['Field'], $st->fetchAll(PDO::FETCH_ASSOC));
      }elseif(mf_db_is_mysqli($dbh)){
        $res = $dbh->query("SHOW COLUMNS FROM `$table`");
        $out=[]; if($res){ while($r=$res->fetch_assoc()){ $out[]=$r['Field']; } }
        return $out;
      }
    }catch(Throwable $e){ }
    return [];
  }
}

/* ---------- LIVE? (widget first, DB fallback) ---------- */
$__liveFromWidget = null;
try{
  if (isset($iN) && method_exists($iN,'iN_LiveStreamingListWidget')) {
    $rows = $iN->iN_LiveStreamingListWidget(120);
    if ($rows && is_array($rows)) {
      $now = time();
      foreach ($rows as $li) {
        $uid = isset($li['live_uid_fk']) ? (int)$li['live_uid_fk'] : 0;
        if ($uid !== (int)$p_profileID) { continue; }
        $fin = (int)($li['finish_time'] ?? 0);
        if ($fin >= $now - 120) { $__liveFromWidget = true; break; }
      }
      if ($__liveFromWidget === null) { $__liveFromWidget = false; }
    }
  }
}catch(Throwable $e){ $__liveFromWidget = null; }

$__liveFromDb = null;
try{
  $dbh = mf_db($iN ?? null, $db ?? null);
  if ($dbh && $p_profileID){
    $sql = "SELECT started_at, finish_time FROM i_live
            WHERE live_uid_fk = ? ORDER BY live_id DESC LIMIT 1";
    $row = null;
    if (mf_db_is_pdo($dbh)){
      $st=$dbh->prepare($sql); $st->execute([(int)$p_profileID]); $row=$st->fetch(PDO::FETCH_ASSOC) ?: null;
    }elseif(mf_db_is_mysqli($dbh)){
      if ($st=$dbh->prepare($sql)){
        $uid=(int)$p_profileID; $st->bind_param("i",$uid);
        if ($st->execute()){ $res=$st->get_result(); $row=$res?$res->fetch_assoc():null; }
        $st->close();
      }
    }
    if ($row){
      $now = time();
      $sta = (int)($row['started_at'] ?? 0);
      $fin = (int)($row['finish_time'] ?? 0);
      if ($fin >= $now - 120)                { $__liveFromDb = true; }
      elseif ($fin == 0 && $sta > $now-21600){ $__liveFromDb = true; }
      else                                   { $__liveFromDb = false; }
    } else { $__liveFromDb = false; }
  }
}catch(Throwable $e){ $__liveFromDb = null; }

$__isLive  = ($__liveFromWidget === true) ? true :
             (($__liveFromWidget === false) ? ($__liveFromDb === true) : ($__liveFromDb === true));
$__liveUrl = mf_live_url_for($base_url ?? '/', $p_username ?? '');

/* ---------- Stats: Following • Followers • Subscribers ---------- */
$mf_followers = 0; $mf_following = 0; $mf_subscribers = 0;

/* ADD THIS ENTIRE BLOCK TO CALCULATE TOTAL LIKES */
$mf_total_likes = 0;
try {
    $dbh = mf_db($iN ?? null, $db ?? null);
    if ($dbh && $p_profileID) {
        // This query joins the 'likes' table with the 'posts' table
        // to count all likes on posts owned by the current profile's user.
        // !! IMPORTANT: You may need to adjust table/column names below !!
        // - i_post_likes: The table that stores each individual like.
        // - i_posts: The table with all the posts.
        // - post_id_fk: The column in 'i_post_likes' that points to a post.
        // - post_owner_id: The column in 'i_posts' that points to the user who made the post.
        $sql = "SELECT COUNT(*)
                FROM i_post_likes l
                INNER JOIN i_posts p ON l.post_id_fk = p.post_id
                WHERE p.post_owner_id = ?";

        if (mf_db_is_pdo($dbh)) {
            $s = $dbh->prepare($sql);
            $s->execute([(int)$p_profileID]);
            $mf_total_likes = (int)$s->fetchColumn();
        } else { // mysqli
            $s = $dbh->prepare($sql);
            $uid = (int)$p_profileID;
            $s->bind_param("i", $uid);
            $s->execute();
            $r = $s->get_result()->fetch_row();
            $mf_total_likes = (int)($r[0] ?? 0);
            $s->close();
        }
    }
} catch(Throwable $e) {
    // Fails silently if there's a DB error
}
/* END OF NEW BLOCK */

try{
  // Prefer core helpers if present
  if (method_exists($iN,'iN_UserFollowersCount')) { $mf_followers = (int)$iN->iN_UserFollowersCount($p_profileID); }
  elseif (method_exists($iN,'iN_TotalFollowers')) { $mf_followers = (int)$iN->iN_TotalFollowers($p_profileID); }

  if (method_exists($iN,'iN_UserFollowingCount')) { $mf_following = (int)$iN->iN_UserFollowingCount($p_profileID); }
  elseif (method_exists($iN,'iN_TotalFollowing')) { $mf_following = (int)$iN->iN_TotalFollowing($p_profileID); }

  foreach (['iN_TotalSubscribers','iN_UserSubscribersCount','iN_UserSubscriberCount','iN_UserSubscriptionsCount'] as $__m){
    if (method_exists($iN,$__m)){ $mf_subscribers = (int)$iN->$__m($p_profileID); break; }
  }

  // Fallbacks from DB
  $dbh = mf_db($iN ?? null, $db ?? null);
  if ($dbh){
    // Followers / Following from i_friends
    if (!$mf_followers || !$mf_following){
      $fcols = mf_cols($dbh,'i_friends');
      $cA = in_array('fr_one',$fcols) ? 'fr_one' : (in_array('f_one',$fcols)?'f_one':null);
      $cB = in_array('fr_two',$fcols) ? 'fr_two' : (in_array('f_two',$fcols)?'f_two':null);
      $cS = in_array('fr_status',$fcols) ? 'fr_status' : (in_array('status',$fcols)?'status':null);

      if ($cA && $cB){
        if (!$mf_followers){
          $sql = $cS
            ? "SELECT COUNT(*) c FROM i_friends WHERE `$cB`=? AND (`$cS`='flwr' OR `$cS`='2' OR `$cS`='following')"
            : "SELECT COUNT(*) c FROM i_friends WHERE `$cB`=?";
          if (mf_db_is_pdo($dbh)){
            $s=$dbh->prepare($sql); $s->execute([(int)$p_profileID]); $mf_followers=(int)$s->fetchColumn();
          }else{
            $s=$dbh->prepare($sql); $u=(int)$p_profileID; $s->bind_param("i",$u);
            $s->execute(); $r=$s->get_result()->fetch_assoc(); $mf_followers=(int)$r['c']; $s->close();
          }
        }
        if (!$mf_following){
          $sql = $cS
            ? "SELECT COUNT(*) c FROM i_friends WHERE `$cA`=? AND (`$cS`='flwr' OR `$cS`='2' OR `$cS`='following')"
            : "SELECT COUNT(*) c FROM i_friends WHERE `$cA`=?";
          if (mf_db_is_pdo($dbh)){
            $s=$dbh->prepare($sql); $s->execute([(int)$p_profileID]); $mf_following=(int)$s->fetchColumn();
          }else{
            $s=$dbh->prepare($sql); $u=(int)$p_profileID; $s->bind_param("i",$u);
            $s->execute(); $r=$s->get_result()->fetch_assoc(); $mf_following=(int)$r['c']; $s->close();
          }
        }
      }
    }

    // Subscribers fallback (common schemas)
    if (!$mf_subscribers){
      $candTables = ['i_user_subscriptions','i_subscriptions','i_users_subscriptions','i_subscribers','i_subscribed_users','i_paid_subscriptions'];
      foreach ($candTables as $t){
        $cols = mf_cols($dbh,$t); if (!$cols) { continue; }
        $ownerCol = null;
        foreach (['owner_uid_fk','owner_uid','subscribed_iuid_fk','subscribed_iuid','iuid_fk','profile_id','to_uid','to_user_id'] as $c){
          if (in_array($c,$cols)){ $ownerCol = $c; break; }
        }
        if (!$ownerCol) { continue; }
        $statusCol = null; $activeVals = ["'1'","'yes'","'active'","'paid'","'approved'"];
        foreach (['status','sub_status','is_active','active','payment_status'] as $c){
          if (in_array($c,$cols)){ $statusCol=$c; break; }
        }
        $sql = "SELECT COUNT(*) c FROM `$t` WHERE `$ownerCol`=?";
        if ($statusCol) { $sql .= " AND `$statusCol` IN (".implode(',',$activeVals).")"; }

        try{
          if (mf_db_is_pdo($dbh)){
            $s=$dbh->prepare($sql); $s->execute([(int)$p_profileID]); $mf_subscribers=(int)$s->fetchColumn();
          }else{
            $s=$dbh->prepare($sql); $u=(int)$p_profileID; $s->bind_param("i",$u);
            $s->execute(); $r=$s->get_result()->fetch_assoc(); $mf_subscribers=(int)($r['c'] ?? 0); $s->close();
          }
          if ($mf_subscribers){ break; }
        }catch(Throwable $e){}
      }
    }
  }
}catch(Throwable $e){}

/* =========================  VIEW  ========================= */
?>
<style>
/* Live indicator */
.i_profile_avatar_wrp{position:relative}
.live-dot{
  position:absolute; right:-4px; bottom:-4px;
  width:16px; height:16px; border-radius:50%;
  background:#12b886; border:2px solid #fff;
  box-shadow:0 0 0 0 rgba(18,184,134,.65);
  animation:livePulse 1.6s infinite;
}
@keyframes livePulse{
  0%{box-shadow:0 0 0 0 rgba(18,184,134,.65)}
  70%{box-shadow:0 0 0 10px rgba(18,184,134,0)}
  100%{box-shadow:0 0 0 0 rgba(18,184,134,0)}
}

/* --------- TOP TABS (counts inside) ---------
   Single-row, 3 equal columns. */
.mf_ff_tabs{
  display:grid;
  grid-template-columns:repeat(3, minmax(0,1fr)); /* allow columns to shrink */
  gap:6px;                                        /* a tad tighter to prevent overflow */
  width:100%;
  box-sizing:border-box;
}

.mf_ff_tabs .i_p_ffs{
  background:transparent!important; box-shadow:none!important; border:0!important;
  min-width:0; /* allow the cell to shrink */
}

.mf_ff_tabs .i_p_ffs > a{
  background:transparent!important; border:0!important;
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  width:100%; text-decoration:none; color:inherit;
  min-width:0; /* key for flex children shrink */
  padding:6px 4px; /* tiny padding so it fits on narrow screens */
}

.mf_ff_tabs .mf_ff_text{
  display:flex; flex-direction:column; align-items:center; text-align:center; line-height:1.1;
  min-width:0; /* lets text compress */
}
.mf_ff_tabs .mf_ff_num{ font-weight:800; font-size:14px; }
.mf_ff_tabs .mf_ff_lab{ font-size:12px; white-space:nowrap; opacity:.8; }

@media (max-width:480px){
  .mf_ff_tabs{ gap:4px; }
  .mf_ff_tabs .mf_ff_num{ font-size:13px; }
  .mf_ff_tabs .mf_ff_lab{ font-size:11px; }
}

/* (Optional) tiny style for the join-live button used below */
.join-live-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:#111;color:#fff;text-decoration:none;font-weight:700}
	/* Put the pulse inside the Join Live pill */
.join-live-pill{
  display:inline-flex;align-items:center;gap:8px;
  padding:8px 14px;border-radius:999px;background:#111;color:#fff;
  text-decoration:none;font-weight:700;box-shadow:0 6px 16px rgba(0,0,0,.18)
}
.join-live-pill .live-dot-mini{
  width:10px;height:10px;border-radius:50%;
  background:#12b886;
  box-shadow:0 0 0 0 rgba(18,184,134,.65);
  animation:livePulse 1.6s infinite;
}

/* OPTIONAL: hide the old avatar blinker so you only see it in the button */
.i_profile_avatar_wrp .live-dot{ display:none; }
	
/* Developer Badge Styling */
.developer-badge-row{
    display:flex;
    align-items:center;
    justify-content:center; /* Center the whole row */
    margin-top:10px; /* Adjust spacing as needed */
    margin-bottom:10px;
}
.developer-badge-row .developer-icon-surround{
    color: #BC14A5; /* Icon color */
    font-size:16px; /* Adjust size if needed */
    margin:0 5px; /* Spacing around icons */
}
.developer-badge{
    display:inline-flex;
    align-items:center;
    gap:4px;
    background:#e0f7fa; /* Light cyan background */
    color:#00796b; /* Dark cyan text */
    padding:2px 8px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    border:1px solid #b2ebf2;
}
.developer-badge svg{
    width:14px; height:14px;
}

/* Custom styles for Edit Profile and Copy URL buttons */
.i_p_cards .i_btn_item.owner-action-button {
    background-color: #BC14A5 !important; /* Specific color for these buttons */
    color: #fff !important; /* White text/icon */
    padding: 6px 10px; /* Smaller padding */
    height: auto; /* Allow height to adjust */
    line-height: 1; /* Adjust line height for better centering */
    font-size: 14px; /* Slightly smaller text if there's any */
    width: fit-content; /* Adjust width to content */
    min-width: 40px; /* Minimum width for the button */
    display: flex; /* Use flex for icon centering */
    justify-content: center;
    align-items: center;
}
.i_p_cards .i_btn_item.owner-action-button svg {
    width: 16px; /* Adjust icon size */
    height: 16px;
    fill: #fff; /* Ensure icon color is white */
}
/* New CSS to align Profile Category with the two buttons */
.mf_profile_action_row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px; /* Adjust spacing between items */
    margin-top: 10px;
}
.mf_profile_action_row .i_btn_item {
    margin: 0 !important; /* Remove default margins */
}
.mf_profile_action_row .i_p_cards {
    margin: 0;
}
.mf_profile_action_row .i_p_cards > div {
    margin: 0;
}
	
	/* Added to center the profile bio text */
.i_p_bio {
    text-align: center;
    width: 100%; /* Ensure it takes full width to center properly */
}
</style>

<?php
/* ===== cover/avatar resolver (minimal + safe) ===== */

/* helper to make absolute URLs from DB-relative paths */
if (!function_exists('mf_full')) {
  function mf_full($path, $base){
    if(!$path) return '';
    if (preg_match('~^https?://~',$path)) return $path;
    return rtrim($base,'/').'/'.ltrim($path,'/');
  }
}

/* theme defaults (used if nothing in DB) */
$__coverDefault  = rtrim($base_url,'/').'/themes/'.$currentTheme.'/img/defaults/cover.jpg';
$__avatarDefault = rtrim($base_url,'/').'/themes/'.$currentTheme.'/img/defaults/avatar.png';

$uid = (int)($p_profileID ?? 0);
$__rawCover  = '';
$__rawAvatar = '';

if ($uid > 0) {
  /* pull from i_users (works whether uploader wrote user_cover or cover/cover_photo) */
  // Your schema only has user_cover / user_avatar — keep it simple
  $sql = "SELECT
            user_cover  AS cover,
            user_avatar AS avatar
          FROM i_users
          WHERE iuid=? LIMIT 1";


  $row = null;

  // prefer mysqli $db if present
  if (isset($db) && $db instanceof mysqli) {
    if ($st = $db->prepare($sql)) {
      $st->bind_param("i", $uid);
      $st->execute();
      $res = $st->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      $st->close();
    }
  }

  // fallback to PDO if $iN->db is PDO
 if (isset($iN, $iN->db) && class_exists('PDO') && ($iN->db instanceof PDO)) {

    $st = $iN->db->prepare($sql);
    $st->execute([$uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  if ($row) {
    $__rawCover  = trim((string)($row['cover']  ?? ''));
    $__rawAvatar = trim((string)($row['avatar'] ?? ''));
  }
}

/* if some themes already set $p_profileCover/$p_profileAvatar, keep them as fallback */
if ($__rawCover  === '' && !empty($p_profileCover))  { $__rawCover  = trim($p_profileCover); }
if ($__rawAvatar === '' && !empty($p_profileAvatar)) { $__rawAvatar = trim($p_profileAvatar); }

/* If cover is a numeric id, resolve from i_user_covers (legacy schemas) */
if ($__rawCover !== '' && ctype_digit($__rawCover) && isset($db) && ($db instanceof mysqli)) {
  $coverId = (int)$__rawCover;
  $path = '';

  if ($q = @mysqli_query($db, "SELECT cover_path FROM i_user_covers WHERE cover_id={$coverId} LIMIT 1")) {
    if ($r = mysqli_fetch_assoc($q)) $path = $r['cover_path'] ?? '';
  }

  if ($path === '' && $uid) {
    if ($q2 = @mysqli_query($db, "SELECT cover_path FROM i_user_covers WHERE iuid_fk={$uid} ORDER BY cover_id DESC LIMIT 1")) {
      if ($r2 = mysqli_fetch_assoc($q2)) $path = $r2['cover_path'] ?? '';
    }
  }

  if ($path !== '') { $__rawCover = $path; }
}

/* sanitize obvious bogus values */
$__rawCover = trim($__rawCover);
$hostBase   = rtrim($base_url ?? '', '/');
if ($__rawCover === '/' || $__rawCover === '#' || $__rawCover === '0' ||
    $__rawCover === $hostBase || $__rawCover === $hostBase.'/') {
  $__rawCover = '';
}

/* final absolute URLs */
$__coverURL  = $__rawCover  ? mf_full($__rawCover,  $base_url) : $__coverDefault;
$__avatarURL = $__rawAvatar ? mf_full($__rawAvatar, $base_url) : $__avatarDefault;

/* diagnostic breadcrumb: view-source to confirm values */
echo "\n\n";
?>



<div class="i_profile_container">
  <div class="i_profile_cover_blur"
       data-background="<?php echo mf_h($__coverURL); ?>"
       style="background-image:url('<?php echo mf_h($__coverURL); ?>')"
       role="img" aria-label="Profile cover image"></div>

 <div class="i_profile_i_container">
    <div class="i_profile_infos_wrapper">
      <div class="i_profile_cover">
        <div class="i_im_cover">
          <img src="<?php echo mf_h($__coverURL); ?>" alt="">
        </div>
        <div class="i_profile_avatar_container">
          <div class="i_profile_avatar_wrp">
            <div class="i_profile_avatar"
                 data-avatar="<?php echo mf_h($__avatarURL); ?>"
                 style="background-image:url('<?php echo mf_h($__avatarURL); ?>')"
                 role="img" aria-label="Profile avatar image">
              <?php echo html_entity_decode($p_is_creator); ?>
            </div>
            <?php if ($__isLive) { ?><span class="live-dot" title="Live now"></span><?php } ?>
          </div>
        </div>
		  <?php if ($p_profileID == $userID && $logedIn == 1) { ?>
  <!-- Camera buttons for editing -->
<div class="edit-cover-btn" onclick="document.getElementById('cover_upload_input').click();" style="position:absolute;top:20px;right:20px;background:rgba(0,0,0,0.6);color:#fff;padding:10px 15px;border-radius:50px;cursor:pointer;z-index:10;display:flex;align-items:center;gap:8px;">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M9.4 5h5.2l1.1 2H19a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V10a3 3 0 0 1 3-3h3.3L9.4 5Zm2.6 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
  <?php echo mf_h($LANG['edit_cover'] ?? 'Edit Cover'); ?>
</div>

<div class="edit-avatar-btn" onclick="document.getElementById('avatar_upload_input').click();" style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.75);color:#fff;width:40px;height:40px;border-radius:50%;cursor:pointer;z-index:10;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.3);" title="<?php echo mf_h($LANG['edit_avatar'] ?? 'Edit Avatar'); ?>">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M9.4 5h5.2l1.1 2H19a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V10a3 3 0 0 1 3-3h3.3L9.4 5Zm2.6 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
</div>

  
  <!-- Hidden file inputs -->
  <input type="file" id="cover_upload_input" accept="image/*" style="display:none;">
  <input type="file" id="avatar_upload_input" accept="image/*" style="display:none;">
  
  <script>
  // Simple inline upload script
  document.getElementById('cover_upload_input').addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    
    var reader = new FileReader();
    reader.onload = function(e) {
      var formData = new FormData();
      formData.append('f', 'coverUpload');
      formData.append('image', e.target.result);
      
      fetch('<?php echo $base_url; ?>requests/request.php', {
        method: 'POST',
        body: formData
      })
     .then(r => r.text())
.then(url => {
  alert("<?php echo mf_h($LANG['cover_updated'] ?? 'Cover updated! Refresh the page to see changes.'); ?>");
  location.reload();
})

      .catch(err => alert('Upload failed: ' + err));
    };
    reader.readAsDataURL(file);
  });
  
  document.getElementById('avatar_upload_input').addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    
    var reader = new FileReader();
    reader.onload = function(e) {
      var formData = new FormData();
      formData.append('f', 'avatarUpload');
      formData.append('image', e.target.result);
      
      fetch('<?php echo $base_url; ?>requests/request.php', {
        method: 'POST',
        body: formData
      })
 .then(r => r.text())
.then(url => {
  alert("<?php echo mf_h($LANG['avatar_updated'] ?? 'Avatar updated! Refresh the page to see changes.'); ?>");
  location.reload();
})


      .catch(err => alert('Upload failed: ' + err));
    };
    reader.readAsDataURL(file);
  });
  </script>
<?php } ?>
      </div> <!-- closes .i_profile_cover -->
      
      <!-- Hidden file inputs for direct upload -->
      <input type="file" id="profile_cover_upload" accept="image/*" style="display:none;" />
      <input type="file" id="profile_avatar_upload" accept="image/*" style="display:none;" />
      
      <div class="i_u_profile_info">
       <div class="i_u_name">
  <?php echo mf_h($p_userfullname); ?>
</div>
        
        <?php if ($p_is_creator == 'developer') { ?>
            <div class="developer-badge-row">
                <span class="developer-icon-surround"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?></span>
                <span class="developer-badge"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?> Developer</span>
                <span class="developer-icon-surround"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?></span>
            </div>
        <?php } ?>

        <div style="margin-top:8px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
         <?php if ($__isLive) { ?>
         <span style="font-weight:700">🔴 <?php echo mf_h($LANG['live_now'] ?? 'LIVE NOW'); ?></span>

          <a class="join-live-pill" href="<?php echo mf_h($__liveUrl); ?>">
            <span class="live-dot-mini" aria-hidden="true"></span>
           <?php echo mf_h($LANG['join_live'] ?? 'Join Live'); ?>

          </a>
        <?php } else { ?>
            <?php echo html_entity_decode($pTime); ?>
          <?php } ?>
        </div>

        <?php if ($p_friend_status != 'me') { ?>
          <div class="i_p_cards">
            <?php echo html_entity_decode($pCategory); ?>
          </div>

          <div class="i_p_cards">
            <?php echo html_entity_decode($sendMessage); ?>

            <div class="i_btn_item transition copyUrl tabing ownTooltip"
                 data-label="<?php echo mf_h($LANG['copy_profile_url']);?>"
                 data-clipboard-text="<?php echo mf_h($profileUrl); ?>">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('30')); ?>
            </div>

            <div class="i_btn_item <?php echo mf_h($blockBtn); ?> transition tabing ownTooltip"
                 data-label="<?php echo mf_h($LANG['block_this_user']);?>"
                 data-u="<?php echo mf_h($p_profileID); ?>">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('64')); ?>
            </div>

            <?php if ($p_friend_status != 'subscriber') { ?>
              <div class="i_fw<?php echo mf_h($p_profileID); ?> transition <?php echo mf_h($flwrBtn); ?>"
                   id="i_btn_like_item" data-u="<?php echo mf_h($p_profileID); ?>">
                <?php echo html_entity_decode($flwBtnIconText); ?>
              </div>
            <?php } ?>
          </div>

          <?php if ($pCertificationStatus == '2' && $pValidationStatus == '2' && $feesStatus == '2') { ?>
            <div class="i_p_items_box">
              <?php if ($p_friend_status != 'subscriber') { ?>
                <div class="i_btn_become_fun <?php echo mf_h($subscBTN); ?> transition"
                     data-u="<?php echo mf_h($p_profileID); ?>">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . $LANG['become_a_subscriber']; ?>
                </div>
              <?php } else { if ($p_subscription_type == 'point') { ?>
                <div class="i_btn_unsubscribe transition unSubUP" data-u="<?php echo mf_h($p_profileID); ?>">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . $LANG['unsubscribe']; ?>
                </div>
              <?php } else { ?>
                <div class="i_btn_unsubscribe transition unSubU" data-u="<?php echo mf_h($p_profileID); ?>">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')) . $LANG['unsubscribe']; ?>
                </div>
              <?php }} ?>

              <div class="i_btn_send_to_point transition sendPoint tabing flex_"
                   data-u="<?php echo mf_h($p_profileID); ?>">
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('145')) . $LANG['offer_a_tip']; ?>
              </div>
            </div>
          <?php } ?>
        <?php } else { /* For the profile owner's view */ ?>

  <?php
    // Labels (with safe fallbacks)
    $__editLabel = $LANG['edit_profile'] ?? 'Edit Profile';
    $__copyLabel = $LANG['copy_profile_url'] ?? 'Copy Profile URL';

    // Pick a gear icon (fallback through a few IDs)
    $__gear = trim((string)$iN->iN_SelectedMenuIcon('44'));
    if ($__gear === '') { $__gear = trim((string)$iN->iN_SelectedMenuIcon('45')); }
    if ($__gear === '') { $__gear = trim((string)$iN->iN_SelectedMenuIcon('79')); }

    // Pick a share icon (fallback through a second ID)
    $__share = trim((string)$iN->iN_SelectedMenuIcon('30'));
    if ($__share === '') { $__share = trim((string)$iN->iN_SelectedMenuIcon('149')); }

    // Profile URL fallback
    $__profileUrl = $profileUrl ?: (rtrim($base_url,'/').'/'.$p_username);
  ?>

  <div class="mf_profile_action_row">
    <!-- Edit Profile -->
    <div class="i_btn_item transition tabing ownTooltip owner-action-button"
         data-label="<?php echo mf_h($__editLabel); ?>"
         onclick="window.location.href='<?php echo mf_h(rtrim($base_url,'/')); ?>/settings?tab=profile';">
      <?php
        if ($__gear !== '') {
          echo html_entity_decode($__gear);
        } else {
          // inline SVG fallback
          echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8.66-3.5c0-.48-.05-.95-.14-1.4l2.06-1.6-2-3.46-2.5 1a8 8 0 0 0-2.42-1.41l-.38-2.6h-4l-.38-2.6a8 8 0 0 0-2.42 1.41l-2.5-1-2 3.46 2.06 1.6c-.09.45-.14.92-.14 1.4s.05.95.14 1.4l-2.06 1.6 2 3.46 2.5-1a8 8 0 0 0 2.42 1.41l.38 2.6h4l.38-2.6a8 8 0 0 0 2.42-1.41l2.5 1 2-3.46-2.06-1.6c.09-.45.14-.92.14-1.4Z"/></svg>';
        }
      ?>
    </div>

    <!-- Category badge in the middle -->
    <?php echo html_entity_decode($pCategory); ?>

    <!-- Copy URL -->
    <div class="i_btn_item transition copyUrl tabing ownTooltip owner-action-button"
         data-label="<?php echo mf_h($__copyLabel); ?>"
         data-clipboard-text="<?php echo mf_h($__profileUrl); ?>">
      <?php
        if ($__share !== '') {
          echo html_entity_decode($__share);
        } else {
          // inline SVG fallback
          echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a3 3 0 1 0-2.83-4H15a3 3 0 0 0 .17 1l-7.4 3.7A3 3 0 0 0 6 8a3 3 0 0 0 1.77 2.73l7.39 3.7a3 3 0 0 0-.16 1.07A3 3 0 1 0 18 16a3 3 0 0 0-2.77-2.98L7.84 9.4A3 3 0 0 0 9 8c0-.37-.07-.73-.2-1.06l7.99-3.99A3 3 0 0 0 18 8Z"/></svg>';
        }
      ?>
    </div>
  </div>

<?php } ?>

        <?php
        $sociallinks = $iN->iN_ShowUserSocialSites($p_profileID);
        if ($sociallinks) {
          echo '<div class="i_profile_menu"><div class="i_profile_menu_middle flex_ tabing">';
          foreach ($sociallinks as $sDa) {
            $sLink = $sDa['s_link'] ?? NULL;
            $sIcon = $sDa['social_icon'] ?? NULL;
            echo '<div class="s_m_link flex_ tabing"><a class="flex_ tabing" href="' . mf_h($sLink) . '">' . $sIcon . '</a></div>';
          }
          echo '</div></div>';
        }
        ?>

        <?php
        $check = $iN->iN_CheckUnsubscribeStatus($userID, $p_profileID);
        if ($unSubscribeStyle == 'yes' && $check && $userID != $p_profileID) {
          $finishTime = date('Y-m-d', strtotime($check)); ?>
          <div class="i_p_item_box">
            <div class="sub_finish_time">
              <?php echo preg_replace('/{.*?}/', $finishTime, $LANG['subs_finish_at']); ?>
            </div>
          </div>
        <?php } ?>

        <?php if ($p_profileBio) { ?>
          <div class="i_p_item_box"><div class="i_p_bio"><?php echo html_entity_decode($p_profileBio); ?></div></div>
        <?php } ?>

        <div class="i_p_item_box flex_ tabing mf_ff_tabs">
          <div class="i_p_ffs flex_ tabing <?php echo mf_h($pCat) == 'following' ? "active_page_menu" : ""; ?>">
            <a class="flex_ tabing" href="<?php echo mf_h($base_url.$p_username.'?pcat=following');?>#ff-target">
              <div class="mf_ff_text">
                <span class="mf_ff_num"><?php echo number_format((int)$mf_following); ?></span>
                <span class="mf_ff_lab"><?php echo mf_h($LANG['im_following'] ?? 'Following'); ?></span>
              </div>
            </a>
          </div>

          <div class="i_p_ffs flex_ tabing <?php echo mf_h($pCat) == 'followers' ? "active_page_menu" : ""; ?>">
            <a class="flex_ tabing" href="<?php echo mf_h($base_url.$p_username.'?pcat=followers');?>#ff-target">
              <div class="mf_ff_text">
                <span class="mf_ff_num"><?php echo number_format((int)$mf_followers); ?></span>
                <span class="mf_ff_lab"><?php echo mf_h($LANG['my_followers'] ?? 'Followers'); ?></span>
              </div>
            </a>
          </div>

          <?php if ($pCertificationStatus == '2' && $pValidationStatus == '2' && $feesStatus == '2') { ?>
         <div class="i_p_ffs i_p_ffs_plus flex_ tabing">
    <div class="mf_ff_text">
        <span class="mf_ff_num"><?php echo number_format((int)$mf_total_likes); ?></span>
        <span class="mf_ff_lab"><?php echo mf_h($LANG['total_likes'] ?? $LANG['likes'] ?? 'Likes'); ?></span>
    </div>
</div>
          <?php } ?>
        </div>
        <div class="i_profile_menu">
          <div class="i_profile_menu_middle flex_ tabing">
            <div class="i_profile_menu_item <?php if(!isset($pCat)){echo 'active_page_menu';}?>">
              <a href="<?php echo mf_h($profileUrl);?>">
                <div class="i_p_sum"><?php echo mf_h($totalPost); ?></div>
                <div class="i_profile_menu_item_con flex_ tabing">
                  <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('67')); ?></div>
                  <div class="i_profile_menu_item_name flex_ tabing"><?php echo mf_h($LANG['profile_posts'] ?? 'Posts'); ?></div>
                </div>
              </a>
            </div>

            <div class="i_profile_menu_item <?php echo mf_h($pCat) == 'photos' ? "active_page_menu" : ""; ?>">
              <a href="<?php echo mf_h($base_url.$p_username.'?pcat=photos');?>">
                <div class="i_p_sum"><?php echo mf_h($totalImagePost); ?></div>
                <div class="i_profile_menu_item_con flex_ tabing">
                  <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('68')); ?></div>
                  <div class="i_profile_menu_item_name flex_ tabing"><?php echo mf_h($LANG['profile_post_images'] ?? 'Photos'); ?></div>
                </div>
              </a>
            </div>

            <div class="i_profile_menu_item <?php echo mf_h($pCat) == 'videos' ? "active_page_menu" : ""; ?>">
              <a href="<?php echo mf_h($base_url.$p_username.'?pcat=videos');?>">
                <div class="i_p_sum"><?php echo mf_h($totalVideoPosts); ?></div>
                <div class="i_profile_menu_item_con flex_ tabing">
                  <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('52')); ?></div>
                  <div class="i_profile_menu_item_name flex_ tabing"><?php echo mf_h($LANG['profile_videos'] ?? 'Videos'); ?></div>
                </div>
              </a>
            </div>

            <div class="i_profile_menu_item <?php echo mf_h($pCat) == 'audios' ? "active_page_menu" : ""; ?>">
              <a href="<?php echo mf_h($base_url.$p_username.'?pcat=audios');?>">
                <div class="i_p_sum"><?php echo mf_h($totalAudioPosts); ?></div>
                <div class="i_profile_menu_item_con flex_ tabing">
                  <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('152')); ?></div>
                  <div class="i_profile_menu_item_name flex_ tabing"><?php echo mf_h($LANG['profile_audios'] ?? 'Audios'); ?></div>
                </div>
              </a>
            </div>

            <?php if ($iN->iN_ShopStatus(1) == 'yes') { ?>
              <div class="i_profile_menu_item <?php echo mf_h($pCat) == 'products' ? "active_page_menu" : ""; ?>">
                <a href="<?php echo mf_h($base_url.$p_username.'?pcat=products');?>">
                  <div class="i_p_sum"><?php echo mf_h($totalProducts); ?></div>
                  <div class="i_profile_menu_item_con flex_ tabing">
                    <div class="i_profile_menu_icon flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?></div>
                    <div class="i_profile_menu_item_name flex_ tabing"><?php echo mf_h($LANG['profile_products'] ?? 'Products'); ?></div>
                  </div>
                </a>
              </div>
            <?php } ?>
          </div>
        </div>
        </div>
      </div>
  </div>
<div id="ff-target" aria-hidden="true"></div>
</div>
<script>
(function($){
  // 1) Open the tip box (modal)
  $(document).on('click', '.sendPoint', function(e){
    e.preventDefault();
    var uid = $(this).data('u');
    $.post('/requests/request.php', { f:'p_tips', tip_u: uid, tpid:'' })
      .done(function(html){
        // server returns the modal HTML from sendTipPoint.php
        $('body').append(html);
      })
      .fail(function(xhr){
        console.error('open tip modal failed', xhr.status, xhr.responseText);
      });
  });

  // 2) Submit the tip from inside the modal
  // Adjust selectors to the actual fields in sendTipPoint.php
  $(document).on('click', '#sendTipBtn', function(e){
    e.preventDefault();
    var $m   = $(this).closest('.i_modal_bg_in, .tipModal, body'); // container
    var uid  = $m.find('#tip_user_id').val() || $m.find('[name="tip_u"]').val();
    var val  = parseInt($m.find('#tip_amount').val() || $m.find('[name="tipVal"]').val(), 10) || 0;

    $.post('/requests/request.php', { f:'p_sendTipProfile', tip_u: uid, tipVal: val })
      .done(function(res){
        try { res = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e){
          console.error('Bad JSON from tip submit:', res);
          return;
        }
        if (res.status === 'ok') {
          // success UI: close modal, show toast, etc.
          $m.remove();
        } else if (res.enamount === 'notEnouhCredit' && res.redirect) {
          window.location = res.redirect;
        } else if (res.enamount === 'notEnough') {
          alert('Amount is below the minimum tip.');
        } else {
          alert('Could not send tip. Try again.');
        }
      })
      .fail(function(xhr){
        console.error('tip submit failed', xhr.status, xhr.responseText);
      });
  });

  // optional: close modal buttons
  $(document).on('click', '.i_close_modal, .tip-close', function(){ 
    $(this).closest('.i_modal_bg_in, .tipModal').remove();
  });
})(jQuery);
</script>

<!-- UNIVERSAL IMAGE VIEWER - Works for everyone (visitors and owner) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cover image viewer
    var coverImg = document.querySelector('.i_im_cover img');
    if (coverImg) {
        coverImg.style.cursor = 'pointer';
        coverImg.addEventListener('click', function() {
            var coverUrl = this.src;
            var modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
            modal.innerHTML = '<img src="' + coverUrl + '" style="max-width:90%;max-height:90%;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.5);">';
            modal.onclick = function() { document.body.removeChild(modal); };
            document.body.appendChild(modal);
        });
    }
    
    // Avatar viewer
    var avatar = document.querySelector('.i_profile_avatar');
    if (avatar) {
        avatar.style.cursor = 'pointer';
        avatar.addEventListener('click', function() {
            var avatarUrl = this.getAttribute('data-avatar');
            var modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
            modal.innerHTML = '<img src="' + avatarUrl + '" style="max-width:90%;max-height:90%;border-radius:50%;box-shadow:0 10px 40px rgba(0,0,0,0.5);">';
            modal.onclick = function() { document.body.removeChild(modal); };
            document.body.appendChild(modal);
        });
    }
});
</script>