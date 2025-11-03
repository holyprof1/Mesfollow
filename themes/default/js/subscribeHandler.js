/* Subscriptions — POINTS ONLY. Cash is handled by subscriptionCheckout.js */
(function ($) {
  "use strict";

  // ensure siteurl exists
  window.siteurl = window.siteurl || (document.querySelector("base")?.href || (location.origin + "/"));

  // close helper
  $(document).on("click", ".i_modal_bg_in .popClose", function () {
    $(this).closest(".i_modal_bg_in").remove();
  });

  /* ===== POINTS: open server modal (creditPoint). If server returns nothing,
     show a small client-side confirm and call subWithPoints directly. ===== */
  $(document).on("click", ".bcmSubsPoint", function (e) {
    e.preventDefault(); e.stopPropagation();
    var $btn      = $(this);
    var creatorId = $btn.data("u") || $btn.attr("data-u");
    var planId    = $btn.attr("id");
    var amount    = $btn.data("amount");
    var interval  = $btn.data("interval");

    if (!creatorId || !planId) return;

    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      data: { f: "creditPoint", id: creatorId, plan: planId },
      success: function (res) {
        var html = String(res || "");
        if (html.indexOf("i_modal_bg_in") !== -1) {
          $("body").append(html);
          setTimeout(function(){ $(".i_modal_bg_in").last().addClass("i_modal_display_in"); }, 100);
          return;
        }

        // --- client-side confirm fallback (works even if server didn't return a modal)
        var label = "Confirm subscription";
        if (amount && interval) {
          var cap = String(interval).charAt(0).toUpperCase() + String(interval).slice(1);
          label = "Abonnez-vous pour " + amount + " Point / " + cap;
        } else if (amount) {
          label = "Abonnez-vous pour " + amount + " Point";
        }
        var htmlFb =
          '<div class="i_modal_bg_in" role="dialog" aria-modal="true">' +
            '<div class="i_modal_in_in i_sf_box">' +
              '<div class="i_modal_content">' +
                '<div class="i_modal_g_header">Subscription' +
                  '<div class="popClose transition" role="button" aria-label="Close">&times;</div>' +
                '</div>' +
                '<div class="i_more_text_wrapper">' +
                  '<div class="i_set_subscription_fee_box">' +
                    '<div class="i_tip_not">Veuillez confirmer votre abonnement.</div>' +
                  '</div>' +
                '</div>' +
                '<div class="i_block_box_footer_container bySub">' +
                  '<div class="pay_subscription_point subMyPoint" id="' + planId + '" data-u="' + creatorId + '" role="button">' +
                    label +
                  '</div>' +
                  '<div class="cntsub" style="display:none;color:#e74c3c;margin-top:8px;">Not enough points.</div>' +
                  '<div class="insfsub" style="display:none;margin-top:8px;">Already subscribed.</div>' +
                '</div>' +
              '</div>' +
            '</div>' +
          '</div>';
        $("body").append(htmlFb);
        setTimeout(function(){ $(".i_modal_bg_in").last().addClass("i_modal_display_in"); }, 100);
      },
      error: function () {
        alert("Network error. Please try again.");
      }
    });
  });

  /* Confirm POINTS */
  $(document).on("click", ".subMyPoint", function (e) {
    e.preventDefault(); e.stopPropagation();
    var planId    = $(this).attr("id");
    var creatorId = $(this).attr("data-u");
    if (!planId || !creatorId) return;

    $.ajax({
      type: "POST",
      url: window.siteurl + "requests/request.php",
      data: { f: "subWithPoints", pl: planId, id: creatorId },
      success: function (resp) {
        var raw = String(resp || "").trim();
        var status = raw;
        try { var j = JSON.parse(raw); if (j && j.status) status = String(j.status); } catch(e){}
        if (status === "200" || status.toLowerCase() === "ok")      { location.reload(); }
        else if (status === "302")                                  { $(".insfsub").show(); }
        else if (status === "404")                                  { $(".cntsub").show(); }
        else { alert("Could not complete subscription."); console.log("subWithPoints response:", resp); }
      },
      error: function () { alert("Network error. Please try again."); }
    });
  });

  // IMPORTANT: No binding for '.bcmSubs' here. Cash belongs to subscriptionCheckout.js
})(jQuery);