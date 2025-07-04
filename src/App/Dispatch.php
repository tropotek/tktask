<?php
namespace App;

use App\Db\User;
use Bs\Listener\RememberHandler;
use Bs\Listener\MaintenanceHandler;

class Dispatch extends \Bs\Dispatch
{

    /**
     * Any Common listeners that are used in both HTTPS or CLI requests
     */
    protected function commonInit(): void
    {
        parent::commonInit();
    }

    /**
     * Called this when executing http requests
     */
    protected function httpInit(): void
    {
        parent::httpInit();

        $this->getDispatcher()->addSubscriber(new MaintenanceHandler());
        $this->getDispatcher()->addSubscriber(new RememberHandler(User::getHomeUrl()));
    }

    /**
     * Called this when executing Console/CLI requests
     */
    protected function cliInit(): void
    {
        parent::cliInit();
    }

}