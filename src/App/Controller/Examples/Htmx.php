<?php
namespace App\Controller\Examples;

use Bs\ControllerAdmin;
use Dom\Template;

class Htmx extends ControllerAdmin
{


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Htmx Examples');


    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $btnRes = $this->forward([\App\Api\HtmxExamples::class, 'doButton'], null, null, null);
        $template->insertHtml('btn', $btnRes->getContent());


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

        $template->setAttr('upload', 'hx-vals', json_encode(['TestKey' => 'Test&Val', 'TestKey2' => 'TestVal2']));

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
    <div class="card-body" var="content">
        <p>Button Test</p>
        <p var="btn"></p>

        <p><button class="btn btn-sm btn-primary" hx-get="api/htmx/button" hx-target="this" hx-trigger="click" hx-swap="outerHTML" >Click Me!</button></p>
        <p>&nbsp;</p>

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
          <select class="form-select" name="type"
            hx-get="api/htmx/users"
            hx-target="#users-sel"
            hx-indicator=".select-loader"
            hx-trigger="change, load"
          >
            <option value="staff">Staff</option>
            <option value="member">Member</option>
          </select>
        </div>
        <div class="mb-3">
          <label>Users</label>
          <select class="form-select" id="users-sel" name="userId"></select>
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


        <h4>Upload Example</h4>
        <form
          id="upload"
          hx-encoding="multipart/form-data"
          hx-post="api/htmx/upload"
          hx-vals=""
          var="upload"
        >
          <div class="row">
              <div class="col-4">
                <input type="file" name="file" class="form-control" />
              </div>
              <div class="col-1">
                <button class="btn btn-outline-secondary">Upload</button>
              </div>
              <div class="col-4">
                  <div class="progress">
                    <div id="progress-b" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-label="Animated striped example" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0"></div>
                  </div>
              </div>
          </div>
          <p>&nbsp;</p>
          <p>&nbsp;</p>
        </form>
        <script>
            htmx.on("#upload", "htmx:xhr:progress", function(evt) {
              let val = evt.detail.loaded/evt.detail.total * 100;
              if (!evt.detail.loaded && !evt.detail.total) val = 100;
              htmx.find("#progress-b").setAttribute("aria-valuenow", val);
              htmx.find("#progress-b").setAttribute("style", 'width: '+val+'%');
            });
            htmx.on("#upload", "change", function(evt) {
              let val = 0;
              htmx.find("#progress-b").setAttribute("aria-valuenow", val);
              htmx.find("#progress-b").setAttribute("style", 'width: '+val+'%');
            });
        </script>

    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


