(function ($) {
  $(document).on('change', '.abp-booking-form [name="service_id"], .abp-booking-form [name="appointment_date"]', function () {
    var $form = $(this).closest('.abp-booking-form');
    var serviceId = $form.find('[name="service_id"]').val();
    var date = $form.find('[name="appointment_date"]').val();
    var $time = $form.find('[name="appointment_time"]');

    $time.empty();

    if (!serviceId || !date) {
      $time.append($('<option>').val('').text(ABP.i18n.select_time));
      return;
    }

    $time.prop('disabled', true);
    $time.append($('<option>').val('').text('...'));

    $.post(ABP.ajax_url, {
      action: 'abp_get_slots',
      nonce: ABP.nonce,
      service_id: serviceId,
      date: date
    }).done(function (resp) {
      $time.empty();
      if (!resp || !resp.success || !resp.data || !resp.data.slots || !resp.data.slots.length) {
        $time.append($('<option>').val('').text(ABP.i18n.no_slots));
        return;
      }
      var any = false;
      resp.data.slots.forEach(function (slot) {
        var opt = $('<option>').val(slot.time).text(slot.time);
        if (!slot.available) {
          opt.prop('disabled', true).text(slot.time + ' — full');
        } else {
          any = true;
        }
        $time.append(opt);
      });
      if (!any) {
        $time.prepend($('<option>').val('').text(ABP.i18n.no_slots));
      } else {
        $time.prepend($('<option>').val('').text(ABP.i18n.select_time));
      }
    }).fail(function () {
      $time.empty().append($('<option>').val('').text('Error'));
    }).always(function () {
      $time.prop('disabled', false);
    });
  });

  $(document).on('submit', '.abp-booking-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var $msg = $form.find('.abp-message');
    $msg.removeClass('abp-error abp-success').attr('hidden', true).text('');

    var payload = {
      action: 'abp_submit_booking',
      nonce: ABP.nonce,
      service_id: $form.find('[name="service_id"]').val(),
      date: $form.find('[name="appointment_date"]').val(),
      time: $form.find('[name="appointment_time"]').val(),
      customer_name: $form.find('[name="customer_name"]').val(),
      customer_email: $form.find('[name="customer_email"]').val(),
      customer_phone: $form.find('[name="customer_phone"]').val(),
      notes: $form.find('[name="notes"]').val()
    };

    $form.find('button[type="submit"]').prop('disabled', true);

    $.post(ABP.ajax_url, payload).done(function (resp) {
      if (resp && resp.success) {
        $msg.addClass('abp-success').text(resp.data && resp.data.message ? resp.data.message : ABP.i18n.booking_success).attr('hidden', false);
        $form.get(0).reset();
        $form.find('[name="appointment_time"]').empty().append($('<option>').val('').text('—'));
      } else {
        var err = resp && resp.data && resp.data.message ? resp.data.message : ABP.i18n.booking_error;
        $msg.addClass('abp-error').text(err).attr('hidden', false);
      }
    }).fail(function () {
      $msg.addClass('abp-error').text('Network error, please try again.').attr('hidden', false);
    }).always(function () {
      $form.find('button[type="submit"]').prop('disabled', false);
    });
  });
})(jQuery);