/* themes/<your-theme>/js/paymentMethodHandler.js */
(function ($) {
  "use strict";

  // --- tiny loader UI (unchanged look) ---
  const preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
  const loaderWrapper = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
  const lockUI = (id) => { $(`#${id}`).append(loaderWrapper); $(".payment_method_box").css("pointer-events", "none"); };
  const unlockUI = () => { $(".payment_method_box").css("pointer-events", "auto"); $(".loaderWrapper").remove(); };

  $(document).ready(function () {
    // Open card form for Iyzico / Authorize.net
    $("body").on("click", ".paywith", function () {
      const type = $(this).data("type");
      if (type === "iyzico" || type === "authorize-net") {
        $(".point_purchase").attr("data-type", type);
      }
      setTimeout(() => $(".i_moda_bg_in_form").addClass("i_modal_display_in"), 200);
    });

    // Close card modal
    $("body").on("click", ".payClose", function () {
      $(".i_moda_bg_in_form").removeClass("i_modal_display_in");
    });

    // --- Main handler for tiles/buttons with class .payMethod ---
    $("body").on("click", ".payMethod", function (e) {
      e.preventDefault();

      const payWidth    = $(this).data("type");    // gateway key (paypal, paystack, stripe, ...)
      const planID      = window.planID;           // credit plan id or product id (your backend expects it as creditPlan)
      const type        = "process";               // tells request.php which branch to use
      const configData  = window.configData || {};
      const gws         = (configData.payments && configData.payments.gateway_configuration) || {};
      const configItem  = gws[payWidth] || {};
      const userDetails = Object.assign({}, window.userData || {}); // shallow copy of your prepared payload

      // UI lock for the specific gateway tile
      lockUI(payWidth);

      // helper: POST to request.php and return jqXHR
      const postRequest = (data) => $.ajax({
        type: "POST",
        url: window.siteurl + "requests/request.php",
        dataType: "JSON",
        data: data
      });

      switch (payWidth) {
        // ---- Gateways that POST then redirect / render immediately ----
        case "paypal":
        case "iyzico":
        case "authorize-net":
        case "bitpay":
        case "mercadopago": {
          const requestData =
            `f=${type}&paymentOption=${payWidth}&creditPlan=${encodeURIComponent(planID)}&` +
            ($("#paymentFrm").length ? $("#paymentFrm").serialize() + "&" : "") +
            $.param(userDetails);

          postRequest(requestData)
            .done(function (response) {
              // keep UI responsive
              unlockUI();

              if (response && response.validationMessage) {
                // optionally show validation messages
                // $.each(response.validationMessage, (_, msg) => { /* toast */ });
              }

              if (payWidth === "paypal") {
                $(".lw-show-till-loading").show();
                window.location.href = response.paypalUrl;
              } else if (payWidth === "bitpay") {
                if (response.status === "success") window.location.href = response.invoiceUrl;
              } else if (payWidth === "iyzico") {
                if (response.status === "success") $("body").html(response.htmlContent);
              } else if (payWidth === "authorize-net") {
                if (response.status === "success") {
                  $("body").html(
                    `<form action='${window.authorizeNetCallbackUrl}' method='post'>
                       <input type='hidden' name='response' value='${JSON.stringify(response)}'>
                       <input type='hidden' name='paymentOption' value='authorize-net'>
                     </form>`
                  );
                  $("body form").trigger("submit");
                }
              } else if (payWidth === "mercadopago") {
                if (response.status === "success") {
                  window.location.href = response.redirect_url;
                } else {
                  $(".lw-show-till-loading").hide();
                }
              }
            })
            .fail(function () {
              unlockUI();
              window.location.href = window.paymentPagePath; // safe fallback
            });
          break;
        }

        // ---- Stripe (unchanged) ----
        case "stripe": {
          unlockUI();

          const testMode = (configItem.testMode === true || configItem.testMode === "true");
          const pubKey   = testMode ? window.stripeTestKey : window.stripeLiveKey;
          const stripe   = Stripe(pubKey);

          userDetails.paymentOption = "stripe";
          userDetails.f            = type;
          userDetails.creditPlan   = planID;

          postRequest(userDetails)
            .done(function (response) {
              stripe.redirectToCheckout({ sessionId: response.id }).then(function () {});
            })
            .fail(function () {
              window.location.href = window.paymentPagePath;
            });
          break;
        }

// ---- ✅ Paystack (fixed & tightened) ----
// ---- ✅ Paystack (final) ----
case "paystack": {
  unlockUI();

  const amountBase = (userDetails.amounts && configItem?.currency)
    ? Number(userDetails.amounts[configItem.currency] || 0)
    : Number(userDetails.amount || 0);

  const kobo    = Math.max(0, Math.round(amountBase * 100));
  const testMode= (configItem.testMode === true || configItem.testMode === "true");
  const pubKey  = testMode ? configItem.paystackTestingPublicKey
                           : configItem.paystackLivePublicKey;

  userDetails.paymentOption = "paystack";
  userDetails.f            = "process";
  userDetails.creditPlan   = planID;

  if (!userDetails.order_id) {
    userDetails.order_id =
      "ORDS" + Math.random().toString(16).slice(2) + Date.now().toString(16);
  }

  // this MUST equal i_user_payments.order_key
  userDetails.paystackReferenceId = userDetails.order_id;

  $.ajax({
    type: "POST",
    url: window.siteurl + "requests/request.php",
    dataType: "json",
    data: $.param(userDetails)
  }).always(function () {
    const handler = PaystackPop.setup({
      key: pubKey,
      email: userDetails.payer_email,
      amount: kobo,
      currency: configItem.currency,

      // 🔥 The important part:
      ref: userDetails.paystackReferenceId,       // <- use "ref"
      // keep both for safety; Paystack will use "ref"
      reference: userDetails.paystackReferenceId,

      callback: function (psResponse) {
		  // new: go straight to the public Paystack callback we built
window.location.href =
  window.siteurl + "payment-response.php?paymentOption=paystack&reference=" +
  encodeURIComponent(psResponse.reference || psResponse.trxref || userDetails.paystackReferenceId);


		
      },
      onClose: function () {
        window.location.href = window.paymentPagePath;
      }
    });
    handler.openIframe();
  });

  break;
}


			  
			  
        // ---- Razorpay (unchanged aside from safe fallbacks) ----
        case "razorpay": {
          unlockUI();

          const amountBase   = userDetails.amounts && configItem.currency ? userDetails.amounts[configItem.currency] : 0;
          const razorpayAmt  = Math.round(Number(amountBase || 0) * 100);
          const testMode     = (configItem.testMode === true || configItem.testMode === "true");
          const razorpayKey  = testMode ? configItem.razorpayTestingkeyId : configItem.razorpayLivekeyId;

          userDetails.paymentOption = "razorpay";
          userDetails.f            = type;
          userDetails.creditPlan   = planID;

          const options = {
            key: razorpayKey,
            amount: razorpayAmt,
            currency: configItem.currency,
            name: configItem.merchantname,
            handler: function (rzpRes) {
              const razorpayData = {
                razorpayPaymentId: rzpRes.razorpay_payment_id,
                razorpayAmount: window.btoa(razorpayAmt)
              };

              const requestData =
                ($("#lwPaymentForm").length ? $("#lwPaymentForm").serialize() + "&" : "") +
                $.param(userDetails) + "&" + $.param(razorpayData);

              postRequest(requestData)
                .done(function (response) {
                  if (response.status === "captured") {
                    const callbackUrl = configItem.callbackUrl + '?orderId=' + encodeURIComponent(userDetails.order_id) + '&paymentOption=razorpay';
                    $("body").html(
                      `<form action='${callbackUrl}' method='post'>
                         <input type='hidden' name='response' value='${JSON.stringify(response)}'>
                         <input type='hidden' name='paymentOption' value='razorpay'>
                       </form>`
                    );
                    $("body form").trigger("submit");
                  } else {
                    window.location.href = window.paymentPagePath;
                  }
                })
                .fail(function () {
                  window.location.href = window.paymentPagePath;
                });
            },
            prefill: {
              name: userDetails.payer_name,
              email: userDetails.payer_email
            },
            theme: { color: configItem.themeColor },
            modal: {
              ondismiss: function () { window.location.href = window.paymentPagePath; }
            }
          };

          try {
            const rzp1 = new Razorpay(options);
            rzp1.open();
          } catch (e) {
            window.location.href = window.paymentPagePath;
          }
          break;
        }

        default:
          unlockUI();
          break;
      }
    });

    // ---- CoinPayment (crypto) ----
    $("body").on("click", ".paywithCrip", function () {
      const planID  = window.planID;
      const payWid  = $(this).data("type");
      const data    = `f=cop&p=${encodeURIComponent(planID)}`;

      $.ajax({
        type: "POST",
        url: window.siteurl + "requests/request.php",
        dataType: "JSON",
        data: data,
        beforeSend: function () { lockUI(payWid); },
        success: function (response) {
          unlockUI();
          if (response.redirect && response.status === '200') {
            window.location.href = response.redirect;
          } else {
            window.location.href = window.paymentPagePath;
          }
        },
        error: function () {
          unlockUI();
          window.location.href = window.paymentPagePath;
        }
      });
    });

  });
})(jQuery);
