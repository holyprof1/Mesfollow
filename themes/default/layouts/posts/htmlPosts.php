<?php
/* htmlPosts.php — drop-in replacement */

/* ---------- INCOMING PARAMS ---------- */
$lastPostID = isset($_POST['last']) ? (int)$_POST['last'] : 0;

/* $page comes either from the parent include or the AJAX payload */
$page = $_POST['type'] ?? ($page ?? '');
echo "<!-- MF-DEBUG: page='$page', logedIn='$logedIn', lastPostID='$lastPostID' -->";
$__mf_disable_profile_promos = ($page === 'profile');

/* category (photos | videos | audios | products | followers | following | subscribers) */
$pCat = $pCat ?? '';
if (isset($_POST['po']))    { $pCat = $_POST['po']; }
elseif (isset($_POST['pcat'])) { $pCat = $_POST['pcat']; }
/* Check if we should use grid view */
$useGridView = (
  (in_array($pCat, ['photos','videos','audios'], true) && $page === 'profile')
  || ($page === 'savedpost')
);


/* target profile id (can arrive as p, uid or u depending on caller) */
$p_profileID = isset($p_profileID) ? (int)$p_profileID
             : (int)($_POST['p'] ?? $_POST['uid'] ?? $_POST['u'] ?? 0);

/* hashtag page parameter (if used) */
$pageFor = $_POST['p'] ?? ($pageFor ?? '');

/* defaults */
$notFoundNot       = '';
$loginFormClass    = '';
$postsFromData     = [];
$postsFromDataProduct = $profileFollowers = $profileFollowing = $profileSubscriber = $userTextForPostTip = '';

// Show more items in grid
// Show more items in grid
if (in_array($pCat, ['photos', 'videos', 'audios'], true) || $page === 'savedpost') {
    $showingNumberOfPost = 50;
}

/* ---------- HELPERS FOR MEDIA FILTER ---------- */
/**
 * Build a WHERE snippet that keeps posts which have at least one matching upload id
 * in p.post_file for the requested media type (image|video|audio).
 * We filter via uploaded_file_ext and rely on FIND_IN_SET to link uploads to posts.
 */
/* ---------- FORCE EXTENSION-BASED FILTER (ignore upload_type) ---------- */
$__mf_build_where_for_media = function(string $wantType) {
    // Map by extension only – we ignore upload_type completely (your DB has 'wall')
    $extMap = [
        'image' => ['jpg','jpeg','png','gif','webp','bmp'],
        'video' => ['mp4','mov','m4v','webm','mkv','avi','mpeg','mpg'],
        'audio' => ['mp3','m4a','aac','wav','ogg','oga','flac'],
    ];
    $exts = $extMap[$wantType] ?? [];
    if (!$exts) { return ''; }

    $extList = "'" . implode("','", array_map('strtolower', $exts)) . "'";

    // Link uploads to posts through post_file CSV (ids like "74,38,18")
    return "
      AND EXISTS (
        SELECT 1
          FROM i_user_uploads f
         WHERE f.upload_status = '1'
           AND LOWER(f.uploaded_file_ext) IN ($extList)
           AND FIND_IN_SET(
                 f.upload_id,
                 REPLACE(TRIM(BOTH ',' FROM p.post_file), ' ', '')
               ) > 0
      )";
};






/**
 * Minimal, parameterized SQL fetch used as fallback when class methods
 * (iN_AllUserProfilePostsByChoosePhotos/Videos/Audios) don’t exist or return empty.
 */
$__mf_sql_media_fallback = function($db, int $p_profileID, int $lastPostID, int $limit, string $whereExtra) {
    $rows = [];
    $sql  = "SELECT p.*
               FROM i_posts p
              WHERE p.post_owner_id = ?
                AND p.post_status   = '1'
                $whereExtra";
    if ($lastPostID > 0) { $sql .= " AND p.post_id < ? "; }
    $sql .= " ORDER BY p.post_id DESC LIMIT ?";

    if ($st = $db->prepare($sql)) {
        if ($lastPostID > 0) {
            $st->bind_param("iii", $p_profileID, $lastPostID, $limit);
        } else {
            $st->bind_param("ii",  $p_profileID, $limit);
        }
        if ($st->execute() && ($res = $st->get_result())) {
            while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        }
        $st->close();
    }
    return $rows;
};

/* ---------- DATA FETCH ---------- */
if ($logedIn === 0) {
    $loginFormClass = 'loginForm';

    if ($page === 'moreposts') {
        $postsFromData = $iN->iN_AllFriendsPostsOut($lastPostID, $showingNumberOfPost);

    } elseif ($page === 'profile') {
        switch ($pCat) {
           case 'audios':
    $where = $__mf_build_where_for_media('audio');
    $postsFromData = $__mf_sql_media_fallback($db, $p_profileID, $lastPostID, $showingNumberOfPost, $where);
    break;

          case 'videos':
    $where = $__mf_build_where_for_media('video');
    $postsFromData = $__mf_sql_media_fallback($db, $p_profileID, $lastPostID, $showingNumberOfPost, $where);
    break;


          case 'photos':
    $where = $__mf_build_where_for_media('image');
    $postsFromData = $__mf_sql_media_fallback($db, $p_profileID, $lastPostID, $showingNumberOfPost, $where);
    break;


            case 'products':
                $postsFromDataProduct = $iN->iN_AllUserProfileProductPosts($p_profileID, $lastPostID, $showingNumberOfPost);
                break;

            case 'followers':
                $profileFollowers = $iN->iN_FollowerUsersListProfilePage($p_profileID, $lastPostID, $showingNumberOfPost);
                break;

            case 'following':
                $profileFollowing = $iN->iN_FollowingUsersListProfilePage($p_profileID, $lastPostID, $showingNumberOfPost);
                break;

            case 'subscribers':
                $profileSubscriber = $iN->iN_SubscribersUsersListProfilePage($p_profileID, $lastPostID, $showingNumberOfPost);
                break;

            default:
                $postsFromData = $iN->iN_AllUserProfilePosts($p_profileID, $lastPostID, $showingNumberOfPost);
        }

        $notFoundNot = $LANG['no_post_wilbe_shown_in_this_profile'];
        echo "<!-- mf-profile-debug guest pcat={$pCat} rows=".(is_array($postsFromData)?count($postsFromData):0)." -->";

    } elseif ($page === 'hashtag') {
        $postsFromData = $iN->iN_GetHashTagsSearch($pageFor, $lastPostID, $showingNumberOfPost);
        $notFoundNot = $LANG['no_post_will_be_shown'];
    }

} else {
    /* Logged in */
    if (in_array($page, ['moreposts', 'friends'], true)) {
        $postsFromData = $iN->iN_AllFriendsPosts($userID, $lastPostID, $showingNumberOfPost);

    } elseif ($page === 'profile') {
        switch ($pCat) {
           case 'audios':
    $where = $__mf_build_where_for_media('audio');
    $postsFromData = $__mf_sql_media_fallback($db, $p_profileID, $lastPostID, $showingNumberOfPost, $where);
    break;

          case 'videos':
    $where = $__mf_build_where_for_media('video');
    $postsFromData = $__mf_sql_media_fallback($db, $p_profileID, $lastPostID, $showingNumberOfPost, $where);
    break;


          case 'photos':
    $where = $__mf_build_where_for_media('image');
    $postsFromData = $__mf_sql_media_fallback($db, $p_profileID, $lastPostID, $showingNumberOfPost, $where);
    break;


            case 'products':
                $postsFromDataProduct = $iN->iN_AllUserProfileProductPosts($p_profileID, $lastPostID, $showingNumberOfPost);
                break;

            case 'followers':
                $profileFollowers = $iN->iN_FollowerUsersListProfilePage($p_profileID, $lastPostID, $showingNumberOfPost);
                break;

            case 'following':
                $profileFollowing = $iN->iN_FollowingUsersListProfilePage($p_profileID, $lastPostID, $showingNumberOfPost);
                break;

            case 'subscribers':
                $profileSubscriber = $iN->iN_SubscribersUsersListProfilePage($p_profileID, $lastPostID, $showingNumberOfPost);
                break;

            default:
                $postsFromData = $iN->iN_AllUserProfilePosts($p_profileID, $lastPostID, $showingNumberOfPost);
        }

        $notFoundNot = $LANG['no_post_wilbe_shown_in_this_profile'];
        echo "<!-- mf-profile-debug auth pcat={$pCat} rows=".(is_array($postsFromData)?count($postsFromData):0)." -->";

    } elseif (in_array($page, ['allPosts', 'moreexplore'], true)) {
        $postsFromData = $iN->iN_AllUserForExplore($userID, $lastPostID, $showingNumberOfPost);

    } elseif (in_array($page, ['premiums', 'morepremium'], true)) {
        $postsFromData = $iN->iN_AllUserForPremium($userID, $lastPostID, $showingNumberOfPost);

    } elseif ($page === 'savedpost') {
        $postsFromData = $iN->iN_SavedPosts($userID, $lastPostID, $showingNumberOfPost);

    } elseif ($page === 'hashtag') {
        $postsFromData = $iN->iN_GetHashTagsSearch($pageFor, $lastPostID, $showingNumberOfPost);

    } elseif (in_array($page, ['purchasedpremiums', 'morepurchased'], true)) {
        $postsFromData = $iN->iN_AllUserForPurchasedPremium($userID, $lastPostID, $showingNumberOfPost);

    } elseif (in_array($page, ['boostedposts', 'moreboostedposts'], true)) {
        $postsFromData = $iN->iN_AllUserForBoostedPosts($userID, $lastPostID, $showingNumberOfPost);

    } elseif ($page === 'trendposts') {
        $postsFromData = $iN->iN_GetTotalHotPosts($userID, $showingNumberOfPost, $showingTrendPostLimitDay);
    }

    $notFoundNot = $notFoundNot ?: $LANG['no_post_will_be_shown'];
}

/* ---- Batch reshare counts for all posts in this page ---- */
$__mf_reshare_counts = [];
if (!empty($postsFromData) && is_array($postsFromData)) {
    $ids = [];
    foreach ($postsFromData as $p) {
        if (!empty($p['post_id']))        { $ids[] = (int)$p['post_id']; }
        if (!empty($p['shared_post_id'])) { $ids[] = (int)$p['shared_post_id']; } // also count originals of shared posts
    }
    $ids = array_values(array_unique(array_filter($ids)));

    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT shared_post_id AS pid, COUNT(*) AS c
                  FROM i_posts
                 WHERE shared_post_id IN ($in)
              GROUP BY shared_post_id";
        if ($st = $db->prepare($sql)) {
            $types = str_repeat('i', count($ids));
            $st->bind_param($types, ...$ids);
            if ($st->execute() && ($res = $st->get_result())) {
                while ($row = $res->fetch_assoc()) {
                    $__mf_reshare_counts[(int)$row['pid']] = (int)$row['c'];
                }
            }
            $st->close();
        }
    }
}


/* ---------- RENDER ---------- */
/* ---- IF GRID VIEW, RENDER AND EXIT ---- */
if ($useGridView) {
    include __DIR__ . '/render-media-grid.php';
    exit;
}
if ($postsFromData) {
   $mfFeedIndex = 0;  // counter for “every 8 posts”

   foreach ($postsFromData as $postFromData) {
      $userPostID = $postFromData['post_id'] ?? null;
      if ($page == 'purchasedpremiums' || $page == 'morepurchased') {
         $userPostID = $postFromData['payment_id'] ?? null;
      } else if ($page == 'moreboostedposts') {
         $userPostID = $postFromData['post_id'] ?? null;
      } else if ($page == 'trendposts') {
         $userPostID = $postFromData['post_id'] ?? null;
      }

      $userPostOwnerID = $postFromData['post_owner_id'] ?? null;
      $userPostText = $postFromData['post_text'] ?? null;
      $userPostFile = $postFromData['post_file'] ?? null;
      $userPostCreatedTime = $postFromData['post_created_time'] ?? null;
      $crTime = date('Y-m-d H:i:s', $userPostCreatedTime);
      $userPostWhoCanSee = $postFromData['who_can_see'] ?? null;
      $userPostWantStatus = $postFromData['post_want_status'] ?? null;
      $userPostWantedCredit = $postFromData['post_wanted_credit'] ?? null;
      $userPostStatus = $postFromData['post_status'] ?? null;
      $userPostOwnerUsername = $postFromData['i_username'] ?? null;
      $userPostOwnerUserFullName = $postFromData['i_user_fullname'] ?? null;
      $userProfileFrame = $postFromData['user_frame'] ?? null;
      $checkPostBoosted = $iN->iN_CheckPostBoostedBefore($userPostOwnerID, $userPostID);

      $planIcon = '';
      if ($checkPostBoosted) {
         $getBoostDetails = $iN->iN_GetBoostedPostDetails($userPostID);
         $boostPlanID = $getBoostDetails['boost_type'] ?? null;
         $boostStatus = $getBoostDetails['status'] ?? null;
         $boostID = $getBoostDetails['boost_id'] ?? null;
         $viewCount = $getBoostDetails['view_count'] ?? null;
         $boostPostOwnerID = $getBoostDetails['iuid_fk'] ?? null;
         $planDetails = $iN->iN_GetBoostPostDetails($boostPlanID);
         $planIcon = '<div class="boostIcon flex_ justify-content-align-items-center">'.$planDetails['plan_icon'].$LANG['boosted_post'].'</div>';
      }

      if ($fullnameorusername == 'no') {
         $userPostOwnerUserFullName = $userPostOwnerUsername;
      }

      $userPostOwnerUserGender = $postFromData['user_gender'] ?? null;
      $userProfileFrame = $postFromData['user_frame'] ?? null;
      $userTextForPostTip = $postFromData['thanks_for_tip'] ?? null;
      $getUserPaymentMethodStatus = $postFromData['payout_method'] ?? null;
      $userPostHashTags = $postFromData['hashtags'] ?? null;
      $userPostCommentAvailableStatus = $postFromData['comment_status'] ?? null;
      $userPostOwnerUserLastLogin = $postFromData['last_login_time'] ?? null;
      $userProfileCategory = $postFromData['profile_category'] ?? null;

      $lastSeen = date("c", $userPostOwnerUserLastLogin);
      $OnlineStatus = date("c", time());
      $oStatus = time() - 35;
      if ($userPostOwnerUserLastLogin > $oStatus) {
         $timeStatus = '<div class="userIsOnline flex_ tabing">'.$LANG['online'].'</div>';
      } else {
         $timeStatus = '<div class="userIsOffline flex_ tabing">'.$LANG['offline'].'</div>';
      }

      $userPostPinStatus = $postFromData['post_pined'] ?? null;
      $slugUrl = $base_url.'post/'.$postFromData['url_slug'].'_'.$userPostID;
      $userPostSharedID = $postFromData['shared_post_id'] ?? null;
      $userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
      $userPostUserVerifiedStatus = $postFromData['user_verified_status'] ?? null;
	   /* The counter we want to show should belong to the ORIGINAL post.
   If this feed item is itself a share, show the count of its original. */
$__mf_reshare_target_id = $userPostSharedID ?: $userPostID;
$__mf_reshare_count     = $__mf_reshare_counts[$__mf_reshare_target_id] ?? 0;


      if ($userPostOwnerUserGender == 'male') {
         $publisherGender = '<div class="i_plus_g">'.$iN->iN_SelectedMenuIcon('12').'</div>';
      } else if ($userPostOwnerUserGender == 'female') {
         $publisherGender = '<div class="i_plus_gf">'.$iN->iN_SelectedMenuIcon('13').'</div>';
      } else if ($userPostOwnerUserGender == 'couple') {
         $publisherGender = '<div class="i_plus_g">'.$iN->iN_SelectedMenuIcon('58').'</div>';
      }

      $userVerifiedStatus = '';
      if ($userPostUserVerifiedStatus == '1') {
         $userVerifiedStatus = '<div class="i_plus_s">'.$iN->iN_SelectedMenuIcon('11').'</div>';
      }

      $profileCategory = $pCt = $profileCategoryLink = '';
      if ($userProfileCategory && $userPostUserVerifiedStatus == '1') {
         $profileCategory = $userProfileCategory;
         if (isset($PROFILE_CATEGORIES[$userProfileCategory])) {
            $pCt = $PROFILE_CATEGORIES[$userProfileCategory] ?? NULL;
         } else if (isset($PROFILE_SUBCATEGORIES[$userProfileCategory])) {
            $pCt = $PROFILE_SUBCATEGORIES[$userProfileCategory] ?? NULL;
         }
         $profileCategoryLink = '<a class="i_p_categoryp flex_ tabing_non_justify" href="'.$base_url.'creators?creator='.$userProfileCategory.'">'.$iN->iN_SelectedMenuIcon('65').$pCt.'</a>- ';
      }

      $postStyle = '';
      if (empty($userPostText)) { $postStyle = 'nonePoint'; }

      /* ====== COMMENTS (your existing logic unchanged) ====== */
      $allComments = $iN->iN_GetPostComments($userPostID, 0);
      $commentCount = $allComments ? count($allComments) : 0;
      $getUserComments = null;
      $TotallyPostComment = '';
      $fullText = '';

      if ($commentCount > 2) {
         $getUserComments = array_slice($allComments, 0, 2);
         $fullText = preg_replace('/{.*?}/', $commentCount, $LANG['t_comments'] ?? 'View all {count} comments');
      } else if ($commentCount > 0) {
         $getUserComments = $allComments;
         $fullText = $commentCount . ' ' . ($LANG['comment_s'] ?? 'commentaires');
      } else {
         $getUserComments = [];
         $fullText = '';
      }
      if ($commentCount > 0) {
         $TotallyPostComment = '
         <div class="lc_sum_container">
           <div class="comnts transition open-post-modal" data-id="'.$userPostID.'">' . $fullText . '</div>
         </div>';
      }
      /* ====== END COMMENTS ====== */

      $pSaveStatusBtn = $iN->iN_SelectedMenuIcon('22');
      if ($logedIn == 0) {
         $getFriendStatusBetweenTwoUser = '1';
         $checkPostLikedBefore = '';
         $checkPostReportedBefore = '';
         $checkUserPurchasedThisPost = '0';
      } else {
         $getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userPostOwnerID);
         $checkPostLikedBefore = $iN->iN_CheckPostLikedBefore($userID, $userPostID);
         $checkPostReportedBefore = $iN->iN_CheckPostReportedBefore($userID, $userPostID);
         if ($iN->iN_CheckPostSavedBefore($userID, $userPostID) == '1') {
            $pSaveStatusBtn = $iN->iN_SelectedMenuIcon('63');
         }
         if ($page == 'purchasedpremiums' || $page == 'morepurchased') {
            $checkUserPurchasedThisPost = $iN->iN_CheckUserPurchasedThisPost($userID, $postFromData['post_id']);
         } else {
            $checkUserPurchasedThisPost = $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID);
         }
      }

      $onlySubs = '';
      $premiumPost = '';
      if ($userPostWhoCanSee == '1') {
         $onlySubs = ''; $premiumPost = ''; $subPostTop = '';
         $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('50').'</div>';
      } else if ($userPostWhoCanSee == '2') {
         $subPostTop = ''; $premiumPost = '';
         $wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
         $onlySubs = '<div class="com_min_height"></div><div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('15').'</div><div class="onlySubs_note">'.preg_replace('/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']).'</div></div></div>';
      } else if ($userPostWhoCanSee == '3') {
         $subPostTop = 'extensionPost';
         $premiumPost = '<div class="premiumIcon flex_ justify-content-align-items-center">'.$iN->iN_SelectedMenuIcon('40').$LANG['l_premium'].'</div>';
         $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('51').'</div>';
         $onlySubs = '<div class="com_min_height"></div><div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('56').'</div><div class="onlySubs_note">'.preg_replace('/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']).'</div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
      } else if ($userPostWhoCanSee == '4') {
         $subPostTop = 'extensionPost';
         $premiumPost = '<div class="premiumIcon flex_ justify-content-align-items-center">'.$iN->iN_SelectedMenuIcon('40').$LANG['l_premium'].'</div>';
         $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
         $onlySubs = '<div class="com_min_height"></div><div class="onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="'.$userPostID.'">'.preg_replace('/{.*?}/', $userPostWantedCredit, $LANG['post_credit']).'</div><div class="buythistext prcsPost" id="'.$userPostID.'">'.$LANG['purchase_post'].'</div></div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
      }

      $postReportStatus = $iN->iN_SelectedMenuIcon('32').$LANG['report_this_post'];
      if ($checkPostReportedBefore == '1') {
         $postReportStatus = $iN->iN_SelectedMenuIcon('32').$LANG['unreport'];
      }
      if ($checkPostLikedBefore) {
         $likeIcon = $iN->iN_SelectedMenuIcon('18');
         $likeClass = 'in_unlike';
      } else {
         $likeIcon = $iN->iN_SelectedMenuIcon('17');
         $likeClass = 'in_like';
      }
      if ($userPostCommentAvailableStatus == '1') {
         $commentStatusText = $LANG['disable_comment'] ?? null;
      } else {
         $commentStatusText = $LANG['enable_comments'] ?? null;
      }
      $pPinStatus = '';
      $pPinStatusBtn = $iN->iN_SelectedMenuIcon('29').$LANG['pin_on_my_profile'];
      if ($userPostPinStatus == '1') {
         $pPinStatus = '<div class="i_pined_post" id="i_pined_post_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('62').'</div>';
         $pPinStatusBtn = $iN->iN_SelectedMenuIcon('29').$LANG['post_pined_on_your_profile'];
      }
      $waitingApprove = '';
      $likeSum = $iN->iN_TotalPostLiked($userPostID);
      if ($likeSum <= '0') { $likeSum = ''; }

      /* Render the post (your existing includes) */
      if ($userPostStatus == '2') {
         $ApproveNot = $iN->iN_GetAdminNot($userPostOwnerID, $userPostID);
         $aprove_status = $ApproveNot['approve_status'] ?? null;
         $a_not = $iN->iN_SelectedMenuIcon('10').$LANG['waiting_for_approve'];
         $theApproveNot = $ApproveNot['approve_not'] ?? null;
         if ($aprove_status == '2') {
            $a_not = $iN->iN_SelectedMenuIcon('113').$LANG['request_rejected'].' '.$theApproveNot;
         } else if ($aprove_status == '3') {
            $a_not = $iN->iN_SelectedMenuIcon('114').$LANG['declined'].' '.$theApproveNot;
         }
         $waitingApprove = '<div class="waiting_approve flex_">'.$a_not.'</div>';
         if ($logedIn == 0) {
            echo '<div class="i_post_body nonePoint body_'.$userPostID.'" id="'.$userPostID.'" data-last="'.$userPostID.'"></div>';
         } else {
            if ($userID == $userPostOwnerID) {
               if (empty($userPostFile)) { include("textPost.php"); } else { include("ImagePost.php"); }
            } else {
               echo '<div class="i_post_body nonePoint body_'.$userPostID.'" id="'.$userPostID.'" data-last="'.$userPostID.'"></div>';
            }
         }
      } else {
         if (empty($userPostFile)) { include("textPost.php"); } else { include("ImagePost.php"); }
      }

      /* Bump counter and drop Reels every 8 posts (on main feeds only) */
   /* Bump counter and drop Reels every 8 posts (on main feeds only, NOT profile) */
$mfFeedIndex++;

if ($mfFeedIndex % 3 === 0 
    && $page !== 'profile'  // Exclude profile pages
    && in_array($page, [
      'moreposts','friends','allPosts','moreexplore',
      'trendposts','boostedposts','moreboostedposts'
   ], true)) {
     include __DIR__ . '/../../layouts/reels_suggested.php';
      }
   } // end foreach - NOW it's in the right place!


}else if(is_array($postsFromDataProduct) || is_object($postsFromDataProduct)){
   foreach($postsFromDataProduct as $oprod){
      $ProductID = $oprod['pr_id'] ?? null;
      $ProductName = $oprod['pr_name'] ?? null;
      $ProductPrice = $oprod['pr_price'] ?? null;
      $ProductFiles = $oprod['pr_files'] ?? null;
      $ProductOwnerID = $oprod['iuid_fk'] ?? null;
      $productOwnerUserName = $oprod['i_username'] ?? null;
      $productOwnerUserFullName = $oprod['i_user_fullname'] ?? null;
      $userProfileFrame = $oprod['user_frame'] ?? null;
      if($fullnameorusername == 'no'){
         $productOwnerUserFullName = $productOwnerUserName;
      }
      $pprofileAvatar = $iN->iN_UserAvatar($ProductOwnerID, $base_url);
      $ProductSlug = $oprod['pr_name_slug'] ?? null;
      $ProductType = $oprod['product_type'] ?? null;
      $p__style = $ProductType;
      if($ProductType == 'scratch'){
          $ProductType = 'simple_product';
          $p__style = 'scratch';
      }
      $ProductSlotsNumber = $oprod['pr_slots_number'] ?? null;
      $SlugUrl = $base_url.'product/'.$ProductSlug.'_'.$ProductID;
      $trimValue = rtrim($ProductFiles,',');
      $nums = preg_split('/\s*,\s*/', $trimValue);
      $lastFileID = end($nums);
      $pfData = $iN->iN_GetUploadedFileDetails($lastFileID);
      if($pfData){
          $fileUploadID = $pfData['upload_id'] ?? null;
          $fileExtension = $pfData['uploaded_file_ext'] ?? null;
          $filePath = $pfData['uploaded_file_path'] ?? null;
          if ($s3Status == 1) {
              $productDataImage = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
          }else if($WasStatus == '1'){
              $productDataImage = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
          } else if ($digitalOceanStatus == '1') {
              $productDataImage = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $filePath;
          } else {
              $productDataImage = $base_url . $filePath;
          }
      }
      include("products.php");
   }
}else if(is_array($profileFollowers) || is_object($profileFollowers)){
   foreach ($profileFollowers as $flU) {
        $followingUserID = $flU['fr_one'] ?? null;
        $followingUserData = $iN->iN_GetUserDetails($followingUserID);
        $flUUserName = $followingUserData['i_username'] ?? null;
        $flUUserFullName = $followingUserData['i_user_fullname'] ?? null;
        $userProfileFrame = $followingUserData['user_frame'] ?? null;
        $flUUserAvatar = $iN->iN_UserAvatar($followingUserID, $base_url); 
       $getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $followingUserID);
      if ($getFriendStatusBetweenTwoUser == 'flwr') {
         $flwrBtn = 'i_btn_like_item_flw f_p_follow';
         $flwBtnIconText = $iN->iN_SelectedMenuIcon('66') . $LANG['unfollow'];
      } else {
         $flwrBtn = 'i_btn_like_item free_follow';
         $flwBtnIconText = $iN->iN_SelectedMenuIcon('66') . $LANG['follow'];
      } 
   include("followers.php");  
   } 
}else if(is_array($profileFollowing) || is_object($profileFollowing)){
   foreach ($profileFollowing as $flU) {
        $followingUserID = $flU['fr_two'] ?? null;
        $followingID = $flU['fr_id'] ?? null;
        $followingUserData = $iN->iN_GetUserDetails($followingUserID);
        $flUUserName = $followingUserData['i_username'] ?? null;
        $flUUserFullName = $followingUserData['i_user_fullname'] ?? null;
        $flUUserAvatar = $iN->iN_UserAvatar($followingUserID, $base_url); 
       $getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $followingUserID);
      if ($getFriendStatusBetweenTwoUser == 'flwr') {
         $flwrBtn = 'i_btn_like_item_flw f_p_follow';
         $flwBtnIconText = $iN->iN_SelectedMenuIcon('66') . $LANG['unfollow'];
      } else {
         $flwrBtn = 'i_btn_like_item free_follow';
         $flwBtnIconText = $iN->iN_SelectedMenuIcon('66') . $LANG['follow'];
      } 
   include("following.php");  
   } 
}else if(is_array($profileSubscriber) || is_object($profileSubscriber)){
   foreach ($profileSubscriber as $flU) {
        $followingUserID = $flU['fr_one'] ?? null;
        $followingID = $flU['fr_id'] ?? null;
        $followingUserData = $iN->iN_GetUserDetails($followingUserID);
        $flUUserName = $followingUserData['i_username'] ?? null;
        $flUUserFullName = $followingUserData['i_user_fullname'] ?? null;
        $flUUserAvatar = $iN->iN_UserAvatar($followingUserID, $base_url); 
       $getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $followingUserID);
      if ($getFriendStatusBetweenTwoUser == 'flwr') {
         $flwrBtn = 'i_btn_like_item_flw f_p_follow';
         $flwBtnIconText =  $LANG['unfollow'];
      } else if ($getFriendStatusBetweenTwoUser == 'subscriber') {
         $flwrBtn = 'i_btn_unsubscribe';
         $flwBtnIconText =   $LANG['unsubscribe'];
      } else {
         $flwrBtn = 'i_btn_like_item free_follow';
         $flwBtnIconText =   $LANG['follow'];
      }
   include("subscribers.php");   
   } 
} else {
   echo '
    <div class="noPost optional_width">
        <div class="noPostIcon">'.$iN->iN_SelectedMenuIcon('182').'</div>
        <div class="noPostNote">'.$notFoundNot.'</div>
    </div>
   ';
}
?>
