<?php
namespace App\Controller;

use App\Db\Notify;
use App\Db\Task;
use App\Db\User;
use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Db;
use Tk\Exception;
use Tk\Uri;

class Dashboard extends ControllerAdmin
{

    protected ?\App\Table\Task $table = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Dashboard');
        $this->getCrumbs()->reset();

        if (!Auth::getAuthUser()) {
            Alert::addWarning('You do not have permission to access the page');
            Uri::create('/')->redirect();
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
              <h3 class="my-2 py-1"><span data-plugin="counterup" var="openTasks">100</span></h3>
<!--              <p class="mb-0 text-muted hidden">-->
<!--                <span class="text-success me-2"><span class="mdi mdi-arrow-up-bold"></span> 0.00%</span>-->
<!--                <span class="text-nowrap">Since last month</span>-->
<!--              </p>-->
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
              <h3 class="my-2 py-1">$<span data-plugin="counterup" var="unpaidInvoices">100.00</span></h3>
<!--              <p class="mb-0 text-muted">-->
<!--                <span class="text-success me-2"><span class="mdi mdi-arrow-up-bold"></span> 0.00%</span>-->
<!--                <span class="text-nowrap">Since last month</span>-->
<!--              </p>-->
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
              <h3 class="my-2 py-1">$<span data-plugin="counterup" var="openInvoices">100.00</span></h3>
<!--              <p class="mb-0 text-muted">-->
<!--                <span class="text-success me-2"><span class="mdi mdi-arrow-up-bold"></span> 0.00%</span>-->
<!--                <span class="text-nowrap">Since last month</span>-->
<!--              </p>-->
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
              <h3 class="my-2 py-1">$<span data-plugin="counterup" var="revenue">100.00</span></h3>
<!--              <p class="mb-0 text-muted">-->
<!--                <span class="text-success me-2"><span class="mdi mdi-arrow-up-bold"></span> 0.00%</span>-->
<!--                <span class="text-nowrap">Since last month</span>-->
<!--              </p>-->
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


