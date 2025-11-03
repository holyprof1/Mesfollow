<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1" />
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>

    <?php
    // Include meta tags, CSS, and head JavaScript files
    include 'header/meta.php';
    include 'header/css.php';
    include 'header/javascripts.php';
    ?>
</head>
<body>
    <?php
    // Define default page
    $page = 'moreposts';

    // Show login form if user is not logged in
    if ($logedIn === '0') {
        include 'login_form.php';
    }

    // Include site header
    include 'header/header.php';
    ?>

    <div class="wrapper <?php echo ($logedIn === '0' ? 'NotLoginYet' : ''); ?>">
        <?php
        // Sidebar menu for logged-in users
        if ($logedIn !== 0) {
            include 'left_menu.php';
        }

        // Main post list and right sidebar
        include 'posts.php';
        include 'page_right.php';
        ?>
    </div>

    <?php if ($logedIn !== 0) { ?>
        <!-- Load story-related JS only for logged-in users -->
        <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/sw/lib/hammer.min.js"></script>
        <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/sw/story-view.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
        <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/sw/storyHandler.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
    <?php } ?>
</body>
</html>