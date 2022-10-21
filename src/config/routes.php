<?php
/**
 * Remember to refresh the cache after editing
 *
 * Reload the page with <Ctrl>+<Shift>+R
 */
use Symfony\Component\Routing;

$routes = \Tk\Factory::instance()->getRouteCollection();

$routes->add('home',      new Routing\Route('/',     ['_controller' => '\App\Controller\Home::doDefault']));
$routes->add('home-path', new Routing\Route('/home', ['_controller' => '\App\Controller\Home::doDefault']));
$routes->add('install',   new Routing\Route('/install',     ['_controller' => '\App\Controller\Install::doDefault']));

$routes->add('phpinfo',   new Routing\Route('/info', ['_controller' => '\App\Controller\Info::doDefault']));
$routes->add('dom-test',  new Routing\Route('/domTest',     ['_controller' => '\App\Controller\DomTest::doDefault']));


$routes->add('user-login',     new Routing\Route('/login',     ['_controller' => '\App\Controller\Login::doDefault']));
$routes->add('user-login',     new Routing\Route('/login',     ['_controller' => '\App\Controller\Login::doDefault']));
$routes->add('user-logout',    new Routing\Route('/logout',    ['_controller' => '\App\Controller\Login::doLogout']));

$routes->add('user-dashboard', new Routing\Route('/dashboard',      ['_controller' => '\App\Controller\Dashboard::doDefault']));
$routes->add('user-manager',   new Routing\Route('/userManager',    ['_controller' => '\App\Controller\User\Manager::doLogout']));
$routes->add('user-edit',      new Routing\Route('/userEdit/{id}',  ['_controller' => '\App\Controller\UserEdit::doLogout']));


