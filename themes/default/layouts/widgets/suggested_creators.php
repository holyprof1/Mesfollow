<?php
if($logedIn != 0){
    $suggestedCreators = $iN->iN_SuggestionCreatorsList($userID,$showingNumberOfSuggestedUser);
}else{
    $suggestedCreators = $iN->iN_SuggestionCreatorsListOut($showingNumberOfSuggestedUser);
}
if($suggestedCreators){?>
    <div class="sp_wrp">
        <div class="suggested_products">
            <div class="i_right_box_header">
            <?php echo iN_HelpSecure($LANG['suggested_creators']);?>
            </div>
            <div class="i_topinoras_wrapper flex_ tabing suggested_flex_flow">
                <?php
                    foreach($suggestedCreators as $sgCreatorData){
                        $sgcreatorUserName = $sgCreatorData['i_username'];
                        $sgCreatorUserfullName = $sgCreatorData['i_user_fullname'];
                        $sgUserGender = $sgCreatorData['user_gender'];
                		if ($sgUserGender == 'male') {
                			$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
                		} else if ($sgUserGender == 'female') {
                			$publisherGender = '<div class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
                		} else if ($sgUserGender == 'couple') {
                			$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
                		}
                        if($fullnameorusername == 'no'){
                            $sgCreatorUserfullName = $sgcreatorUserName;
                        }
                        $sgcreatorUserID = $sgCreatorData['iuid'];
                        $sgCreatorUserAvatar = $iN->iN_UserAvatar($sgcreatorUserID, $base_url);
                        $sgCreatorUserCover = $iN->iN_UserCover($sgcreatorUserID, $base_url);
                        $sgtotalPost = $iN->iN_TotalPosts($sgcreatorUserID);
                        $sgtotalImagePost = $iN->iN_TotalImagePosts($sgcreatorUserID);
                        $sgtotalVideoPosts = $iN->iN_TotalVideoPosts($sgcreatorUserID);
                ?>
                 <div class="i_message_wrpper">
                    <a href="<?php echo $base_url.$sgcreatorUserName;?>" target="blank_" title="<?php echo $sgCreatorUserfullName;?>">
                         <div class="i_message_wrapper transition">
                            <div class="i_message_owner_avatar">
                                <div class="i_message_avatar">
                                    <img src="<?php echo $sgCreatorUserAvatar;?>" alt="<?php echo $sgCreatorUserfullName;?>">
                                </div>
                            </div>
                            <!---->
                            <div class="i_message_info_container">
                                <div class="i_message_owner_name"><?php echo $sgCreatorUserfullName;?><?php echo html_entity_decode($publisherGender); ?></div>
                                <div class="i_message_i">@<?php echo $sgcreatorUserName;?></div>
                            </div>
                            <!---->
                         </div>
                    </a>
                 </div>
                <?php } ?>
            </div>
        </div>
    </div>
<?php }?>