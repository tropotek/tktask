/**
 * Init all application specific scripts here
 *
 *
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
   * remove focus on tab links
   */
  let initTabBlur = function () {
    $(document).on('click', '.nav-tabs .nav-link', function () {
      $(this).blur();
    });
  };

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
    // if ($.fn.tktabs === undefined) {
    //   console.warn('jquery.tktabs.js is not installed.');
    //   return;
    // }

    function init() {
      $(this).tktabs({});
    }

    $('form').on(EVENT_INIT, document, init).each(init);
  };

  /**
   * Create a bootstrap 3 panel around a div. Update the template to add your own panel
   * Div Eg:
   *   <div class="tk-panel" data-panel-title="Panel Title" data-panel-icon="fa fa-building-o"></div>
   * @todo Update/Remove this if not needed
   */
  let initTkPanel = function () {

    if (config.tkPanel === undefined) {
      config.tkPanel = {};
    }
    if (config.tkPanel.template === undefined) {
      config.tkPanel.template =
        '<div class="panel panel-default">\n' +
        '  <div class="panel-heading"><i class="tp-icon"></i> <span class="tp-title"></span></div>\n' +
        '  <div class="tp-body panel-body"></div>\n' +
        '</div>';
    }

    $('.tk-panel').each(function () {
      let element = $(this);
      element.hide();
      let defaults = {
        panelTemplate: config.tkPanel.template
      };
      let settings = $.extend({}, defaults, element.data());
      if (settings.panelTitle === undefined && $('.page-header').length) {
        if ($('.page-header .page-title').length) {
          settings.panelTitle = $('.page-header .page-title').text();
        } else {
          settings.panelTitle = $('.page-header').text();
        }
      }
      let tpl = $(settings.panelTemplate);
      tpl.addClass(element.attr('class'));
      element.attr('class', 'tk-panel-org');

      tpl.hide();
      if (settings.panelIcon !== undefined) {
        tpl.find('.tp-icon').addClass(settings.panelIcon);
      }
      if (settings.panelTitle !== undefined) {
        tpl.find('.tp-title').text(settings.panelTitle);
      }
      if (element.find('.tk-panel-title-right')) {
        element.find('.tk-panel-title-right').addClass('pull-right float-right');
        tpl.find('.tp-title').parent().append(element.find('.tk-panel-title-right'));
        //tpl.find('.tp-title').parent().parent().append(element.find('.tk-panel-title-right'));
      }
      element.before(tpl);
      element.detach();
      tpl.find('.tp-body').append(element);
      element.show();
      tpl.show();

    });
  };

  return {
    initTabBlur: initTabBlur,
    initHtmxToasts: initHtmxToasts,
    initTkFormTabs: initTkFormTabs,
    initTkPanel: initTkPanel,
  }

}();