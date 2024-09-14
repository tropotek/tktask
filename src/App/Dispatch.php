<?php
namespace App;


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
    }

    /**
     * Called this when executing Console/CLI requests
     */
    protected function cliInit(): void
    {
        parent::cliInit();
    }

}