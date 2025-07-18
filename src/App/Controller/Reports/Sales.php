<?php
namespace App\Controller\Reports;

use App\Db\Company;
use App\Db\Invoice;
use App\Db\Payment;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Bs\Mvc\Table;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Db;
use Tk\Form\Field\Select;
use Tk\Money;
use Tk\Table\Cell;
use Tk\Uri;

class Sales extends ControllerAdmin
{

    protected array $dateSet = [];
    protected Form  $form;
    protected Table $table;


    public function doDefault(): void
    {
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Client Sales Report', 'fas fa-chart-line');
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

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('name');
        $this->table->setLimit(25);

        $this->table->appendCell('name')
            ->addCss('max-width')
            ->addOnValue(function(\stdClass $obj, Cell $cell) {
                $url = Uri::create('/companyEdit', ['companyId' => $obj->companyId]);
                return sprintf('<a href="%s" target="_blank">%s</a>', $url, $obj->name);
            });
        $this->table->appendCell('total')
            ->addOnValue(function (\stdClass $obj, Cell $cell) {
                return \Tk\Money::create($obj->total)->toString();
            });

        // execute table
        $this->table->execute();

        // Set the table rows
        $rows = $this->getSales($this->dateSet);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());


    }

    protected function getSales(array $dateset): array
    {
        return Db::query("
            SELECT
              c.company_id as companyId,
              c.name,
              SUM(p.amount) AS total
            FROM company c
            LEFT JOIN invoice i ON (i.fkey = 'App\\\\Db\\\\Company' AND i.fid = c.company_id)
            LEFT JOIN payment p USING (invoice_id)
            WHERE c.type = :type
            AND p.created BETWEEN :dateFrom AND :dateTo
            GROUP BY c.company_id, c.name
            ORDER BY c.name",
            [
                'type'     => Company::TYPE_CLIENT,
                'dateFrom' => $dateset[0]->format(Date::FORMAT_ISO_DATETIME),
                'dateTo'   => $dateset[1]->format(Date::FORMAT_ISO_DATETIME),
            ]
        );
    }

    protected function getSalesTotal(array $dateset): int
    {
        return Db::queryInt("
            SELECT SUM(p.amount) AS total
            FROM payment p
            WHERE p.created BETWEEN :dateFrom AND :dateTo",
            [
                'dateFrom' => $dateset[0]->format(Date::FORMAT_ISO_DATETIME),
                'dateTo'   => $dateset[1]->format(Date::FORMAT_ISO_DATETIME),
            ]
        );
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->appendTemplate('content', $this->form->show());
        $template->appendTemplate('content', $this->table->show());


        $pdf = Uri::create()->set('act', 'pdf');
        $template->setAttr('btn-pdf', 'href', $pdf);

        $template->setText('total-sales', Money::create($this->getSalesTotal($this->dateSet))->toString());


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
  <div class="card mb-3">
    <div class="card-header">
        <i var="icon"></i> <span var="title"></span>
        <div class="float-end">
            Totals Sales: <span var="total-sales"></span>
        </div>
    </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}