

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

  // make instance
  const mceElf = new tinymceElfinder({
    // connector URL (Use elFinder Demo site's connector for this demo)
    url: config.vendorOrgUrl + '/tk-base/assets/js/elfinder/connector.minimal.php',
    // upload target folder hash for this tinyMCE
    uploadTargetHash: 'l1_dGVzdA', // l3 MCE_Imgs on elFinder Demo site for this demo
    // elFinder dialog node id
    nodeId: 'elfinder'
  });

  tinymce.init({
    selector: '.mce-min'
  });

function myCustomURLConverter(url, node, on_save) {
  // Do some custom URL conversion
  //url = url.substring(3);
  let parts = url.split(config.baseUrl);
  if (parts.length > 1) {
    url = config.baseUrl + parts[1];
  }
  console.log(url);
  console.log(on_save);
  // Return new URL
  return url;
}



// See this article for how to create plugins in custom paths and see if it works
// Custom plugins: https://stackoverflow.com/questions/21779730/custom-plugin-in-custom-directory-for-tinymce-jquery-plugin

  tinymce.init({
    selector: '.mce',
    height: 500,
    //theme : "advanced",
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'image', 'media', 'charmap', 'preview',
      'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
      'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',

    toolbar1:
      'bold italic backcolor | blocks | alignleft aligncenter ' +
      'alignright alignjustify | bullist numlist outdent indent',
    toolbar2:
      'link image media | removeformat code | help',
    // toolbar: 'undo redo | blocks | ' +
    //   'bold italic backcolor | alignleft aligncenter ' +
    //   'alignright alignjustify | bullist numlist outdent indent | ' +
    //   'removeformat code | link image media | help',

    // Optimisations
    //button_tile_map: true,
    //entity_encoding: 'raw',
    //verify_html: false,


    urlconverter_callback : function (url, node, on_save) {
      let parts = url.split(config.baseUrl);
      if (parts.length > 1) {
        url = config.baseUrl + parts[1];
      }
      return url;
    },

    //file_picker_callback : _elFinderPickerCallback,
    file_picker_callback : mceElf.browser,
    images_upload_handler: mceElf.uploadHandler,
    imagetools_cors_hosts: ['hypweb.net'] // set CORS for this demo
  });





});


