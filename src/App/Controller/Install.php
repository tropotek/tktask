<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Install extends PageController
{


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->getTemplate()->setTitleText('Login');
    }

    public function doDefault(Request $request)
    {
        $this->composerInstall();
        return $this->getPage();
    }

    /**
     * @return null
     * @see https://stackoverflow.com/questions/27485665/how-can-i-install-composer-via-php-script
     */
    private function composerInstall()
    {
        $installerFilename = \Tk\Config::instance()->getTempPath()."/composer-installer.php";
        $composer_installer_content  = file_get_contents('https://getcomposer.org/installer');
        $find = array('#!/usr/bin/env php', 'exit(',' print');
        $replace = array('', 'return(',' //print');
        $new_composer_installer_content = str_replace($find,$replace, $composer_installer_content);
        file_put_contents($installerFilename, $new_composer_installer_content);
        $argv = array(
            '--ignore-platform-reqs',
            '--install-dir='.\Tk\Config::instance()->getTempPath(),
            // '--filename=composer.phar',
            // '--version=1.0.0-alpha8'
        );
        if (\Tk\Config::instance()->isDebug()) {
            $argv[] = '--prefer-source';
        }
        include_once($installerFilename);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();



        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <p>Installing...</p>
</div>
HTML;
        return $this->getFactory()->getTemplateLoader()->load($html);
    }

}


