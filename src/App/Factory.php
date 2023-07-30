<?php
namespace App;

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

    public function createPage($templatePath = '', callable $onCreate = null): Page
    {
        $page = Page::create($templatePath);
        if ($onCreate) {
            call_user_func_array($onCreate, [$page]);
        }
        return $page;
    }

    public function getAdminPage(): Page
    {
        $path = $this->getConfig()->get('path.template.admin');
        if (str_contains($path, '/minton/')) {
            $path = $this->getRegistry()->get('minton.template', $path);
        }
        return $this->createPage($this->getSystem()->makePath($path));
    }

}