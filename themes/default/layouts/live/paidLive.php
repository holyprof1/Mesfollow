<?php
/* paidLive.php — stable layout, fills video, and mobile HUD layout (TikTok style) */

if (!isset($theLiveID) && isset($liveID)) { $theLiveID = $liveID; }

/* Gate: viewers must have access (or be admin) */
$checkUserPurchasedThisLiveStream = '1';
if ($userID != $liveCreator) {
  $checkUserPurchasedThisLiveStream = $iN->iN_CheckUserPurchasedThisLiveStream($userID, $liveID);
}
?>

<?php if ($checkUserPurchasedThisLiveStream || $userType == '2') { ?>
<style>
/* ====== BASE PLAYER (all screens) ====== */
.live__live_video_holder,
.live_vide__holder,
#local-player {
  min-height: 360px;
  position: relative;
  background: #000;
}

.live_holder_plus_in, 
.live_footer_holder {
  position: relative;
  z-index: 2;
}

/* ====== DYNAMIC LAYOUTS ====== */

/* Base container */
.live_vide__holder {
  position: relative;
  width: 100%;
  min-height: 360px;
  background: #000;
  display: block;
}

/* All videos fill their containers */
#local-player,
#local-player video,
.op-tile,
.op-tile video,
[id^="user-"],
[id^="user-"] video,
[id^="agora-video-player"],
[id^="agora-video-player"] video {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  display: block;
}

/* Layout 1: Single person (full screen) */
.live_vide__holder.layout-1 #local-player {
  position: absolute;
  inset: 0;
  width: 100% !important;
  height: 100% !important;
  z-index: 1;
}

.live_vide__holder.layout-1 #remote-playerlist {
  display: none !important;
}

/* Layout 2: Two people (side by side) */
.live_vide__holder.layout-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.live_vide__holder.layout-2 #local-player {
  position: relative !important;
  width: 100% !important;
  height: 100% !important;
  min-height: 360px;
  inset: auto !important;
}

.live_vide__holder.layout-2 #remote-playerlist {
  position: relative !important;
  display: block !important;
  width: 100% !important;
  height: 100% !important;
  min-height: 360px;
  inset: auto !important;
}

.live_vide__holder.layout-2 #remote-playerlist > div {
  width: 100% !important;
  height: 100% !important;
}

/* Layout 3: Three+ people (main + thumbnails) */
.live_vide__holder.layout-3 {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 8px;
}

.live_vide__holder.layout-3 #local-player {
  position: relative !important;
  width: 100% !important;
  height: 100% !important;
  min-height: 360px;
  inset: auto !important;
}

.live_vide__holder.layout-3 #remote-playerlist {
  position: relative !important;
  display: flex !important;
  flex-direction: column;
  gap: 8px;
  width: 100% !important;
  height: auto !important;
  inset: auto !important;
}

.live_vide__holder.layout-3 #remote-playerlist > div {
  position: relative !important;
  width: 100% !important;
  height: 180px !important;
  min-height: 180px;
  border-radius: 8px;
  overflow: hidden;
  background: #000;
  inset: auto !important;
}

/* ================================
   MOBILE LAYOUT (≤ 768px)
   ================================ */
@media (max-width: 768px) {
  /* Hide desktop chrome */
  .live_left,
  .header,
  .live_footer_holder,
  .live_video_header {
    display: none !important;
  }

  .live_wrapper_tik {
    padding-top: 0 !important;
  }

  .live_right {
    width: 100% !important;
  }

  .live_right_in_wrapper {
    padding: 0 !important;
  }

  .live__live_video_holder {
    position: relative !important;
  }

  .live_vide__holder {
    position: relative !important;
    min-height: calc(100vh - 56px) !important;
    background: #000;
  }

  /* Remove emoji UI on mobile */
  .getMEmojisa,
  .Message_stickersContainer,
  .nanos {
    display: none !important;
  }

  /* Mobile Layout 1: Single person (full screen) */
  .live_vide__holder.layout-1 {
    display: block;
  }

  .live_vide__holder.layout-1 #local-player {
    position: absolute;
    inset: 0;
    min-height: calc(100vh - 56px);
  }

  /* Mobile Layout 2: Two people (stack vertically) */
  .live_vide__holder.layout-2 {
    grid-template-columns: 1fr;
    grid-template-rows: 1fr 1fr;
  }

  .live_vide__holder.layout-2 #local-player,
  .live_vide__holder.layout-2 #remote-playerlist {
    min-height: calc((100vh - 56px) / 2 - 4px);
  }

  .live_vide__holder.layout-2 #remote-playerlist > div {
    height: 100% !important;
  }

  /* Mobile Layout 3: Main + small tiles */
  .live_vide__holder.layout-3 {
    grid-template-columns: 1fr;
    grid-template-rows: auto auto;
  }

  .live_vide__holder.layout-3 #local-player {
    min-height: 60vh;
  }

  .live_vide__holder.layout-3 #remote-playerlist {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 6px;
    height: auto !important;
  }

  .live_vide__holder.layout-3 #remote-playerlist > div {
    flex: 1 1 calc(50% - 3px);
    height: 20vh !important;
    min-height: 20vh !important;
    min-width: calc(50% - 3px);
  }

  /* ===== HUD (TikTok-style) ===== */
  .tthud {
    position: absolute;
    left: 10px;
    right: 10px;
    top: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    z-index: 5;
    pointer-events: none;
    color: #fff;
    font-size: 12px;
  }

  .tthud a {
    pointer-events: auto;
    text-decoration: none;
    color: #fff;
  }

  .tthud-row {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .tthud-host {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .tthud-ava {
    width: 32px;
    height: 32px;
    border-radius: 999px;
    object-fit: cover;
  }

  .tthud-hostnames .tthud-name {
    font-weight: 700;
    line-height: 1;
  }

  .tthud-hostnames .tthud-username {
    opacity: 0.85;
    line-height: 1;
  }

  .tthud-meta {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .tthud-livepill {
    background: #e11d48;
    color: #fff;
    padding: 3px 8px;
    border-radius: 999px;
    font-weight: 700;
    letter-spacing: 0.2px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .tthud-livepill .dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #fff;
    display: inline-block;
  }

  .tthud-sep {
    opacity: 0.7;
  }

  .tthud-actions .tthud-follow {
    background: #10b981;
    color: #062;
    font-weight: 700;
    border-radius: 999px;
    padding: 4px 10px;
    display: inline-flex;
    align-items: center;
    pointer-events: auto;
  }

  /* Right-side vertical buttons (❤️ + 🎁) */
  .tthud-side {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 5;
    pointer-events: auto;
  }

  .tthud-like,
  .tthud-gift {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    color: #fff;
  }

  .tthud-like-count {
    text-align: center;
    color: #fff;
    font-weight: 700;
    margin-top: 4px;
  }

  /* Floating hearts layer */
  .tthud-hearts {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 4;
  }

  /* Chat list over video */
  .live_right_in_right,
  .live_right_in_right_mobile {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 72px;
    top: auto;
    height: 40vh;
    z-index: 4;
    padding: 0 10px;
  }

  .live_right_in_right .live_right_in_right_in,
  .live_right_in_right_mobile .live_right_in_right_in {
    pointer-events: auto;
    height: 100%;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }

  /* Gift panel: hidden by default on mobile */
  .live_footer_holder {
    display: none !important;
  }

  /* When user taps the Gift button, JS adds this class => show as bottom sheet */
  .live_footer_holder.live_footer_holder_show {
    display: block !important;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 7;
    max-height: 45vh;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    background: rgba(10, 10, 12, 0.96);
    backdrop-filter: blur(6px);
    padding: 12px 12px 16px;
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
  }

  /* Dim background to close when tapped */
  .appendBoxLive {
    position: fixed;
    inset: 0;
    z-index: 6;
    background: rgba(0, 0, 0, 0.35);
  }

  /* Sticky chat composer */
  .live_send_message_box_wrapper {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 6;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.45) 24%, rgba(0, 0, 0, 0.65) 100%);
    padding: 10px 10px 12px;
  }

  .live_send_message_box_wrapper .optional_width {
    width: 100% !important;
  }

  .live_send_message_box_wrapper .message_form_items {
    width: 100%;
    gap: 8px;
  }

  .live_send_message_box_wrapper .message_text_textarea {
    flex: 1;
  }

  .live_send_message_box_wrapper .lmSize {
    width: 100%;
    min-height: 38px;
    max-height: 96px;
    resize: none;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: rgba(0, 0, 0, 0.45);
    color: #fff;
    padding: 10px 12px;
  }

  .live_send_message_box_wrapper .message_form_plus,
  .live_send_message_box_wrapper .getMEmojisa {
    background: rgba(0, 0, 0, 0.45);
    border-radius: 14px;
    padding: 8px;
    color: #fff;
  }

  /* Ensure video sits under HUD/Chat layers */
  #remote-playerlist,
  #local-player {
    z-index: 1;
  }
}

/* Floating Quit/Leave button (mobile, top-right) */
.tthud-quit {
  position: absolute;
  top: 12px;
  right: 10px;
  width: 40px;
  height: 40px;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
  color: #fff;
  z-index: 6;
  pointer-events: auto;
}

.tthud-quit svg {
  width: 22px;
  height: 22px;
}

/* Desktop: hide quit button by default */
@media (min-width: 769px) {
  .tthud-quit {
    display: none;
  }
}

/* Co-host UI controls */
.op-seat-ui {
  position: fixed;
  right: 12px;
  bottom: 88px;
  z-index: 9999;
  font-family: system-ui;
}

.op-seat-ui button {
  margin: 4px 0;
  padding: 8px 12px;
  border: 0;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  background: #fff;
}

.op-seat-list {
  max-height: 180px;
  overflow: auto;
  margin-top: 6px;
  padding: 6px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.op-seat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 4px 0;
}

.op-chip {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 999px;
  background: #eee;
  margin-left: 8px;
}
</style>

<div class="live_wrapper_tik" id="<?php echo iN_HelpSecure($liveID);?>">
  <div class="live_left">
    <div class="live_left_in_wrapper">
      <div class="live_left_in_holder">
        <a href="<?php echo iN_HelpSecure($base_url);?>">
          <div class="i_left_menu_box transition">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('99'));?>
            <div class="m_tit"><?php echo iN_HelpSecure($LANG['home_page']);?></div>
          </div>
        </a>

        <div class="i_left_menu_box transition g_feed" data-get="friends" data-type="moreposts">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('7'));?>
          <div class="m_tit"><?php echo iN_HelpSecure($LANG['newsfeed']);?></div>
        </div>

        <div class="i_left_menu_box transition g_feed" data-get="allPosts" data-type="moreexplore">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('8'));?>
          <div class="m_tit"><?php echo iN_HelpSecure($LANG['explore']);?></div>
        </div>

        <div class="i_left_menu_box transition g_feed" data-get="premiums" data-type="morepremium">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('9'));?>
          <div class="m_tit"><?php echo iN_HelpSecure($LANG['premium']);?></div>
        </div>

        <a href="<?php echo iN_HelpSecure($base_url);?>creators">
          <div class="i_left_menu_box transition">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('95'));?>
            <div class="m_tit"><?php echo iN_HelpSecure($LANG['our_creators']);?></div>
          </div>
        </a>

        <div class="live_suggested_lives_wrapper">
          <?php include "live_list_widget.php";?>
        </div>
      </div>
    </div>
  </div>

  <div class="live_right">
    <div class="live_right_in_wrapper">
      <div class="live_right_in_left">
        <!-- Desktop header (hidden on mobile by CSS above) -->
        <div class="live_video_header">
          <div class="live_creator_avatar_live flex_ tabing">
            <a class="flex_ alignItem" href="<?php echo $base_url.$liveCreatorUserName;?>" target="_blank">
              <img src="<?php echo iN_HelpSecure($liveCreatorAvatar);?>">
            </a>
          </div>

          <div class="live_creator_live_name_live_username">
            <div class="live_creator_live_username">
              <a class="flex_ alignItem exen loi" href="<?php echo $base_url.$liveCreatorUserName;?>" target="_blank">
                <?php echo iN_HelpSecure($liveCreatorFullname);?>
              </a>
            </div>
            <div class="live_creator_live_name flex_ tabing">
              <?php echo $siteTitle;?> <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('15'));?>
              <span class="sumonline">0</span>
              <div class="live_pulse">LIVE</div>
            </div>
          </div>

          <div class="live_header_in_right flex_ tabing">
            <div class="live_owner_flw_btn">
              <?php if ($p_friend_status != 'subscriber' && $p_friend_status != 'me' && $p_friend_status != 'flwr') { ?>
                <div class="i_fw<?php echo iN_HelpSecure($liveCreator); ?> transition <?php echo iN_HelpSecure($flwrBtn); ?>"
                     id="i_btn_like_item" data-u="<?php echo iN_HelpSecure($liveCreator); ?>">
                  <?php echo html_entity_decode($flwBtnIconText); ?>
                </div>
              <?php } ?>
            </div>

            <?php if($userID == $liveCreator){ ?>
              <div class="i_header_btn_item topPoints transition cameli">
                <div class="i_h_in_live camera_chs">
                  <div class="camList cam-list" id="camera-list"></div>
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('137'));?>
                </div>
              </div>
              <div class="i_header_btn_item topPoints transition cameli">
                <div class="i_h_in_live mick_chs">
                  <div class="micList mic-list" id="camera-list"></div>
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('152'));?>
                </div>
              </div>
              <div class="i_header_btn_item topPoints transition">
                <div class="i_h_in_live camcloseCall"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('172'));?></div>
              </div>
            <?php } ?>
          </div>
        </div>

        <div class="live__live_video_holder">
         <div class="live_vide__holder">
  <div id="local-player"></div>
  <div id="remote-playerlist"></div>



            <?php /* TikTok-like HUD overlay (green LIVE) */ ?>
            <?php include "_hud.php"; ?>
<?php /* Mobile-only Quit/Leave (right side). Host uses .camcloseCall, audience uses #leave */ ?>
<?php if ((string)$userID === (string)$liveCreator) { ?>
  <div class="tthud-quit camcloseCall" id="<?php echo iN_HelpSecure($theLiveID);?>" title="<?php echo iN_HelpSecure($LANG['cancel']);?>">
    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('172'));?>
  </div>
<?php } else { ?>
  <a id="leave" class="tthud-quit" title="Leave">
    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('172'));?>
  </a>
<?php } ?>

            <div class="live_holder_plus_in">
              <div class="holder_l_in flex_ tabing">
                <?php if($userID == $liveCreator){ ?>
                  <div class="button-group">
                    <button id="mute-audio" type="button" class="flex_ tabing"><?php echo iN_HelpSecure($LANG['mute_audio']);?></button>
                    <button id="mute-video" type="button" class="flex_ tabing"><?php echo iN_HelpSecure($LANG['mute_video']);?></button>
                  </div>
                <?php } ?>
                <div class="live_like_t">
                  <div class="like_live flex_ <?php echo iN_HelpSecure($likeClass);?>"
                       id="p_l_l_<?php echo iN_HelpSecure($liveID);?>"
                       data-id="<?php echo iN_HelpSecure($liveID);?>">
                    <?php echo html_entity_decode($likeIcon);?>
                  </div>
                  <div class="lp_sum_l flex_ tabing" id="lp_sum_l_<?php echo iN_HelpSecure($liveID);?>"><?php echo iN_HelpSecure($likeSum);?></div>
                </div>
                <?php if($userID != $liveCreator){ ?>
                  <div class="live_gift_call flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('145'));?></div>
                <?php } ?>
              </div>
            </div>
          </div>

          <div class="live_footer_holder">
            <?php if($p_friend_status != 'me'){?>
              <?php include "liveCoinList.php";?>
              <div class="live_coin_current_balance">
                <div class="current_balance_box flex_ tabing_non_justify">
                  <?php echo iN_HelpSecure($LANG['point_balance']);?>
                  <span class="crnblnc"><?php echo number_format($userCurrentPoints);?></span>
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40'));?>
                  <a href="<?php echo $base_url.'purchase/purchase_point';?>" target="_blank" class="transitions">
                    <?php echo iN_HelpSecure($LANG["get_points"]);?>
                  </a>
                </div>
              </div>
            <?php }?>

            <div class="currentt_live_streamings_list_container tabing">
              <?php include "sugLiveStreams.php";?>
            </div>
          </div>
        </div>
      </div>

      <div class="live_right_in_right relativePosition">
        <div class="live_right_in_right_in">
          <?php include "liveChat.php";?>
        </div>
        <div class="live_send_message_box_wrapper">
          <div class="nanos transition"></div>
          <div class="tabing_non_justify flex_ optional_width">
            <div class="message_form_items flex_ tabing">
              <div class="message_send_text flex_ tabing">
                <div class="message_text_textarea flex_">
                  <textarea class="lmSize"></textarea>
                  <div class="message_smiley getMEmojisa">
                    <div class="message_form_smiley_plus transition">
                      <div class="message_pls flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('25')); ?></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="message_form_plus transition livesendmes">
                <div class="message_pls flex_ tabing"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('26')); ?></div>
              </div>
            </div>
          </div>
        </div>
      </div></div>
  </div>
</div>

<?php if($userID != $liveCreator){ $iN->iN_InsertMyOnlineStatus($userID, $liveID); } ?>

<script>
  window.siteurl     = "<?php echo iN_HelpSecure($base_url); ?>";
  window.liveAppID   = "<?php echo iN_HelpSecure($agoraAppID); ?>";
  window.liveChannel = "<?php echo iN_HelpSecure($liveChannel); ?>";
  window.liveUserID  = "<?php echo iN_HelpSecure($userID); ?>";
  window.liveCreator = "<?php echo iN_HelpSecure($liveCreator); ?>";
  window.theLiveID   = "<?php echo iN_HelpSecure($theLiveID); ?>";
</script>
<!-- POC co-host controls: safe, isolated -->
<style>
  .op-seat-ui{position:fixed;right:12px;bottom:88px;z-index:9999;font-family:system-ui}
  .op-seat-ui button{margin:4px 0;padding:8px 12px;border:0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.15);cursor:pointer}
  .op-seat-list{max-height:180px;overflow:auto;margin-top:6px;padding:6px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1)}
  .op-seat-item{display:flex;justify-content:space-between;align-items:center;margin:4px 0}
  .op-chip{font-size:12px;padding:2px 8px;border-radius:999px;background:#eee;margin-left:8px}
</style>

<div class="op-seat-ui" id="opSeatUI" style="display:none">
  <div id="opSeatForHost" style="display:none">
    <button id="opRefreshRequests">Pending requests</button>
    <div class="op-seat-list" id="opRequestsList"></div>
  </div>
  <div id="opSeatForViewer" style="display:none">
    <button id="opRequestSeat">Request to join</button>
    <div id="opSeatStatus" class="op-chip" style="display:none">Requested…</div>
  </div>
</div>

<script>
(function(){
  // Expect these globals from your existing page:
  //   window.liveAppID, window.liveChannel, window.liveUserID, window.liveCreator
  //   window.options.role ("host"|"audience") set by your livestream script

  const channel = (window.liveChannel || "").toString();
  const me = (window.liveUserID || "").toString();
  const host = (window.liveCreator || "").toString();
  const role = (window.options && window.options.role) ? window.options.role : ((me===host) ? "host" : "audience");

  // If we don't have basics, do nothing (safety)
  if (!channel || !me) return;

  const ui = document.getElementById('opSeatUI');
  const asHost = document.getElementById('opSeatForHost');
  const asViewer = document.getElementById('opSeatForViewer');
  const btnReq = document.getElementById('opRequestSeat');
  const chip = document.getElementById('opSeatStatus');
  const btnList = document.getElementById('opRefreshRequests');
  const list = document.getElementById('opRequestsList');

  ui.style.display = 'block';
  if (role === 'host') {
    asHost.style.display = 'block';
  } else {
    asViewer.style.display = 'block';
  }

  async function api(action, extra){
    const params = new URLSearchParams(Object.assign({
      action, channel
    }, extra||{}));
    const res = await fetch('/requests/live_seat_api.php?' + params.toString(), { credentials:'same-origin' });
    return res.json().catch(()=>({ok:false}));
  }

  // Viewer flow: request + poll for approval → upgrade role and publish
  if (role !== 'host') {
    btnReq?.addEventListener('click', async ()=>{
      const r = await api('request', { requester_uid: me });
      if (r && r.ok) {
        chip.style.display = 'inline-block';
        // start polling approval
        startPoll();
      } else {
        alert('Could not send request');
      }
    });

    let pollTimer = null;
    async function pollOnce(){
      const r = await api('poll', { requester_uid: me });
      if (r && r.ok && r.approved) {
        chip.textContent = 'Approved! Going live…';
        // Upgrade to host role and publish tracks using your existing RTC client
        try {
          if (window.client || (window.rtcClient && window.rtcClient.setClientRole)) {
           const rtc = window.rtcClient || window.client;
await rtc.setClientRole('host');
const mic = await AgoraRTC.createMicrophoneAudioTrack();
const cam = await AgoraRTC.createCameraVideoTrack();
window.localTracks = window.localTracks || {};
window.localTracks.mic = mic; 
window.localTracks.cam = cam;

// show my own camera in the main player area
try {
  cam.play("local-player", { fit: "cover" });
} catch(e){ console.warn("local preview play failed", e); }

// publish to the channel
await rtc.publish([mic, cam]);
// Make sure we render anyone already publishing (host, other co-hosts)
try {
  const users = rtc.remoteUsers || [];
  for (const u of users) {
    try { await rtc.subscribe(u, "video"); } catch(e){}
    try { await rtc.subscribe(u, "audio"); } catch(e){}
    if (typeof ensureRemoteTileAndPlay === "function") ensureRemoteTileAndPlay(u);
  }
} catch(e){ console.warn("resubscribe after upgrade failed", e); }

          }
        } catch(e){ console.warn('Publish failed:', e); }
        clearInterval(pollTimer);
      }
    }
    function startPoll(){
      if (pollTimer) return;
      pollTimer = setInterval(pollOnce, 2500);
    }
  }

  // Host flow: list & approve
  if (role === 'host') {
    btnList?.addEventListener('click', async ()=>{
      list.innerHTML = '<div class="op-chip">Loading…</div>';
      const r = await api('list', { host_uid: host });
      if (!(r && r.ok)) { list.innerHTML = '<div class="op-chip">Error</div>'; return; }
      if (!r.pending || !r.pending.length) {
        list.innerHTML = '<div class="op-chip">No pending</div>'; return;
      }
      list.innerHTML = '';
      r.pending.forEach(req=>{
        const row = document.createElement('div');
        row.className = 'op-seat-item';
        row.innerHTML = `
          <div>UID: <strong>${req.uid}</strong> <span class="op-chip">pending</span></div>
          <button data-uid="${req.uid}">Approve</button>
        `;
        list.appendChild(row);
      });
      list.querySelectorAll('button[data-uid]').forEach(btn=>{
        btn.addEventListener('click', async ()=>{
          const uid = btn.getAttribute('data-uid');
          const rr = await api('approve', { target_uid: uid });
          if (rr && rr.ok) {
            btn.disabled = true;
            btn.textContent = 'Approved';
          } else {
            alert('Approve failed');
          }
        });
      });
    });
  }
})();
</script>

<?php
/* ---------- ONE-TIME “LIVE NOW” NOTIFICATIONS (followers + subscribers) ---------- */
$__log = function($m){
  @file_put_contents(__DIR__.'/paid_notif.log', date('c').' '.$m.PHP_EOL, FILE_APPEND);
};

try {
  $dbc = isset($iN->db) ? $iN->db : (isset($db) ? $db : null);
  if ($dbc && (int)$userID === (int)$liveCreator && !empty($theLiveID)) {

    $lid     = (int)$theLiveID;
    $creator = (int)$liveCreator;
    $t       = time();

    /* skip if already sent for this live_id */
    $already = 0;
    if ($dbc instanceof PDO) {
      $s = $dbc->prepare("SELECT COUNT(*) FROM i_user_notifications
                           WHERE not_iuid=:u AND not_type='live' AND not_not_type='live_now' AND not_post_id=:lid");
      $s->execute([':u'=>$creator, ':lid'=>$lid]); $already = (int)$s->fetchColumn();
    } else {
      $q = $dbc->query("SELECT COUNT(*) c FROM i_user_notifications
                           WHERE not_iuid={$creator} AND not_type='live' AND not_not_type='live_now' AND not_post_id={$lid}");
      $r = $q ? $q->fetch_assoc() : null; $already = (int)($r['c'] ?? 0);
    }

    if (!$already) {
      /* recipient column */
      $recipientCol = 'not_own_iuid';
      if ($dbc instanceof PDO) {
        $cols = $dbc->query("SHOW COLUMNS FROM i_user_notifications")->fetchAll(PDO::FETCH_COLUMN,0);
        foreach (['not_own_iuid','not_u_id','not_to_uid','u_id','uid_to','receiver_id','to_uid'] as $c) {
          if (in_array($c,$cols,true)) { $recipientCol = $c; break; }
        }
      } else {
        if ($cols = $dbc->query("SHOW COLUMNS FROM i_user_notifications")) {
          while ($c = $cols->fetch_assoc()) {
            $f = $c['Field'];
            if (in_array($f,['not_own_iuid','not_u_id','not_to_uid','u_id','uid_to','receiver_id','to_uid'],true)) { $recipientCol = $f; break; }
          }
        }
      }

      /* followers + subscribers */
      $followers = [];
      if ($dbc instanceof PDO) {
        $s = $dbc->prepare("SELECT fr_one FROM i_friends WHERE fr_two=:c AND fr_status IN ('flwr','subscriber')");
        $s->execute([':c'=>$creator]);
        $followers = array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN));
      } else {
        $rs = $dbc->query("SELECT fr_one FROM i_friends WHERE fr_two={$creator} AND fr_status IN ('flwr','subscriber')");
        if ($rs) { while($r=$rs->fetch_row()){ $followers[] = (int)$r[0]; } }
      }
      $followers = array_values(array_unique(array_filter($followers, fn($x)=>$x>0 && $x!=$creator)));

      /* insert */
      if (!empty($followers)) {
        if ($dbc instanceof PDO) {
          $exists = $dbc->prepare("SELECT not_id FROM i_user_notifications
                                      WHERE not_iuid=:from AND {$recipientCol}=:to
                                        AND not_type='live' AND not_not_type='live_now'
                                        AND not_post_id=:lid LIMIT 1");
          $ins = $dbc->prepare("INSERT INTO i_user_notifications
                                   (not_iuid, {$recipientCol}, not_type, not_not_type, not_post_id, not_time, not_status)
                                 VALUES
                                   (:from, :to, 'live', 'live_now', :lid, :t, '0')");
          foreach ($followers as $to) {
            $exists->execute([':from'=>$creator, ':to'=>$to, ':lid'=>$lid]);
            if ($exists->fetchColumn()) continue;
            $ins->execute([':from'=>$creator, ':to'=>$to, ':lid'=>$lid, ':t'=>$t]);
          }
        } else {
          foreach ($followers as $to) {
            $ex = $dbc->query("SELECT not_id FROM i_user_notifications
                                WHERE not_iuid={$creator} AND {$recipientCol}={$to}
                                  AND not_type='live' AND not_not_type='live_now'
                                  AND not_post_id={$lid} LIMIT 1");
            if ($ex && $ex->num_rows) continue;
            $dbc->query("INSERT INTO i_user_notifications
                           (not_iuid, {$recipientCol}, not_type, not_not_type, not_post_id, not_time, not_status)
                         VALUES ({$creator}, {$to}, 'live', 'live_now', {$lid}, {$t}, '0')");
          }
        }

        /* flip unread badge */
        $in = implode(',', array_map('intval',$followers));
        if ($in) { $dbc->query("UPDATE i_users SET notification_read_status='1' WHERE iuid IN ($in)"); }
      }

      $__log("sent live_now for live_id={$lid} to ".count($followers)." users");
    }
  }
} catch (Throwable $e) {
  $__log("ERROR ".$e->getMessage());
}
?>

<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
<script>
  /* autoplay/inline & enforce object-fit on any newly added videos */
  (function(){
    function bless(v){
      if(!v) return;
      v.setAttribute('playsinline',''); v.setAttribute('webkit-playsinline','');
      v.autoplay = true; v.muted = true;
      v.style.objectFit='cover'; v.style.width='100%'; v.style.height='100%';
      v.play && v.play().catch(()=>{});
    }
    const mo=new MutationObserver(m=>m.forEach(x=>x.addedNodes.forEach(n=>{
      if(n.tagName==='VIDEO') bless(n);
      if(n.querySelectorAll) n.querySelectorAll('video').forEach(bless);
    })));
    mo.observe(document.body,{childList:true,subtree:true});
    document.addEventListener('click',function once(){
      document.querySelectorAll('video').forEach(v=>v.play && v.play().catch(()=>{}));
      document.removeEventListener('click',once,true);
    },true);
  })();
</script>
<script src="<?php echo $base_url; ?>themes/<?php echo $currentTheme; ?>/js/liveStreamingFullHandler.js?v=<?php echo $version; ?>"></script>

<?php } else { /* Not purchased -> paywall */ ?>
<div class="live_wrapper">
  <div class="i_liv_stream_video relativePosition backgroundBlack">
    <div class="i_modal_bg_in i_modal_display_in">
      <div class="i_modal_in_in i_sf_box">
        <div class="i_modal_content">
          <div class="purchase_premium_header flex_ tabing border_top_radius">
            <?php echo iN_HelpSecure($LANG['join_the_live_broadcast']);?>
          </div>
          <div class="purchase_post_details">
            <div class="wallet-debit-confirm-container flex_">
              <div class="owner_avatar" style="background-image:url(<?php echo iN_HelpSecure($liveCreatorAvatar, FILTER_VALIDATE_URL);?>);"></div>
              <div class="album-details"><?php echo iN_HelpSecure($LANG['paying_point_for_live_streaming']);?></div>
              <div class="album-wanted-point">
                <div><?php echo html_entity_decode($liveCredit);?></div>
                <span><?php echo iN_HelpSecure($LANG['points']);?></span>
              </div>
            </div>
          </div>
          <div class="i_modal_g_footer">
            <div class="alertBtnRight joinLiveStream transition" id="<?php echo iN_HelpSecure($liveID); ?>">
              <?php echo iN_HelpSecure($LANG['confirm']);?>
            </div>
            <div id="cancel-live-purchase" class="alertBtnLeft no-del transition"><?php echo iN_HelpSecure($LANG['cancel']);?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>
