<?php
// NOTE: We keep your OG meta/ref logic intact

?><!DOCTYPE html>
<?php
  // Decide active language for the html tag, even if not logged in
  $activeLangForHtml = isset($userLang) && $userLang ? $userLang : (
    (isset($_SESSION['lang']) && $_SESSION['lang']) ? $_SESSION['lang'] : (
      (isset($_COOKIE['lang']) && $_COOKIE['lang']) ? $_COOKIE['lang'] : ($defaultLanguage ?? 'en')
    )
  );
?>
<html lang="<?php echo iN_HelpSecure($activeLangForHtml); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle);?></title>

    <!-- Primary Meta Tags -->
    <meta name="title" content="<?php echo iN_HelpSecure($siteTitle);?>">
    <meta name="description" content="<?php echo iN_HelpSecure($siteDescription);?>">
    <meta name="keywords" content="<?php echo iN_HelpSecure($siteKeyWords);?>">

    <?php
    // ===== referral preview (kept from your original) =====
    $metaBaseUrl = $base_url;
    if(isset($_GET['ref']) && $_GET['ref'] != ''){
        $siteTitle = $LANG['ref_title'];
        $siteDescription = $LANG['ref_description'];
        $refUserName = mysqli_real_escape_string($db, $_GET['ref']);
        if ($iN->iN_CheckUserName($refUserName)){
            $refOwnerData = $iN->iN_GetUserDetailsFromUsername($refUserName);
            $refOwnerUserID = $iN->iN_Secure($refOwnerData['iuid']);
            $metaBaseUrl = $iN->iN_UserAvatar($refOwnerUserID, $base_url);
        }
    }
    ?>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo iN_HelpSecure($metaBaseUrl);?>">
    <meta property="og:title" content="<?php echo iN_HelpSecure($siteTitle);?>">
    <meta property="og:description" content="<?php echo iN_HelpSecure($siteDescription);?>">
    <meta property="og:image" content="<?php echo iN_HelpSecure($metaBaseUrl);?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo iN_HelpSecure($metaBaseUrl);?>">
    <meta property="twitter:title" content="<?php echo iN_HelpSecure($siteTitle);?>">
    <meta property="twitter:description" content="<?php echo iN_HelpSecure($siteDescription);?>">
    <meta property="twitter:image" content="<?php echo iN_HelpSecure($metaBaseUrl);?>">

    <meta name="theme-color" content="#f65169">
    <link rel="shortcut icon" type="image/png" href="<?php echo iN_HelpSecure($siteFavicon);?>" sizes="128x128">

    <?php
       // keep your asset includes
       include("layouts/header/css.php");
       include("layouts/header/javascripts.php");
    ?>

    <style>
      /* ===== SAME LOOK AS LOGIN ===== */
      *{margin:0;padding:0;box-sizing:border-box}
      body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,sans-serif;overflow-x:hidden}
      .login-page-wrapper{
        min-height:100vh; display:flex; flex-direction:column;
        background:linear-gradient(135deg,#7928CA 0%,#FF0080 100%);
        position:relative; overflow:hidden;
      }
      .login-page-wrapper::before,.login-page-wrapper::after{
        content:''; position:absolute; border-radius:50%;
        background:rgba(255,255,255,.1); animation:float 20s infinite ease-in-out;
      }
      .login-page-wrapper::before{width:500px;height:500px;top:-250px;right:-100px;animation-delay:-5s}
      .login-page-wrapper::after{width:400px;height:400px;bottom:-200px;left:-100px;animation-delay:-10s}
      @keyframes float{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-50px) scale(1.1)}}

      .login-center{flex:1;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;z-index:10;width:100%!important}
      .login-page-container{
        background:rgba(255,255,255,.98); backdrop-filter:blur(10px);
        border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,.3);
        max-width:460px; width:100%; padding:45px 40px;
        animation:slideIn .6s cubic-bezier(.16,1,.3,1); margin:0 auto!important; float:none!important;
      }
      @keyframes slideIn{from{opacity:0;transform:translateY(30px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}

      .login-page-header{text-align:center;margin-bottom:28px}
      /* use LOGO here (no celebration svg) */
      .i_login_box_wellcome_icon{margin-bottom:14px}
      .i_login_box_wellcome_icon img{height:64px;width:auto;display:inline-block}
      .i_lBack{font-size:32px;font-weight:800;color:#1a202c;margin-bottom:8px;letter-spacing:-.5px}
      .i_lnot{font-size:15px;color:#718096;font-weight:500}

      .login-title{ text-align:center; margin:24px 0 16px; position:relative; font-size:13px; color:#718096; font-weight:600 }
      .login-title span{ background:rgba(255,255,255,.98); padding:0 18px; position:relative; z-index:1 }
      .login-title::before{ content:''; position:absolute; left:0; right:0; top:50%; height:1px; background:#e2e8f0 }

      .i_social-btns{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-bottom:6px}
      .i_social-btns>div{flex:1;min-width:140px}
      .i_social-btns a{
        display:flex;align-items:center;justify-content:center;gap:10px;padding:12px 20px;
        border:2px solid #e2e8f0;border-radius:12px;text-decoration:none;color:#2d3748;
        font-size:14px;font-weight:600;transition:all .3s ease;background:#f7fafc;
      }
      .i_social-btns a:hover{border-color:#7928CA;background:#fff;transform:translateY(-2px);box-shadow:0 6px 20px rgba(121,40,202,.15)}

      .i_warns{margin:12px 0}
      .i_warns .i_error{display:none;background:#fff5f5;border:1px solid #feb2b2;color:#c53030;padding:12px 14px;border-radius:12px;font-size:14px;font-weight:500}

      .i_helper_title{font-size:14px;color:#2d3748;font-weight:700;margin:6px 0 12px;text-align:center}

      .i_settings_item_title_for.flex_{display:flex;gap:12px;flex-wrap:wrap}
      .youare input{display:none}
      .youare span{gap:8px;align-items:center;padding:10px 14px;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;background:#f7fafc;font-weight:600;color:#2d3748}
      .youare input:checked + span{border-color:#7928CA;box-shadow:0 0 0 4px rgba(121,40,202,.08);background:#fff}

      .i_re_box{flex:1;min-width:100%}
      .i_settings_item_title{font-size:13px;color:#2d3748;font-weight:600;margin-bottom:8px}
      .i_settings_item_title_for_style input.flnm{
        width:100%; padding:14px 18px; border:2px solid #e2e8f0; border-radius:12px; font-size:15px;
        transition:all .3s ease; background:#f7fafc;
      }
      .i_settings_item_title_for_style input.flnm:focus{outline:none;border-color:#7928CA;background:#fff;box-shadow:0 0 0 4px rgba(121,40,202,.1)}
      .min_with{width:100%}

      .register_warning{display:none;margin:8px 0;padding:10px 12px;border-radius:10px;font-size:13px;background:#fffbe6;border:1px solid #ffe58f;color:#8d6a00}

      .i_login_button{margin-top:18px}
      .i_login_button button{
        width:100%; padding:16px; background:linear-gradient(135deg,#7928CA 0%,#FF0080 100%);
        color:#fff; border:none; border-radius:12px; font-size:16px; font-weight:700; cursor:pointer;
        transition:all .3s ease; box-shadow:0 10px 25px rgba(121,40,202,.3); letter-spacing:.3px;
      }
      .i_login_button button:hover{transform:translateY(-2px);box-shadow:0 15px 35px rgba(121,40,202,.4)}

      /* mobile tweaks */
      @media (min-width:540px){ .i_re_box{min-width:calc(50% - 6px)} }
      @media (max-width:480px){
        .login-page-container{padding:35px 25px;margin:10px;border-radius:20px}
        .i_login_box_wellcome_icon img{height:56px}
        .i_lBack{font-size:26px}
        .i_lnot{font-size:14px}
        .i_social-btns>div{min-width:100%}
        .i_settings_item_title_for_style input.flnm{font-size:16px}
      }
    </style>
</head>
<body>
  <div class="login-page-wrapper">
    <div class="login-center">
      <div class="login-page-container">

        <!-- Header (logo instead of celebration SVG) -->
        <div class="login-page-header">
          <div class="i_login_box_wellcome_icon">
            <img src="<?php echo iN_HelpSecure($siteLogoUrl);?>" alt="<?php echo iN_HelpSecure($siteTitle);?>">
          </div>
          <div class="i_welcome_back">
            <div class="i_lBack"><?php echo iN_HelpSecure($LANG['sign_up']);?></div>
            <div class="i_lnot"><?php echo iN_HelpSecure($LANG['try_site_for_free']);?></div>
          </div>
        </div>

        <?php if($userCanRegister == '1'){
            // claim username (kept)
            $claimName = '';
            if(isset($_GET['claim']) && $_GET['claim'] != ''){
              $claimName = mysqli_real_escape_string($db,$_GET['claim']);
              $checkUserNameExist = $iN->iN_CheckUsernameExistForRegister($iN->iN_Secure($claimName));
              if($checkUserNameExist || empty($claimName)){ $claimName = ''; }
            }
        ?>

          <!-- Social login (kept, exact links as your original) -->
          <?php if($socialLoginStatus == '1'){
              $socialLogins = $iN->iN_SocialLogins();
              if($socialLogins){ ?>
                <div class="i_modal_social_login_content">
                  <div class="login-title"><span><?php echo $LANG['login-with']; ?></span></div>
                  <div class="i_social-btns">
                    <?php foreach($socialLogins as $sL){
                        $sKey  = $sL['s_key']  ?? null;
                        $sIcon = $sL['s_icon'] ?? null; ?>
                        <div>
                          <a class="<?php echo iN_HelpSecure($sKey);?>-login" href="<?php echo iN_HelpSecure($base_url).$sKey;?>Login.php">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon($sIcon));?>
                            <span><?php echo iN_HelpSecure($LANG[$sKey]);?></span>
                          </a>
                        </div>
                    <?php } ?>
                  </div>
                  <div class="login-title"><span><?php echo $LANG['or-directly']; ?></span></div>
                </div>
          <?php } } ?>

          <!-- Inline warnings (kept) -->
          <div class="i_warns"><div class="i_error"></div></div>

          <!-- “You are” gender selector (kept) -->
          <div class="i_helper_title"><?php echo iN_HelpSecure($LANG['you_are']);?></div>

          <!-- Register form (kept IDs/names so JS & backend continue working) -->
          <div class="i_direct_register i_register_box_">
            <form enctype="multipart/form-data" method="post" id="iregister" autocomplete="off">
              <div class="i_settings_item_title_for flex_">
                <div class="flexBox flex_">
                  <label class="youare" id="youarein" for="female">
                    <input type="radio" name="gender" id="female" value="female">
                    <span class="flex_ transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('13'));?><?php echo iN_HelpSecure($LANG['female']);?></span>
                  </label>
                </div>
                <div class="flexBox flex_">
                  <label class="youare flex_" id="youarein" for="male">
                    <input type="radio" name="gender" id="male" value="male">
                    <span class="flex_ transition"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('12'));?><?php echo iN_HelpSecure($LANG['male']);?></span>
                  </label>
                </div>
              </div>

              <?php if(isset($_GET['ref']) && $_GET['ref'] != ''){ ?>
                <input type="hidden" name="refuser" value="<?php echo $_GET['ref'];?>">
              <?php } ?>

              <div class="i_settings_item_title_for flex_ extra_style">
                <div class="i_re_box">
                  <div class="i_settings_item_title i_settings_item_title_extra_with"><?php echo iN_HelpSecure($LANG['full_name']);?></div>
                  <div class="i_settings_item_title_for i_settings_item_title_for_style">
                    <input type="text" name="flname" class="flnm min_with" placeholder="<?php echo iN_HelpSecure($LANG['your_full_name']);?>">
                  </div>
                </div>
                <div class="i_re_box">
                  <div class="i_settings_item_title i_settings_item_title_extra_with"><?php echo iN_HelpSecure($LANG['username']);?></div>
                  <div class="i_settings_item_title_for i_settings_item_title_for_style">
                    <input type="text" name="uusername" class="flnm min_with" placeholder="<?php echo iN_HelpSecure($LANG['your_username']);?>" value="<?php echo iN_HelpSecure($claimName);?>">
                  </div>
                </div>
              </div>

              <div class="i_settings_item_title_for flex_ extra_style">
                <div class="i_re_box">
                  <div class="i_settings_item_title i_settings_item_title_extra_with"><?php echo iN_HelpSecure($LANG['your_email_address']);?></div>
                  <div class="i_settings_item_title_for i_settings_item_title_for_style">
                    <input type="text" name="y_email" class="flnm min_with" placeholder="<?php echo iN_HelpSecure($LANG['your_email_address']);?>">
                  </div>
                </div>
                <div class="i_re_box">
                  <div class="i_settings_item_title i_settings_item_title_extra_with"><?php echo iN_HelpSecure($LANG['password']);?></div>
                  <div class="i_settings_item_title_for i_settings_item_title_for_style">
                    <input type="password" name="y_password" class="flnm min_with" placeholder="<?php echo iN_HelpSecure($LANG['password']);?>">
                  </div>
                </div>
              </div>

              <div class="i_settings_item_title_for flex_ extra_style">
                <div class="certification_file_form">
                  <div class="certification_file_box">
                    <?php echo html_entity_decode($LANG['accept_terms_of_conditions_register']);?>
                  </div>
                </div>
              </div>

              <!-- warnings (kept exact classes so your JS shows/hides them) -->
              <div class="register_warning fill_all"><?php echo iN_HelpSecure($LANG['full_for_register']);?></div>
              <div class="register_warning fill_pass"><?php echo iN_HelpSecure($LANG['passwor_too_short']);?></div>
              <div class="register_warning fill_email_used"><?php echo iN_HelpSecure($LANG['email_already_in_use_warning']);?></div>
              <div class="register_warning fill_email_invalid"><?php echo iN_HelpSecure($LANG['invalid_email_address']);?></div>
              <div class="register_warning fill_username_empty"><?php echo iN_HelpSecure($LANG['username_should_not_be_empty']);?></div>
              <div class="register_warning fill_username_used"><?php echo iN_HelpSecure($LANG['try_different_username']);?></div>
              <div class="register_warning fill_username_short"><?php echo iN_HelpSecure($LANG['short_username']);?></div>
              <div class="register_warning fill_username_invalid"><?php echo iN_HelpSecure($LANG['invalid_username']);?></div>

              <div class="form_group">
                <div class="i_login_button">
                  <button type="submit"><?php echo iN_HelpSecure($LANG['register']);?></button>
                </div>
              </div>
            </form>
          </div>

        <?php } else { ?>
          <!-- Register disabled view (inside same card so style matches) -->
          <div class="tabing column flex_ register_disabled" style="text-align:center">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('130'));?>
            <div style="margin-top:8px"><?php echo iN_HelpSecure($LANG['register_disabled']);?></div>
          </div>
        <?php } ?>

      </div>
    </div>
  </div>
</body>
</html>
