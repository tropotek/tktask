

jQuery(function ($) {

  //htmx.logAll();

  if ($.fn.bsConfirm === undefined) {
    $('[data-confirm]').on('click', document, function () {
      return confirm($('<p>' + $(this).data('confirm') + '</p>').text());
    });
  } else {
    $('[data-confirm]').bsConfirm();
  }

  // Trigger on finished request loads (ie: after a form submits)
  $(document).on('htmx:afterSettle', '.toastPanel', function () {
    $('.toast', this).toast('show');
  });

});


