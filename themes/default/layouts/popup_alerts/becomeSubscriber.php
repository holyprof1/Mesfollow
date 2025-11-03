<?php
/* Cash subscription popup (Monthly + Yearly) – formal layout + working .bcmSubs buttons.
 * IMPORTANT: the buttons include data-amount, data-interval, data-currency
 * because your Paystack checkout script reads those values.
 */
?>
<div class="i_modal_bg_in" role="dialog" aria-modal="true" aria-labelledby="subscriptionModalTitle">
  <div class="i_modal_in_in i_sf_box">
    <div class="i_modal_content">
      <!-- Cover + avatar -->
      <div class="i_f_cover_avatar" style="background-image:url('<?php echo iN_HelpSecure($f_profileCover);?>');">
        <div class="popClose transition" role="button" aria-label="Close">
          <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5'));?>
        </div>
        <div class="i_f_avatar_container">
          <div class="i_f_avatar" style="background-image:url('<?php echo iN_HelpSecure($f_profileAvatar);?>');"></div>
        </div>
      </div>

      <!-- Details -->
      <div class="i_f_other" id="pr_u_id">
        <div class="i_u_name" id="subscriptionModalTitle">
          <a href="<?php echo iN_HelpSecure($fprofileUrl);?>">
            <?php echo iN_HelpSecure($f_userfullname);?><?php echo html_entity_decode($fVerifyStatus);?> <?php echo html_entity_decode($fGender);?>
          </a>
        </div>
        <div class="i_u_name_mention">
          <a href="<?php echo iN_HelpSecure($fprofileUrl);?>">@<?php echo iN_HelpSecure($f_username);?></a>
        </div>

        <div class="support_not">
          <?php echo iN_HelpSecure(preg_replace('/{.*?}/', $f_userfullname, $LANG['subscribeNot']));?>
        </div>

        <div class="i_s_popup_title_dark"><?php echo iN_HelpSecure($LANG['avantages_of_subscription']);?></div>
        <div class="i_advantages_wrapper">
          <div class="avantage_box"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?> <?php echo iN_HelpSecure($LANG['unblock_all_fan_contents']);?></div>
          <div class="avantage_box"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?> <?php echo iN_HelpSecure($LANG['full_acces_my_conent']);?></div>
          <div class="avantage_box"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?> <?php echo iN_HelpSecure($LANG['direct_message_me']);?></div>
          <div class="avantage_box"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?> <?php echo iN_HelpSecure($LANG['cancel_subs_any_time']);?></div>
        </div>

        <div class="i_s_popup_title_dark_offers"><?php echo iN_HelpSecure($LANG['offers']);?></div>

        <?php
        // Build Monthly/Yearly offers
        $offers = [];
        if ($rows = $iN->iN_UserSusbscriptionOffers($f_userID)) {
          foreach ($rows as $u) {
            if ($u['plan_type'] === 'monthly' || $u['plan_type'] === 'yearly') {
              $offers[] = [
                'plan_id'   => $u['plan_id'],
                'amount'    => $u['amount'],
                'plan_type' => $u['plan_type']
              ];
            }
          }
        }
        if (empty($offers)) {
          global $db;
          $uid = mysqli_real_escape_string($db, (string)$f_userID);
          $q = @mysqli_query($db, "SELECT
              IFNULL(sub_month_status,0) mS, IFNULL(sub_month_amount,'') mA,
              IFNULL(sub_year_status,0)  yS, IFNULL(sub_year_amount,'')  yA
            FROM i_users WHERE iuid='$uid' LIMIT 1");
          if ($q && ($r = mysqli_fetch_assoc($q))) {
            if ((int)$r['mS'] === 1 && $r['mA'] !== '') $offers[] = ['plan_id'=>"month-$uid",'amount'=>$r['mA'],'plan_type'=>'monthly'];
            if ((int)$r['yS'] === 1 && $r['yA'] !== '') $offers[] = ['plan_id'=>"year-$uid", 'amount'=>$r['yA'],'plan_type'=>'yearly'];
          }
        }

        $noOffersText = $LANG['no_offers_available'] ?? 'No offers available yet.';
        $currencyCode = strtoupper($defaultCurrency ?? 'NGN'); // used only as a label + data attribute

        if (!empty($offers)) {
          // Monthly first, then Yearly
          usort($offers, function($a,$b){ $rank=['monthly'=>1,'yearly'=>2]; return ($rank[$a['plan_type']]??9) <=> ($rank[$b['plan_type']]??9); });
          foreach ($offers as $o) {
            $interval = $o['plan_type'];
            $amount   = (float)$o['amount'];
            ?>
            <div class="i_prices_subscribe">
              <div
                class="subscribe_price_btn bcmSubs"
                id="<?php echo iN_HelpSecure($o['plan_id']); ?>"
                data-u="<?php echo iN_HelpSecure($f_userID); ?>"
                data-amount="<?php echo iN_HelpSecure($amount); ?>"
                data-interval="<?php echo iN_HelpSecure($interval); ?>"
                data-currency="<?php echo iN_HelpSecure($currencyCode); ?>"
              >
                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('51')); ?>
                <?php echo iN_HelpSecure($LANG['subscribe'] ?? 'Subscribe'); ?>
                – <?php echo iN_HelpSecure($currencyCode.' '.$amount); ?> /
                <?php echo iN_HelpSecure($LANG[$interval] ?? ucfirst($interval)); ?>
              </div>
            </div>
          <?php }
        } else { ?>
          <div class="i_prices_subscribe">
            <div class="subscribe_price_btn disabled"><?php echo iN_HelpSecure($noOffersText);?></div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
