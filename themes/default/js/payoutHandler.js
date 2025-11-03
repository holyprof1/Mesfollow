// themes/<yourTheme>/js/payoutHandler.js
$(document).ready(function () {
  // ========== Handle choosing payout icon ==========
  $('#payout-form-view').on('click', '.payout_icon', function () {
    var method = $(this).data('method');
    var placeholder = $(this).data('placeholder') || '';

    // Save selected method
    $('#selected_method').val(method);

    // Visual state
    $('.payout_icon').removeClass('selected');
    $(this).addClass('selected');

    // Reset input areas
    $('#payout_details').val('');
    $('#payoutWarning').hide();

    // Toggle UI by method
    if (method === 'mtn' || method === 'orange' || method === 'wave') {
      // Wallet: show country code + textarea; hide bank fields
      $('#country_code_container').slideDown();
      $('#payout_details').attr('placeholder', 'Enter mobile number (without country code)').slideDown();
      $('#bank_fields_container').hide();
    } else if (method === 'bank') {
      // Bank: show bank fields; hide country code + textarea
      $('#country_code_container').hide();
      $('#payout_details').hide();
      $('#bank_fields_container').slideDown();
    } else {
      // Fallback
      $('#country_code_container').hide();
      $('#bank_fields_container').hide();
      $('#payout_details').attr('placeholder', placeholder).slideDown();
    }

    // Reveal container
    $('.payout_input_container').slideDown();
  });

  // ========== Edit button switches to form ==========
  $('#edit-payout-btn').on('click', function () {
    $('#payout-display-view').slideUp(function () {
      $('#payout-form-view').slideDown();
    });
  });

  // ========== Save ==========
  $('#payout-form-view').on('click', '.pyot_sNext', function () {
    var selectedMethod = $('#selected_method').val();
    var payoutDetails = $('#payout_details').val().trim();
    var selectedCode = $('#country_code').val() || '';

    $('#payoutWarning').hide();

    if (!selectedMethod) {
      $('#payoutWarning').text('Please select a payout method.').show();
      return false;
    }

    // Compose details by method
    if (selectedMethod === 'mtn' || selectedMethod === 'orange' || selectedMethod === 'wave') {
      // Wallet with country code
      if (!payoutDetails.length) {
        $('#payoutWarning').text('Please enter your mobile number.').show();
        return false;
      }
      payoutDetails = (selectedCode ? selectedCode + ' ' : '') + payoutDetails;

    } else if (selectedMethod === 'bank') {
      // Gather bank fields and build one line
      var b_fullname    = ($('#bank_fullname').val() || '').trim();
      var b_ben_addr    = ($('#bank_beneficiary_address').val() || '').trim();
      var b_bankname    = ($('#bank_bankname').val() || '').trim();
      var b_bankaddr    = ($('#bank_bankaddress').val() || '').trim();
      var b_account     = ($('#bank_account').val() || '').trim();
      var b_swift       = ($('#bank_swift').val() || '').trim();
      var b_currency    = ($('#bank_currency').val() || '').trim();

      // Minimal sanity check (don’t be too strict to avoid breaking UX)
      if (!b_bankname || !b_account) {
        $('#payoutWarning').text('Please provide at least Bank name and Account/IBAN.').show();
        return false;
      }

      // Build compact string (single field for DB)
      var parts = [];
      if (b_fullname) parts.push('Full name: ' + b_fullname);
      if (b_ben_addr) parts.push('Beneficiary address: ' + b_ben_addr);
      if (b_bankname) parts.push('Bank name: ' + b_bankname);
      if (b_bankaddr) parts.push('Bank address: ' + b_bankaddr);
      if (b_account)  parts.push('Account/IBAN: ' + b_account);
      if (b_swift)    parts.push('SWIFT/BIC: ' + b_swift);
      if (b_currency) parts.push('Currency: ' + b_currency);

      payoutDetails = parts.join(' | ');

      if (!payoutDetails.length) {
        $('#payoutWarning').text('Please fill the bank details.').show();
        return false;
      }
    } else {
      // Generic fallback (uses textarea)
      if (!payoutDetails.length) {
        $('#payoutWarning').text('Please fill the details.').show();
        return false;
      }
    }

    // Build payload (backend unchanged)
    var data =
      'f=updatePayoutSet' +
      '&method=' + encodeURIComponent(selectedMethod) +
      '&details=' + encodeURIComponent(payoutDetails);

    $.ajax({
      type: 'POST',
      url: (typeof siteurl !== 'undefined' ? siteurl : '/') + 'requests/request.php',
      data: data,
      cache: false,
      beforeSend: function () {
        $('.pyot_sNext').css('pointer-events', 'none');
      },
      success: function (response) {
        if (response == '200') {
          $('#payoutMethodForm').slideUp();
          $('.pyot_sNext').slideUp();
          $('.successNot').slideDown();

          setTimeout(function () {
            location.reload();
          }, 2000);
        } else {
          $('#payoutWarning').text('An error occurred. Please try again.').show();
          $('.pyot_sNext').css('pointer-events', 'auto');
        }
      },
      error: function () {
        $('#payoutWarning').text('Network error. Please try again.').show();
        $('.pyot_sNext').css('pointer-events', 'auto');
      }
    });
  });
});
