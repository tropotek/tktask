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
        $this->getPage()->setTitle(ucwords($this->type) . ' Manager');

        if ($this->type == User::TYPE_STAFF) {
            $this->setUserAccess(User::PERM_MANAGE_STAFF);
        }

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
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(User $user, Cell $cell) {
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
            ->addOnValue(function(User $user, Cell $cell) {
                $url = Uri::create('/user/'.$user->type.'Edit', ['userId' => $user->userId]);
                return sprintf('<a href="%s">%s</a>', $url, $user->nameShort);
            });

        $this->table->appendCell('username')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('email')
            ->setSortable(true)
            ->addOnValue(function(User $user, Cell $cell) {
                return sprintf('<a href="mailto:%s">%s</a>', $user->email, $user->email);
            });

        if (Auth::getAuthUser()->hasPermission(User::PERM_ADMIN) && $this->type == User::TYPE_STAFF) {
            $this->table->appendCell('permissions')
                ->addCss('text-nowrap')
                ->addOnValue(function (User $user, Cell $cell) {
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
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('lastLogin')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateTime::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search: uid, name, email, username');

        $list = ['' => '-- All --', 'y' => 'Active', 'n' => 'Disabled'];
        $this->table->getForm()->appendField(new Select('active', $list))->setValue('y');

        // Add Table actions
        $this->table->appendAction(\Tk\Table\Action\Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnSelect(function(\Tk\Table\Action\Select $action, array $selected, string $value) {
                foreach ($selected as $id) {
                    $u = User::find($id);
                    $a = $u->getAuth();
                    $a->active = (strtolower($value) == 'active');
                    $a->save();
                }
            })
        );

        $this->table->appendAction(Csv::create()
            ->addOnCsv(function(Csv $action) {
                $action->setExcluded(['actions', 'permissions']);
                if (!$this->table->getCell(User::getPrimaryProperty())) {
                    $this->table->prependCell(User::getPrimaryProperty())->setHeader('id');
                }
                $this->table->getCell('username')->getOnValue()->reset();
                $this->table->getCell('email')->getOnValue()->reset();    // remove html from cell

                $filter = $this->table->getDbFilter()->resetLimits();
                $filter['type'] = $this->type;
                return User::findFiltered($filter);
            })
        );

        // execute table to init filter object
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
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->setAttr('create-staff', 'href', Uri::create('/user/staffEdit'));
        $template->setVisible('create-staff');

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Create Staff" class="btn btn-outline-secondary" choice="create-staff"><i class="fa fa-user"></i> Create Staff</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-users"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}