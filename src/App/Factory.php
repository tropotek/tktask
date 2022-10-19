<?php
namespace App;

use App\Ui\AlertRenderer;
use Dom\Mvc\Loader;
use Dom\Mvc\Modifier;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Auth\Adapter\AdapterInterface;
use Tk\Auth\Auth;
use Tk\Auth\FactoryInterface;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Factory extends \Tk\Factory implements FactoryInterface
{

    public function getPublicPage(): Page
    {
        $page = Page::create($this->getConfig()->getTemplatePath() . '/public.html');
        $page->addRenderer(new AlertRenderer(), 'alert');
        return $page;
    }

    public function getUserPage(): Page
    {
        $page = Page::create($this->getConfig()->getTemplatePath() . '/public.html');
        $page->addRenderer(new AlertRenderer(), 'alert');

        // PagePermission Check
        if (!$this->getAuthUser()) {
            $this->getSession()->getFlashBag()->add('error', 'You do not have permissions to access this page.');
            \Tk\Uri::create('/')->redirect();
        }
        return $page;
    }

    public function getLoginPage(): Page
    {
        $page = Page::create($this->getConfig()->getTemplatePath() . '/login.html');
        $page->addRenderer(new AlertRenderer(), 'alert');
        return $page;
    }

    public function initEventDispatcher(): ?EventDispatcher
    {
        if ($this->getEventDispatcher()) {
            new Dispatch($this->getEventDispatcher());
        }
        return $this->getEventDispatcher();
    }

    public function getTemplateLoader(): ?Loader
    {
        if (!$this->has('templateLoader')) {
            $loader = new Loader($this->getEventDispatcher());
            $path = $this->getConfig()->getTemplatePath() . '/templates';
            $loader->addAdapter(new Loader\DefaultAdapter());
            $loader->addAdapter(new Loader\ClassPathAdapter($path));
            $this->set('templateLoader', $loader);
        }
        return $this->get('templateLoader');
    }

    public function getTemplateModifier(): Modifier
    {
        if (!$this->get('templateModifier')) {
            $dm = new Modifier();

            if (class_exists('ScssPhp\ScssPhp\Compiler')) {
                $vars = [
                    'baseUrl' => $this->getConfig()->getBaseUrl(),
                    'dataUrl' => $this->getSystem()->makeUrl($this->getConfig()->getDataPath())
                ];
                $scss = new Modifier\Scss($this->getConfig()->getBasePath(), $this->getConfig()->getBaseUrl(), $this->getConfig()->getCachePath(), $vars);
                $scss->setCompress(true);
                $scss->setCacheEnabled(!$this->getSystem()->isRefreshCacheRequest());
                $scss->setCacheTimeout(\Tk\Date::DAY*14);
                $dm->addFilter('scss', $scss);
            }

            $dm->addFilter('urlPath', new Modifier\UrlPath($this->getConfig()->getBaseUrl()));
            $dm->addFilter('jsLast', new Modifier\JsLast());
            if ($this->getConfig()->isDebug()) {
                $dm->addFilter('pageBytes', new Modifier\PageBytes($this->getConfig()->getBasePath()));
            }

            $this->set('templateModifier', $dm);
        }
        return $this->get('templateModifier');
    }

    public function getAuthController(): Auth
    {
        if (!$this->has('authController')) {
            $auth = new Auth(new \Tk\Auth\Storage\SessionStorage($this->getSession()));
            $this->set('authController', $auth);
        }
        return $this->get('authController');
    }

    /**
     * This is the default Authentication adapter
     * Override this method in your own site's Factory object
     */
    public function getAuthAdapter(): AdapterInterface
    {
        if (!$this->has('authAdapter')) {
            $adapter = new \Tk\Auth\Adapter\Config('admin', hash('md5', 'password'));
            $this->set('authAdapter', $adapter);
        }
        return $this->get('authAdapter');
    }

    /**
     * Return a User object or record that is located from the Auth's getIdentity() method
     * Override this method in your own site's Factory object
     * @return null|mixed Null if no user logged in
     */
    public function getAuthUser()
    {
        if (!$this->has('authUser')) {
            if ($this->getAuthController()->hasIdentity()) {
                $user = $this->getAuthController()->getIdentity();
                $this->set('authUser', $user);
            }
        }
        return $this->get('authUser');
    }
}