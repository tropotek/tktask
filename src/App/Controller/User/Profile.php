<?php
namespace App\Controller\User;

use App\Db\User;
use Au\Auth;
use Bs\ControllerAdmin;
use Bs\Factory;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Config;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Form\Field\Select;
use Tk\Uri;

class Profile extends ControllerAdmin
{

    protected ?Form $form = null;
    protected ?User $user = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('My Profile');

        if (!Auth::getAuthUser()) {
            Alert::addError('You do not have access to this page.');
            Uri::create('/')->redirect();
        }

        // Get the form template
        /** @var User $user */
        $this->user = Auth::getAuthUser()->getDbModel();
        $this->form = new Form($this->user);

        $tab = 'Details';
        $this->form->appendField(new Hidden('userId'))->setReadonly();

        $list = User::TITLE_LIST;
        $this->form->appendField(new Select('title', $list))
            ->setGroup($tab)
            ->setLabel('Title')
            ->prependOption('', '');

        $this->form->appendField(new Input('givenName'))
            ->setGroup($tab)
            ->setRequired();

        $this->form->appendField(new Input('familyName'))
            ->setGroup($tab)
            ->setRequired();

        $this->form->appendField(new Input('username'))->setGroup($tab)
            ->setDisabled()
            ->setReadonly()
            ->setRequired();

        $this->form->appendField(new Input('email'))->setGroup($tab)
            ->addCss('tk-input-lock')
            ->setRequired()
            ->setRequired();

        if ($this->user->isType(User::TYPE_STAFF)) {
            $list = array_flip(User::PERMISSION_LIST);
            $this->form->appendField(new Checkbox('perm', $list))
                ->setGroup('Permissions')
                ->setDisabled()
                ->setReadonly();
        }

        if (Config::instance()->get('auth.profile.password')) {
            $tab = 'Password';
            $this->form->appendField(new Password('currentPass'))->setGroup($tab)
                ->setLabel('Current Password')
                ->setAttr('autocomplete', 'new-password');
            $this->form->appendField(new Password('newPass'))->setGroup($tab)
                ->setLabel('New Password')
                ->setAttr('autocomplete', 'new-password');
            $this->form->appendField(new Password('confPass'))->setGroup($tab)
                ->setLabel('Confirm Password')
                ->setAttr('autocomplete', 'new-password');
        }

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Factory::instance()->getBackUrl()));

        // Load form with object values
        $load = $this->form->unmapModel($this->user);
        if ($this->user->getAuth()) {
            $load['perm'] = array_keys(array_filter(User::PERMISSION_LIST,
                    fn($k) => ($k & $this->user->getAuth()->permissions), ARRAY_FILTER_USE_KEY)
            );
        }
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        // set object values from fields
        $form->mapModel($this->user);
        $form->mapModel($this->user->getAuth());

        if ($form->getField('currentPass') && $form->getFieldValue('currentPass')) {
            if (!password_verify($form->getFieldValue('currentPass'), $this->user->getAuth()->password)) {
                $form->addFieldError('currentPass', 'Invalid current password, password not updated');
            }
            if ($form->getField('newPass') && $form->getFieldValue('newPass')) {
                if ($form->getFieldValue('newPass') != $form->getFieldValue('confPass')) {
                    $form->addFieldError('newPass', 'Passwords do not match');
                } else {
                    if (!$e = Auth::validatePassword($form->getFieldValue('newPass'))) {
                        $form->addFieldError('newPass', 'Week password: ' . implode(', ', $e));
                    }
                }
            } else {
                $form->addFieldError('newPass', 'Please supply a new password');
            }
        }

        $form->addFieldErrors($this->user->validate());
        $form->addFieldErrors($this->user->getAuth()->validate());

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }
        if ($form->getFieldValue('currentPass')) {
            $this->user->getAuth()->password = Auth::hashPassword($form->getFieldValue('newPass'));
            Alert::addSuccess('Your password has been updated, remember to use this on your next login.');
        }
        $this->user->save();
        $this->user->getAuth()->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/profile'));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Uri::create('/'));
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $this->form->getField('title')->addFieldCss('col-1');
        $this->form->getField('givenName')->addFieldCss('col-5');
        $this->form->getField('familyName')->addFieldCss('col-6');

        $this->form->getField('username')->addFieldCss('col-6');
        $this->form->getField('email')->addFieldCss('col-6');
        $this->form->getRenderer()->addFieldCss('mb-3');
        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-user"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}