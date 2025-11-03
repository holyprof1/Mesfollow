<div class="th_middle">
    <div class="greetalert hidden"></div>
    <div class="pageMiddle">
        <?php
        // If user is not logged in, show welcome box
        if ($logedIn === 0) {
            include 'posts/welcomebox.php';
        } else {
            if ($page !== 'profile') {
                // Announcement box
                include 'widgets/announcement.php';
                // Show stories if enabled
                if ($iN->iN_StoryData($userID, '1') === 'yes') {
                    include 'storie/stories.php';
                }
                // Show post form if allowed
                if ($normalUserCanPost === 'yes' || $feesStatus === '2') {
                    include 'posts/postForm.php';
                }
            }
        }
        
        // Random box logic (ads or suggested users) - EXCLUDE FROM PROFILE
        if ($page !== 'profile') {
            $files = [
                1 => 'suggestedusers',
                2 => 'ads'
            ];
            shuffle($files);
            include 'random_boxs/' . iN_HelpSecure($files[0]) . '.php';
        }
        
        // Post category (null-safe)
        $pCat = $pCat ?? null;
        // Boosted post display
        if ($boostedPostEnableDisable === 'yes' && $iN->iN_CheckHaveBoostedPostAllTheSite() > 0) {
            include 'posts/boostedPost.php';
        }
        // Show pinned posts only on profile page
        if ($page === 'profile') {
            include 'posts/pinedPosts.php';
        }
        // Posts output block
        ?>
        <div id="moreType" data-type="<?php echo iN_HelpSecure($page); ?>" data-po="<?php echo iN_HelpSecure($pCat); ?>">
            <?php include 'posts/htmlPosts.php'; ?>
        </div>
    </div>
</div>