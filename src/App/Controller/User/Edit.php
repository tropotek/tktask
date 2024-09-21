<?php
namespace App\Controller\User;

use App\Db\User;
use Au\Auth;
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
use Tk\Form\Field\Textarea;
use Tk\Uri;

/**
 *
 * @todo Implement the user phone and address fields. Look at using google api to get timestamp etc.
 */
class Edit extends ControllerAdmin
{
    protected ?User  $user = null;
    protected ?Form  $form = null;
    protected string $type = User::TYPE_MEMBER;


    public function doDefault(mixed $request, string $type): void
    {
        $this->getPage()->setTitle('Edit ' . ucfirst($type));

        $userId  = intval($_GET['userId'] ?? 0);
        $newType = trim($_GET['cv'] ?? '');

        $this->type = $type;
        $this->user = new User();
        $this->user->type = $type;
        if ($userId) {
            $this->user = User::find($userId);
            if (!$this->user) {
                throw new Exception('Invalid User ID: ' . $userId);
            }
        }

        if ($this->type == User::TYPE_STAFF) {
            $this->setAccess(User::PERM_MANAGE_STAFF);
        }
        if ($this->type == User::TYPE_MEMBER) {
            $this->setAccess(User::PERM_MANAGE_MEMBERS);
        }

        // Get the form template
        $this->form = new Form();

        $group = 'Details';
        $this->form->appendField(new Hidden('userId'))->setReadonly();

        $list = \Bs\Db\User::getTitleList();
        $this->form->appendField(new Select('title', $list))
            ->setGroup($group)
            ->prependOption('', '');

        $this->form->appendField(new Input('givenName'))
            ->setGroup($group)
            ->setRequired();

        $this->form->appendField(new Input('familyName'))
            ->setGroup($group)
            ->setRequired();

        $l1 = $this->form->appendField(new Input('username'))
            ->setGroup($group)
            ->setRequired();

        $l2 = $this->form->appendField(new Input('email'))
            ->setGroup($group)
            ->setRequired();

        // Only input lock existing user
        if ($this->getUser()->userId) {
            $l1->addCss('tk-input-lock');
            $l2->addCss('tk-input-lock');
        }

        $factory = Factory::instance();
        $auth = Auth::getAuthUser();
        if ($this->getUser()->isStaff() && $auth->hasPermission(User::PERM_SYSADMIN)) {
            $list = array_flip($factory->getAvailablePermissions($this->getUser()));
            $field = $this->form->appendField(new Checkbox('perm', $list))
                ->setLabel('Permissions')
                ->setGroup('Permissions')
                ->setNotes('Only admin users can modify permissions');
            if (!$auth->isAdmin()) {   // disable permission change for admin user
                $field->setDisabled();
            }

            $this->form->appendField(new Checkbox('active', ['Enable User Login' => '1']))
                ->setGroup($group);
        }

        $this->form->appendField(new Textarea('notes'))
            ->setGroup($group);


        // Form Actions
        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', $this->getBackUrl()));


        $load = $this->form->unmapModel($this->getUser());
        $load = array_merge($load, $this->form->unmapModel($this->getUser()->getAuth()));
        if ($this->getUser()->getAuth()) {
            $load['perm'] = array_keys(array_filter(User::PERMISSION_LIST,
                    fn($k) => ($k & $this->getUser()->getAuth()->permissions), ARRAY_FILTER_USE_KEY)
            );
        }
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

        if ($this->getAuthUser()->hasPermission(User::PERM_ADMIN) && !empty($newType)) {
            if ($newType == User::TYPE_STAFF) {
                $this->getUser()->type = User::TYPE_STAFF;
                Alert::addSuccess('User now set to type STAFF, please select and save the users new permissions.');
            } else if ($newType == User::TYPE_MEMBER) {
                $this->getUser()->type = User::TYPE_MEMBER;
                Alert::addSuccess('User now set to type MEMBER.');
            }
            $this->getUser()->save();
            Uri::create()->remove('cv')->redirect();
        }

    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        // non admin cannot change permissions
        if (!Factory::instance()->getAuthUser()->isAdmin()) {
            $form->removeField('perm');
        }

        // set object values from fields
        $form->mapModel($this->getUser());
        $form->mapModel($this->getUser()->getAuth());

        if ($form->getField('perm')) {
            $this->getUser()->permissions = array_sum($form->getFieldValue('perm') ?? []);
        }

        $form->addFieldErrors($this->getUser()->validate());
        $form->addFieldErrors($this->getUser()->getAuth()->validate());
        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $isNew = $this->getUser()->userId == 0;

        $this->getUser()->save();
        $this->getUser()->getAuth()->save();

        // Send email to update password
        if ($isNew) {
            if (\App\Email\User::sendRecovery($this->getUser())) {
                Alert::addSuccess('An email has been sent to ' . $this->getUser()->email . ' to create their password.');
            } else {
                Alert::addError('Failed to send email to ' . $this->getUser()->email . ' to create their password.');
            }
        }

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/user/'.$this->type.'Edit')->set('userId', $this->getUser()->userId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        if ($this->getAuthUser()->hasPermission(User::PERM_ADMIN)) {
            if ($this->getUser()->isType(User::TYPE_MEMBER)) {
                $url = Uri::create()->set('cv', User::TYPE_STAFF);
                $template->setAttr('to-staff', 'href', $url);
                $template->setVisible('to-staff');
            } else if ($this->getUser()->isType(User::TYPE_STAFF)) {
                $url = Uri::create()->set('cv', User::TYPE_MEMBER);
                $template->setAttr('to-member', 'href', $url);
                $template->setVisible('to-member');
            }
        }

        $template->appendText('title', $this->getPage()->getTitle());
        if (!$this->getUser()->userId) {
            $template->setVisible('new-user');
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
      <a href="/" title="Convert user to staff" data-confirm="Convert this user to staff" class="btn btn-outline-secondary" choice="to-staff"><i class="fa fa-retweet"></i> Convert To Staff</a>
      <a href="/" title="Convert user to member" data-confirm="Convert this user to member" class="btn btn-outline-secondary" choice="to-member"><i class="fa fa-retweet"></i> Convert To Member</a>
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