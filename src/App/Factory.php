<?php
namespace App;

use Bs\PageDomInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Auth\FactoryInterface;

class Factory extends \Bs\Factory implements FactoryInterface
{

    public function initEventDispatcher(): ?EventDispatcher
    {
        if ($this->getEventDispatcher()) {
            new Dispatch($this->getEventDispatcher());
        }
        return $this->getEventDispatcher();
    }

    public function createDomPage(string $templatePath = ''): PageDomInterface
    {
        // So we can change the mintion template from the settings page
        if (str_contains($templatePath, '/minton/')) {
            $templatePath = $this->makePath($this->getRegistry()->get('minton.template', '/html/minton/sn-admin.html'));
        }
        return new Page($templatePath);
    }

}