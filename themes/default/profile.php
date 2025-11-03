<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
        // Include meta, CSS and JS files
        include("header/meta.php");
        include("header/css.php");
        include("header/javascripts.php");
    ?>
</head>
<body>
    <?php
        // Show login form if user is not logged in
        if ($logedIn == 0) {
            include('login_form.php');
        }

        // Include header
        include("header/header.php");
    ?>

    <div class="profile_wrapper" id="prw" data-u="<?php echo iN_HelpSecure($p_profileID); ?>">
        <?php
            $page = 'profile';

            // Load profile info section
            include("profile/profile_infos.php");

            // Show message if logged out and posts are hidden
            if ($logedIn == 0 && $p_showHidePosts == '1') {
                echo '<div class="th_middle">
                        <div class="pageMiddle">
                            <div id="moreType" data-type="' . $page . '">' . $LANG['just_loged_in_user'] . '</div>
                        </div>
                      </div>';
            } else {
                // Include user's posts
                include("posts.php");
            }
        ?>
    </div>
    
    <!-- Stop infinite scroll on media grids -->
 
</body>
</html>