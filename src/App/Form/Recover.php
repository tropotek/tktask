<?php
namespace App\Form;

use App\Db\UserMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Encrypt;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Form\Field\Input;
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
        $this->getForm()->appendField(new Input('username'))->setAttr('autocomplete', 'off')
            ->setRequired()->setNotes('Enter your username to recover access your account.');

        $html = <<<HTML
            <a href="/login">Login</a> | <a href="/register">Register</a>
        HTML;
        $this->getForm()->appendField(new Form\Field\Html('links', $html))->setLabel('');
        $this->getForm()->appendField(new Form\Action\Submit('recover', [$this, 'onSubmit']))->addCss('w-100');

        $load = [];
        $this->getForm()->setFieldValues($load);

        $this->getForm()->execute($request->request->all());
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        if (!$form->getFieldValue('username')) {
            $form->addFieldError('username', 'Please enter a valid username.');
            return;
        }

        $token = $this->getSession()->get('recover', 0);
        $this->getSession()->remove('recover');
        if (($token + 60*2) < time()) { // submit before form token times out
            $form->addFieldError('username', 'Invalid form submission, please try again.');
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
        $message->set('activate-url', $url->toString());

        $this->getFactory()->getMailGateway()->send($message);

        $this->getFactory()->getSession()->getFlashBag()->add('success', 'Please check your email for instructions to recover your account.');
        Uri::create('/home')->redirect();
    }

    public function doRecover(Request $request)
    {
        //$token = $request->get('t');        // Bug in here that replaces + with a space on POSTS
        $token = $_REQUEST['t'] ?? '';
        $arr = Encrypt::create($this->getConfig()->get('system.encrypt'))->decrypt($token);
        $arr = unserialize($arr);
        vd($arr);
        if (!is_array($arr)) {
            $this->getFactory()->getSession()->getFlashBag()->add('danger', 'Unknown account recovery error, please try again.');
            Uri::create('/home')->redirect();
        }

        if ((($arr['t'] ?? 0) + 60*60*8) < time()) { // submit before form token times out
        //if ((($arr['t'] ?? time()) + 60*1) < time()) { // submit before form token times out
            $this->getFactory()->getSession()->getFlashBag()->add('danger', 'Recovery URL has expired, please try again.');
            Uri::create('/home')->redirect();
        }

        $this->user = UserMap::create()->findByHash($arr['h'] ?? '');
        if (!$this->user) {
            $this->getFactory()->getSession()->getFlashBag()->add('danger', 'Invalid user token');
            Uri::create('/home')->redirect();
        }

        $this->getForm()->appendField(new Form\Field\Hidden('t'));
        $this->getForm()->appendField(new Input('newPassword'))->setLabel('Password')
            ->setAttr('autocomplete', 'off')->setRequired();
        $this->getForm()->appendField(new Input('confPassword'))->setLabel('Confirm')
            ->setAttr('autocomplete', 'off')->setRequired();

        $this->getForm()->appendField(new Form\Action\Submit('recover-update', [$this, 'onRecover']))->addCss('w-100');

        $load = [
            't' => $token
        ];
        $this->getForm()->setFieldValues($load);

        $this->getForm()->execute($request->request->all());
    }

    public function onRecover(Form $form, Form\Action\ActionInterface $action)
    {
        if (!$form->getFieldValue('newPassword')  || $form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('newPassword');
            $form->addFieldError('confPassword');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            if (!$this->getConfig()->isDebug()) {
                $errors = $this->checkPassword($form->getFieldValue('newPassword'));
                if (count($errors)) {
                    $form->addFieldError('confPassword', implode('<br/>', $errors));
                }
            }
        }

        if ($form->hasErrors()) {
            return;
        }

        $this->user->setPassword(password_hash($form->getFieldValue('newPassword'), PASSWORD_DEFAULT));
        $this->user->save();

        $this->getFactory()->getSession()->getFlashBag()->add('success', 'Successfully account recovery. Please login.');
        Uri::create('/login')->redirect();
    }

    protected function checkPassword(string $pwd, array &$errors = []): array
    {
        if (strlen($pwd) < 8) {
            $errors[] = "Password too short!";
        }

        if (!preg_match("#[0-9]+#", $pwd)) {
            $errors[] = "Must include at least one number!";
        }

        if (!preg_match("#[a-zA-Z]+#", $pwd)) {
            $errors[] = "Must include at least one letter!";
        }

        if( !preg_match("#[A-Z]+#", $pwd) ) {
            $errors[] = "Must include at least one Capital!";
        }

        if( !preg_match("#\W+#", $pwd) ) {
            $errors[] = "Must include at least one symbol!";
        }

        return $errors;
    }

    public function show(): ?Template
    {
        $renderer = new FormRenderer($this->getForm());

        return $renderer->show();
    }

}