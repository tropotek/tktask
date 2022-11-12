<?php
namespace App\Controller\User;

use App\Db\UserMap;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Table;
use Tk\TableRenderer;
use Tk\Ui\Button;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Manager extends PageController
{

    protected Table $table;
    protected Form $filters;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Manager');
    }

    public function doDefault(Request $request)
    {

        $this->table = new Table('newTable');

        $this->table->appendCell(new Table\Cell\Checkbox('id'));
        $this->table->appendCell(new Table\Cell\Text('actions'))->addOnShow(function (Table\Cell\Text $cell) {
            $cell->addCss('text-nowrap text-center');
            $obj = $cell->getRow()->getData();

            $template = $cell->getTemplate();
            $btn = new Button('Edit');
            $btn->setText('');
            $btn->setIcon('fa fa-edit');
            $btn->addCss('btn btn-primary');
            $btn->setUrl('/userEdit/'.$obj->getId());

            $btn->setAttr('data-id', $obj->getId());
            $btn->setAttr('style', '--bs-btn-padding-y: .2rem; --bs-btn-padding-x: .4rem; --bs-btn-font-size: .75rem;');
            $template->appendTemplate('td', $btn->show());
            $template->appendHtml('td', '&nbsp;');

            $btn = new Button('Delete');
            $btn->setText('');
            $btn->setIcon('fa fa-trash');
            $btn->addCss('btn btn-danger');
            $btn->setAttr('style', '--bs-btn-padding-y: .2rem; --bs-btn-padding-x: .4rem; --bs-btn-font-size: .75rem;');
            $btn->setAttr('data-id', $obj->getId());
            $template->appendTemplate('td', $btn->show());
            $template->appendHtml('td', '&nbsp;');

            $btn = new Button('Action', );
            $btn->setText('');
            $btn->setIcon('fa fa-check');
            $btn->addCss('btn btn-success');
            $btn->setAttr('style', '--bs-btn-padding-y: .2rem; --bs-btn-padding-x: .4rem; --bs-btn-font-size: .75rem;');
            $btn->setAttr('data-id', $obj->getId());
            $template->appendTemplate('td', $btn->show());

        });
        $this->table->appendCell(new Table\Cell\Text('username'))->setAttr('style', 'width: 100%;')->addOnShow(function (Table\Cell\Text $cell) {
            $obj = $cell->getRow()->getData();
            $cell->setUrl('/userEdit/'.$obj->getId());
        });
        $this->table->appendCell(new Table\Cell\Text('nameFirst'))->addOnShow(function (Table\Cell\Text $cell) {
            $obj = $cell->getRow()->getData();
            $cell->setUrl('/nUserEdit/'.$obj->getId());
        });
        $this->table->appendCell(new Table\Cell\Text('nameLast'));
        $this->table->appendCell(new Table\Cell\Text('type'));
        $this->table->appendCell(new Table\Cell\Text('email'))->addOnShow(function (Table\Cell\Text $cell) {
            $obj = $cell->getRow()->getData();
            $cell->setUrl('mailto:'.$obj->getEmail());
        });
        //$this->table->appendCell(new Table\Cell\Summarize('notes'));

        $this->table->appendCell(new Table\Cell\Text('active'));
        //$this->table->appendCell(new Table\Cell\Text('modified'));
        $this->table->appendCell(new Table\Cell\Text('created'));


        // TODO: Setup Table Filters
        $this->filters = new Form($this->table->getId() . '-filters');

        $this->filters->appendField(new Form\Field\Input('search'))->setAttr('placeholder', 'Search');
        $list = array('-- Type --' => '', 'Admin' => 'admin', 'Member' => 'member');
        $this->filters->appendField(new Form\Field\Select('type', $list));

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


        // TODO: Setup Table Actions
        if ($this->getConfig()->isDebug()) {
            $this->table->appendAction(new Table\Action\Link('reset', Uri::create()->set(Table::RESET_TABLE, $this->table->getId()), 'fa fa-retweet'))
                ->setLabel('')
                ->setAttr('data-confirm', 'Are you sure you want to reset the Table`s session?')
                ->setAttr('title', 'Reset table filters and order to default.');
        }
        $this->table->appendAction(new Table\Action\Button('Create'))->setUrl(Uri::create('/userEdit'));
        $this->table->appendAction(new Table\Action\Delete());
        $this->table->appendAction(new Table\Action\Csv())->addExcluded('actions');


        //$this->table->resetTableSession();
        $tool = $this->table->getTool('created DESC', 25);

        // Query
        $filter = $this->filters->getFieldValues();
        //vd($filter);
        $list = UserMap::create()->findFiltered($filter, $tool);
        $this->table->setList($list, $tool->getFoundRows());

        $this->table->execute($request);

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $this->table->getRow()->addCss('text-nowrap');
        $template->setText('title', $this->getPage()->getTitle());

        $renderer = new TableRenderer($this->table, $this->makePath($this->getConfig()->get('template.path.table')));
        //$renderer->setFooterEnabled(false);
        $this->table->addCss('table-hover');

        $this->filters->addCss('row gy-2 gx-3 align-items-center');
        $filterRenderer = new FormRenderer($this->filters, $this->makePath($this->getConfig()->get('template.path.form.inline')));
        $renderer->getTemplate()->appendTemplate('filters', $filterRenderer->show());
        $renderer->getTemplate()->setVisible('filters');

        $template->appendTemplate('content', $renderer->show());


        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <h2 var="title"></h2>
  <div var="content"></div>

</div>
HTML;
        return $this->loadTemplate($html);
    }


}