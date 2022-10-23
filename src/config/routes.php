<?php
/**
 * Remember to refresh the cache after editing
 *
 * Reload the page with <Ctrl>+<Shift>+R
 */
use Symfony\Component\Routing;

return function (Routing\RouteCollection $routes) {

    $routes->add('home', new Routing\Route('/', ['_controller' => '\App\Controller\Home::doDefault']));
    $routes->add('home-path', new Routing\Route('/home', ['_controller' => '\App\Controller\Home::doDefault']));
    $routes->add('install', new Routing\Route('/install', ['_controller' => '\App\Controller\Install::doDefault']));

    // Authentication pages
    $routes->add('login', new Routing\Route('/login', ['_controller' => '\App\Controller\Login::doDefault']));
    $routes->add('logout', new Routing\Route('/logout', ['_controller' => '\App\Controller\Login::doLogout']));

    // Site user Pages
    $routes->add('user-dashboard', new Routing\Route('/dashboard', ['_controller' => '\App\Controller\Dashboard::doDefault']));
    $routes->add('user-manager', new Routing\Route('/userManager', ['_controller' => '\App\Controller\User\Manager::doLogout']));
    $routes->add('user-edit', new Routing\Route('/userEdit/{id}', ['_controller' => '\App\Controller\UserEdit::doLogout']));

    // Test pages
    $routes->add('phpinfo', new Routing\Route('/info', ['_controller' => '\App\Controller\Info::doDefault']));
    $routes->add('dom-test', new Routing\Route('/domTest', ['_controller' => '\App\Controller\DomTest::doDefault']));

};