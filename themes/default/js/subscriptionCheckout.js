(function ($) {
  // CASH subscriptions only. Do not touch points here.
  $(document).on('click', '.bcmSubs', function (e) {
    e.preventDefault();
    var $b = $(this);
    var creatorId = $b.data('u');
    var planId    = $b.attr('id');
    var amount    = $b.data('amount');   // plain number like 2000.00
    var interval  = $b.data('interval'); // monthly|yearly

    // Hand-off to server to init Paystack (standard redirect).
    // This must point to a handler that returns authorization_url redirect.
    window.location.href =
      window.siteurl + 'requests/request.php'
      + '?f=paystackSubInit'
      + '&creator_id=' + encodeURIComponent(creatorId)
      + '&plan_id='    + encodeURIComponent(planId)
      + '&amount='     + encodeURIComponent(amount)
      + '&interval='   + encodeURIComponent(interval);
  });

  // DO NOT intercept points; let inora.js post type=subWithPoints by itself.
})(jQuery);
