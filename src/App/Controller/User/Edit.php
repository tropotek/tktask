<?php
namespace App\Controller\User;

use App\Db\User;
use Bs\Auth;
use Bs\Db\Masquerade;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Date;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

/**
 *
 */
class Edit extends ControllerAdmin
{
    protected ?User  $user = null;
    protected ?Auth  $auth = null;
    protected ?Form  $form = null;
    protected string $type = User::TYPE_STAFF;
    protected bool   $templateSelectEnabled = false;


    public function doDefault(mixed $request): void
    {
        $this->getPage()->setTitle('Edit ' . ucfirst($this->type), 'fa fa-users');

        $userId  = intval($_REQUEST['userId'] ?? 0);
        $this->templateSelectEnabled = str_contains($this->getPage()->getTemplatePath(), '/minton/');

        if (isset($_GET[Masquerade::QUERY_MSQ])) {
            $this->doMsq(intval($_GET[Masquerade::QUERY_MSQ] ?? 0));
        }

        $this->user = new User();
        $this->user->type = $this->type;
        if ($userId) {
            $this->user = User::find($userId);
            if (is_null($this->user)) {
                throw new Exception('Invalid User ID: ' . $userId);
            }
        }
        $this->auth = $this->user->getAuth();

        $this->setUserAccess(User::PERM_MANAGE_STAFF);

        // Request user to reset their password
        if ($this->user->userId && isset($_GET['r'])) {
            if (\App\Email\User::sendRecovery($this->user)) {
                Alert::addSuccess('An email has been sent to ' . $this->user->nameShort . ' to reset their password.');
            } else {
                Alert::addError('Failed to send email to ' . $this->user->nameShort . ' to reset their password.');
            }
            Uri::create()->remove('r')->redirect();
        }

        // Get the form template
        $this->form = new Form();

        $group = 'Details';
        $this->form->appendField(new Hidden('userId'))->setReadonly();

        $list = Collection::listCombine(User::TITLE_LIST);
        $this->form->appendField((new Select('title', $list))
            ->setGroup($group)
            ->prependOption('', '')
            ->addFieldCss('col-md-1')
        );

        $this->form->appendField(new Input('givenName'))
            ->setGroup($group)
            ->setRequired()
            ->addFieldCss('col-md-5');

        $this->form->appendField(new Input('familyName'))
            ->setGroup($group)
            ->addFieldCss('col-md-6');

        $l1 = $this->form->appendField(new Input('username'))
            ->setGroup($group)
            ->setRequired()
            ->addFieldCss('col-md-6');

        $l2 = $this->form->appendField(new Input('email'))
            ->setGroup($group)
            ->setRequired()
            ->addFieldCss('col-md-6');

        // Only input lock existing user
        if ($this->user->userId) {
            $l1->addCss('tk-input-lock');
            $l2->addCss('tk-input-lock');
        }

        if ($this->user->userId) {
            $this->form->appendField(new Checkbox('active', ['1' => 'active']))
                ->setLabel('')
                ->setNotes('User Login Enabled')
                ->setGroup($group);
        }

        if ($this->templateSelectEnabled) {
            $list = ['sn-admin' => 'Side Menu', 'tn-admin' => 'Top Menu'];
            $this->form->appendField((new \Tk\Form\Field\Select('template', $list))
                ->prependOption('-- Site Default --', '')
                ->setLabel('Template Layout')
                ->setNotes('Select a side-menu or top-menu template as the default site layout.')
                ->setGroup($group)
            );
        }

        $list = User::PERMISSION_LIST;
        $field = $this->form->appendField(new Checkbox('perm', $list))
            ->setLabel('Permissions')
            ->setGroup('Permissions');

        if (!Auth::getAuthUser()->hasPermission(User::PERM_MANAGE_STAFF)) {
            $field->setNotes('You require "Manage Staff" to modify permissions');
            $field->setDisabled();
        }

        // Form Actions
        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Breadcrumbs::getBackUrl()));

        $load = $this->user->unmapForm();
        $load['perm'] = array_keys(
            array_filter(
                User::PERMISSION_LIST,
                fn($k) => ($k & $this->auth->permissions) != 0,
                ARRAY_FILTER_USE_KEY
            )
        );

        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        // non admin cannot change permissions
        if (!Auth::getAuthUser()->hasPermission(User::PERM_SYSADMIN)) {
            $form->removeField('perm');
        }

        // set object values from fields
        $values = $form->getFieldValues();
        $this->user->mapForm($values);
        $this->auth->mapForm($values);

        if ($form->getField('perm')) {
            $this->auth->permissions = array_sum($form->getFieldValue('perm') ?? []);
        }

        $form->addFieldErrors($this->user->validate());
        $form->addFieldErrors($this->auth->validate());

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $isNew = ($this->user->userId == 0);
        $this->user->save();
        if ($isNew) {
            $this->auth->fid = $this->user->userId;
            $this->auth->active = false;
        }
        $this->auth->save();
        $this->user->save();

        // Send email to update password
        if ($isNew) {
            if (\App\Email\User::sendRecovery($this->user)) {
                Alert::addSuccess('An email has been sent to ' . $this->user->email . ' to create their password.');
            } else {
                Alert::addError('Failed to send email to ' . $this->user->email . ' to create their password.');
            }
        } else {
            Alert::addSuccess('Form save successfully.');
        }

        $action->setRedirect(Uri::create('/user/'.$this->type.'Edit')->set('userId', $this->user->userId));
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
            $template->setVisible('edit');
            $template->setText('modified', $this->user->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->user->created->format(Date::FORMAT_LONG_DATETIME));
        }

        if (!$this->user->userId) {
            $template->setVisible('new-user');
        }
        if ($this->user->userId && Masquerade::canMasqueradeAs(Auth::getAuthUser(), $this->user->getAuth())) {
            $msqUrl = Uri::create()->set(Masquerade::QUERY_MSQ, $this->user->userId);
            $template->setAttr('msq', 'href', $msqUrl);
            $template->setVisible('msq');
        }

        if ($this->user->userId) {
            $url = Uri::create()->set('r');
            $template->setAttr('reset', 'href', $url);
            $template->setVisible('reset');
        }

        $renderer = $this->form->getRenderer();
        $renderer->addFieldCss('mb-3');

        $template->appendTemplate('content', $this->form->show());

        return $template;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3" choice="edit">
    <div class="card-body">
      <a href="/" title="Masquerade" data-confirm="Masquerade as this user" class="btn btn-outline-secondary" choice="msq"><i class="fa fa-user-secret"></i> Masquerade</a>
      <a href="/" title="Request Password Reset Email" data-confirm="Send an email to request user to reset their password?<br>Note: This will activate any inactive account." class="btn btn-outline-secondary" choice="reset"><i class="fa fa-fw fa-envelope"></i> Send Password Reset Email</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header">
      <div class="info-dropdown dropdown float-end" title="Details" choice="edit">
        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end">
          <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
          <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
        </div>
      </div>
      <i var="icon"></i> <span var="title"></span>
    </div>
    <div class="card-body" var="content">
      <p choice="new-user"><b>NOTE:</b> New users will be sent an email requesting them to activate their account and create a new password.</p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}