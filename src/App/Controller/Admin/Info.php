<?php
namespace App\Controller\Admin;

use Bs\ControllerDomInterface;
use Bs\Db\User;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Info extends ControllerDomInterface
{

    public function doDefault(Request $request)
    {
        $this->getPage()->setTitle('PHP Info');
        $this->setAccess(User::PERM_SYSADMIN);

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        ob_start();
        phpinfo();
        $ob = ob_get_clean();
        $ob1 = tidy_repair_string($ob, ['output-xhtml' => true, 'show-body-only' => true], 'utf8');
        $template->appendHtml('content', $ob1);

        $js = <<<JS
jQuery(function($) {
    $('.php-info table').addClass('table table-striped');
    $('.php-info table td:not(:first-child)').addClass('text-center');
    $('.php-info table td:first-child').addClass('fw-bold text-nowrap');
});
JS;
        $template->appendJs($js);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> PHP Info</div>
    <div class="card-body php-info" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


