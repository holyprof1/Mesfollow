<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($LANG['live_is_done_title'] ?? 'Le live est terminé'); ?></title>
    <?php
    include "layouts/header/meta.php";
    include "layouts/header/css.php";
    include "layouts/header/javascripts.php";
    ?>
</head>
<body>
<?php if ($logedIn == 0) {include 'layouts/login_form.php';}?>
<?php include "layouts/header/header.php";?>

<div class="wrapper">
    <div class="i_not_found_page transition i_centered" style="padding: 80px 20px; text-align:center;">
        <div class="noPostIcon" style="font-size: 80px; margin-bottom: 20px; color:#333;">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('113')); ?>
        </div>

        <h1 style="font-size:24px; color:#333; margin-bottom:10px;">
            <?php echo iN_HelpSecure($LANG['live_is_done_title'] ?? 'Le live est terminé'); ?>
        </h1>

        <p style="font-size:16px; color:#666;">
            <?php echo iN_HelpSecure($LANG['live_is_done_desc'] ?? 'Ce live n\'est plus disponible.'); ?>
        </p>

        <a href="<?php echo $base_url; ?>" class="i_btn_l_like_item" style="margin-top:30px; display:inline-block; text-decoration:none; background-color:#d9d9d9; color:#111; padding:10px 20px; border-radius:8px; font-weight:bold;">
            <?php echo iN_HelpSecure($LANG['home_page'] ?? 'Home Page'); ?>
        </a>
    </div>
</div>

<div class="footer_container_out"><?php include("layouts/footer.php");?></div>
</body>
</html>