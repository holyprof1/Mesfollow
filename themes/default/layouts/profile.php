<!DOCTYPE html>
<html lang="en">
<head>
	
   
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>

    <?php
    // Include meta tags, CSS files, and JavaScript files
    include("header/meta.php");
    include("header/css.php");
    include("header/javascripts.php");
    ?>
</head>
<body>
<?php 
// If the user is not logged in, show the login form
if($logedIn == 0){ 
    include('login_form.php'); 
} 
?>

<?php 
// Include the top header section
include("header/header.php"); 
?>

<div class="profile_wrapper" id="prw" data-u="<?php echo iN_HelpSecure($p_profileID);?>">
    <?php
    // Set the current page variable
    $page = 'profile';

// Define the list of allowed profile content categories
$pCats = array('photos','videos','audios','products','followers','following','subscribers');

// Read pcat once and keep it as a short string ('' when none)
$pCat = '';
if (!empty($_GET['pcat']) && in_array($_GET['pcat'], $pCats, true)) {
    $pCat = preg_replace('~[^a-z]~', '', $_GET['pcat']); // safe, letters only
}
// ------- Build profile media filter WHERE (photos/videos/audios) -------
$__pcatWhere = '';
if (!function_exists('mf_cols')) {
  function mf_cols($db, $table){
    $cols = [];
    if ($db instanceof mysqli) {
      if ($res = $db->query("SHOW COLUMNS FROM `$table`")) {
        while($r = $res->fetch_assoc()){ $cols[] = $r['Field']; }
      }
    }
    return $cols;
  }
}
if ($pCat === 'photos' || $pCat === 'videos' || $pCat === 'audios') {
  $postsCols = mf_cols($db, 'i_posts');            // check columns on i_posts
  $uplCols   = mf_cols($db, 'i_user_uploads');     // check columns on i_user_uploads

  $want = $pCat === 'photos' ? 'image' : ($pCat === 'videos' ? 'video' : 'audio');

  if (in_array('post_type', $postsCols, true)) {
    // Simple case: a type column exists on i_posts
    $__pcatWhere = " AND p.post_type = '{$want}' ";
  } elseif ($uplCols) {
    // Fallback: look into uploads by MIME / file_type
    $fileTypeCol = in_array('file_type',$uplCols,true) ? 'file_type'
                 : (in_array('mime',$uplCols,true) ? 'mime' : '');
    $postIdCol   = in_array('post_id',$uplCols,true) ? 'post_id' : '';

    if ($fileTypeCol && $postIdCol) {
      if ($fileTypeCol === 'mime') {
        $__pcatWhere = " AND EXISTS (
            SELECT 1 FROM i_user_uploads f
            WHERE f.`$postIdCol` = p.post_id
              AND f.`$fileTypeCol` LIKE '{$want}/%'
          ) ";
      } else {
        $__pcatWhere = " AND EXISTS (
            SELECT 1 FROM i_user_uploads f
            WHERE f.`$postIdCol` = p.post_id
              AND f.`$fileTypeCol` = '{$want}'
          ) ";
      }
    }
  }
}
// ------- end: $__pcatWhere -------


    // Include profile information section
    include("profile/profile_infos.php");

    // If user is not logged in and posts are hidden, show access restriction message
    if($logedIn == 0 && $p_showHidePosts == '1'){
        echo '<div class="th_middle"><div class="pageMiddle"><div id="moreType" data-type="'.$page.'">'.$LANG['just_loged_in_user'].'</div></div></div>';
    } else {
        // If category is valid, show the posts
        $pCats = array('photos','videos','audios','products','followers','following','subscribers');
        if(isset($_GET['pcat']) && $_GET['pcat'] != '' && !empty($_GET['pcat']) && in_array($_GET['pcat'], $pCats)){
            $pCat = mysqli_real_escape_string($db, $_GET['pcat']);
            include("posts.php");
        } else {
            include("posts.php");
        }
    }
    ?>
</div>

<!-- Audio player -->
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/greenaudioplayer/audioplayer.js?v=<?php echo iN_HelpSecure($version); ?>"></script>

<!-- Profile upload -->
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/profileDirectUpload.js?v=<?php echo iN_HelpSecure($version); ?>"></script>

<!-- Media grid handler -->
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/media-grid-handler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>

<!-- Anchor scroll -->
<script src="<?php echo $base_url; ?>themes/<?php echo $currentTheme; ?>/js/anchor-scroll-fix.js?v=<?php echo $version; ?>"></script>
</body>
</html>