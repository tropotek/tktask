<?php
namespace App;

use App\Db\Company;
use App\Db\Product;
use App\Db\User;
use Bs\Mvc\PageDomInterface;
use Bs\Registry;
use Bs\Ui\Breadcrumbs;
use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Config;
use Tk\Path;
use Tk\Uri;

class Factory extends \Bs\Factory
{

    public function initEventDispatcher(): ?EventDispatcher
    {
        if ($this->getEventDispatcher()) {
            new Dispatch($this->getEventDispatcher());
        }
        return $this->getEventDispatcher();
    }

//    public function createDomPage(string $templatePath = ''): PageDomInterface
//    {
//        // So we can change the mintion template from the settings page
//        if (str_contains($templatePath, '/minton/')) {
//            $templatePath = Path::create(Registry::getValue('minton.template', '/html/minton/sn-admin.html'));
//        }
//        return new Page($templatePath);
//    }

    public function createDomPage(string $templatePath = ''): PageDomInterface
    {
        // So we can change the mintion template from the settings page
        if (str_contains($templatePath, '/minton/')) {
            $selected = Registry::getValue('minton.template', 'sn-admin');
            if (User::getAuthUser()?->template) {
                $selected = User::getAuthUser()->template;
            }
            $templatePath = Path::create(sprintf('/html/minton/%s.html', preg_replace('|[^0-9a-z_-]|i', '', $selected)));
            if (!is_file($templatePath)) {
                $templatePath = Path::create('/html/minton/sn-admin.html');
            }
        }
        return new Page($templatePath);
    }

    /**
     * Get the owner company for this website
     * @todo: Move this to the Company object
     */
    public function getOwnerCompany(): Company
    {
        if (!$this->get('site.owner.company')) {
            $cid = 1;
            if (Registry::instance()->has('site.company.id')) {
                $cid = (int)Registry::getValue('site.company.id');
            }
            $company = Company::find($cid);
            if ($company instanceof Company) {
                $this->set('site.owner.company', $company);
            }
        }
        return $this->get('site.owner.company');
    }

    /**
     * @todo: Move this to the Company object
     */
    public function isOwnerCompany(Company $company): bool
    {
        if (
            Registry::instance()->has('site.company.id') &&
            Registry::getValue('site.company.id') == $company->companyId
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
                //$app->add(new \App\Console\MigrateTis());
                $app->add(new \App\Console\Test());
            }
        }
        return $this->get('console');
    }

}