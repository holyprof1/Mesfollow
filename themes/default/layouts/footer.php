<?php
if ($getPages) {
  foreach ($getPages as $page) {
    $pageName  = $page['page_name'];
    $pageTitle = $page['page_title'];
    $page_Name = isset($LANG[$pageName]) ? $LANG[$pageName] : $pageTitle;
    echo '<div class="footer_menu_item"><a href="'.$base_url.$pageName.'">'.$page_Name.'</a></div>';
  }
  echo '<div class="footer_menu_item">'.$siteName.' © '.date('Y').'</div>';
}
?>

<!-- put scripts OUTSIDE php -->
<script src="<?php echo $base_url; ?>src/js/jquery-v3.7.1.min.js"></script>

<div class="post-modal-container" id="post-modal-container" style="display:none;">
    <div class="post-modal-content">
        <button class="post-modal-close">&times;</button>
        <div class="post-modal-body" id="post-modal-body">
            </div>
    </div>
</div>