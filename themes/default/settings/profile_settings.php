<?php
/* themes/default/settings/profile_settings.php */
?>
<style>
/* Only [Edit] links in Profile Settings (scoped to this page) */
.settings_main_wrapper .i_settings_item_title_for .edit-link{
  color:#e11d48;
  font-weight:600;
  text-decoration:none;
  margin-left:6px;
}
.settings_main_wrapper .i_settings_item_title_for .edit-link:hover{
  text-decoration:underline;
}
</style>

<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in">
    <div class="i_settings_wrapper_title"><?php echo iN_HelpSecure($LANG['my_profile']);?></div>

    <form enctype="multipart/form-data" method="post" id="myProfileForm">
      <div class="i_settings_wrapper_items">
        <!-- Profile pictures row -->
        <div class="i_settings_wrapper_item">
          <input type="hidden" name="f" value="editMyPage">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['profile_pictures']);?></div>
          <div class="i_settings_item_title_for modify_avatar_cover">
            <?php echo iN_HelpSecure($LANG['modify_avatar_cover']);?>
            <a href="#" class="editAvatarCover edit-link"><?php echo iN_HelpSecure($LANG['_edit_']);?></a>
          </div>
        </div>

        <!-- Email row -->
        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['email_address']);?></div>
          <div class="i_settings_item_title_for account_email">
            <span class="email-text"><?php echo filter_var($userEmail, FILTER_SANITIZE_EMAIL);?></span>
            <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=email_settings" class="edit-link"><?php echo iN_HelpSecure($LANG['_edit_']);?></a>
          </div>
        </div>

        <!-- Full name -->
        <div class="i_settings_wrapper_item <?php echo iN_HelpSecure($fullnameorusername) == 'no' ? "nonePoint" : '';?>">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['full_name']);?></div>
          <div class="i_settings_item_title_for">
            <input type="text" name="flname" class="flnm" value="<?php echo iN_HelpSecure($userFullName);?>">
          </div>
        </div>

        <!-- Username -->
        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['username']);?></div>
          <div class="i_settings_item_title_for">
            <input type="text" name="uname" id="uname" class="flnm" value="<?php echo iN_HelpSecure($userName);?>">
            <div class="box_not">
              <?php echo iN_HelpSecure($LANG['your_profile_url']);?> <?php echo iN_HelpSecure($base_url);?>
              <span id="reUnm"><?php echo iN_HelpSecure($userName);?></span>
            </div>
            <div class="box_not warning_username"><?php echo iN_HelpSecure($LANG['username_already_in_use_warning']);?></div>
            <div class="box_not invalid_username"><?php echo iN_HelpSecure($LANG['username_special_character_warning']);?></div>
            <div class="box_not character_warning"><?php echo iN_HelpSecure($LANG['username_least_character_warning']);?></div>
          </div>
        </div>

        <!-- Category -->
        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['category']);?></div>
          <div class="i_settings_item_title_for">
            <div class="ib">
              <select class="page_category" name="ctgry">
                <?php
                  $gategoryList = $iN->iN_GetCategories();
                  if($gategoryList){
                    foreach($gategoryList as $cData){
                      $categoryID     = $cData['c_id'];
                      $categoryKey    = $cData['c_key'];
                      $categoryStatus = $cData['c_status'];
                      $checkAndGetSubCat = $iN->iN_CheckAndGetSubCat($categoryID);
                ?>
                  <option value="<?php echo iN_HelpSecure($categoryKey); ?>"
                    <?php echo iN_HelpSecure($userProfileCategory) == $categoryKey ? "selected='selected'" : ""; ?>>
                    <?php echo isset($PROFILE_CATEGORIES[$categoryKey]) ? $PROFILE_CATEGORIES[$categoryKey] : preg_replace('/{.*?}/', $categoryKey, $LANG['add_this_not_for_key']); ?>
                  </option>
                <?php
                      if($checkAndGetSubCat){
                        echo '<optgroup label="---">';
                        foreach($checkAndGetSubCat as $scaData){
                          $subCategoryKey = $scaData['sc_key'];
                          $scID           = $scaData['sc_id'];
                          $scStatus       = $scaData['sc_status'];
                ?>
                  <option value="<?php echo iN_HelpSecure($subCategoryKey); ?>"
                    <?php echo iN_HelpSecure($userProfileCategory) == $subCategoryKey ? "selected='selected'" : ""; ?>>
                    <?php echo isset($PROFILE_SUBCATEGORIES[$subCategoryKey]) ? $PROFILE_SUBCATEGORIES[$subCategoryKey] : preg_replace('/{.*?}/', $subCategoryKey, $LANG['add_lang_key_not']); ?>
                  </option>
                <?php
                        }
                        echo '</optgroup>';
                      }
                    }
                  }
                ?>
              </select>
            </div>
            <?php if($conditionStatus == '0' && $beaCreatorStatus == 'request'){ ?>
              <div class="box_not"><?php echo html_entity_decode($LANG['not_become_creator']);?></div>
            <?php } ?>
          </div>
        </div>

        <!-- Gender -->
        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['you_are_a']);?></div>
          <div class="i_settings_item_title_for flex_">
            <div class="flexBox flex_">
              <label class="youare" id="youarein" for="female">
                <input type="radio" name="gender" id="female" value="female" <?php echo iN_HelpSecure($userGender) == 'female' ? "checked='checked'" : ""; ?>>
                <span class="flex_ transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('13'));?><?php echo iN_HelpSecure($LANG['female']);?></span>
              </label>
            </div>

            

            <div class="flexBox flex_">
              <label class="youare flex_" id="youarein" for="male">
                <input type="radio" name="gender" id="male" value="male" <?php echo iN_HelpSecure($userGender) == 'male' ? "checked='checked'" : ""; ?>>
                <span class="flex_ transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('12'));?><?php echo iN_HelpSecure($LANG['male']);?></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Birthday -->
        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['birthday']);?></div>
          <div class="i_settings_item_title_for">
            <input name="birthdate" type="text" id="date1" class="flnm" maxlength="10" size="10"
              placeholder="<?php echo iN_HelpSecure($LANG['birthday_format_placeholder']);?>"
              value="<?php echo iN_HelpSecure($userBirthDay);?>">
            <div class="box_not"><?php echo iN_HelpSecure($LANG['birthday_help']);?></div>
            <div class="box_not character_warning"><?php echo iN_HelpSecure($LANG['your_age_must_be']);?></div>
          </div>
        </div>

        <!-- Social profiles -->
        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['social_profiles']);?></div>
          <div class="i_settings_item_title_for">
            <?php
              $socialNet = $iN->iN_ShowUserSocialSitesList($userID);
              if($socialNet){
                foreach($socialNet as $snet){
                  $soID         = $snet['id'];
                  $sKey         = $snet['skey'];
                  $sPlaceHolder = $snet['place_holder'];
                  $socialIcon   = $snet['social_icon'];
                  $ulData       = $iN->iN_GetUserProfileLinkDetails($userID, $soID);
                  $userSLink    = isset($ulData['s_link']) ? $ulData['s_link'] : NULL;
            ?>
              <div class="i_social_link_">
                <div class="i_i_social_icon flex_ tabing">
                  <div class="iisocialicon flex_ tabing"><?php echo html_entity_decode($socialIcon);?></div>
                </div>
                <input name="<?php echo iN_HelpSecure($sKey);?>" class="flnmk" type="text"
                  placeholder="<?php echo iN_HelpSecure($sPlaceHolder);?>" value="<?php echo iN_HelpSecure($userSLink);?>">
              </div>
            <?php
                }
              }
            ?>
          </div>
        </div>

        <!-- Description -->
        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['description']);?></div>
          <div class="i_settings_item_title_for">
            <textarea class="description_" name="bio" placeholder="<?php echo iN_HelpSecure($LANG['description_placeholder']);?>"><?php echo iN_HelpSecure($iN->sanitize_output($userBio,$base_url));?></textarea>
          </div>
        </div>

        <?php if($feesStatus == '2'){ ?>
        <!-- Tip thanks note -->
        <div class="i_settings_wrapper_item">
          <div class="i_settings_item_title"><?php echo iN_HelpSecure($LANG['tip_thanks_not']);?></div>
          <div class="i_settings_item_title_for">
            <textarea class="description_" name="tnot" placeholder="<?php echo iN_HelpSecure($LANG['write_your_thank_note']);?>"><?php echo iN_HelpSecure($iN->sanitize_output($thanksNOtForTip,$base_url));?></textarea>
          </div>
        </div>
        <?php } ?>
      </div> <!-- /.i_settings_wrapper_items -->

      <div class="i_settings_wrapper_item successNot">
        <?php echo iN_HelpSecure($LANG['profile_updated_success'])?>
      </div>

      <div class="i_settings_wrapper_item i_warning_point nonePoint">
        <?php echo iN_HelpSecure($LANG['full_name_must_be']);?>
      </div>

      <div class="i_become_creator_box_footer">
        <button type="submit" name="submit" class="i_nex_btn_btn transition" id="update_myprofile">
          <?php echo iN_HelpSecure($LANG['save_edit']);?>
        </button>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>themes/<?php echo iN_HelpSecure($currentTheme);?>/js/masked/jquery.mask.js?v=<?php echo iN_HelpSecure($version);?>"></script>
<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>themes/<?php echo iN_HelpSecure($currentTheme);?>/js/profileSettingsHandler.js?v=<?php echo iN_HelpSecure($version);?>"></script>
