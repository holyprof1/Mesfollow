(function ($) {
  "use strict";

  /* -------------------------- helpers -------------------------- */
  function hideAllUsernameHints(){
    $('.invalid_username, .character_warning, .warning_username').hide();
  }
  function isLikelyHtml(str){
    return /<\s*(div|form|section|article|dialog|html|body|style|script)\b/i.test(str || "");
  }

  /* ----------------------- birthday mask ----------------------- */
  $(function(){
    if ($.fn.mask) { $('#date1').mask('00/00/0000'); }
  });

  /* ----------- debounced username availability check ----------- */
  let usernameTimer = null;
  $(document).on('input', '#uname', function () {
    clearTimeout(usernameTimer);
    const username = $("#uname").val().trim();

    usernameTimer = setTimeout(function () {
      if (username.length < 3) { hideAllUsernameHints(); return; }
      $.ajax({
        type: 'POST',
        url : window.siteurl + "requests/request.php",
        data: { f: 'checkusername', username: username },
        cache: false
      }).done(function (res) {
        hideAllUsernameHints();
        switch (String(res).trim()) {
          case '1': /* ok */ break;
          case '2': $('.warning_username').show(); break;
          case '3': $('.invalid_username').show(); break;
          case '4': $('.character_warning').show(); break;
          default:  hideAllUsernameHints();
        }
      });
    }, 400);
  });

  /* ---------------- avatar/cover editor opener ----------------- */
  function openAvatarPopup() {
    // prevent spam clicks
    if ($('body').data('mf-opening-avatar')) return;
    $('body').data('mf-opening-avatar', true);

    // tiny loader overlay
    const loader = $(
      '<div class="mf_popup mf_loader" ' +
      'style="position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9999;display:flex;align-items:center;justify-content:center">' +
      '<div style="background:#fff;padding:14px 18px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.2)">Loading…</div>' +
      '</div>'
    );
    $('body').append(loader);

    $.ajax({
      type: 'POST',
      url : window.siteurl + 'requests/request.php',
      data: { f: 'updateAvatarCover' },
      cache: false
    }).done(function (html) {
      loader.remove();
      $('body').data('mf-opening-avatar', false);

      const trimmed = String(html || '').trim();

      // 1) If proper popup HTML came back, inject it
      if (trimmed && isLikelyHtml(trimmed) && /avatar|cover|popup|modal/i.test(trimmed)) {
        // clean older popups if any
        $('.generalBox.popup, .popup_alert, .i_box_wrapper_popup, .mf_popup').remove();
        $('body').append(trimmed);
        return;
      }

      // 2) If the endpoint returned only a code ("200"/"OK"/json), fall back to the dedicated tab
      window.location.href = window.siteurl + 'settings?tab=avatar_settings';
    }).fail(function(){
      loader.remove();
      $('body').data('mf-opening-avatar', false);
      // network / CORS / 500 → fallback
      window.location.href = window.siteurl + 'settings?tab=avatar_settings';
    });
  }

  // The clickable row is a DIV with these classes
  $(document).on('click', '.editAvatarCover, .modify_avatar_cover', function (e) {
    e.preventDefault();
    openAvatarPopup();
  });

  /* -------------------- AJAX save profile form ----------------- */
  const $form = $('#myProfileForm');
  $form.on('submit', function (e) {
    e.preventDefault();
    hideAllUsernameHints();

    const $btn = $('#update_myprofile');
    $btn.prop('disabled', true).addClass('is-loading');

    $.ajax({
      type: 'POST',
      url : window.siteurl + 'requests/request.php',
      data: $form.serialize(),  // includes hidden f=editMyPage
      cache: false
    }).done(function (resp) {
      const r = String(resp || '').trim();
      const ok = /(^|\n|\r)\s*200(\s|$)/.test(r) || /"ok"\s*:\s*1/.test(r);

      if (ok) {
        const u = $('#uname').val().trim();
        if (u) $('#reUnm').text(u);
        const $n = $('.successNot');
        $n.stop(true, true).fadeIn(160);
        setTimeout(function(){ $n.fadeOut(300); }, 2500);
      } else if (/username_taken/.test(r)) {
        $('.warning_username').show();
      } else if (/invalid_username/.test(r)) {
        $('.invalid_username').show();
      } else if ($('#uname').val().trim().length > 0 && $('#uname').val().trim().length < 5) {
        $('.character_warning').show();
      } else {
        alert('Could not save your settings. Please try again.');
      }
    }).fail(function () {
      alert('Network error. Please try again.');
    }).always(function () {
      $btn.prop('disabled', false).removeClass('is-loading');
    });
  });

})(jQuery);
