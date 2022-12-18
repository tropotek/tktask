<?php
namespace App;

use Dom\Mvc\Loader;
use Dom\Mvc\Modifier;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Auth\Adapter\AdapterInterface;
use Tk\Auth\Auth;
use Tk\Auth\FactoryInterface;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
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