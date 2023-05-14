<?php
namespace App\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Traits\SystemTrait;

/**
 *
 */
class AuthHandler implements EventSubscriberInterface
{
    use SystemTrait;


    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @throws \Exception
     */
    public function validatePageAccess($event)
    {
        $config = \Bs\Config::getInstance();
        // TODO: we need to create an Object pattern that can handle page permissions with exceptions etc...
        $urlRole = \Bs\Uri::create()->getRoleType($config->getUserTypeList(true));
        if ($urlRole && $urlRole != 'public') {
            if (!$config->getAuthUser()) {  // if no user and the url has permissions set
                // Save the request URL and redirect once authenticated
                $config->getSession()->set('auth.redirect.url', \Bs\Uri::create()->toString());
                $this->getLoginUrl()->redirect();
            }
            // Finally check if the user has access to the url
            if (!$config->getAuthUser()->hasType($urlRole)) {
                \Tk\Alert::addWarning('1000: You do not have access to the requested page.');
                $config->getUserHomeUrl($config->getAuthUser())->redirect();
            }
        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $auth = $config->getAuth();

        if ($config->getMasqueradeHandler()->isMasquerading()) {
            $config->getMasqueradeHandler()->masqueradeClear();
        }

        $result = null;
        if (!$event->getAdapter()) {
            $adapterList = $config->get('system.auth.adapters');
            $result = null;
            foreach ($adapterList as $name => $class) {
                $event->setAdapter($config->getAuthAdapter($class, $event->all()));
                if (!$event->getAdapter()) continue;
                $result = $auth->authenticate($event->getAdapter());
                if ($result && $result->isValid()) {
                    $event->setResult($result);
                    break;
                }
            }
        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLoginSuccess(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $result = $event->getResult();
        if (!$result || !$result->isValid()) return;

        /* @var \Bs\Db\User $user */
        $user = $config->getUserMapper()->findByAuthIdentity($result->getIdentity());
        if ($user && $user->isActive()) {
            $config->setAuthUser($user);
        }
        if ($config->getSession()->has('auth.redirect.url')) {
            $event->setRedirect(\Bs\Uri::create($config->getSession()->get('auth.redirect.url')));
            $config->getSession()->remove('auth.redirect.url');
        } else if(!$event->getRedirect()) {
            $event->setRedirect(\Bs\Config::getInstance()->getUserHomeUrl($user));
        }
    }

    public function onLoginFailure(AuthEvent $event)
    {

    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function updateUser(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        if ($config->getMasqueradeHandler()->isMasquerading()) return;
        $user = $config->getAuthUser();
        if ($user) {
            $user->lastLogin = \Tk\Date::create();
            $user->save();
        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $auth = $config->getAuth();
        $url = $event->getRedirect();
        if (!$url) {
            $event->setRedirect(\Tk\Uri::create('/'));
        }

        $auth->clearIdentity();
        if (!$config->getMasqueradeHandler()->isMasquerading()) {
            \Tk\Log::warning('Destroying Session');
            $config->getSession()->destroy();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            KernelEvents::REQUEST => [
                ['onRequest', 5],
                ['validatePageAccess', -5],
            ],
            AuthEvents::LOGIN => 'onLogin',
            AuthEvents::LOGIN_SUCCESS => [
                ['onLoginSuccess', 5],
                ['updateUser', 0],
            ],
            AuthEvents::LOGOUT => 'onLogout',
        );
    }

}
