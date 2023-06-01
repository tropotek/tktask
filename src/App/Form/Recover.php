<?php
namespace App\Form;

use App\Db\UserMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Encrypt;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\FormRenderer;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class Recover
{
    use SystemTrait;
    use Form\FormTrait;

    protected ?\App\Db\User $user = null;

    public function __construct()
    {
        // Set a token in the session on show, to ensure this browser is the one that requested the login.
        $this->getSession()->set('recover', time());
        $this->setForm(Form::create('recover'));
    }

    public function doDefault(Request $request)
    {
        $this->getForm()->appendField(new Field\Input('username'))->setAttr('autocomplete', 'off')
            ->setAttr('placeholder', 'Username')
            ->setRequired()->setNotes('Enter your username to recover access your account.');

        $html = <<<HTML
            <a href="/login">Login</a>
        HTML;
        if ($this->getRegistry()->get('site.account.registration', false)) {
            $html = <<<HTML
                <a href="/register">Register</a> | <a href="/login">Login</a>
            HTML;

        }
        $this->getForm()->appendField(new Field\Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->getForm()->appendField(new Action\Submit('recover', [$this, 'onSubmit']));

        $load = [];
        $this->getForm()->setFieldValues($load);

        $this->getForm()->execute($request->request->all());

        $this->setFormRenderer(new FormRenderer($this->getForm()));
    }

    public function onSubmit(Form $form, Action\ActionInterface $action)
    {
        if (!$form->getFieldValue('username')) {
            $form->addError('Please enter a valid username.');
            return;
        }

        $token = $this->getSession()->get('recover', 0);
        $this->getSession()->remove('recover');
        if (($token + 60*2) < time()) { // submit before form token times out
            $form->addError('Invalid form submission, please try again.');
            return;
        }

        $user = UserMap::create()->findByUsername(strtolower($form->getFieldValue('username')));
        if (!$user) {
            $form->addFieldError('username', 'Please enter a valid username.');
            return;
        }

        // send email to user
        $content = <<<HTML
            <h2>Account Recovery.</h2>
            <p>
              Welcome {name}
            </p>
            <p>
              Please follow the link to finish recovering your account.<br/>
              <a href="{activate-url}" target="_blank">{activate-url}</a>
            </p>
            <p><small>Note: If you did not initiate your account recovery you can safely disregard this message.</small></p>
        HTML;

        $message = $this->getFactory()->createMessage();
        $message->set('content', $content);
        $message->setSubject($this->getConfig()->get('site.title') . ' Password Recovery');
        $message->addTo($user->getEmail());
        $message->set('name', $user->getName());

        $hashToken = Encrypt::create($this->getConfig()->get('system.encrypt'))->encrypt(serialize(['h' => $user->getHash(), 't' => time()]));
        $url = Uri::create('/recoverUpdate')->set('t', $hashToken);
        //$url = Uri::create('/recoverUpdate/'.urlencode($hashToken));
        $message->set('activate-url', $url->toString());

        $this->getFactory()->getMailGateway()->send($message);

        Alert::addSuccess('Please check your email for instructions to recover your account.');
        Uri::create('/home')->redirect();
    }

    public function doRecover(Request $request)
    {
        //$token = $request->get('t');        // Bug in here that replaces + with a space on POSTS
        $token = $_REQUEST['t'] ?? '';
        $arr = Encrypt::create($this->getConfig()->get('system.encrypt'))->decrypt($token);
        $arr = unserialize($arr);
        if (!is_array($arr)) {
            Alert::addError('Unknown account recovery error, please try again.');
            Uri::create('/home')->redirect();
        }

        if ((($arr['t'] ?? 0) + 60*60*1) < time()) { // submit before form token times out
        //if ((($arr['t'] ?? time()) + 60*1) < time()) { // submit before form token times out
            Alert::addError('Recovery URL has expired, please try again.');
            Uri::create('/home')->redirect();
        }

        $this->user = UserMap::create()->findByHash($arr['h'] ?? '');
        if (!$this->user) {
            Alert::addError('Invalid user token');
            Uri::create('/home')->redirect();
        }

        $this->getForm()->appendField(new Field\Hidden('t'));
        $this->getForm()->appendField(new Field\Password('newPassword'))->setLabel('Password')
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')->setRequired();
        $this->getForm()->appendField(new Field\Password('confPassword'))->setLabel('Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')->setRequired();

        $this->getForm()->appendField(new Action\Submit('recover-update', [$this, 'onRecover']));

        $load = [
            't' => $token
        ];
        $this->getForm()->setFieldValues($load);

        $this->getForm()->execute($request->request->all());
    }

    public function onRecover(Form $form, Action\ActionInterface $action)
    {
        if (!$form->getFieldValue('newPassword')  || $form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('newPassword');
            $form->addFieldError('confPassword');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            if (!$this->getConfig()->isDebug()) {
                $errors = \App\Db\User::checkPassword($form->getFieldValue('newPassword'));
                if (count($errors)) {
                    $form->addFieldError('confPassword', implode('<br/>', $errors));
                }
            }
        }

        if ($form->hasErrors()) {
            return;
        }

        $this->user->setPassword(\App\Db\User::hashPassword($form->getFieldValue('newPassword')));
        $this->user->save();

        Alert::addSuccess('Successfully account recovery. Please login.');
        Uri::create('/login')->redirect();
    }

    public function show(): ?Template
    {
        return $this->getFormRenderer()->show();
    }

}