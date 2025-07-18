<?php
namespace App\Controller\User;

use App\Db\User;
use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Bs\Db\Masquerade;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Table\Action\ColumnSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{

    protected ?Table $table = null;
    protected string $type  = User::TYPE_STAFF;

    public function doDefault(): void
    {
        $this->setUserAccess(User::PERM_SYSADMIN);
        $this->getPage()->setTitle(ucwords($this->type) . ' Manager', 'fa fa-users');

        if (isset($_GET[Masquerade::QUERY_MSQ])) {
            $this->doMsq(intval($_GET[Masquerade::QUERY_MSQ] ?? 0));
        }

        // init the user table
        $this->table = new \Bs\Mvc\Table();
        $this->table->setOrderBy('username');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'userId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('actions')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->addOnHtml(function(User $user, Cell $cell) {
                $msq = Uri::create()->set(Masquerade::QUERY_MSQ, strval($user->userId));
                $disabled = !Masquerade::canMasqueradeAs(Auth::getAuthUser(), $user->getAuth()) ? 'disabled' : '';
                return <<<HTML
                    <a class="btn btn-outline-dark {$disabled}" href="$msq" title="Masquerade" data-confirm="Are you sure you want to log-in as user {$user->nameShort}" {$disabled}><i class="fa fa-fw fa-user-secret"></i></a>
                HTML;
            });

        $this->table->appendCell('nameShort')
            ->addCss('text-nowrap')
            ->addHeaderCss('max-width')
            ->setSortable(true)
            ->addOnHtml(function(User $user, Cell $cell) {
                $url = Uri::create('/user/'.$user->type.'Edit', ['userId' => $user->userId]);
                return sprintf('<a href="%s">%s</a>', $url, $user->nameShort);
            });

        $this->table->appendCell('username')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('email')
            ->setSortable(true)
            ->addOnHtml(function(User $user, Cell $cell) {
                return sprintf('<a href="mailto:%s">%s</a>', $user->email, $user->email);
            });

        if (Auth::getAuthUser()->hasPermission(User::PERM_ADMIN) && $this->type == User::TYPE_STAFF) {
            $this->table->appendCell('permissions')
                ->addCss('text-nowrap')
                ->addOnhtml(function (User $user, Cell $cell) {
                    if ($user->hasPermission(User::PERM_ADMIN)) {
                        $list = User::PERMISSION_LIST;
                        return $list[User::PERM_ADMIN];
                    }
                    $list = array_filter(User::PERMISSION_LIST, function ($k) use ($user) {
                        return $user->hasPermission($k);
                    }, ARRAY_FILTER_USE_KEY);
                    return implode(', <br/>', $list);
                });
        }

        $this->table->appendCell('active')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('lastLogin')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search: uid, name, email, username');

        $list = ['' => '-- All --', 'y' => 'Active', 'n' => 'Disabled'];
        $this->table->getForm()->appendField(new Select('active', $list))->setValue('y');

        // Add Table actions
        $this->table->appendAction(ColumnSelect::create());
        $this->table->appendAction(\Tk\Table\Action\Select::createActiveSelect(Auth::class, $rowSelect));
        $this->table->appendAction(Csv::createDefault(User::class, $rowSelect));

        // execute table, init filter object
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter->set('type', $this->type);
        $rows = User::findFiltered($filter);

        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    private function doMsq(int $userId): void
    {
        $msqUser = Auth::findByModelId(User::class, $userId);
        if ($msqUser && Masquerade::masqueradeLogin(Auth::getAuthUser(), $msqUser)) {
            Alert::addSuccess('You are now logged in as user ' . $msqUser->username);
            $msqUser->getHomeUrl()->redirect();
        }

        Alert::addWarning('You cannot login as user ' . $msqUser->username . ' invalid permissions');
        Uri::create()->remove(Masquerade::QUERY_MSQ)->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body" var="actions">
      <a href="/user/staffEdit" title="Create Staff" class="btn btn-outline-secondary"><i class="fa fa-user"></i> Create Staff</a>
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