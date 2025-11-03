<?php
/* --- Ensure UI mirrors the DB exactly --- */
$me   = (int)$userID;
$urow = null;
if ($me) {
  $q = @mysqli_query($db, "SELECT
      IFNULL(sub_week_status,0)  AS w,
      IFNULL(sub_month_status,0) AS m,
      IFNULL(sub_year_status,0)  AS y,
      sub_week_amount  AS wa,
      sub_month_amount AS ma,
      sub_year_amount  AS ya
    FROM i_users WHERE iuid='$me' LIMIT 1");
  $urow = $q ? mysqli_fetch_assoc($q) : null;
}
$wOn  = !empty($urow) ? ((int)$urow['w'] === 1) : false;
$mOn  = !empty($urow) ? ((int)$urow['m'] === 1) : false;
$yOn  = !empty($urow) ? ((int)$urow['y'] === 1) : false;

$wAmt = !empty($urow) && $urow['wa'] !== null && $urow['wa'] !== '' ? $urow['wa'] :
         (isset($WeeklySubDetail['amount'])  ? $WeeklySubDetail['amount']  : '');
$mAmt = !empty($urow) && $urow['ma'] !== null && $urow['ma'] !== '' ? $urow['ma'] :
         (isset($MonthlySubDetail['amount']) ? $MonthlySubDetail['amount'] : '');
$yAmt = !empty($urow) && $urow['ya'] !== null && $urow['ya'] !== '' ? $urow['ya'] :
         (isset($YearlySubDetail['amount'])  ? $YearlySubDetail['amount']  : '');
?>
<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in i_inline_table">
     <div class="i_settings_wrapper_title">
       <div class="i_settings_wrapper_title_txt flex_"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('77'));?><?php echo iN_HelpSecure($LANG['setup_subscribers_fee']);?></div>
    </div>
    <div class="i_settings_wrapper_items">
    <div class="payouts_form_container">
   <div class="i_payout_methods_form_container">
   <div class="certification_form_not"><?php echo html_entity_decode($LANG['short_point_earning_calculation']);?></div>

   <!-- NOTE: Max price hint (doesn't change any logic, just informs users) -->
   <div class="i_t_warning" style="margin:8px 0 12px">
     Max price per plan is <b>500 points</b>. If you enter more, it will be capped to 500.
   </div>

   <div class="i_subscription_form_container">

   <?php if($subWeekStatus == 'yes'){?>
    <!--SET SUBSCRIPTION FEE BOX-->
    <div class="i_set_subscription_fee_box">
        <div class="i_sub_not">
           <?php echo iN_HelpSecure($LANG['weekly_subs_fee']);?> <span class="weekly_success"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></span>
        </div>
        <div class="i_sub_not_check">
           <?php echo iN_HelpSecure($LANG['weekly_subs_fee_not']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow">
                    <input type="checkbox" name="weekly" <?php echo $wOn ? 'checked="checked"' : ''; ?>>
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_t_warning" id="wweekly"><?php echo iN_HelpSecure($LANG['must_specify_weekly_subscription_fee_point']);?></div>
        <div class="i_t_warning" id="waweekly"><?php echo iN_HelpSecure($LANG['minimum_weekly_subscription_fee_point']);?></div>
        <div class="i_set_subscription_fee">
           <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40'));?></div>
           <div class="i_subs_price">
                <input type="text" class="transition paval" id="spweek"
                       placeholder="<?php echo iN_HelpSecure($LANG['weekly_subs_ex_fee']);?>"
                       onkeypress='return event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)'
                       inputmode="decimal" data-max="500"
                       value="<?php echo iN_HelpSecure($wAmt); ?>">
           </div>
           <div class="i_subs_interval"><?php echo iN_HelpSecure($LANG['weekly']);?></div>
        </div>
        <div class="i_t_warning_earning weekly_earning"><?php echo iN_HelpSecure($LANG['potential_gain']);?> <?php echo iN_HelpSecure($currencys[$defaultCurrency]);?><span id="weekly_earning"></span></div>
    </div>
    <!--/SET SUBSCRIPTION FEE BOX-->
    <?php }?>

    <?php if($subMontlyStatus == 'yes'){?>
    <!--SET SUBSCRIPTION FEE BOX-->
    <div class="i_set_subscription_fee_box">
        <div class="i_sub_not">
        <?php echo iN_HelpSecure($LANG['monthly_subs_fee']);?><span class="monthly_success"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></span>
        </div>
        <div class="i_sub_not_check">
        <?php echo iN_HelpSecure($LANG['monthly_subs_fee_not']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow">
                    <input type="checkbox" name="monthly" <?php echo $mOn ? 'checked="checked"' : ''; ?>>
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>
        <div class="i_t_warning" id="wmonthly"><?php echo iN_HelpSecure($LANG['must_specify_monthly_subscription_fee_point']);?></div>
        <div class="i_t_warning" id="mamonthly"><?php echo iN_HelpSecure($LANG['minimum_monthly_subscription_fee_point']);?></div>
        <div class="i_set_subscription_fee">
           <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40'));?></div>
           <div class="i_subs_price">
                <input type="text" class="transition paval" id="spmonth"
                       placeholder="<?php echo iN_HelpSecure($LANG['monthly_subs_ex_fee']);?>"
                       onkeypress='return event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)'
                       inputmode="decimal" data-max="500"
                       value="<?php echo iN_HelpSecure($mAmt); ?>">
           </div>
           <div class="i_subs_interval"><?php echo iN_HelpSecure($LANG['monthly']);?></div>
        </div>
        <div class="i_t_warning_earning mamonthly_earning"><?php echo iN_HelpSecure($LANG['potential_gain']);?> <?php echo iN_HelpSecure($currencys[$defaultCurrency]);?><span id="mamonthly_earning"></span></div>
    </div>
    <!--/SET SUBSCRIPTION FEE BOX-->
    <?php }?>

    <?php if($subYearlyStatus == 'yes'){?>
    <!--SET SUBSCRIPTION FEE BOX-->
    <div class="i_set_subscription_fee_box">
        <div class="i_sub_not">
        <?php echo iN_HelpSecure($LANG['yearly_subs_fee']);?><span class="yearly_success"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('69'));?></span>
        </div>
        <div class="i_sub_not_check">
           <?php echo iN_HelpSecure($LANG['yearly_subs_fee_not']);?>
           <div class="i_sub_not_check_box">
                <label class="el-switch el-switch-yellow">
                    <input type="checkbox" name="yearly" <?php echo $yOn ? 'checked="checked"' : ''; ?>>
                    <span class="el-switch-style"></span>
                </label>
           </div>
        </div>

        <div class="i_t_warning" id="wyearly"><?php echo iN_HelpSecure($LANG['must_specify_yearly_subscription_fee_point']);?></div>
        <div class="i_t_warning" id="yayearly"><?php echo iN_HelpSecure($LANG['minimum_yearly_subscription_fee_point']);?></div>
        <div class="i_set_subscription_fee">
           <div class="i_subs_currency"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40'));?></div>
           <div class="i_subs_price">
                <input type="text" class="transition paval" id="spyear"
                       placeholder="<?php echo iN_HelpSecure($LANG['yearly_subs_ex_fee']);?>"
                       onkeypress='return event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)'
                       inputmode="decimal" data-max="500"
                       value="<?php echo iN_HelpSecure($yAmt); ?>">
           </div>
           <div class="i_subs_interval"><?php echo iN_HelpSecure($LANG['yearly']);?></div>
        </div>
        <div class="i_t_warning_earning yayearly_earning"><?php echo iN_HelpSecure($LANG['potential_gain']);?> <?php echo iN_HelpSecure($currencys[$defaultCurrency]);?><span id="yayearly_earning"></span></div>
    </div>
    <!--/SET SUBSCRIPTION FEE BOX-->
    <?php }?>

   </div><!--/.i_subscription_form_container-->
   </div>
</div>
    </div>
    <div class="i_settings_wrapper_item successNot">
        <?php echo iN_HelpSecure($LANG['payment_settings_updated_success'])?>
    </div>
     <div class="i_become_creator_box_footer tabing">
        <div class="i_nex_btn c_pNext transition"><?php echo iN_HelpSecure($LANG['save_edit']);?></div>
     </div>
  </div>
</div>

<script>
window.subscriptionData = {
  weeklyMin: <?php echo iN_HelpSecure($minPointFeeWeekly); ?>,
  monthlyMin: <?php echo iN_HelpSecure($minPointFeeMonthly); ?>,
  yearlyMin: <?php echo iN_HelpSecure($minPointFeeYearly); ?>,
  onePointEqual: <?php echo iN_HelpSecure($onePointEqual); ?>,
  adminFee: <?php echo iN_HelpSecure($adminFee / 100); ?>
};
</script>
<script src="<?php echo $base_url; ?>themes/<?php echo $currentTheme; ?>/js/subscriptionFeesHandler.js?v=<?php echo iN_HelpSecure(time());?>" defer></script>
