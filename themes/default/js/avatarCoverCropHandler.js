(function ($) {
  "use strict";

  // prevent duplicate bindings if the popup is injected more than once
  if (window._mfACropBound) return;
  window._mfACropBound = true;

  const preLoadingAnimation = '<div class="i_loading"><div class="dot-pulse"></div></div>';
  const plreLoadingAnimationPlus = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';

  let $coverCrop = null;
  let $avatarCrop = null;

  function ensureCoverCrop() {
    if ($coverCrop) return $coverCrop;
    const $ct = $(".i_modal_cover_resize_bg_in .cropier_container:visible");
    // if hidden, width can be 0 – fallback to 720
    const w = ($ct.width() || 720);
    const h = Math.max(220, Math.round(w * 0.33)); // ~3:1 viewport
    $coverCrop = $("#cover_image").croppie({
      enableExif: true,
      enableOrientation: true,
      viewport: { width: w, height: h, type: "square" },
      boundary: { width: w, height: h }
    });
    return $coverCrop;
  }

  function ensureAvatarCrop() {
    if ($avatarCrop) return $avatarCrop;
    $avatarCrop = $("#avatar_image").croppie({
      enableExif: true,
      viewport: { width: 240, height: 240, type: "square" },
      boundary: { width: 260, height: 260 }
    });
    return $avatarCrop;
  }

  function destroyCoverCrop() {
    try { $("#cover_image").croppie("destroy"); } catch(e) {}
    $coverCrop = null;
    $("#cover").val("");
  }
  function destroyAvatarCrop() {
    try { $("#avatar_image").croppie("destroy"); } catch(e) {}
    $avatarCrop = null;
    $("#avatar").val("");
  }

  // Accept either a plain URL or "200\n{json}"
  function parseUrl(resp) {
    if (typeof resp === "object" && resp) return resp.avatar || resp.cover || resp.url || "";
    let s = String(resp || "").trim();
    s = s.replace(/^200\s*/i, ""); // strip a leading "200" if present
    try { const j = JSON.parse(s); return j.avatar || j.cover || j.url || ""; } catch(e) {}
    return s; // plain URL
  }

  // --- COVER ---
  $("body").on("change", "#cover", function () {
    const f = this.files && this.files[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = function (ev) {
      $(".i_modal_cover_resize_bg_in").addClass("i_modal_display_in");
      setTimeout(function () {
        ensureCoverCrop().croppie("bind", { url: ev.target.result });
      }, 20);
    };
    reader.readAsDataURL(f);
  });

  $("body").on("click", ".finishCrop", function () {
    ensureCoverCrop().croppie("result", { type: "canvas", size: "original", circle: false })
      .then(function (img) {
        $(".i_modal_cover_resize_bg_in .i_modal_content").append(plreLoadingAnimationPlus);
        $.post(siteurl + "requests/request.php", { f: "coverUpload", image: img }, function (resp) {
          $(".loaderWrapper").remove();
          const url = parseUrl(resp);
          if (!url) { $(".i_cover_upload_error").fadeIn().delay(4000).fadeOut(); return; }
          $(".coverImageArea").css("background-image", "url(" + url + ")");
          $(".i_modal_cover_resize_bg_in").removeClass("i_modal_display_in");
          destroyCoverCrop();
        });
      });
  });

  // --- AVATAR ---
  $("body").on("change", "#avatar", function () {
    const f = this.files && this.files[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = function (ev) {
      $(".i_modal_avatar_resize_bg_in").addClass("i_modal_display_in");
      setTimeout(function () {
        ensureAvatarCrop().croppie("bind", { url: ev.target.result });
      }, 20);
    };
    reader.readAsDataURL(f);
  });

  $("body").on("click", ".finishACrop", function () {
    ensureAvatarCrop().croppie("result", { type: "canvas", size: "original", circle: false })
      .then(function (img) {
        $(".i_modal_avatar_resize_bg_in .i_modal_content").append(plreLoadingAnimationPlus);
        $.post(siteurl + "requests/request.php", { f: "avatarUpload", image: img }, function (resp) {
          $(".loaderWrapper").remove();
          const url = parseUrl(resp);
          if (!url) { $(".i_cover_upload_error").fadeIn().delay(4000).fadeOut(); return; }
          $(".avatarImage").css("background-image", "url(" + url + ")");
          $(".i_modal_avatar_resize_bg_in").removeClass("i_modal_display_in");
          // optional: refresh any small header avatar
          $(".i_header_user_avatar img, .i_message_avatar img").attr("src", url);
          destroyAvatarCrop();
        });
      });
  });

  // Close buttons = clean up
  $("body").on("click", ".cnclcrp, .coverCropClose, .no-del, .shareClose", function () {
    $(".i_modal_cover_resize_bg_in, .i_modal_avatar_resize_bg_in").removeClass("i_modal_display_in");
    destroyCoverCrop();
    destroyAvatarCrop();
  });

})(jQuery);
