(function ($) {
  'use strict';

  // Open profile tip modal
  $(document).on('click', '.i_btn_send_to_point.sendPoint', function (e) {
    e.preventDefault();
    const uid = $(this).data('u');
    if (!uid) return;
    $('.i_modal_bg_in').remove(); // remove any old modal
    $.post('/requests/request.php', { f: 'p_tips', tip_u: uid, tpid: '' })
      .done(function (html) { $('body').append(html); });
  });

  // Close
  $(document).on('click', '.i_modal_bg_in .shareClose', function () {
    $(this).closest('.i_modal_bg_in').remove();
  });
})(jQuery);