<?php
namespace App\Component;

use App\Db\Company;
use App\Db\ExpenseCategory;
use App\Db\User;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Collection;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Input;
use Tk\Uri;

class CompanySelectDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected string       $dialogId  = 'company-select';
    protected array        $hxEvents  = [];
    protected string       $type      = Company::TYPE_CLIENT;
    protected array        $companies = [];


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $this->type = trim($_GET['type'] ?? $_POST['type'] ?? Company::TYPE_CLIENT);
        if (!in_array($this->type, Company::TYPE_LIST)) {
            throw new Exception("Invalid company type");
        }

        $this->companies = Company::findFiltered(Filter::create(['active' => true, 'type' => $this->type], 'name'));


        // Send HX event headers
        if (count($this->hxEvents)) {
            header(sprintf('HX-Trigger: %s', json_encode($this->hxEvents)));
        }

        return $this->show();
    }


    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());

        foreach ($this->companies as $company) {
            $row = $template->getRepeat('row');
            $row->setText('name', $company->name);
            $row->setAttr('name', 'data-company-id', $company->companyId);
            $row->appendRepeat();
        }

        $js = <<<JS
jQuery(function($) {
    const dialog = '#{$this->getDialogId()}';

    $('.company-item a', dialog).on('click', function() {
        $(dialog).trigger('companySelect', this);
    });

});
JS;
        $template->appendJs($js);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="modal fade" var="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Select Client</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" var="content">

        <table class="table">
          <thead>
            <tr><th class="text-start">Name</th></tr>
          </thead>
          <tbody>
            <tr repeat="row">
              <td class="company-item"><a href="javascript:;" var="name"></a></td>
            </tr>
          </tbody>
        </table>

      </div>
    </div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

    public function getDialogId(): string
    {
        return $this->dialogId;
    }

}
