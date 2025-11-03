(function($) {
    "use strict";

    $(document).on("click", ".copyUrl", function() {
        PopUPAlerts('urlCopied', 'ialert');
    });
    document.addEventListener("DOMContentLoaded", function () {
      const mappings = [
        { selector: '.i_profile_cover_blur', attr: 'data-background' },
        { selector: '.i_profile_avatar', attr: 'data-avatar' }
      ];

      mappings.forEach(function (map) {
        document.querySelectorAll(map.selector).forEach(function (el) {
          const val = el.getAttribute(map.attr);
          if (val) {
            el.style.backgroundImage = 'url(' + val + ')';
          }
        });
      });
    });
    document.querySelectorAll('.hshCl[data-color]').forEach(function(el) {
      el.style.color = el.getAttribute('data-color');
    });
    var preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
    var plreLoadingAnimationPlus = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
    var likeBox = '<div class="like_heart flex_ tabing">' +
        '<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
        '<path d="M2 9.1371C2 14 6.01943 16.5914 8.96173 18.9109C10 19.7294 11 20.5 12 20.5C13 20.5 14 19.7294 15.0383 18.9109C17.9806 16.5914 22 14 22 9.1371C22 4.27416 16.4998 0.825464 12 5.50063C7.50016 0.825464 2 4.27416 2 9.1371Z" fill="#d32f2f"/>' +
        '</svg>' +
        '</div>';
    var UnlikeBox = '<div class="like_heart flex_ tabing">' +
    '<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
    '<path d="M8.10627 18.2468C5.29819 16.0833 2 13.5422 2 9.1371C2 4.53656 6.9226 1.20176 11.2639 4.81373L9.81064 8.20467C9.6718 8.52862 9.77727 8.90554 10.0641 9.1104L12.8973 11.1341L10.4306 14.012C10.1755 14.3096 10.1926 14.7533 10.4697 15.0304L12.1694 16.7302L11.2594 20.3702C10.5043 20.1169 9.74389 19.5275 8.96173 18.9109C8.68471 18.6925 8.39814 18.4717 8.10627 18.2468Z" fill="#d32f2f"/>' +
    '<path d="M12.8118 20.3453C13.5435 20.0798 14.2807 19.5081 15.0383 18.9109C15.3153 18.6925 15.6019 18.4717 15.8937 18.2468C18.7018 16.0833 22 13.5422 22 9.1371C22 4.62221 17.259 1.32637 12.9792 4.61919L11.4272 8.24067L14.4359 10.3898C14.6072 10.5121 14.7191 10.7007 14.7445 10.9096C14.7699 11.1185 14.7064 11.3284 14.5694 11.4882L12.0214 14.4609L13.5303 15.9698C13.7166 16.1561 13.7915 16.4264 13.7276 16.682L12.8118 20.3453Z" fill="#d32f2f"/>' +
    '</svg>' +
    '</div>';
    $(document).ready(function () {
        if ($('.commenta').length > 0 && $.fn.autoResize) {
          $('.commenta').autoResize();
        }

        if (typeof ClipboardJS !== "undefined") {
          new ClipboardJS('.copyUrl');
        }

        if ($('#newPostT').length > 0 && typeof $.fn.characterCounter === "function") {
          $("#newPostT").characterCounter({
            limit: typeof availableLength !== "undefined" ? availableLength : 250
          });
        }
      });
    /*Notifications*/
 
	
	/*  ===== TEMP DISABLED: notifications poller =====

   var g = '';
   getm(g);
   function playNotificationSound() {
       var audio = document.getElementById('notification-sound-not');
       if (audio) {
           var playPromise = audio.play();

           if (playPromise !== undefined) {
               playPromise.then(_ => {}).catch(error => {});
           }
       }
   }
   function getm(g) {
       var type = 'get';
       if ($.trim(type).length === 0) {
           setTimeout(getm, 10000);
       } else {
           $.ajax({
               type: 'GET',
               url: siteurl + 'requests/get.php?f=1',
               dataType: "json",
               cache: false,
               beforeSend: function() {},
               success: function(response) {
                   var messageNotificationStatus = response.messageNotificationStatus;
                   var notificationStatus = response.notificationStatus;
                   var unReadedNotfications = response.unReadedNotfications;
                   var unReadMessageNotifications = response.unReadMessageNotifications;
                   var videoCallFound = response.videoCallFound;
                   var acceptStatus = response.acceptStatus;
                   if (messageNotificationStatus == '1') {
                       $(".msg_not").show();
                       $(".sum_m").html(unReadMessageNotifications);
                       if ($(".sum_m").attr("data-id") != messageNotificationStatus) {
                           $(".sum_m").attr("data-id", messageNotificationStatus);
                           playNotificationSound();
                       }
                   }
                   if (notificationStatus == '1') {
                       $(".not_not").show();
                       $(".sum_not").html(unReadedNotfications);
                       if ($(".sum_not").attr("data-id") != notificationStatus) {
                           $(".sum_not").attr("data-id", notificationStatus);
                           document.getElementById('notification-sound-not').play();
                       }
                   }
                   if (videoCallFound) {
                       if (!$("div").hasClass("videoCall")) {
                           VideoCallAlert(videoCallFound);
                       }
                   }
                   if (acceptStatus == '3') {
                       $(".caller_det").hide();
                       $(".call_declined").show();
                       $("#notification-sound-call")[0].pause();
                   }
                   if (!g) {
                       setTimeout(getm, 10000);
                   }
               }
           });
       }
   }

===== END TEMP DISABLED ===== */

	
	
	
    $(document).on("click", ".loginForm", function() {
        $('.i_modal_bg').addClass('i_modal_display');
    });
    $(document).on("click", ".i_modal_close", function() {
        $('.i_modal_bg').removeClass('i_modal_display');
        $(".i_modal_in").attr("style", "");
        $(".i_modal_forgot").hide();
    });
    $(document).on("click", ".password-reset", function() {
        $(".i_modal_in").hide();
        $(".i_modal_forgot").show();
    });
    $(document).on("click", ".already-member", function() {
        $(".i_modal_in").show();
        $(".i_modal_forgot").hide();
    });

    $(".i_comment_form_textarea").focusin(function() {
        var words = $(this).val();
        var ID = $(this).attr("data-id");
    });
    $(document).on("click", ".openPostMenu", function() {
        var ID = $(this).attr("id");
        $(".mnoBox" + ID).addClass("dblock");
    });
    $(document).on("click", ".openShareMenu", function() {
        var ID = $(this).attr("id");
        $(".mnsBox" + ID).addClass("dblock");
    });
    $(document).on("click", ".openComMenu", function() {
        var ID = $(this).attr("id");
        $(".comMBox" + ID).addClass("dblock");
    });
    $(document).on("click", ".msg_Set", function() {
        var ID = $(this).attr("id");
        if ($(".msg_Set")[0]) {
            $(".msg_Set").removeClass("dblock");
        }
        $(".msg_Set_" + ID).addClass("dblock");
    });
    $(document).on("click", ".smscd", function() {
        var ID = $(this).attr("id");
        if ($(".smscd")[0]) {
            $(".me_msg_plus").removeClass("dblock");
        }
        $(".msg_set_plus_" + ID).addClass("dblock");
    });
    $(document).on("click", ".whs", function() {
        $(".i_choose_ws_wrapper").addClass("dblock");
    });
    $(document).on("click", ".in_comment", function() {
        var ID = $(this).attr("id");
        $("#comment" + ID).focus();
    });
   $(document).on("mouseup touchend", function(e) {
  var $t = $(e.target);

  // If the click was inside any menu/picker/trigger, do nothing.
  if ($t.closest('.mnoBox, .mnsBox, .comMBox, .msg_Set, .me_msg_plus, .cSetc, .i_choose_ws_wrapper, .i_postFormContainer').length) {
    return;
  }
  if ($t.closest('.emojiBox, .emojiBoxC, .emojis_Container, .stickersContainer, .gifBox, .chtBtns, .camList').length) {
    return;
  }
  // Also ignore the click if it was on the triggers (prevents “open then instantly close”)
  if ($t.closest('.getMenu, .getEmojis, .getEmojisC, .getStickers, .getGifs').length) {
    return;
  }

  // Close floating menus
  $('.mnoBox, .mnsBox, .comMBox, .msg_Set, .me_msg_plus, .cSetc, .i_choose_ws_wrapper, .i_postFormContainer').removeClass('dblock');

  // Close notifications/pickers
  $(".i_general_box_container, .i_general_box_message_notifications_container, .i_general_box_notifications_container, .emojiBox, .emojiBoxC, .emojis_Container, .stickersContainer, .gifBox, .ch_fl_btns_container").remove();
});

    $(document).on("click", ".emoji_item", function() {
        var copyEmoji = $(this).attr("data-emoji");
        var getValue = $(".newPostT").val();
        $(".newPostT").val(getValue + copyEmoji);
        $(".emojiBox").remove();
    });
    $(document).on("click", ".emoji_item_c", function() {
        var copyEmoji = $(this).attr("data-emoji");
        var ID = $(this).attr("data-id");
        var getValue = $("#comment" + ID).val();
        $("#comment" + ID).val(getValue + ' ' + copyEmoji + ' ');
    });

    function GetSlimScroll() {
        if ($(window).width() < 330) {
            $(".btest").slimScroll({
                height: '100%',
                width: '100%',
                railVisible: false,
                alwaysVisible: false,
                wheelStep: 1,
                railOpacity: .1
            });
        }
    }
    GetSlimScroll();
    $(document).on("click", ".getMenu", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_container")[0]) {
                    $("#" + type).append(response);
                    GetSlimScroll();
                } else {
                    $(".i_general_box_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC ,  .i_general_box_message_notifications_container , .i_general_box_notifications_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".getEmojis", function() {
        var type = 'emoji';
        var ID = $(this).attr("data-type");
        var dataID = '';
        var data = 'f=' + type + '&id=' + ID + '&ec=' + dataID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".emojiBox")[0]) {
                    $(".i_pb_emojis").append(response);
                    GetSlimScroll();
                } else {
                    $(".emojiBox").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBoxC , .i_general_box_message_notifications_container , .i_general_box_notifications_container , .i_general_box_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".getAiBox", function() {
        var type = 'aiBox';
        var ID = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    $(document).on("click", ".createAiContent", function() {
        var type = 'generateAiContent';
        var prompt = $(".aiContT").val().trim();
        if (prompt === '') {
            return;
        }
        var data = 'f=' + type + '&uPrompt=' + encodeURIComponent(prompt);

        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $(".i_modal_content").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response === 'no') {
                    $(".i_loading").remove();
                    $(".i_warning_ai").show();
                    setTimeout(() => {
                        $(".i_warning_ai").hide();
                    }, 5000);
                } else if (response === 'no_enough_credit') {
                    $(".i_loading").remove();
                    $(".i_warning_ai_credit").show();
                    setTimeout(() => {
                        $(".i_warning_ai_credit").hide();
                    }, 5000);
                } else {
                    $(".i_modal_bg_in").remove();
                    $(".newPostT").val(response + "\n").focus();
                    $('.newPostT').autoResize();
                }
            },
            error: function() {
                // console.error("Request failed.");
                // Avoid using console in production. You may optionally show an error box to the user here.
            }
        });
    });
   /* Get Emojis for Comment -- FINAL CORRECTED VERSION */
$(document).on("click", ".getEmojisC", function() {
    // First, remove any old picker that might be open
    $('.emojiBoxC, .stickersContainer').remove();

    var emojiButton = $(this); // Get a reference to the button that was clicked
    var type = 'emoji';(function($) {
/* Get Emojis for Comment — open BELOW the button */
$(document).on("click", ".getEmojisC", function (e) {
  e.preventDefault();
  e.stopImmediatePropagation();

  // Close anything open
  $('.emojiBoxC, .stickersContainer, .gifBox').remove();

  var $btn = $(this);
  var id   = $btn.attr('id') || $btn.data('id') || '';

  $.ajax({
    type: 'POST',
    url: siteurl + 'requests/request.php',
    data: { f: 'emoji', id: id },
    success: function (html) {
      $('body').append(html);                 // html contains .emojiBoxC
      var pos = $btn.offset();
      var h   = $btn.outerHeight();

      var $box = $('.emojiBoxC').last();
      $box.css({
        position: 'absolute',
        zIndex: 100000,
        top: pos.top + h + 6,                 // BELOW the button
        left: pos.left - 150
      }).show();
    }
  });
});


    $(document).on("click", ".topMessages", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_message_notifications_container")[0]) {
                    $("#" + type).append(response);
                    $(".msg_not").hide();
                    $(".sum_m").attr("data-id", 0);
                    GetSlimScroll();
                } else {
                    $(".i_general_box_message_notifications_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_container , .i_general_box_notifications_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".topPoints", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_container")[0]) {
                    $("#" + type).append(response);
                } else {
                    $(".i_general_box_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_notifications_container , .i_general_box_message_notifications_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".topNotifications", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_notifications_container")[0]) {
                    $("#" + type).append(response);
                    if ($(".i_notifications_count")[0]) {
                        $(".not_not").hide();
                    }
                    GetSlimScroll();
                } else {
                    $(".i_general_box_notifications_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_container , .i_general_box_message_notifications_container").remove();
                }
            }
        });
    });



		
		
		
		
		
    $(document).on("click", ".g_feed", function() {
        var get = $(this).attr("data-get");
        var type = $(this).attr("data-type");
        var data = 'f=' + get;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $("#moreType").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '404') {
                    $("#moreType").attr("data-type", type);
                    $("#moreType").html('').append(response);
                    $(".mobile_left").removeClass("leftStickyActive");
                    $(".is_mobile").removeClass("svg_active_icon");
                    $(".i_postFormContainer").hide();
                        initGalleriesInDOM();
                        reInitPostPlugins($(document));
                        initImageBackgrounds();
                        initStandaloneSwiperLightGallery();
                        initSuggestedCreatorsSwiper();
                        initImageSuggestedBackgrounds();
                }
            }
        });
    });
    const initializedGalleries = new Set();

    function initGalleriesInDOM(scope = $(document)) {
        scope.find(".gallery_trigger").each(function () {
          const galleryID = $(this).data("gallery-id");
          if (galleryID && !initializedGalleries.has(galleryID)) {
            const $gallery = $("#" + galleryID);
            if ($gallery.length > 0) {
              $gallery.lightGallery({
                videojs: true,
                mode: 'lg-fade',
                cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
                download: false,
                share: false
              });
              initializedGalleries.add(galleryID);
            }
          }
        });
    }

    function reInitPostPlugins(scope) {
        if (!scope) return;

        scope.find('[id^="lightgallery"]').each(function () {
          const $this = $(this);
          if (!$this.hasClass('lg-initialized')) {
            $this.lightGallery({
              videojs: true,
              mode: 'lg-fade',
              cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
              download: false,
              share: false
            });
        }
    });

        scope.find('[id^="play_po_"]').each(function () {
          const $this = $(this);
          if (!$this.hasClass('green-audio-player-loaded')) {
            new GreenAudioPlayer($this[0], {
              stopOthersOnPlay: true,
              showTooltips: true,
              showDownloadButton: false,
              enableKeystrokes: true
            });
            $this.addClass('green-audio-player-loaded');
          }
        });
    }

    window.initImageBackgrounds = function (targetSelector = '.i_post_image_swip_wrapper', scope = $(document)) {
      scope.find(targetSelector).each(function () {
        const bg = $(this).attr('data-bg');
        if (bg) {
          $(this).css('background-image', 'url(' + bg + ')');
        }
      });
    };

    window.initImageSuggestedBackgrounds = function (targetSelector = '.i_sub_u_cov', scope = $(document)) {
      scope.find(targetSelector).each(function () {
        const bg = $(this).attr('data-bg');
        if (bg) {
          $(this).css('background-image', 'url(' + bg + ')');
        }
      });
    };


 const initializedStandaloneSwiper = new Set();

window.initStandaloneSwiperLightGallery = function (scope = $(document)) {
  scope.find('.swiper-wrapper[data-standalone-gallery="true"]').each(function () {
    const $wrapper = $(this);
    const galleryID = $wrapper.attr('id');

    if (!galleryID || initializedStandaloneSwiper.has(galleryID)) {
      return;
    }

    $wrapper.lightGallery({
      selector: '.swiper-slide a',
      videojs: true,
      mode: 'lg-fade',
      cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
      download: false,
      share: false
    });

    initializedStandaloneSwiper.add(galleryID);
    $wrapper.addClass('lg-initialized');
  });

  scope.find('.product_images_container .mySwiper').each(function () {
    new Swiper(this, {
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
      },
      pagination: {
        el: ".swiper-pagination",
        dynamicBullets: true,
      }
    });
  });
};

$(document).on('click', '.swiper-slide a', function (e) {
  const wrapper = $(this).closest('.swiper-wrapper');
  if (wrapper.hasClass('lg-initialized')) {
    e.preventDefault();
  }
});

    const initializedSuggestedSwipers = new Set();

  /**
   * Initialize Swiper in dynamically loaded suggested creators sections.
   * @param {jQuery|HTMLElement} scope - Optional. DOM scope to search for .mySwiper elements.
   */
  window.initSuggestedCreatorsSwiper = function (scope = $(document)) {
    scope.find('.i_postFormContainer_swiper .mySwiper').each(function () {
      const swiperElement = this;

      // Use a unique identifier to prevent double initialization
      const swiperID = $(swiperElement).data('swiper-id') || swiperElement.id || Math.random().toString(36).substr(2, 9);

      if (!initializedSuggestedSwipers.has(swiperID)) {
        new Swiper(swiperElement, {
          effect: "cards",
          grabCursor: true
        });
        initializedSuggestedSwipers.add(swiperID);
      }
    });
  };

$(document).ready(function () {
  initGalleriesInDOM();
  reInitPostPlugins($(document));
  initImageBackgrounds();
  initStandaloneSwiperLightGallery();
  initSuggestedCreatorsSwiper();
  initImageSuggestedBackgrounds();
});

    window.reInitLightGallery = function (html) {
        initGalleriesInDOM(html);
    };

  /********* SCROLL TO LOAD MORE ***********/
  let scrollLoad = true;
  $(document).on('touchmove', showMoreData); /* For mobile */
  $(window).on('scroll', showMoreData);

  function showMoreData() {
    if (
      scrollLoad &&
      $(window).scrollTop() >= $(document).height() - $(window).height() - 500
    ) {
      const moreType = $("#moreType").attr("data-type");
      const moreCat = $("#moreType").attr("data-po");
      let profileUserID = '';
      let ID;

      if (moreType === 'notifications' || moreType === 'paid' || moreType === 'free' || moreType === 'creators') {
        ID = $('#moreType').children('.mor').last().attr('data-last');
        if (moreType === 'creators') {
          profileUserID = $("#moreType").attr("data-r");
        }
      }

      if (
        moreType === 'moreposts' || moreType === 'savedpost' || moreType === 'moreexplore' ||
        moreType === 'morepremium' || moreType === 'friends' || moreType === 'morepurchased' ||
        moreType === 'moreboostedposts' || moreType === 'moretrendposts' || moreType === 'hashtag'
      ) {
        ID = $('#moreType').children('.i_post_body').last().attr('data-last');
        if (moreType === 'hashtag') {
          profileUserID = $("#moreType").attr("data-hash");
        }
      }

      if (moreType === 'profile') {
        ID = $('#moreType').children('.i_post_body').last().attr('data-last');
        if (!ID) {
          ID = $('#moreType').children('.i_sub_box_container').last().attr('data-last');
        }
        profileUserID = $("#prw").attr("data-u");
      }

      if (
        $('.i_loading , .nomore , .noPost , .no_creator_f_wrap').length === 0 &&
        !$(".i_loading , .nomore , .noPost , .no_creator_f_wrap")[0] &&
        moreType !== undefined
      ) {
        let data = `f=${moreType}&last=${ID}&p=${profileUserID}`;
        if (moreCat) {
          data += `&pcat=${moreCat}`;
        }

        $.ajax({
          type: "POST",
          url: siteurl + 'requests/request.php',
          data: data,
          cache: false,
          beforeSend: function () {
            $(".body_" + ID).after(preLoadingAnimation);
            scrollLoad = false;
          },
          success: function (response) {
            $(".i_loading").remove();
            if (response && !$(".nomore")[0]) {
                const $newContent = $(response);
                $("#moreType").append($newContent);

                reInitLightGallery($newContent);
                reInitPostPlugins($newContent);
                initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                initSuggestedCreatorsSwiper($newContent);
                initImageSuggestedBackgrounds();
              scrollLoad = true;
            }
          }
        });
      }
    }
  }

    /*Update Who Can See POST Before Share Post*/
    $(document).on("click", ".wsUpdate", function() {
        var type = 'whoSee';
        var ID = $(this).attr("data-id");
        var data = 'f=' + type + '&who=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_whoseech_menu_item_out").removeClass("wselected");
                if (response) {
                    $("#wsUpdate" + ID).addClass("wselected");
                    $(".wBox").html('').append(response);
                    $(".i_choose_ws_wrapper").removeClass('dblock');
                }
                if (ID == '4' && $("div[class='point_input_wrapper']").length === 0) {
                    whoSeePremium();
                } else {
                    $(".point_input_wrapper").remove();
                }
            }
        });
    });

    function whoSeePremium() {
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: 'f=pw_premium',
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $(".aft").after(response);;
                }
            }
        });
    }
    /*Get PopUp for Post Updating WhoCanSee*/
    $(document).on("click", ".wcs", function() {
        var type = 'wcs';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Update WhoCanSee Status for Shared Post*/
    $(document).on("click", ".who_can_see_pop_item", function() {
        var type = 'uwcs';
        var ID = $(this).attr("id");
        var wcs = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&wci=' + wcs;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("#ipublic_" + ID).html('').append(response);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
            }
        });
    });
    /*Call Edit Post PoUpbox*/
    $(document).on("click", ".edtp", function() {
        var type = 'c_editPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delp", function() {
        var type = 'ddelPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Save Post Edit*/
    $(document).on("click", ".sedt", function() {
        var type = 'editS';
        var ID = $(this).attr('id');
        var editText = $("#ed_" + ID).val();
        var data = 'f=' + type + '&id=' + ID + '&text=' + encodeURIComponent(editText);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var editedText = response.text;
                if (responseStatus == 'no') {
                    PopUPAlerts('eCouldNotEmpty', 'ialert');
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (responseStatus == '200') {
                    $("#i_post_container_" + ID).show();
                    $("#i_post_text_" + ID).html(editedText);
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                }
            }
        });
    });
    /*Uploading Music, Video and Image*/
    $(document).on("change", "#i_image_video", function (e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $("#i_image_video").attr("data-id");
        var data = { f: id };

        $('.i_uploaded_iv').append('<div class="i_upload_progress"></div>');

        $("#uploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function () {
                $(".i_warning_unsupported").hide();
                $(".i_uploaded_iv").show();
                $(".i_upload_progress").width('0%');
                $(".publish").prop("disabled", true);
                $(".publish").css("pointer-events", "none");
            },
            uploadProgress: function (e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function (response) {
                if (response != '303') {
                    $(".i_uploaded_file_box").append(response);
                    var K = $('.i_uploaded_item').map(function () { return this.id }).toArray();
                    var T = K + "," + values;
                    if (T != "undefined,") {
                        $("#uploadVal").val(T);
                    }

                } else {
                    $(".i_uploaded_iv , .i_uploading_not").hide();
                    $(".i_warning_unsupported").show();
                }
                $(".i_upload_progress").width('0%');
                $(".i_uploading_not").hide();
                setTimeout(() => {
                    $('.publish').prop('disabled', false);
                    $(".publish").css("pointer-events", "auto");
                }, 3000);
            },
            error: function () { }
        }).submit();
    });

    /*Delete Uploaded File Before Publish*/
    $(document).on("click", ".i_delete_item_button", function() {
        var type = 'delete_file';
        var ID = $(this).attr('id');
        var input = $('#uploadVal');
        var data = 'f=' + type + '&file=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $(".iu_f_" + ID).remove();
                    input.val(function(_, value) {
                        return value.split(',').filter(function(val) {
                            return val !== ID;
                        }).join(',');
                    });
                } else {
                    PopUPAlerts('not_file', 'ialert')
                }
                if (!$(".i_uploaded_item")[0]) {
                    $(".i_uploaded_iv").hide();
                }
            }
        });
    });
    /*Save New Post*/
    $(document).on("click", ".publish", function() {
        var text = $("#newPostT").val();
        var files = $("#uploadVal").val();
        var point = $("#point").val();
        var type = 'newPost';
        var data = 'f=' + type + '&txt=' + encodeURIComponent(text) + '&file=' + files + '&point=' + point;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $('.publish').prop('disabled', true);
                $(".publish").css("pointer-events", "none");
                $(".i_warning_point , .i_warning , .i_warning_prmfl").fadeOut(100);
            },
            success: function(response) {
                $('.publish').prop('disabled', false);
                $(".publish").css("pointer-events", "auto");
                if ($("div").hasClass("noPost")) {
                    $(".noPost").remove();
                }
                if (response == '200') {
                    $(".i_warning").fadeIn();
                } else if (response == '201') {
                    $(".i_warning_point").fadeIn();
                } else if (response == '203') {
                    $(".i_warning_point_two").fadeIn();
                } else if (response == '202') {
                    $(".i_warning_prmfl").fadeIn();
                } else if (response == '204') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    $(".i_uploaded_file_box").html('');
                    $(".i_uploaded_iv").hide();
                    const $newContent = $(response);
                    $("#moreType").prepend($newContent);

                    reInitLightGallery($newContent);
                    reInitPostPlugins($newContent);
                    initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                    $(".newPostT").val('').trigger('change');
                    $("#uploadVal").val('');
                    $("#point").val('');
                }
            }
        });
    });
    /*Like Post*/
    $(document).on("click", ".in_like , .in_unlike", function() {
        var type = 'p_like';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&post=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {
                $('.in_like , .in_unlike').prop('disabled', true);
            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.like;
                var liksCount = response.likeCount;
                if (status == 'in_unlike') {
                    $("#p_l_" + ID).removeClass("in_like").addClass("in_unlike");
                    $("#lp_sum_" + ID).html(liksCount);

                    var $postID = $(".body_" + ID);
                    var $existingLikeHeart = $postID.find('.like_heart');

                    if ($existingLikeHeart.length > 0) {
                        $existingLikeHeart.fadeOut(300, function() {
                            $(this).remove();
                        });
                        clearTimeout($postID.data('likeTimer'));
                    } else {
                        $postID.append(likeBox);
                        var likeTimer = setTimeout(() => {
                            $postID.find(".like_heart").fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 450);
                        $postID.data('likeTimer', likeTimer);
                    }

                } else {
                    $("#p_l_" + ID).removeClass("in_unlike").addClass("in_like");
                    $("#lp_sum_" + ID).html(liksCount);

                    var $postID = $(".body_" + ID);
                    var $existingLikeHeart = $postID.find('.like_heart');

                    if ($existingLikeHeart.length > 0) {
                        $existingLikeHeart.fadeOut(300, function() {
                            $(this).remove();
                        });
                        clearTimeout($postID.data('likeTimer'));
                    } else {
                        $postID.append(UnlikeBox);
                        var likeTimer = setTimeout(() => {
                            $postID.find(".like_heart").fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 450);
                        $postID.data('likeTimer', likeTimer);
                    }

                }
                $("#p_l_" + ID).html(statusIcon);
                $('.in_like , .in_unlike').prop('disabled', false);
            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".in_share", function() {
        var ID = $(this).attr("data-id");
        var type = 'p_share';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&sp=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.in_share').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);

                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                            const $newContent = $(".i_modal_bg_in").last();

                            initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                            reInitPostPlugins($newContent);
                            initGalleriesInDOM($newContent);
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.in_share').prop('disabled', false);
                }
            });
        }
    });

    function PopUPAlerts(ialert, type) {
        var data = 'f=' + type + '&al=' + ialert;
        if (!$(".i_bottom_left_alert_container")[0]) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {

                },
                success: function(response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_bottom_left_alert_container").addClass('fadeOutDown');
                    }, 5000);
                    setTimeout(() => {
                        $(".i_bottom_left_alert_container").remove();
                    }, 5000);
                }
            });
        }
    }
    /*Save Re-Share Post*/
    $(document).on("click", ".re-share", function() {
        var ID = $(this).attr("id");
        var type = 'p_rshare';
        var postText = $(".more_textarea").val();
        var data = 'f=' + type + '&sp=' + ID + '&pt=' + encodeURIComponent(postText);
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                } else {
                    PopUPAlerts('not_Shared', 'ialert');
                }
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del", function() {
        var type = 'deletePost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".shareClose , .no-del , .popClose , .svAC", function() {
        $(".i_modal_in_in").addClass("i_modal_in_in_out");
        setTimeout(() => {
            $(".i_modal_bg_in").remove();
        }, 200);
    });
    /*Update Comment Status*/
    $(document).on("click", ".pcl", function() {
        var type = 'updateComentStatus';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        PopUPAlerts('commentClosed', 'ialert');
                    } else {
                        PopUPAlerts('commentOpened', 'ialert');
                    }
                    $("#dc_" + ID).html(statusIcon);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Pin Post*/
    $(document).on("click", ".i_pnp", function() {
        var type = 'pinpost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                var btns = response.btn;
                if (status) {
                    if (status == '200') {
                        PopUPAlerts('pined', 'ialert');
                        $(".body_" + ID).append(statusIcon);
                    } else {
                        PopUPAlerts('pinClosed', 'ialert');
                        $("#i_pined_post_" + ID).remove();
                    }
                    $(".pbtn_" + ID).html(btns);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Report Post*/
    $(document).on("click", ".rpp", function() {
        var type = 'reportPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".rpp" + ID).html(statusIcon);
                    } else if (status == '404') {
                        $(".rpp" + ID).html(statusIcon);
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Report Post*/
    $(document).on("click", ".svp", function() {
        var type = 'savePost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".in_save_" + ID).html(statusIcon);
                        PopUPAlerts('sAdded', 'ialert');
                    } else if (status == '404') {
                        $(".in_save_" + ID).html(statusIcon);
                        PopUPAlerts('sRemoved', 'ialert');
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
  /*Click To Send Comment -- CORRECTED*/
$(document).on("click", ".sndcom", function() {
    var sendButton = $(this);
    // The context is the form that contains the button we just clicked.
    var form_context = sendButton.closest('.i_comment_form');
    var postID = form_context.find('.nwComment').attr('data-id');

    var value = form_context.find(".nwComment").val();
    var stickerID = form_context.find("#stic_" + postID).val();
    var gif = form_context.find("#cgif_" + postID).val();
    
    Comment(postID, value, 'comment', stickerID, gif, form_context);
});

/*Press Enter To Send Comment -- CORRECTED*/
$(document).on('keydown', ".nwComment", function(e) {
    var key = e.which || e.keyCode || 0;
    if (key == 13 && !e.shiftKey) { // Added !e.shiftKey to allow new lines
        e.preventDefault();
        var textarea = $(this);
        // The context is the form that contains the textarea.
        var form_context = textarea.closest('.i_comment_form');
        var postID = textarea.attr('data-id');

        var value = textarea.val();
        var stickerID = form_context.find("#stic_" + postID).val();
        var gif = form_context.find("#cgif_" + postID).val();

        Comment(postID, value, 'comment', stickerID, gif, form_context);
    }
});
	
	
	/* ======================================================================== */
/* == STEP 3: ADD The Missing Comment Liking Function == */
/* ======================================================================== */

/* Like a Comment -- This function handles liking individual comments */
$(document).on("click", ".c_in_like, .c_in_unlike", function() {
    var likeButton = $(this); // The button that was clicked
    var commentID = likeButton.attr('data-id');
    var postID = likeButton.attr("data-p");
    
    // The context is the specific comment body containing the button
    var comment_context = likeButton.closest('.i_u_comment_body');

    var data = 'f=pc_like&post=' + postID + '&com=' + commentID;
    $.ajax({
        type: 'POST',
        url: siteurl + 'requests/request.php',
        dataType: "json",
        data: data,
        cache: false,
        success: function(response) {
            var status = response.status;
            var statusIcon = response.like;
            var statusTotalLike = response.totalLike;
            
            // We now search for elements ONLY within the specific comment we liked
            var targetButton = comment_context.find(".c_in_l_" + commentID);
            var targetCount = comment_context.find("#t_c_" + commentID);

            if (status == 'c_in_unlike') {
                targetButton.removeClass("c_in_like").addClass("c_in_unlike");
            } else {
                targetButton.removeClass("c_in_unlike").addClass("c_in_like");
            }
            targetCount.html(statusTotalLike);
            targetButton.html(statusIcon);
        }
    });
});
	
	
    /*Send Gif Comment*/
    $(document).on("click", ".rGif", function() {
        var src = $(this).attr("src");
        var ID = $(this).attr("data-id");
        if ($("#comment" + ID).val() === '') {
            Comment(ID, '', 'comment', '', src);
        } else {
            $(".emptyGifArea" + ID).show();
            $(".srcGif" + ID).attr('src', src);
            $("#cgif_" + ID).val(src);
            $(".stickersContainer").remove();
        }
    });
    /*Add Sticker*/
    $(document).on("click", ".addSticker", function() {
        var type = 'addSticker';
        var ID = $(this).attr("id");
        var dataID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pi=' + dataID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".emptyStickerArea" + dataID).html('');
            },
            success: function(response) {
                var sticker_url = response.stickerUrl;
                var stickerID = response.st_id;
                if (sticker_url) {
                    $(".stickersContainer").remove();
                    if ($("#comment" + dataID).val() === '') {
                        Comment(dataID, '', 'comment', stickerID);
                    } else {
                        $(".emptyStickerArea" + dataID).append(sticker_url);
                        $("#stic_" + dataID).val(stickerID);
                    }
                }
            }
        });
    });

/* The Main Comment Sending Function -- FINAL CORRECTED VERSION */
function Comment(postID, value, type, sticker, gif, form_context) {
    var data = 'f=' + type + '&id=' + postID + '&val=' + encodeURIComponent(value) + '&sticker=' + sticker + '&gf=' + gif;
    $.ajax({
        type: 'POST',
        url: siteurl + 'requests/request.php',
        data: data,
        cache: false,
        success: function(response) {
            var panelCommentSection = $('.universal-panel-container').find('#i_user_comments_' + postID);
            var targetCommentSection = panelCommentSection.length ? panelCommentSection : $('#i_user_comments_' + postID);

            if (response == '404') {
                PopUPAlerts('sWrong', 'ialert');
            } else {
                targetCommentSection.append(response);
            }

            if (form_context) {
                var textarea = form_context.find(".nwComment");
                
                // THE FIX: Do all three actions in order.
                textarea.val('');           // 1. Clear the text.
                textarea.trigger('input');  // 2. Trigger resize to fix the height.
                textarea.focus();           // 3. Re-focus to fix the width and make it ready for the next comment.
                
                form_context.find(".emptyStickerArea" + postID).empty();
                form_context.find("#stic_" + postID).val('');
                form_context.find(".emptyGifArea" + postID).hide();
                form_context.find("#cgif_" + postID).val('');
            }
            
            if (panelCommentSection.length) {
                var commentsWrapper = panelCommentSection.closest('.i_post_comments_wrapper');
                commentsWrapper.scrollTop(commentsWrapper[0].scrollHeight);
            }
        }
    });
}
	
	
	
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delCm", function() {
        var type = 'ddelComment';
        var ID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pid=' + postID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Delete Comment*/
    $(document).on("click", ".dlCm", function() {
        var type = 'deletecomment';
        var ID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&cid=' + ID + '&pid=' + postID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                    if (response == '200') {
                        $(".dlCm" + ID).fadeOut();
                        PopUPAlerts('delete_comment_success', 'ialert');
                    } else {
                        PopUPAlerts('delete_comment_not_success', 'ialert');
                    }
                }
            }
        });
    });
    /*Report Comment*/
    $(document).on("click", ".ccp", function() {
        var type = 'reportComment';
        var commentID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + commentID + '&pid=' + postID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".ccp" + commentID).html(statusIcon);
                    } else if (status == '404') {
                        $(".ccp" + commentID).html(statusIcon);
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Call Edit Comment PoUpbox*/
    $(document).on("click", ".cced", function() {
        var type = 'c_editComment';
        var commentID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&cid=' + commentID + '&pid=' + postID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Save Comment Edit*/
    $(document).on("click", ".secdt", function() {
        var type = 'editSC';
        var commentID = $(this).attr('id');
        var postID = $(this).attr('data-id');
        var editText = $("#ed_" + commentID).val();
        var data = 'f=' + type + '&cid=' + commentID + '&pid=' + postID + '&text=' + encodeURIComponent(editText);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var editedText = response.text;
                if (responseStatus == 'no') {
                    PopUPAlerts('eCouldNotEmpty', 'ialert');
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (responseStatus == '200') {
                    $("#i_u_c_" + commentID).html(editedText);
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                }
            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".free_follow", function() {
        var type = 'follow_free_not';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });

    /*Follow Profile Free*/
    $(document).on("click", ".f_p_follow", function() {
        var type = 'freeFollow';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&follow=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".i_modal_content").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var responseStatus = response.status;
                var responseNot = response.text;
                var responseBtn = response.btn;
                if (responseStatus == '200') {
                    $(".i_fw" + ID).html(responseBtn);
                    if (responseNot == 'flw') {
                        $(".i_fw" + ID).removeClass("i_btn_like_item free_follow").addClass("i_btn_like_item_flw f_p_follow");
                        PopUPAlerts('youFollowing', 'ialert');
                    } else if (responseNot == 'unflw') {
                        $(".i_fw" + ID).removeClass("i_btn_like_item_flw f_p_follow").addClass("i_btn_like_item free_follow");
                        PopUPAlerts('youUnfollowing', 'ialert');
                    }
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Block Not PopUp Call*/
    $(document).on("click", ".ublknot", function() {
        var type = 'uBlockNotice';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Choose Block TYPE*/
    $(document).on("click", ".i_redtrict_u", function() {
        var ID = $(this).attr("data-s");
        $(".block_a_status").removeClass("blockboxActive").addClass("blockboxPassive");
        $("#bl_s_" + ID).addClass("blockboxActive");
        $(".ublk").attr('data-bt', ID);
    });
    /*Block User*/
    $(document).on("click", ".ublk", function() {
        var type = 'ublock';
        var ID = $(this).attr("id");
        var blockType = $(this).attr("data-bt");
        var data = 'f=' + type + '&id=' + ID + '&blckt=' + blockType;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    window.location.href = responseRedirect;
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                }

            }
        });
    });
    /*Block Not PopUp Call*/
    $(document).on("click", ".uSubsModal", function() {
        var type = 'subsModal';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

                $(".uSubsModal").prop("disabled", true);
                $(".uSubsModal").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                    $(".uSubsModal").prop("disabled", false);
                    $(".uSubsModal").css("pointer-events", "auto");
                }, 200);
            }
        });
    });
   
    /*Upload Verification Files*/
    $(document).on("change", "#id_card_two, #id_card", function(e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
        $("#vUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".f_" + type).html('');
                $("#uploadVal_" + type).val('');
                $("#" + type).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".f_" + type).append(response);
                    var K = $(".f_" + type + " > div:last").attr("id");
                    var T = K;
                    if (T != "undefined,") {
                        $("#uploadVal_" + type).val(T);
                    }
                    $("#id_card , #id_card_two").val('');
                }
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Send Verification Certificate Request*/
    $(document).on("click", ".v_Next", function() {
        var type = 'verificationRequest';
        var IDCard = $("#uploadVal_sec_one").val();
        var IDPhoto = $("#uploadVal_sec_two").val();
        var data = 'f=' + type + '&cID=' + IDCard + '&cP=' + IDPhoto;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".card , .both , .photo").hide();
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == 'card') {
                    $("#id_card , #id_card_two").val('');
                    $(".card").show();
                } else if (response == 'photo') {
                    $("#id_card , #id_card_two").val('');
                    $(".photo").show();
                } else if (response == 'both') {
                    $("#id_card , #id_card_two").val('');
                    $(".both").show();
                }
                $(".i_nex_btn").css("pointer-events", "auto");
            }
        });
    });
    /*Call Avatar And Cover PopUP*/
    $(document).on("click", ".editAvatarCover", function() {
        var type = 'updateAvatarCover';
        var data = 'f=' + type;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });


    $(document).on('submit', "#myEmailForm", function(e) {
        e.preventDefault();
        var myEmailForm = $(this);
        if ($("#cPass").val().length == 0) {
            $(".warning_required").show();
            return false;
        }
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: myEmailForm.serialize(),
            beforeSend: function() {
                $(".warning_inuse , .warning_invalid , .warning_wrong_password , .warning_required , .warning_same_email").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                myEmailForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    myEmailForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '404') {
                    $(".warning_inuse").show();
                } else if (data == 'no') {
                    $(".warning_invalid").show();
                } else if (data == 'same') {
                    $(".warning_same_email").show();
                } else if (data == '200') {
                    $(".successNot").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });

    $(document).on('submit', "#myProfileForm", function(e) {
        e.preventDefault();
        var myProfileForm = $(this);
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: myProfileForm.serialize(),
            beforeSend: function() {
                $(".invalid_username , .character_warning , .warning_username").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                myProfileForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    myProfileForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '1') {
                    $(".successNot").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".pyot_sNext", function() {
        var type = 'updatePayoutSet';
        var defaultMethod = $('input[name=default]:checked', '#bankForm').val();
        var paypalEmail = $("#paypale").val();
        var repaypalEmail = $("#paypalere").val();
        var bankAccount = $("#bank_transfer").val();
        if (defaultMethod == 'paypal') {
            if (paypalEmail.length == 0) {
                $("#setWarning").show();
                return false;
            } else {
                $("#setWarning").hide();
            }
            if (repaypalEmail.length == 0) {
                $("#setWarning").show();
                return false;
            } else {
                $("#setWarning").hide();
            }
            if (paypalEmail != repaypalEmail) {
                $("#notMatch").show();
                return false;
            } else {
                $("#notMatch").hide();
            }
        }
        if (defaultMethod == 'bank') {
            if (bankAccount.length == 0) {
                $("#setBankWarning").show();
                return false;
            } else {
                $("#setBankWarning").hide();
            }
        }
        var data = 'f=' + type + '&paypalEmail=' + encodeURIComponent(paypalEmail) + '&paypalReEmail=' + encodeURIComponent(repaypalEmail) + '&bank=' + bankAccount + '&method=' + defaultMethod;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '200') {
                    if (response == 'email_warning') {
                        $("#notMatch").show();
                    } else if (response == 'paypal_warning') {
                        $("#setWarning").show();
                    } else if (response == 'bank_warning') {
                        $("#setBankWarning").show();
                    } else if (response == 'not_valid_email') {
                        $("#notValidE").show();
                    }
                }
                setTimeout(() => {
                    $(".successNot").show();
                    $(".loaderWrapper").remove();
                    $(".i_nex_btn").css("pointer-events", "auto");
                }, 3000);
            }
        });
    });

   $(document).on("click", ".mwithdraw", function() {
    var button = $(this);
    var amount = $("#wamount").val();
    var data = 'f=makewithDraw&amount=' + amount;

    // Hide all warnings before making the request
    $(".i_t_warning").hide();
    $(".successNot").hide();

    $.ajax({
        type: "POST",
        url: siteurl + 'requests/request.php',
        data: data,
        cache: false,
        beforeSend: function() {
            button.css("pointer-events", "none");
            // Your loading animation variable
            if (typeof plreLoadingAnimationPlus !== 'undefined') {
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
            }
        },
        success: function(response) {
            // Remove the loader and re-enable the button
            $(".loaderWrapper").remove();
            button.css("pointer-events", "auto");

            // Handle the response from the server
            if (response == '1') {
                $(".successNot").show(); // Success
            } else if (response == '2') {
                $("#mwithdrawal").show(); // Minimum amount error
            } else if (response == '3') {
                $("#nbudget").show(); // Not enough budget
            } else if (response == '4') {
                $("#nnoway").show(); // Generic error
            } else if (response == '5') {
                $("#nwaitpending").show(); // Pending request exists
            } else if (response == '6') {
                $("#no_payout_method").show(); // New: Payout method not set
            } else {
                $("#nnoway").show(); // Fallback for any other error
            }
        },
        error: function() {
            // In case of a network error, also re-enable the button
             $(".loaderWrapper").remove();
             button.css("pointer-events", "auto");
             $("#nnoway").show();
        }
    });
});
    /*Credit Card Form Call*/
    $(document).on("click", ".prcsPost", function() {
        var type = 'pPurchase';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&purchase=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("click", ".prchase_go_wallet", function() {
        var type = 'goWallet';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&p=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                window.location.href = response;
            }
        });
    });
    $(document).on("click", ".buyPoint", function() {
        var type = 'choosePaymentMethod';
        var pointID = $(this).attr("id");
        var data = 'f=' + type + '&type=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $("#p_i_" + pointID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".loaderWrapper").remove();
                $(".credit_plan_box").css("pointer-events", "auto");
            }
        });
    });
    $(document).on("click", ".mcSt", function() {
        if ($(".cSetc")[0]) {
            $(".cSetc").removeClass("dblock");
        }
        $(".cSetc").addClass("dblock");
    });
    /*Get Gifs*/
    $(document).on("click", ".getmGifs", function() {
        var type = 'chat_gifs';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getmGifs").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getmGifs").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    /*Get Gifs*/
    $(document).on("click", ".getmStickers", function() {
        var type = 'chat_stickers';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getmGifs").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getmGifs").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    $(document).on("click", ".getMEmojis", function() {
        var type = 'memoji';
        var ID = $(this).attr("data-type");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getMEmojis").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getMEmojis").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    /*Get Gifs*/
    $(document).on("click", ".chtBtns", function() {
        var type = 'chat_btns';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("ch_fl_btns_container")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".chtBtns").css("pointer-events", "none");
                },
                success: function(response) {
                    $(".chtBtns").css("pointer-events", "auto");
                    $(".fl_btns").append(response);
                }
            });
        }
    });

    function ScrollBottomChat() {
        if ($("div").hasClass("all_messages")) {
            $(".all_messages").stop().animate({ scrollTop: $(".all_messages")[0].scrollHeight }, 100);
        }
    }
    ScrollBottomChat();
    $(document).on('keydown', ".mSize", function(e) {
        var key = e.which || e.keyCode || 0;
        if (key == 13) {
            var type = 'nmessage';
            var ID = $(".message_send_form_wrapper").attr("id");
            var value = $(this).val();
            var gMoney = $("#sicVal").val();
            var gf = '';
            var st = '';
            Message(ID, value, type, gf, st, '', gMoney);
        }
    });
    /*Add Sticker*/
    $(document).on("click", ".MaddSticker", function() {
        var type = 'message_sticker';
        var ID = $(this).attr("id");
        var dataID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pi=' + dataID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".Message_stickersContainer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var stickerID = response.st_id;
                if (stickerID) {
                    $(".Message_stickersContainer").remove();
                    var gMoney = $("#sicVal").val();
                    Message(dataID, '', 'nmessage', stickerID, '', '',gMoney);
                    $(".loaderWrapper").remove();
                }
            }
        });
    });
    /*Send Gif Message*/
    $(document).on("click", ".mrGif", function() {
        var src = $(this).attr("src");
        var ID = $(this).attr("data-id");
        var gMoney = $("#sicVal").val();
        Message(ID, '', 'nmessage', '', src, '',gMoney);
        $(".Message_stickersContainer").remove();
    });

    $(document).on("click", ".emoji_item_m", function() {
        var copyEmoji = $(this).attr("data-emoji");
        var getValue = $(".mSize").val();
        $(".mSize").val(getValue + ' ' + copyEmoji + ' ');
    });
    /*Comment*/
    function Message(ID, value, type, stickerID, gfSrc, file, gMoney) {
        var data = 'f=' + type + '&id=' + ID + '&val=' + encodeURIComponent(value) + '&sticker=' + stickerID + '&gif=' + gfSrc + '&fl=' + file + '&mo='+gMoney;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".Message_stickersContainer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } if(response == '403'){
                    PopUPAlerts('cNotSend', 'ialert');
                }else {
                    $(".all_messages_container").append(response);
                    ScrollBottomChat();
                }
                $(".mSize").val('');
                $(".Message_stickersContainer").remove();
                $(".loaderWrapper").remove();
                $(".i_write_secret_post_price").addClass("boxD");
                $("#sicVal").val('');
            }
        });
    }
    $(document).on("click", ".sendmes", function() {
        var value = $(".mSize").val();
        var ID = $(".message_send_form_wrapper").attr("id");
        var gMoney = $("#sicVal").val();
        Message(ID, value, 'nmessage', '', '', '',gMoney);
    });
    /*Uploading Message Image*/
    $(document).on("change", "#ci_image", function(e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $("#ci_image").attr("data-id");
        var ID = $(".message_send_form_wrapper").attr("id");
        var data = { f: id, c: ID };
        $("#uploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".ch_fl_btns_container").remove();
                $('.message_send_form_wrapper').append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".i_uploaded_iv").show();
                    if (response) {
                        var gMoney = $("#sicVal").val();
                        if(gMoney.length != 0){
                            Message(ID, '', 'nmessage','', '', response, gMoney);
                        }else{
                            Message(ID, '', 'nmessage', '', '', response, '');
                        }
                    }
                }
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Get More Comment*/
    $(document).on("click", ".more_comment", function() {
        var type = 'moreComment';
        var ID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $("#pf_l_" + ID).append(preLoadingAnimation);
                $(".comnts").css("pointer-events", "none");
            },
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    $("#i_user_comments_" + ID).html(response);
                    $(".lc_sum_container_" + ID).remove();
                }
                $(".comnts").css("pointer-events", "auto");
                $(".i_loading").remove();
            }
        });
    });
    $(document).on("click", ".chooseLanguage", function() {
        var type = 'chooseLanguage';
        var data = 'f=' + type;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".chooseLanguage").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".chooseLanguage").css("pointer-events", "auto");
            }
        });
    });
    /*Change Language*/
    $(document).on("click", ".chLang", function() {
        var type = 'changeMyLang';
        var id = $(this).attr("id");
        var data = 'f=' + type + '&id=' + id;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".chLang").css("pointer-events", "none");
                $(".purchase_post_details").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".chLang").css("pointer-events", "auto");
                if (response == '200') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 200);
                }

            }
        });
    });

    /*Search Creator*/
    $(document).delegate('#search_creator', 'keyup', function() {
        var searchValue = $(this).val();
        var type = 'searchCreator';
        var sum = searchValue.replace(/\s+/g, '').length;
        if (sum >= 1) {
            $(".i_general_box_search_container").show();
            setTimeout(() => {
                var data = 'f=' + type + '&s=' + encodeURIComponent(searchValue);

                $.ajax({
                    type: "POST",
                    url: siteurl + 'requests/request.php',
                    data: data,
                    cache: false,
                    beforeSend: function() {
                        $(".sb_items").append(plreLoadingAnimationPlus);
                    },
                    success: function(response) {
                        $(".sb_items").html(response);
                    }
                });

            }, 1000);
        } else {
            $(".i_general_box_search_container").hide();
        }
    });
    $(document).on("mouseup", function(e) {
        var container = $(".i_general_box_search_container");
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
            $(".sb_items").html('');
        }
    });
    $(document).on("click", ".newMessageMe", function() {
        var type = 'newMessageMe';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&user=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".newMessageMe").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".newMessageMe").css("pointer-events", "auto");
            }
        });
    });
    /*Send New Message*/
    $(document).on("click", ".sndNewMessage", function() {
        var type = 'newfirstMessage';
        var value = $("#sendNewM").val();
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&fm=' + encodeURIComponent(value) + '&u=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".sndNewMessage").css("pointer-events", "none");
            },
            success: function(response) {
                if (response != '404') {
                    window.location.href = response;
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
            }
        });
    });
    /*Update Theme*/
    $(document).on("click", ".updateTheme", function() {
        var type = 'updateTheme';
        var theme = $(this).attr("data-id");
        var data = 'f=' + type + '&theme=' + theme;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".sndNewMessage").css("pointer-events", "none");
            },
            success: function(response) {
                if (response != '404') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".mobile_hamburger", function() {
        if (!$(".leftStickyActive")[0]) {
            $(".mobile_left").addClass("leftStickyActive");
            $(".is_mobile").addClass("svg_active_icon");
        } else {
            $(".mobile_left").removeClass("leftStickyActive");
            $(".is_mobile").removeClass("svg_active_icon");
        }
    });
    $(document).on("click", ".mobile_srcbtn", function() {
        if (!$(".i_search_active")[0]) {
            $(".i_search").addClass("i_search_active");
        } else {
            $(".i_search").removeClass("i_search_active");
        }
    });
    $(window).on("resize", function() {
        checkWidth();
    });
    checkWidth();

    function checkWidth() {
        var vWidth = $(window).width();
        if (vWidth < 700) {
            if (!$(".mobile_footer_fixed_menu_container")[0]) {
                $.ajax({
                    type: "POST",
                    url: siteurl + 'requests/request.php',
                    data: 'f=fixedMenu',
                    cache: false,
                    beforeSend: function() {
                        $(".sndNewMessage").css("pointer-events", "none");
                    },
                    success: function(response) {
                        if (!$(".mobile_footer_fixed_menu_container")[0] && !$(".live_video_header")[0]) {
                            $("body").append(response);
                        }
                    }
                });
            }
        } else {
            if ($(".mobile_footer_fixed_menu_container")[0]) {
                $(".mobile_footer_fixed_menu_container").remove();
            }
        }
    }
    $(document).on("keyup keypress keydown", ".nwComment", function() {
        var ID = $(this).attr("data-id");
        var check = $(this).val().length;
        var $vWidth = $(window).width();
        if (check > 20) {
            $(".i_comment_footer" + ID).addClass("commentFooterResize");
        } else {
            $(".i_comment_footer" + ID).removeClass("commentFooterResize");
        }
    });
    $(document).on("click", ".settings_mobile_menu_container", function() {
        if (!$(".settingsMenuDisplay")[0]) {
            $(".i_settings_menu_wrapper").addClass("settingsMenuDisplay");
        } else {
            $(".i_settings_menu_wrapper").removeClass("settingsMenuDisplay");
        }
    });
    $(document).on("click", ".cList", function() {
        if (!$(".chatDisplay")[0]) {
            $(".chat_left_container").addClass("chatDisplay");
        } else {
            $(".chat_left_container").removeClass("chatDisplay");
        }
    });
    /*Delete Message*/
    $(document).on("click", ".dlMesv", function() {
        var type = 'deleteMessage';
        var ID = $(this).attr("id");
        var cID = $(".cList").attr("id");
        var data = 'f=' + type + '&id=' + ID + '&cid=' + cID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("#msg_" + ID).remove();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
            }
        });
    });
    $(document).on("click", ".delmes", function() {
        var type = 'ddelMesage';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".d_conversation", function() {
        var type = 'ddelConv';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Delete Message*/
    $(document).on("click", ".dlConv", function() {
        var type = 'deleteConversation';
        var ID = $(this).attr("id");
        var cID = $(".cList").attr("id");
        var data = 'f=' + type + '&id=' + ID + '&cid=' + cID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Search Creator*/
    $(document).delegate('#c_search', 'keyup', function() {
        var searchValue = $(this).val();
        var type = 'searchUser';
        var sum = searchValue.replace(/\s+/g, '').length;
        if (sum >= 3) {
            $(".chat_users_wrapper_results").show();
            $(".chat_users_wrapper").hide();
            setTimeout(() => {
                var data = 'f=' + type + '&key=' + encodeURIComponent(searchValue);
                $.ajax({
                    type: "POST",
                    url: siteurl + 'requests/request.php',
                    data: data,
                    cache: false,
                    beforeSend: function() {
                        $(".chat_users_wrapper_results").append(plreLoadingAnimationPlus);
                    },
                    success: function(response) {
                        if (response) {
                            $(".chat_users_wrapper_results").html(response);
                        } else {
                            $(".chat_users_wrapper_results").hide().html('');
                            $(".chat_users_wrapper").show();
                        }
                    }
                });
            }, 1000);
        } else {
            $(".chat_users_wrapper_results").hide().html('');
            $(".chat_users_wrapper").show();
        }
    });
    /*Hide Notification*/
    $(document).on("click", ".hidNot", function() {
        var type = 'hideNotification';
        var hideID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + hideID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".hidNot_" + hideID).fadeOut();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*UnBlock User*/
    $(document).on("click", ".unblock", function() {
        var type = 'unblock';
        var ID = $(this).attr("id");
        var blockedUserID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID + '&u=' + blockedUserID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".block_id_" + ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    $(".block_id_" + ID).fadeOut();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });

    $(document).on('submit', '#myPasswordChange', function(e) {
        e.preventDefault();
        var passChange = $(this);
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: passChange.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_not_mach , .warning_not_correct , .warning_write_current_password , .no_new_password_wrote , .minimum_character_not , .not_contain_whitespace").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                passChange.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    passChange.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '1') {
                    $(".warning_not_correct").show();
                } else if (data == '2') {
                    $(".warning_not_mach").show();
                } else if (data == '3') {
                    $(".warning_write_current_password").show();
                } else if (data == '4') {
                    $(".no_new_password_wrote").show();
                } else if (data == '5') {
                    $(".minimum_character_not").show();
                } else if (data == '6') {
                    $(".not_contain_whitespace").show();
                } else if (data == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    window.location.href = data;
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Notifications*/
    $(document).on("click", ".setChange", function() {
        var type = 'p_preferences';
        var setChange = $(this).val();
        var setType = $(this).attr("id");
        var dataNot = 'f=' + type + '&notit=' + encodeURIComponent(setChange) + '&sType=' + setType;
        $.ajax({
            type: 'POST',
            url: siteurl + "requests/request.php",
            data: dataNot,
            cache: false,
            beforeSend: function() {
                $("." + setType).append(plreLoadingAnimationPlus);
                $('.setChange').attr('disabled', true);
            },
            success: function(response) {
                setTimeout(function() {
                    $('.setChange').attr('disabled', false);
                }, 500);
                if (response == '200') {
                    if (setChange == '0') {
                        $("#" + setType).val('1');
                    }
                    if (setChange == '1') {
                        $("#" + setType).val('0');
                    }
                }
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1500);
            }
        });
    });
    /*Close Alert*/
    $(document).on("click", ".i_alert_close", function() {
        $(".i_bottom_left_alert_container").remove();
    });
    /*Create a Live Streaming PopUp Call*/
    $(document).on("click", ".cNLive", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + "requests/request.php",
            data: data,
            cache: false,
            beforeSend: function() {
                $("." + type).append(plreLoadingAnimationPlus);
                $("." + type).attr('disabled', true);
            },
            success: function(response) {
                setTimeout(function() {
                    $("." + type).attr('disabled', false);
                }, 500);
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1500);
            }
        });
    });
    /*Save Live Streaming*/
    $(document).on("click", ".createLiveStream", function() {
        var type = 'createFreeLiveStream';
        var liveStreamingTitle = $("#liveName").val();
        var data = 'f=' + type + '&lTitle=' + encodeURIComponent(liveStreamingTitle);
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() { $(".warning_required").hide(); },
            success: function(response) {
                var status = response.status;
                var start = response.start;
                if (status == '200') {
                    window.location.href = start;
                } else if (status == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (status == 'require') {
                    $(".require").show();
                } else if (status == '4') {
                    $(".name_short").show();
                }
            }
        });
    });
    /*Like Post*/
    $(document).on("click", ".lin_like , .lin_unlike", function() {
        var type = 'l_like';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&post=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {
                $('.lin_like , .lin_unlike').prop('disabled', true);
            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.like;
                var liksCount = response.likeCount;
                if (status == 'lin_unlike') {
                    $("#p_l_l_" + ID).removeClass("lin_like").addClass("lin_unlike");
                    $("#lp_sum_l_" + ID).html(liksCount);
                } else {
                    $("#p_l_l_" + ID).removeClass("lin_unlike").addClass("lin_like");
                    $("#lp_sum_l_" + ID).html(liksCount);
                }
                $("#p_l_l_" + ID).html(statusIcon);
                $('.lin_like , .lin_unlike').prop('disabled', false);
            }
        });
    });
    /*Save Live Streaming*/
    $(document).on("click", ".createLiveStreamPaid", function() {
        var type = 'createPaidLiveStream';
        var liveStreamingTitle = $("#liveName").val();
        var liveFee = $("#lsFee").val();
        var data = 'f=' + type + '&lTitle=' + encodeURIComponent(liveStreamingTitle) + '&pointfee=' + liveFee;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".warning_required").hide();
            },
            success: function(response) {
                var status = response.status;
                var start = response.start;
                if (status == '200') {
                    window.location.href = start;
                } else if (status == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (status == 'point') {
                    $(".point_warning").show();
                } else if (status == 'title') {
                    $(".title_warning").show();
                } else if (status == 'require') {
                    $(".require").show();
                }
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".purchaseLiveButton", function() {
        var type = 'pLivePurchase';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&purchase=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("click", ".joinLiveStream", function() {
        var type = 'goWalletLive';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&p=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                window.location.href = response;
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".unSubU", function() {
        var type = 'unSub';
        var post = $(this).attr("data-u");
        var data = 'f=' + type + '&u=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".unSubUP", function() {
        var type = 'unSubP';
        var post = $(this).attr("data-u");
        var data = 'f=' + type + '&u=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*UnSubscribe PointUser*/
    $(document).on("click", ".unSubPNow", function() {
        var type = 'unSubscribePoint';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    location.reload();
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                }

            }
        });
    });
    /*UnSubscribe User*/
    $(document).on("click", ".unSubNow", function() {
        var type = 'unSubscribe';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    location.reload();
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                }

            }
        });
    });
    /*Upload Verification Files*/
    $(document).on("change", ".cTumb", function(e) {
        e.preventDefault();
        var f = 'vTumbnail';
        var id = $(this).attr("data-id");
        var data = { f: f, id: id };
        $("#tuploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".iu_f_" + id).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                $("#viTumb" + id).css('background-image', 'url(' + response + ')');
                $("#viTumbi" + id).attr('src', response);
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    
	
	
	

	
	
	
	
	
	
	
	
	
	
	
	
    /*Call Post for Share*/
    $(document).on("click", ".in_tips", function() {
        var ID = $(this).attr("data-id");
        var tipPostID = $(this).attr("data-ppid");
        var type = 'p_tips';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tip_u=' + ID + '&tpid=' + tipPostID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.in_tips').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.in_tips').prop('disabled', false);
                }
            });
        }
    });
 // SEND TIPS — feed/profile (scoped to the opened modal)
$(document).on("click", ".send_tip_btn", function () {
  var $m        = $(this).closest(".i_modal_bg_in");   // scope: this modal only
  var $fee      = $m.find(".i_set_subscription_fee");
  var ID        = $fee.attr("id");                     // tip_u (receiver id)
  var tipPostID = $fee.attr("data-pid") || "";         // post id (optional)
  var tipValue  = ($m.find("#tipVal").val() || "").toString().trim();

  if (!ID) return;
  if (!tipValue) { $m.find(".i_tip_not").css("color", "red"); return; }

  $.ajax({
    type: "POST",
    url: siteurl + 'requests/request.php',
    data: 'f=p_sendTip'
        + '&tip_u=' + encodeURIComponent(ID)
        + '&tipVal=' + encodeURIComponent(tipValue)
        + '&tpid='   + encodeURIComponent(tipPostID),
    dataType: "json",
    cache: false,
    beforeSend: function () { $m.find('.send_tip_btn').prop('disabled', true); },
    success: function (res) {
      if (res.status === 'ok') {
        if (tipPostID) { showBubble(ID, tipPostID); }  // feed bubble only

        // graceful close
        $m.find(".i_modal_in_in").addClass("i_modal_in_in_out");
        setTimeout(function(){ $m.remove(); }, 200);

        // hard fallback (in case another old handler kept it around)
        setTimeout(function(){ $('.i_modal_bg_in').remove(); }, 500);
      } else if (res.enamount === 'notEnough') {
        $m.find(".i_tip_not").css("color", "red");
      } else if (res.enamount === 'notEnouhCredit') {
        window.location.href = res.redirect;
      }
    },
    complete: function(){ $m.find('.send_tip_btn').prop('disabled', false); }
  });
});

	

    function showBubble(userid, postid) {
        $(".tip_" + postid).show();
        document.getElementById('notification-sound-coin').play();
        setTimeout(() => {
            $(".tip_" + postid).fadeOut();
        }, 5000);
    }
    $(document).on("click", ".live_coin_send", function() {
        var ID = $(this).attr("data-u");
        var tipValue = $(this).attr("data-tip");
        var liveID = $(".live_wrapper_tik").attr("id");
        var type = 'p_sendGift';
        var data = 'f=' + type + '&tip_u=' + ID + '&tipTyp=' + tipValue + '&lid=' + liveID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $('.co_' + tipValue).append(plreLoadingAnimationPlus);
                $(".live_animation_wrapper").remove();
            },
            success: function(response) {
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1000);
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                var senamount = response.enamount;
                var sendSuccessAnimation = response.giftAnimation;
                var currentBalance = response.current_balance;
                if (responseStatus == 'ok') {
                    $(".filtvid").append(sendSuccessAnimation);
                    setTimeout(() => {
                        $(".live_animation_wrapper").remove();
                    }, 5000);
                    $(".crnblnc").html(currentBalance);
                }
                if (senamount == 'notEnough') {
                    $(".i_tip_not").css("color", "red");
                }
                if (senamount == 'notEnouhCredit') {
                    window.location.href = responseRedirect;
                }
            }
        });
    });
    /*QR Code Generator*/
    $(document).on("click", ".qrCodeGenerator", function() {
        var type = 'generateQRCode';
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $('.createQrBox').append(plreLoadingAnimationPlus);
                $('.qrCodeGenerator').prop('disabled', true);
            },
            success: function(response) {
                $(".loaderWrapper").remove();
                if (response) {
                    $(".qrCodeImage").html('<img src="' + response + '">');
                }
                $('.qrCodeGenerator').prop('disabled', false);
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del-story", function() {
        var type = 'deleteStorie';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del-product", function() {
        var type = 'deleteProduct';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    /*Share This Story*/
    $(document).on("click", ".share_this_story", function() {
        var type = 'shareMyStorie';
        var ID = $(this).attr("id");
        var text = $(".st_txt_" + ID).val();
        var data = 'f=' + type + '&id=' + ID + '&txt=' + encodeURIComponent(text);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('shared_storie_success', 'ialert');
                    window.location = siteurl;
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });


	
	
	
	
	
/* MF OVERRIDE — legacy mention UI disabled (handled by new endpoint/UI) */
(function($){
  // keep helper available if other code calls it
  $.fn.focusTextToEnd = $.fn.focusTextToEnd || function(){
    this.focus();
    var v = this.val();
    this.val('').val(v);
    return this;
  };
  // make sure any old bindings are gone (safety)
  $(document).off('keyup', '.newPostT');
  $(document).off('click', '.mres_u');
})(jQuery);

	
	
	
	
	

    var timeoutId;
    // Append User Profile Card
    $(document).on("mouseenter", ".ownTooltip", function() {
        if ($(window).width() > 800) {
            var tooltipText = $(this).attr("data-label");
            $(this).append("<div class='ownTooltipWrapper'>" + tooltipText + "</div>");
        }
    });
    $(document).on('mouseleave', '.ownTooltip', function() {
        $(".ownTooltipWrapper").fadeOut("normal", function() {
            window.clearTimeout(timeoutId);
            timeoutId = null;
            $(this).remove();
        });
    });
    /*Get PopUp for Which Story*/
    $(document).on("click", ".chsStoryw", function() {
        var type = 'whcStory';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    $(document).on("click", ".share_text_story", function() {
        var type = 'shareMyTextStory';
        var ID = $(".choosed_bg").attr("data-iid");
        var textStory = $("#strt_text").val();
        var data = 'f=' + type + '&id=' + ID + '&stext=' + encodeURIComponent(textStory);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    PopUPAlerts('shared_storie_success', 'ialert');
                    setTimeout(() => {
                        window.location = siteurl;
                    }, 1000);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".buy__myproduct", function() {
        var type = 'buyProduct';
        var pointID = $(this).attr("data-id");
        var data = 'f=' + type + '&type=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $("#p_i_" + pointID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == 'me') {
                    PopUPAlerts('cnbowni', 'ialert');
                } else {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                    $(".loaderWrapper").remove();
                    $(".credit_plan_box").css("pointer-events", "auto");
                }
            }
        });
    });
    $(document).on("click", ".s_p_p_p_download", function() {
        var type = 'downloadMyProduct';
        var pointID = $(this).attr("data-id");
        var data = 'f=' + type + '&myp=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'dfile.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".s_p_p_p_download").css("pointer-events", "none");
            },
            success: function(response) {
                if (response == 'me') {
                    PopUPAlerts('sRong', 'ialert');
                }
                setTimeout(() => {
                    $(".s_p_p_p_download").css("pointer-events", "auto");
                }, 2000);

            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".sendPoint", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_tips';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPoint').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPoint').prop('disabled', false);
                }
            });
        }
    });
    $(document).on("click", ".sendPointMessage", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_tips_message';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPointMessage').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPointMessage').prop('disabled', false);
                }
            });
        }
    });
    function VideoCallAlert(callID) {
        var data = 'f=videoCallAlert' + '&call=' + callID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                        $("#notification-sound-call")[0].play();
                    }, 200);
                }
            }
        });
    }
    $(document).on("click", ".call_accept", function() {
        var ID = $(this).attr("id");
        var type = 'call_accepted';
        var data = 'f=' + type + '&accID=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $('.call_accept').prop('disabled', true);
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response) {
                    $("#notification-sound-call")[0].pause();
                    window.location.href = response;
                }
            }
        });
    });
    $(document).on("click", ".call_decline", function() {
        var ID = $(this).attr("id");
        var type = 'call_declined';
        var data = 'f=' + type + '&accID=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $('.call_decline').prop('disabled', true);
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_bg_in").remove();
                $("#notification-sound-call")[0].pause();
            }
        });
    });
    /*SEND TIPS*/
    $(document).on("click", ".send_tip_btn_message", function() {
        var ID = $(".subU").attr("id");
        var chatID = $(".message_send_form_wrapper").attr("id");
        var tipValue = $("#tipVal").val();
        var type = 'p_sendTipMessage';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tip_u=' + ID + '&tipVal=' + tipValue +'&chID=' + chatID;
            $.ajax({
                type: "POST",
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.send_tip_btn').prop('disabled', true);
                },
                success: function(response) {
                    if (response == 'notEnough') {
                        $(".i_tip_not").css("color", "red");
                    }
                    if (response == 'notEnouhCredit') {
                        window.location.href = siteurl + 'purchase/purchase_point';
                    }

                    if (response != '404') {
                        if(response){
                            PopUPAlerts('tipSuccess', 'ialert');
                            $(".i_modal_in_in").addClass("i_modal_in_in_out");
                            setTimeout(() => {
                                $(".i_modal_bg_in").remove();
                            }, 200);
                            $(".all_messages_container").append(response);
                            ScrollBottomChat();
                        }else{
                            $(".aval").val('').focus();
                        }
                    }
                    $('.send_tip_btn_message').prop('disabled', false);
                }
            });
        }
    });


    /*Unlock Message*/
    $(document).on("click",".unlockFor", function(){
        var ID = $(this).attr("id");
        var cID = $(".message_send_form_wrapper").attr("id");
        var type = 'unlockMessage';
        var data = 'f='+type+'&mi='+ID+'&ci='+cID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
            },
            success: function(response) {
                if(response == '404'){
                   $(".unlc_"+ID).show();
                }else if(response == '403'){
                  PopUPAlerts('tipSuccess', 'ialert');
                }else{
                   $("#msg_"+ID).html('').append(response);
                }
            }
        });
    });
    $(document).on("click",".joinOffline", function(){
        PopUPAlerts('camOffline', 'camAlert');
    });
    $(document).on("click",".rplyComment", function(){
        var ID = $(this).attr('id');
        var who = $(this).attr("data-who");
        var textComment = $("#comment"+ID).focus().val('@'+who+' ');
    });
    $(document).on("click",".boostThisPost", function(){
        var type = 'getBoostList';
        var ID = $(this).attr("id");
        var data = 'f='+type+'&bp='+ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("click",".bThisP", function(){
        var type = 'boostThisPlan';
        var planID = $(this).attr("id");
        var postID = $(this).attr("data-post");
        var data = 'f='+type+'&pbID='+planID+'&bpID='+postID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
                $(".warning_boost_post").hide();
            },
            success: function(response) {
                if(response == '404'){
                  $(".warning_boost_post").show();
                }else{
                   window.location.href = response;
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Notifications*/
    $(document).on("click", ".boosStat", function() {
        var type = 'updateBoostStatus';
        var setChange = $(this).val();
        var setType = $(this).attr("data-id");
        var dataNot = 'f=' + type + '&mod=' + encodeURIComponent(setChange) + '&bpid=' + setType;
        $.ajax({
            type: 'POST',
            url: siteurl + "requests/request.php",
            data: dataNot,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {

            }
        });
    });
    $(document).on("click",".bankOpen", function(){
        if ($("div").hasClass("displayNone")) {
            $(".bank_container").removeClass('displayNone');
        }else{
            $(".bank_container").addClass('displayNone');
        }
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_card", function(e) {
        e.preventDefault();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
        $("#pBUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".f_" + type).html('');
                $("#uploadVal_" + type).val('');
                $("#" + type).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".f_" + type).append(response);
                    var K = $(".f_" + type + " > div:last").attr("id");
                    var T = K;
                    if (T != "undefined,") {
                        $("#uploadVal_" + type).val(T);
                    }
                    $("#id_card").val('');
                }
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Send Verification Certificate Request*/
    $(document).on("click", ".bnk_Next", function() {
        var type = 'verificationRequestForBankPayment';
        var IDPhoto = $("#uploadVal_sec_one").val();
        var planID = $(this).attr('id');
        var data = 'f=' + type + '&cP=' + IDPhoto + '&pID=' +planID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".card , .both , .photo").hide();
            },
            success: function(response) {
                if (response == '200') {
                    $(".payment_success_bank").show();
                    $(".bank_container").hide();
                    setTimeout(() => {
                        window.location.href = siteurl + 'settings?tab=purchased_points';
                    }, 5000);
                } else if (response == 'card') {
                    $("#id_card").val('');
                    $(".card").show();
                } else if (response == 'photo') {
                    $("#id_card").val('');
                    $(".photo").show();
                } else if (response == 'both') {
                    $("#id_card").val('');
                    $(".both").show();
                }
                $(".i_nex_btn").css("pointer-events", "auto");
            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".sendFrame", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_frame';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPoint').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPoint').prop('disabled', false);
                }
            });
        }
    });
    $(document).on("click", ".buyFrameGift", function() {
        var type = 'buyFrameGift';
        var pointID = $(this).attr("id");
        var purchaseToThisUser = $(".profile_wrapper").attr("data-u");
        var data = 'f=' + type + '&type=' + pointID + '&pUf=' + purchaseToThisUser;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $("#p_i_" + pointID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if(response == '404'){
                    PopUPAlerts('sWrong', 'ialert');
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                    $(".loaderWrapper").remove();
                    $(".credit_plan_box").css("pointer-events", "auto");
                }else if(response == '200') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                    $(".loaderWrapper").remove();
                    $(".credit_plan_box").css("pointer-events", "auto");
                    PopUPAlerts('buySuccess', 'ialert');
                    setTimeout(() => {
                        location.reload();
                    }, 5000);
                }else {

                }
            }
        });
    });
    /*Update My Frame*/
    $(document).on("click", ".updateMyFrame", function() {
        var type = 'UpdateMyFrame';
        var frameID = $(this).attr("data-id");
        var data = 'f=' + type + '&frameID=' + frameID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $(".credit_plan_box_" + frameID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if(response == '400'){
                    PopUPAlerts('sWrong', 'ialert');
                }else{
                    PopUPAlerts('frameSuccess', 'ialert');
                }

                setTimeout(() => {
                   $(".loaderWrapper").remove();
                   $(".credit_plan_box").css("pointer-events", "auto");
                }, 3000);

            }
        });
    });
    /*Update My Frame*/
    $(document).on("click", ".inv_btn", function() {
        var type = 'inviteFriend';
        var iemail = $("#inv_email").val();
        var data = 'f=' + type + '&invEmail=' + encodeURIComponent(iemail);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/inviteEmail.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".inv_btn").css("pointer-events", "none");
                $(".already_in_use").hide();
                $(".inviteemail").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if(response == '404'){
                    PopUPAlerts('sWrong', 'ialert');
                }else if(response == '1'){
                    $(".already_in_use").show();
                }else{
                    PopUPAlerts('emailSendsuccess', 'ialert');
                }
                $(".inviteemail_input").val('');
                setTimeout(() => {
                   $(".inv_btn").css("pointer-events", "auto");
                   $(".already_in_use").hide();
                }, 3000);
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".stat_icon", function() {
        $(this).hide();
        $(this).next(".stat_icona").show();
        var ID = $(this).attr("id");
        $(".bstatistick_"+ID).addClass("changeHeight");
    });

    $(document).on("click", ".stat_icona", function() {
        $(this).hide();
        $(this).prev(".stat_icon").show();
        var ID = $(this).attr("id");
        $(".bstatistick_"+ID).removeClass("changeHeight");
    });

    $(document).on("click", ".move_my_point", function () {
      const type = "moveMyAffilateBalance";
      const point = window.affiliateConfig?.earnings || 0;
      const data = "f=" + type + "&myp=" + point;

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function () {
          $(".move_my_point").hide().css("pointer-events", "none");
        },
        success: function (response) {
          if (response === "me") {
            PopUPAlerts("sRong", "ialert");
          } else {
            location.reload();
          }

          setTimeout(() => {
            $(".move_my_point").show().css("pointer-events", "auto");
          }, 2000);
        }
      });
    });
    $(document).on("click",".bCountry", function(){
        var ID = $(this).attr("data-c");
        var i = $(this).attr("id");
        var type = 'bCountry';
        var data =  'f='+type+'&c='+ID;
        $.ajax({
          type: 'POST',
          url: siteurl + "requests/request.php",
          data: data,
          cache: false,
          success: function(response) {
             if(response == '1'){
               $("#"+i).addClass('chsed');
             }else{
               $("#"+i).removeClass('chsed');
             }
          }
        });
      });
      $(document).on("click", ".move_my_point_earn", function () {
      const data = {
        f: "moveMyEarnedPoints",
        myp: window.earnedPointData?.allTotal || 0
      };

      const $button = $(".move_my_point");
      const $alertBox = $(window.earnedPointData.alertSuccessSelector);

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function () {
          $button.hide().css("pointer-events", "none");
          $alertBox.text("").hide();
        },
        success: function (response) {
          if (response === "me") {
            PopUPAlerts("sRong", "ialert");
          } else if (response === "ok") {
            location.reload();
          } else {
            $alertBox.text(response).show();
          }

          setTimeout(() => {
            $button.show().css("pointer-events", "auto");
          }, 2000);
        }
      });
    });
    // Toggle advanced settings (question and slots)
    $(document).on("change", ".subfeea", function () {
      const type = $(this).data("id");
      const isChecked = $(this).val() === "ok";

      if (isChecked) {
        $("#" + type).val("not");
        $("." + type).hide();
      } else {
        $("#" + type).val("ok");
        $("." + type).show();
      }
    });

    // Save product edit
    $(document).on("click", ".pr_save_btna", function () {
      const data = {
        f: "saveEditPr",
        prid: window.editProduct.productID,
        prnm: $("#pr_name").val(),
        prprc: $("#pr_price").val(),
        prdsc: $("#pr_description").val(),
        prdscinf: $("#pr_conf").val(),
        lmSlot: $("#limitslots").val(),
        askQ: $("#askaquestion").val(),
        qAsk: $("#question_ask").val(),
        lSlot: $("#limit_slot").val()
      };

      $(window.editProduct.warningTextSelector).hide();
      $(window.editProduct.successTextSelector).hide();

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        success: function (response) {
          if (response === "200") {
            $(window.editProduct.successTextSelector).show();
          } else {
            $(window.editProduct.warningTextSelector).show();
          }
        }
      });
    });
    $(document).ready(function () {
        let debounceTimer;

        $(document).on("keyup", "#newEmail", function () {
          clearTimeout(debounceTimer);
          const email = $(this).val();

          if (email.length < 3) return;

          debounceTimer = setTimeout(() => {
            const data = {
              f: "checkemail",
              newEmail: email
            };

            $(".warning_inuse, .warning_invalid").hide();

            $.ajax({
              type: "POST",
              url: siteurl + "requests/request.php",
              data: data,
              cache: false,
              success: function (response) {
                if (response === "404") {
                  $(".warning_invalid").hide();
                  $(".warning_inuse").show();
                } else if (response === "no") {
                  $(".warning_inuse").hide();
                  $(".warning_invalid").show();
                } else {
                  $(".warning_inuse, .warning_invalid").hide();
                }
              }
            });
          }, 500);
    });
    $(document).on("keyup", ".aval", function () {
      const val = parseFloat($(this).val());
      const ID = $(this).attr("id");

      $(".i_t_warning").hide();

      if (ID === "spweek" && val < parseFloat(window.subscriptionConfig.minWeekAmount)) {
        $("#waweekly").show();
      } else if (ID === "spmonth" && val < parseFloat(window.subscriptionConfig.minMonthAmount)) {
        $("#mamonthly").show();
      } else if (ID === "spyear" && val < parseFloat(window.subscriptionConfig.minYearAmount)) {
        $("#yayearly").show();
      }
    });

    $(document).on("change", ".subfeea", function () {
      const type = $(this).data("id");
      const currentVal = $(this).val();
      $(this).val(currentVal === "1" ? "0" : "1");
    });

    
		

// SAVE subscription fees (robust)
$(document).off("click", ".c_uNext").on("click", ".c_uNext", function () {
  const loading = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
  const loader  = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + loading + '</div></div></div>';

  // read toggle value safely whether it's checkbox, hidden, or custom switch
  const getFlag = (name) => {
    const el = document.querySelector(`input[name="${name}"]`);
    if (!el) return "0";
    if (el.type === "checkbox" || el.type === "radio") return el.checked ? "1" : "0";
    const v = (el.value || "").toString().toLowerCase();
    return (v === "1" || v === "true" || v === "on") ? "1" : "0";
  };

  const data = {
    f: "updateSubscriptionPayments",
    wSubWeekAmount: window.subscriptionConfig?.subWeekStatus     === "yes" ? $("#spweek").val()  : "",
    mSubMonthAmount: window.subscriptionConfig?.subMonthlyStatus === "yes" ? $("#spmonth").val() : "",
    mSubYearAmount: window.subscriptionConfig?.subYearlyStatus   === "yes" ? $("#spyear").val()  : "",
    wStatus: getFlag("weekly"),
    mStatus: getFlag("monthly"),
    yStatus: getFlag("yearly")
  };

  // basic validation
  if (data.wStatus === "1" && !data.wSubWeekAmount)  { $("#wweekly").show();  return; }
  if (data.mStatus === "1" && !data.mSubMonthAmount) { $("#wmonthly").show(); return; }
  if (data.yStatus === "1" && !data.mSubYearAmount)  { $("#wyearly").show();  return; }

  $(".i_nex_btn").css("pointer-events", "none");
  $("#wweekly,#wmonthly,#wyearly,.weekly_success,.monthly_success,.yearly_success").hide();
  $(".i_become_creator_box_footer").append(loader);

  $.ajax({
    type: "POST",
    url: siteurl + "requests/request.php",
    data: data,
    dataType: "text",
    cache: false
  })
  .done(function (raw) {
    let res;
    try { res = typeof raw === "string" ? JSON.parse(raw) : raw; }
    catch { console.warn("[fees] Non-JSON:", raw); PopUPAlerts('sWrong','ialert'); return; }

    if (res.weekly  === "404") $("#wweekly").show();
    if (res.weekly  === "200") $(".weekly_success").show();

    if (res.monthly === "404") $("#wmonthly").show();
    if (res.monthly === "200") $(".monthly_success").show();

    if (res.yearly  === "404") $("#wyearly").show();
    if (res.yearly  === "200") $(".yearly_success").show();
  })
  .fail(function (xhr) {
    console.warn("[fees] AJAX fail", xhr.status, xhr.responseText);
    PopUPAlerts('sWrong','ialert');
  })
  .always(function () {
    $(".loaderWrapper").remove();
    $(".i_nex_btn").css("pointer-events", "auto");
  });
});

		

		

    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdProd", function() {
        var type = 'productStatus';
        var value = $(this).val();
        var prID = $(this).attr("data-id");
        var data = 'f=' + type + '&mod=' + value + '&id='+prID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $("#pr_s_"+prID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#pr_i_"+prID).val('0');
                    } else {
                        $("#pr_i_"+prID).val('1');
                    }
                } else if (response == '404') {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();

            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".delprod", function() {
        var type = 'delete_product';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".del_stor", function() {
        var type = 'delete_storie_alert';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".stViewers", function() {
        var type = 'storieViewers';
        var ID = $(this).attr("data-viewer");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("keyup", ".avalv", function() {
        var inputVal = $(this).val();
        $(".i_t_warning").hide();

        if (inputVal === "0" || inputVal === "" || inputVal === "undefined") {
          $(".i_t_warning").show();
        }

        var calculatedValue = parseFloat(inputVal) * parseFloat(pointEqualValue);
        if (!isNaN(calculatedValue)) {
          $(".pricecal").text(calculatedValue.toFixed(2));
        }
      });

    $(document).on("click", ".c_UpdateCostV", function() {
        var videoCost = $(".avalv").val();
        var data = "f=vCost&vCostFee=" + encodeURIComponent(videoCost);

        $.ajax({
          type: "POST",
          url: siteurl + "requests/request.php",
          data: data,
          beforeSend: function() {
            $(".i_t_warning, .successNot").hide();
          },
          success: function(response) {
            if (response === "not") {
              $(".i_t_warning").show();
            } else {
              $(".successNot").show();
            }
          }
        });
    });
    $(document).on("click", ".payClose", function() {
        $(".i_payment_pop_box").addClass("i_modal_in_in_out");
        setTimeout(() => {
            $(".i_subs_modal").remove();
        }, 200);
    });
	
	

    // END of your previous code.

// PASTE THE NEW NOTIFICATION CODE HERE
// ========================================================================
// --- FINAL NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================
/* ===== NOTIFICATIONS BLOCK (drop-in, no IIFE/use strict) ===== */

/* ===== NOTIFICATIONS BLOCK (drop-in, no IIFE/use strict) ===== */
// ========================================================================
// --- FINAL NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================

// This function will poll the server for new notification and message counts.
// ========================================================================
// --- RELIABLE & SIMPLE NOTIFICATION SCRIPT ---
// ========================================================================
// ========================================================================
// --- FINAL NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================

// ========================================================================
// --- WORKING NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================

// ========================================================================
// --- WORKING NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================

// Global variables to track previous counts and prevent unwanted sounds
window.previousNotificationCount = 0;
window.previousMessageCount = 0;
window.isFirstLoad = true;

// Your existing notification sound function (keep this)
function playNotificationSound() {
    var audio = document.getElementById('notification-sound-not');
    if (audio) {
        var playPromise = audio.play();
        if (playPromise !== undefined) {
            playPromise.then(_ => {}).catch(error => {});
        }
    }
}

function sanitizeBadgesOnLoad($) {
    [
        {wrap: '.not_not', badge: '.isum.sum_not'},
        {wrap: '.msg_not', badge: '.isum.sum_m'}
    ].forEach(function(sel) {
        var $wrap = $(sel.wrap);
        var $badge = $wrap.find(sel.badge);
        if (!$badge.length) return;

        var raw = $badge.text();
        var txt = $.trim(String(raw));
        var n = parseInt(txt, 10);

        // hide if it's "0", empty, NaN, or <= 0
        if (!txt || isNaN(n) || n <= 0) {
            $badge.text('');
            $wrap.hide();
        }
    });
}

function attachZeroKiller($) {
    [
        {wrap: '.not_not', badge: '.isum.sum_not'},
        {wrap: '.msg_not', badge: '.isum.sum_m'}
    ].forEach(function(sel) {
        var $wrap = $(sel.wrap);
        var $badge = $wrap.find(sel.badge);
        if (!$badge.length) return;

        // Observe text changes; if "0", wipe & hide immediately
        var target = $badge.get(0);
        var obs = new MutationObserver(function() {
            var t = $.trim(String($badge.text()));
            var v = parseInt(t, 10);
            if (!t || isNaN(v) || v <= 0) {
                $badge.text('');
                $wrap.hide();
            }
        });
        obs.observe(target, { characterData: true, subtree: true, childList: true });
    });
}

// Modified version of your existing getm function with smart sound logic
var g = '';
getm(g);

function getm(g) {
    var type = 'get';
    if ($.trim(type).length === 0) {
        setTimeout(getm, 10000);
    } else {
        $.ajax({
            type: 'GET',
            url: siteurl + 'requests/get.php?f=1',
            dataType: "json",
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                var messageNotificationStatus = response.messageNotificationStatus;
                var notificationStatus = response.notificationStatus;
                var unReadedNotfications = parseInt(response.unReadedNotfications, 10) || 0;
                var unReadMessageNotifications = parseInt(response.unReadMessageNotifications, 10) || 0;
                var videoCallFound = response.videoCallFound;
                var acceptStatus = response.acceptStatus;

                // --- Handle Messages ---
                if (messageNotificationStatus == '1' && unReadMessageNotifications > 0) {
                    $(".msg_not").show();
                    $(".sum_m").html(unReadMessageNotifications);
                    
                    // Play sound if: not first load AND (count increased OR data-id changed)
                    var shouldPlayMessageSound = false;
                    var currentMessageDataId = $(".sum_m").attr("data-id");
                    
                    if (!window.isFirstLoad) {
                        // Sound if count went up OR if the data-id is different (new notification batch)
                        if (unReadMessageNotifications > window.previousMessageCount || 
                            currentMessageDataId != messageNotificationStatus) {
                            shouldPlayMessageSound = true;
                        }
                    }
                    
                    if (shouldPlayMessageSound) {
                        playNotificationSound();
                    }
                    
                    $(".sum_m").attr("data-id", messageNotificationStatus);
                } else {
                    // Hide message badge if no unread messages
                    $(".msg_not").hide();
                    $(".sum_m").html('');
                }

                // --- Handle Notifications ---
                if (notificationStatus == '1' && unReadedNotfications > 0) {
                    $(".not_not").show();
                    $(".sum_not").html(unReadedNotfications);
                    
                    // Play sound if: not first load AND (count increased OR data-id changed)
                    var shouldPlayNotificationSound = false;
                    var currentNotificationDataId = $(".sum_not").attr("data-id");
                    
                    if (!window.isFirstLoad) {
                        // Sound if count went up OR if the data-id is different (new notification batch)  
                        if (unReadedNotfications > window.previousNotificationCount || 
                            currentNotificationDataId != notificationStatus) {
                            shouldPlayNotificationSound = true;
                        }
                    }
                    
                    if (shouldPlayNotificationSound) {
                        document.getElementById('notification-sound-not').play();
                    }
                    
                    $(".sum_not").attr("data-id", notificationStatus);
                } else {
                    // Hide notification badge if no unread notifications
                    $(".not_not").hide();
                    $(".sum_not").html('');
                }

                // Update our tracking variables
                window.previousNotificationCount = unReadedNotfications;
                window.previousMessageCount = unReadMessageNotifications;
                window.isFirstLoad = false;

                // --- Handle Video Calls ---
                if (videoCallFound) {
                    if (!$("div").hasClass("videoCall")) {
                        VideoCallAlert(videoCallFound);
                    }
                }
                if (acceptStatus == '3') {
                    $(".caller_det").hide();
                    $(".call_declined").show();
                    $("#notification-sound-call")[0].pause();
                }

                if (!g) {
                    setTimeout(getm, 10000);
                }
            }
        });
    }
}

// Always sanitize and start observer on DOM ready
jQuery(function($) {
    sanitizeBadgesOnLoad($);
    attachZeroKiller($);
});

// Handle clicking on a single notification
$(document).on('click', '.mf-noti-link', function(e) {
    e.preventDefault();
    const link = $(this);
    const destinationUrl = link.attr('href');
    const notifId = link.data('id');
    const item = link.closest('.i_message_wrpper, .i_notification_wrpper');

    if (item.hasClass('is-unread')) {
        item.removeClass('is-unread').addClass('is-read');
    }

    $.ajax({
        type: 'POST',
        url: window.siteurl + 'requests/request.php',
        data: { f: 'markNotificationRead', id: notifId },
        complete: function() { window.location.href = destinationUrl; }
    });
});

// Handle clicking the "Mark all as read" button
$(document).on('click', '#mfNotiMarkAll', function(e) {
    e.preventDefault();
    const button = $(this);
    button.prop('disabled', true);

    // Visually update all items
    $('.i_message_wrpper.is-unread, .i_notification_wrpper.is-unread').removeClass('is-unread').addClass('is-read');

    // Forcefully hide the bell icon badge
    $('.not_not').hide();
    $('.not_not .isum.sum_not').text('');

    // Reset our tracking to prevent sound on next poll
    window.previousNotificationCount = 0;

    $.ajax({
        type: 'POST',
        url: window.siteurl + 'requests/request.php',
        data: { f: 'markAllNotificationsRead' },
        complete: function() { button.prop('disabled', false); }
    });
});

// Handle "Load More" on the notifications page
$(document).on('click', '#loadMoreNotifications', function(e) {
    e.preventDefault();
    const button = $(this);
    const container = $('#notifications-container');
    const lastId = container.find('.i_notification_wrpper:last').data('last');

    if (!lastId) return;

    const originalButtonText = button.text();
    button.text('Loading...').prop('disabled', true);

    $.ajax({
        type: "POST",
        url: window.siteurl + 'requests/request.php',
        data: { f: 'loadMoreNotifications', last_id: lastId },
        dataType: 'json',
        success: function(response) {
            if (response && response.html) {
                container.append(response.html);
            }
            if (!response || !response.has_more) {
                $('#load-more-wrapper').removeClass('badge--show');
            }
        },
        error: function() { alert('Could not load more notifications.'); },
        complete: function() {
            button.text(originalButtonText).prop('disabled', false);
        }
    });
});

// Your other existing event handlers (keep these as they are)
$(document).on("click", ".loginForm", function() {
    $('.i_modal_bg').addClass('i_modal_display');
});
$(document).on("click", ".i_modal_close", function() {
    $('.i_modal_bg').removeClass('i_modal_display');
    $(".i_modal_in").attr("style", "");
    $(".i_modal_forgot").hide();
});
$(document).on("click", ".password-reset", function() {
    $(".i_modal_in").hide();
    $(".i_modal_forgot").show();
});
$(document).on("click", ".already-member", function() {
    $(".i_modal_in").show();
    $(".i_modal_forgot").hide();
});

$(".i_comment_form_textarea").focusin(function() {
    var words = $(this).val();
    var ID = $(this).attr("data-id");
});
$(document).on("click", ".openPostMenu", function() {
    var ID = $(this).attr("id");
    $(".mnoBox" + ID).addClass("dblock");
});
$(document).on("click", ".openShareMenu", function() {
    var ID = $(this).attr("id");
    $(".mnsBox" + ID).addClass("dblock");
});
$(document).on("click", ".openComMenu", function() {
    var ID = $(this).attr("id");
    $(".comMBox" + ID).addClass("dblock");
});
$(document).on("click", ".msg_Set", function() {
    var ID = $(this).attr("id");
    if ($(".msg_Set")[0]) {
        $(".msg_Set").removeClass("dblock");
    }
    $(".msg_Set_" + ID).addClass("dblock");
});
$(document).on("click", ".smscd", function() {
    var ID = $(this).attr("id");
    if ($(".smscd")[0]) {
        $(".me_msg_plus").removeClass("dblock");
    }
    $(".msg_set_plus_" + ID).addClass("dblock");
});
$(document).on("click", ".whs", function() {
    $(".i_choose_ws_wrapper").addClass("dblock");
});
$(document).on("click", ".in_comment", function() {
    var ID = $(this).attr("id");
    $("#comment" + ID).focus();
});
	
	

	
	/* ======================================================================== */


/* ======================================================================== */
/* == FINAL SCRIPT for Comments & Likes (Corrected) == */
/* ======================================================================== */

// Your other existing event handlers can go above this line...
// =============================================================

/* ======================================================================== */
/* == FINAL POPUP SCRIPT (DESKTOP MODAL + MOBILE SLIDE-UP PANEL) == */
// Your other existing event handlers can go above this line...
// =============================================================

/* ======================================================================== */
/* == FINAL POPUP SCRIPT (Desktop Popup + Mobile Redirect) == */
/* ======================================================================== */
/* == FINAL UNIVERSAL SLIDE-UP PANEL SCRIPT == */
/* ======================================================================== */
// Your other existing event handlers can go above this line...
// =============================================================

/* ======================================================================== */
/* == FINAL SCRIPT: Working Desktop Popup + Reliable Mobile Redirect == */
/* ======================================================================== */

/* ======================================================================== */
/* == FINAL SCRIPT: Working Desktop Popup + Reliable Mobile Redirect == */
/* ======================================================================== */

// Your other existing event handlers can go above this line...
// =============================================================

/* ======================================================================== */
/* == FINAL POPUP SCRIPT (Desktop Popup + Mobile Redirect) == */
/* ======================================================================== */

/* ======================================================================== */
/* == FINAL UNIVERSAL SLIDE-UP PANEL SCRIPT == */
/* ======================================================================== */

/* ======================================================================== */
/* == FINAL UNIVERSAL SLIDE-UP PANEL (Corrected and Unified) == */
/* ======================================================================== */

// Scroll Lock Helpers
function lockBodyScroll() {
    if ($('body').hasClass('panel-open')) return;
    const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
    $('body').attr('data-scroll-y', scrollY.toString()).css('top', `-${scrollY}px`);
    $('body').addClass('panel-open');
}
function unlockBodyScroll() {
    if (!$('body').hasClass('panel-open')) return;
    const scrollY = parseInt($('body').attr('data-scroll-y') || '0', 10);
    $('body').removeClass('panel-open').css('top', '').removeAttr('data-scroll-y');
    window.scrollTo(0, scrollY);
}

// Universal Panel Opener
function openSlidingPanel(ajaxData) {
    $('.universal-panel-container').remove(); // Clear any old panels
    var panelHTML = `
        <div class="universal-panel-container">
            <div class="universal-panel-overlay"></div>
            <div class="universal-panel-content">
                <div class="panel-header">
                    <div class="panel-close-btn">
                        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path></svg>
                    </div>
                    <div class="panel-title-text"></div>
                </div>
                <div class="panel-body">
                    <div class="panel-loader"><div class="dot-pulse"></div></div>
                </div>
            </div>
        </div>
    `;
    $('body').append(panelHTML);
    lockBodyScroll();
    setTimeout(function() {
        $('.universal-panel-container').addClass('is-visible');
    }, 50);

    // Fetch the actual content
    $.ajax({
        type: 'POST',
        url: siteurl + 'requests/request.php', // Correctly uses siteurl
        data: ajaxData,
        success: function(response) {
            var panel = $('.universal-panel-container');
            var panelBody = panel.find('.panel-body');
            var panelTitle = panel.find('.panel-title-text');
            
            panelBody.html(response);
            
            if (ajaxData.f === 'getPostForModal') {
                panelTitle.text('Post'); // Set title
                var commentsWrapper = panelBody.find('.i_post_comments_wrapper');
                if (commentsWrapper.length) {
                    setTimeout(function() {
                       commentsWrapper.scrollTop(commentsWrapper[0].scrollHeight);
                    }, 100);
                }
            } else if (ajaxData.f === 'getPostLikers'){
                panelTitle.text('Liked by'); // Set title
            }
        }
    });
}

// Event Triggers for Popups
$(document).on('click', '.open-post-modal, .show-likers', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var postID = $(this).data('id');
    if (!postID) return;
    var action = $(this).hasClass('show-likers') ? 'getPostLikers' : 'getPostForModal';
    openSlidingPanel({ f: action, post_id: postID });
});

// Universal Panel Closer
$(document).on('click', '.universal-panel-overlay, .panel-close-btn', function(e) {
    e.preventDefault();
    var panel = $('.universal-panel-container');
    panel.removeClass('is-visible');
    unlockBodyScroll();
    setTimeout(function() { panel.remove(); }, 300);
});

	
	
	
// This script adds a class to the body on single post pages for the sticky comment bar
$(document).ready(function(){
    if(window.location.href.indexOf("/post/") > -1) {
        $('body').addClass('single-post-page');
    }
});

})(jQuery);
    var ID = emojiButton.attr("data-type");
    var dataID = emojiButton.attr("data-id");
    var data = 'f=' + type + '&id=' + ID + '&ec=' + dataID;

    $.ajax({
        type: 'POST',
        url: siteurl + 'requests/request.php',
        data: data,
        cache: false,
        success: function(response) {
            // IMPORTANT: We append to the BODY so it can appear over everything.
            $("body").append(response);

            // Calculate the correct position relative to the button
            var position = emojiButton.offset();
            var pickerHeight = $('.emojiBoxC').outerHeight();

            // Position the new box above the button
            $('.emojiBoxC').css({
                'z-index': '100000', // Make sure it's on top
                'top': position.top - pickerHeight - 10 + 'px', // 10px spacing
                'left': position.left - 100 + 'px' // Adjust this number to align it better
            }).show();
        }
    });
});
    $(document).on("click", ".topMessages", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_message_notifications_container")[0]) {
                    $("#" + type).append(response);
                    $(".msg_not").hide();
                    $(".sum_m").attr("data-id", 0);
                    GetSlimScroll();
                } else {
                    $(".i_general_box_message_notifications_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_container , .i_general_box_notifications_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".topPoints", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_container")[0]) {
                    $("#" + type).append(response);
                } else {
                    $(".i_general_box_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_notifications_container , .i_general_box_message_notifications_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".topNotifications", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_notifications_container")[0]) {
                    $("#" + type).append(response);
                    if ($(".i_notifications_count")[0]) {
                        $(".not_not").hide();
                    }
                    GetSlimScroll();
                } else {
                    $(".i_general_box_notifications_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_container , .i_general_box_message_notifications_container").remove();
                }
            }
        });
    });
    /*Get Stickers*/
    $(document).on("click", ".getStickers", function() {
		$('.stickersContainer, .emojiBoxC').remove();
        var type = 'stickers';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".stickersContainer")[0]) {
                    $(".getStickers" + ID).append(response);
                } else {
                    $(".stickersContainer").remove();
                }
                if ($("div").hasClass("emojiBox") || $("div").hasClass("emojiBoxC") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".emojiBoxC , .emojiBox , .i_general_box_message_notifications_container , .i_general_box_notifications_container , .i_general_box_container").remove();
                }
            }
        });
    });
    /*Get Stickers*/
    $(document).on("click", ".getGifs", function() {
        var type = 'gifList';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".stickersContainer")[0]) {
                    $(".getStickers" + ID).append(response);
                } else {
                    $(".stickersContainer").remove();
                }
                if ($("div").hasClass("emojiBox") || $("div").hasClass("emojiBoxC") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".emojiBoxC , .emojiBox , .i_general_box_message_notifications_container , .i_general_box_notifications_container , .i_general_box_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".g_feed", function() {
        var get = $(this).attr("data-get");
        var type = $(this).attr("data-type");
        var data = 'f=' + get;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $("#moreType").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '404') {
                    $("#moreType").attr("data-type", type);
                    $("#moreType").html('').append(response);
                    $(".mobile_left").removeClass("leftStickyActive");
                    $(".is_mobile").removeClass("svg_active_icon");
                    $(".i_postFormContainer").hide();
                        initGalleriesInDOM();
                        reInitPostPlugins($(document));
                        initImageBackgrounds();
                        initStandaloneSwiperLightGallery();
                        initSuggestedCreatorsSwiper();
                        initImageSuggestedBackgrounds();
                }
            }
        });
    });
    const initializedGalleries = new Set();

    function initGalleriesInDOM(scope = $(document)) {
        scope.find(".gallery_trigger").each(function () {
          const galleryID = $(this).data("gallery-id");
          if (galleryID && !initializedGalleries.has(galleryID)) {
            const $gallery = $("#" + galleryID);
            if ($gallery.length > 0) {
              $gallery.lightGallery({
                videojs: true,
                mode: 'lg-fade',
                cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
                download: false,
                share: false
              });
              initializedGalleries.add(galleryID);
            }
          }
        });
    }

    function reInitPostPlugins(scope) {
        if (!scope) return;

        scope.find('[id^="lightgallery"]').each(function () {
          const $this = $(this);
          if (!$this.hasClass('lg-initialized')) {
            $this.lightGallery({
              videojs: true,
              mode: 'lg-fade',
              cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
              download: false,
              share: false
            });
        }
    });

        scope.find('[id^="play_po_"]').each(function () {
          const $this = $(this);
          if (!$this.hasClass('green-audio-player-loaded')) {
            new GreenAudioPlayer($this[0], {
              stopOthersOnPlay: true,
              showTooltips: true,
              showDownloadButton: false,
              enableKeystrokes: true
            });
            $this.addClass('green-audio-player-loaded');
          }
        });
    }

    window.initImageBackgrounds = function (targetSelector = '.i_post_image_swip_wrapper', scope = $(document)) {
      scope.find(targetSelector).each(function () {
        const bg = $(this).attr('data-bg');
        if (bg) {
          $(this).css('background-image', 'url(' + bg + ')');
        }
      });
    };

    window.initImageSuggestedBackgrounds = function (targetSelector = '.i_sub_u_cov', scope = $(document)) {
      scope.find(targetSelector).each(function () {
        const bg = $(this).attr('data-bg');
        if (bg) {
          $(this).css('background-image', 'url(' + bg + ')');
        }
      });
    };


 const initializedStandaloneSwiper = new Set();

window.initStandaloneSwiperLightGallery = function (scope = $(document)) {
  scope.find('.swiper-wrapper[data-standalone-gallery="true"]').each(function () {
    const $wrapper = $(this);
    const galleryID = $wrapper.attr('id');

    if (!galleryID || initializedStandaloneSwiper.has(galleryID)) {
      return;
    }

    $wrapper.lightGallery({
      selector: '.swiper-slide a',
      videojs: true,
      mode: 'lg-fade',
      cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
      download: false,
      share: false
    });

    initializedStandaloneSwiper.add(galleryID);
    $wrapper.addClass('lg-initialized');
  });

  scope.find('.product_images_container .mySwiper').each(function () {
    new Swiper(this, {
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
      },
      pagination: {
        el: ".swiper-pagination",
        dynamicBullets: true,
      }
    });
  });
};

$(document).on('click', '.swiper-slide a', function (e) {
  const wrapper = $(this).closest('.swiper-wrapper');
  if (wrapper.hasClass('lg-initialized')) {
    e.preventDefault();
  }
});

    const initializedSuggestedSwipers = new Set();

  /**
   * Initialize Swiper in dynamically loaded suggested creators sections.
   * @param {jQuery|HTMLElement} scope - Optional. DOM scope to search for .mySwiper elements.
   */
  window.initSuggestedCreatorsSwiper = function (scope = $(document)) {
    scope.find('.i_postFormContainer_swiper .mySwiper').each(function () {
      const swiperElement = this;

      // Use a unique identifier to prevent double initialization
      const swiperID = $(swiperElement).data('swiper-id') || swiperElement.id || Math.random().toString(36).substr(2, 9);

      if (!initializedSuggestedSwipers.has(swiperID)) {
        new Swiper(swiperElement, {
          effect: "cards",
          grabCursor: true
        });
        initializedSuggestedSwipers.add(swiperID);
      }
    });
  };

$(document).ready(function () {
  initGalleriesInDOM();
  reInitPostPlugins($(document));
  initImageBackgrounds();
  initStandaloneSwiperLightGallery();
  initSuggestedCreatorsSwiper();
  initImageSuggestedBackgrounds();
});

    window.reInitLightGallery = function (html) {
        initGalleriesInDOM(html);
    };

  /********* SCROLL TO LOAD MORE ***********/
  let scrollLoad = true;
  $(document).on('touchmove', showMoreData); /* For mobile */
  $(window).on('scroll', showMoreData);

  function showMoreData() {
    if (
      scrollLoad &&
      $(window).scrollTop() >= $(document).height() - $(window).height() - 500
    ) {
      const moreType = $("#moreType").attr("data-type");
      const moreCat = $("#moreType").attr("data-po");
      let profileUserID = '';
      let ID;

      if (moreType === 'notifications' || moreType === 'paid' || moreType === 'free' || moreType === 'creators') {
        ID = $('#moreType').children('.mor').last().attr('data-last');
        if (moreType === 'creators') {
          profileUserID = $("#moreType").attr("data-r");
        }
      }

      if (
        moreType === 'moreposts' || moreType === 'savedpost' || moreType === 'moreexplore' ||
        moreType === 'morepremium' || moreType === 'friends' || moreType === 'morepurchased' ||
        moreType === 'moreboostedposts' || moreType === 'moretrendposts' || moreType === 'hashtag'
      ) {
        ID = $('#moreType').children('.i_post_body').last().attr('data-last');
        if (moreType === 'hashtag') {
          profileUserID = $("#moreType").attr("data-hash");
        }
      }

      if (moreType === 'profile') {
        ID = $('#moreType').children('.i_post_body').last().attr('data-last');
        if (!ID) {
          ID = $('#moreType').children('.i_sub_box_container').last().attr('data-last');
        }
        profileUserID = $("#prw").attr("data-u");
      }

      if (
        $('.i_loading , .nomore , .noPost , .no_creator_f_wrap').length === 0 &&
        !$(".i_loading , .nomore , .noPost , .no_creator_f_wrap")[0] &&
        moreType !== undefined
      ) {
        let data = `f=${moreType}&last=${ID}&p=${profileUserID}`;
        if (moreCat) {
          data += `&pcat=${moreCat}`;
        }

        $.ajax({
          type: "POST",
          url: siteurl + 'requests/request.php',
          data: data,
          cache: false,
          beforeSend: function () {
            $(".body_" + ID).after(preLoadingAnimation);
            scrollLoad = false;
          },
          success: function (response) {
            $(".i_loading").remove();
            if (response && !$(".nomore")[0]) {
                const $newContent = $(response);
                $("#moreType").append($newContent);

                reInitLightGallery($newContent);
                reInitPostPlugins($newContent);
                initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                initSuggestedCreatorsSwiper($newContent);
                initImageSuggestedBackgrounds();
              scrollLoad = true;
            }
          }
        });
      }
    }
  }

    /*Update Who Can See POST Before Share Post*/
    $(document).on("click", ".wsUpdate", function() {
        var type = 'whoSee';
        var ID = $(this).attr("data-id");
        var data = 'f=' + type + '&who=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_whoseech_menu_item_out").removeClass("wselected");
                if (response) {
                    $("#wsUpdate" + ID).addClass("wselected");
                    $(".wBox").html('').append(response);
                    $(".i_choose_ws_wrapper").removeClass('dblock');
                }
                if (ID == '4' && $("div[class='point_input_wrapper']").length === 0) {
                    whoSeePremium();
                } else {
                    $(".point_input_wrapper").remove();
                }
            }
        });
    });

    function whoSeePremium() {
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: 'f=pw_premium',
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $(".aft").after(response);;
                }
            }
        });
    }
    /*Get PopUp for Post Updating WhoCanSee*/
    $(document).on("click", ".wcs", function() {
        var type = 'wcs';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Update WhoCanSee Status for Shared Post*/
    $(document).on("click", ".who_can_see_pop_item", function() {
        var type = 'uwcs';
        var ID = $(this).attr("id");
        var wcs = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&wci=' + wcs;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("#ipublic_" + ID).html('').append(response);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
            }
        });
    });
    /*Call Edit Post PoUpbox*/
    $(document).on("click", ".edtp", function() {
        var type = 'c_editPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delp", function() {
        var type = 'ddelPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Save Post Edit*/
    $(document).on("click", ".sedt", function() {
        var type = 'editS';
        var ID = $(this).attr('id');
        var editText = $("#ed_" + ID).val();
        var data = 'f=' + type + '&id=' + ID + '&text=' + encodeURIComponent(editText);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var editedText = response.text;
                if (responseStatus == 'no') {
                    PopUPAlerts('eCouldNotEmpty', 'ialert');
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (responseStatus == '200') {
                    $("#i_post_container_" + ID).show();
                    $("#i_post_text_" + ID).html(editedText);
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                }
            }
        });
    });
    /*Uploading Music, Video and Image*/
    $(document).on("change", "#i_image_video", function (e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $("#i_image_video").attr("data-id");
        var data = { f: id };

        $('.i_uploaded_iv').append('<div class="i_upload_progress"></div>');

        $("#uploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function () {
                $(".i_warning_unsupported").hide();
                $(".i_uploaded_iv").show();
                $(".i_upload_progress").width('0%');
                $(".publish").prop("disabled", true);
                $(".publish").css("pointer-events", "none");
            },
            uploadProgress: function (e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function (response) {
                if (response != '303') {
                    $(".i_uploaded_file_box").append(response);
                    var K = $('.i_uploaded_item').map(function () { return this.id }).toArray();
                    var T = K + "," + values;
                    if (T != "undefined,") {
                        $("#uploadVal").val(T);
                    }

                } else {
                    $(".i_uploaded_iv , .i_uploading_not").hide();
                    $(".i_warning_unsupported").show();
                }
                $(".i_upload_progress").width('0%');
                $(".i_uploading_not").hide();
                setTimeout(() => {
                    $('.publish').prop('disabled', false);
                    $(".publish").css("pointer-events", "auto");
                }, 3000);
            },
            error: function () { }
        }).submit();
    });

    /*Delete Uploaded File Before Publish*/
    $(document).on("click", ".i_delete_item_button", function() {
        var type = 'delete_file';
        var ID = $(this).attr('id');
        var input = $('#uploadVal');
        var data = 'f=' + type + '&file=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $(".iu_f_" + ID).remove();
                    input.val(function(_, value) {
                        return value.split(',').filter(function(val) {
                            return val !== ID;
                        }).join(',');
                    });
                } else {
                    PopUPAlerts('not_file', 'ialert')
                }
                if (!$(".i_uploaded_item")[0]) {
                    $(".i_uploaded_iv").hide();
                }
            }
        });
    });
    /*Save New Post*/
    $(document).on("click", ".publish", function() {
        var text = $("#newPostT").val();
        var files = $("#uploadVal").val();
        var point = $("#point").val();
        var type = 'newPost';
        var data = 'f=' + type + '&txt=' + encodeURIComponent(text) + '&file=' + files + '&point=' + point;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $('.publish').prop('disabled', true);
                $(".publish").css("pointer-events", "none");
                $(".i_warning_point , .i_warning , .i_warning_prmfl").fadeOut(100);
            },
            success: function(response) {
                $('.publish').prop('disabled', false);
                $(".publish").css("pointer-events", "auto");
                if ($("div").hasClass("noPost")) {
                    $(".noPost").remove();
                }
                if (response == '200') {
                    $(".i_warning").fadeIn();
                } else if (response == '201') {
                    $(".i_warning_point").fadeIn();
                } else if (response == '203') {
                    $(".i_warning_point_two").fadeIn();
                } else if (response == '202') {
                    $(".i_warning_prmfl").fadeIn();
                } else if (response == '204') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    $(".i_uploaded_file_box").html('');
                    $(".i_uploaded_iv").hide();
                    const $newContent = $(response);
                    $("#moreType").prepend($newContent);

                    reInitLightGallery($newContent);
                    reInitPostPlugins($newContent);
                    initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                    $(".newPostT").val('').trigger('change');
                    $("#uploadVal").val('');
                    $("#point").val('');
                }
            }
        });
    });
    /*Like Post*/
    $(document).on("click", ".in_like , .in_unlike", function() {
        var type = 'p_like';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&post=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {
                $('.in_like , .in_unlike').prop('disabled', true);
            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.like;
                var liksCount = response.likeCount;
                if (status == 'in_unlike') {
                    $("#p_l_" + ID).removeClass("in_like").addClass("in_unlike");
                    $("#lp_sum_" + ID).html(liksCount);

                    var $postID = $(".body_" + ID);
                    var $existingLikeHeart = $postID.find('.like_heart');

                    if ($existingLikeHeart.length > 0) {
                        $existingLikeHeart.fadeOut(300, function() {
                            $(this).remove();
                        });
                        clearTimeout($postID.data('likeTimer'));
                    } else {
                        $postID.append(likeBox);
                        var likeTimer = setTimeout(() => {
                            $postID.find(".like_heart").fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 450);
                        $postID.data('likeTimer', likeTimer);
                    }

                } else {
                    $("#p_l_" + ID).removeClass("in_unlike").addClass("in_like");
                    $("#lp_sum_" + ID).html(liksCount);

                    var $postID = $(".body_" + ID);
                    var $existingLikeHeart = $postID.find('.like_heart');

                    if ($existingLikeHeart.length > 0) {
                        $existingLikeHeart.fadeOut(300, function() {
                            $(this).remove();
                        });
                        clearTimeout($postID.data('likeTimer'));
                    } else {
                        $postID.append(UnlikeBox);
                        var likeTimer = setTimeout(() => {
                            $postID.find(".like_heart").fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 450);
                        $postID.data('likeTimer', likeTimer);
                    }

                }
                $("#p_l_" + ID).html(statusIcon);
                $('.in_like , .in_unlike').prop('disabled', false);
            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".in_share", function() {
        var ID = $(this).attr("data-id");
        var type = 'p_share';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&sp=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.in_share').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);

                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                            const $newContent = $(".i_modal_bg_in").last();

                            initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                            reInitPostPlugins($newContent);
                            initGalleriesInDOM($newContent);
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.in_share').prop('disabled', false);
                }
            });
        }
    });

    function PopUPAlerts(ialert, type) {
        var data = 'f=' + type + '&al=' + ialert;
        if (!$(".i_bottom_left_alert_container")[0]) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {

                },
                success: function(response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_bottom_left_alert_container").addClass('fadeOutDown');
                    }, 5000);
                    setTimeout(() => {
                        $(".i_bottom_left_alert_container").remove();
                    }, 5000);
                }
            });
        }
    }
    /*Save Re-Share Post*/
    $(document).on("click", ".re-share", function() {
        var ID = $(this).attr("id");
        var type = 'p_rshare';
        var postText = $(".more_textarea").val();
        var data = 'f=' + type + '&sp=' + ID + '&pt=' + encodeURIComponent(postText);
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                } else {
                    PopUPAlerts('not_Shared', 'ialert');
                }
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del", function() {
        var type = 'deletePost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".shareClose , .no-del , .popClose , .svAC", function() {
        $(".i_modal_in_in").addClass("i_modal_in_in_out");
        setTimeout(() => {
            $(".i_modal_bg_in").remove();
        }, 200);
    });
    /*Update Comment Status*/
    $(document).on("click", ".pcl", function() {
        var type = 'updateComentStatus';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        PopUPAlerts('commentClosed', 'ialert');
                    } else {
                        PopUPAlerts('commentOpened', 'ialert');
                    }
                    $("#dc_" + ID).html(statusIcon);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Pin Post*/
    $(document).on("click", ".i_pnp", function() {
        var type = 'pinpost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                var btns = response.btn;
                if (status) {
                    if (status == '200') {
                        PopUPAlerts('pined', 'ialert');
                        $(".body_" + ID).append(statusIcon);
                    } else {
                        PopUPAlerts('pinClosed', 'ialert');
                        $("#i_pined_post_" + ID).remove();
                    }
                    $(".pbtn_" + ID).html(btns);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Report Post*/
    $(document).on("click", ".rpp", function() {
        var type = 'reportPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".rpp" + ID).html(statusIcon);
                    } else if (status == '404') {
                        $(".rpp" + ID).html(statusIcon);
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Report Post*/
    $(document).on("click", ".svp", function() {
        var type = 'savePost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".in_save_" + ID).html(statusIcon);
                        PopUPAlerts('sAdded', 'ialert');
                    } else if (status == '404') {
                        $(".in_save_" + ID).html(statusIcon);
                        PopUPAlerts('sRemoved', 'ialert');
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
  /*Click To Send Comment -- CORRECTED*/
$(document).on("click", ".sndcom", function() {
    var sendButton = $(this);
    // The context is the form that contains the button we just clicked.
    var form_context = sendButton.closest('.i_comment_form');
    var postID = form_context.find('.nwComment').attr('data-id');

    var value = form_context.find(".nwComment").val();
    var stickerID = form_context.find("#stic_" + postID).val();
    var gif = form_context.find("#cgif_" + postID).val();
    
    Comment(postID, value, 'comment', stickerID, gif, form_context);
});

/*Press Enter To Send Comment -- CORRECTED*/
$(document).on('keydown', ".nwComment", function(e) {
    var key = e.which || e.keyCode || 0;
    if (key == 13 && !e.shiftKey) { // Added !e.shiftKey to allow new lines
        e.preventDefault();
        var textarea = $(this);
        // The context is the form that contains the textarea.
        var form_context = textarea.closest('.i_comment_form');
        var postID = textarea.attr('data-id');

        var value = textarea.val();
        var stickerID = form_context.find("#stic_" + postID).val();
        var gif = form_context.find("#cgif_" + postID).val();

        Comment(postID, value, 'comment', stickerID, gif, form_context);
    }
});
	
	
	/* ======================================================================== */
/* == STEP 3: ADD The Missing Comment Liking Function == */
/* ======================================================================== */

/* Like a Comment -- This function handles liking individual comments */
$(document).on("click", ".c_in_like, .c_in_unlike", function() {
    var likeButton = $(this); // The button that was clicked
    var commentID = likeButton.attr('data-id');
    var postID = likeButton.attr("data-p");
    
    // The context is the specific comment body containing the button
    var comment_context = likeButton.closest('.i_u_comment_body');

    var data = 'f=pc_like&post=' + postID + '&com=' + commentID;
    $.ajax({
        type: 'POST',
        url: siteurl + 'requests/request.php',
        dataType: "json",
        data: data,
        cache: false,
        success: function(response) {
            var status = response.status;
            var statusIcon = response.like;
            var statusTotalLike = response.totalLike;
            
            // We now search for elements ONLY within the specific comment we liked
            var targetButton = comment_context.find(".c_in_l_" + commentID);
            var targetCount = comment_context.find("#t_c_" + commentID);

            if (status == 'c_in_unlike') {
                targetButton.removeClass("c_in_like").addClass("c_in_unlike");
            } else {
                targetButton.removeClass("c_in_unlike").addClass("c_in_like");
            }
            targetCount.html(statusTotalLike);
            targetButton.html(statusIcon);
        }
    });
});
	
	
    /*Send Gif Comment*/
    $(document).on("click", ".rGif", function() {
        var src = $(this).attr("src");
        var ID = $(this).attr("data-id");
        if ($("#comment" + ID).val() === '') {
            Comment(ID, '', 'comment', '', src);
        } else {
            $(".emptyGifArea" + ID).show();
            $(".srcGif" + ID).attr('src', src);
            $("#cgif_" + ID).val(src);
            $(".stickersContainer").remove();
        }
    });
    /*Add Sticker*/
    $(document).on("click", ".addSticker", function() {
        var type = 'addSticker';
        var ID = $(this).attr("id");
        var dataID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pi=' + dataID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".emptyStickerArea" + dataID).html('');
            },
            success: function(response) {
                var sticker_url = response.stickerUrl;
                var stickerID = response.st_id;
                if (sticker_url) {
                    $(".stickersContainer").remove();
                    if ($("#comment" + dataID).val() === '') {
                        Comment(dataID, '', 'comment', stickerID);
                    } else {
                        $(".emptyStickerArea" + dataID).append(sticker_url);
                        $("#stic_" + dataID).val(stickerID);
                    }
                }
            }
        });
    });

/* The Main Comment Sending Function -- FINAL VERSION */
function Comment(postID, value, type, sticker, gif, form_context) {
    var data = 'f=' + type + '&id=' + postID + '&val=' + encodeURIComponent(value) + '&sticker=' + sticker + '&gf=' + gif;
    $.ajax({
        type: 'POST',
        url: siteurl + 'requests/request.php',
        data: data,
        cache: false,
        success: function(response) {
            // Find which comment section to add to (the one in the panel, or the one on the page)
            var panelCommentSection = $('.universal-panel-container').find('#i_user_comments_' + postID);
            var targetCommentSection = panelCommentSection.length ? panelCommentSection : $('#i_user_comments_' + postID);

            if (response == '404') {
                PopUPAlerts('sWrong', 'ialert');
            } else {
                targetCommentSection.append(response);
            }

            // Clear the form fields only within the form that was submitted
            if (form_context) {
                form_context.find(".nwComment").val('');
                form_context.find(".emptyStickerArea" + postID).empty();
                form_context.find("#stic_" + postID).val('');
                form_context.find(".emptyGifArea" + postID).hide();
                form_context.find("#cgif_" + postID).val('');
            }
            
            // Auto-scroll to the new comment if we are in the popup
            if (panelCommentSection.length) {
                var commentsWrapper = panelCommentSection.closest('.i_post_comments_wrapper');
                commentsWrapper.scrollTop(commentsWrapper[0].scrollHeight);
            }
        }
    });
}
	
	
	
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delCm", function() {
        var type = 'ddelComment';
        var ID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pid=' + postID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Delete Comment*/
    $(document).on("click", ".dlCm", function() {
        var type = 'deletecomment';
        var ID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&cid=' + ID + '&pid=' + postID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                    if (response == '200') {
                        $(".dlCm" + ID).fadeOut();
                        PopUPAlerts('delete_comment_success', 'ialert');
                    } else {
                        PopUPAlerts('delete_comment_not_success', 'ialert');
                    }
                }
            }
        });
    });
    /*Report Comment*/
    $(document).on("click", ".ccp", function() {
        var type = 'reportComment';
        var commentID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + commentID + '&pid=' + postID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".ccp" + commentID).html(statusIcon);
                    } else if (status == '404') {
                        $(".ccp" + commentID).html(statusIcon);
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Call Edit Comment PoUpbox*/
    $(document).on("click", ".cced", function() {
        var type = 'c_editComment';
        var commentID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&cid=' + commentID + '&pid=' + postID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Save Comment Edit*/
    $(document).on("click", ".secdt", function() {
        var type = 'editSC';
        var commentID = $(this).attr('id');
        var postID = $(this).attr('data-id');
        var editText = $("#ed_" + commentID).val();
        var data = 'f=' + type + '&cid=' + commentID + '&pid=' + postID + '&text=' + encodeURIComponent(editText);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var editedText = response.text;
                if (responseStatus == 'no') {
                    PopUPAlerts('eCouldNotEmpty', 'ialert');
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (responseStatus == '200') {
                    $("#i_u_c_" + commentID).html(editedText);
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                }
            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".free_follow", function() {
        var type = 'follow_free_not';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });

    /*Follow Profile Free*/
    $(document).on("click", ".f_p_follow", function() {
        var type = 'freeFollow';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&follow=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".i_modal_content").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var responseStatus = response.status;
                var responseNot = response.text;
                var responseBtn = response.btn;
                if (responseStatus == '200') {
                    $(".i_fw" + ID).html(responseBtn);
                    if (responseNot == 'flw') {
                        $(".i_fw" + ID).removeClass("i_btn_like_item free_follow").addClass("i_btn_like_item_flw f_p_follow");
                        PopUPAlerts('youFollowing', 'ialert');
                    } else if (responseNot == 'unflw') {
                        $(".i_fw" + ID).removeClass("i_btn_like_item_flw f_p_follow").addClass("i_btn_like_item free_follow");
                        PopUPAlerts('youUnfollowing', 'ialert');
                    }
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Block Not PopUp Call*/
    $(document).on("click", ".ublknot", function() {
        var type = 'uBlockNotice';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Choose Block TYPE*/
    $(document).on("click", ".i_redtrict_u", function() {
        var ID = $(this).attr("data-s");
        $(".block_a_status").removeClass("blockboxActive").addClass("blockboxPassive");
        $("#bl_s_" + ID).addClass("blockboxActive");
        $(".ublk").attr('data-bt', ID);
    });
    /*Block User*/
    $(document).on("click", ".ublk", function() {
        var type = 'ublock';
        var ID = $(this).attr("id");
        var blockType = $(this).attr("data-bt");
        var data = 'f=' + type + '&id=' + ID + '&blckt=' + blockType;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    window.location.href = responseRedirect;
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                }

            }
        });
    });
    /*Block Not PopUp Call*/
    $(document).on("click", ".uSubsModal", function() {
        var type = 'subsModal';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

                $(".uSubsModal").prop("disabled", true);
                $(".uSubsModal").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                    $(".uSubsModal").prop("disabled", false);
                    $(".uSubsModal").css("pointer-events", "auto");
                }, 200);
            }
        });
    });
   
    /*Upload Verification Files*/
    $(document).on("change", "#id_card_two, #id_card", function(e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
        $("#vUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".f_" + type).html('');
                $("#uploadVal_" + type).val('');
                $("#" + type).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".f_" + type).append(response);
                    var K = $(".f_" + type + " > div:last").attr("id");
                    var T = K;
                    if (T != "undefined,") {
                        $("#uploadVal_" + type).val(T);
                    }
                    $("#id_card , #id_card_two").val('');
                }
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Send Verification Certificate Request*/
    $(document).on("click", ".v_Next", function() {
        var type = 'verificationRequest';
        var IDCard = $("#uploadVal_sec_one").val();
        var IDPhoto = $("#uploadVal_sec_two").val();
        var data = 'f=' + type + '&cID=' + IDCard + '&cP=' + IDPhoto;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".card , .both , .photo").hide();
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == 'card') {
                    $("#id_card , #id_card_two").val('');
                    $(".card").show();
                } else if (response == 'photo') {
                    $("#id_card , #id_card_two").val('');
                    $(".photo").show();
                } else if (response == 'both') {
                    $("#id_card , #id_card_two").val('');
                    $(".both").show();
                }
                $(".i_nex_btn").css("pointer-events", "auto");
            }
        });
    });
    /*Call Avatar And Cover PopUP*/
    $(document).on("click", ".editAvatarCover", function() {
        var type = 'updateAvatarCover';
        var data = 'f=' + type;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });


    $(document).on('submit', "#myEmailForm", function(e) {
        e.preventDefault();
        var myEmailForm = $(this);
        if ($("#cPass").val().length == 0) {
            $(".warning_required").show();
            return false;
        }
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: myEmailForm.serialize(),
            beforeSend: function() {
                $(".warning_inuse , .warning_invalid , .warning_wrong_password , .warning_required , .warning_same_email").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                myEmailForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    myEmailForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '404') {
                    $(".warning_inuse").show();
                } else if (data == 'no') {
                    $(".warning_invalid").show();
                } else if (data == 'same') {
                    $(".warning_same_email").show();
                } else if (data == '200') {
                    $(".successNot").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });

    $(document).on('submit', "#myProfileForm", function(e) {
        e.preventDefault();
        var myProfileForm = $(this);
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: myProfileForm.serialize(),
            beforeSend: function() {
                $(".invalid_username , .character_warning , .warning_username").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                myProfileForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    myProfileForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '1') {
                    $(".successNot").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".pyot_sNext", function() {
        var type = 'updatePayoutSet';
        var defaultMethod = $('input[name=default]:checked', '#bankForm').val();
        var paypalEmail = $("#paypale").val();
        var repaypalEmail = $("#paypalere").val();
        var bankAccount = $("#bank_transfer").val();
        if (defaultMethod == 'paypal') {
            if (paypalEmail.length == 0) {
                $("#setWarning").show();
                return false;
            } else {
                $("#setWarning").hide();
            }
            if (repaypalEmail.length == 0) {
                $("#setWarning").show();
                return false;
            } else {
                $("#setWarning").hide();
            }
            if (paypalEmail != repaypalEmail) {
                $("#notMatch").show();
                return false;
            } else {
                $("#notMatch").hide();
            }
        }
        if (defaultMethod == 'bank') {
            if (bankAccount.length == 0) {
                $("#setBankWarning").show();
                return false;
            } else {
                $("#setBankWarning").hide();
            }
        }
        var data = 'f=' + type + '&paypalEmail=' + encodeURIComponent(paypalEmail) + '&paypalReEmail=' + encodeURIComponent(repaypalEmail) + '&bank=' + bankAccount + '&method=' + defaultMethod;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '200') {
                    if (response == 'email_warning') {
                        $("#notMatch").show();
                    } else if (response == 'paypal_warning') {
                        $("#setWarning").show();
                    } else if (response == 'bank_warning') {
                        $("#setBankWarning").show();
                    } else if (response == 'not_valid_email') {
                        $("#notValidE").show();
                    }
                }
                setTimeout(() => {
                    $(".successNot").show();
                    $(".loaderWrapper").remove();
                    $(".i_nex_btn").css("pointer-events", "auto");
                }, 3000);
            }
        });
    });

   $(document).on("click", ".mwithdraw", function() {
    var button = $(this);
    var amount = $("#wamount").val();
    var data = 'f=makewithDraw&amount=' + amount;

    // Hide all warnings before making the request
    $(".i_t_warning").hide();
    $(".successNot").hide();

    $.ajax({
        type: "POST",
        url: siteurl + 'requests/request.php',
        data: data,
        cache: false,
        beforeSend: function() {
            button.css("pointer-events", "none");
            // Your loading animation variable
            if (typeof plreLoadingAnimationPlus !== 'undefined') {
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
            }
        },
        success: function(response) {
            // Remove the loader and re-enable the button
            $(".loaderWrapper").remove();
            button.css("pointer-events", "auto");

            // Handle the response from the server
            if (response == '1') {
                $(".successNot").show(); // Success
            } else if (response == '2') {
                $("#mwithdrawal").show(); // Minimum amount error
            } else if (response == '3') {
                $("#nbudget").show(); // Not enough budget
            } else if (response == '4') {
                $("#nnoway").show(); // Generic error
            } else if (response == '5') {
                $("#nwaitpending").show(); // Pending request exists
            } else if (response == '6') {
                $("#no_payout_method").show(); // New: Payout method not set
            } else {
                $("#nnoway").show(); // Fallback for any other error
            }
        },
        error: function() {
            // In case of a network error, also re-enable the button
             $(".loaderWrapper").remove();
             button.css("pointer-events", "auto");
             $("#nnoway").show();
        }
    });
});
    /*Credit Card Form Call*/
    $(document).on("click", ".prcsPost", function() {
        var type = 'pPurchase';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&purchase=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("click", ".prchase_go_wallet", function() {
        var type = 'goWallet';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&p=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                window.location.href = response;
            }
        });
    });
    $(document).on("click", ".buyPoint", function() {
        var type = 'choosePaymentMethod';
        var pointID = $(this).attr("id");
        var data = 'f=' + type + '&type=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $("#p_i_" + pointID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".loaderWrapper").remove();
                $(".credit_plan_box").css("pointer-events", "auto");
            }
        });
    });
    $(document).on("click", ".mcSt", function() {
        if ($(".cSetc")[0]) {
            $(".cSetc").removeClass("dblock");
        }
        $(".cSetc").addClass("dblock");
    });
    /*Get Gifs*/
    $(document).on("click", ".getmGifs", function() {
        var type = 'chat_gifs';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getmGifs").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getmGifs").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    /*Get Gifs*/
    $(document).on("click", ".getmStickers", function() {
        var type = 'chat_stickers';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getmGifs").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getmGifs").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    $(document).on("click", ".getMEmojis", function() {
        var type = 'memoji';
        var ID = $(this).attr("data-type");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getMEmojis").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getMEmojis").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    /*Get Gifs*/
    $(document).on("click", ".chtBtns", function() {
        var type = 'chat_btns';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("ch_fl_btns_container")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".chtBtns").css("pointer-events", "none");
                },
                success: function(response) {
                    $(".chtBtns").css("pointer-events", "auto");
                    $(".fl_btns").append(response);
                }
            });
        }
    });

    function ScrollBottomChat() {
        if ($("div").hasClass("all_messages")) {
            $(".all_messages").stop().animate({ scrollTop: $(".all_messages")[0].scrollHeight }, 100);
        }
    }
    ScrollBottomChat();
    $(document).on('keydown', ".mSize", function(e) {
        var key = e.which || e.keyCode || 0;
        if (key == 13) {
            var type = 'nmessage';
            var ID = $(".message_send_form_wrapper").attr("id");
            var value = $(this).val();
            var gMoney = $("#sicVal").val();
            var gf = '';
            var st = '';
            Message(ID, value, type, gf, st, '', gMoney);
        }
    });
    /*Add Sticker*/
    $(document).on("click", ".MaddSticker", function() {
        var type = 'message_sticker';
        var ID = $(this).attr("id");
        var dataID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pi=' + dataID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".Message_stickersContainer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var stickerID = response.st_id;
                if (stickerID) {
                    $(".Message_stickersContainer").remove();
                    var gMoney = $("#sicVal").val();
                    Message(dataID, '', 'nmessage', stickerID, '', '',gMoney);
                    $(".loaderWrapper").remove();
                }
            }
        });
    });
    /*Send Gif Message*/
    $(document).on("click", ".mrGif", function() {
        var src = $(this).attr("src");
        var ID = $(this).attr("data-id");
        var gMoney = $("#sicVal").val();
        Message(ID, '', 'nmessage', '', src, '',gMoney);
        $(".Message_stickersContainer").remove();
    });

    $(document).on("click", ".emoji_item_m", function() {
        var copyEmoji = $(this).attr("data-emoji");
        var getValue = $(".mSize").val();
        $(".mSize").val(getValue + ' ' + copyEmoji + ' ');
    });
    /*Comment*/
    function Message(ID, value, type, stickerID, gfSrc, file, gMoney) {
        var data = 'f=' + type + '&id=' + ID + '&val=' + encodeURIComponent(value) + '&sticker=' + stickerID + '&gif=' + gfSrc + '&fl=' + file + '&mo='+gMoney;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".Message_stickersContainer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } if(response == '403'){
                    PopUPAlerts('cNotSend', 'ialert');
                }else {
                    $(".all_messages_container").append(response);
                    ScrollBottomChat();
                }
                $(".mSize").val('');
                $(".Message_stickersContainer").remove();
                $(".loaderWrapper").remove();
                $(".i_write_secret_post_price").addClass("boxD");
                $("#sicVal").val('');
            }
        });
    }
    $(document).on("click", ".sendmes", function() {
        var value = $(".mSize").val();
        var ID = $(".message_send_form_wrapper").attr("id");
        var gMoney = $("#sicVal").val();
        Message(ID, value, 'nmessage', '', '', '',gMoney);
    });
    /*Uploading Message Image*/
    $(document).on("change", "#ci_image", function(e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $("#ci_image").attr("data-id");
        var ID = $(".message_send_form_wrapper").attr("id");
        var data = { f: id, c: ID };
        $("#uploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".ch_fl_btns_container").remove();
                $('.message_send_form_wrapper').append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".i_uploaded_iv").show();
                    if (response) {
                        var gMoney = $("#sicVal").val();
                        if(gMoney.length != 0){
                            Message(ID, '', 'nmessage','', '', response, gMoney);
                        }else{
                            Message(ID, '', 'nmessage', '', '', response, '');
                        }
                    }
                }
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Get More Comment*/
    $(document).on("click", ".more_comment", function() {
        var type = 'moreComment';
        var ID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $("#pf_l_" + ID).append(preLoadingAnimation);
                $(".comnts").css("pointer-events", "none");
            },
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    $("#i_user_comments_" + ID).html(response);
                    $(".lc_sum_container_" + ID).remove();
                }
                $(".comnts").css("pointer-events", "auto");
                $(".i_loading").remove();
            }
        });
    });
    $(document).on("click", ".chooseLanguage", function() {
        var type = 'chooseLanguage';
        var data = 'f=' + type;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".chooseLanguage").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".chooseLanguage").css("pointer-events", "auto");
            }
        });
    });
    /*Change Language*/
    $(document).on("click", ".chLang", function() {
        var type = 'changeMyLang';
        var id = $(this).attr("id");
        var data = 'f=' + type + '&id=' + id;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".chLang").css("pointer-events", "none");
                $(".purchase_post_details").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".chLang").css("pointer-events", "auto");
                if (response == '200') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 200);
                }

            }
        });
    });

    /*Search Creator*/
    $(document).delegate('#search_creator', 'keyup', function() {
        var searchValue = $(this).val();
        var type = 'searchCreator';
        var sum = searchValue.replace(/\s+/g, '').length;
        if (sum >= 1) {
            $(".i_general_box_search_container").show();
            setTimeout(() => {
                var data = 'f=' + type + '&s=' + encodeURIComponent(searchValue);

                $.ajax({
                    type: "POST",
                    url: siteurl + 'requests/request.php',
                    data: data,
                    cache: false,
                    beforeSend: function() {
                        $(".sb_items").append(plreLoadingAnimationPlus);
                    },
                    success: function(response) {
                        $(".sb_items").html(response);
                    }
                });

            }, 1000);
        } else {
            $(".i_general_box_search_container").hide();
        }
    });
    $(document).on("mouseup", function(e) {
        var container = $(".i_general_box_search_container");
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
            $(".sb_items").html('');
        }
    });
    $(document).on("click", ".newMessageMe", function() {
        var type = 'newMessageMe';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&user=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".newMessageMe").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".newMessageMe").css("pointer-events", "auto");
            }
        });
    });
    /*Send New Message*/
    $(document).on("click", ".sndNewMessage", function() {
        var type = 'newfirstMessage';
        var value = $("#sendNewM").val();
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&fm=' + encodeURIComponent(value) + '&u=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".sndNewMessage").css("pointer-events", "none");
            },
            success: function(response) {
                if (response != '404') {
                    window.location.href = response;
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
            }
        });
    });
    /*Update Theme*/
    $(document).on("click", ".updateTheme", function() {
        var type = 'updateTheme';
        var theme = $(this).attr("data-id");
        var data = 'f=' + type + '&theme=' + theme;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".sndNewMessage").css("pointer-events", "none");
            },
            success: function(response) {
                if (response != '404') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".mobile_hamburger", function() {
        if (!$(".leftStickyActive")[0]) {
            $(".mobile_left").addClass("leftStickyActive");
            $(".is_mobile").addClass("svg_active_icon");
        } else {
            $(".mobile_left").removeClass("leftStickyActive");
            $(".is_mobile").removeClass("svg_active_icon");
        }
    });
    $(document).on("click", ".mobile_srcbtn", function() {
        if (!$(".i_search_active")[0]) {
            $(".i_search").addClass("i_search_active");
        } else {
            $(".i_search").removeClass("i_search_active");
        }
    });
    $(window).on("resize", function() {
        checkWidth();
    });
    checkWidth();

    function checkWidth() {
        var vWidth = $(window).width();
        if (vWidth < 700) {
            if (!$(".mobile_footer_fixed_menu_container")[0]) {
                $.ajax({
                    type: "POST",
                    url: siteurl + 'requests/request.php',
                    data: 'f=fixedMenu',
                    cache: false,
                    beforeSend: function() {
                        $(".sndNewMessage").css("pointer-events", "none");
                    },
                    success: function(response) {
                        if (!$(".mobile_footer_fixed_menu_container")[0] && !$(".live_video_header")[0]) {
                            $("body").append(response);
                        }
                    }
                });
            }
        } else {
            if ($(".mobile_footer_fixed_menu_container")[0]) {
                $(".mobile_footer_fixed_menu_container").remove();
            }
        }
    }
    $(document).on("keyup keypress keydown", ".nwComment", function() {
        var ID = $(this).attr("data-id");
        var check = $(this).val().length;
        var $vWidth = $(window).width();
        if (check > 20) {
            $(".i_comment_footer" + ID).addClass("commentFooterResize");
        } else {
            $(".i_comment_footer" + ID).removeClass("commentFooterResize");
        }
    });
    $(document).on("click", ".settings_mobile_menu_container", function() {
        if (!$(".settingsMenuDisplay")[0]) {
            $(".i_settings_menu_wrapper").addClass("settingsMenuDisplay");
        } else {
            $(".i_settings_menu_wrapper").removeClass("settingsMenuDisplay");
        }
    });
    $(document).on("click", ".cList", function() {
        if (!$(".chatDisplay")[0]) {
            $(".chat_left_container").addClass("chatDisplay");
        } else {
            $(".chat_left_container").removeClass("chatDisplay");
        }
    });
    /*Delete Message*/
    $(document).on("click", ".dlMesv", function() {
        var type = 'deleteMessage';
        var ID = $(this).attr("id");
        var cID = $(".cList").attr("id");
        var data = 'f=' + type + '&id=' + ID + '&cid=' + cID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("#msg_" + ID).remove();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
            }
        });
    });
    $(document).on("click", ".delmes", function() {
        var type = 'ddelMesage';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".d_conversation", function() {
        var type = 'ddelConv';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Delete Message*/
    $(document).on("click", ".dlConv", function() {
        var type = 'deleteConversation';
        var ID = $(this).attr("id");
        var cID = $(".cList").attr("id");
        var data = 'f=' + type + '&id=' + ID + '&cid=' + cID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Search Creator*/
    $(document).delegate('#c_search', 'keyup', function() {
        var searchValue = $(this).val();
        var type = 'searchUser';
        var sum = searchValue.replace(/\s+/g, '').length;
        if (sum >= 3) {
            $(".chat_users_wrapper_results").show();
            $(".chat_users_wrapper").hide();
            setTimeout(() => {
                var data = 'f=' + type + '&key=' + encodeURIComponent(searchValue);
                $.ajax({
                    type: "POST",
                    url: siteurl + 'requests/request.php',
                    data: data,
                    cache: false,
                    beforeSend: function() {
                        $(".chat_users_wrapper_results").append(plreLoadingAnimationPlus);
                    },
                    success: function(response) {
                        if (response) {
                            $(".chat_users_wrapper_results").html(response);
                        } else {
                            $(".chat_users_wrapper_results").hide().html('');
                            $(".chat_users_wrapper").show();
                        }
                    }
                });
            }, 1000);
        } else {
            $(".chat_users_wrapper_results").hide().html('');
            $(".chat_users_wrapper").show();
        }
    });
    /*Hide Notification*/
    $(document).on("click", ".hidNot", function() {
        var type = 'hideNotification';
        var hideID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + hideID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".hidNot_" + hideID).fadeOut();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*UnBlock User*/
    $(document).on("click", ".unblock", function() {
        var type = 'unblock';
        var ID = $(this).attr("id");
        var blockedUserID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID + '&u=' + blockedUserID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".block_id_" + ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    $(".block_id_" + ID).fadeOut();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });

    $(document).on('submit', '#myPasswordChange', function(e) {
        e.preventDefault();
        var passChange = $(this);
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: passChange.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_not_mach , .warning_not_correct , .warning_write_current_password , .no_new_password_wrote , .minimum_character_not , .not_contain_whitespace").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                passChange.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    passChange.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '1') {
                    $(".warning_not_correct").show();
                } else if (data == '2') {
                    $(".warning_not_mach").show();
                } else if (data == '3') {
                    $(".warning_write_current_password").show();
                } else if (data == '4') {
                    $(".no_new_password_wrote").show();
                } else if (data == '5') {
                    $(".minimum_character_not").show();
                } else if (data == '6') {
                    $(".not_contain_whitespace").show();
                } else if (data == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    window.location.href = data;
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Notifications*/
    $(document).on("click", ".setChange", function() {
        var type = 'p_preferences';
        var setChange = $(this).val();
        var setType = $(this).attr("id");
        var dataNot = 'f=' + type + '&notit=' + encodeURIComponent(setChange) + '&sType=' + setType;
        $.ajax({
            type: 'POST',
            url: siteurl + "requests/request.php",
            data: dataNot,
            cache: false,
            beforeSend: function() {
                $("." + setType).append(plreLoadingAnimationPlus);
                $('.setChange').attr('disabled', true);
            },
            success: function(response) {
                setTimeout(function() {
                    $('.setChange').attr('disabled', false);
                }, 500);
                if (response == '200') {
                    if (setChange == '0') {
                        $("#" + setType).val('1');
                    }
                    if (setChange == '1') {
                        $("#" + setType).val('0');
                    }
                }
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1500);
            }
        });
    });
    /*Close Alert*/
    $(document).on("click", ".i_alert_close", function() {
        $(".i_bottom_left_alert_container").remove();
    });
    /*Create a Live Streaming PopUp Call*/
    $(document).on("click", ".cNLive", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + "requests/request.php",
            data: data,
            cache: false,
            beforeSend: function() {
                $("." + type).append(plreLoadingAnimationPlus);
                $("." + type).attr('disabled', true);
            },
            success: function(response) {
                setTimeout(function() {
                    $("." + type).attr('disabled', false);
                }, 500);
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1500);
            }
        });
    });
    /*Save Live Streaming*/
    $(document).on("click", ".createLiveStream", function() {
        var type = 'createFreeLiveStream';
        var liveStreamingTitle = $("#liveName").val();
        var data = 'f=' + type + '&lTitle=' + encodeURIComponent(liveStreamingTitle);
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() { $(".warning_required").hide(); },
            success: function(response) {
                var status = response.status;
                var start = response.start;
                if (status == '200') {
                    window.location.href = start;
                } else if (status == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (status == 'require') {
                    $(".require").show();
                } else if (status == '4') {
                    $(".name_short").show();
                }
            }
        });
    });
    /*Like Post*/
    $(document).on("click", ".lin_like , .lin_unlike", function() {
        var type = 'l_like';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&post=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {
                $('.lin_like , .lin_unlike').prop('disabled', true);
            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.like;
                var liksCount = response.likeCount;
                if (status == 'lin_unlike') {
                    $("#p_l_l_" + ID).removeClass("lin_like").addClass("lin_unlike");
                    $("#lp_sum_l_" + ID).html(liksCount);
                } else {
                    $("#p_l_l_" + ID).removeClass("lin_unlike").addClass("lin_like");
                    $("#lp_sum_l_" + ID).html(liksCount);
                }
                $("#p_l_l_" + ID).html(statusIcon);
                $('.lin_like , .lin_unlike').prop('disabled', false);
            }
        });
    });
    /*Save Live Streaming*/
    $(document).on("click", ".createLiveStreamPaid", function() {
        var type = 'createPaidLiveStream';
        var liveStreamingTitle = $("#liveName").val();
        var liveFee = $("#lsFee").val();
        var data = 'f=' + type + '&lTitle=' + encodeURIComponent(liveStreamingTitle) + '&pointfee=' + liveFee;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".warning_required").hide();
            },
            success: function(response) {
                var status = response.status;
                var start = response.start;
                if (status == '200') {
                    window.location.href = start;
                } else if (status == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (status == 'point') {
                    $(".point_warning").show();
                } else if (status == 'title') {
                    $(".title_warning").show();
                } else if (status == 'require') {
                    $(".require").show();
                }
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".purchaseLiveButton", function() {
        var type = 'pLivePurchase';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&purchase=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("click", ".joinLiveStream", function() {
        var type = 'goWalletLive';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&p=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                window.location.href = response;
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".unSubU", function() {
        var type = 'unSub';
        var post = $(this).attr("data-u");
        var data = 'f=' + type + '&u=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".unSubUP", function() {
        var type = 'unSubP';
        var post = $(this).attr("data-u");
        var data = 'f=' + type + '&u=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*UnSubscribe PointUser*/
    $(document).on("click", ".unSubPNow", function() {
        var type = 'unSubscribePoint';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    location.reload();
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                }

            }
        });
    });
    /*UnSubscribe User*/
    $(document).on("click", ".unSubNow", function() {
        var type = 'unSubscribe';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    location.reload();
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                }

            }
        });
    });
    /*Upload Verification Files*/
    $(document).on("change", ".cTumb", function(e) {
        e.preventDefault();
        var f = 'vTumbnail';
        var id = $(this).attr("data-id");
        var data = { f: f, id: id };
        $("#tuploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".iu_f_" + id).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                $("#viTumb" + id).css('background-image', 'url(' + response + ')');
                $("#viTumbi" + id).attr('src', response);
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    
	
	
	

	
	
	
	
	
	
	
	
	
	
	
	
    /*Call Post for Share*/
    $(document).on("click", ".in_tips", function() {
        var ID = $(this).attr("data-id");
        var tipPostID = $(this).attr("data-ppid");
        var type = 'p_tips';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tip_u=' + ID + '&tpid=' + tipPostID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.in_tips').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.in_tips').prop('disabled', false);
                }
            });
        }
    });
 // SEND TIPS — feed/profile (scoped to the opened modal)
$(document).on("click", ".send_tip_btn", function () {
  var $m        = $(this).closest(".i_modal_bg_in");   // scope: this modal only
  var $fee      = $m.find(".i_set_subscription_fee");
  var ID        = $fee.attr("id");                     // tip_u (receiver id)
  var tipPostID = $fee.attr("data-pid") || "";         // post id (optional)
  var tipValue  = ($m.find("#tipVal").val() || "").toString().trim();

  if (!ID) return;
  if (!tipValue) { $m.find(".i_tip_not").css("color", "red"); return; }

  $.ajax({
    type: "POST",
    url: siteurl + 'requests/request.php',
    data: 'f=p_sendTip'
        + '&tip_u=' + encodeURIComponent(ID)
        + '&tipVal=' + encodeURIComponent(tipValue)
        + '&tpid='   + encodeURIComponent(tipPostID),
    dataType: "json",
    cache: false,
    beforeSend: function () { $m.find('.send_tip_btn').prop('disabled', true); },
    success: function (res) {
      if (res.status === 'ok') {
        if (tipPostID) { showBubble(ID, tipPostID); }  // feed bubble only

        // graceful close
        $m.find(".i_modal_in_in").addClass("i_modal_in_in_out");
        setTimeout(function(){ $m.remove(); }, 200);

        // hard fallback (in case another old handler kept it around)
        setTimeout(function(){ $('.i_modal_bg_in').remove(); }, 500);
      } else if (res.enamount === 'notEnough') {
        $m.find(".i_tip_not").css("color", "red");
      } else if (res.enamount === 'notEnouhCredit') {
        window.location.href = res.redirect;
      }
    },
    complete: function(){ $m.find('.send_tip_btn').prop('disabled', false); }
  });
});

	

    function showBubble(userid, postid) {
        $(".tip_" + postid).show();
        document.getElementById('notification-sound-coin').play();
        setTimeout(() => {
            $(".tip_" + postid).fadeOut();
        }, 5000);
    }
    $(document).on("click", ".live_coin_send", function() {
        var ID = $(this).attr("data-u");
        var tipValue = $(this).attr("data-tip");
        var liveID = $(".live_wrapper_tik").attr("id");
        var type = 'p_sendGift';
        var data = 'f=' + type + '&tip_u=' + ID + '&tipTyp=' + tipValue + '&lid=' + liveID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $('.co_' + tipValue).append(plreLoadingAnimationPlus);
                $(".live_animation_wrapper").remove();
            },
            success: function(response) {
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1000);
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                var senamount = response.enamount;
                var sendSuccessAnimation = response.giftAnimation;
                var currentBalance = response.current_balance;
                if (responseStatus == 'ok') {
                    $(".filtvid").append(sendSuccessAnimation);
                    setTimeout(() => {
                        $(".live_animation_wrapper").remove();
                    }, 5000);
                    $(".crnblnc").html(currentBalance);
                }
                if (senamount == 'notEnough') {
                    $(".i_tip_not").css("color", "red");
                }
                if (senamount == 'notEnouhCredit') {
                    window.location.href = responseRedirect;
                }
            }
        });
    });
    /*QR Code Generator*/
    $(document).on("click", ".qrCodeGenerator", function() {
        var type = 'generateQRCode';
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $('.createQrBox').append(plreLoadingAnimationPlus);
                $('.qrCodeGenerator').prop('disabled', true);
            },
            success: function(response) {
                $(".loaderWrapper").remove();
                if (response) {
                    $(".qrCodeImage").html('<img src="' + response + '">');
                }
                $('.qrCodeGenerator').prop('disabled', false);
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del-story", function() {
        var type = 'deleteStorie';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del-product", function() {
        var type = 'deleteProduct';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    /*Share This Story*/
    $(document).on("click", ".share_this_story", function() {
        var type = 'shareMyStorie';
        var ID = $(this).attr("id");
        var text = $(".st_txt_" + ID).val();
        var data = 'f=' + type + '&id=' + ID + '&txt=' + encodeURIComponent(text);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('shared_storie_success', 'ialert');
                    window.location = siteurl;
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });


	
	
	
	
	
/* MF OVERRIDE — legacy mention UI disabled (handled by new endpoint/UI) */
(function($){
  // keep helper available if other code calls it
  $.fn.focusTextToEnd = $.fn.focusTextToEnd || function(){
    this.focus();
    var v = this.val();
    this.val('').val(v);
    return this;
  };
  // make sure any old bindings are gone (safety)
  $(document).off('keyup', '.newPostT');
  $(document).off('click', '.mres_u');
})(jQuery);

	
	
	
	
	

    var timeoutId;
    // Append User Profile Card
    $(document).on("mouseenter", ".ownTooltip", function() {
        if ($(window).width() > 800) {
            var tooltipText = $(this).attr("data-label");
            $(this).append("<div class='ownTooltipWrapper'>" + tooltipText + "</div>");
        }
    });
    $(document).on('mouseleave', '.ownTooltip', function() {
        $(".ownTooltipWrapper").fadeOut("normal", function() {
            window.clearTimeout(timeoutId);
            timeoutId = null;
            $(this).remove();
        });
    });
    /*Get PopUp for Which Story*/
    $(document).on("click", ".chsStoryw", function() {
        var type = 'whcStory';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    $(document).on("click", ".share_text_story", function() {
        var type = 'shareMyTextStory';
        var ID = $(".choosed_bg").attr("data-iid");
        var textStory = $("#strt_text").val();
        var data = 'f=' + type + '&id=' + ID + '&stext=' + encodeURIComponent(textStory);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    PopUPAlerts('shared_storie_success', 'ialert');
                    setTimeout(() => {
                        window.location = siteurl;
                    }, 1000);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".buy__myproduct", function() {
        var type = 'buyProduct';
        var pointID = $(this).attr("data-id");
        var data = 'f=' + type + '&type=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $("#p_i_" + pointID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == 'me') {
                    PopUPAlerts('cnbowni', 'ialert');
                } else {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                    $(".loaderWrapper").remove();
                    $(".credit_plan_box").css("pointer-events", "auto");
                }
            }
        });
    });
    $(document).on("click", ".s_p_p_p_download", function() {
        var type = 'downloadMyProduct';
        var pointID = $(this).attr("data-id");
        var data = 'f=' + type + '&myp=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'dfile.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".s_p_p_p_download").css("pointer-events", "none");
            },
            success: function(response) {
                if (response == 'me') {
                    PopUPAlerts('sRong', 'ialert');
                }
                setTimeout(() => {
                    $(".s_p_p_p_download").css("pointer-events", "auto");
                }, 2000);

            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".sendPoint", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_tips';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPoint').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPoint').prop('disabled', false);
                }
            });
        }
    });
    $(document).on("click", ".sendPointMessage", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_tips_message';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPointMessage').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPointMessage').prop('disabled', false);
                }
            });
        }
    });
    function VideoCallAlert(callID) {
        var data = 'f=videoCallAlert' + '&call=' + callID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                        $("#notification-sound-call")[0].play();
                    }, 200);
                }
            }
        });
    }
    $(document).on("click", ".call_accept", function() {
        var ID = $(this).attr("id");
        var type = 'call_accepted';
        var data = 'f=' + type + '&accID=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $('.call_accept').prop('disabled', true);
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response) {
                    $("#notification-sound-call")[0].pause();
                    window.location.href = response;
                }
            }
        });
    });
    $(document).on("click", ".call_decline", function() {
        var ID = $(this).attr("id");
        var type = 'call_declined';
        var data = 'f=' + type + '&accID=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $('.call_decline').prop('disabled', true);
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_bg_in").remove();
                $("#notification-sound-call")[0].pause();
            }
        });
    });
    /*SEND TIPS*/
    $(document).on("click", ".send_tip_btn_message", function() {
        var ID = $(".subU").attr("id");
        var chatID = $(".message_send_form_wrapper").attr("id");
        var tipValue = $("#tipVal").val();
        var type = 'p_sendTipMessage';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tip_u=' + ID + '&tipVal=' + tipValue +'&chID=' + chatID;
            $.ajax({
                type: "POST",
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.send_tip_btn').prop('disabled', true);
                },
                success: function(response) {
                    if (response == 'notEnough') {
                        $(".i_tip_not").css("color", "red");
                    }
                    if (response == 'notEnouhCredit') {
                        window.location.href = siteurl + 'purchase/purchase_point';
                    }

                    if (response != '404') {
                        if(response){
                            PopUPAlerts('tipSuccess', 'ialert');
                            $(".i_modal_in_in").addClass("i_modal_in_in_out");
                            setTimeout(() => {
                                $(".i_modal_bg_in").remove();
                            }, 200);
                            $(".all_messages_container").append(response);
                            ScrollBottomChat();
                        }else{
                            $(".aval").val('').focus();
                        }
                    }
                    $('.send_tip_btn_message').prop('disabled', false);
                }
            });
        }
    });


    /*Unlock Message*/
    $(document).on("click",".unlockFor", function(){
        var ID = $(this).attr("id");
        var cID = $(".message_send_form_wrapper").attr("id");
        var type = 'unlockMessage';
        var data = 'f='+type+'&mi='+ID+'&ci='+cID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
            },
            success: function(response) {
                if(response == '404'){
                   $(".unlc_"+ID).show();
                }else if(response == '403'){
                  PopUPAlerts('tipSuccess', 'ialert');
                }else{
                   $("#msg_"+ID).html('').append(response);
                }
            }
        });
    });
    $(document).on("click",".joinOffline", function(){
        PopUPAlerts('camOffline', 'camAlert');
    });
    $(document).on("click",".rplyComment", function(){
        var ID = $(this).attr('id');
        var who = $(this).attr("data-who");
        var textComment = $("#comment"+ID).focus().val('@'+who+' ');
    });
    $(document).on("click",".boostThisPost", function(){
        var type = 'getBoostList';
        var ID = $(this).attr("id");
        var data = 'f='+type+'&bp='+ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("click",".bThisP", function(){
        var type = 'boostThisPlan';
        var planID = $(this).attr("id");
        var postID = $(this).attr("data-post");
        var data = 'f='+type+'&pbID='+planID+'&bpID='+postID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
                $(".warning_boost_post").hide();
            },
            success: function(response) {
                if(response == '404'){
                  $(".warning_boost_post").show();
                }else{
                   window.location.href = response;
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Notifications*/
    $(document).on("click", ".boosStat", function() {
        var type = 'updateBoostStatus';
        var setChange = $(this).val();
        var setType = $(this).attr("data-id");
        var dataNot = 'f=' + type + '&mod=' + encodeURIComponent(setChange) + '&bpid=' + setType;
        $.ajax({
            type: 'POST',
            url: siteurl + "requests/request.php",
            data: dataNot,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {

            }
        });
    });
    $(document).on("click",".bankOpen", function(){
        if ($("div").hasClass("displayNone")) {
            $(".bank_container").removeClass('displayNone');
        }else{
            $(".bank_container").addClass('displayNone');
        }
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_card", function(e) {
        e.preventDefault();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
        $("#pBUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".f_" + type).html('');
                $("#uploadVal_" + type).val('');
                $("#" + type).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".f_" + type).append(response);
                    var K = $(".f_" + type + " > div:last").attr("id");
                    var T = K;
                    if (T != "undefined,") {
                        $("#uploadVal_" + type).val(T);
                    }
                    $("#id_card").val('');
                }
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Send Verification Certificate Request*/
    $(document).on("click", ".bnk_Next", function() {
        var type = 'verificationRequestForBankPayment';
        var IDPhoto = $("#uploadVal_sec_one").val();
        var planID = $(this).attr('id');
        var data = 'f=' + type + '&cP=' + IDPhoto + '&pID=' +planID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".card , .both , .photo").hide();
            },
            success: function(response) {
                if (response == '200') {
                    $(".payment_success_bank").show();
                    $(".bank_container").hide();
                    setTimeout(() => {
                        window.location.href = siteurl + 'settings?tab=purchased_points';
                    }, 5000);
                } else if (response == 'card') {
                    $("#id_card").val('');
                    $(".card").show();
                } else if (response == 'photo') {
                    $("#id_card").val('');
                    $(".photo").show();
                } else if (response == 'both') {
                    $("#id_card").val('');
                    $(".both").show();
                }
                $(".i_nex_btn").css("pointer-events", "auto");
            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".sendFrame", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_frame';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPoint').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPoint').prop('disabled', false);
                }
            });
        }
    });
    $(document).on("click", ".buyFrameGift", function() {
        var type = 'buyFrameGift';
        var pointID = $(this).attr("id");
        var purchaseToThisUser = $(".profile_wrapper").attr("data-u");
        var data = 'f=' + type + '&type=' + pointID + '&pUf=' + purchaseToThisUser;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $("#p_i_" + pointID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if(response == '404'){
                    PopUPAlerts('sWrong', 'ialert');
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                    $(".loaderWrapper").remove();
                    $(".credit_plan_box").css("pointer-events", "auto");
                }else if(response == '200') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                    $(".loaderWrapper").remove();
                    $(".credit_plan_box").css("pointer-events", "auto");
                    PopUPAlerts('buySuccess', 'ialert');
                    setTimeout(() => {
                        location.reload();
                    }, 5000);
                }else {

                }
            }
        });
    });
    /*Update My Frame*/
    $(document).on("click", ".updateMyFrame", function() {
        var type = 'UpdateMyFrame';
        var frameID = $(this).attr("data-id");
        var data = 'f=' + type + '&frameID=' + frameID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $(".credit_plan_box_" + frameID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if(response == '400'){
                    PopUPAlerts('sWrong', 'ialert');
                }else{
                    PopUPAlerts('frameSuccess', 'ialert');
                }

                setTimeout(() => {
                   $(".loaderWrapper").remove();
                   $(".credit_plan_box").css("pointer-events", "auto");
                }, 3000);

            }
        });
    });
    /*Update My Frame*/
    $(document).on("click", ".inv_btn", function() {
        var type = 'inviteFriend';
        var iemail = $("#inv_email").val();
        var data = 'f=' + type + '&invEmail=' + encodeURIComponent(iemail);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/inviteEmail.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".inv_btn").css("pointer-events", "none");
                $(".already_in_use").hide();
                $(".inviteemail").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if(response == '404'){
                    PopUPAlerts('sWrong', 'ialert');
                }else if(response == '1'){
                    $(".already_in_use").show();
                }else{
                    PopUPAlerts('emailSendsuccess', 'ialert');
                }
                $(".inviteemail_input").val('');
                setTimeout(() => {
                   $(".inv_btn").css("pointer-events", "auto");
                   $(".already_in_use").hide();
                }, 3000);
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".stat_icon", function() {
        $(this).hide();
        $(this).next(".stat_icona").show();
        var ID = $(this).attr("id");
        $(".bstatistick_"+ID).addClass("changeHeight");
    });

    $(document).on("click", ".stat_icona", function() {
        $(this).hide();
        $(this).prev(".stat_icon").show();
        var ID = $(this).attr("id");
        $(".bstatistick_"+ID).removeClass("changeHeight");
    });

    $(document).on("click", ".move_my_point", function () {
      const type = "moveMyAffilateBalance";
      const point = window.affiliateConfig?.earnings || 0;
      const data = "f=" + type + "&myp=" + point;

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function () {
          $(".move_my_point").hide().css("pointer-events", "none");
        },
        success: function (response) {
          if (response === "me") {
            PopUPAlerts("sRong", "ialert");
          } else {
            location.reload();
          }

          setTimeout(() => {
            $(".move_my_point").show().css("pointer-events", "auto");
          }, 2000);
        }
      });
    });
    $(document).on("click",".bCountry", function(){
        var ID = $(this).attr("data-c");
        var i = $(this).attr("id");
        var type = 'bCountry';
        var data =  'f='+type+'&c='+ID;
        $.ajax({
          type: 'POST',
          url: siteurl + "requests/request.php",
          data: data,
          cache: false,
          success: function(response) {
             if(response == '1'){
               $("#"+i).addClass('chsed');
             }else{
               $("#"+i).removeClass('chsed');
             }
          }
        });
      });
      $(document).on("click", ".move_my_point_earn", function () {
      const data = {
        f: "moveMyEarnedPoints",
        myp: window.earnedPointData?.allTotal || 0
      };

      const $button = $(".move_my_point");
      const $alertBox = $(window.earnedPointData.alertSuccessSelector);

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function () {
          $button.hide().css("pointer-events", "none");
          $alertBox.text("").hide();
        },
        success: function (response) {
          if (response === "me") {
            PopUPAlerts("sRong", "ialert");
          } else if (response === "ok") {
            location.reload();
          } else {
            $alertBox.text(response).show();
          }

          setTimeout(() => {
            $button.show().css("pointer-events", "auto");
          }, 2000);
        }
      });
    });
    // Toggle advanced settings (question and slots)
    $(document).on("change", ".subfeea", function () {
      const type = $(this).data("id");
      const isChecked = $(this).val() === "ok";

      if (isChecked) {
        $("#" + type).val("not");
        $("." + type).hide();
      } else {
        $("#" + type).val("ok");
        $("." + type).show();
      }
    });

    // Save product edit
    $(document).on("click", ".pr_save_btna", function () {
      const data = {
        f: "saveEditPr",
        prid: window.editProduct.productID,
        prnm: $("#pr_name").val(),
        prprc: $("#pr_price").val(),
        prdsc: $("#pr_description").val(),
        prdscinf: $("#pr_conf").val(),
        lmSlot: $("#limitslots").val(),
        askQ: $("#askaquestion").val(),
        qAsk: $("#question_ask").val(),
        lSlot: $("#limit_slot").val()
      };

      $(window.editProduct.warningTextSelector).hide();
      $(window.editProduct.successTextSelector).hide();

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        success: function (response) {
          if (response === "200") {
            $(window.editProduct.successTextSelector).show();
          } else {
            $(window.editProduct.warningTextSelector).show();
          }
        }
      });
    });
    $(document).ready(function () {
        let debounceTimer;

        $(document).on("keyup", "#newEmail", function () {
          clearTimeout(debounceTimer);
          const email = $(this).val();

          if (email.length < 3) return;

          debounceTimer = setTimeout(() => {
            const data = {
              f: "checkemail",
              newEmail: email
            };

            $(".warning_inuse, .warning_invalid").hide();

            $.ajax({
              type: "POST",
              url: siteurl + "requests/request.php",
              data: data,
              cache: false,
              success: function (response) {
                if (response === "404") {
                  $(".warning_invalid").hide();
                  $(".warning_inuse").show();
                } else if (response === "no") {
                  $(".warning_inuse").hide();
                  $(".warning_invalid").show();
                } else {
                  $(".warning_inuse, .warning_invalid").hide();
                }
              }
            });
          }, 500);
    });
    $(document).on("keyup", ".aval", function () {
      const val = parseFloat($(this).val());
      const ID = $(this).attr("id");

      $(".i_t_warning").hide();

      if (ID === "spweek" && val < parseFloat(window.subscriptionConfig.minWeekAmount)) {
        $("#waweekly").show();
      } else if (ID === "spmonth" && val < parseFloat(window.subscriptionConfig.minMonthAmount)) {
        $("#mamonthly").show();
      } else if (ID === "spyear" && val < parseFloat(window.subscriptionConfig.minYearAmount)) {
        $("#yayearly").show();
      }
    });

    $(document).on("change", ".subfeea", function () {
      const type = $(this).data("id");
      const currentVal = $(this).val();
      $(this).val(currentVal === "1" ? "0" : "1");
    });

    
		

// SAVE subscription fees (robust)
$(document).off("click", ".c_uNext").on("click", ".c_uNext", function () {
  const loading = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
  const loader  = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + loading + '</div></div></div>';

  // read toggle value safely whether it's checkbox, hidden, or custom switch
  const getFlag = (name) => {
    const el = document.querySelector(`input[name="${name}"]`);
    if (!el) return "0";
    if (el.type === "checkbox" || el.type === "radio") return el.checked ? "1" : "0";
    const v = (el.value || "").toString().toLowerCase();
    return (v === "1" || v === "true" || v === "on") ? "1" : "0";
  };

  const data = {
    f: "updateSubscriptionPayments",
    wSubWeekAmount: window.subscriptionConfig?.subWeekStatus     === "yes" ? $("#spweek").val()  : "",
    mSubMonthAmount: window.subscriptionConfig?.subMonthlyStatus === "yes" ? $("#spmonth").val() : "",
    mSubYearAmount: window.subscriptionConfig?.subYearlyStatus   === "yes" ? $("#spyear").val()  : "",
    wStatus: getFlag("weekly"),
    mStatus: getFlag("monthly"),
    yStatus: getFlag("yearly")
  };

  // basic validation
  if (data.wStatus === "1" && !data.wSubWeekAmount)  { $("#wweekly").show();  return; }
  if (data.mStatus === "1" && !data.mSubMonthAmount) { $("#wmonthly").show(); return; }
  if (data.yStatus === "1" && !data.mSubYearAmount)  { $("#wyearly").show();  return; }

  $(".i_nex_btn").css("pointer-events", "none");
  $("#wweekly,#wmonthly,#wyearly,.weekly_success,.monthly_success,.yearly_success").hide();
  $(".i_become_creator_box_footer").append(loader);

  $.ajax({
    type: "POST",
    url: siteurl + "requests/request.php",
    data: data,
    dataType: "text",
    cache: false
  })
  .done(function (raw) {
    let res;
    try { res = typeof raw === "string" ? JSON.parse(raw) : raw; }
    catch { console.warn("[fees] Non-JSON:", raw); PopUPAlerts('sWrong','ialert'); return; }

    if (res.weekly  === "404") $("#wweekly").show();
    if (res.weekly  === "200") $(".weekly_success").show();

    if (res.monthly === "404") $("#wmonthly").show();
    if (res.monthly === "200") $(".monthly_success").show();

    if (res.yearly  === "404") $("#wyearly").show();
    if (res.yearly  === "200") $(".yearly_success").show();
  })
  .fail(function (xhr) {
    console.warn("[fees] AJAX fail", xhr.status, xhr.responseText);
    PopUPAlerts('sWrong','ialert');
  })
  .always(function () {
    $(".loaderWrapper").remove();
    $(".i_nex_btn").css("pointer-events", "auto");
  });
});

		

		

    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdProd", function() {
        var type = 'productStatus';
        var value = $(this).val();
        var prID = $(this).attr("data-id");
        var data = 'f=' + type + '&mod=' + value + '&id='+prID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $("#pr_s_"+prID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#pr_i_"+prID).val('0');
                    } else {
                        $("#pr_i_"+prID).val('1');
                    }
                } else if (response == '404') {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();

            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".delprod", function() {
        var type = 'delete_product';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".del_stor", function() {
        var type = 'delete_storie_alert';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".stViewers", function() {
        var type = 'storieViewers';
        var ID = $(this).attr("data-viewer");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("keyup", ".avalv", function() {
        var inputVal = $(this).val();
        $(".i_t_warning").hide();

        if (inputVal === "0" || inputVal === "" || inputVal === "undefined") {
          $(".i_t_warning").show();
        }

        var calculatedValue = parseFloat(inputVal) * parseFloat(pointEqualValue);
        if (!isNaN(calculatedValue)) {
          $(".pricecal").text(calculatedValue.toFixed(2));
        }
      });

    $(document).on("click", ".c_UpdateCostV", function() {
        var videoCost = $(".avalv").val();
        var data = "f=vCost&vCostFee=" + encodeURIComponent(videoCost);

        $.ajax({
          type: "POST",
          url: siteurl + "requests/request.php",
          data: data,
          beforeSend: function() {
            $(".i_t_warning, .successNot").hide();
          },
          success: function(response) {
            if (response === "not") {
              $(".i_t_warning").show();
            } else {
              $(".successNot").show();
            }
          }
        });
    });
    $(document).on("click", ".payClose", function() {
        $(".i_payment_pop_box").addClass("i_modal_in_in_out");
        setTimeout(() => {
            $(".i_subs_modal").remove();
        }, 200);
    });
	
	

    // END of your previous code.

// PASTE THE NEW NOTIFICATION CODE HERE
// ========================================================================
// --- FINAL NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================
/* ===== NOTIFICATIONS BLOCK (drop-in, no IIFE/use strict) ===== */

/* ===== NOTIFICATIONS BLOCK (drop-in, no IIFE/use strict) ===== */
// ========================================================================
// --- FINAL NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================

// This function will poll the server for new notification and message counts.
// ========================================================================
// --- RELIABLE & SIMPLE NOTIFICATION SCRIPT ---
// ========================================================================
// ========================================================================
// --- FINAL NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================

// ========================================================================
// --- WORKING NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================

// ========================================================================
// --- WORKING NOTIFICATION SYSTEM SCRIPT (PASTE THIS ENTIRE BLOCK) ---
// ========================================================================

// Global variables to track previous counts and prevent unwanted sounds
window.previousNotificationCount = 0;
window.previousMessageCount = 0;
window.isFirstLoad = true;

// Your existing notification sound function (keep this)
function playNotificationSound() {
    var audio = document.getElementById('notification-sound-not');
    if (audio) {
        var playPromise = audio.play();
        if (playPromise !== undefined) {
            playPromise.then(_ => {}).catch(error => {});
        }
    }
}

function sanitizeBadgesOnLoad($) {
    [
        {wrap: '.not_not', badge: '.isum.sum_not'},
        {wrap: '.msg_not', badge: '.isum.sum_m'}
    ].forEach(function(sel) {
        var $wrap = $(sel.wrap);
        var $badge = $wrap.find(sel.badge);
        if (!$badge.length) return;

        var raw = $badge.text();
        var txt = $.trim(String(raw));
        var n = parseInt(txt, 10);

        // hide if it's "0", empty, NaN, or <= 0
        if (!txt || isNaN(n) || n <= 0) {
            $badge.text('');
            $wrap.hide();
        }
    });
}

function attachZeroKiller($) {
    [
        {wrap: '.not_not', badge: '.isum.sum_not'},
        {wrap: '.msg_not', badge: '.isum.sum_m'}
    ].forEach(function(sel) {
        var $wrap = $(sel.wrap);
        var $badge = $wrap.find(sel.badge);
        if (!$badge.length) return;

        // Observe text changes; if "0", wipe & hide immediately
        var target = $badge.get(0);
        var obs = new MutationObserver(function() {
            var t = $.trim(String($badge.text()));
            var v = parseInt(t, 10);
            if (!t || isNaN(v) || v <= 0) {
                $badge.text('');
                $wrap.hide();
            }
        });
        obs.observe(target, { characterData: true, subtree: true, childList: true });
    });
}

// Modified version of your existing getm function with smart sound logic
var g = '';
getm(g);

function getm(g) {
    var type = 'get';
    if ($.trim(type).length === 0) {
        setTimeout(getm, 10000);
    } else {
        $.ajax({
            type: 'GET',
            url: siteurl + 'requests/get.php?f=1',
            dataType: "json",
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                var messageNotificationStatus = response.messageNotificationStatus;
                var notificationStatus = response.notificationStatus;
                var unReadedNotfications = parseInt(response.unReadedNotfications, 10) || 0;
                var unReadMessageNotifications = parseInt(response.unReadMessageNotifications, 10) || 0;
                var videoCallFound = response.videoCallFound;
                var acceptStatus = response.acceptStatus;

                // --- Handle Messages ---
                if (messageNotificationStatus == '1' && unReadMessageNotifications > 0) {
                    $(".msg_not").show();
                    $(".sum_m").html(unReadMessageNotifications);
                    
                    // Play sound if: not first load AND (count increased OR data-id changed)
                    var shouldPlayMessageSound = false;
                    var currentMessageDataId = $(".sum_m").attr("data-id");
                    
                    if (!window.isFirstLoad) {
                        // Sound if count went up OR if the data-id is different (new notification batch)
                        if (unReadMessageNotifications > window.previousMessageCount || 
                            currentMessageDataId != messageNotificationStatus) {
                            shouldPlayMessageSound = true;
                        }
                    }
                    
                    if (shouldPlayMessageSound) {
                        playNotificationSound();
                    }
                    
                    $(".sum_m").attr("data-id", messageNotificationStatus);
                } else {
                    // Hide message badge if no unread messages
                    $(".msg_not").hide();
                    $(".sum_m").html('');
                }

                // --- Handle Notifications ---
                if (notificationStatus == '1' && unReadedNotfications > 0) {
                    $(".not_not").show();
                    $(".sum_not").html(unReadedNotfications);
                    
                    // Play sound if: not first load AND (count increased OR data-id changed)
                    var shouldPlayNotificationSound = false;
                    var currentNotificationDataId = $(".sum_not").attr("data-id");
                    
                    if (!window.isFirstLoad) {
                        // Sound if count went up OR if the data-id is different (new notification batch)  
                        if (unReadedNotfications > window.previousNotificationCount || 
                            currentNotificationDataId != notificationStatus) {
                            shouldPlayNotificationSound = true;
                        }
                    }
                    
                    if (shouldPlayNotificationSound) {
                        document.getElementById('notification-sound-not').play();
                    }
                    
                    $(".sum_not").attr("data-id", notificationStatus);
                } else {
                    // Hide notification badge if no unread notifications
                    $(".not_not").hide();
                    $(".sum_not").html('');
                }

                // Update our tracking variables
                window.previousNotificationCount = unReadedNotfications;
                window.previousMessageCount = unReadMessageNotifications;
                window.isFirstLoad = false;

                // --- Handle Video Calls ---
                if (videoCallFound) {
                    if (!$("div").hasClass("videoCall")) {
                        VideoCallAlert(videoCallFound);
                    }
                }
                if (acceptStatus == '3') {
                    $(".caller_det").hide();
                    $(".call_declined").show();
                    $("#notification-sound-call")[0].pause();
                }

                if (!g) {
                    setTimeout(getm, 10000);
                }
            }
        });
    }
}

// Always sanitize and start observer on DOM ready
jQuery(function($) {
    sanitizeBadgesOnLoad($);
    attachZeroKiller($);
});

// Handle clicking on a single notification
$(document).on('click', '.mf-noti-link', function(e) {
    e.preventDefault();
    const link = $(this);
    const destinationUrl = link.attr('href');
    const notifId = link.data('id');
    const item = link.closest('.i_message_wrpper, .i_notification_wrpper');

    if (item.hasClass('is-unread')) {
        item.removeClass('is-unread').addClass('is-read');
    }

    $.ajax({
        type: 'POST',
        url: window.siteurl + 'requests/request.php',
        data: { f: 'markNotificationRead', id: notifId },
        complete: function() { window.location.href = destinationUrl; }
    });
});

// Handle clicking the "Mark all as read" button
$(document).on('click', '#mfNotiMarkAll', function(e) {
    e.preventDefault();
    const button = $(this);
    button.prop('disabled', true);

    // Visually update all items
    $('.i_message_wrpper.is-unread, .i_notification_wrpper.is-unread').removeClass('is-unread').addClass('is-read');

    // Forcefully hide the bell icon badge
    $('.not_not').hide();
    $('.not_not .isum.sum_not').text('');

    // Reset our tracking to prevent sound on next poll
    window.previousNotificationCount = 0;

    $.ajax({
        type: 'POST',
        url: window.siteurl + 'requests/request.php',
        data: { f: 'markAllNotificationsRead' },
        complete: function() { button.prop('disabled', false); }
    });
});

// Handle "Load More" on the notifications page
$(document).on('click', '#loadMoreNotifications', function(e) {
    e.preventDefault();
    const button = $(this);
    const container = $('#notifications-container');
    const lastId = container.find('.i_notification_wrpper:last').data('last');

    if (!lastId) return;

    const originalButtonText = button.text();
    button.text('Loading...').prop('disabled', true);

    $.ajax({
        type: "POST",
        url: window.siteurl + 'requests/request.php',
        data: { f: 'loadMoreNotifications', last_id: lastId },
        dataType: 'json',
        success: function(response) {
            if (response && response.html) {
                container.append(response.html);
            }
            if (!response || !response.has_more) {
                $('#load-more-wrapper').removeClass('badge--show');
            }
        },
        error: function() { alert('Could not load more notifications.'); },
        complete: function() {
            button.text(originalButtonText).prop('disabled', false);
        }
    });
});

// Your other existing event handlers (keep these as they are)
$(document).on("click", ".loginForm", function() {
    $('.i_modal_bg').addClass('i_modal_display');
});
$(document).on("click", ".i_modal_close", function() {
    $('.i_modal_bg').removeClass('i_modal_display');
    $(".i_modal_in").attr("style", "");
    $(".i_modal_forgot").hide();
});
$(document).on("click", ".password-reset", function() {
    $(".i_modal_in").hide();
    $(".i_modal_forgot").show();
});
$(document).on("click", ".already-member", function() {
    $(".i_modal_in").show();
    $(".i_modal_forgot").hide();
});

$(".i_comment_form_textarea").focusin(function() {
    var words = $(this).val();
    var ID = $(this).attr("data-id");
});
$(document).on("click", ".openPostMenu", function() {
    var ID = $(this).attr("id");
    $(".mnoBox" + ID).addClass("dblock");
});
$(document).on("click", ".openShareMenu", function() {
    var ID = $(this).attr("id");
    $(".mnsBox" + ID).addClass("dblock");
});
$(document).on("click", ".openComMenu", function() {
    var ID = $(this).attr("id");
    $(".comMBox" + ID).addClass("dblock");
});
$(document).on("click", ".msg_Set", function() {
    var ID = $(this).attr("id");
    if ($(".msg_Set")[0]) {
        $(".msg_Set").removeClass("dblock");
    }
    $(".msg_Set_" + ID).addClass("dblock");
});
$(document).on("click", ".smscd", function() {
    var ID = $(this).attr("id");
    if ($(".smscd")[0]) {
        $(".me_msg_plus").removeClass("dblock");
    }
    $(".msg_set_plus_" + ID).addClass("dblock");
});
$(document).on("click", ".whs", function() {
    $(".i_choose_ws_wrapper").addClass("dblock");
});
$(document).on("click", ".in_comment", function() {
    var ID = $(this).attr("id");
    $("#comment" + ID).focus();
});
	
	

	
	/* ======================================================================== */


/* ======================================================================== */
/* == FINAL SCRIPT for Comments & Likes (Corrected) == */
/* ======================================================================== */

// Your other existing event handlers can go above this line...
// =============================================================

/* ======================================================================== */
/* == FINAL POPUP SCRIPT (DESKTOP MODAL + MOBILE SLIDE-UP PANEL) == */
// Your other existing event handlers can go above this line...
// =============================================================

/* ======================================================================== */
/* == FINAL POPUP SCRIPT (Desktop Popup + Mobile Redirect) == */
/* ======================================================================== */
/* == FINAL UNIVERSAL SLIDE-UP PANEL SCRIPT == */
/* ======================================================================== */
// Your other existing event handlers can go above this line...
// =============================================================

/* ======================================================================== */
/* == FINAL SCRIPT: Working Desktop Popup + Reliable Mobile Redirect == */
/* ======================================================================== */

/* ======================================================================== */
/* == FINAL SCRIPT: Working Desktop Popup + Reliable Mobile Redirect == */
/* ======================================================================== */

// Your other existing event handlers can go above this line...
// =============================================================

/* ======================================================================== */
/* == FINAL POPUP SCRIPT (Desktop Popup + Mobile Redirect) == */
/* ======================================================================== */

/* ======================================================================== */
/* == FINAL UNIVERSAL SLIDE-UP PANEL SCRIPT == */
/* ======================================================================== */

/* ======================================================================== */
/* == FINAL UNIVERSAL SLIDE-UP PANEL (Corrected and Unified) == */
/* ======================================================================== */

// Scroll Lock Helpers
function lockBodyScroll() {
    if ($('body').hasClass('panel-open')) return;
    const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
    $('body').attr('data-scroll-y', scrollY.toString()).css('top', `-${scrollY}px`);
    $('body').addClass('panel-open');
}
function unlockBodyScroll() {
    if (!$('body').hasClass('panel-open')) return;
    const scrollY = parseInt($('body').attr('data-scroll-y') || '0', 10);
    $('body').removeClass('panel-open').css('top', '').removeAttr('data-scroll-y');
    window.scrollTo(0, scrollY);
}

// Universal Panel Opener
function openSlidingPanel(ajaxData) {
    $('.universal-panel-container').remove(); // Clear any old panels
    var panelHTML = `
        <div class="universal-panel-container">
            <div class="universal-panel-overlay"></div>
            <div class="universal-panel-content">
                <div class="panel-header">
                    <div class="panel-close-btn">
                        <svg viewBox="0 0 24 24">
  <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"></path>
</svg>
                    </div>
                    <div class="panel-title-text"></div>
                </div>
                <div class="panel-body">
                    <div class="panel-loader"><div class="dot-pulse"></div></div>
                </div>
            </div>
        </div>
    `;
    $('body').append(panelHTML);
    lockBodyScroll();
    setTimeout(function() {
        $('.universal-panel-container').addClass('is-visible');
    }, 50);

    // Fetch the actual content
    $.ajax({
        type: 'POST',
        url: siteurl + 'requests/request.php', // Correctly uses siteurl
        data: ajaxData,
        success: function(response) {
            var panel = $('.universal-panel-container');
            var panelBody = panel.find('.panel-body');
            var panelTitle = panel.find('.panel-title-text');
            
            panelBody.html(response);
            
            if (ajaxData.f === 'getPostForModal') {
                panelTitle.text('Post'); // Set title
                var commentsWrapper = panelBody.find('.i_post_comments_wrapper');
                if (commentsWrapper.length) {
                    setTimeout(function() {
                       commentsWrapper.scrollTop(commentsWrapper[0].scrollHeight);
                    }, 100);
                }
            } else if (ajaxData.f === 'getPostLikers'){
                panelTitle.text('Liked by'); // Set title
            }
        }
    });
}

// Event Triggers for Popups
$(document).on('click', '.open-post-modal, .show-likers', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var postID = $(this).data('id');
    if (!postID) return;
    var action = $(this).hasClass('show-likers') ? 'getPostLikers' : 'getPostForModal';
    openSlidingPanel({ f: action, post_id: postID });
});

// Universal Panel Closer
$(document).on('click', '.universal-panel-overlay, .panel-close-btn', function(e) {
    e.preventDefault();
    var panel = $('.universal-panel-container');
    panel.removeClass('is-visible');
    unlockBodyScroll();
    setTimeout(function() { panel.remove(); }, 300);
});

	
	
	
// This script adds a class to the body on single post pages for the sticky comment bar
$(document).ready(function(){
    if(window.location.href.indexOf("/post/") > -1) {
        $('body').addClass('single-post-page');
    }
});
})(jQuery);