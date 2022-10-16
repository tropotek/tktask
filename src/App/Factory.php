<?php
namespace App;

use App\Ui\AlertRenderer;
use Dom\Mvc\Loader;
use Dom\Mvc\Modifier;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Factory extends \Tk\Factory
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

    public function initEventDispatcher()
    {
        if ($this->getEventDispatcher()) {
            new Dispatch($this->getEventDispatcher());
        }
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
}