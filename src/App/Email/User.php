<?php
namespace App\Email;

use Bs\Db\GuestToken;
use Bs\Factory;
use Tk\Config;
use Bs\Registry;
use Tk\Uri;

class User
{

    public static function sendRegister(\App\Db\User $user): bool
    {
        $content = <<<HTML
            <h2>Account Activation.</h2>
            <p>
              Welcome {name}
            </p>
            <p>
              Please follow the link to create a new password and activate your account.<br/>
              <a href="{activate-url}" target="_blank">{activate-url}</a>
            </p>
            <p><small>Note: If you did not initiate this account creation you can safely disregard this message.</small></p>
        HTML;

        $message = Factory::instance()->createMailMessage();
        $message->set('content', $content);
        $message->setSubject(Registry::instance()->getSiteName() . ' Account Registration');
        $message->addTo($user->email);
        $message->set('name', $user->nameShort);

        $gt = GuestToken::create([
            Uri::create('/registerActivate')->getPath(),
        ], [
            'h' => $user->hash
        ], 60);
        $message->set('activate-url', $gt->getUrl()->toString());

        return Factory::instance()->getMailGateway()->send($message);
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
              Please follow the link to create a new password and activate your account.<br/>
              <a href="{activate-url}" target="_blank">{activate-url}</a>
            </p>
            <p><small>Note: If you did not initiate this email, you can safely disregard this message.</small></p>
        HTML;

        $message = Factory::instance()->createMailMessage();
        $message->set('content', $content);
        $message->setSubject($config->get('site.title') . ' Password Recovery');
        $message->addTo($user->email);
        $message->set('name', $user->nameShort);

        $gt = GuestToken::create([
            Uri::create('/recoverUpdate')->getPath(),
        ], [
            'h' => $user->hash
        ], 20);
        $message->set('activate-url', $gt->getUrl()->toString());

        return Factory::instance()->getMailGateway()->send($message);
    }

}