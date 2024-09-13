<?php
namespace App;

use Bs\PageDomInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Auth\FactoryInterface;
use Tk\System;

class Factory extends \Bs\Factory
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
            $templatePath = System::makePath($this->getRegistry()->get('minton.template', '/html/minton/sn-admin.html'));
        }
        return new Page($templatePath);
    }

    public function getConsole(): Application
    {
        if (!$this->has('console')) {
            $app = parent::getConsole();
            // Setup App Console Commands
            $app->add(new \App\Console\Cron());
            if ($this->getConfig()->isDev()) {
                $app->add(new \App\Console\TestData());
                $app->add(new \App\Console\Test());
            }
        }
        return $this->get('console');
    }

}