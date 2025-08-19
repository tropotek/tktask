<?php
namespace App;

use App\Db\Company;
use App\Db\User;
use Bs\Auth;
use Bs\Db\UserInterface;
use Bs\Mvc\PageDomInterface;
use Bs\Registry;
use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Config;
use Tk\Path;

class Factory extends \Bs\Factory
{

    public function initEventDispatcher(): ?EventDispatcher
    {
        if (!$this->has('eventDispatcher')) {
            new Listeners($this->getEventDispatcher());
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

    /**
     *
     */
    public function createNewUser(string $username, string $email, string $password, int $perms = 0, string $type = ''): ?UserInterface
    {
        $user = new User();
        $user->givenName = ucfirst($username);
        $user->type = $type ?: User::TYPE_STAFF;
        $user->country = 'AU';
        $user->save();

        $auth = Auth::create($user);
        $auth->username = $username;
        $auth->email = $email;
        $auth->permissions = $perms;
        $auth->password = Auth::hashPassword($password);
        $auth->save();

        return $user;
    }

}