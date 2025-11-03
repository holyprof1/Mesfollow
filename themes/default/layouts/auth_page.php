<?php
/* themes/<theme>/layouts/auth_page.php
   Stand-alone login page that reuses your existing modal login_form.php.
   We only add small CSS overrides so the modal renders as a normal page.
*/
?>
<!DOCTYPE html>
<?php
  $activeLangForHtml = isset($userLang) && $userLang ? $userLang : (
    (isset($_SESSION['lang']) && $_SESSION['lang']) ? $_SESSION['lang'] : (
      (isset($_COOKIE['lang']) && $_COOKIE['lang']) ? $_COOKIE['lang'] : ($defaultLanguage ?? 'en')
    )
  );
?>
<html lang="<?php echo iN_HelpSecure($activeLangForHtml); ?>">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1" />
  <title><?php echo iN_HelpSecure($siteTitle); ?></title>

  <?php
    // Reuse your theme head assets (keeps style/JS bindings for #ilogin, forgot password toggles, etc.)
    include __DIR__ . '/../header/meta.php';
    include __DIR__ . '/../header/css.php';
    include __DIR__ . '/../header/javascripts.php';
  ?>

  <style>
    /* === Turn modal into a normal centered page === */
    html, body { height: 100%; }
    body { margin: 0; }

    /* Full-page centering wrapper */
    .auth-page-wrap {
      min-height: 100dvh;
      display: grid;
      place-items: center;
      padding: 24px;
      background: var(--bodyBg, #0f0f12);
    }

    /* Make the modal container behave like a card on a page */
    .i_modal_bg {
      display: block !important;     /* ensure visible */
      position: static !important;   /* no fixed overlay */
      background: transparent !important;
      width: 100%;
      max-width: 520px;
      margin: 0 auto;
    }
    .i_modal_in, .i_modal_forgot {
      position: static !important;
      width: 100%;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,.08);
      background: #15161b;
      box-shadow: 0 12px 40px rgba(0,0,0,.35);
      overflow: hidden;
    }
    .i_modal_content { padding: 20px; }
    .i_login_box_header { padding: 18px 20px 0 20px; }

    /* Hide “X” close button on page (no overlay to close) */
    .i_modal_close { display: none !important; }

    /* Footer links spacing */
    .i_l_footer { padding: 10px 20px 16px; }

    /* Forgot-password link under the card */
    a.password-reset {
      display: block;
      text-align: center;
      margin: 12px auto 0;
      color: #9ecbff;
      text-decoration: none;
    }

    /* Optional: reduce max-width on very small screens */
    @media (max-width: 480px){
      .i_modal_in, .i_modal_forgot { border-radius: 14px; }
      .i_modal_content { padding: 16px; }
    }
  </style>
</head>
<body>
  <div class="auth-page-wrap">
    <?php
      // Reuse your exact modal markup as page content:
      // If your login_form.php path differs, adjust the include path below.
      include __DIR__ . '/../login_form.php';
    ?>
  </div>

  <script>
    // Optional guard: ensure the login view is shown initially (depends on theme CSS/JS defaults)
    (function(){
      var inBox = document.querySelector('.i_modal_in');
      var forgot = document.querySelector('.i_modal_forgot');
      if (inBox && forgot) {
        inBox.style.display = 'block';
        forgot.style.display = 'none';
      }
    })();
  </script>
</body>
</html>
