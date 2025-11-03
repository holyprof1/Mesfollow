<?php
@http_response_code(200);
@header('Content-Type: text/html; charset=utf-8');
@header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/includes/inc.php';

$themeDir = __DIR__ . '/themes/default';
$themeFile = $themeDir . '/payment-success.php';

if (is_file($themeFile)) {
    chdir($themeDir);              // IMPORTANT: fix relative includes inside the theme
    require $themeFile;            // renders the nice themed page
    exit;
}

// Fallback (only if themed file is missing)
?>
<div class="container" style="max-width:720px;margin:40px auto;padding:24px;text-align:center">
  <h2 style="margin-bottom:10px;">Payment Successful</h2>
  <p style="margin-bottom:24px;">Your payment has been confirmed. Merci !</p>
  <a href="javascript:history.back()" class="i_button btn btn-primary" style="display:inline-block;padding:10px 18px;border-radius:8px;text-decoration:none;">Retour</a>
  <div style="margin-top:10px;"><a href="/" class="i_button btn btn-outline">Accueil</a></div>
</div>
