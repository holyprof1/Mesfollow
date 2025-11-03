<?php
/* TikTok-like HUD overlay (green LIVE), used on free & paid pages. */
$startTS = isset($liveStartTime) ? (int)$liveStartTime : time(); // if you have start time, pass it; else page load
?>
<div class="tthud"
     data-live-id="<?php echo iN_HelpSecure($theLiveID); ?>"
     data-host-id="<?php echo iN_HelpSecure($liveCreator); ?>"
     data-start="<?php echo $startTS; ?>">
  <div class="tthud-row">
    <a class="tthud-host" href="<?php echo iN_HelpSecure($base_url.$liveCreatorUserName); ?>" target="_blank" rel="noopener">
      <img class="tthud-ava" src="<?php echo iN_HelpSecure($liveCreatorAvatar); ?>" alt="<?php echo iN_HelpSecure($liveCreatorFullname); ?>">
      <div class="tthud-hostnames">
        <div class="tthud-name"><?php echo iN_HelpSecure($liveCreatorFullname); ?></div>
        <div class="tthud-username">@<?php echo iN_HelpSecure($liveCreatorUserName); ?></div>
      </div>
    </a>

    <div class="tthud-meta">
      <span class="tthud-livepill">
        <span class="dot"></span> LIVE
      </span>
      <span class="tthud-sep">•</span>
      <span class="tthud-viewers">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15')); ?>
        <span class="tthud-viewers-num sumonline">0</span>
      </span>
      <span class="tthud-sep">•</span>
      <span class="tthud-elapsed" id="tthudElapsed">00:00</span>
    </div>

    <div class="tthud-actions">
      <?php if ($p_friend_status != 'subscriber' && $p_friend_status != 'me' && $p_friend_status != 'flwr') { ?>
        <div class="tthud-follow i_fw<?php echo iN_HelpSecure($liveCreator); ?> transition <?php echo iN_HelpSecure($flwrBtn); ?>"
             id="i_btn_like_item" data-u="<?php echo iN_HelpSecure($liveCreator); ?>">
          <?php echo html_entity_decode($flwBtnIconText); ?>
        </div>
      <?php } ?>
    </div>
  </div>

  <div class="tthud-side">
    <div class="tthud-like like_live <?php echo iN_HelpSecure($likeClass);?>"
         id="p_l_l_<?php echo iN_HelpSecure($liveID);?>" data-id="<?php echo iN_HelpSecure($liveID);?>">
      <?php
        $heart = $iN->iN_SelectedMenuIcon('173') ?: '<svg viewBox="0 0 24 24" width="24" height="24"><path d="M12 21s-8-4.438-8-10a5 5 0 019-3 5 5 0 019 3c0 5.562-8 10-8 10z"/></svg>';
        echo html_entity_decode($heart);
      ?>
      <div class="tthud-like-count lp_sum_l" id="lp_sum_l_<?php echo iN_HelpSecure($liveID);?>"><?php echo iN_HelpSecure($likeSum);?></div>
    </div>

    <?php if($userID != $liveCreator){ ?>
      <div class="tthud-gift live_gift_call">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('145'));?>
      </div>
    <?php } ?>
  </div>

  <div class="tthud-hearts" id="tthudHearts"></div>
</div>