<?php /* themes/<theme>/layouts/popup_alerts/updateAvatarCover.php */ ?>
<div class="i_modal_bg_in" id="mf-avc-modal">
  <div class="i_modal_in" style="max-width:520px;margin:40px auto;background:#fff;border-radius:12px;padding:16px">
    <div class="i_modal_title" style="font-weight:600;margin-bottom:8px">Update avatar / cover</div>
    <div class="i_modal_desc" style="opacity:.85;margin-bottom:16px">Choose what you want to change.</div>

    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button type="button" class="i_nex_btn_btn" id="mf-choose-avatar">Change avatar</button>
      <button type="button" class="i_nex_btn_btn" id="mf-choose-cover">Change cover</button>
      <button type="button" class="i_close_modal">Close</button>
    </div>
  </div>
</div>

<script>
(function(){
  // Try to click the actual file input on the current page.
  function openPicker(which){
    // tolerant selectors (works across most themes)
    var sel = which==='cover'
      ? 'input[type="file"][name*="cover" i], input[type="file"][id*="cover" i]'
      : 'input[type="file"][name*="avatar" i], input[type="file"][id*="avatar" i]';

    var input = document.querySelector(sel);
    if (input) {
      input.click();
      document.getElementById('mf-avc-modal')?.remove();
      return;
    }
    // Not on the avatar page? Go there and auto-open.
    var base = (window.siteurl || '/') + 'settings?tab=avatar_settings&auto=' + which;
    window.location.href = base;
  }

  document.getElementById('mf-choose-avatar')?.addEventListener('click', function(){ openPicker('avatar'); });
  document.getElementById('mf-choose-cover') ?.addEventListener('click', function(){ openPicker('cover');  });
  document.querySelector('#mf-avc-modal .i_close_modal')?.addEventListener('click', function(){
    document.getElementById('mf-avc-modal')?.remove();
  });

  // Match theme’s show animation
  setTimeout(function(){ document.querySelector('.i_modal_bg_in')?.classList.add('i_modal_display_in'); }, 50);
})();
</script>
