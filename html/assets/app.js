/**
 * Init all application specific scripts here
 */

// Put this into the app page templates
// jQuery(function ($) {
//
//   // Init page javascript functions
//   tkbase.initSugar();
//   tkbase.initDialogConfirm();
//   tkbase.initTkInputLock();
//   tkbase.initDataToggle();
//   tkbase.initTinymce();
//   tkbase.initCodemirror();
//
//   // Init app functionality
//   app.initHtmxToasts();
//
// });


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

  /**
   * Creates bootstrap 5 tabs around the \Tk\Form renderer groups (.tk-form-group) output
   */
  let initTkFormTabs = function () {
    if ($.fn.tktabs === undefined) {
      console.warn('jquery.tktabs.js is not installed.');
      return;
    }

    function init() {
      $(this).tktabs({});
    }

    $('form').on(EVENT_INIT, document, init).each(init);
  };

  /**
   * Setup the jquery datepicker UI
   */
  let initDatepicker = function () {
    if ($.fn.datepicker === undefined) {
      console.warn('jquery-ui.js is not installed.');
      return;
    }

    function init() {
      let defaults = { dateFormat: config.dateFormat.jqDatepicker };
      $('input.date').each(function () {
        let settings = $.extend({}, defaults, $(this).data());
        $(this).datepicker(settings);
      });
    }

    $('form').on(EVENT_INIT, document, init).each(init);
  };

  return {
    initHtmxToasts: initHtmxToasts,
    initTkFormTabs: initTkFormTabs,
    initDatepicker: initDatepicker
  }

}();