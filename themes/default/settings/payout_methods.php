<?php
// Get the user's current payout details
$currentUserDetails = $iN->iN_GetUserDetails($userID);
$currentMethodKey   = $currentUserDetails['payout_method'] ?? null;
$currentFullDetails = $currentUserDetails['bank_account'] ?? '';
$userAvatar         = $iN->iN_UserAvatar($userID, $base_url);
$userFullName       = $currentUserDetails['i_user_fullname'];

// Parse the details string e.g., "Bank / Full name: … | Account/IBAN: …"
$detailsParts        = explode(' / ', $currentFullDetails, 2);
$currentDetailsOnly  = $detailsParts[1] ?? $currentFullDetails;

// Local-friendly labels (CI-first)
$paymentMethods = [
    'wave'   => ['label' => 'Wave',          'icon' => 'wave.png',  'placeholder' => 'Enter your Wave number'],
    'orange' => ['label' => 'Orange Money',  'icon' => 'orange.png','placeholder' => 'Enter your Orange Money number'],
    'mtn'    => ['label' => 'MTN MoMo',      'icon' => 'mtn.png',   'placeholder' => 'Enter your MTN MoMo number'],
    'bank'   => ['label' => 'Bank Transfer', 'icon' => 'bank.png',  'placeholder' => 'Enter your Bank Details'],
];
?>
<style>
/* Cleaner Form Styles */
.payout_section_title { font-size: 16px; font-weight: 600; color: #555; margin-bottom: 10px; padding-left: 5px; }
.payout_icon_wrapper { display: flex; align-items: center; gap: 10px; margin-bottom: 25px; padding-left: 5px; }
.payout_icon { padding: 2px; cursor: pointer; border: 2px solid transparent; border-radius: 8px; transition: all 0.2s ease-in-out; }
.payout_icon img { height: 35px; display: block; }
.payout_icon.selected { border-color: #BC14A5; }
.payout_input_container { display: none; }

/* Cleaner Display View Styles */
.payout_display_container { border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; }
.payout_display_header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; }
.payout_display_header .user_info { display: flex; align-items: center; gap: 12px; }
.payout_display_header .user_avatar { width: 45px; height: 45px; border-radius: 50%; background-size: cover; background-position: center; }
.payout_display_header .user_name { font-weight: 600; font-size: 18px; }
.payout_display_header .title { font-weight: bold; font-size: 20px; }
.payout_display_details .detail_row { padding: 12px 0; font-size: 15px; align-items: flex-start; border-bottom: 1px dashed #eee; display: grid; grid-template-columns: 220px 1fr; gap: 12px; }
.payout_display_details .detail_row:last-child { border-bottom: 0; }
.payout_display_details .detail_label { color: #666; min-width: 180px; }
.payout_display_details .detail_value { font-weight: 500; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.payout_display_details .detail_value img { height: 24px; width: 32px; object-fit: contain; }
.payout_edit_button_wrapper { margin-top: 20px; text-align: right; }

/* Bank fields layout (form) */
.bank_fields_container{display:none;margin-top:8px}
.bank-grid{ display:grid; gap:10px; grid-template-columns: 1fr; }
.bank-grid label{ font-size:13px;color:#555 }
.bank-grid input, .bank-grid textarea, .bank-grid select{ width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:14px }
@media(min-width:720px){
  .bank-grid{grid-template-columns:1fr 1fr}
  .bank-grid .col-span-2{grid-column:1 / -1}
}
</style>

<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in i_inline_table">
    <div class="i_settings_wrapper_title">
      <div class="i_settings_wrapper_title_txt flex_">
        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('77'));?><?php echo iN_HelpSecure($LANG['payout_methods']);?>
      </div>
    </div>

    <div class="i_settings_wrapper_items">

      <!-- ===== DISPLAY VIEW ===== -->
      <div id="payout-display-view" style="<?php echo (empty($currentMethodKey) || empty($currentFullDetails)) ? 'display:none;' : 'display:block;'; ?>">
        <div class="payout_display_container">
          <div class="payout_display_header">
            <div class="user_info">
              <div class="user_avatar" style="background-image:url('<?php echo $userAvatar; ?>');"></div>
              <div class="user_name"><?php echo iN_HelpSecure($userFullName); ?></div>
            </div>
            <div class="title"><?php echo iN_HelpSecure($LANG['payout_details'] ?? 'Payout Details'); ?></div>
          </div>

          <div class="payout_display_details">
            <!-- Always show Method -->
            <div class="detail_row">
              <span class="detail_label"><?php echo iN_HelpSecure($LANG['payment_method'] ?? 'Payment Method'); ?></span>
              <span class="detail_value"><?php echo iN_HelpSecure($paymentMethods[$currentMethodKey]['label'] ?? 'N/A'); ?></span>
            </div>

            <?php if ($currentMethodKey === 'bank'): ?>
              <?php
                // Expecting a string like: "Full name: … | Beneficiary address: … | Bank name: … | Bank address: … | Account/IBAN: … | SWIFT/BIC: … | Currency: …"
                $rows = [];
                $pairs = array_map('trim', explode('|', $currentDetailsOnly));
                foreach ($pairs as $pair) {
                    if (!$pair) continue;
                    $kv = explode(':', $pair, 2);
                    $k  = trim($kv[0] ?? '');
                    $v  = trim($kv[1] ?? '');
                    if ($k && $v) {
                        $rows[] = ['label' => $k, 'value' => $v];
                    }
                }
              ?>
              <?php if (!empty($rows)): ?>
                <?php foreach ($rows as $r): ?>
                  <div class="detail_row">
                    <span class="detail_label"><?php echo iN_HelpSecure($r['label']); ?></span>
                    <span class="detail_value"><?php echo iN_HelpSecure($r['value']); ?></span>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Fallback to the raw address if parsing fails -->
                <div class="detail_row">
                  <span class="detail_label"><?php echo iN_HelpSecure($LANG['payment_address'] ?? 'Payment Address'); ?></span>
                  <span class="detail_value"><?php echo iN_HelpSecure($currentDetailsOnly); ?></span>
                </div>
              <?php endif; ?>

            <?php else: ?>
              <!-- Wallets (Wave / Orange Money / MTN MoMo): show single address with icon -->
              <div class="detail_row">
                <span class="detail_label"><?php echo iN_HelpSecure($LANG['payment_address'] ?? 'Payment Address'); ?></span>
                <span class="detail_value">
                  <img src="<?php echo $base_url . 'img/payments/' . ($paymentMethods[$currentMethodKey]['icon'] ?? ''); ?>" alt="">
                  <?php echo iN_HelpSecure($currentDetailsOnly); ?>
                </span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="payout_edit_button_wrapper">
          <div class="i_nex_btn transition" id="edit-payout-btn"><?php echo iN_HelpSecure($LANG['edit'] ?? 'Edit');?></div>
        </div>
      </div>

      <!-- ===== FORM VIEW ===== -->
      <div id="payout-form-view" style="<?php echo (empty($currentMethodKey) || empty($currentFullDetails)) ? 'display:block;' : 'display:none;'; ?>">
        <form id="payoutMethodForm">
          <input type="hidden" id="selected_method" name="method">

          <div class="payout_section_title"><?php echo iN_HelpSecure($LANG['mobile_money_wallet'] ?? 'Mobile Money / Wallet');?></div>
          <div class="payout_icon_wrapper">
            <?php foreach(['wave', 'orange', 'mtn'] as $key): ?>
              <div class="payout_icon" data-method="<?php echo $key; ?>" data-placeholder="<?php echo iN_HelpSecure($paymentMethods[$key]['placeholder']); ?>">
                <img src="<?php echo $base_url . 'img/payments/' . $paymentMethods[$key]['icon']; ?>">
              </div>
            <?php endforeach; ?>
          </div>

          <div class="payout_section_title"><?php echo iN_HelpSecure($LANG['bank_transfer'] ?? 'Bank Transfer');?></div>
          <div class="payout_icon_wrapper">
            <?php $key = 'bank'; ?>
            <div class="payout_icon" data-method="<?php echo $key; ?>" data-placeholder="<?php echo iN_HelpSecure($paymentMethods[$key]['placeholder']); ?>">
              <img src="<?php echo $base_url . 'img/payments/' . $paymentMethods[$key]['icon']; ?>">
            </div>
          </div>

          <div class="payout_input_container">
            <!-- Country code select (CI-first) -->
            <div id="country_code_container" style="display:none; margin-bottom:8px;">
              <select id="country_code" class="i_payout_" style="padding:8px; border-radius:6px; border:1px solid #ccc;">
                <option value="+225" selected>🇨🇮 Côte d’Ivoire (+225)</option>
                <option value="+221">🇸🇳 Sénégal (+221)</option>
                <option value="+229">🇧🇯 Bénin (+229)</option>
                <option value="+226">🇧🇫 Burkina Faso (+226)</option>
                <option value="+228">🇹🇬 Togo (+228)</option>
                <option value="+223">🇲🇱 Mali (+223)</option>
              </select>
            </div>

            <!-- Generic textarea (used for wallets; hidden for bank by JS) -->
            <textarea id="payout_details" class="i_payout_" rows="3" placeholder=""></textarea>
          </div>

          <!-- BANK EXPANDED FIELDS (UI only; saved as one string by JS) -->
          <div id="bank_fields_container" class="bank_fields_container">
            <div class="bank-grid">
              <div class="col-span-2">
                <label>Full name</label>
                <input type="text" id="bank_fullname" autocomplete="name" placeholder="e.g. Kouadio Yao">
              </div>

              <div class="col-span-2">
                <label>Beneficiary address</label>
                <textarea id="bank_beneficiary_address" rows="2" placeholder="e.g. Riviera Palmeraie, Cocody, Abidjan, Côte d’Ivoire"></textarea>
              </div>

              <div>
                <label>Bank name</label>
                <input type="text" id="bank_bankname" placeholder="e.g. NSIA Banque CI">
              </div>

              <div>
                <label>Bank address (city, country)</label>
                <input type="text" id="bank_bankaddress" placeholder="e.g. Abidjan, Côte d’Ivoire">
              </div>

              <div>
                <label>Bank account number or IBAN</label>
                <input type="text" id="bank_account" placeholder="e.g. 0123456789 or CI12 XXXX XXXX XXXX">
              </div>

              <div>
                <label>Bank SWIFT/BIC code</label>
                <input type="text" id="bank_swift" placeholder="e.g. NSIACIAB">
              </div>

              <div>
                <label>Currency of the transfer</label>
                <select id="bank_currency">
                  <option value="">Select currency</option>
                  <option value="XOF" selected>XOF</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                  <option value="GBP">GBP</option>
                </select>
              </div>
            </div>
          </div>

          <div class="i_t_warning" id="payoutWarning" style="display:none; margin-top:15px;">
            <?php echo iN_HelpSecure($LANG['payout_method_field_warning'] ?? 'Please select a method and fill in the details.');?>
          </div>
        </form>

        <div class="i_settings_wrapper_item successNot" style="display:none; margin-top:15px;">
          <?php echo iN_HelpSecure($LANG['payment_settings_updated_success']); ?>
        </div>
        <div class="i_become_creator_box_footer tabing">
          <div class="i_nex_btn pyot_sNext transition"><?php echo iN_HelpSecure($LANG['save_edit']);?></div>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>themes/<?php echo iN_HelpSecure($currentTheme);?>/js/payoutHandler.js?v=<?php echo time();?>"></script>
