<?php
if (!empty($moreNotifications)) {
  foreach ($moreNotifications as $notData) {
    $notificationID   = $notData['not_id'];
    $notPostID        = $notData['not_post_id'];
    $typeGroup        = $notData['not_type'];
    $typeDetail       = $notData['not_not_type'];
    $notCreator       = $notData['not_iuid'];

    $u       = $iN->iN_GetUserDetails($notCreator);
    $uname   = $u['i_username'];
    $ufull   = ($fullnameorusername === 'no') ? $uname : ($u['i_user_fullname'] ?? $uname);
    $uava    = $iN->iN_UserAvatar($notCreator, $base_url);

    $notText = $LANG['notification'];
    $notIcon = $iN->iN_SelectedMenuIcon('37');
    $notUrl  = $base_url . $uname;

    if ($typeDetail === 'live_now') {
      $notText = $LANG['is_live_now'] ?? 'is live now';
      $notIcon = $iN->iN_SelectedMenuIcon('115');
      $notUrl  = $base_url . 'live/' . $uname;

    } elseif ($typeDetail === 'commented') {
      $notText = $LANG['commented_on_your_post'];
      $notIcon = $iN->iN_SelectedMenuIcon('20');
      $notUrl  = $base_url . 'post/' . $notPostID;

    } elseif ($typeDetail === 'postLike') {
      $notText = $LANG['liked_your_post'];
      $notIcon = $iN->iN_SelectedMenuIcon('18');
      $notUrl  = $base_url . 'post/' . $notPostID;

    } elseif ($typeDetail === 'commentLike') {
      $notText = $LANG['liked_your_comment'];
      $notIcon = $iN->iN_SelectedMenuIcon('18');
      $notUrl  = $base_url . 'post/' . $notPostID;

    } elseif ($typeDetail === 'follow') {
      $notText = $LANG['is_now_following_your_profile'];
      $notIcon = $iN->iN_SelectedMenuIcon('66');
      $notUrl  = $base_url . $uname;

    } elseif ($typeDetail === 'subscribe') {
      $notText = $LANG['is_subscribed_your_profile'];
      $notIcon = $iN->iN_SelectedMenuIcon('51');
      $notUrl  = $base_url . $uname;

    } elseif ($typeDetail === 'accepted_post') {
      $notText = $LANG['accepted_post'];
      $notIcon = $iN->iN_SelectedMenuIcon('69');
      $notUrl  = $base_url . 'post/' . $notPostID;
    }
?>
  <div class="i_notification_wrpper mor body_<?php echo iN_HelpSecure($notificationID); ?>" id="<?php echo iN_HelpSecure($notificationID); ?>" data-last="<?php echo iN_HelpSecure($notificationID); ?>">
    <a href="<?php echo iN_HelpSecure($notUrl); ?>">
      <div class="i_notification_wrapper transition">
        <div class="i_message_owner_avatar">
          <div class="i_message_not_icon flex_ tabing"><?php echo html_entity_decode($notIcon); ?></div>
          <div class="i_message_avatar">
            <img src="<?php echo iN_HelpSecure($uava); ?>" alt="<?php echo iN_HelpSecure($ufull); ?>">
          </div>
        </div>
        <div class="i_message_info_container">
          <div class="i_message_owner_name"><?php echo iN_HelpSecure($ufull); ?></div>
          <div class="i_message_i"><?php echo iN_HelpSecure($notText); ?></div>
        </div>
      </div>
    </a>
    <div class="i_message_setting msg_Set msg_Set_<?php echo iN_HelpSecure($notificationID); ?>" id="<?php echo iN_HelpSecure($notificationID); ?>">
      <div class="i_message_set_icon">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('16')); ?>
      </div>
      <div class="i_message_set_container msg_Set msg_Set_<?php echo iN_HelpSecure($notificationID); ?>">
        <div class="i_post_menu_item_out transition">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('28')); ?>
          <?php echo iN_HelpSecure($LANG['remove_this_notification']); ?>
        </div>
        <div class="i_post_menu_item_out transition">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('47')); ?>
          <?php echo iN_HelpSecure($LANG['mark_as_read']); ?>
        </div>
      </div>
    </div>
  </div>
<?php
  } // foreach
} // if
?>
