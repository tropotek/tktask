<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Exception;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Htmx extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Htmx Examples');
    }

    public function doDefault(Request $request)
    {



        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $css = <<<CSS
.tk-loading {
  display:none;
}
.htmx-request.tk-loading,
.htmx-request .tk-loading {
    display: block;
}

CSS;
        $template->appendCss($css);

        $js = <<<JS
// htmx.logger = function(elt, event, data) {
//     if(console) {
//         console.log(event, elt, data);
//     }
// }


jQuery(function($) {

    // htmx.logAll();
    // htmx.monitorEvents(htmx.find('#users'));

});
JS;
        $template->appendJs($js);

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
    <h3 var="title">Welcome Home</h3>
    <p var="content"></p>

    <h4>Search Example</h4>
    <div class="row mb-3">
      <div class="col-4">
        <input type="text" class="form-control" placeholder="Search..."
          name="q"
          hx-post="api/htmx/test"
          hx-trigger="keyup changed delay:500ms, search"
          hx-target="#search-results"
          hx-indicator=".search-loader"
        />
      </div>
      <div class="col-1">
        <span class="spinner-border tk-loading search-loader" role="status">
          <span class="visually-hidden">Loading...</span>
        </span>
      </div>
    </div>
    <div id="search-results"></div>
    <p>&nbsp;</p>

    <h4>Select Example</h4>
    <div class="mb-3">
      <label>User Type</label>
      <select class="form-control" name="type"
        hx-get="api/htmx/users"
        hx-target="#users"
        hx-indicator=".select-loader"
        hx-trigger="change, load"
      >
        <option value="admin">Admin</option>
        <option value="member">Member</option>
      </select>
    </div>
    <div class="mb-3">
      <label>Users</label>
      <select class="form-control" id="users" name="userId"></select>
    </div>

    <span class="spinner-border tk-loading select-loader" role="status">
      <span class="visually-hidden">Loading...</span>
    </span>

    <p>&nbsp;</p>
    <h4>Tab Example</h4>
    <div id="tabs"
        hx-get="api/htmx/tabs?tab=0"
        hx-trigger="load delay:100ms"
        hx-target="#tabs"
        hx-swap="innerHTML"
    ></div>


    <p>&nbsp;</p>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


