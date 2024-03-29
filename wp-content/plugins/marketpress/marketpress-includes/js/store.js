/**** MarketPress Checkout JS *********/
jQuery(document).ready(function($) {
  
  //coupon codes
  $('#coupon-link').click(function() {
    $('#coupon-link').hide();
    $('#coupon-code').show();
    $('#coupon_code').focus();
    return false;
  });
  
  //payment method choice
  $('input.mp_choose_gateway').change(function() {
    var gid = $('input.mp_choose_gateway:checked').val();
    $('div.mp_gateway_form').hide();
    $('div#' + gid).show();
  });

  //province field choice
  $('#mp_country').change(function() {
    $("#mp_province_field").html('<img src="'+MP_Ajax.imgUrl+'" alt="Loading..." />');
    var country = $(this).val();
    $.post(MP_Ajax.ajaxUrl, {action: 'mp-province-field', country: country}, function(data) {
      $("#mp_province_field").html(data);
      //remap listener
      $('#mp_state').change(function() {
        if ($('#mp_city').val() && $('#mp_state').val() && $('#mp_zip').val()) mp_refresh_shipping();
      });
    });
    mp_refresh_shipping();
  });
  
  //shipping field choice
  $('#mp-shipping-select').change(function() {mp_refresh_shipping();});
  
  //For fedex residential delivery
  $('#mp_residential').change(function() {mp_refresh_shipping();});
  
  //refresh on blur if necessary 3 fields are set
  $('#mp_shipping_form .mp_shipping_field').change(function() {
    if ($('#mp_city').val() && $('#mp_state').val() && $('#mp_zip').val()) mp_refresh_shipping();
  });
  
  function mp_refresh_shipping() {
    $("#mp_shipping_submit").attr('disabled', 'disabled');
    $("#mp-shipping-select-holder").html('<img src="'+MP_Ajax.imgUrl+'" alt="Loading..." />');
    var serializedForm = $('form#mp_shipping_form').serialize();
    $.post(MP_Ajax.ajaxUrl, serializedForm, function(data) {
      $("#mp-shipping-select-holder").html(data);
    });
    $("#mp_shipping_submit").removeAttr('disabled');
  }
});