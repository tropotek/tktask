<?php
namespace App\Table;

use App\Db\UserMap;
use App\Util\Masquerade;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Log;
use Tk\ObjectUtil;
use Tk\Table;
use Tk\TableRenderer;
use Tk\Traits\SystemTrait;
use Tk\Ui\Button;
use Tk\Ui\Link;
use Tk\Uri;

/**
 * Example:
 * <code>
 *   $table = new \App\Table\User::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 *
 */
class User
{
    use SystemTrait;

    protected Table $table;

    protected ?Form $filters = null;

    protected string $type = \App\Db\User::TYPE_USER;


    public function __construct(string $type)
    {
        $this->table = new Table($type);
        $this->type = $type;
    }

    private function doDelete($user_id)
    {
        /** @var \App\Db\User $user */
        $user = UserMap::create()->find($user_id);
        $user?->delete();

        $this->getSession()->getFlashBag()->add('success', 'User removed successfully.');
        Uri::create()->reset()->redirect();
    }

    private function doMsq($user_id)
    {
        /** @var \App\Db\User $msqUser */
        $msqUser = UserMap::create()->find($user_id);

        if ($msqUser && Masquerade::masqueradeLogin($this->getFactory()->getAuthUser(), $msqUser)) {
            $this->getSession()->getFlashBag()->add('success', 'You are now logged in as user ' . $msqUser->getUsername());
            Uri::create('/dashboard')->redirect();
        }
        $this->getSession()->getFlashBag()->add('warning', 'You cannot login as user ' . $msqUser->getUsername() . ' invalid permissions');
        Uri::create()->remove(Masquerade::QUERY_MSQ)->redirect();
    }

    public function doDefault(Request $request)
    {
        if ($request->query->has('del')) {
            $this->doDelete($request->query->get('del'));
        }
        if ($request->query->has(Masquerade::QUERY_MSQ)) {
            $this->doMsq($request->query->get(Masquerade::QUERY_MSQ));
        }

        $this->table->appendCell(new Table\Cell\Checkbox('id'));
        $this->table->appendCell(new Table\Cell\Text('actions'))->addOnShow(function (Table\Cell\Text $cell) {
            $cell->addCss('text-nowrap text-center');
            $obj = $cell->getRow()->getData();

            $template = $cell->getTemplate();
            $btn = new Link('Edit');
            $btn->setText('');
            $btn->setIcon('fa fa-edit');
            $btn->addCss('btn btn-primary');
            $btn->setUrl('/userEditX/'.$obj->getId());
            $template->appendTemplate('td', $btn->show());
            $template->appendHtml('td', '&nbsp;');

            $btn = new Link('Masquerade');
            $btn->setText('');
            $btn->setIcon('fa fa-user-secret');
            $btn->addCss('btn btn-outline-dark');
            $btn->setUrl(Uri::create()->set(Masquerade::QUERY_MSQ, $obj->getId()));
            $btn->setAttr('data-confirm', 'Are you sure you want to log-in as user \''.$obj->getName().'\'');
            $template->appendTemplate('td', $btn->show());
            $template->appendHtml('td', '&nbsp;');

            $btn = new Link('Delete');
            $btn->setText('');
            $btn->setIcon('fa fa-trash');
            $btn->addCss('btn btn-danger');
            $btn->setUrl(Uri::create()->set('del', $obj->getId()));
            $btn->setAttr('data-confirm', 'Are you sure you want to delete \''.$obj->getName().'\'');
            $template->appendTemplate('td', $btn->show());

        });
        $this->table->appendCell(new Table\Cell\Text('username'))->setAttr('style', 'width: 100%;')->addOnShow(function (Table\Cell\Text $cell) {
            $obj = $cell->getRow()->getData();
            $cell->setUrl('/userEdit/'.$obj->getId());
        });
        $this->table->appendCell(new Table\Cell\Text('name'));
        //$this->table->appendCell(new Table\Cell\Text('type'));

        if ($this->type == \App\Db\User::TYPE_STAFF) {
            $this->table->appendCell(new Table\Cell\Text('permissions'))->addOnShow(function (Table\Cell\Text $cell) {
                /** @var \App\Db\User $user */
                $user = $cell->getRow()->getData();
                //vd(ObjectUtil::getClassConstants(\App\Db\User::class, 'PERM_'));
                //vd($user->getPermissionList(), \App\Db\User::PERMISSION_LIST);
                if ($user->hasPermission(\App\Db\User::PERM_ADMIN)) {
                    $cell->setValue(\App\Db\User::PERMISSION_LIST[\App\Db\User::PERM_ADMIN]);
                    return;
                }
                $list = array_filter(\App\Db\User::PERMISSION_LIST, function ($k) use ($user) {
                    return $user->hasPermission($k);
                }, ARRAY_FILTER_USE_KEY);
                $cell->setValue(implode(', ', $list));
            });
        }
        $this->table->appendCell(new Table\Cell\Text('email'))->addOnShow(function (Table\Cell\Text $cell) {
            /** @var \App\Db\User $user */
            $user = $cell->getRow()->getData();
            $cell->setUrl('mailto:'.$user->getEmail());
        });
        $this->table->appendCell(new Table\Cell\Text('active'));
        //$this->table->appendCell(new Table\Cell\Text('modified'));
        $this->table->appendCell(new Table\Cell\Text('created'));


        // Table Filters
        $this->filters = new Form($this->table->getId() . '-filters');

        $this->filters->appendField(new Form\Field\Input('search'))->setAttr('placeholder', 'Search');

//        $list = ['Staff' => 'staff', 'User' => 'user'];
//        $this->filters->appendField(new Form\Field\Select('type', $list))->prependOption('-- Type --', '');

        // load values
        $this->filters->setFieldValues($this->table->getTableSession()->get($this->filters->getId(), []));

        $this->filters->appendField(new Form\Action\Submit('Search', function (Form $form, Form\Action\ActionInterface $action) {
            $this->table->getTableSession()->set($this->filters->getId(), $form->getFieldValues());
            Uri::create()->redirect();
        }))->setGroup('');
        $this->filters->appendField(new Form\Action\Submit('Clear', function (Form $form, Form\Action\ActionInterface $action) {
            $this->table->getTableSession()->set($this->filters->getId(), []);
            Uri::create()->redirect();
        }))->setGroup('')->addCss('btn-secondary');

        $this->filters->execute($request->request->all());

        // Table Actions
        if ($this->getConfig()->isDebug()) {
            $this->table->appendAction(new Table\Action\Link('reset', Uri::create()->set(Table::RESET_TABLE, $this->table->getId()), 'fa fa-retweet'))
                ->setLabel('')
                ->setAttr('data-confirm', 'Are you sure you want to reset the Table`s session?')
                ->setAttr('title', 'Reset table filters and order to default.');
        }
        $this->table->appendAction(new Table\Action\Button('Create'))->setUrl(Uri::create('/userEdit')->set('type', $this->type));
        $this->table->appendAction(new Table\Action\Delete());
        $this->table->appendAction(new Table\Action\Csv())->addExcluded('actions');

        // Query
        $tool = $this->table->getTool();
        $filter = $this->filters->getFieldValues();
        $filter['type'] = $this->type;
        $list = UserMap::create()->findFiltered($filter, $tool);
        $this->table->setList($list, $tool->getFoundRows());

        //$this->table->resetTableSession();
        $this->table->execute($request);
    }

    public function show(): ?Template
    {
        $renderer = new TableRenderer($this->table);
        //$renderer->setFooterEnabled(false);
        $this->table->getRow()->addCss('text-nowrap');
        $this->table->addCss('table-hover');

        if ($this->filters) {
            $this->filters->addCss('row gy-2 gx-3 align-items-center');
            $filterRenderer = FormRenderer::createInlineRenderer($this->filters);
            $renderer->getTemplate()->appendTemplate('filters', $filterRenderer->show());
            $renderer->getTemplate()->setVisible('filters');
        }

        return $renderer->show();
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getFilterForm(): ?Form
    {
        return $this->filters;
    }
}