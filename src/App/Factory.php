<?php
namespace App;

use App\Db\User;
use App\Db\UserMap;
use App\Dom\Modifier\AppAttributes;
use Bs\Db\UserInterface;
use Dom\Mvc\Loader;
use Dom\Mvc\Modifier;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Auth\Adapter\AdapterInterface;
use Tk\Auth\Auth;
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

    public function createPage($templatePath, callable $onCreate = null): Page
    {
        $page = Page::create($templatePath);
        if ($onCreate) {
            call_user_func_array($onCreate, [$page]);
        }
        return $page;
    }

    public function getTemplateModifier(): Modifier
    {
        if (!$this->get('templateModifier')) {
            $dm = parent::getTemplateModifier();
            $dm->addFilter('appAttributes', new AppAttributes());
        }
        return $this->get('templateModifier');
    }

    /**
     * Return a User object or record that is located from the Auth's getIdentity() method
     * Override this method in your own site's Factory object
     * @return mixed|UserInterface|User Null if no user logged in
     */
    public function getAuthUser(): mixed
    {
        if (!$this->has('authUser')) {
            if ($this->getAuthController()->hasIdentity()) {
                $user = UserMap::create()->findByUsername($this->getAuthController()->getIdentity());
                $this->set('authUser', $user);
            }
        }
        return $this->get('authUser');
    }

    /**
     * This is the default Authentication adapter
     * Override this method in your own site's Factory object
     */
    public function getAuthAdapter(): AdapterInterface
    {
        return parent::getAuthAdapter();

//        if (!$this->has('authAdapter')) {
//            $adapter = new \Tk\Auth\Adapter\Config('admin', hash('md5', 'password'));
//            $this->set('authAdapter', $adapter);
//        }
//        return $this->get('authAdapter');
    }
}