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
  // app.initHtmxToasts();
});

let app = function () {
  "use strict";

  // let initHtmxToasts = function () {
  //   // Enable HTMX logging in the console
  //   //htmx.logAll();
  //   // Trigger on finished request loads (ie: after a form submits)
  //   $(document).on('htmx:afterSettle', '.toastPanel', function () {
  //     $('.toast', this).toast('show');
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
                console.log('Testing');
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


  return {
    // initHtmxToasts: initHtmxToasts
    initNotifications: initNotifications
  }

}();