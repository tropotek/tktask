<?php
namespace App\Controller\Reports;

use App\Db\Invoice;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Form\Field\Select;
use Tk\Uri;


class ProfitLoss extends ControllerAdmin
{

    protected ?\App\Ui\ProfitLoss $report = null;
    protected array $dateSet = [];
    protected Form  $form;


    public function doDefault(): mixed
    {
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Profit & Loss Report', 'fas fa-dollar-sign');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $this->dateSet = Date::getFinancialYear(new \DateTime($_GET['date'] ?? 'now'));

        $this->form = new Form();
        $this->form->setMethod(\Tk\Form::METHOD_GET);

        $firstInvoice = Invoice::getFirstInvoice();
        if (is_null($firstInvoice)) {
            Alert::addWarning('No invoices available to report on.');
            Uri::create('/')->redirect();
        }

        $start = Date::getFinancialYear()[0];
        $end = Date::getFinancialYear($firstInvoice->created)[0];
        $val   = $this->dateSet[0]->format('Y-m-d');
        $list  = [];

        while ($start >= $end) {
            $years = sprintf('%s-%s', (int)$start->format('Y'), (int)$start->format('Y')+1);
            $list[$start->format('Y-m-d')] = $years;
            $start = $start->sub(new \DateInterval('P1Y'));
        }
        $this->form->appendField(new Select('date', $list))
            ->setLabel('Financial Year:')
            ->setValue($val);

        $this->form->execute($_GET);

        $this->report = new \App\Ui\ProfitLoss($this->dateSet);

        return null;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->appendTemplate('content', $this->form->show());
        $template->appendTemplate('content', $this->report->show());

        $pdf = Uri::create('/pdf/profitLoss')->set('date', $this->dateSet[0]->format('Y-m-d'))->set('o', 'pdf');
        $template->setAttr('btn-pdf', 'href', $pdf);


        $js = <<<JS
jQuery(function ($) {
  $('#form_date').on('change', function (e) {
      $(this).closest('form').submit();
  });
});
JS;
        $template->appendJs($js);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body" var="actions">
      <a href="#" class="btn btn-outline-secondary" title="PDF" target="_blank" var="btn-pdf">
        <i class="fa fa-download"></i>
        <span>PDF</span>
      </a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}