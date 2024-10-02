<?php
namespace App\Controller\User;

use App\Db\User;
use Au\Auth;
use Au\Masquerade;
use Bs\ControllerAdmin;
use Bs\Factory;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
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
 * @todo Implement the user phone and address fields. Look at using google api to get timestamp etc.
 */
class Edit extends ControllerAdmin
{
    protected ?User  $user = null;
    protected ?Auth  $auth = null;
    protected ?Form  $form = null;
    protected string $type = User::TYPE_MEMBER;


    public function doDefault(mixed $request, string $type): void
    {
        $this->getPage()->setTitle('Edit ' . ucfirst($type));

        $userId  = intval($_GET['userId'] ?? 0);
        $newType = trim($_GET['cv'] ?? '');

        if (isset($_GET[Masquerade::QUERY_MSQ])) {
            $this->doMsq(intval($_GET[Masquerade::QUERY_MSQ] ?? 0));
        }

        $this->type = $type;
        $this->user = new User();
        $this->user->type = $type;
        if ($userId) {
            $this->user = User::find($userId);
            if (!$this->user) {
                throw new Exception('Invalid User ID: ' . $userId);
            }
        }
        $this->auth = $this->user->getAuth();

        if ($this->type == User::TYPE_STAFF) {
            $this->setAccess(User::PERM_MANAGE_STAFF);
        }
        if ($this->type == User::TYPE_MEMBER) {
            $this->setAccess(User::PERM_MANAGE_MEMBERS);
        }

        // send inactive user activation email
        if (isset($_GET['a']) && !$this->user->active) {
            if (\App\Email\User::sendRecovery($this->user)) {
                Alert::addSuccess('An email has been sent to ' . $this->user->nameShort . ' to create their password.');
            } else {
                Alert::addError('Failed to send email to ' . $this->user->nameShort . ' to create their password.');
            }
            Uri::create()->remove('a')->redirect();
        }

        // Get the form template
        $this->form = new Form();

        $group = 'Details';
        $this->form->appendField(new Hidden('userId'))->setReadonly();

        $list = User::TITLE_LIST;
        $this->form->appendField(new Select('title', $list))
            ->setGroup($group)
            ->prependOption('', '');

        $this->form->appendField(new Input('givenName'))
            ->setGroup($group)
            ->setRequired();

        $this->form->appendField(new Input('familyName'))
            ->setGroup($group);

        $l1 = $this->form->appendField(new Input('username'))
            ->setGroup($group)
            ->setRequired();

        $l2 = $this->form->appendField(new Input('email'))
            ->setGroup($group)
            ->setRequired();

        // Only input lock existing user
        if ($this->user->userId) {
            $l1->addCss('tk-input-lock');
            $l2->addCss('tk-input-lock');
        }

        if ($this->type == User::TYPE_STAFF) {
            $list = array_flip(User::PERMISSION_LIST);
            $field = $this->form->appendField(new Checkbox('perm', $list))
                ->setLabel('Permissions')
                ->setGroup('Permissions');

            if (!Auth::getAuthUser()->hasPermission(User::PERM_MANAGE_STAFF)) {
                $field->setNotes('You require "Manage Staff" to modify permissions');
                $field->setDisabled();
            }
        }

        if ($this->user->userId) {
            $this->form->appendField(new Checkbox('active', ['Enable User Login' => '1']))
                ->setGroup($group);
        }

        // Form Actions
        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', $this->getBackUrl()));

        $load = $this->form->unmapModel($this->user);
        if ($this->type == User::TYPE_STAFF) {
            $load['perm'] = array_keys(array_filter(User::PERMISSION_LIST,
                    fn($k) => ($k & $this->auth->permissions), ARRAY_FILTER_USE_KEY)
            );
        }
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

        if (Auth::getAuthUser()->hasPermission(User::PERM_ADMIN) && !empty($newType)) {
            if ($newType == User::TYPE_STAFF) {
                $this->user->type = User::TYPE_STAFF;
                Alert::addSuccess('User now set to type STAFF, please select and save the users new permissions.');
            } else if ($newType == User::TYPE_MEMBER) {
                $this->user->type = User::TYPE_MEMBER;
                Alert::addSuccess('User now set to type MEMBER.');
            }
            $this->user->save();
            Uri::create()->remove('cv')->redirect();
        }

    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        // non admin cannot change permissions
        if (!Auth::getAuthUser()->isAdmin()) {
            $form->removeField('perm');
        }

        // set object values from fields
        $form->mapModel($this->user);
        $form->mapModel($this->auth);

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
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        if ($this->user->hasPermission(User::PERM_ADMIN)) {
            if ($this->user->isType(User::TYPE_MEMBER)) {
                $url = Uri::create()->set('cv', User::TYPE_STAFF);
                $template->setAttr('to-staff', 'href', $url);
                $template->setVisible('to-staff');
            } else if ($this->user->isType(User::TYPE_STAFF)) {
                $url = Uri::create()->set('cv', User::TYPE_MEMBER);
                $template->setAttr('to-member', 'href', $url);
                $template->setVisible('to-member');
            }
        }

        $template->appendText('title', $this->getPage()->getTitle());
        if (!$this->user->userId) {
            $template->setVisible('new-user');
        }
        if (Masquerade::canMasqueradeAs(Auth::getAuthUser(), $this->user->getAuth())) {
            $msqUrl = Uri::create()->set(Masquerade::QUERY_MSQ, $this->user->userId);
            $template->setAttr('msq', 'href', $msqUrl);
            $template->setVisible('msq');
        }

        if ($this->user->userId && !$this->user->active) {
            $url = Uri::create()->set('a');
            $template->setAttr('activate', 'href', $url);
            $template->setVisible('activate');
        }

        $this->form->getField('title')->addFieldCss('col-1');
        $this->form->getField('givenName')->addFieldCss('col-5');
        $this->form->getField('familyName')->addFieldCss('col-6');

        $this->form->getField('username')->addFieldCss('col-6');
        $this->form->getField('email')->addFieldCss('col-6');

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
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      <a href="/" title="Masquerade" data-confirm="Masquerade as this user" class="btn btn-outline-secondary" choice="msq"><i class="fa fa-user-secret"></i> Masquerade</a>
      <a href="/" title="Convert user to staff" data-confirm="Convert this user to staff" class="btn btn-outline-secondary" choice="to-staff"><i class="fa fa-retweet"></i> Convert To Staff</a>
      <a href="/" title="Convert user to member" data-confirm="Convert this user to member" class="btn btn-outline-secondary" choice="to-member"><i class="fa fa-retweet"></i> Convert To Member</a>
      <a href="/" title="Send Activation Email" data-confirm="Re-send the user activation email?" class="btn btn-outline-secondary" choice="activate"><i class="fa fa-fw fa-envelope"></i> Send Activate Email</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-users"></i> </div>
    <div class="card-body" var="content">
      <p choice="new-user"><b>NOTE:</b> New users will be sent an email requesting them to activate their account and create a new password.</p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}