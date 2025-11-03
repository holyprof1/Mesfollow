(function ($) {
  "use strict";

  const stripePublicKey = window.payWithCardData?.stripePublicKey || "";
  const siteurl = window.payWithCardData?.siteurl || "";
  const planID = window.payWithCardData?.planID || "";
  const userID = window.payWithCardData?.userID || "";
  const theme = window.payWithCardData?.lightDark || "light";

  const stripe = Stripe(stripePublicKey);
  const elements = stripe.elements();

  const style = theme === "dark" ? {
    base: {
      color: "#ffffff"
    }
  } : {};

  const cardElement = elements.create("cardNumber", { style });
  const expElement = elements.create("cardExpiry", { style });
  const cvcElement = elements.create("cardCvc", { style });

  cardElement.mount("#card_number");
  expElement.mount("#card_expiry");
  cvcElement.mount("#card_cvc");

  const resultContainer = document.getElementById("paymentResponse");

  cardElement.on("change", function (event) {
    resultContainer.innerHTML = event.error ? '<p>' + event.error.message + '</p>' : "";
  });

  const form = document.getElementById("paymentFrm");

  function createToken() {
    stripe.createToken(cardElement).then(function (result) {
      if (result.error) {
        resultContainer.innerHTML = '<p>' + result.error.message + '</p>';
      } else {
        stripeTokenHandler(result.token);
      }
    });
  }

  function stripeTokenHandler(token) {
    $("#stripeTokenID").remove();
    const hiddenInput = document.createElement("input");
    hiddenInput.setAttribute("type", "hidden");
    hiddenInput.setAttribute("name", "stripeToken");
    hiddenInput.setAttribute("id", "stripeTokenID");
    hiddenInput.setAttribute("value", token.id);
    form.appendChild(hiddenInput);
  }

  $("body").on("click", ".pay_subscription", function () {
    const preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
    const loaderHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
    $(".i_modal_in_in").append(loaderHTML);

    createToken();

    setTimeout(() => {
      const name = $("#name").val();
      const email = $("#email").val();
      const token = $("#stripeTokenID").val();

      const data = `f=subscribeMe&u=${userID}&pl=${planID}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&t=${token}`;

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        success: function (response) {
          if (response === "200") {
            location.reload();
          } else {
            $("#paymentResponse").show().html(response);
            $(".loaderWrapper").remove();
          }
        }
      });
    }, 1200);
  });

  $("body").on("click", ".payClose", function () {
    $(".i_payment_pop_box").addClass("i_modal_in_in_out");
    setTimeout(() => {
      $(".i_subs_modal").remove();
      $("iframe").remove();
      $("strong").remove();
    }, 200);
  });

})(jQuery);