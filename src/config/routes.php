<?php
/**
 * Remember to refresh the cache after editing
 *
 * Reload the page with <Ctrl>+<Shift>+R
 */
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

// @see https://symfony.com/doc/current/routing.html
return function (CollectionConfigurator $routes) {

    $routes->add('index', '/')
        ->controller([\App\Controller\Home::class, 'doDefault']);
    $routes->add('home', '/home')
        ->controller([\App\Controller\Home::class, 'doDefault']);
    $routes->add('install', '/install')
        ->controller([\App\Controller\Install::class, 'doDefault']);

    $routes->add('login', '/login')
        ->controller([\App\Controller\Login::class, 'doDefault']);
    $routes->add('logout', '/logout')
        ->controller([\App\Controller\Login::class, 'doLogout']);


    $routes->add('user-dashboard', '/dashboard')
        ->controller([\App\Controller\Dashboard::class, 'doDefault']);

    $routes->add('user-manager', '/userManager')
        ->controller([\App\Controller\User\Manager::class, 'doDefault']);
    $routes->add('user-edit', '/userEdit/{id}')
        ->controller([\App\Controller\User\Edit::class, 'doDefault'])
        ->defaults(['id' => 0]);


    $routes->add('phpinfo', '/info')
        ->controller([\App\Controller\Info::class, 'doDefault']);
    $routes->add('dom-test', '/domTest')
        ->controller([\App\Controller\DomTest::class, 'doDefault']);
};