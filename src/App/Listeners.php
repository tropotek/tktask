<?php
namespace App;

use App\Db\User;
use Bs\Listener\MaintenanceHandler;
use Bs\Listener\RememberHandler;

class Listeners extends \Bs\Listeners
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