<?php
namespace App\Controller\User;

use App\Db\User;
use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Config;
use Tk\Date;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Form\Field\Select;
use Tk\Uri;

/**
 *
 */
class Profile extends ControllerAdmin
{
    protected ?Form $form = null;
    protected ?User $user = null;
    protected bool  $templateSelectEnabled = false;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('My Profile', 'fa fa-user');
        $this->setUserAccess();

        $this->templateSelectEnabled = str_contains($this->getPage()->getTemplatePath(), '/minton/');

        // Get the form template
        $this->user = User::getAuthUser();
        $this->form = new Form($this->user);

        // send inactive user activation email
        if (isset($_GET['pass'])) {
            if ($_GET['pass'] != $this->user->hash) {
                throw new \Exception('Invalid user action, please contact your administrator.');
            }
            if (\App\Email\User::sendRecovery($this->user)) {
                Alert::addSuccess('An email has been sent to ' . $this->user->nameShort . ' to create their password.');
            } else {
                Alert::addError('Failed to send email to ' . $this->user->nameShort . ' to create their password.');
            }
            Uri::create()->remove('pass')->redirect();
        }

        $tab = 'Details';
        $this->form->appendField(new Hidden('userId'))->setReadonly();

        $list = Collection::listCombine(User::TITLE_LIST);
        $this->form->appendField((new Select('title', $list))
            ->prependOption('', ''))
            ->setGroup($tab)
            ->addFieldCss('col-md-2')
            ->setLabel('Title');

        $this->form->appendField(new Input('givenName'))
            ->setGroup($tab)
            ->addFieldCss('col-md-5')
            ->setRequired();

        $this->form->appendField(new Input('familyName'))
            ->addFieldCss('col-md-5')
            ->setGroup($tab);

        $this->form->appendField(new Input('username'))->setGroup($tab)
            ->setDisabled()
            ->setReadonly()
            ->setRequired()
            ->addFieldCss('col-md-6');

        $this->form->appendField(new Input('email'))->setGroup($tab)
            ->setDisabled()
            ->setReadonly()
            ->setRequired()
            ->addFieldCss('col-md-6');

        if ($this->templateSelectEnabled) {
            $list = ['sn-admin' => 'Side Menu', 'tn-admin' => 'Top Menu'];
            $this->form->appendField((new \Tk\Form\Field\Select('template', $list))
                ->prependOption('-- Site Default --', '')
                ->setLabel('Template Layout')
                ->setNotes('Select a side-menu or top-menu template as the default site layout.')
                ->setGroup($tab)
            );
        }

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Breadcrumbs::getBackUrl()));

        // Load form with object values
        $load = $this->user->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        // set object values from fields
        $values = $form->getFieldValues();
        $this->user->mapForm($values);
        $this->user->getAuth()->mapForm($values);

        $form->addFieldErrors($this->user->validate());
        $form->addFieldErrors($this->user->getAuth()->validate());

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $this->user->save();
        $this->user->getAuth()->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/profile'));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Breadcrumbs::getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        if ($this->user->userId) {
            $template->setText('modified', $this->user->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->user->created->format(Date::FORMAT_LONG_DATETIME));
            if ($this->user->type == User::TYPE_STAFF) {
                $url = Uri::create('/component/userPermissions', [
                    'userId' => $this->user->userId,
                    'canEdit' => false,
                ]);
                $template->setAttr('comp-perms', 'hx-get', $url);
                $template->setVisible('comp-perms');
            }

            $url = Uri::create('/component/userPhoto', ['userId' => $this->user->userId]);
            $template->setAttr('comp-photo', 'hx-get', $url);
        }

        $this->form->getRenderer()->addFieldCss('mb-3');
        $template->appendTemplate('content', $this->form->show());

        if (Config::instance()->get('auth.profile.password')) {
            $template->setVisible('password');
            $url = Uri::create()->set('pass', $this->user->hash);
            $template->setAttr('pass', 'href', $url);
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <div class="row" choice="password">
        <div class="col">
            <div class="card mb-3">
                <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
                <div class="card-body" var="actions">
                    <a href="/" title="Change Password" data-confirm="Request change password email?" class="btn btn-outline-secondary" var="pass"><i class="fa fa-fw fa-key"></i> Change Password</a>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="info-dropdown dropdown float-end" title="Details">
                        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
                            <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
                        </div>
                    </div>
                    <i var="icon"></i> <span var="title"></span>
                </div>
                <div class="card-body" var="content"></div>
            </div>
        </div>
    
        <div class="col-3">
            <div hx-get="/component/userPermissions" hx-trigger="load" hx-swap="outerHTML" choice="comp-perms">
                <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
            </div>
            <div hx-get="/component/userPhoto" hx-trigger="load" hx-swap="outerHTML" var="comp-photo">
              <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
            </div>
        </div>
    </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}