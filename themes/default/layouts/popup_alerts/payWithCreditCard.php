<?php if($subscriptionType == '1'){?>
<div class="i_modal_bg_in i_subs_modal pay_zindex">
    <!--SHARE-->
   <div class="i_modal_in_in i_payment_pop_box">
       <div class="i_modal_content">
           <div class="payClose transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5'));?></div>
           <!--Subscribing Avatar-->
           <div class="i_subscribing" style="background-image:url(<?php echo iN_HelpSecure($f_profileAvatar);?>);"></div>
           <div class="i_subscribing_note" id="pln" data-p="<?php echo iN_HelpSecure($planID);?>">
              <?php echo preg_replace( '/{.*?}/', $f_userfullname, $LANG['subscription_payment']); ?>
           </div>
           <form id="paymentFrm">
           <div class="i_credit_card_form">
                <div id="paymentResponse"></div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['card_holder']);?></label>
                    <div class="form-control">
                        <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('70'));?></div>
                       <input type="text" id="name" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['card_holder']);?>">
                    </div>
                </div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['email']);?></label>
                    <div class="form-control">
                       <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('71'));?></div>
                       <input type="text" id="email" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['email']);?>">
                    </div>
                </div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['card_number']);?></label>
                    <div class="form-control">
                       <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('72'));?></div>
                       <div id="card_number" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['card_number']);?>"></div>
                    </div>
                </div>
                <div class="pay_form_group_plus">
                    <div class="i_form_group_plus">
                        <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['expiration_date']);?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73'));?></div>
                            <div id="card_expiry" class="inora_user_input" placeholder="DD/YY"></div>
                        </div>
                    </div>
                    <div class="i_form_group_plus">
                        <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['ccv_code']);?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('74'));?></div>
                            <div id="card_cvc" class="inora_user_input" placeholder="123"></div>
                        </div>
                    </div>
                </div>
                <div class="pay_form_group">
                    <div class="pay_subscription transition"><?php echo iN_HelpSecure($LANG['pay']);?> <?php echo iN_HelpSecure($currencys[$stripeCurrency]).$f_PlanAmount;?></div>
                </div>
                <div class="pay_form_group">
                   <div class="i_pay_note">
                       <?php echo iN_HelpSecure($LANG['subscription_renew']);?>
                   </div>
                </div>
           </div>
           </form>
       </div>
   </div>
<script>
    window.payWithCardData = {
        stripePublicKey: "<?php echo iN_HelpSecure($stripePublicKey); ?>",
        siteurl: "<?php echo iN_HelpSecure($base_url); ?>",
        planID: "<?php echo iN_HelpSecure($planID); ?>",
        userID: "<?php echo iN_HelpSecure($userID); ?>",
        lightDark: "<?php echo iN_HelpSecure($lightDark); ?>"
    };
</script>
<script src="https://js.stripe.com/v3"></script> 
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/payWithCreditCard.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
</div>
<?php }else if($subscriptionType == '3'){?>
<script>
    window.manualCardData = {
        siteurl: "<?php echo iN_HelpSecure($base_url); ?>",
        planID: "<?php echo iN_HelpSecure($planID); ?>",
        userID: "<?php echo iN_HelpSecure($userID); ?>"
    };
</script> 
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/manualCreditCard.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
<!--CREDIT CARD FORM-->
<div class="i_moda_bg_in_form i_subs_modal i_modal_display_in pay_zindex">
   <div class="i_modal_in_in i_payment_pop_box">
       <div class="i_modal_content">
           <div class="payClose transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('5'));?></div>
           <div class="i_subscribing" style="background-image:url(<?php echo iN_HelpSecure($f_profileAvatar);?>);"></div>
           <div class="i_subscribing_note" id="pln" data-p="<?php echo iN_HelpSecure($planID);?>">
              <?php echo preg_replace( '/{.*?}/', $f_userfullname, $LANG['subscription_payment']); ?>
           </div>
           <form id="paymentFrm">
           <div class="i_credit_card_form">
                <div id="paymentResponse"></div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['card_holder']);?></label>
                    <div class="form-control">
                        <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('70'));?></div>
                       <input type="text" id="cname" name="cardname" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['card_holder']);?>">
                    </div>
                </div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['email']);?></label>
                    <div class="form-control">
                       <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('71'));?></div>
                       <input type="text" id="email" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['email']);?>">
                    </div>
                </div>
                <div class="pay_form_group">
                    <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['card_number']);?></label>
                    <div class="form-control">
                       <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('72'));?></div>
                       <input type="text" id="cardNumber" name="cardnumber" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['card_number']);?>">
                    </div>
                </div>
                <div class="pay_form_group_plus">
                    <div class="i_form_group_plus_extra">
                        <label class="form_label"><?php echo iN_HelpSecure($LANG['expiration_date']);?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73'));?></div>
                            <input type="text" id="expmonth" name="expmonth" class="inora_user_input" placeholder="DD">
                        </div>
                    </div>
                    <div class="i_form_group_plus_extra">
                        <label class="form_label"><?php echo iN_HelpSecure($LANG['expiration_year']);?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('73'));?></div>
                            <input type="text" id="expyear" name="expyear" class="inora_user_input" placeholder="YY">
                        </div>
                    </div>
                    <div class="i_form_group_plus_extra">
                        <label class="form_label"><?php echo iN_HelpSecure($LANG['ccv_code']);?></label>
                        <div class="form-control">
                            <div class="form_control_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('74'));?></div>
                            <input type="text" id="cvv" name="cvv" class="inora_user_input" placeholder="123">
                        </div>
                    </div>
                </div>
                <div class="pay_form_group">
                    <div class="pay_subscription transition"><?php echo iN_HelpSecure($LANG['pay']);?> <?php echo iN_HelpSecure($currencys[$stripeCurrency]).$f_PlanAmount;?></div>
                </div>
                <div class="pay_form_group">
                   <div class="i_pay_note"><?php echo iN_HelpSecure($LANG['subscription_renew']);?></div>
                </div>
           </div>
           </form>
           <script src="https://js.stripe.com/v3"></script>
       </div>
   </div>
</div>
<?php } ?>