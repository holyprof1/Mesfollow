<?php
// If already logged in, redirect to home
if($logedIn == '1'){
    header('Location: ' . $base_url);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?> - Login</title>
    <?php
       include("themes/$currentTheme/layouts/header/meta.php");
       include("themes/$currentTheme/layouts/header/css.php");
       include("themes/$currentTheme/layouts/header/javascripts.php");
    ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            overflow-x: hidden;
        }
        .login-page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #7928CA 0%, #FF0080 100%);
            position: relative;
            overflow: hidden;
        }
        .login-page-wrapper::before,
        .login-page-wrapper::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite ease-in-out;
        }
        .login-page-wrapper::before {
            width: 500px;
            height: 500px;
            top: -250px;
            right: -100px;
            animation-delay: -5s;
        }
        .login-page-wrapper::after {
            width: 400px;
            height: 400px;
            bottom: -200px;
            left: -100px;
            animation-delay: -10s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-50px) scale(1.1); }
        }

        /* Logo header (WILL BE REMOVED IN HTML BELOW) */
        .login-header { padding: 20px 30px; position: relative; z-index: 10; }
        .login-header img { height: 45px; width: auto; display: block; }

        .login-center {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 20px; position: relative; z-index: 10;
        }
		
			/* ==== hard-center the card even if theme CSS overrides ==== */
.login-center{justify-content:center!important;align-items:center!important}
.login-page-container{margin:0 auto!important;float:none!important}
		
        .login-page-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            max-width: 460px; width: 100%;
            padding: 45px 40px;
            animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-page-header { text-align: center; margin-bottom: 35px; }

        /* celebration SVG -> REPLACED WITH LOGO IMAGE */
        .login-page-header .i_login_box_wellcome_icon {
            font-size: 64px; margin-bottom: 20px;
            background: linear-gradient(135deg, #7928CA, #FF0080);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        /* <<< ADDED: keep same class but show logo image inside */
        .i_login_box_wellcome_icon img { height: 64px; width: auto; display: inline-block; } /* <<< ADDED */

        .login-page-header .i_lBack { font-size: 32px; font-weight: 800; color: #1a202c; margin-bottom: 10px; letter-spacing: -0.5px; }
        .login-page-header .i_lnot { font-size: 15px; color: #718096; font-weight: 500; }

        .i_warns { margin-bottom: 20px; }
        .i_warns .i_error {
            background: #fff5f5; border: 1px solid #feb2b2; color: #c53030;
            padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; line-height: 1.5; animation: shake 0.4s;
        }
        @keyframes shake { 0%,100%{transform:translateX(0);} 25%{transform:translateX(-10px);} 75%{transform:translateX(10px);} }

        .form_group { margin-bottom: 22px; }
        .form_label { display:block; font-size:14px; font-weight:600; color:#2d3748; margin-bottom:10px; }
        .form-control input {
            width:100%; padding:14px 18px; border:2px solid #e2e8f0; border-radius:12px; font-size:15px;
            transition: all 0.3s ease; background: #f7fafc;
        }
        .form-control input:focus {
            outline:none; border-color:#7928CA; background:#fff; box-shadow:0 0 0 4px rgba(121,40,202,0.1);
        }
        .form-control input::placeholder { color:#a0aec0; }

        .i_login_button { margin-top: 28px; }
        .i_login_button button {
            width:100%; padding:16px; background:linear-gradient(135deg, #7928CA 0%, #FF0080 100%);
            color:#fff; border:none; border-radius:12px; font-size:16px; font-weight:700; cursor:pointer;
            transition: all .3s ease; box-shadow:0 10px 25px rgba(121,40,202,0.3); letter-spacing:.3px;
        }
        .i_login_button button:hover { transform: translateY(-2px); box-shadow:0 15px 35px rgba(121,40,202,0.4); }
        .i_login_button button:active { transform: translateY(0); }
        .i_login_button button:disabled { opacity:.6; cursor:not-allowed; transform:none; box-shadow:0 10px 25px rgba(121,40,202,0.3); }

        .login-title { text-align:center; margin:30px 0 20px; position:relative; font-size:13px; color:#718096; font-weight:600; }
        .login-title span { background: rgba(255,255,255,0.98); padding:0 18px; position:relative; z-index:1; }
        .login-title::before { content:''; position:absolute; left:0; right:0; top:50%; height:1px; background:#e2e8f0; }

        .i_social-btns { display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin-bottom:10px; }
        .i_social-btns > div { flex:1; min-width:140px; }
        .i_social-btns a {
            display:flex; align-items:center; justify-content:center; gap:10px;
            padding:12px 20px; border:2px solid #e2e8f0; border-radius:12px; text-decoration:none;
            color:#2d3748; font-size:14px; font-weight:600; transition:all 0.3s ease; background:#f7fafc;
        }
        .i_social-btns a:hover { border-color:#7928CA; background:#fff; transform:translateY(-2px); box-shadow:0 6px 20px rgba(121,40,202,0.15); }

        .i_l_footer { text-align:center; margin-top:28px; font-size:14px; color:#718096; font-weight:500; }
        .i_l_footer a { color:#7928CA; text-decoration:none; font-weight:700; transition:color 0.2s; }
        .i_l_footer a:hover { color:#FF0080; }

        .password-reset { display:inline-block; text-align:center; margin-top:12px; color:#7928CA; text-decoration:none; font-size:14px; font-weight:600; cursor:pointer; transition:color 0.2s; } /* margin tuned */
        .password-reset:hover { color:#FF0080; }

        .forgot-footer { text-align:center; margin-top: 0; } /* keep but we’ll not use it outside */

        /* <<< ADDED: forgot panel hidden by default for inora toggles */
        .i_modal_forgot { display:none; } /* <<< ADDED */

        @media (max-width: 480px) {
            .login-header { padding: 15px 20px; }
            .login-header img { height: 38px; }
            .login-page-container { padding: 35px 25px; margin: 10px; border-radius: 20px; }
            .login-page-header .i_login_box_wellcome_icon { font-size: 56px; }
            .login-page-header .i_lBack { font-size: 26px; }
            .login-page-header .i_lnot { font-size: 14px; }
            .i_social-btns > div { min-width: 100%; }
            .form-control input { font-size: 16px; }
            .password-reset, .i_l_footer { font-size: 13px; }
        }
        @media (max-width: 360px) { .login-page-container { padding: 30px 20px; } }
		
	/* --- hard-center the card (you already added these, keep them) --- */
.login-center{justify-content:center!important;align-items:center!important}
.login-page-container{margin:0 auto!important;float:none!important}

/* --- FIX: modal block being narrow/left-aligned due to theme modal CSS --- */
.i_modal_in,
.i_modal_forgot{
  width:100% !important;
  max-width:none !important;
  float:none !important;
  margin:0 !important;
  position:static !important;

}

/* make all inner sections span full width */
.i_modal_social_login_content,
.i_direct_login,
.i_l_footer,
.forgot-footer{
  width:100% !important;
}

/* ensure the flex container spans full row */
.login-center{width:100% !important}

/* === Fix 1: "Mot de passe oublié ?" centered & tucked under the register line === */
.forgot-footer{
  text-align:center !important;
  margin-top: 10px !important;   /* was larger */
}
.forgot-footer .password-reset{
  display:inline-block !important;
}

/* === Fix 2: reduce big gap between subtitle and "CONNEXION AVEC" === */
.login-page-header{ 
  margin-bottom: 16px !important;  /* was 35px */
}
/* tighten the first divider title inside the social block */
.i_modal_social_login_content .login-title:first-child{
  margin: 10px 0 12px !important;  /* was ~24–30px */
}
/* optional: tighten the second divider too (before the direct form) */
.i_modal_social_login_content .login-title:last-child{
  margin: 16px 0 12px !important;
}
/* make the subtitle itself sit a bit closer to the headline */
.login-page-header .i_lnot{
  margin-top: 4px !important;
}

/* === Fix 3: wider card on desktop (like register) === */
@media (min-width: 1024px){
  .login-page-container{
    max-width: 560px !important;   /* bump from 460px; use 600px if you want even wider */
  }
}
/* --- A) Move the form block up (less gap under the header) --- */
.login-page-container{ padding-top: 30px !important; }   /* was 45px */
.login-page-header{ margin-bottom: 4px !important; }     /* was 35px/16px */
.i_modal_social_login_content{ margin-top: 4px !important; }
.i_modal_social_login_content .login-title{ margin: 8px 0 10px !important; }

/* --- B) Perfectly center the "Mot de passe oublié ?" link inside the card --- */
.forgot-footer{
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  margin-top: 10px !important;
  width: 100% !important;
  text-align: center !important;
}
.forgot-footer .password-reset{
  position: static !important;
  float: none !important;
  margin: 0 auto !important;
  display: inline-block !important;
}
/* === Lift the login modal section (reduce empty space under subtitle) === */
.i_modal_in {
  margin-top: -15px !important;   /* move block up a little */
}
/* LIFT THE LOGIN MODAL UNDER THE SUBTITLE */
div.i_modal_in{
  /* kill the theme transform that makes it look lower */
  transform: none !important;

  /* override the earlier "margin:0 !important" */
  margin-top: -24px !important;   /* tweak to taste: -18 / -24 / -30 */
}

/* (optional) tighten a bit more from the header side */
.login-page-header{ margin-bottom: 0 !important; }

    </style>
</head>
<body>
    <div class="login-page-wrapper">

        <!-- Logo Header — REMOVED per request -->
        <!--
        <div class="login-header">
            <a href="<?php echo iN_HelpSecure($base_url); ?>">
                <img src="<?php echo iN_HelpSecure($siteLogoUrl); ?>" alt="<?php echo iN_HelpSecure($siteTitle); ?>">
            </a>
        </div>
        -->

        <div class="login-center">
            <div class="login-page-container">
                <!-- Header -->
                <div class="login-page-header">
                    <!-- celebration icon REPLACED by your logo -->
                    <div class="i_login_box_wellcome_icon">
                        <img src="<?php echo iN_HelpSecure($siteLogoUrl); ?>" alt="<?php echo iN_HelpSecure($siteTitle); ?>"> <!-- <<< CHANGED -->
                    </div>
                    <div class="i_welcome_back">
                        <div class="i_lBack"><?php echo iN_HelpSecure($LANG['you-are-back']); ?></div>
                        <div class="i_lnot"><?php echo iN_HelpSecure($LANG['login-to-access-your-account']); ?></div>
                    </div>
                </div>

                <!-- ===== VISIBLE LOGIN BLOCK (.i_modal_in) for inora toggles ===== -->
                <div class="i_modal_in"> <!-- <<< ADDED WRAPPER -->
                    <!-- Social Login -->
                    <?php if ($socialLoginStatus == '1') {
                        $socialLogins = $iN->iN_SocialLogins();
                        if ($socialLogins) { ?>
                            <div class="i_modal_social_login_content">
                                <div class="login-title"><span><?php echo $LANG['login-with']; ?></span></div>
                                <div class="i_social-btns">
                                    <?php foreach ($socialLogins as $sL) {
                                        $sKey = $sL['s_key'];
                                        $sIcon = $sL['s_icon'];
                                    ?>
                                        <div>
                                            <a class="<?php echo iN_HelpSecure($sKey); ?>-login" href="<?php echo iN_HelpSecure($base_url) . $sKey; ?>Login">
                                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon($sIcon)); ?>
                                                <span><?php echo iN_HelpSecure($LANG[$sKey]); ?></span>
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="login-title"><span><?php echo $LANG['or-directly']; ?></span></div>
                            </div>
                        <?php }
                    } ?>

                    <!-- Error Messages -->
                    <div class="i_warns">
                        <div class="i_error" style="display:none;"></div>
                    </div>

                    <!-- Login Form -->
                    <div class="i_direct_login">
                        <form enctype="multipart/form-data" method="post" id='ilogin' autocomplete="off">
                            <div class="form_group">
                                <label for="i_nora_username" class="form_label"><?php echo iN_HelpSecure($LANG['username-or-email']); ?></label>
                                <div class="form-control">
                                    <input type="text" name="username" id="i_nora_username" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['username-or-email-ex']); ?>" required>
                                </div>
                            </div>
                            <div class="form_group">
                                <label for="i_nora_password" class="form_label"><?php echo iN_HelpSecure($LANG['password']); ?></label>
                                <div class="form-control">
                                    <input type="password" name="password" id="i_nora_password" class="inora_user_input" placeholder="<?php echo iN_HelpSecure($LANG['password']); ?>" required>
                                </div>
                            </div>
                            <div class="form_group">
                                <div class="i_login_button">
                                    <button type="submit"><?php echo iN_HelpSecure($LANG['login']); ?></button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Footer -->
                    <div class="i_l_footer">
                        <?php echo html_entity_decode($LANG['not-member-yet']); ?>
                    </div>

                    <!-- Forgot Password INSIDE the box, under the register line -->
                    <div class="forgot-footer">
                        <a href="javascript:void(0)" class="password-reset"><?php echo iN_HelpSecure($LANG['forgot-password']); ?></a> <!-- <<< CHANGED href for JS toggle -->
                    </div>
                </div>
                <!-- / .i_modal_in -->

                <!-- ===== HIDDEN FORGOT BLOCK (.i_modal_forgot) ===== -->
                <div class="i_modal_forgot" style="display:none;"> <!-- <<< ADDED -->
                    <div class="login-page-header" style="margin-bottom:18px;">
                        <div class="i_login_box_wellcome_icon">
                            <img src="<?php echo iN_HelpSecure($siteLogoUrl); ?>" alt="<?php echo iN_HelpSecure($siteTitle); ?>">
                        </div>
                        <div class="i_welcome_back">
                            <div class="i_lBack"><?php echo iN_HelpSecure($LANG['forgot-password']); ?></div>
                            <div class="i_lnot"><?php echo iN_HelpSecure($LANG['login-to-access-your-account']); ?></div>
                        </div>
                    </div>

                    <form method="post" action="<?php echo iN_HelpSecure($base_url); ?>reset_password" autocomplete="off">
                        <div class="form_group">
                            <label for="reset_email" class="form_label"><?php echo iN_HelpSecure($LANG['username-or-email']); ?></label>
                            <div class="form-control">
                                <input type="text" id="reset_email" name="email" placeholder="<?php echo iN_HelpSecure($LANG['username-or-email-ex']); ?>" required>
                            </div>
                        </div>
                        <div class="form_group">
                            <div class="i_login_button">
                                <button type="submit"><?php echo iN_HelpSecure($LANG['send']); ?></button>
                            </div>
                        </div>
                    </form>

                    <div class="forgot-footer" style="margin-top:12px;">
                       <a href="javascript:void(0)" class="already-member"><?php echo iN_HelpSecure($LANG['login']); ?></a>

                    </div>
                </div>
                <!-- / .i_modal_forgot -->

            </div>
        </div>
    </div>
</body>
</html>
