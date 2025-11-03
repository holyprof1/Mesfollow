<?php
include_once __DIR__ . "/includes/inc.php";

$pageUrl = $base_url;
$pageTitle = $siteTitle;
$pageDescription = $siteDescription;
$pageImage = $base_url . "themes/" . $currentTheme . "/img/og.jpg";
$redirectURL = $base_url;

if (!empty($_GET['page'])) {
  $pageID = mysqli_real_escape_string($db, $_GET['page']);
  $pageQrCode = isset($_GET['qr']) ? mysqli_real_escape_string($db, $_GET['qr']) : '';
  $decodePageID = base64_decode($pageID);
  $getData = $iN->iN_GetUserDetailsFromUsername($decodePageID);

  if ($getData) {
    $userProfileID = $getData['iuid'];
    $pageLink = $getData['i_username'];
    $userFullName = $getData['i_user_fullname'] ?: $pageLink;
    $pageDescription = ($getData['u_bio'] ?: ($userFullName . ' | ' . $LANG['meta_description_profile']));
    $pageImage = $iN->iN_UserAvatar($userProfileID, $base_url);
    $pageUrl = $base_url . $pageLink;
    $pageTitle = $userFullName . ' | ' . $siteTitle;
    $redirectURL = $pageUrl;

    if (!empty($pageQrCode) && !empty($getData['qr_image'])) {
      $pageImage = $base_url . $getData['qr_image'];
      $pageDescription = $userFullName . ' | ' . $LANG['meta_description_qr_code'];
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?php echo iN_HelpSecure($pageTitle); ?></title>

  <!-- Primary -->
  <meta name="title" content="<?php echo iN_HelpSecure($pageTitle); ?>">
  <meta name="description" content="<?php echo iN_HelpSecure($pageDescription); ?>">
  <meta name="keywords" content="<?php echo iN_HelpSecure($siteKeyWords); ?>">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo iN_HelpSecure($pageUrl); ?>">
  <meta property="og:title" content="<?php echo iN_HelpSecure($pageTitle); ?>">
  <meta property="og:description" content="<?php echo iN_HelpSecure($pageDescription); ?>">
  <meta property="og:image" content="<?php echo iN_HelpSecure($pageImage); ?>">

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta property="twitter:url" content="<?php echo iN_HelpSecure($pageUrl); ?>">
  <meta name="twitter:title" content="<?php echo iN_HelpSecure($pageTitle); ?>">
  <meta name="twitter:description" content="<?php echo iN_HelpSecure($pageDescription); ?>">
  <meta name="twitter:image" content="<?php echo iN_HelpSecure($pageImage); ?>">
</head>
<body>
  <script>window.location.replace("<?php echo iN_HelpSecure($redirectURL); ?>");</script>
</body>
</html>
