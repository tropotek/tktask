<?php
namespace App\Controller\Test;

use App\Component\Test;
use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;

class Component extends ControllerAdmin
{
    protected ?Test $com1 = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Component Test');

        if (!Auth::getAuthUser()) {
            Alert::addWarning('You do not have permission to access the page: <b>' . Uri::create()->getRelativePath() . '</b>');
            Uri::create('/')->redirect();
        }

        $this->com1 = new Test();

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $html = $this->com1->doDefault();
        $template->appendHtml('components', $html);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row">
    <div class="col-8">
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-cogs"></i> <span var="title"></span></div>
            <div class="card-body" var="content">
                <p>Main Content</p>

            </div>
        </div>
    </div>
    <div class="col-4" var="components">

    </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}