
jQuery(function ($) {



  if ($.fn.bsConfirm === undefined) {
    $('[data-confirm]').on('click', document, function () {
      return confirm($('<p>' + $(this).data('confirm') + '</p>').text());
    });
  } else {
    $('[data-confirm]').bsConfirm();
  }




});


