/**
 * Init all application specific scripts here
 */

jQuery(function ($) {
    // Init page javascript functions
    tkbase.initDialogConfirm();
    tkbase.initTkInputLock();
    tkbase.initDataToggle();
    tkbase.initTinymce();
    tkbase.initTkFormTabs();
    tkbase.initDatepicker();
    tkbase.initPasswordToggle();
    tkbase.initHtmxConfirmDialog();

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
                let select = $('select', this);
                let showMessage = select.data('message') !== 'off';
                let msg = $('textarea', this);
                let cb = $('[type="checkbox"]', this);

                select.data('cs-current-val', select.val());
                msg.hide();
                cb.prop('checked', false);

                select.on('change', function () {
                    if ($(this).val() === $(this).data('cs-current-val')) {
                        cb.prop('checked', false);
                        if (showMessage) {
                            msg.hide();
                        }
                    } else {
                        cb.prop('checked', true);
                        if (showMessage) {
                            msg.show();
                        }
                    }

                    if (showMessage) {
                        $(this).blur();
                        msg.focus();
                    }
                });
            });
        });
    }; // end initStatusSelect()

    let initTimeSelect = function () {
        tkRegisterInit(function () {
            $('.tk-minutes', this).each(function () {
                let field = $(this);

                $('.tk-hrs-opts a', this).on('click', function () {
                    $('input.hrs', field).val($(this).text());
                });
                $('.tk-mins-opts a', this).on('click', function () {
                    $('input.mins', field).val($(this).text());
                });
            });
        });
    }; // end initTimeSelect()


    return {
        initCheckSelect: initCheckSelect,
        initStatusSelect: initStatusSelect,
        initTimeSelect: initTimeSelect,
    }

}();