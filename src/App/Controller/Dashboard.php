<?php
namespace App\Controller;

use App\Db\Domain;
use App\Db\Invoice;
use App\Db\Payment;
use App\Db\Task;
use App\Db\User;
use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Db;
use Tk\Money;
use Tk\Uri;

class Dashboard extends ControllerAdmin
{

    protected ?\App\Table\Task $table = null;


    public function doDefault(): void
    {
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Dashboard');

        if (!Auth::getAuthUser()) {
            Alert::addWarning('You do not have permission to access the page');
            Uri::create('/')->redirect();
        }

        // This will do for now, no need for warnings on every page
        $pings = Domain::findFiltered(['status' => false, 'active' => true]);
        if (count($pings)) {
            $msg = '<strong>Sites Offline:</strong><br>';
            foreach ($pings as $ping) {
                $msg .= sprintf('%s - %s<br>', $ping->companyName, $ping->url);
            }
            Alert::addWarning($msg);
        }

        $this->table = new \App\Table\Task('mytasks');
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter->set('assignedUserId', User::getAuthUser()->userId);
        $rows = Task::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());


    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $template->appendTemplate('content', $this->table->show());

        // Open Tasks
        $tasks = Task::findFiltered([
            'assignedUserId' => User::getAuthUser()->userId,
            'status' => [Task::STATUS_OPEN, Task::STATUS_PENDING, Task::STATUS_HOLD],
        ]);
        $template->setText('openTasks', count($tasks));

        // Unpaid Invoices
        $unpaid = Invoice::findFiltered([
            'status' => Invoice::STATUS_UNPAID
        ]);
        $total = Money::create();
        foreach ($unpaid as $invoice) {
            $total = $total->add($invoice->total);
        }
        $template->setText('unpaidInvoices', $total->toFloatString('.', ','));

        // Open Invoices
        $open = Invoice::findFiltered([
            'status' => Invoice::STATUS_OPEN
        ]);
        $total = Money::create();
        foreach ($open as $invoice) {
            $total = $total->add($invoice->total);
        }
        $template->setText('openInvoices', $total->toFloatString('.', ','));


        $dateSet = Date::getFinancialYear(Date::create());
        $payments = Payment::findFiltered([
            'dateStart' => $dateSet[0],
            'dateEnd' => $dateSet[1],
            'status' => Payment::STATUS_CLEARED,
        ]);
        $total = Money::create();
        foreach ($payments as $payment) {
            $total = $total->add($payment->amount);
        }
        $template->setText('revenue', $total->toFloatString('.', ','));

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="row">

    <!-- Open Tasks -->
    <div class="col-xl-3 col-md-6 d-none d-sm-none d-md-block">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h5 class="text-muted fw-normal mt-0 text-truncate" title="Open Tasks">Open Tasks</h5>
              <h3 class="my-2"><span data-plugin="counterup" var="openTasks">100</span></h3>
            </div>
            <div class="avatar-sm">
              <span class="avatar-title bg-soft-primary rounded">
                <i class="ri-stack-line font-20 text-primary"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Unpaid Invoices -->
    <div class="col-xl-3 col-md-6 d-none d-sm-none d-md-block">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h5 class="text-muted fw-normal mt-0 text-truncate" title="Unpaid Invoices">Unpaid Invoices</h5>
              <h3 class="my-2">$<span data-plugin="counterup" var="unpaidInvoices">100.00</span></h3>
            </div>
            <div class="avatar-sm">
              <span class="avatar-title bg-soft-primary rounded">
                <i class="ri-coins-line font-20 text-primary"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Open Invoices -->
    <div class="col-xl-3 col-md-6 d-none d-sm-none d-md-block">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h5 class="text-muted fw-normal mt-0 text-truncate" title="Campaign Sent">Open Invoices</h5>
              <h3 class="my-2">$<span data-plugin="counterup" var="openInvoices">100.00</span></h3>
            </div>
            <div class="avatar-sm">
              <span class="avatar-title bg-soft-primary rounded">
                <i class="ri-coins-fill font-20 text-primary"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Revenue -->
    <div class="col-xl-3 col-md-6 d-none d-sm-none d-md-block">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h5 class="text-muted fw-normal mt-0 text-truncate" title="Financial Year Revenue">Revenue</h5>
              <h3 class="my-2">$<span data-plugin="counterup" var="revenue">100.00</span></h3>
            </div>
            <div class="avatar-sm">
              <span class="avatar-title bg-soft-primary rounded">
                <i class="ri-funds-box-line font-20 text-primary"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-tasks"></i> <span>My Tasks</span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


