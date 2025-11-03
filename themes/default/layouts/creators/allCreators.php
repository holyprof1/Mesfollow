<div class="creators_container">
  <div class="creator_pate_title"><?php echo iN_HelpSecure($LANG['normal_user'] ?? 'Utilisateur normal');?></div>

    <div class="creators_list_container" id="moreType" data-type="creators" data-r="">
        <?php
            // Get ALL active users (no category filter)
            $lastPostID = isset($_POST['last']) ? $_POST['last'] : '';
            
            // Modified query to get ALL users
            $moreUser = '';
            if (!empty($lastPostID)) {
                $moreUser = " AND iuid < '$lastPostID' ";
            }
            
            $sql = "SELECT * FROM i_users 
                    WHERE uStatus IN('1','3') 
                    {$moreUser}
                    ORDER BY iuid DESC 
                    LIMIT {$scrollLimit}";
            
            $query = mysqli_query($db, $sql);
            $creatorTypeUrl = [];
            while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                $creatorTypeUrl[] = $row;
            }
            
            if (!empty($creatorTypeUrl) && is_array($creatorTypeUrl)) {

                foreach($creatorTypeUrl as $td){
                    $popularuserID = $td['iuid'];
                    $uD = $iN->iN_GetUserDetails($popularuserID);
                    $popularUserAvatar = $iN->iN_UserAvatar($popularuserID, $base_url);
                    $creatorCover = $iN->iN_UserCover($popularuserID, $base_url);
                    $popularUserName = $td['i_username'];
                    $popularUserFullName = $td['i_user_fullname'];
                    $userProfileFrame = isset($td['user_frame']) ? $td['user_frame'] : NULL;
                    if($fullnameorusername == 'no'){
                        $popularUserFullName = $popularUserName;
                    }
                    $uPCategory = isset($uD['profile_category']) ? $uD['profile_category'] : NULL;
                    $totalPost = $iN->iN_TotalPosts($popularuserID);
                    $totalImagePost = $iN->iN_TotalImagePosts($popularuserID);
                    $totalVideoPosts = $iN->iN_TotalVideoPosts($popularuserID);
                    
                    // Category display
                    $pCt = '';
                    if(isset($PROFILE_CATEGORIES[$uPCategory])){
                        $pCt = isset($PROFILE_CATEGORIES[$uPCategory]) ? $PROFILE_CATEGORIES[$uPCategory] : NULL;
                    }else if(isset($PROFILE_SUBCATEGORIES[$uPCategory])){
                        $pCt = isset($PROFILE_SUBCATEGORIES[$uPCategory]) ? $PROFILE_SUBCATEGORIES[$uPCategory] : NULL;
                    }
                    
                    $uCateg = '';
                    if($uPCategory && $pCt){ 
                        $uCateg = '<div class="i_p_cards"><div class="i_creator_category"><a href="'.iN_HelpSecure($base_url).'creators?creator='.$uPCategory.'">'.html_entity_decode($iN->iN_SelectedMenuIcon('65')).$pCt.'</a></div></div>';
                    }
        ?>
        <!---->
        <div class="creator_list_box_wrp mor body_<?php echo iN_HelpSecure($popularuserID);?>" data-last="<?php echo iN_HelpSecure($popularuserID);?>">
            <div class="creator_l_box transition flex_">
                <div class="creator_l_cover" style="<?php echo !empty($creatorCover) && filter_var($creatorCover, FILTER_VALIDATE_URL) ? 'background-image:url('.iN_HelpSecure($creatorCover).');' : ''; ?>"></div>
                <!---->
                <div class="creator_l_avatar_name">
                <div class="creator_avatar_container">
                        <?php if($userProfileFrame){ ?>
                            <div class="frame_out_container_creator"><div class="frame_container_creator"><img src="<?php echo $base_url.$userProfileFrame;?>"></div></div>
                        <?php }?>
                        <a href="<?php echo iN_HelpSecure($base_url).$popularUserName;?>"><div class="creator_avatar"><img src="<?php echo iN_HelpSecure($popularUserAvatar);?>"></div></a>
                    </div>
                    <div class="creator_nm transition truncated"><a href="<?php echo iN_HelpSecure($base_url).$popularUserName;?>"><?php echo iN_HelpSecure($iN->iN_Secure($popularUserFullName));?></a></div>
                    <?php echo $uCateg; ?>
                    <!---->
                    <div class="i_p_items_box_">
                        <div class="i_btn_item_box transition">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('67'));?> <?php echo iN_HelpSecure($totalPost);?>
                        </div>
                        <div class="i_btn_item_box transition">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('68'));?> <?php echo iN_HelpSecure($totalImagePost);?>
                        </div>
                        <div class="i_btn_item_box transition">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('52'));?> <?php echo iN_HelpSecure($totalVideoPosts);?>
                        </div>
                    </div>
                    <!---->
                    <!---->
                    <div class="creator_last_two_post flex_ tabing">
                        <?php
                           $getLatestFivePost = $iN->iN_ExploreUserLatestFivePost($popularuserID);
                           if($getLatestFivePost){
                               foreach($getLatestFivePost as $suggestedData){
                                $userPostID = $suggestedData['post_id'];
                                $userPostFile = $suggestedData['post_file'];
                                $slugData = isset($suggestedData['url_slug']) ? $suggestedData['url_slug'] : NULL;
                                $slugUrl = $base_url.'post/'.$slugData.'_'.$userPostID;
                                $userPostWhoCanSee = $suggestedData['who_can_see'];
                                $trimValue = rtrim($userPostFile,',');
                                $explodeFiles = explode(',', $trimValue);
                                $explodeFiles = array_unique($explodeFiles);
                                $countExplodedFiles = count($explodeFiles);
                                $nums = preg_split('/\s*,\s*/', $trimValue);
                                $lastFileID = end($nums);
                                $getFriendStatusBetweenTwoUser = '';
                                if($logedIn == '1'){
                                    $getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $popularuserID);
                                } 
                                $fileData = $iN->iN_GetUploadedFileDetails($lastFileID);
                                if($fileData){
                                    $fileUploadID = $fileData['upload_id'];
                                    $fileExtension = $fileData['uploaded_file_ext'];
                                    $filePath = $fileData['uploaded_file_path'];
                                    $filePathTumbnail = $fileData['upload_tumbnail_file_path'];
                                    if($filePathTumbnail){
                                        $imageTumbnail = $filePathTumbnail;
                                     }else{
                                        $imageTumbnail = $filePath;
                                     }
                                    if($userPostWhoCanSee != '1' && $logedIn == '1'){
                                        if($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                                            $filePath = $fileData['uploaded_x_file_path'];
                                        }
                                    }else{
                                        $filePath = $fileData['uploaded_x_file_path'];
                                    }
                                    $filePathWithoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filePath);
                                    if($s3Status == 1){
                                        if($filePathTumbnail){
                                            $filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' .$imageTumbnail;
                                        }else{
                                            $filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
                                        }
                                    } else if($digitalOceanStatus == '1'){
                                        if($filePathTumbnail){
                                            $filePathUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/'.$imageTumbnail;
                                        }else{
                                            $filePathUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/'. $filePath;
                                        }
                                    }else if($WasStatus == '1'){
                                        if($filePathTumbnail){
                                            $filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $imageTumbnail;
                                        }else{
                                            $filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $filePath;
                                        }
                                    }else{
                                        if($filePathTumbnail){
                                            $filePathUrl = $base_url . $imageTumbnail;
                                        }else{
                                            $filePathUrl = $base_url . $filePath;
                                        }
                                    }
                                    $videoPlaybutton ='';
                                    if($fileExtension == 'mp4'){
                                        $videoPlaybutton = '<div class="playbutton">'.$iN->iN_SelectedMenuIcon('55').'</div>';
                                        $PathExtension = '.png';
                                        if($s3Status == 1){
                                            $filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePathTumbnail;
                                        }else if($digitalOceanStatus == '1'){
                                            $filePathUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/'. $filePathTumbnail;
                                        }else if($WasStatus == '1'){
                                            $filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/'. $filePathTumbnail;
                                        }else{
                                            $filePathUrl = $base_url . $filePathTumbnail;
                                        }
                                        $fileisVideo = 'data-poster="'.$filePathUrl.'" data-html="#video'.$fileUploadID.'"';
                                    }else{
                                        if($s3Status == 1){
                                            $filePathUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $filePath;
                                        } else if($digitalOceanStatus == '1'){
                                            $filePathUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/'. $filePath;
                                        }else if($WasStatus == '1'){
                                            $filePathUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/'. $filePath;
                                        }else{
                                            $filePathUrl = $base_url.$filePath;
                                        }
                                        $fileisVideo = 'data-src="'.$filePathUrl.'"';
                                    }
                                    $onlySubs = '';
                                    if($userPostWhoCanSee == '1'){
                                        $onlySubs = '';
                                    }else if($userPostWhoCanSee == '2'){
                                        $wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div></div>';
                                    }else if($userPostWhoCanSee == '3'){
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div></div>';
                                    }else if($userPostWhoCanSee == '4'){
                                        $onlySubs = '<div class="onlySubsSuggestion"><div class="onlySubsSuggestionWrapper"><div class="onlySubsSuggestion_icon">'.$iN->iN_SelectedMenuIcon('40').'</div></div></div>';
                                    }
                                } 
                        ?>
                            <div class="creator_last_post_item">
                                <div class="creator_last_post_item-box"  style="background-image: url('<?php echo iN_HelpSecure($filePathUrl);?>');">
                                <a href="<?php echo iN_HelpSecure($slugUrl);?>">
                                    <?php
                                      if($logedIn == '1'){
                                        if($getFriendStatusBetweenTwoUser != 'me' && $getFriendStatusBetweenTwoUser != 'subscriber'){
                                            echo html_entity_decode($onlySubs);
                                        }
                                      }else{
                                        echo html_entity_decode($onlySubs);
                                      }
                                    ?>
                                    <img class="creator_last_post_item-img" src="<?php echo iN_HelpSecure($filePathUrl);?>">
                                </a>
                                </div>
                            </div>
                        <?php  
                               }
                            }
                            ?>
                    </div>
                    <!---->
                </div>
                <!---->
            </div>
        </div>
        <!---->
    <?php  } }else{
        echo '<div class="no_creator_f_wrap flex_ tabing"><div class="no_c_icon">'.$iN->iN_SelectedMenuIcon('54').'</div><div class="n_c_t">'.iN_HelpSecure($LANG['no_users_found'] ?? 'Aucun utilisateur trouvé').'</div></div>';
    } ?>
    </div>
</div>