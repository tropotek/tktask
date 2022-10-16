<?php
namespace App;


use Dom\Mvc\EventListener\PageBytesHandler;
use Dom\Mvc\EventListener\ViewHandler;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Dispatch extends \Tk\Mvc\Dispatch
{

    /**
     * Any Common listeners that are used in both HTTPS or CLI requests
     */
    protected function commonInit()
    {
        parent::commonInit();

        $this->getDispatcher()->addSubscriber(new ViewHandler($this->getFactory()->getTemplateModifier()));
        $this->getDispatcher()->addSubscriber(new PageBytesHandler(
            $this->getFactory()->getLogger(),
            $this->getFactory()->getTemplateModifier()->getFilter('pageBytes')
        ));

    }

    /**
     * Called this when executing http requests
     */
    protected function httpInit()
    {
        parent::httpInit();


    }

    /**
     * Called this when executing Console/CLI requests
     */
    protected function cliInit()
    {
        parent::cliInit();


    }

}