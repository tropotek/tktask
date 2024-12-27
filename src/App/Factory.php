<?php
namespace App;

use App\Db\Company;
use App\Db\Product;
use Bs\Mvc\PageDomInterface;
use Bs\Registry;
use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Config;

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
            $templatePath = Config::makePath($this->getRegistry()->get('minton.template', '/html/minton/sn-admin.html'));
        }
        return new Page($templatePath);
    }

    /**
     * Get the owner company for this website
     */
    public function getOwnerCompany(): Company
    {
        if (!$this->get('site.owner.company')) {
            $cid = 1;
            if (Registry::instance()->has('site.company.id')) {
                $cid = (int)Registry::instance()->get('site.company.id');
            }
            $company = Company::find($cid);
            if ($company instanceof Company) {
                $this->set('site.owner.company', $company);
            }
        }
        return $this->get('site.owner.company');
    }

    public function isOwnerCompany(Company $company): bool
    {
        if (
            Registry::instance()->has('site.company.id') &&
            Registry::instance()->get('site.company.id') == $company->companyId
        ) {
            return true;
        }
        return false;
    }

    public function getConsole(): Application
    {
        if (!$this->has('console')) {
            $app = parent::getConsole();

            $app->add(new \App\Console\Cron());
            if (Config::isDev()) {
                $app->add(new \App\Console\Test());
            }
        }
        return $this->get('console');
    }

}