/**
 * @name tktabs
 * @version 1.0.0
 * @date 2023-05-23
 * @author Tropotek <http://www.tropotek.com/>
 * @license Copyright 2007 Tropotek
 *
 * Description:
 *   This should be executed on a Tk form to generate tabs from the
 *   `.tk-form-group` elements. (see \Tk\FormRenderer templates)
 *
 * ```javascript
 *   $(document).ready(function() {
 *     // attach the plugin to an element
 *     $('#element').tktabs({'foo': 'bar'});
 *
 *     // call a public method
 *     $('#element').data('tktabs').foo_public_method();
 *
 *     // get the value of a property
 *     $('#element').data('tktabs').settings.foo;
 *
 *   });
 * ```
 */

;(function($) {
  let tktabs = function(element, options) {
    let plugin = this;
    plugin.settings = {};
    let $element = $(element);

    // plugin settings
    let defaults = {
      navTabsTpl: `<ul class="nav nav-tabs mb-3" role="tablist" id="">`,
      tabItemTpl: `<li class="nav-item" role="presentation">
        <a class="nav-link" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#" id="" href="#" aria-controls="" aria-selected="false"></a>
      </li>`,
      tabGroup: '.tk-form-group'
    };

    // plugin vars
    let navTabs = null;

    // constructor method
    plugin.init = function() {
      plugin.settings = $.extend({}, defaults, $element.data(), options);

      if ($(plugin.settings.tabGroup, element).length < 2) return;

      navTabs = $(plugin.settings.navTabsTpl);
      $(plugin.settings.tabGroup, element).each(function (i) {
        let name = $(this).data('name');
        let id = $(this).attr('id');
        let li = $(plugin.settings.tabItemTpl);

        $('a', li).text(name);
        $('a', li).attr({
          'id': id + '-tab',
          'href': '#' + id,
          'data-bs-target': '#' + id,
          'aria-controls': id,
        });
        // Check for errors
        if ($('.has-error, .is-invalid', this).length) {
          li.addClass('has-error');
        }
        navTabs.append(li);
        $(this).addClass('tab-pane')
          .attr('tabindex', '0')
          .attr('role', 'tabpanel')
          .attr('aria-labelledby', id+'-tab');
      });

      $('a.nav-link', navTabs).on('click', function () {
        $(this).blur();
      });

      // append tabs
      $('.tk-form-fields', element).before(navTabs);
      // setup tab panes
      $('.tk-form-fields', element).addClass('tab-content');

      // show first tab or first error tab
      $('li:nth-child(1) a', navTabs).tab('show');   // shows first tab
      $('li.has-error a', navTabs).first().tab('show');   // shows first error tab

    };  // END init()

    // call the "constructor" method
    plugin.init();
  };

  // add the plugin to the jQuery.fn object
  $.fn.tktabs = function(options) {
    return this.each(function() {
      if (undefined === $(this).data('tktabs')) {
        let plugin = new tktabs(this, options);
        $(this).data('tktabs', plugin);
      }
    });
  }

})(jQuery);

