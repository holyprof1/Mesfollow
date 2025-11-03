<?php
// expects $notData, $iN, $LANG, $base_url, $fullnameorusername
$notificationID = (int)($notData['not_id'] ?? 0);
$read           = (int)($notData['not_status'] ?? 1);
$pid            = $notData['not_post_id'] ?? '';
$det            = $notData['not_not_type'] ?? '';
$actorID        = (int)($notData['not_iuid'] ?? 0);

$u   = $iN->iN_GetUserDetails($actorID);
$un  = $u['i_username'] ?? '';
$uf  = ($fullnameorusername === 'no') ? $un : ($u['i_user_fullname'] ?? $un);
$ava = $iN->iN_UserAvatar($actorID, $base_url);

$icon = ''; $text = ''; $url = $base_url;
switch ($det) {
  case 'live_now':
    $text = $LANG['is_live_now'] ?? 'is live now';
    $icon = $iN->iN_SelectedMenuIcon('115');
    $url  = $base_url . 'live/' . $un;
    break;
  case 'commented':
    $text = $LANG['commented_on_your_post'] ?? 'commented on your post';
    $icon = $iN->iN_SelectedMenuIcon('20');
    $url  = $base_url . 'post/' . $pid;
    break;
  case 'postLike':
    $text = $LANG['liked_your_post'] ?? 'liked your post';
    $icon = $iN->iN_SelectedMenuIcon('18');
    $url  = $base_url . 'post/' . $pid;
    break;
  case 'commentLike':
    $text = $LANG['liked_your_comment'] ?? 'liked your comment';
    $icon = $iN->iN_SelectedMenuIcon('18');
    $url  = $base_url . 'post/' . $pid;
    break;
  case 'follow':
    $text = $LANG['is_now_following_your_profile'] ?? 'started following you';
    $icon = $iN->iN_SelectedMenuIcon('66');
    $url  = $base_url . $un;
    break;
  case 'subscribe':
    $text = $LANG['is_subscribed_your_profile'] ?? 'subscribed to you';
    $icon = $iN->iN_SelectedMenuIcon('51');
    $url  = $base_url . $un;
    break;
  case 'accepted_post':
    $text = $LANG['accepted_post'] ?? 'post accepted';
    $icon = $iN->iN_SelectedMenuIcon('69');
    $url  = $base_url . 'post/' . $pid;
    break;
  case 'rejected_post':
  case 'declined_post':
    $text = $LANG['rejected_post'] ?? 'post rejected';
    $icon = $iN->iN_SelectedMenuIcon('5');
    $url  = $base_url . 'post/' . $pid;
    break;
  case 'umentioned':
    $text = $LANG['mentioned_u'] ?? 'mentioned you';
    $icon = $iN->iN_SelectedMenuIcon('37');
    $url  = $base_url . 'post/' . $pid;
    break;
  case 'purchasedYourPost':
    $text = $LANG['congratulations_you_sold'] ?? 'sold your post';
    $icon = $iN->iN_SelectedMenuIcon('175');
    $url  = $base_url . 'post/' . $pid;
    break;
		case 'subscription_expiring_5d':
    $text = $LANG['subscription_expires_in_5_days'];
    $icon = $iN->iN_SelectedMenuIcon('51');
    $url = $base_url . $un;
    break;

case 'subscription_expiring_2d':
    $text = $LANG['subscription_expires_in_2_days'];
    $icon = $iN->iN_SelectedMenuIcon('51');
    $url = $base_url . $un;
    break;

case 'subscription_expiring_0d':
    $text = $LANG['subscription_expires_today'];
    $icon = $iN->iN_SelectedMenuIcon('51');
    $url = $base_url . $un;
    break;

case 'subscription_expired':
    $text = $LANG['subscription_has_expired'];
    $icon = $iN->iN_SelectedMenuIcon('5');
    $url = $base_url . $un;
    break;
  default:
    $text = $LANG['notification'] ?? 'Notification';
    $icon = $iN->iN_SelectedMenuIcon('37');
    $url  = $base_url . $un;
    break;
}
$cls = $read ? 'is-read' : 'is-unread';
?>
<div class="i_message_wrpper <?php echo $cls; ?>">
  <a href="<?php echo iN_HelpSecure($url); ?>"
     class="mf-noti-link"
     data-id="<?php echo (int)$notificationID; ?>"
     data-actor-id="<?php echo (int)$actorID; ?>">
    <div class="i_message_wrapper transition">
      <div class="i_message_owner_avatar">
        <div class="i_message_not_icon flex_ tabing"><?php echo html_entity_decode($icon); ?></div>
        <div class="i_message_avatar">
          <img src="<?php echo iN_HelpSecure($ava); ?>" alt="<?php echo iN_HelpSecure($uf); ?>">
        </div>
      </div>
      <div class="i_message_info_container">
        <div class="i_message_owner_name"><?php echo iN_HelpSecure($uf); ?></div>
        <div class="i_message_i"><?php echo iN_HelpSecure($text); ?></div>
      </div>
    </div>
  </a>
</div>
