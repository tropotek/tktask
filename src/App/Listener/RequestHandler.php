<?php
namespace App\Listener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Log;
use Tk\Traits\SystemTrait;

class RequestHandler implements EventSubscriberInterface
{
    use SystemTrait;

    public function onRequest(RequestEvent $event)
    {
        // TODO: Check user still logged in, if not use any remember me cookies to auto login and redirect to back to this URI
        if (!$this->getFactory()->getAuthUser()) {
            $user = \App\Db\User::retrieveMe();
            if ($user) {
                Log::alert('user `' . $user->getUsername() . '` auto logged in via cookie');
            }
        }


        // TODO: Check if maintenance mode is enabled then redirect to appropriate URI

        //\Tk\Log::emergency('TODO: HTTP implement RequestHandler....');

    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

}