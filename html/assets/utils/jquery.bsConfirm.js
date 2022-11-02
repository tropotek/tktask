/**
 * Plugin: bsConfirm
 *
 * Description:
 *
 * To enable the plugin on your desired selector you can use the following script
 * that defaults to a standard javascript dialog if the plugin is not available.
 *
 * ```
 *   if ($.fn.bsConfirm === undefined) {
 *     $('[data-confirm]').on('click', document, function () {
 *       return confirm($('<p>' + $(this).data('confirm') + '</p>').text());
 *     });
 *   } else {
 *     $('[data-confirm]').bsConfirm();
 *   }
 * ```
 *
 * Now all elements containing the data-confirm attribute will have a confirm dialog on the click event.
 *
 * ```
 *   <a href="/home/page/action" title="Action Confirmation Title"
 *      data-confirm="Are you sure you want to complete this action?"
 *      data-ok="Yep" data-cancel="Nuh">Action</a>
 * ```
 *
 *
 * @author Tropotek <http://www.tropotek.com/>
 * @date 31/10/2022
 * @version 1.2
 */

;(function($) {
  let bsConfirm = function(element, options) {

    // plugin settings
    const defaults = {
      // BS3-BS4 modal template
//       template: /*html*/`
// <div class="modal fade bsConfirm-modal" tabindex="-1" role="dialog" aria-labelledby="bsConfirmModalLabel" aria-hidden="true">
//   <div class="modal-dialog" role="document">
//     <div class="modal-content">
//       <div class="modal-header">
//         <h5 class="modal-title" id="bsConfirmModalLabel"></h5>
//         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
//       </div>
//       <div class="modal-body"></div>
//       <div class="modal-footer">
//         <button class="btn btn-danger btn-cancel" data-dismiss="modal">Cancel</button>
//         <button class="btn btn-success btn-ok">OK</button>
//       </div>
//     </div>
//   </div>
// </div>`,
      // BS5+ modal template
      template: /*html*/`
<div class="modal fade bsConfirm-modal" tabindex="-1" role="dialog" aria-labelledby="bsConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bsConfirmModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <button class="btn btn-danger btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success btn-ok">OK</button>
      </div>
    </div>
  </div>
</div>`,
      ok: 'Ok',
      cancel: 'Cancel'
    };

	  // Plugin private params
    let plugin = this;
    plugin.settings = {};
    let el = $(element);

    // constructor method
    plugin.init = function() {
      plugin.settings = $.extend({}, defaults, el.data(), options);

      el.on('click', function (e) {
        $('.confirm-modal').remove();
        let modal = $(plugin.settings.template);
        $('.modal-title', modal).text(el.attr('title'));
        $('.modal-body', modal).html(el.data('confirm'));
        $('.btn-cancel', modal).html(plugin.settings.cancel);
        $('.btn-ok', modal).html(plugin.settings.ok);

        $('.btn-cancel', modal).on('click', function () {
          modal.modal('hide');
          return false;
        });
        $('.btn-ok', modal).on('click', function () {
          modal.modal('hide');
          document.location = el.attr('href');
          return true;
        });

        $('body').append(modal);
        modal.modal();
        modal.on('hidden.bs.modal', function (e) {
          modal.remove();
        })
        modal.modal('show');
        return false;
      });

    };  // END init()

    plugin.init();
  };

  // add the plugin to the jQuery.fn object
  $.fn.bsConfirm = function(options) {
    return this.each(function() {
      if (undefined === $(this).data('bsConfirm')) {
        let plugin = new bsConfirm(this, options);
        $(this).data('bsConfirm', plugin);
      }
    });
  }

})(jQuery);
