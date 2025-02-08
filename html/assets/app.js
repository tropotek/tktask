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

  app.initNotifications();
  app.initCheckSelect();
  app.initStatusSelect();
  app.initTimeSelect();

});

let app = function () {
  "use strict";

  /**
   * @see https://bootstrap-datepicker.readthedocs.io/en/latest/options.html#format
   */
  // let initDatepicker = function () {
  //   if ($.fn.datepicker === undefined) return;
  //
  //   tkRegisterInit(function () {
  //     $('input.datepicker', this).datepicker({
  //       format: tkConfig.dateFormat.bsDatepicker ?? 'dd/mm/yyyy'
  //     });
  //   });
  // };

  /**
   * Enable browser notifications using the systems Notify object
   * @see \App\Db\Notify, \App\Ui\Notify, \Api\Notify
   */
  let initNotifications = function () {
    if (typeof Notification === 'undefined') {
      console.warn('Browser does not support Web Notifications');
      return;
    }

    $('.notify-toggle').on('click', function(e) {
      if (Notification.permission !== 'granted') {
        let promise = Notification.requestPermission();
        promise.then(function() {
          if (Notification.permission === 'granted') {
            getNotification();
          }
        });
      }
    });

    if (Notification.permission !== 'granted') {
      return;
    }

    if (tkConfig.isAuth) {
      getNotification();
      setInterval(function () {
        getNotification();
      }, 50000);
    }

    function getNotification() {
      if (Notification.permission !== 'granted') return;
      $.post(tkConfig.baseUrl + '/api/notify/getNotifications', {})
      .done(function(data) {
        let notices = data.notices;
        if (notices === undefined || notices.length) {
          for(let note of notices) {
            let notification = new Notification(
              note.title,
              {
                icon: note.icon,
                body: note.message
              }
            );
            if (note.url !== '') {
              notification.onclick = function () {
                window.open(note.url);
                notification.close();
              };
            }
            setTimeout(function(){
              notification.close();
            }, 5000);
          }
        }
      })
      .fail(function(data) {
        console.warn(arguments);
      });
    }
  }; // end initNotifications()

  /**
   * Form checkbox dropdown select plugin
   */
  let initCheckSelect = function () {
    if ($.fn.tkCheckSelect === undefined) return;

    tkRegisterInit(function () {
      $('select.tk-checkselect', this).tkCheckSelect({
        search: true,
        selectAll: true,
      });
    });
  }; // end initCheckSelect()

  /**
   * Init \App\Form\Field\StatusSelect fields
   */
  let initStatusSelect = function () {
    tkRegisterInit(function () {
      $('.tk-status-select', this).each(function () {
        var select = $('select', this);
        var msg = $('textarea', this);
        var cb = $('[type="checkbox"]', this);

        select.data('cs-current-val', select.val());
        msg.hide();
        cb.prop('checked', false);

        select.on('change', function () {
          if ($(this).val() === $(this).data('cs-current-val')) {
            cb.prop('checked', false);
            if (select.data('messageText') !== 'off')
              msg.hide();
          } else {
            cb.prop('checked', true);
            if (select.data('messageText') !== 'off')
              msg.show();
          }

          if (select.data('messageText') !== 'off') {
            $(this).blur();
            msg.focus();
          }
        });
      });
    });
  }; // end initStatusSelect()

  let initTimeSelect = function () {
    tkRegisterInit(function () {
        $('.tk-minutes', this).each(function() {
            let field = $(this);

            $('.tk-hrs-opts a', this).on('click', function() {
                $('input.hrs', field).val($(this).text());
            });
            $('.tk-mins-opts a', this).on('click', function() {
                $('input.mins', field).val($(this).text());
            });
        });
    });
  }; // end initTimeSelect()


  return {
    initNotifications: initNotifications,
    initCheckSelect: initCheckSelect,
    initStatusSelect: initStatusSelect,
    initTimeSelect: initTimeSelect,
  }

}();