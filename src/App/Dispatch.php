<?php
namespace App;

use App\Listener\AlertHandler;
use App\Listener\RequestHandler;

class Dispatch extends \Bs\Dispatch
{

    /**
     * Any Common listeners that are used in both HTTPS or CLI requests
     */
    protected function commonInit()
    {
        parent::commonInit();


    }

    /**
     * Called this when executing http requests
     */
    protected function httpInit()
    {
        parent::httpInit();

        $this->getDispatcher()->addSubscriber(new RequestHandler());
        $this->getDispatcher()->addSubscriber(new AlertHandler());

    }

    /**
     * Called this when executing Console/CLI requests
     */
    protected function cliInit()
    {
        parent::cliInit();


    }

}