(function ($) {
  "use strict";

  const MAX_FEE = 1000; // <-- NEW: hard cap

  // simple thousands formatter
  function decimalFormat(nStr) {
    var $decimalDot = ".";
    var $decimalComma = ",";
    nStr += "";
    var x = nStr.split(".");
    var x1 = x[0];
    var x2 = x.length > 1 ? $decimalDot + x[1] : "";
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
      x1 = x1.replace(rgx, "$1" + $decimalComma + "$2");
    }
    return x1 + x2;
  }

  $(document).ready(function () {
    // if the page provided subscriptionData, use it to show earnings
    var weeklyMin = (window.subscriptionData && window.subscriptionData.weeklyMin) || 0;
    var monthlyMin = (window.subscriptionData && window.subscriptionData.monthlyMin) || 0;
    var yearlyMin  = (window.subscriptionData && window.subscriptionData.yearlyMin)  || 0;
    var onePointEqual = (window.subscriptionData && window.subscriptionData.onePointEqual) || 1;
    var adminFee = (window.subscriptionData && window.subscriptionData.adminFee) || 0; // already 0..1
    var $decimal = 2;

    // helper: clamp to MAX_FEE, return numeric or NaN if invalid
    function clampFee(v){
      var n = parseFloat(v);
      if (isNaN(n)) return NaN;
      if (n > MAX_FEE) n = MAX_FEE;
      if (n < 0) n = 0;
      return n;
    }

    $("body").on("keyup", ".paval", function () {
      var $input = $(this);

      // NEW: live clamp while typing
      var typed = $input.val();
      var val = parseFloat(typed);
      if (!isNaN(val) && val > MAX_FEE) {
        val = MAX_FEE;
        $input.val(val);
      }

      var ID = $input.attr("id");
      $(".i_t_warning, .i_t_warning_earning").hide();

      if (!isNaN(val)) {
        if (ID === "spweek" && val < weeklyMin) {
          $("#waweekly").show();
        } else if (ID === "spmonth" && val < monthlyMin) {
          $("#mamonthly").show();
        } else if (ID === "spyear" && val < yearlyMin) {
          $("#yayearly").show();
        } else {
          var earning = (val * onePointEqual) - ((val * onePointEqual) * adminFee);
          var formatted = decimalFormat(earning.toFixed($decimal));
          if (ID === "spweek") {
            $(".weekly_earning").show();
            $("#weekly_earning").html(formatted);
          } else if (ID === "spmonth") {
            $(".mamonthly_earning").show();
            $("#mamonthly_earning").html(formatted);
          } else if (ID === "spyear") {
            $(".yayearly_earning").show();
            $("#yayearly_earning").html(formatted);
          }
        }
      }
    });

    // CLICK: save
    $(document).on("click", ".c_pNext", function () {
      // NEW: clamp before sending
      function valOrEmpty(id){
        var raw = $(id).val() || "";
        var n = clampFee(raw);
        if (isNaN(n)) return "";     // keep your empty check logic
        return n.toString();
      }

      const weekly  = valOrEmpty("#spweek");
      const monthly = valOrEmpty("#spmonth");
      const yearly  = valOrEmpty("#spyear");

      const weeklyStatus  = $('input[name="weekly"]').is(':checked')  ? 1 : 0;
      const monthlyStatus = $('input[name="monthly"]').is(':checked') ? 1 : 0;
      const yearlyStatus  = $('input[name="yearly"]').is(':checked')  ? 1 : 0;

      if (weeklyStatus  && weekly.length  === 0) return $("#wweekly").show();
      if (monthlyStatus && monthly.length === 0) return $("#wmonthly").show();
      if (yearlyStatus  && yearly.length  === 0) return $("#wyearly").show();

      $.ajax({
        type: "POST",
        url: siteurl + 'requests/request.php',
        dataType: "json",
        data: {
          f: "updateSubscriptionPayments",
          wSubWeekAmount: weekly,
          mSubMonthAmount: monthly,
          mSubYearAmount: yearly,
          wStatus: weeklyStatus,
          mStatus: monthlyStatus,
          yStatus: yearlyStatus
        },
        beforeSend: function () {
          $(".i_nex_btn").css("pointer-events", "none");
          $("#wweekly,#wmonthly,#wyearly,.weekly_success,.monthly_success,.yearly_success").hide();
        },
        success: function (res) {
          let ok = true;
          if (res.weekly  === "404") { $("#wweekly").show();  ok = false; }
          if (res.monthly === "404") { $("#wmonthly").show(); ok = false; }
          if (res.yearly  === "404") { $("#wyearly").show();  ok = false; }
          if (ok) location.reload(); else $(".i_nex_btn").css("pointer-events","auto");
        }
      });
    });

  });
})(jQuery);
