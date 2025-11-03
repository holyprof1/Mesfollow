<?php
/*
Header notifications dropdown - V7 FINAL
This code works perfectly once the notification data limit is increased in the core files.
*/

// HELPER FUNCTION: Generates the smart notification text.
if (!function_exists('generateSmartNotificationText')) {
    function generateSmartNotificationText($actorNames, $actionText, $lang) {
        $count = count($actorNames);
        if ($count === 0) { return ''; }
        if ($count === 1) { return "<strong>" . htmlspecialchars($actorNames[0]) . "</strong> " . $actionText; }
        if ($count === 2) { return "<strong>" . htmlspecialchars($actorNames[0]) . "</strong> " . $lang['and'] . " <strong>" . htmlspecialchars($actorNames[1]) . "</strong> " . $actionText; }
        if ($count === 3) { return "<strong>" . htmlspecialchars($actorNames[0]) . "</strong>, <strong>" . htmlspecialchars($actorNames[1]) . "</strong> " . $lang['and'] . " <strong>" . htmlspecialchars($actorNames[2]) . "</strong> " . $actionText; }
        $firstThree = array_slice($actorNames, 0, 3);
        $othersCount = $count - 3;
        $othersText = sprintf($lang['and_x_others'], $othersCount);
        return "<strong>" . htmlspecialchars($firstThree[0]) . "</strong>, <strong>" . htmlspecialchars($firstThree[1]) . "</strong>, <strong>" . htmlspecialchars($firstThree[2]) . "</strong> " . $lang['and'] . " " . $othersText . " " . $actionText;
    }
}
?>
<style>
  /* Final, working CSS */
  .i_message_wrpper.is-unread .i_message_wrapper{background:#FBE7F6}
  .i_message_wrapper{border-radius:12px; padding: 8px 0;}
  #mfNotiMarkAll{background:#111;color:#fff;border:0;border-radius:8px;padding:6px 10px;cursor:pointer}
  #mfNotiMarkAll[disabled]{opacity:.6;cursor:default}
  .i_header_others_box .i_message_wrapper {display: flex !important; align-items: center !important; width: 100% !important;}
  .i_header_others_box .i_message_info_container {flex: 1 1 auto !important; min-width: 0 !important;}
  .i_header_others_box .i_message_i {white-space: normal !important; overflow: visible !important; text-overflow: clip !important; display: block !important; line-height: 1.4; padding-right: 5px;}
  .i_header_others_box .notification-group-count {flex-shrink: 0; margin-left: auto; margin-right: 15px; background-color: #f0f2f5; color: #65676b; font-size: 12px; font-weight: bold; padding: 3px 8px; border-radius: 8px;}
  .i_header_others_box .is-unread .notification-group-count {background-color: #e6007e; color: #ffffff;}
</style>

<div class="i_general_box_notifications_container generalBox">
  <div class="btest">
    <div class="i_user_details">
      <div class="i_box_messages_header" style="display:flex;align-items:center;gap:10px">
        <span><?php echo iN_HelpSecure($LANG['notifications']); ?></span>
        <button id="mfNotiMarkAll"><?php echo iN_HelpSecure($LANG['mark_all_as_read'] ?? 'Read all'); ?></button>
        <div class="i_message_full_screen transition" style="margin-left:auto">
          <a href="<?php echo iN_HelpSecure($base_url); ?>notifications"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('48')); ?></a>
        </div>
      </div>

      <div class="i_header_others_box" id="mfHeaderNoti">
        <?php
        $finalNotifications = [];
        if (!empty($Notifications)) {
            foreach ($Notifications as $notData) {
                $type = $notData['not_not_type'] ?? null;
                $postID = $notData['not_post_id'] ?? null;
                $actorID = (int)$notData['not_iuid'];
                $groupKey = null;

              // Group strictly by post for likes & comments.
// Everything else (including live_now) stays single.
if (($type === 'commented' || $type === 'postLike') && !empty($postID)) {
    $groupKey = $type . '_' . $postID;
}


                if ($groupKey) {
                    if (!isset($finalNotifications[$groupKey])) {
                        $finalNotifications[$groupKey] = ['is_group' => true, 'type' => $type, 'postID' => $postID, 'notifications' => [], 'actors' => [], 'is_unread' => false];
                    }
                    $finalNotifications[$groupKey]['notifications'][] = $notData;
                    $finalNotifications[$groupKey]['actors'][$actorID] = $notData;
                    if ((int)($notData['not_status'] ?? '1') == '0') { $finalNotifications[$groupKey]['is_unread'] = true; }
                } else {
                    $finalNotifications['single_' . $notData['not_id']] = $notData;
                }
            }
        }

        if (!empty($finalNotifications)) {
            foreach ($finalNotifications as $item) {
                if (isset($item['is_group'])) {
                    $count = count($item['notifications']);
                    if ($count === 0) continue;
                    $actors = $item['actors'];
                    $mostRecentActorData = end($item['notifications']);
                    $firstActorID = (int)$mostRecentActorData['not_iuid'];
                    $u = $iN->iN_GetUserDetails($firstActorID);
                    $uname = $u['i_username'] ?? '';

                    $actorNames = [];
                    foreach ($actors as $actorData) {
                         $actorDetails = $iN->iN_GetUserDetails((int)$actorData['not_iuid']);
                         $actorNames[] = ($fullnameorusername === 'no' ? $actorDetails['i_username'] : ($actorDetails['i_user_fullname'] ?? $actorDetails['i_username']));
                    }
                    $actorNames = array_reverse($actorNames);
                    $actionText = ''; $notIcon = ''; $notUrl = ''; $dataAttrs = '';
                    $langForText = ['and' => $LANG['and'], 'and_x_others' => $LANG['and_x_others']];

                  switch ($item['type']) {
    case 'commented':
        $actionText = $LANG['commented_on_your_post'];
        $notIcon    = $iN->iN_SelectedMenuIcon('20');
        $notUrl     = $base_url . 'post/' . $item['postID'];
        $dataAttrs  = 'data-group-type="commented" data-post-id="'.$item['postID'].'"';
        break;

    case 'postLike':
        $actionText = $LANG['liked_your_post'];
        $notIcon    = $iN->iN_SelectedMenuIcon('18');
        $notUrl     = $base_url . 'post/' . $item['postID'];
        $dataAttrs  = 'data-group-type="postLike" data-post-id="'.$item['postID'].'"';
        break;
						  
		
    default:
        // defensive: skip any unexpected grouped type
        continue 2;
}


                    $smartText = generateSmartNotificationText($actorNames, $actionText, $langForText);
                    $firstActorAvatar = $iN->iN_UserAvatar($firstActorID, $base_url);
                    $isUnreadClass = $item['is_unread'] ? 'is-unread' : 'is-read';
                    ?>
                    <div class="i_message_wrpper <?php echo $isUnreadClass; ?>">
                        <a href="<?php echo iN_HelpSecure($notUrl); ?>" class="mf-noti-link" <?php echo $dataAttrs; ?>>
                            <div class="i_message_wrapper transition">
                                <div class="i_message_owner_avatar">
                                    <div class="i_message_not_icon flex_ tabing"><?php echo html_entity_decode($notIcon); ?></div>
                                    <div class="i_message_avatar"><img src="<?php echo iN_HelpSecure($firstActorAvatar); ?>" alt=""></div>
                                </div>
                                <div class="i_message_info_container">
                                    <div class="i_message_i"><?php echo $smartText; ?></div>
                                </div>
                                <span class="notification-group-count"><?php echo $count; ?></span>
                            </div>
                        </a>
                    </div>
                    <?php
                } else {
                    $notData = $item;
                    include( \dirname( __FILE__ ) . '/notification_template.php' );
                }
            }
        } else { ?>
            <div class="no_not_here tabing flex_"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('103')); ?></div>
        <?php } ?>
      </div>
    </div>
    <div class="footer_container messages"><a href="<?php echo iN_HelpSecure($base_url); ?>notifications"><?php echo iN_HelpSecure($LANG['see_all_notifications']); ?></a></div>
  </div>
</div>
<script>
$(document).ready(function(){
    $('body').off('click.mfNotiLink').on('click.mfNotiLink', 'a.mf-noti-link', function(e) {
        e.preventDefault();
        const link = $(this);
        const href = link.attr('href');
        const singleId = link.data('id');
        const groupType = link.data('group-type');
        let ajaxData = { f: 'NotificationSeen', id: singleId };
        if (groupType) { ajaxData = { f: 'mark_grouped_notifications_read', type: groupType, postID: link.data('post-id'), actorID: link.data('actor-id') }; }
        $.post(window.siteurl + 'requests/request.php', ajaxData).always(function() { window.location.href = href; });
    });
});
</script>