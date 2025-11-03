(function ($) {
  "use strict";

  const siteurl = window.manualCardData?.siteurl || "";
  const planID = window.manualCardData?.planID || "";
  const userID = window.manualCardData?.userID || "";

  $("body").on("click", ".payClose", function () {
    $(".i_moda_bg_in_form").removeClass("i_modal_display_in");
  });

  $("body").on("click", ".pay_subscription", function () {
    const preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
    const loaderHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
    $(".i_modal_in_in").append(loaderHTML);

    setTimeout(() => {
      const name = $("#cname").val();
      const email = $("#email").val();
      const cardNumber = $("#cardNumber").val();
      const expMonth = $("#expmonth").val();
      const expYear = $("#expyear").val();
      const cardCCV = $("#cvv").val();

      const data = `f=subscribeMeAut&u=${userID}&pl=${planID}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&card=${cardNumber}&exm=${expMonth}&exy=${expYear}&cccv=${cardCCV}`;

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

})(jQuery);