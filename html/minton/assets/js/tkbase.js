/**
 * Init all application specific scripts here
 */

jQuery(function ($) {
  // Init page javascript functions
  tkbase.initSugar();
  tkbase.initDialogConfirm();
  tkbase.initTkInputLock();
  tkbase.initDataToggle();
  tkbase.initTinymce();
  tkbase.initTkFormTabs();
  tkbase.initDatepicker();
  tkbase.initPasswordToggle();

  app.initHtmxToasts();
});

let app = function () {
  "use strict";

  /**
   * remove focus on menu links
   */
  let initHtmxToasts = function () {
    // Enable HTMX logging in the console
    //htmx.logAll();
    // Trigger on finished request loads (ie: after a form submits)
    $(document).on('htmx:afterSettle', '.toastPanel', function () {
      $('.toast', this).toast('show');
    });
  };


  return {
    initHtmxToasts: initHtmxToasts
  }

}();