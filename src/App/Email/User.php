<?php
namespace App\Email;

use Bs\Db\GuestToken;
use Bs\Factory;
use Tk\Config;
use Bs\Registry;
use Tk\Mail\Mailer;
use Tk\Uri;

class User
{

    public static function sendWelcome(\App\Db\User $user, bool $isSsi = false): bool
    {
        $config = Config::instance();

        $content = <<<HTML
            <h2>Account Created And Activated.</h2>
            <p>
              Welcome {name}
            </p>
            <p>
              Your account with the username "{username}" has been successfully created and activated.<br/>
              To log in to your new account, visit: <a href="{home-url}" target="_blank">{home-url}</a>
            </p>
        HTML;

        $message = Factory::instance()->createMailMessage($content);
        $message->setSubject($config->get('site.title') . ' New Account Created');;
        $message->addTo($user->email);
        $message->set('name', $user->nameShort);
        $message->set('home-url', $user->getHomeUrl()->toString());
        $message->set('username', $user->username);

        return Mailer::instance()->send($message);
    }

    public static function sendRegister(\App\Db\User $user): bool
    {
        $content = <<<HTML
            <h2>Account Registration.</h2>
            <p>
              Welcome {name}
            </p>
            <p>
              Please follow the link to create a new password and activate your account with the username "{username}".<br/>
              <a href="{activate-url}" target="_blank">{activate-url}</a>
            </p>
            <p><small>Note: If you did not initiate this account creation, you can safely disregard this message.</small></p>
        HTML;

        $message = Factory::instance()->createMailMessage($content);
        $message->setSubject(Registry::getSiteName() . ' Account Registration');
        $message->addTo($user->email);
        $message->set('name', $user->nameShort);
        $message->set('username', $user->username);

        $gt = GuestToken::create([
            Uri::create('/registerActivate')->getPath(),
        ], [
            'h' => $user->hash
        ], 60);
        $message->set('activate-url', $gt->getUrl()->toString());

        return Mailer::instance()->send($message);
    }


    public static function sendRecovery(\App\Db\User $user): bool
    {
        $config = Config::instance();

        $content = <<<HTML
            <h2>Account Recovery.</h2>
            <p>
              Welcome {name}
            </p>
            <p>
              Please follow the link to create a new password and activate your account with the username "{username}".<br/>
              <a href="{activate-url}" target="_blank">{activate-url}</a>
            </p>
            <p><small>Note: If you did not initiate this email, you can safely disregard this message.</small></p>
        HTML;

        $message = Factory::instance()->createMailMessage($content);
        $message->setSubject($config->get('site.title') . ' Account Recovery');
        $message->addTo($user->email);
        $message->set('name', $user->nameShort);
        $message->set('username', $user->username);
        vd($user);

        $gt = GuestToken::create([
            Uri::create('/recoverUpdate')->getPath(),
        ], [
            'h' => $user->hash
        ], 20);
        $message->set('activate-url', $gt->getUrl()->toString());

        return Mailer::instance()->send($message);
    }

}