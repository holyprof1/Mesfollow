<?php
/* ==== Views helpers (safe, drop-in) ==== */
if (!function_exists('mf_db')) { function mf_db($iN,$db){ return isset($iN->db)?$iN->db:($db??null); } }
if (!function_exists('mf_db_is_pdo'))    { function mf_db_is_pdo($dbh){ return $dbh instanceof PDO; } }
if (!function_exists('mf_db_is_mysqli')) { function mf_db_is_mysqli($dbh){ return $dbh instanceof mysqli; } }

if (!function_exists('mf_cols')) {
  function mf_cols($dbh,$table){
    try{
      if (mf_db_is_pdo($dbh)){
        $q=$dbh->query("SHOW COLUMNS FROM `$table`"); if(!$q){return[];}
        return array_map(fn($r)=>$r['Field'],$q->fetchAll(PDO::FETCH_ASSOC));
      } elseif (mf_db_is_mysqli($dbh)){
        $q=$dbh->query("SHOW COLUMNS FROM `$table`"); $out=[];
        if($q){ while($r=$q->fetch_assoc()){ $out[]=$r['Field']; } }
        return $out;
      }
    }catch(Throwable $e){}
    return [];
  }
}

if (!function_exists('mf_k')) { // 1.2K / 3.4M formatter
  function mf_k($n){ $n=(int)$n; if($n>=1000000) return round($n/1000000,1).'M';
                     if($n>=1000) return round($n/1000,1).'K'; return (string)$n; }
}

/* READ current views (supports either i_post_views table OR i_posts.post_views column) */
if (!function_exists('mf_post_views')) {
  function mf_post_views($postId, $iN=null, $db=null){
    static $mode=null;  // detect once per request
    $dbh = mf_db($iN,$db); if(!$dbh || !$postId){ return 0; }

    if ($mode===null){
      $pcols = mf_cols($dbh,'i_posts');
      $mode = count(mf_cols($dbh,'i_post_views'))>0 ? 'table'
            : (in_array('post_views',$pcols) ? 'column' : 'none');
    }

    try{
      if ($mode==='table'){
        $sql="SELECT COUNT(*) c FROM i_post_views WHERE post_id=?";
        if (mf_db_is_pdo($dbh)){ $s=$dbh->prepare($sql); $s->execute([(int)$postId]); return (int)$s->fetchColumn(); }
        $s=$dbh->prepare($sql); $id=(int)$postId; $s->bind_param("i",$id); $s->execute();
        $r=$s->get_result()->fetch_assoc(); $s->close(); return (int)($r['c']??0);
      } elseif ($mode==='column'){
        $sql="SELECT COALESCE(post_views,0) v FROM i_posts WHERE post_id=? LIMIT 1";
        if (mf_db_is_pdo($dbh)){ $s=$dbh->prepare($sql); $s->execute([(int)$postId]); return (int)($s->fetchColumn() ?: 0); }
        $s=$dbh->prepare($sql); $id=(int)$postId; $s->bind_param("i",$id); $s->execute();
        $r=$s->get_result()->fetch_row(); $s->close(); return (int)($r[0]??0);
      }
    }catch(Throwable $e){}
    return 0;
  }
}

/* WRITE one view per request (tries i_post_views INSERT, falls back to i_posts.post_views++) */
if (!function_exists('mf_track_view')) {
  function mf_track_view($postId, $iN=null, $db=null){
    static $seen = [];
    if (!$postId || isset($seen[$postId])) { return; } // only once per request
    $seen[$postId] = 1;

    $dbh = mf_db($iN,$db); if(!$dbh){ return; }

    // Detect capabilities
    $hasViewTable=false; $hasPostViewsCol=false;
    try{
      if (mf_db_is_pdo($dbh)) {
        $hasViewTable   = !!$dbh->query("SHOW TABLES LIKE 'i_post_views'")->fetch();
        $hasPostViewsCol= !!$dbh->query("SHOW COLUMNS FROM i_posts LIKE 'post_views'")->fetch();
      } else {
        $q1=@$dbh->query("SHOW TABLES LIKE 'i_post_views'"); $hasViewTable = $q1 && $q1->num_rows>0;
        $q2=@$dbh->query("SHOW COLUMNS FROM i_posts LIKE 'post_views'"); $hasPostViewsCol = $q2 && $q2->num_rows>0;
      }
    }catch(Throwable $e){}

    // Try INSERT into i_post_views with only post_id (other cols must be nullable/defaulted)
    if ($hasViewTable){
      try{
        if (mf_db_is_pdo($dbh)) {
          $dbh->prepare("INSERT INTO i_post_views (post_id) VALUES (?)")->execute([(int)$postId]);
          return;
        } else {
          $st=$dbh->prepare("INSERT INTO i_post_views (post_id) VALUES (?)");
          $id=(int)$postId; $st->bind_param("i",$id); $st->execute(); $st->close();
          return;
        }
      }catch(Throwable $e){
        // fall through to column mode if table INSERT fails
      }
    }

    // Fallback: increment i_posts.post_views
    if ($hasPostViewsCol){
      try{
        if (mf_db_is_pdo($dbh)) {
          $dbh->prepare("UPDATE i_posts SET post_views=COALESCE(post_views,0)+1 WHERE post_id=?")->execute([(int)$postId]);
        } else {
          $st=$dbh->prepare("UPDATE i_posts SET post_views=COALESCE(post_views,0)+1 WHERE post_id=?");
          $id=(int)$postId; $st->bind_param("i",$id); $st->execute(); $st->close();
        }
      }catch(Throwable $e){}
    }
  }
}

/* VIEW chip HTML */
if (!function_exists('mf_view_chip')) {
  function mf_view_chip($postId, $iN=null, $db=null){
    $v = mf_post_views($postId,$iN,$db);
    if ($v<=0) return ''; // hide when there are no views yet
    $txt = mf_k($v);
    return '
      <div class="mf_view_chip" aria-label="Views">
        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
          <path fill="currentColor" d="M8 5v14l11-7z"></path>
        </svg>
        <span>'.$txt.'</span>
      </div>';
  }
}
?>

<style>
/* chip UI; safe to include more than once */
.i_post_image_swip_wrapper{ position:relative; } /* ensure positioning */
.mf_video_wrap{ position:relative; } /* ensure chip overlays video too */
.mf_view_chip{
  position:absolute; left:8px; bottom:8px;
  display:inline-flex; align-items:center; gap:6px;
  padding:4px 8px; font-weight:700; font-size:12px; line-height:1;
  color:#fff; background:rgba(0,0,0,.55); border-radius:10px;
}
	
	.i_post_image_swip_wrapper,
.mf_video_wrap{ position:relative; overflow:hidden; }

.mf_view_chip{
  position:absolute; left:8px; bottom:8px;
  display:inline-flex; gap:6px; padding:4px 8px; font-weight:700; font-size:12px;
  color:#fff; background:rgba(0,0,0,.55); border-radius:10px;
  z-index:9;            /* <-- keeps it above play overlay */
  pointer-events:none;  /* <-- click-through to video */
}

/* make sure your play overlay isn't higher */
.playbutton{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; z-index:1; }

</style>

<?php recordPostView($userPostID, $db, $logedIn, $userID); ?>

<div class="i_post_body body_<?php echo iN_HelpSecure($userPostID); ?> <?php echo iN_HelpSecure($subPostTop); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>" data-last="<?php echo iN_HelpSecure($userPostID); ?>">
<?php echo html_entity_decode($waitingApprove ?? '');
echo html_entity_decode($pPinStatus ?? ''); ?>
    <!--POST HEADER-->
    <div class="i_post_body_header">
	    <?php
        echo html_entity_decode($planIcon ?? '');
        echo html_entity_decode($premiumPost ?? '');
        ?>

	    <div class="user_post_user_avatar_plus">
	        <?php if($userProfileFrame){ ?>
                <div class="frame_out_container"><div class="frame_container"><img src="<?php echo $base_url.$userProfileFrame;?>"></div></div>
            <?php }?>
            <div class="i_post_user_avatar">
                <img src="<?php echo iN_HelpSecure($userPostOwnerUserAvatar); ?>"/>
                <!---->
                <div class="i_thanks_bubble_cont tip_<?php echo iN_HelpSecure($userPostID); ?>">
                    <div class="i_bubble"><?php echo iN_HelpSecure($userTextForPostTip); ?></div>
                </div>
                <!---->
            </div>
        </div>
        <div class="i_post_i">
           <div class="i_post_username"><a class="truncated" href="<?php echo iN_HelpSecure($base_url) . $userPostOwnerUsername; ?>"><?php echo iN_HelpSecure($userPostOwnerUserFullName); ?><?php echo html_entity_decode($wCanSee); ?><?php echo html_entity_decode($timeStatus);?></a></div>
            <div class="i_post_shared_time"><?php if($userPostWhoCanSee == '4'){echo '<div class="premium_amount_he flex_ tabing">'.html_entity_decode($iN->iN_SelectedMenuIcon('40')).$userPostWantedCredit.'</div>';} ;?><?php echo html_entity_decode($profileCategoryLink);?><a href="<?php echo iN_HelpSecure($base_url) . $userPostOwnerUsername; ?>">@<?php echo iN_HelpSecure($userPostOwnerUsername); ?></a> - <?php echo TimeAgo::ago($crTime, date('Y-m-d H:i:s')); ?></div>
            <div class="i_post_menu">
                <div class="i_post_menu_dot openPostMenu transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('16')); ?>
                    <!--POST MENU-->
                    <div class="i_post_menu_container mnoBox mnoBox<?php echo iN_HelpSecure($userPostID); ?>">
                       <div class="i_post_menu_item_wrapper">
                           <?php if ($logedIn != 0 && ($userPostOwnerID == $userID || $userType == '2')) {?>
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out wcs transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <span><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?></span> <?php echo iN_HelpSecure($LANG['whocanseethis']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out edtp transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27')); ?> <?php echo iN_HelpSecure($LANG['edit_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out pcl transition" id="dc_<?php echo iN_HelpSecure($userPostID); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('31')); ?> <?php echo html_entity_decode($commentStatusText); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out delp transition" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?> <?php echo iN_HelpSecure($LANG['delete_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <?php }?>
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out transition copyUrl" data-clipboard-text="<?php echo $slugUrl; ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('30')); ?> <?php echo iN_HelpSecure($LANG['copy_post_url']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <!--MENU ITEM-->
                           <a class="i_opennewtab" href="<?php echo $slugUrl; ?>" target="blank_">
                           <div class="i_post_menu_item_out transition">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('183')); ?> <?php echo iN_HelpSecure($LANG['open_in_new_tab']); ?>
                           </div>
                           </a>
                           <!--/MENU ITEM-->
                           <?php if ($logedIn != 0 && ($userPostOwnerID != $userID)) {?>
                           <!--MENU ITEM-->
                           <div class="i_post_menu_item_out transition rpp rpp<?php echo iN_HelpSecure($userPostID); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('32')); ?> <?php echo iN_HelpSecure($LANG['report_this_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
                           <?php }?>
						   <div class="arrow"></div>
						   <?php if ($logedIn != 0 && ($userPostOwnerID == $userID)) {?>
						   <!--MENU ITEM-->
                           <div class="i_post_menu_item_out i_pnp transition pbtn_<?php echo iN_HelpSecure($userPostID); ?>" id="<?php echo iN_HelpSecure($userPostID); ?>">
                              <?php echo html_entity_decode($pPinStatusBtn); ?>
                           </div>
                           <!--/MENU ITEM-->
						   <?php }?>
						   <?php if ($logedIn != 0 && ($userPostOwnerID == $userID) && !$checkPostBoosted) {?>
						   <!--MENU ITEM-->
                           <div class="i_post_menu_item_out transition boostThisPost" id="<?php echo iN_HelpSecure($userPostID);?>">
                              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('177')); ?> <?php echo iN_HelpSecure($LANG['boost_this_post']); ?>
                           </div>
                           <!--/MENU ITEM-->
						   <?php }?>
                       </div>
                    </div>
                    <!--/POST MENU-->
                </div>
            </div>
        </div>
    </div>
    <!--/POST HEADER-->
    <?php if (!empty($userPostText)) { ?>
    <!--POST CONTAINER-->
    <div class="i_post_container <?php echo iN_HelpSecure($postStyle); ?>" id="i_post_container_<?php echo iN_HelpSecure($userPostID); ?>">
        <!--POST TEXT-->
        <div class="i_post_text" id="i_post_text_<?php echo iN_HelpSecure($userPostID); ?>">
            <?php
                $pStatus = '1';

                if ($userPostWhoCanSee != '1') {
                    if (
                        $getFriendStatusBetweenTwoUser != 'me' &&
                        $getFriendStatusBetweenTwoUser != 'subscriber' &&
                        $userPostStatus != '2' &&
                        $userPostWhoCanSee == '3'
                    ) {
                        $pStatus = '0';
                    } elseif (
                        $userPostWhoCanSee == '4' &&
                        $getFriendStatusBetweenTwoUser != 'me'
                    ) {
                        if (
                            $checkUserPurchasedThisPost == '0' &&
                            $getFriendStatusBetweenTwoUser != 'subscriber'
                        ) {
                            $pStatus = '0';
                        }
                    } elseif (
                        $userPostWhoCanSee == '2' &&
                        $getFriendStatusBetweenTwoUser != 'me' &&
                        $getFriendStatusBetweenTwoUser != 'flwr'
                    ) {
                        $pStatus = '0';
                    }
                }

                if ($pStatus == '1') {
                    if (!empty($userPostText)) {
                        if (isset($userPostHashTags) && !empty($userPostHashTags)) {
                            echo $urlHighlight->highlightUrls(
                                $iN->sanitize_output(
                                    $iN->iN_RemoveYoutubelink($userPostText),
                                    $base_url
                                )
                            );
                        } else {
                            echo $urlHighlight->highlightUrls(
                                $iN->sanitize_output(
                                    $iN->iN_RemoveYoutubelink($userPostText),
                                    $base_url
                                )
                            );
                        }
                    }

                    $regexUrl = '/\b(https?|ftp|file):\/\/[\-A-Z0-9+&@#\/\%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
                    $totalUrl = preg_match_all($regexUrl, $userPostText, $matches);

                    $urls = $matches[0];

                    foreach ($urls as $url) {
                        $em = new Url_Expand($url);
                        $site = $em->get_site();

                        if ($site != '') {
                            $code = $em->get_iframe();
                            if ($code == '') {
                                $code = $em->get_embed();
                                if ($code == '') {
                                    $codesrc = $em->get_thumb('medium');
                                }
                            }
                            echo $code;
                        }
                    }
                }
            ?>
        </div>
        <!--/POST TEXT-->
    </div>
    <!--/POST CONTAINER-->
    <?php } ?>
    <!--POST IMAGES-->
    <div class="i_post_u_images <?php echo iN_HelpSecure($loginFormClass); ?>">
        <?php
            if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostWhoCanSee == '3') {
            	echo html_entity_decode($onlySubs);
            } else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
            	if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
            		echo html_entity_decode($onlySubs);
            	}
            } else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
            	echo html_entity_decode($onlySubs);
            }
            $trimValue = rtrim($userPostFile, ',');
            $explodeFiles = explode(',', $trimValue);
            $explodeFiles = array_unique($explodeFiles);
            $countExplodedFiles = $iN->iN_CheckCountFile($userPostFile);
            $container = '';
            if ($countExplodedFiles == 1) {
            	$container = 'i_image_one';
            } else if ($countExplodedFiles == 2) {
            	$container = 'i_image_two';
            } else if ($countExplodedFiles == 3) {
            	$container = 'i_image_three';
            } else if ($countExplodedFiles == 4) {
            	$container = 'i_image_four';
            } else if ($countExplodedFiles >= 5) {
            	$container = 'i_image_five';
            }

            // hidden/lightbox video html containers (poster below uses data-html reference)
            foreach ($explodeFiles as $explodeVideoFile) {
            	$VideofileData = $iN->iN_GetUploadedFileDetails($explodeVideoFile);
            	if ($VideofileData) {
            		$VideofileUploadID = $VideofileData['upload_id'] ?? null;
            		$VideofileExtension = $VideofileData['uploaded_file_ext'] ?? null;
            		$VideofilePath = $VideofileData['uploaded_file_path'] ?? null;
            		$videoFileTumbnailHere = $VideofileData['upload_tumbnail_file_path'] ?? null;
            		if ($userPostWhoCanSee != '1') {
            			if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3') {
            				$VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
            			} else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
            				if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
            					$VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
            				}
            			} else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
            				$VideofilePath = $VideofileData['uploaded_x_file_path'] ?? null;
            			}
            		}
            		$VideofilePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $VideofilePath);
            		if (strtolower((string)$VideofileExtension) === 'mp4') {
            			$VideoPathExtension = '.jpg';
            			if ($s3Status == 1) {
            				$VideofilePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $VideofilePath;
            				$VideofileTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $VideofilePathWithoutExt . $VideoPathExtension;
            			}else if($WasStatus == 1){
            				$VideofilePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $VideofilePath;
            				$VideofileTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $VideofilePathWithoutExt . $VideoPathExtension;
            			} else if ($digitalOceanStatus == '1') {
            				$VideofilePathUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $VideofilePath;
            				$VideofileTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $VideofilePathWithoutExt . $VideoPathExtension;
            			} else {
            				$VideofilePathUrl = $base_url . $VideofilePath;
            				$VideofileTumbnailUrl = $base_url . $VideofilePathWithoutExt . $VideoPathExtension; // FIX
            			}
            			echo '
  <div class="nonePoint mf_video_wrap" id="video' . $VideofileUploadID . '">
    <video class="lg-video-object lg-html5 video-js vjs-default-skin" controls onended="videoEnded()">
      <source src="' . $VideofilePathUrl . '" type="video/mp4">
      Your browser does not support HTML5 video.
    </video>
    ' . mf_view_chip($userPostID, $iN, $db) . '
  </div>
';
            		}
            	}
            }

      $isCarousel = ($countExplodedFiles > 1);
echo '<div class="' . $container . ($isCarousel ? ' mf-snap' : '') . '" id="lightgallery' . $userPostID . '">';

        foreach ($explodeFiles as $dataFile) {
        	$fileData = $iN->iN_GetUploadedFileDetails($dataFile);
        	if ($fileData) {
        		$fileUploadID = $fileData['upload_id'] ?? null;
        		$fileExtension = $fileData['uploaded_file_ext'] ?? null;
        		$filePath = $fileData['uploaded_file_path'] ?? null;
        		$filePathTumbnail = $fileData['upload_tumbnail_file_path'] ?? null;
        		if ($filePathTumbnail) {
        			$imageTumbnail = $filePathTumbnail;
        		} else {
        			$imageTumbnail = $filePath;
        		}
        		if ($userPostWhoCanSee != '1') {
        			if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3') {
        				$filePath = $fileData['uploaded_x_file_path'];
        			} else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
        				if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
        					$filePath = $fileData['uploaded_x_file_path'] ?? NULL;
        				} else {
        					$filePath = $fileData['uploaded_file_path'] ?? NULL;
        				}
        			} else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
        				$filePath = $fileData['uploaded_x_file_path'] ?? NULL;
        			} else {
        				if ($getFriendStatusBetweenTwoUser == 'me') {
        					$filePath = $fileData['uploaded_file_path'] ?? NULL;
        				} else {
        					if ($getFriendStatusBetweenTwoUser == 'subscriber' && $userPostWhoCanSee == '3') {
        						$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					} else {
        						if ($getFriendStatusBetweenTwoUser == 'flwr' || $getFriendStatusBetweenTwoUser == 'subscriber') {
        							$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        						} else {
        							$filePath = $fileData['uploaded_x_file_path'] ?? NULL;
        						}
        					}
        				}
        			}
        		} else {
        			$filePath = $fileData['uploaded_file_path'];
        		}
        		$filePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $filePath);
        		if ($s3Status == 1) {
        			if ($filePathTumbnail) {
        				$filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $imageTumbnail;
        			} else {
        				$filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
        			}
        		}else if($WasStatus == 1){
        			if ($filePathTumbnail) {
        				$filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $imageTumbnail;
        			} else {
        				$filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
        			}
        		} else if ($digitalOceanStatus == '1') {
        			if ($filePathTumbnail) {
        				$filePathUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $imageTumbnail;
        			} else {
        				$filePathUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $filePath;
        			}
        		} else {
        			if ($filePathTumbnail) {
        				$filePathUrl = $base_url . $filePath;
        			} else {
        				$filePathUrl = $base_url . $filePath;
        			}
        		}

        		$videoPlaybutton = '';
        		if (strtolower((string)$fileExtension) === 'mp4') {
        			$videoPlaybutton = '<div class="playbutton">' . $iN->iN_SelectedMenuIcon('55') . '</div>';
        			$PathExtension = '.jpg';
        			if ($s3Status == 1) {
        				if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $filePath);
        				} else if ($getFriendStatusBetweenTwoUser == 'me') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $filePath);
        				} else {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $filePath);
        				}
        				if ($ffmpegStatus == '1') {
        					$filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
        					$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
        				} else {
        					if ($s3Status == 1) {
        						$filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
        						$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
        					} else {
        						$filePathUrl = $base_url . $filePathTumbnail;
        						$filePathTumbnailUrl = $base_url . $fileData['upload_tumbnail_file_path'];
        					}
        				}
        			}else if($WasStatus == 1){
        				if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $filePath);
        				} else if ($getFriendStatusBetweenTwoUser == 'me') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $filePath);
        				} else {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        					$filePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $filePath);
        				}
        				if ($ffmpegStatus == '1') {
        					$filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
        					$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
        				} else {
        					if ($WasStatus == 1) {
        						$filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
        						$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
        					} else {
        						$filePathUrl = $base_url . $filePathTumbnail;
        						$filePathTumbnailUrl = $base_url . $fileData['upload_tumbnail_file_path'];
        					}
        				}
        			} else if ($digitalOceanStatus == '1') {
        				if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
        					$filePath = $fileData['uploaded_x_file_path'] ?? NULL;
        				} else if ($getFriendStatusBetweenTwoUser == 'me') {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        				} else {
        					$filePath = $fileData['upload_tumbnail_file_path'] ?? NULL;
        				}
        				if ($ffmpegStatus == '1') {
        					$filePathUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $filePath;
        					$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $filePath;
        				} else {
        					if ($digitalOceanStatus == '1') {
        						$filePathUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $filePath;
        						$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $filePath;
        					} else {
        						$filePathUrl = $base_url . $filePathTumbnail;
        						$filePathTumbnailUrl = $base_url . $filePath;
        					}
        				}
        			} else {
        				if($userPostWhoCanSee == '3' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber'){
        				   $filePathWithoutExt = preg_replace('/\.[^.\s]{3,4}$/', '', $filePath);
                           $filePathUrl = $base_url . $filePathWithoutExt . $PathExtension;
        				   $filePathTumbnailUrl = $base_url . $filePathWithoutExt . $PathExtension;
        				}else{
        					$filePathUrl = $base_url . $fileData['upload_tumbnail_file_path'];
        					$filePathTumbnailUrl = $base_url . $fileData['upload_tumbnail_file_path'];
        				}
        			}
        			$fileisVideo = 'data-poster="' . $filePathUrl . '" data-html="#video' . $fileUploadID . '"';
        		} else {
        			/* images */
        			if ($s3Status == 1) {
        				$filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
        				$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $fileData['uploaded_file_path'];
        			}else if($WasStatus == 1){
        				$filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
        				$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $fileData['uploaded_file_path'];
        			} else if ($digitalOceanStatus == '1') {
        				$filePathUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $filePath;
        				$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $fileData['uploaded_file_path'];
        			} else {
        				$filePathUrl = $base_url . $filePath;
        				$filePathTumbnailUrl = $base_url . $fileData['uploaded_file_path'];
        			}
        			if (($userPostWhoCanSee == '3' || $userPostWhoCanSee == '4' || $userPostWhoCanSee == '2') && $getFriendStatusBetweenTwoUser != 'me' && $checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'flwr') {
        				if ($s3Status == 1) {
        					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
        						$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $fileData['uploaded_file_path'];
        					} else {
        						$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $fileData['uploaded_x_file_path'];
        					}
        				}else if($WasStatus == '1'){
        					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
        						$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $fileData['uploaded_file_path'];
        					} else {
        						$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $fileData['uploaded_x_file_path'];
        					}
        				} else if ($digitalOceanStatus == '1') {
        					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
        						$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $fileData['uploaded_file_path'];
        					} else {
        						$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $fileData['uploaded_x_file_path'];
        					}
        				} else {
        					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
        						$filePathTumbnailUrl = $base_url . $fileData['uploaded_file_path'];
        					} else {
        						$filePathTumbnailUrl = $base_url . $fileData['uploaded_x_file_path'];
        					}
        				}
        			} else {
        				if ($s3Status == 1) {
        					$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $fileData['uploaded_file_path'];
        				}else if ($WasStatus == 1) {
        					$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $fileData['uploaded_file_path'];
        				} else if ($digitalOceanStatus == '1') {
        					$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $fileData['uploaded_file_path'];
        				} else {
        					$filePathTumbnailUrl = $base_url . $filePath;
        				}
        			}
        			$fileisVideo = 'data-src="' . $filePathTumbnailUrl . '"';
        		}
        		?>
        		<?php if($fileExtension != 'mp3'){?>
                   <div class="i_post_image_swip_wrapper" data-bg="<?php echo iN_HelpSecure($filePathUrl); ?>" <?php echo html_entity_decode($fileisVideo); ?>>
                      <?php echo html_entity_decode($videoPlaybutton); ?>
                      <img class="i_p_image" src="<?php echo iN_HelpSecure($filePathUrl); ?>">
                        <?php if (strtolower((string)$fileExtension) === 'mp4') {
         echo mf_view_chip($userPostID, $iN, $db);
       } ?>

                   </div>
        		<?php }?>
       <?php
  } } // close: if ($fileData) AND foreach ($explodeFiles as $dataFile)

  echo '</div>'; // close #lightgallery{postID}

  if (!empty($isCarousel)) {
    echo '
      <div class="mf-navs" data-pid="'.$userPostID.'">
        <button class="mf-prev" type="button" aria-label="Previous">&#10094;</button>
        <div class="mf-dots" aria-label="Slide dots"></div>
        <button class="mf-next" type="button" aria-label="Next">&#10095;</button>
      </div>
    ';
  }
?>

    </div>
    <!--POST IMAGES-->
	<?php
echo '<div class="myaudio">';
foreach ($explodeFiles as $dataFile) {
	$fileAudioData = $iN->iN_GetUploadedMp3FileDetails($dataFile);
	if($fileAudioData){

		$fileUploadID = $fileAudioData['upload_id'] ?? null;
		$fileExtension = $fileAudioData['uploaded_file_ext'] ?? null;
		$filePath = $fileAudioData['uploaded_file_path'] ?? null;
		$filePathTumbnail = $fileAudioData['upload_tumbnail_file_path'] ?? null;

		if ($userPostWhoCanSee != '1') {
			if ($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber' && $userPostStatus != '2' && $userPostWhoCanSee == '3') {
				$filePath = $fileAudioData['uploaded_x_file_path'] ?? null;
			} else if ($userPostWhoCanSee == '4' && $getFriendStatusBetweenTwoUser != 'me') {
				if ($checkUserPurchasedThisPost == '0' && $getFriendStatusBetweenTwoUser != 'subscriber') {
					$filePath = $fileAudioData['uploaded_x_file_path'] ?? null;
				} else {
					$filePath = $fileAudioData['uploaded_file_path'] ?? null;
				}
			} else if ($userPostWhoCanSee == '2' && $getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'flwr' && $getFriendStatusBetweenTwoUser != 'subscriber') {
				$filePath = $fileAudioData['uploaded_x_file_path'] ?? null;
			} else {
				if ($getFriendStatusBetweenTwoUser == 'me') {
					$filePath = $fileAudioData['uploaded_file_path'] ?? null;
				} else {
					if ($getFriendStatusBetweenTwoUser == 'subscriber' && $userPostWhoCanSee == '3') {
						$filePath = $fileAudioData['upload_tumbnail_file_path'] ?? null;
					} else {
						if ($getFriendStatusBetweenTwoUser == 'flwr' || $getFriendStatusBetweenTwoUser == 'subscriber') {
							$filePath = $fileAudioData['upload_tumbnail_file_path'] ?? null;
						} else {
							$filePath = $fileAudioData['uploaded_x_file_path'] ?? null;
						}
					}
				}
			}
		} else {
			$filePath = $fileAudioData['uploaded_file_path'] ?? null;
		}
		if($fileExtension == 'mp3'){
			if ($s3Status == 1) {
				$filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
				$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $fileAudioData['uploaded_file_path'];
			}else if($WasStatus == 1){
				$filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
				$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $fileAudioData['uploaded_file_path'];
			} else if ($digitalOceanStatus == '1') {
				$filePathUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $filePath;
				$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $fileAudioData['uploaded_file_path'];
			} else {
				$filePathUrl = $base_url . $filePath;
				$filePathTumbnailUrl = $base_url . $fileAudioData['uploaded_file_path'];
			}
			$audShowType = '<audio  crossorigin="" preload="none"><source src="'.iN_HelpSecure($filePathUrl).'" type="audio/mp3" /></audio>';
			if (($userPostWhoCanSee == '3' || $userPostWhoCanSee == '4' || $userPostWhoCanSee == '2') && $getFriendStatusBetweenTwoUser != 'me' && $checkUserPurchasedThisPost == '0') {
				if ($s3Status == 1) {
					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
						$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $fileAudioData['uploaded_file_path'];
						$audShowType = '<audio  crossorigin="" preload="none"><source src="'.iN_HelpSecure($filePathUrl).'" type="audio/mp3" /></audio>';
					} else {
						$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $fileAudioData['uploaded_x_file_path'];
						$audShowType = '<img class="i_p_image plus_opacity" src="'.$filePathTumbnailUrl.'">';
					}
				}else if($WasStatus == 1){
					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
						$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $fileAudioData['uploaded_file_path'];
						$audShowType = '<audio  crossorigin="" preload="none"><source src="'.iN_HelpSecure($filePathUrl).'" type="audio/mp3" /></audio>';
					} else {
						$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $fileAudioData['uploaded_x_file_path'];
						$audShowType = '<img class="i_p_image plus_opacity" src="'.$filePathTumbnailUrl.'">';
					}
				} else if ($digitalOceanStatus == '1') {
					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
						$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $fileAudioData['uploaded_file_path'];
						$audShowType = '<audio crossorigin="" preload="none"><source src="'.iN_HelpSecure($filePathUrl).'" type="audio/mp3" /></audio>';
					} else {
						$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $fileAudioData['uploaded_x_file_path'];
						$audShowType = '<img class="i_p_image plus_opacity" src="'.$filePathTumbnailUrl.'">';
					}
				} else {
					if ($getFriendStatusBetweenTwoUser == 'subscriber') {
						$filePathTumbnailUrl = $base_url . $fileAudioData['uploaded_file_path'];
						$audShowType = '<audio crossorigin="" preload="none"><source src="'.iN_HelpSecure($filePathUrl).'" type="audio/mp3" /></audio>';
					} else {
						$filePathTumbnailUrl = $base_url . $fileAudioData['uploaded_x_file_path'];
						$audShowType = '<img class="i_p_image plus_opacity" src="'.$filePathTumbnailUrl.'">';
					}
				}
			} else {
				if ($s3Status == 1) {
					$filePathTumbnailUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $fileAudioData['uploaded_file_path'];
				}else if($WasStatus == 1){
					$filePathTumbnailUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $fileAudioData['uploaded_file_path'];
				} else if ($digitalOceanStatus == '1') {
					$filePathTumbnailUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $fileAudioData['uploaded_file_path'];
				} else {
					$filePathTumbnailUrl = $base_url . $filePath;
				}
			}
			$fileisVideo = 'data-src="' . $filePathTumbnailUrl . '"';
		}?>
                <?php if($fileExtension == 'mp3'){?>
					<div class="i_post_image_swip_wrappera" <?php echo html_entity_decode($fileisVideo); ?>>
						<div id="play_po_<?php echo iN_HelpSecure($fileUploadID);?>" class="green-audio-player">
							<?php echo html_entity_decode($audShowType);?>
						</div>
				    </div>
				<?php } ?>
	<?php }
}
echo '</div>';
?>
    <!--POST LIKE/COMMENT/SHARE/SOCIAL SHARE/SAVE BUTTONS-->

	
	<div class="i_post_footer" id="pf_l_<?php echo iN_HelpSecure($userPostID); ?>">

    <div class="i_post_footer_item">
        <div class="i_post_item_btn transition <?php echo iN_HelpSecure($likeClass); ?> <?php echo iN_HelpSecure($loginFormClass); ?>" id="p_l_<?php echo iN_HelpSecure($userPostID); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($likeIcon); ?></div>
        <?php if (!empty($likeSum)) { ?>
            <div class="lp_sum flex_ tabing show-likers" id="lp_sum_<?php echo iN_HelpSecure($userPostID); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo iN_HelpSecure($likeSum); ?></div>
        <?php } ?>
    </div>

    <?php if ($logedIn != 0 && $userPostOwnerID != $userID) { ?>
        <div class="i_post_footer_item">
            <div class="i_post_item_btn transition in_tips flex_ tabing <?php echo iN_HelpSecure($loginFormClass); ?>" data-id="<?php echo iN_HelpSecure($userPostOwnerID); ?>" data-ppid="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('144')); ?></div>
        </div>
    <?php } ?>

    <div class="i_post_footer_item">
        <div class="i_post_item_btn transition in_comment open-post-modal" data-id="<?php echo iN_HelpSecure($userPostID); ?>">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('20')); ?>
        </div>
        <?php if (!empty($commentCount) && $commentCount > 0) { ?>
            <div class="lp_sum flex_ tabing open-post-modal" data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                <?php echo iN_HelpSecure($commentCount); ?>
            </div>
        <?php } ?>
    </div>

    <?php
// Use the same “target id” logic as text posts: if this card is a share,
// count against the ORIGINAL post; otherwise count this post.
$reshareTargetId = isset($__mf_reshare_target_id) && $__mf_reshare_target_id
                   ? (int)$__mf_reshare_target_id
                   : ((isset($userPostSharedID) && $userPostSharedID) ? (int)$userPostSharedID : (int)$userPostID);

// Count computed in htmlPosts.php (batch), fallback to 0.
$reshareCount = isset($__mf_reshare_count) ? (int)$__mf_reshare_count : 0;

// Render empty text when 0 (so UI shows nothing instead of "0")
$reshareText = $reshareCount > 0 ? number_format($reshareCount) : '';
?>
<div class="i_post_footer_item">
  <div class="i_post_item_btn transition in_share <?php echo iN_HelpSecure($loginFormClass); ?>"
       id="share_<?php echo iN_HelpSecure($reshareTargetId); ?>"
       data-id="<?php echo iN_HelpSecure($reshareTargetId); ?>">
    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('19')); ?>
  </div>

  <!-- Always present so JS can bump it; empty when zero -->
  <div class="lp_sum flex_ tabing"
       id="rsc_<?php echo iN_HelpSecure($reshareTargetId); ?>">
    <?php echo iN_HelpSecure($reshareText); ?>
  </div>
</div>


    <div class="i_post_footer_item">
        <div class="i_post_item_btn transition in_social_share openShareMenu" id="<?php echo iN_HelpSecure($userPostID); ?>">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('21')); ?>
            <div class="i_share_this_post mnsBox mnsBox<?php echo iN_HelpSecure($userPostID); ?>">
                <div class="i_share_menu_wrapper">
                    <div class="i_post_menu_item_out transition share-btn" data-social="facebook" data-url="<?php echo iN_HelpSecure($slugUrl); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('33')); ?> <?php echo iN_HelpSecure($LANG['share_on_facebook']); ?>
                    </div>
                    <div class="i_post_menu_item_out transition share-btn" data-social="twitter" data-url="<?php echo iN_HelpSecure($slugUrl); ?>" data-id="<?php echo iN_HelpSecure($userPostID); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('34')); ?> <?php echo iN_HelpSecure($LANG['share_on_twitter']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="i_post_footer_item">
        <?php
        // Calculates the save count for this specific post
        $savedCount = $iN->iN_CountSavedPosts($userPostID);
        ?>
        <div class="i_post_item_btn transition svp in_save_<?php echo iN_HelpSecure($userPostID); ?> in_save" id="<?php echo iN_HelpSecure($userPostID); ?>"><?php echo html_entity_decode($pSaveStatusBtn); ?></div>
        <?php if (!empty($savedCount) && $savedCount > 0) { ?>
            <div class="lp_sum flex_ tabing">
                <?php echo iN_HelpSecure($savedCount); ?>
            </div>
        <?php } ?>
    </div>

</div>
	
	
	
    <?php if(isset($userID)){if($checkPostBoosted && ($userPostOwnerID == $userID)){
        $userIP = $iN->iN_GetIPAddress();
        if($userID != $boostPostOwnerID){
            $iN->iN_BoostPostSeenCounter($userID, $boostID, $userIP);
        }
    ?>
    <!--Post BOOST Footer-->
	<div class="i_post_footer_boost bstatistick_<?php echo iN_HelpSecure($boostID);?>">
	  <!---->
	  <div class="show_hide_statistic">
	      <div class="stat_icon flex_ tabing b_p_p_<?php echo iN_HelpSecure($boostID);?>" id="<?php echo iN_HelpSecure($boostID);?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('174')); ?></div>
	      <div class="stat_icona flex_ tabing b_p_p_<?php echo iN_HelpSecure($boostID);?>" id="<?php echo iN_HelpSecure($boostID);?>"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('10')); ?></div>
	  </div>
	  <!---->
      <div class="i_post_footer_boost_item">
		<div class="ipf_item"><?php echo iN_HelpSecure($LANG['status']);?></div>
		<div class="ipf_item">
		    <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow" for="boost_s_<?php echo iN_HelpSecure($boostID);?>">
                    <input type="checkbox" name="boost_s_<?php echo iN_HelpSecure($boostID);?>" data-id="<?php echo iN_HelpSecure($boostID);?>" id="boost_s_<?php echo iN_HelpSecure($boostID);?>" class="boosStat" <?php echo iN_HelpSecure($boostStatus) == 'yes' ? 'checked="checked"' : '';?> value="<?php echo iN_HelpSecure($boostStatus) == 'yes' ? 'no' : 'yes';?>">
                    <span class="el-switch-style"></span>
                </label>
            </div>
		</div>
	  </div>
	  <div class="i_post_footer_boost_item">
	    <div class="ipf_item flex_ justify-content-align-items-center">
            <div class="ipf_item_title flex_ justify-content-align-items-center"><?php echo iN_HelpSecure($LANG['number_of_people_show']);?></div>
            <div class="ipf_item_title flex_ justify-content-align-items-center"><?php echo iN_HelpSecure($LANG['view_viewed']);?></div>
        </div>
		<div class="ipf_item flex_ justify-content-align-items-center">
            <div class="ipf_item_title flex_ justify-content-align-items-center bigText"><?php echo iN_HelpSecure($viewCount);?></div>
            <div class="ipf_item_title flex_ justify-content-align-items-center bigText"><?php echo iN_HelpSecure($iN->iN_CountSeenBoostedPostbyID($userPostOwnerID,$boostID));?></div>
        </div>
	  </div>
	</div>
	<!--/Post BOOST Footer-->
    <?php }}?>
    <?php echo html_entity_decode($TotallyPostComment); ?>
    <!--COMMENT FORM COMMENTS-->
    <div class="i_post_comments_wrapper">
        <div class="i_post_comments_box">
            <!--USER COMMENTS-->
            <div class="i_user_comments" name="i_user_comments_<?php echo iN_HelpSecure($userPostID); ?>" id="i_user_comments_<?php echo iN_HelpSecure($userPostID); ?>">
            <?php
        if ($getUserComments && $logedIn == 1) {
        	foreach ($getUserComments as $comment) {
        		$commentID = $comment['com_id'] ?? null;
        		$commentedUserID = $comment['comment_uid_fk'] ?? null;
        		$Usercomment = $comment['comment'] ?? null;
        		$commentTime = $comment['comment_time'] ?? null;
        		$corTime = date('Y-m-d H:i:s', $commentTime);
        		$commentFile = $comment['comment_file'] ?? null;
        		$stickerUrl = $comment['sticker_url'] ?? null;
        		$gifUrl = $comment['gif_url'] ?? null;
        		$commentedUserIDFk = $comment['iuid'] ?? null;
        		$commentedUserName = $comment['i_username'] ?? null;
        		$commentedUserFullName = $comment['i_user_fullname'] ?? null;
                $commentUserFrame = $comment['user_frame'] ?? null;
        		if($fullnameorusername == 'no'){
        			$commentedUserFullName = $commentedUserName;
        		}
        		$checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
                $cUType = '';
                if($checkUserIsCreator){
                    $cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
                }
        		$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
        		$commentedUserGender = $comment['user_gender'] ?? null;
        		if ($commentedUserGender == 'male') {
        			$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
        		} else if ($commentedUserGender == 'female') {
        			$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
        		} else if ($commentedUserGender == 'couple') {
        			$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
        		}
        		$commentedUserLastLogin = $comment['last_login_time'] ?? null;
        		$commentedUserVerifyStatus = $comment['user_verified_status'] ?? null;
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
        		include "comments.php";
        	}
        }
        ?>
            </div>
            <!--/USER COMMENTS-->
            <?php
                if ($logedIn != '0') {
                    if ($userPostCommentAvailableStatus === '1') {
                        include 'comment.php';
                    } elseif ($userPostCommentAvailableStatus === '0') {
                        if ($userType === '2' || $userPostOwnerID === $userID) {
                            include 'comment.php';
                        } else {
                            echo '
                                <div class="i_comment_form">
                                    <div class="need_login">' . iN_HelpSecure($LANG['comments_limited_for_this_post']) . '</div>
                                </div>';
                        }
                    }
                } elseif ($logedIn === '0') {
                    ?>
                    <div class="i_comment_form">
                        <div class="need_login"><?php echo iN_HelpSecure($LANG['must_login_for_comment']); ?></div>
                    </div>
                    <?php
                }
            ?>
        </div>
    </div>
    <!--/COMMENT FORM COMMENTS-->
	
<script>
(function(){
  var initialized = {}; // Track which carousels we've already set up

  function initOne(pid){
    // Prevent duplicate initialization
    if(initialized[pid]) return;
    initialized[pid] = true;

    var gal = document.getElementById('lightgallery'+pid);
    if(!gal || !gal.classList.contains('mf-snap')) return;
    
    var nav = document.querySelector('.mf-navs[data-pid="'+pid+'"]');
    if(!nav) return;

    var prev = nav.querySelector('.mf-prev');
    var next = nav.querySelector('.mf-next');
    var dotsWrap = nav.querySelector('.mf-dots');
    
    var slides = Array.from(gal.querySelectorAll('.i_post_image_swip_wrapper'));
    if(slides.length <= 1) return;

    // Clear any existing dots first
    dotsWrap.innerHTML = '';

    // Build dots (one per slide)
    slides.forEach(function(_, i){
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', 'Go to slide '+(i+1));
      b.addEventListener('click', function(){ goTo(i); });
      dotsWrap.appendChild(b);
    });

    var idx = 0;
    function width(){ return gal.clientWidth; }

    function update(){
      var buttons = dotsWrap.querySelectorAll('button');
      buttons.forEach(function(b,i){
        b.setAttribute('aria-current', i===idx ? 'true' : 'false');
      });
      if(prev) prev.disabled = (idx===0);
      if(next) next.disabled = (idx===slides.length-1);
    }

    function goTo(i){
      idx = Math.max(0, Math.min(slides.length-1, i));
      gal.scrollTo({ left: idx * width(), behavior: 'smooth' });
      update();
    }

    // Click handlers
    if(prev) prev.addEventListener('click', function(){ goTo(idx-1); });
    if(next) next.addEventListener('click', function(){ goTo(idx+1); });

    // Sync when user swipes
    var ticking = false;
    gal.addEventListener('scroll', function(){
      if(ticking) return;
      requestAnimationFrame(function(){
        var i = Math.round(gal.scrollLeft / width());
        if (i !== idx){ idx = i; update(); }
        ticking = false;
      });
      ticking = true;
    });

    // Keep position on resize
    window.addEventListener('resize', function(){ goTo(idx); });

    // Initialize
    update();
  }

  // Initialize all carousels on page load
  document.querySelectorAll('.mf-snap[id^="lightgallery"]').forEach(function(el){
    var pid = el.id.replace('lightgallery','');
    initOne(pid);
  });
})();
</script>
<script>
// Keep videos playing when scrolling in modal
$(document).on('scroll', '.sliding-panel', function() {
    // Find all videos in the modal
    var videos = $('.sliding-panel video');
    
    videos.each(function() {
        var video = this;
        
        // If video was playing, keep it playing
        if (!video.paused) {
            video.play();
        }
    });
});

// When modal opens, start video and keep it playing
$(document).on('click', '.open-post-modal', function(e) {
    setTimeout(function() {
        var video = $('.sliding-panel video')[0];
        
        if (video) {
            // Play video
            video.play();
            
            // Keep playing even when scrolling
            video.addEventListener('pause', function() {
                if (!video.ended) {
                    video.play();
                }
            });
        }
    }, 500); // Wait for modal to load
});
</script>
</div>
