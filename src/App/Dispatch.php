<?php
namespace App;


use App\Listener\RequestHandler;
use Dom\Mvc\EventListener\PageBytesHandler;
use Dom\Mvc\EventListener\ViewHandler;
use Dom\Mvc\Modifier\PageBytes;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
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

    }

    /**
     * Called this when executing Console/CLI requests
     */
    protected function cliInit()
    {
        parent::cliInit();


    }

}