(function($){
  "use strict";

  $(document).ready(function(){

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

    // live earning calc if your inputs carry data-* (optional)
    $("body").on("keyup", ".aval", function(){
      var $input = $(this);
      var val  = parseFloat($input.val());
      var min  = parseFloat($input.data("min"));
      var rate = parseFloat($input.data("rate"));
      var fee  = parseFloat($input.data("fee"));
      var id   = $input.attr("id");

      $(".i_t_warning, .i_t_warning_earning").hide();

      if (!isNaN(val)) {
        if (val >= (isNaN(min)?0:min)) {
          var calc = val * (isNaN(rate)?1:rate);
          var net  = calc - (calc * (isNaN(fee)?0:fee));
          var out  = decimalFormat(net.toFixed(2));
          if (id === "spweek")   { $(".weekly_earning").show();   $("#weekly_earning").html(out); }
          if (id === "spmonth")  { $(".mamonthly_earning").show();$("#mamonthly_earning").html(out); }
          if (id === "spyear")   { $(".yayearly_earning").show(); $("#yayearly_earning").html(out); }
        } else {
          if (id === "spweek")   { $("#waweekly").show(); }
          if (id === "spmonth")  { $("#mamonthly").show(); }
          if (id === "spyear")   { $("#yayearly").show(); }
        }
      } else {
        $(".i_t_warning_earning").hide();
      }
    });

   // CLICK: save (points page) -> same endpoint, JSON response
$("body").on("click", ".c_Next", function(){
  const weekly  = $("#spweek").val()  || "";
  const monthly = $("#spmonth").val() || "";
  const yearly  = $("#spyear").val()  || "";

  const weeklyStatus  = $('input[name="weekly"]').is(":checked")  ? 1 : 0;
  const monthlyStatus = $('input[name="monthly"]').is(":checked") ? 1 : 0;
  const yearlyStatus  = $('input[name="yearly"]').is(":checked")  ? 1 : 0;

  if (weeklyStatus  && weekly.length  === 0) { $("#wweekly").show();  return; }
  if (monthlyStatus && monthly.length === 0) { $("#wmonthly").show(); return; }
  if (yearlyStatus  && yearly.length  === 0) { $("#wyearly").show();  return; }

  $.ajax({
    type: "POST",
    url: siteurl + "requests/request.php",
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
    beforeSend: function(){ $(".i_nex_btn").css("pointer-events","none"); },
    success: function(res){
      // show errors if any, else reload
      let ok = true;
      if (res.weekly  === "404") { $("#wweekly").show();  ok = false; }
      if (res.monthly === "404") { $("#wmonthly").show(); ok = false; }
      if (res.yearly  === "404") { $("#wyearly").show();  ok = false; }
      if (ok) location.reload();
      else $(".i_nex_btn").css("pointer-events","auto");
    }
  });
});

  });

})(jQuery);